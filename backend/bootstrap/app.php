<?php

use App\Core\Support\ApiResponse;
use App\Core\Support\LogRequestAndCatchExceptions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(LogRequestAndCatchExceptions::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                return ApiResponse::error(
                    'Validation failed.',
                    422,
                    $exception->errors(),
                    'validation_failed'
                );
            }

            if ($exception instanceof AuthenticationException) {
                return ApiResponse::error('Unauthenticated.', 401, [], 'unauthenticated');
            }

            if ($exception instanceof AuthorizationException) {
                return ApiResponse::error('This action is unauthorized.', 403, [], 'forbidden');
            }

            if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
                return ApiResponse::error('Resource not found.', 404, [], 'not_found');
            }

            if ($exception instanceof ThrottleRequestsException) {
                return ApiResponse::error('Too many requests.', 429, [], 'too_many_requests');
            }

            $status = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;

            $message = $status >= 500 && ! config('app.debug')
                ? 'An unexpected error occurred.'
                : ($exception->getMessage() ?: 'Request failed.');

            return ApiResponse::error($message, $status, [], $status >= 500 ? 'server_error' : 'request_failed');
        });
    })->create();
