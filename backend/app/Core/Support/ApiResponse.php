<?php

namespace App\Core\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Request completed successfully.',
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        return self::withTraceHeader(response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ], $status));
    }

    public static function error(
        string $message = 'Request failed.',
        int $status = 500,
        array $errors = [],
        ?string $code = null
    ): JsonResponse {
        return self::withTraceHeader(response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'code' => $code,
        ], $status));
    }

    private static function withTraceHeader(JsonResponse $response): JsonResponse
    {
        $traceId = request()->attributes->get('trace_id')
            ?: request()->headers->get('X-Request-Id');

        if ($traceId !== null) {
            $response->headers->set('X-Request-Id', $traceId);
        }

        return $response;
    }
}
