<?php

namespace App\Http\Middleware;

use App\Support\TenantHost;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !TenantHost::shouldEnforce($request)) {
            return $next($request);
        }

        // Super admin "Login As Company" must keep working on the main domain.
        $user = Auth::user();
        if (method_exists($user, 'isImpersonated') && $user->isImpersonated()) {
            return $next($request);
        }

        $mismatch = TenantHost::sessionHostMismatchRedirect($user, $request);
        if ($mismatch === null) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $portal = TenantHost::portalUrlForUser($user);
        $loginUrl = ($portal ?: ('https://' . TenantHost::baseDomain())) . '/login';

        return redirect()->to($loginUrl)->with('error', $mismatch);
    }
}
