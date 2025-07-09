<?php

namespace Modules\Cases\Providers;

use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            module_path('Cases', 'config/CaseEntity_states.php'),
            'Modules.Cases.CaseEntity_states'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
