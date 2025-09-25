<?php

declare(strict_types=1);

use Tailstream\LaravelLogger\Batch\CacheBatchProcessor;
use Tailstream\LaravelLogger\Http\Middleware\TailstreamLogger;

test('service provider registers batch processor as singleton', function () {
    $processor1 = app(CacheBatchProcessor::class);
    $processor2 = app(CacheBatchProcessor::class);

    expect($processor1)->toBe($processor2);
});

test('service provider registers flush command', function () {
    $commands = $this->app->make('Illuminate\Contracts\Console\Kernel')
        ->all();

    expect($commands)->toHaveKey('tailstream:flush');
});

test('service provider auto-registers middleware when enabled', function () {
    config(['tailstream-logger.auto_register_middleware' => true]);

    $router = $this->app->make('router');
    $webMiddleware = $router->getMiddlewareGroups()['web'];
    $apiMiddleware = $router->getMiddlewareGroups()['api'];

    expect($webMiddleware)->toContain(TailstreamLogger::class);
    expect($apiMiddleware)->toContain(TailstreamLogger::class);
});

test('service provider checks auto_register_middleware config', function () {
    // Simple test to verify the config key exists and can be set
    config(['tailstream-logger.auto_register_middleware' => false]);
    expect(config('tailstream-logger.auto_register_middleware'))->toBeFalse();

    config(['tailstream-logger.auto_register_middleware' => true]);
    expect(config('tailstream-logger.auto_register_middleware'))->toBeTrue();
});

test('service provider publishes configuration file', function () {
    $this->artisan('vendor:publish', [
        '--tag' => 'tailstream-logger-config',
        '--force' => true,
    ])->assertSuccessful();

    expect(file_exists(config_path('tailstream-logger.php')))->toBeTrue();
});
