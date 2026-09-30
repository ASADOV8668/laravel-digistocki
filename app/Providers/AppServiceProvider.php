<?php

namespace App\Providers;

use App\Models\Listing;
use App\Policies\ListingPolicy;
use App\Services\SystemOptions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SystemOptions::class, fn () => new SystemOptions);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Listing::class, ListingPolicy::class);
    }
}
