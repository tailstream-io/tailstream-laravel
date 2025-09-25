# Tailstream Laravel Logger

A beautiful Laravel log driver package with performance monitoring, request tracking, and cache-based batching capabilities. Built with Laravel 12+ compatibility and following convention over configuration principles.

## About Tailstream

[Tailstream](https://tailstream.io) is a modern log aggregation and monitoring platform designed for developers who need powerful observability without the complexity. It provides real-time log streaming, intelligent alerting, and beautiful dashboards to help you monitor your applications effectively.

## ✨ Features

- 🚀 **Minimal Configuration** - Works with just basic setup
- ⚡ **Performance Monitoring** - Track response times and memory usage
- 🔗 **Request Correlation** - UUID-based request tracking across your application
- 📦 **Smart Batching** - Cache-based log batching with configurable thresholds
- 🚀 **Queue Support** - Optional asynchronous processing via Laravel queues
- 🎯 **Tailstream Integration** - Direct integration with Tailstream API
- 🛡️ **Laravel 12 Native** - Built using Laravel 12's modern patterns and conventions
- 🧪 **Comprehensive Tests** - Full Pest test suite with 100% coverage

## 🚀 Installation

```bash
composer require tailstream-io/laravel-logger
```

### Configure Stream Credentials

Add your stream credentials to your `.env` file:

```env
TAILSTREAM_STREAM_UUID=your-stream-uuid
TAILSTREAM_STREAM_SECRET=your-stream-secret
```

That's it! The package will automatically:
- Register itself via Laravel's package discovery
- Add global middleware to track all requests
- Start sending request data to Tailstream immediately

## 📋 Quick Start

Your application is now automatically sending request data to Tailstream! All HTTP requests will be tracked and batched for efficient delivery.

## ⚙️ Configuration

While the package requires minimal configuration to get started, you can customize it further:

```bash
php artisan vendor:publish --tag=tailstream-logger-config
```

### Environment Variables

```env
# Basic Settings
TAILSTREAM_ENABLED=true
TAILSTREAM_BASE_URL=https://app.tailstream.io/api
TAILSTREAM_SAMPLING_RATE=1.0
TAILSTREAM_INSTANCE_ID=web-01

# Batching Configuration
TAILSTREAM_BATCH_SIZE=50          # Flush when batch reaches this size
TAILSTREAM_FLUSH_FREQUENCY=10     # Flush every N requests
TAILSTREAM_CACHE_TTL=3600

# Queue Configuration (Optional)
TAILSTREAM_USE_QUEUE=false        # Enable asynchronous processing
TAILSTREAM_QUEUE_NAME=tailstream   # Queue name for processing
TAILSTREAM_QUEUE_CONNECTION=       # Queue connection (default: app default)

# Middleware
TAILSTREAM_AUTO_MIDDLEWARE=true
```

## 📊 What Gets Logged

Data is sent in batches to the Tailstream ingest endpoint (`https://app.tailstream.io/api/ingest/{stream_uuid}`) with Bearer token authentication using NDJSON format:

```
{"ts":"2024-03-15T10:30:45.123456+00:00","host":"web-01","path":"/api/users","method":"GET","status":200,"rt":0.245,"bytes":1024,"src":"192.168.1.100"}
{"ts":"2024-03-15T10:31:12.789012+00:00","host":"web-01","path":"/api/posts","method":"POST","status":201,"rt":0.156,"bytes":512,"src":"192.168.1.101"}
```

### Field Descriptions

- **ts**: ISO 8601 timestamp when the request was processed
- **host**: Server hostname where the request was handled
- **path**: Request path (without query parameters)
- **method**: HTTP method (GET, POST, etc.)
- **status**: HTTP response status code
- **rt**: Response time in seconds
- **bytes**: Response size in bytes
- **src**: Client IP address



## 🔧 Advanced Configuration

### Queue-Based Processing

For high-traffic applications, you can enable asynchronous processing using Laravel queues. This prevents the HTTP request from being blocked while logs are sent to Tailstream:

```env
TAILSTREAM_USE_QUEUE=true
TAILSTREAM_QUEUE_NAME=tailstream
TAILSTREAM_QUEUE_CONNECTION=redis
```

When queue processing is enabled:
- Log batches are dispatched to the specified queue instead of being sent synchronously
- The queue job includes retry logic (3 attempts with 60-second backoff)
- Your queue workers will handle the actual transmission to Tailstream
- This reduces request latency and provides better fault tolerance

**Requirements:**
- Ensure your queue system is properly configured and running
- Queue workers must be active to process the jobs
- Consider using a reliable queue driver like Redis or database for production

### Sampling for High-Traffic Apps

```php
'sampling_rate' => 0.1, // Log 10% of requests
```

### Exclude Specific Paths

```php
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
```


## 🧪 Testing

```bash
composer test
```

## 📚 Laravel Integration

### Middleware Registration

The package automatically registers middleware globally. To disable:

```php
'auto_register_middleware' => false,
```

Then manually register:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(TailstreamLogger::class);
})
```


## 🔍 Monitoring & Debugging

### Request Correlation

Every request gets a unique UUID that's included in all logs during that request, making it easy to trace issues across your application.

## 📈 Batching Strategy

- **Batch Size**: Process when 50 entries are collected (configurable)
- **Periodic Flush**: Process every 10 requests to prevent logs sitting too long (configurable)
- **Cache-Based**: Uses your configured cache driver (Redis recommended)
- **Queue Support**: Optional asynchronous processing for high-traffic applications

## 🔧 Management Commands

The package includes artisan commands for batch management:

```bash
# Check batch status
php artisan tailstream:flush --status

# Manually flush pending logs
php artisan tailstream:flush

# Clear batch without processing
php artisan tailstream:flush --clear
```

## 📝 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## 🐛 Bug Reports

If you discover a bug, please open an issue on [GitHub](https://github.com/tailstream/laravel-logger/issues).