<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

// Import de excepciones
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;

// Tu BaseApiController que usa el trait ApiResponse
use Modules\Core\app\Http\BaseApiController;

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

        // Inyectar VerifyExternalAuth en todo el grupo api
        $middleware->appendToGroup('api', \App\Http\Middleware\VerifyExternalAuth::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Opcional: reportar QueryException a Sentry u otro servicio
        $exceptions->reportable(function (QueryException $e) {
            // Aquí podrías hacer: Log::error($e);
            // o enviar a Sentry: app('sentry')->captureException($e);
        });

        // Captura y renderiza todas las excepciones para rutas API
        $exceptions->renderable(function (Throwable $e, $request) {
            $wantsJson = $request->expectsJson() || $request->is('api/*');
            if (! $wantsJson) {
                // Si no es API/JSON, deja que fluyan las vistas HTML normales
                return;
            }

            $controller = app(BaseApiController::class);

            // 1) Errores de validación
            if ($e instanceof ValidationException) {
                return $controller->error(
                    'Errores de validación',
                    422,
                    $e->errors()
                );
            }

            // 2) No autenticado
            if ($e instanceof AuthenticationException) {
                return $controller->error(
                    'No autenticado',
                    401
                );
            }

            // 3) No autorizado
            if ($e instanceof AuthorizationException) {
                return $controller->error(
                    'No autorizado',
                    403
                );
            }

            // 4) Modelo no encontrado
            if ($e instanceof ModelNotFoundException) {
                $model = class_basename($e->getModel());
                return $controller->error(
                    "{$model} no encontrado",
                    404
                );
            }

            // 5) Límite de peticiones (Throttle)
            if ($e instanceof TooManyRequestsHttpException) {
                return $controller->error(
                    'Demasiadas peticiones',
                    429
                );
            }

            // 6) Otras HTTP exceptions (404 ruta, 405 método, etc.)
            if ($e instanceof HttpExceptionInterface) {
                $status  = $e->getStatusCode();
                $message = $e->getMessage()
                    ?: (SymfonyResponse::$statusTexts[$status] ?? 'Error HTTP');
                return $controller->error(
                    $message,
                    $status
                );
            }

            // 7) Errores de base de datos
            if ($e instanceof QueryException) {
                return $controller->error(
                    'Error en base de datos',
                    500,
                    $e->getMessage()
                );
            }

            // 8) Cualquier otra excepción inesperada
            $msg = app()->isProduction()
                ? 'Error interno del servidor'
                : $e->getMessage();

            return $controller->error(
                $msg,
                500
            );
        });
    })
    ->create();
