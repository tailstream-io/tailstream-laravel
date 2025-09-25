<?php

declare(strict_types=1);

namespace Tailstream\LaravelLogger\Commands;

use Illuminate\Console\Command;
use Tailstream\LaravelLogger\Batch\CacheBatchProcessor;

class TailstreamFlushCommand extends Command
{
    protected $signature = 'tailstream:flush
                           {--clear : Clear the batch without processing}
                           {--status : Show current batch status}
                           {--force : Force flush even if batch is small}';

    protected $description = 'Flush pending Tailstream logger batch entries';

    public function handle(CacheBatchProcessor $batchProcessor): int
    {
        if ($this->option('status')) {
            return $this->showStatus($batchProcessor);
        }

        if ($this->option('clear')) {
            return $this->clearBatch($batchProcessor);
        }

        return $this->flushBatch($batchProcessor);
    }

    private function showStatus(CacheBatchProcessor $batchProcessor): int
    {
        $batchSize = $batchProcessor->getBatchSize();

        $this->table(['Metric', 'Value'], [
            ['Pending Entries', $batchSize],
            ['Status', $batchSize > 0 ? 'Has pending logs' : 'No pending logs'],
            ['Cache Driver', config('cache.default')],
            ['Batch Size Limit', config('tailstream-logger.batch_size', 50)],
            ['Time Threshold', config('tailstream-logger.batch_time_threshold_seconds', 30).' seconds'],
        ]);

        if ($batchSize > 0) {
            $this->info("Use 'php artisan tailstream:flush' to process pending entries.");
        }

        return self::SUCCESS;
    }

    private function clearBatch(CacheBatchProcessor $batchProcessor): int
    {
        $batchSize = $batchProcessor->getBatchSize();

        if ($batchSize === 0) {
            $this->info('No pending entries to clear.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Are you sure you want to clear {$batchSize} pending log entries?")) {
            $this->info('Clear operation cancelled.');

            return self::SUCCESS;
        }

        $batchProcessor->clearBatch();

        $this->info("Cleared {$batchSize} pending log entries.");

        return self::SUCCESS;
    }

    private function flushBatch(CacheBatchProcessor $batchProcessor): int
    {
        $batchSize = $batchProcessor->getBatchSize();

        if ($batchSize === 0) {
            $this->info('No pending entries to flush.');

            return self::SUCCESS;
        }

        $this->info("Flushing {$batchSize} pending log entries...");

        $startTime = microtime(true);
        $success = $batchProcessor->flush();
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        if ($success) {
            $this->info("✅ Successfully flushed {$batchSize} log entries in {$duration}ms");

            return self::SUCCESS;
        } else {
            $this->error('❌ Failed to flush log entries. Check logs for details.');

            return self::FAILURE;
        }
    }
}
