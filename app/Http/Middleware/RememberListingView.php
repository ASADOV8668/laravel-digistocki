<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RememberListingView
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $view = $request->query('view');

        if (in_array($view, ['list', 'grid'], true)) {
            $response->withCookie(cookie(
                'listing_view',
                $view,
                60 * 24 * 365,
                '/',
                null,
                $request->isSecure(),
                false,
                false,
                'lax',
            ));
        }

        return $response;
    }
}
