<?php

namespace App\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class BaseService
{
    protected function transaction(string $operation, callable $callback): mixed
    {
        try {
            return DB::transaction($callback);
        } catch (Throwable $exception) {
            Log::channel('business')->error('Transactional service operation failed.', [
                'operation' => $operation,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
