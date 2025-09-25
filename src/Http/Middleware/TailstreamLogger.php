<?php

declare(strict_types=1);

namespace Tailstream\LaravelLogger\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tailstream\LaravelLogger\Batch\CacheBatchProcessor;
use Tailstream\LaravelLogger\Collectors\RequestDataCollector;

class TailstreamLogger
{
    private const REQUEST_COUNT_KEY = 'tailstream_logger_request_count';

    public function __construct(
        private RequestDataCollector $requestDataCollector,
        private CacheBatchProcessor $batchProcessor
    ) {}

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $startTime = microtime(true);

        $request->attributes->set('tailstream.start_time', $startTime);

        $this->requestDataCollector->setRequestStart($startTime);

        return $next($request);
    }

    public function terminate(Request $request, SymfonyResponse $response): void
    {
        if (! $this->shouldLog($request, $response)) {
            return;
        }

        $endTime = microtime(true);

        $responseSize = strlen($response->getContent());

        $this->requestDataCollector->setRequestEnd(
            $endTime,
            $response->getStatusCode(),
            $responseSize
        );

        $requestData = $this->requestDataCollector->collect();

        if ($requestData !== null) {
            $this->batchProcessor->add(
                $requestData->toIngestFormat((string) gethostname())
            );
        }

        // Periodic flush based on request count
        $this->attemptPeriodicFlush();
    }

    private function shouldLog(Request $request, SymfonyResponse $response): bool
    {
        $config = config('tailstream-logger', []);

        if (! ($config['enabled'] ?? true)) {
            return false;
        }

        if ($response instanceof StreamedResponse) {
            return false;
        }

        $excludedPaths = $config['excluded_paths'];

        foreach ($excludedPaths as $path) {
            if ($request->is($path)) {
                return false;
            }
        }

        $samplingRate = $config['sampling_rate'] ?? 1.0;
        if ($samplingRate < 1.0 && mt_rand() / mt_getrandmax() > $samplingRate) {
            return false;
        }

        return true;
    }

    private function attemptPeriodicFlush(): void
    {
        $config = config('tailstream-logger', []);
        $flushFrequency = $config['flush_frequency'] ?? 10;

        // Get and increment request count from cache
        $cache = app('cache');
        $requestCount = $cache->increment(self::REQUEST_COUNT_KEY, 1);

        // Set TTL on first increment to prevent infinite growth
        if ($requestCount === 1) {
            $cache->put(self::REQUEST_COUNT_KEY, 1, 3600); // 1 hour TTL
        }

        // Flush every N requests
        if ($requestCount % $flushFrequency === 0) {
            $this->batchProcessor->flush();
        }
    }
}
