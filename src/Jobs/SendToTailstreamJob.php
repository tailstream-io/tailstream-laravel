<?php

declare(strict_types=1);

namespace Tailstream\LaravelLogger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendToTailstreamJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        private array $batch
    ) {}

    public function handle(ConfigRepository $config): void
    {
        try {
            $this->sendToTailstream($this->batch, $config);
        } catch (Throwable $e) {
            Log::error('Failed to send queued logs to Tailstream', [
                'error' => $e->getMessage(),
                'batch_size' => count($this->batch),
            ]);

            throw $e;
        }
    }

    private function sendToTailstream(array $batch, ConfigRepository $config): void
    {
        $streamUuid = $config->get('tailstream-logger.stream_uuid');
        $streamSecret = $config->get('tailstream-logger.stream_secret');

        if (! $streamUuid || ! $streamSecret) {
            Log::error('Tailstream - not sending logs because stream UUID or secret is not set');

            return;
        }

        $baseUrl = $config->get('tailstream-logger.base_url');
        $url = "{$baseUrl}/ingest/{$streamUuid}";

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
}
