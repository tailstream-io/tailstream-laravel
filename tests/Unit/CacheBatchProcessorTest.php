<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tailstream\LaravelLogger\Batch\CacheBatchProcessor;

beforeEach(function () {
    $this->processor = app(CacheBatchProcessor::class);
});

test('can add log entries to batch', function () {
    $logEntry = [
        'level' => 'INFO',
        'message' => 'Test message',
        'context' => ['key' => 'value'],
    ];

    $this->processor->add($logEntry);

    expect($this->processor->getBatchSize())->toBe(1);
});

test('automatically flushes when batch size is reached', function () {
    config(['tailstream-logger.batch_size' => 2]);

    $this->processor->add(['message' => 'First entry']);
    expect($this->processor->getBatchSize())->toBe(1);

    $this->processor->add(['message' => 'Second entry']);
    expect($this->processor->getBatchSize())->toBe(0);
});

test('can manually flush batch', function () {
    $this->processor->add(['message' => 'Test entry']);
    expect($this->processor->getBatchSize())->toBe(1);

    $success = $this->processor->flush();

    expect($success)->toBeTrue();
    expect($this->processor->getBatchSize())->toBe(0);
});

test('can clear batch without processing', function () {
    $this->processor->add(['message' => 'Test entry']);
    expect($this->processor->getBatchSize())->toBe(1);

    $this->processor->clearBatch();
    expect($this->processor->getBatchSize())->toBe(0);
});

test('handles flush lock correctly', function () {
    Cache::put('tailstream_logger_flush_lock', true, 30);

    $this->processor->add(['message' => 'Test entry']);
    $success = $this->processor->flush();

    expect($success)->toBeFalse();
    expect($this->processor->getBatchSize())->toBe(1);
});

test('sends logs to http destination', function () {
    Http::fake();

    config([
        'tailstream-logger.stream_uuid' => 'test-uuid',
        'tailstream-logger.stream_secret' => 'test-secret',
    ]);

    $this->processor->add(['ts' => '2023-01-01T00:00:00Z', 'host' => 'test']);
    $this->processor->flush();

    Http::assertSent(function ($request) {
        $baseUrl = config('tailstream-logger.base_url');

        return str_contains($request->url(), "{$baseUrl}/ingest/test-uuid") &&
               $request->hasHeader('Authorization', 'Bearer test-secret') &&
               $request->hasHeader('Content-Type', 'application/x-ndjson') &&
               is_string($request->body()) &&
               str_contains($request->body(), '"ts":"2023-01-01T00:00:00Z"');
    });
});
