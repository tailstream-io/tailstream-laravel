<?php

declare(strict_types=1);

namespace Tailstream\LaravelLogger\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Tailstream\LaravelLogger\TailstreamLoggerServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('tailstream-logger.enabled', true);
        config()->set('tailstream-logger.batch_size', 5);
        config()->set('tailstream-logger.batch_time_threshold_seconds', 1);
        config()->set('cache.default', 'array');
    }

    protected function getPackageProviders($app): array
    {
        return [
            TailstreamLoggerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('cache.stores.array', [
            'driver' => 'array',
            'serialize' => false,
        ]);
    }
}
