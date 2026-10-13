<?php

namespace App\Http\Middleware;

use App\Services\SystemOptions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplySystemSessionLifetime
{
    public function handle(Request $request, Closure $next): Response
    {
        config(['session.lifetime' => app(SystemOptions::class)->sessionLifetimeMinutes()]);

        return $next($request);
    }
}
