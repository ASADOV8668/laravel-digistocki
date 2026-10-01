<?php

namespace App\Providers;

use App\Models\Listing;
use App\Policies\ListingPolicy;
use App\Services\SystemOptions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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

        // Apache serves the local project from /app while Laravel's front
        // controller lives in /app/public. Preserve that base path in route
        // and redirect URLs; the built-in server keeps its request URL.
        if (! $this->app->runningInConsole() && $this->app->environment('local')) {
            $request = request();

            if ($request->getHost() === 'localhost' && $request->getPort() === 80) {
                $request->server->set('SCRIPT_NAME', '/app/index.php');
                $request->server->set('PHP_SELF', '/app/index.php');
                URL::forceRootUrl(rtrim((string) config('app.url'), '/'));
                URL::forceScheme($request->getScheme());
            } else {
                URL::useAssetOrigin($request->getSchemeAndHttpHost().rtrim($request->getBaseUrl(), '/'));
            }
        }
    }
}
