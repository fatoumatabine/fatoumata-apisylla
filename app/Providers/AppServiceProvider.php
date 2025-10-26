<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enregistrer Faker pour les seeders en production
        if ($this->app->environment('production')) {
            $this->app->singleton(\Faker\Generator::class, function () {
                return \Faker\Factory::create('fr_FR');
            });
        }
    }
}
