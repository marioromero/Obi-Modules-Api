<?php

namespace Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;

/**
 * Service‑provider del módulo Core.
 * Centraliza helpers, traits y cualquier binding común.
 */
class CoreServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name       = 'Core';
    protected string $nameLower  = 'core';

    /**
     * Registrar servicios (bindings) en el contenedor.
     * De momento está vacío, pero aquí podrías añadir singletons:
     *
     * $this->app->singleton(DateFormatter::class, fn () => new DateFormatter('America/Santiago'));
     */
    public function register(): void
    {
        // Registrar otros providers de Core si los agregas
        // $this->app->register(EventServiceProvider::class);
        // $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Código que se ejecuta cuando la app termina de arrancar.
     * Útil para listeners globales, macros, etc.
     */
    public function boot(): void
    {
        // Ejemplo: publicar configuración o traducciones en el futuro
        // $this->publishes([...], 'core-config');
    }

    /**
     * Lista de servicios que provee (opcional).
     */
    public function provides(): array
    {
        return [];
    }
}
