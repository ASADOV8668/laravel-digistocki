<?php

namespace App\Http\Middleware;

use App\Services\AdminPriorityModules;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPriorityModule
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        abort_unless($request->user() && app(AdminPriorityModules::class)->allows($request->user(), $module), 403);

        return $next($request);
    }
}
