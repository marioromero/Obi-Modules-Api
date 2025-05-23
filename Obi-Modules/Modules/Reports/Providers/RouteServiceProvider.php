<?php

namespace Modules\Reports\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Arr;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Reports';
    protected string $moduleNameLower = 'reports';

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
        Route::middleware('web')->group(module_path($this->moduleNameLower, '/routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
     protected function mapApiRoutes(): void
{
    // 1) Prefijo global (/obi/api)
    $gateway = config('api.gateway_prefix');

    // 2) Array de versiones por módulo
    $versions = config('api.versions');

    // 3) Versión por defecto si no existe entrada específica
    $defaultVersion = config('api.default_version');

    // 4) El "slug" de tu módulo, coincide con $this->moduleNameLower
    //    (en este caso "reports")
    $module = $this->moduleNameLower;

    // 5) Buscamos la versión de "banks" o usamos la default
    $version = Arr::get($versions, $module, $defaultVersion);

    Route::middleware('api')
        ->as('api.')                                                 // mantienes los nombres api.xxx
        ->prefix("{$gateway}/{$module}/{$version}")                 // ej. obi/api/banks/v1
        //->domain(
        //    config('api.subdomain')
        //        ? config('api.subdomain') . '.' . parse_url(config('app.url'), PHP_URL_HOST)
        //        : null
        //)
        // 6) Asegúrate de usar la ruta relativa SIN slash inicial
        ->group(module_path($this->moduleNameLower, 'Routes/api.php'));
}
}

