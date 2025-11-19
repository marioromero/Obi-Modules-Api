<?php

namespace Modules\Core\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;

class CoreServiceProvider extends ServiceProvider
{
    protected string $name = 'Core';

    protected string $moduleNameLower = 'core';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->group(module_path($this->moduleNameLower, '/routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * Estas rutas son típicamente stateless.
     */
    protected function mapApiRoutes(): void
    {
        // 1) Prefijo global (/obi/api)
        $gateway = config('api.gateway_prefix');

        // 2) Array de versiones por módulo
        $versions = config('api.versions');

        // 3) Versión por defecto si no existe entrada específica
        $defaultVersion = config('api.default_version');

        // 4) El "slug" de tu módulo, coincide con $this->moduleNameLower (en este caso "core")
        $module = $this->moduleNameLower;

        // 5) Buscamos la versión de "core" o usamos la default
        $version = Arr::get($versions, $module, $defaultVersion);

        Route::middleware('api')
            ->as('api.') // nombres api.xxx
            ->prefix("{$gateway}/{$module}/{$version}") // ej. obi/api/core/v1
            ->group(module_path($this->moduleNameLower, 'routes/api.php'));
    }
}
