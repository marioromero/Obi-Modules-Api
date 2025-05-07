<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyExternalAuth
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle($request, Closure $next)
    {
        // Validar que venga un ID de usuario autenticado
        if (!$request->hasHeader('X-User-ID') || !$request->hasHeader('X-User-Role')) {
            return response()->json(['error' => 'Unauthorized. Missing user headers.'], 401);
        }

        // (Opcional) validación de una API_KEY interna
        $expectedApiKey = env('TRUSTED_INTERNAL_API_KEY');

        if (!$expectedApiKey) {
            // 500 para alertar de configuración incorrecta
            abort(500, 'Server misconfiguration: missing TRUSTED_INTERNAL_API_KEY');
        }

        if ($request->header('X-Internal-Key') !== $expectedApiKey) {
            return response()->json(['error' => 'Unauthorized source.'], 401);
        }

        // Cargar "usuario simulado" si lo deseas (para policies futuras)
        $request->merge([
            'external_user_id' => $request->header('X-User-ID'),
            'external_user_role' => $request->header('X-User-Role'),
        ]);

        return $next($request);
    }

}
