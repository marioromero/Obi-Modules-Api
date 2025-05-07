<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Alias para usar en rutas puntuales: ->middleware('api-auth')
        $middleware->alias([
            'api-auth', \App\Http\Middleware\VerifyExternalAuth::class
        ]);

        // Inyectar VerifyExternalAuth en to-do el grupo api
        $middleware->appendToGroup('api', \App\Http\Middleware\VerifyExternalAuth::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (Throwable $e, $request) {

            $wantsJson = $request->expectsJson() || $request->is('api/*');
            if (!$wantsJson) {
                return; // deja fluir vistas HTML para otras rutas
            }

            // ----------- Códigos y mensajes ----------
            $status = 500;
            $message = 'Internal server error';

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $message = $e->getMessage()
                    ?: (SymfonyResponse::$statusTexts[$status] ?? 'Error');
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'code' => 422,
                    'errors' => $e->errors(),
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'code' => $status,
                'data' => null,
            ], $status);
        });
    })
    ->create();
