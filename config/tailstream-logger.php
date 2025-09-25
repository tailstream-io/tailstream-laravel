<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Tailstream Logger Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file allows you to customize the behavior of the
    | Tailstream Logger package. Most settings have sensible defaults that
    | follow the "convention over configuration" principle.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Determine if the Tailstream logger is enabled. When disabled, no logging
    | or performance tracking will occur.
    |
    */

    'enabled' => env('TAILSTREAM_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the API. There usually isn't a need to change this, but
    | if you really want to, go wild.
    |
    */

    'base_url' => env('TAILSTREAM_BASE_URL', 'https://app.tailstream.io/api'),

    /*
    |--------------------------------------------------------------------------
    | Auto-Register Middleware
    |--------------------------------------------------------------------------
    |
    | Automatically register the Tailstream middleware globally on 'web' and
    | 'api' middleware groups. Set to false if you want to manually register.
    |
    */

    'auto_register_middleware' => env('TAILSTREAM_AUTO_MIDDLEWARE', true),

    /*
    |--------------------------------------------------------------------------
    | Instance ID
    |--------------------------------------------------------------------------
    |
    | A unique identifier for this application instance. Useful when running
    | multiple instances of the same application.
    |
    */

    'instance_id' => env('TAILSTREAM_INSTANCE_ID', gethostname()),

    /*
    |--------------------------------------------------------------------------
    | Sampling Rate
    |--------------------------------------------------------------------------
    |
    | The percentage of requests to log (0.0 to 1.0). Use lower values for
    | high-traffic applications. 1.0 means log all requests.
    |
    */

    'sampling_rate' => (float) env('TAILSTREAM_SAMPLING_RATE', 1.0),

    /*
    |--------------------------------------------------------------------------
    | Excluded Paths
    |--------------------------------------------------------------------------
    |
    | Request paths that should be excluded from logging. Supports wildcards.
    |
    */

    'excluded_paths' => [
        'telescope/*',
        'horizon/*',
        '_ignition/*',
        'health-check',
        'up',
        'favicon.ico',
        '*.css',
        '*.js',
        '*.map',
        'storage/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Batch Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how logs are batched before being sent to destinations.
    | batch_size: Flush when this many logs are collected
    | flush_frequency: Flush every N requests to prevent logs sitting too long
    |
    */

    'batch_size' => (int) env('TAILSTREAM_BATCH_SIZE', 50),
    'flush_frequency' => (int) env('TAILSTREAM_FLUSH_FREQUENCY', 5),
    'cache_ttl' => (int) env('TAILSTREAM_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Stream Credentials
    |--------------------------------------------------------------------------
    |
    | Your Tailstream stream UUID and secret for authentication.
    |
    */

    'stream_uuid' => env('TAILSTREAM_STREAM_UUID'),
    'stream_secret' => env('TAILSTREAM_STREAM_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how logs are queued instead of being sent directly.
    | use_queue: Enable/disable queue-based processing
    | queue_name: The queue name to use for processing Tailstream logs
    | queue_connection: The queue connection to use (null for default)
    |
    */

    'use_queue' => env('TAILSTREAM_USE_QUEUE', false),
    'queue_name' => env('TAILSTREAM_QUEUE_NAME', 'tailstream'),
    'queue_connection' => env('TAILSTREAM_QUEUE_CONNECTION'),

];
