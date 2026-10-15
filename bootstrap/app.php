<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\ApplySystemSessionLifetime;
use App\Http\Middleware\NormalizePersianDigits;
use App\Http\Middleware\RememberListingView;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'admin' => EnsureUserIsAdmin::class,
        ]);
        $middleware->web(
            prepend: [NormalizePersianDigits::class, ApplySystemSessionLifetime::class],
            append: [SecurityHeaders::class, RememberListingView::class],
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
