<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use Carbon\Carbon;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Passport::tokensExpireIn(Carbon::now()->addHours(1));
        Passport::refreshTokensExpireIn(Carbon::now()->addDays(30));
        Passport::personalAccessTokensExpireIn(Carbon::now()->addMonths(6));

        Passport::tokensCan([
            'view-accounts' => 'View bank accounts',
            'manage-accounts' => 'Manage bank accounts (create, update, delete)',
            'view-transactions' => 'View transactions',
            'manage-transactions' => 'Manage transactions (create, update, delete)',
        ]);

        // Utiliser l'algorithme RS256
        Passport::tokensCan([
            // ... vos scopes existants
        ]);
        Passport::hashClientSecrets(); // Pour Laravel Passport 10+
    }
}
