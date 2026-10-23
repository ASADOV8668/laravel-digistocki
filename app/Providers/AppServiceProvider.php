<?php

namespace App\Providers;

use App\Models\ChatConversation;
use App\Models\Listing;
use App\Policies\ChatConversationPolicy;
use App\Policies\ListingPolicy;
use App\Services\SystemOptions;
use App\Support\PersianNumber;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
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
        $formatMegabytes = static function (string|int|float $kilobytes): string {
            $megabytes = (float) $kilobytes / 1024;
            $formatted = fmod($megabytes, 1.0) === 0.0
                ? (string) (int) $megabytes
                : rtrim(rtrim(number_format($megabytes, 2, '.', ''), '0'), '.');

            return PersianNumber::digits($formatted);
        };

        foreach (['max', 'min', 'size', 'between'] as $rule) {
            Validator::replacer($rule, static function (
                string $message,
                string $attribute,
                string $rule,
                array $parameters,
                \Illuminate\Contracts\Validation\Validator $validator,
            ) use ($formatMegabytes): string {
                if (! $validator->getValue($attribute) instanceof UploadedFile) {
                    return $message;
                }

                if ($rule === 'between') {
                    return str_replace([
                        ':min',
                        ':max',
                    ], [
                        $formatMegabytes($parameters[0] ?? 0),
                        $formatMegabytes($parameters[1] ?? 0),
                    ], $message);
                }

                $placeholder = $rule === 'size' ? ':size' : ':'.$rule;

                return str_replace($placeholder, $formatMegabytes($parameters[0] ?? 0), $message);
            });
        }

        Gate::policy(Listing::class, ListingPolicy::class);
        Gate::policy(ChatConversation::class, ChatConversationPolicy::class);

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
