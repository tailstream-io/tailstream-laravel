<?php

declare(strict_types=1);

namespace Tailstream\LaravelLogger\Collectors;

use Illuminate\Http\Request;
use Tailstream\LaravelLogger\Data\RequestData;
use Throwable;

class RequestDataCollector
{
    private array $requestData = [];

    public function __construct() {}

    public function setRequestStart(float $startTime): void
    {
        try {
            $this->requestData = [
                'start_time' => $startTime,
            ];
        } catch (Throwable) {
            // Silently ignore errors
        }
    }

    public function setRequestEnd(float $endTime, int $statusCode, int $responseSize): void
    {
        try {
            $this->requestData['end_time'] = $endTime;
            $this->requestData['status_code'] = $statusCode;
            $this->requestData['response_size'] = $responseSize;
        } catch (Throwable) {
            // Silently ignore errors
        }
    }

    public function collect(): ?RequestData
    {
        try {
            $request = request();

            if (! $request instanceof Request) {
                return null;
            }

            $responseTimeMs = 0.0;
            if (isset($this->requestData['start_time'], $this->requestData['end_time'])) {
                $responseTime = $this->requestData['end_time'] - $this->requestData['start_time'];
                $responseTimeMs = round($responseTime * 1000, 2);
            }

            $method = $request->getMethod();
            $path = '/'.ltrim($request->path(), '/');
            $ip = $request->ip() ?? 'unknown';

            if (empty($method) || empty($path)) {
                return null;
            }

            return new RequestData(
                method: $method,
                path: $path,
                ip: $ip,
                statusCode: $this->requestData['status_code'] ?? 200,
                responseTimeMs: $responseTimeMs,
                bytes: $this->requestData['response_size'] ?? 0,
            );
        } catch (Throwable) {
            return null;
        }
    }
}
