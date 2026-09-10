<?php

namespace App\Http\Middleware;

use App\Support\TenantHost;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureJeminiMainPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!TenantHost::isJeminiMainPortal()) {
            abort(404);
        }

        return $next($request);
    }
}
