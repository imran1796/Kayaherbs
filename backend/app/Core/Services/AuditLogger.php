<?php

namespace App\Core\Services;

use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $event,
        ?Model $actor = null,
        ?Model $auditable = null,
        array $metadata = [],
        string $outcome = 'success',
        ?Request $request = null,
        ?string $guard = null
    ): void {
        try {
            $request ??= request();

            AuditEvent::query()->create([
                'event' => $event,
                'outcome' => $outcome,
                'actor_type' => $actor?->getMorphClass(),
                'actor_id' => $actor?->getKey(),
                'auditable_type' => $auditable?->getMorphClass(),
                'auditable_id' => $auditable?->getKey(),
                'guard' => $guard,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'request_id' => $request?->attributes->get('trace_id') ?: $request?->headers->get('X-Request-Id'),
                'metadata' => $this->safeMetadata($metadata),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Audit event recording failed.', [
                'event' => $event,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function safeMetadata(array $metadata): array
    {
        $blockedKeys = [
            'password',
            'password_confirmation',
            'token',
            'access_token',
            'authorization',
        ];

        foreach ($blockedKeys as $key) {
            if (array_key_exists($key, $metadata)) {
                $metadata[$key] = '[redacted]';
            }
        }

        return $metadata;
    }
}
