<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
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
        //
    })->create();
