<?php

namespace App\Providers;

use App\Helpers\RoleHelper;
use App\Services\Dja\DjaIngestor;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One ingestor per request/command so the Excel import and the caller share the same counters
        $this->app->singleton(DjaIngestor::class);

        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Super Admin is allowed everything, so a permission added later never locks the owner out
        Gate::before(fn ($user) => $user->hasRole(RoleHelper::SUPER_ADMIN) ? true : null);

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinutes(3, 5)->by($request->ip());
        });
    }
}
