<?php

namespace Modules\Users\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Users';
    protected string $moduleNameLower = 'Users';

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
        Route::middleware('api')
            ->as('api.')                                        // nombres como api.users.index
            ->prefix('api/' . config('api.version'))            // api/v1/...
            ->domain(
                config('api.subdomain')
                    ? config('api.subdomain') . '.' . parse_url(config('app.url'), PHP_URL_HOST)
                    : null
            )
            ->group(module_path($this->moduleNameLower, '/routes/api.php'));
    }
}
