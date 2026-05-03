<?php

namespace App\Modules\Auth\Middleware;

use App\Core\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerApiToken
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum') ?? $request->user();
        $token = $user?->currentAccessToken();

        if (
            ! $user
            || $user->is_admin
            || $user->status !== 'active'
            || ! $token instanceof PersonalAccessToken
            || ! $token->can('customer')
        ) {
            $this->auditLogger->record(
                'auth.access_denied',
                actor: $user,
                auditable: $user,
                metadata: [
                    'reason' => 'customer_token_boundary',
                    'method' => $request->method(),
                    'path' => $request->path(),
                ],
                outcome: 'denied',
                request: $request,
                guard: 'sanctum'
            );

            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
