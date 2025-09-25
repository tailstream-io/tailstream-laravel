<?php

declare(strict_types=1);

namespace Tailstream\LaravelLogger;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Tailstream\LaravelLogger\Batch\CacheBatchProcessor;
use Tailstream\LaravelLogger\Commands\TailstreamFlushCommand;
use Tailstream\LaravelLogger\Http\Middleware\TailstreamLogger;

class TailstreamLoggerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/tailstream-logger.php',
            'tailstream-logger'
        );

        $this->app->singleton(CacheBatchProcessor::class, function (Application $app) {
            return new CacheBatchProcessor(
                $app->make('cache')->store(),
                $app->make('config')
            );
        });

        $this->commands([
            TailstreamFlushCommand::class,
        ]);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/tailstream-logger.php' => config_path('tailstream-logger.php'),
        ], 'tailstream-logger-config');

        $this->registerMiddleware();
    }

    private function registerMiddleware(): void
    {
        if (config('tailstream-logger.auto_register_middleware', true)) {
            $this->app->make('router')->pushMiddlewareToGroup('web', TailstreamLogger::class);
            $this->app->make('router')->pushMiddlewareToGroup('api', TailstreamLogger::class);
        }
    }
}
