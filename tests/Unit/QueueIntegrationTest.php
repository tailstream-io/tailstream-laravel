<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Tailstream\LaravelLogger\Batch\CacheBatchProcessor;
use Tailstream\LaravelLogger\Jobs\SendToTailstreamJob;

beforeEach(function () {
    $this->processor = app(CacheBatchProcessor::class);
});

test('batch processor uses queue when configured', function () {
    Queue::fake();

    config([
        'tailstream-logger.use_queue' => true,
        'tailstream-logger.queue_name' => 'test-queue',
        'tailstream-logger.batch_size' => 2,
    ]);

    // Add entries to trigger flush
    $this->processor->add(['test' => 'entry1']);
    $this->processor->add(['test' => 'entry2']);

    // Verify job was dispatched
    Queue::assertPushed(SendToTailstreamJob::class, function ($job) {
        return $job->queue === 'test-queue';
    });
});

test('batch processor sends directly when queue is disabled', function () {
    Queue::fake();

    config([
        'tailstream-logger.use_queue' => false,
        'tailstream-logger.batch_size' => 2,
    ]);

    // Add entries to trigger flush
    $this->processor->add(['test' => 'entry1']);
    $this->processor->add(['test' => 'entry2']);

    // Verify no job was dispatched
    Queue::assertNothingPushed();
});

test('queue job can specify custom connection', function () {
    Queue::fake();

    config([
        'tailstream-logger.use_queue' => true,
        'tailstream-logger.queue_name' => 'test-queue',
        'tailstream-logger.queue_connection' => 'redis',
        'tailstream-logger.batch_size' => 1,
    ]);

    $this->processor->add(['test' => 'entry']);

    Queue::assertPushed(SendToTailstreamJob::class, function ($job) {
        return $job->connection === 'redis' && $job->queue === 'test-queue';
    });
});
