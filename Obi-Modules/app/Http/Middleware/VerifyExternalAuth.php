<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\Core\App\Http\BaseApiController;

class VerifyExternalAuth
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle($request, Closure $next)
    {
        $responder = app(BaseApiController::class);

        if (!$request->hasHeader('X-User-ID') || !$request->hasHeader('X-User-Role')) {
            return $responder->error(
                'Unauthorized. Missing user headers.',
                401
            );
        }

        $expectedApiKey = env('TRUSTED_INTERNAL_API_KEY');

        if (!$expectedApiKey) {
            return $responder->error(
                'Server misconfiguration: missing TRUSTED_INTERNAL_API_KEY',
                500
            );
        }

        if ($request->header('X-Internal-Key') !== $expectedApiKey) {
            return $responder->error(
                'Unauthorized source.',
                401
            );
        }

        $request->merge([
            'external_user_id'   => $request->header('X-User-ID'),
            'external_user_role' => $request->header('X-User-Role'),
        ]);

        return $next($request);
    }
}
