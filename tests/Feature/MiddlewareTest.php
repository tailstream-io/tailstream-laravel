<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tailstream\LaravelLogger\Batch\CacheBatchProcessor;
use Tailstream\LaravelLogger\Collectors\RequestDataCollector;
use Tailstream\LaravelLogger\Http\Middleware\TailstreamLogger;

beforeEach(function () {
    $this->collector = app(RequestDataCollector::class);
    $this->batchProcessor = app(CacheBatchProcessor::class);
    $this->middleware = new TailstreamLogger($this->collector, $this->batchProcessor);
});

test('middleware sets start timing', function () {
    $request = Request::create('/test', 'GET');

    $response = $this->middleware->handle($request, function ($req) {
        expect($req->attributes->get('tailstream.start_time'))
            ->toBeFloat()
            ->toBeGreaterThan(0);

        return new Response('OK');
    });

    expect($response)->toBeInstanceOf(Response::class);
});

test('middleware adds data to batch processor on terminate', function () {
    $request = Request::create('/test-path', 'POST');
    $request->attributes->set('tailstream.start_time', microtime(true) - 0.1);

    $response = new Response('Test content', 201);

    $this->middleware->terminate($request, $response);

    expect($this->batchProcessor->getBatchSize())->toBe(1);
});

test('middleware respects excluded paths configuration', function () {
    config(['tailstream-logger.excluded_paths' => ['health-check', 'admin/*']]);

    $healthRequest = Request::create('/health-check', 'GET');
    $adminRequest = Request::create('/admin/dashboard', 'GET');

    $response = new Response('OK');

    $this->middleware->terminate($healthRequest, $response);
    $this->middleware->terminate($adminRequest, $response);

    expect($this->batchProcessor->getBatchSize())->toBe(0);
});

test('middleware respects sampling rate', function () {
    config(['tailstream-logger.sampling_rate' => 0.0]);

    $request = Request::create('/test', 'GET');
    $response = new Response('OK');

    $this->middleware->terminate($request, $response);

    expect($this->batchProcessor->getBatchSize())->toBe(0);
});

test('middleware can be disabled', function () {
    config(['tailstream-logger.enabled' => false]);

    $request = Request::create('/test', 'GET');
    $response = new Response('OK');

    $this->middleware->terminate($request, $response);

    expect($this->batchProcessor->getBatchSize())->toBe(0);
});

test('middleware flushes periodically based on request count', function () {
    config(['tailstream-logger.flush_frequency' => 3]); // Flush every 3 requests

    // Add some entries first
    $this->batchProcessor->add(['ts' => now()->format('Y-m-d\TH:i:s.uP'), 'test' => 'data1']);
    $this->batchProcessor->add(['ts' => now()->format('Y-m-d\TH:i:s.uP'), 'test' => 'data2']);

    expect($this->batchProcessor->getBatchSize())->toBe(2);

    $request = Request::create('/test', 'GET');
    $request->attributes->set('tailstream.start_time', microtime(true) - 0.1);
    $response = new Response('OK');

    // First request - no flush yet
    $this->middleware->terminate($request, $response);
    expect($this->batchProcessor->getBatchSize())->toBe(3);

    // Second request - no flush yet
    $this->middleware->terminate($request, $response);
    expect($this->batchProcessor->getBatchSize())->toBe(4);

    // Third request - should trigger flush
    $this->middleware->terminate($request, $response);
    expect($this->batchProcessor->getBatchSize())->toBe(0);
});
