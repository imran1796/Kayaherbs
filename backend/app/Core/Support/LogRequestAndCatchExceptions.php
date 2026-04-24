<?php

namespace App\Core\Support;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogRequestAndCatchExceptions
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $traceId = $request->headers->get('X-Request-Id') ?: (string) Str::uuid();

        $request->headers->set('X-Request-Id', $traceId);
        $request->attributes->set('trace_id', $traceId);

        $context = [
            'trace_id' => $traceId,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
        ];

        Log::channel('request')->info('Incoming request.', $context);

        try {
            $response = $next($request);

            Log::channel('request')->info('Request completed.', [
                ...$context,
                'status' => $response->getStatusCode(),
                'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            ]);

            $response->headers->set('X-Request-Id', $traceId);

            return $response;
        } catch (Throwable $exception) {
            Log::channel('request')->error('Request failed with unhandled exception.', [
                ...$context,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            ]);

            throw $exception;
        }
    }
}
