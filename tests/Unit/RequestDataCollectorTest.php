<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Tailstream\LaravelLogger\Collectors\RequestDataCollector;
use Tailstream\LaravelLogger\Data\RequestData;

beforeEach(function () {
    $this->collector = app(RequestDataCollector::class);
});

test('collects basic request data', function () {
    $request = Request::create('/test-path', 'GET');
    $this->app->instance('request', $request);

    $data = $this->collector->collect();

    expect($data)
        ->not->toBeNull()
        ->toBeInstanceOf(RequestData::class)
        ->and($data->method)->toBe('GET')
        ->and($data->path)->toBe('/test-path');
});

test('tracks request timing when set', function () {
    $startTime = microtime(true);

    $this->collector->setRequestStart($startTime);

    usleep(100000); // 100ms

    $endTime = microtime(true);

    $this->collector->setRequestEnd($endTime, 200, 1024);

    $request = Request::create('/test', 'GET');
    $this->app->instance('request', $request);

    $data = $this->collector->collect();

    expect($data)
        ->not->toBeNull()
        ->toBeInstanceOf(RequestData::class)
        ->and($data->statusCode)->toBe(200)
        ->and($data->bytes)->toBe(1024)
        ->and($data->responseTimeMs)->toBeGreaterThan(90);
});

test('handles missing request gracefully', function () {
    // Clear any existing request
    $this->app->forgetInstance('request');

    $data = $this->collector->collect();

    expect($data)->toBeNull();
});
