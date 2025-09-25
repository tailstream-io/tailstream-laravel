<?php

declare(strict_types=1);

namespace Tailstream\LaravelLogger\Batch;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tailstream\LaravelLogger\Jobs\SendToTailstreamJob;
use Throwable;

class CacheBatchProcessor
{
    private const CACHE_KEY_PREFIX = 'tailstream_logger_batch';

    private const FLUSH_LOCK_KEY = 'tailstream_logger_flush_lock';

    private const LOCK_TIMEOUT = 30;

    public function __construct(
        private CacheRepository $cache,
        private ConfigRepository $config
    ) {}

    public function add(array $logEntry): void
    {
        $cacheKey = $this->getBatchCacheKey();
        $batch = $this->cache->get($cacheKey, []);

        $batch[] = $logEntry;

        $this->cache->put($cacheKey, $batch, $this->getCacheTtl());

        if ($this->shouldFlush($batch)) {
            $this->flush();

            return;
        }
    }

    public function flush(): bool
    {
        $lockKey = self::FLUSH_LOCK_KEY;

        if (! $this->cache->add($lockKey, true, self::LOCK_TIMEOUT)) {
            return false;
        }

        try {
            $cacheKey = $this->getBatchCacheKey();
            $batch = $this->cache->get($cacheKey, []);

            if (empty($batch)) {
                return true;
            }

            $this->cache->forget($cacheKey);

            $this->processBatch($batch);

            return true;
        } catch (Throwable $e) {
            Log::error('Failed to flush Tailstream logger batch', [
                'error' => $e->getMessage(),
            ]);

            return false;
        } finally {
            $this->cache->forget($lockKey);
        }
    }

    public function getBatchSize(): int
    {
        $cacheKey = $this->getBatchCacheKey();
        $batch = $this->cache->get($cacheKey, []);

        return count($batch);
    }

    public function clearBatch(): void
    {
        $cacheKey = $this->getBatchCacheKey();
        $this->cache->forget($cacheKey);
    }

    private function shouldFlush(array $batch): bool
    {
        $batchSize = $this->config->get('tailstream-logger.batch_size', 50);

        return count($batch) >= $batchSize;
    }

    private function processBatch(array $batch): void
    {
        try {
            if ($this->config->get('tailstream-logger.use_queue', false)) {
                $this->dispatchToQueue($batch);
            } else {
                $this->sendToTailstream($batch);
            }
        } catch (Throwable $e) {
            Log::error('Failed to send logs to destinations', [
                'error' => $e->getMessage(),
                'batch_size' => count($batch),
            ]);
        }
    }

    private function sendToTailstream(array $batch): void
    {
        // Build Tailstream API URL from stream credentials
        $streamUuid = $this->config->get('tailstream-logger.stream_uuid');
        $streamSecret = $this->config->get('tailstream-logger.stream_secret');

        if (! $streamUuid || ! $streamSecret) {
            Log::error('Tailstream - not sending logs because stream UUID or secret is not set');

            return;
        }

        $baseUrl = $this->config->get('tailstream-logger.base_url');

        $url = "{$baseUrl}/ingest/{$streamUuid}";

        // Convert batch to NDJSON format (newline-delimited JSON)
        $ndjsonPayload = collect($batch)
            ->map(fn ($entry) => json_encode($entry))
            ->join("\n");

        $response = Http::timeout(5)
            ->withHeaders([
                'Authorization' => "Bearer {$streamSecret}",
                'Content-Type' => 'application/x-ndjson',
            ])
            ->withBody($ndjsonPayload, 'application/x-ndjson')
            ->post($url)
            ->throw();
    }

    private function dispatchToQueue(array $batch): void
    {
        $job = new SendToTailstreamJob($batch);

        $queueName = $this->config->get('tailstream-logger.queue_name', 'tailstream');
        $queueConnection = $this->config->get('tailstream-logger.queue_connection');

        if ($queueConnection) {
            $job->onConnection($queueConnection);
        }

        $job->onQueue($queueName);

        dispatch($job);
    }

    private function getBatchCacheKey(): string
    {
        $instanceId = $this->config->get('tailstream-logger.instance_id', 'default');

        return self::CACHE_KEY_PREFIX.':'.$instanceId;
    }

    private function getCacheTtl(): int
    {
        return $this->config->get('tailstream-logger.cache_ttl', 3600);
    }
}
