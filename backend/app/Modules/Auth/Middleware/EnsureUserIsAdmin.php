<?php

namespace App\Modules\Auth\Middleware;

use App\Core\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (! $user?->is_admin || $user->status !== 'active') {
            $this->auditLogger->record(
                'auth.access_denied',
                actor: $user,
                auditable: $user,
                metadata: [
                    'reason' => 'admin_boundary',
                    'method' => $request->method(),
                    'path' => $request->path(),
                ],
                outcome: 'denied',
                request: $request,
                guard: $request->is('api/*') ? 'sanctum' : 'web'
            );

            abort(Response::HTTP_FORBIDDEN, 'Admin privileges are required.');
        }

        return $next($request);
    }
}
