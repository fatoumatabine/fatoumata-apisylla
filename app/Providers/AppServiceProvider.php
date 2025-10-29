<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    // Enregistrer les interfaces et leurs implémentations
        $this->app->bind(\App\Contracts\TransactionArchiveInterface::class, \App\Services\PostgresTransactionArchive::class);
        $this->app->bind(\App\Contracts\SmsServiceInterface::class, \App\Services\TwilioSmsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Forcer HTTPS en production
        if ($this->app->environment('production') && env('APP_URL')) {
            URL::forceScheme('https');
        }

        // Enregistrer Faker pour les seeders en production
        if ($this->app->environment('production')) {
            $this->app->singleton(\Faker\Generator::class, function () {
                return \Faker\Factory::create('fr_FR');
            });
        }
    }
}
