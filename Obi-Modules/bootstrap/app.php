<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
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
        apiPrefix: env('API_GATEWAY_PREFIX', 'api')  // <- ahora lee obi/api
    )
    ->withMiddleware(function (Middleware $middleware) {

        /* ──────────────── CORS PRIMERO ──────────────── */
        $middleware->prepend(HandleCors::class);       // ← nueva línea

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
            $apiPrefix = trim((string) env('API_GATEWAY_PREFIX', 'api'), '/');
            $wantsJson = $request->expectsJson()
                || $request->is($apiPrefix.'/*')
                || $request->is('*/'.$apiPrefix.'/*');
            if (! $wantsJson) {
                // Si no es API/JSON, deja que fluyan las vistas HTML normales
                return;
            }

            $controller = app(BaseApiController::class);
            $response = null;

            // 1) Errores de validación
            if ($e instanceof ValidationException) {
                $response = $controller->error(
                    'Errores de validación',
                    422,
                    $e->errors()
                );
            }
            // 2) No autenticado
            elseif ($e instanceof AuthenticationException) {
                $response = $controller->error(
                    'No autenticado',
                    401
                );
            }
            // 3) No autorizado
            elseif ($e instanceof AuthorizationException) {
                $response = $controller->error(
                    'No autorizado',
                    403
                );
            }
            // 4) Modelo no encontrado
            elseif ($e instanceof ModelNotFoundException) {
                $model = class_basename($e->getModel());
                $response = $controller->error(
                    "{$model} no encontrado",
                    404
                );
            }
            // 5) Límite de peticiones (Throttle)
            elseif ($e instanceof TooManyRequestsHttpException) {
                $response = $controller->error(
                    'Demasiadas peticiones',
                    429
                );
            }
            // 6) Otras HTTP exceptions (404 ruta, 405 método, etc.)
            elseif ($e instanceof HttpExceptionInterface) {
                $status  = $e->getStatusCode();
                $message = $e->getMessage()
                    ?: (SymfonyResponse::$statusTexts[$status] ?? 'Error HTTP');
                $response = $controller->error(
                    $message,
                    $status
                );
            }
            // 7) Errores de base de datos
            elseif ($e instanceof QueryException) {
                $response = $controller->error(
                    'Error en base de datos',
                    500,
                    $e->getMessage()
                );
            }
            // 8) Cualquier otra excepción inesperada
            else {
                $msg = app()->isProduction()
                    ? 'Error interno del servidor'
                    : $e->getMessage();

                $response = $controller->error(
                    $msg,
                    500
                );
            }

            // Las respuestas de error se renderizan FUERA del pipeline global de
            // middleware, por lo que nunca pasan por HandleCors y llegaban al
            // navegador SIN Access-Control-Allow-Origin (el browser las reporta
            // como error CORS y enmascara el 5xx real). Re-aplicar CORS aquí.
            try {
                return app(HandleCors::class)->handle($request, fn () => $response);
            } catch (\Throwable $corsError) {
                return $response;
            }
        });
    })
    ->create();
