<?php

declare(strict_types=1);

namespace Tailstream\LaravelLogger\Data;

use Illuminate\Support\Facades\Log;
use Throwable;

readonly class RequestData
{
    public function __construct(
        public string $method,
        public string $path,
        public string $ip,
        public int $statusCode,
        public float $responseTimeMs,
        public int $bytes,
    ) {}

    public function toIngestFormat(string $host): array
    {
        try {
            return [
                'ts' => now()->format('Y-m-d\TH:i:s.uP'),
                'host' => $host ?: 'unknown',
                'path' => $this->path ?: '/',
                'method' => $this->method ?: 'GET',
                'status' => max(100, min(599, $this->statusCode)),
                'rt' => max(0.0, $this->responseTimeMs / 1000),
                'bytes' => max(0, $this->bytes),
                'src' => $this->ip ?: 'unknown',
            ];
        } catch (Throwable $e) {
            Log::error('Tailstream: Failed to format request data for ingest', [
                'error' => $e->getMessage(),
            ]);

            // Return minimal valid data as fallback
            return [
                'ts' => now()->format('Y-m-d\TH:i:s.uP'),
                'host' => $host ?: 'unknown',
                'path' => '/',
                'method' => 'GET',
                'status' => 200,
                'rt' => 0.0,
                'bytes' => 0,
                'src' => 'unknown',
            ];
        }
    }
}
