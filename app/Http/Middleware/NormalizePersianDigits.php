<?php

namespace App\Http\Middleware;

use App\Support\PersianDigits;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizePersianDigits
{
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->isJson() ? $request->json() : $request->request;
        $input->replace(PersianDigits::normalizeRecursive($input->all()));
        $request->query->replace(PersianDigits::normalizeRecursive($request->query->all()));

        return $next($request);
    }
}
