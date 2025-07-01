<?php

namespace Modules\Cases\Providers;
use Spatie\ModelStates\Events\StateChanged;
use Modules\Cases\Listeners\LogCaseEntityStateTransition;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
        protected $listen = [
        StateChanged::class => [
            LogCaseEntityStateTransition::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}

