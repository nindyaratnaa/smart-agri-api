<?php

namespace App\Providers;

use App\Models\DataPertanian;
use App\Models\Validasi;
use App\Observers\DataPertanianObserver;
use App\Observers\ValidasiObserver;
use App\Policies\DataPertanianPolicy;
use Illuminate\Support\Facades\Gate;
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
        // Register Observers
        DataPertanian::observe(DataPertanianObserver::class);
        Validasi::observe(ValidasiObserver::class);

        // Register Policies
        Gate::policy(DataPertanian::class, DataPertanianPolicy::class);

        // Admin bypasses all policies
        Gate::before(function ($user, $ability) {
            if ($user->isAdmin()) return true;
        });
    }
}
