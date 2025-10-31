<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
    Registered::class => [
    SendEmailVerificationNotification::class,
    ],
    \App\Events\ClientCreated::class => [
    \App\Listeners\SendClientNotification::class,
    ],
    \App\Events\TransactionCreated::class => [
    \App\Listeners\SendTransactionNotification::class,
    ],
        \App\Events\TransactionCreatedEvent::class => [
            \App\Listeners\SendTransactionSmsNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Enregistrer les observers
        \App\Models\Transaction::observe(\App\Observers\TransactionObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
