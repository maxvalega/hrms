<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/*
|--------------------------------------------------------------------------
| Environment Auto-Detection: Local vs Cloud
|--------------------------------------------------------------------------
|
| Automatically picks the right .env file:
|   .env.local  → when running on localhost / 127.0.0.1 (XAMPP)
|   .env.cloud  → when running on any other server (production/cloud)
|
| To force an environment, set the ENV_FILE environment variable:
|   SetEnv ENV_FILE .env.cloud   (in Apache .htaccess)
|   ENV_FILE=.env.cloud php artisan serve
|
| Files:
|   .env.local  — Local XAMPP settings (DB: root@localhost, debug ON)
|   .env.cloud  — Cloud/production settings (remote DB, debug OFF)
|   .env        — Fallback (always kept as a copy of active env)
|
*/
$envFile = null;

// 1. Explicit override via environment variable
if (!empty($_SERVER['ENV_FILE'])) {
    $envFile = $_SERVER['ENV_FILE'];
} elseif (!empty($_ENV['ENV_FILE'])) {
    $envFile = $_ENV['ENV_FILE'];
}

// 2. Auto-detect from server context
if ($envFile === null) {
    $isCli = (PHP_SAPI === 'cli' || PHP_SAPI === 'cli-server');
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    $localHosts = ['localhost', '127.0.0.1', '::1'];
    $hostOnly = strtolower(explode(':', (string) $host)[0]);
    $basePathEarly = dirname(__DIR__);

    // HTTP localhost / 127.0.0.1 → local
    $isHttpLocal = in_array($hostOnly, $localHosts, true);

    // CLI on a developer machine → local; CLI on Hostinger/VPS → cloud
    $isLocalCli = $isCli && (
        PHP_OS_FAMILY === 'Darwin'
        || str_contains($basePathEarly, '/Users/')
        || str_contains($basePathEarly, 'C:\\')
        || str_contains(strtolower($basePathEarly), 'xampp')
    );

    if ($isHttpLocal || $isLocalCli) {
        $envFile = '.env.local';
    } else {
        $envFile = '.env.cloud';
    }
}

// 3. Verify the file exists, fall back to .env
$basePath = dirname(__DIR__);
if (!file_exists($basePath . '/' . $envFile)) {
    $envFile = '.env';
}

$app = Application::configure(basePath: $basePath)
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        // commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Middleware
        $middleware->append([
            // \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\TrustProxies::class,
            \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \App\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        // RouteMiddleware / Alias — must be a SINGLE alias() call (Laravel replaces, does not merge).
        $middlewareAliases = [
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'XSS' => \App\Http\Middleware\XSS::class,
            'CheckPlan' => \App\Http\Middleware\CheckPlan::class,
            'Pusher' => \App\Http\Middleware\getPusherSettings::class,
            'mobile.app.key' => \App\Http\Middleware\EnsureMobileAppKeyIsValid::class,
            'tenant.host' => \App\Http\Middleware\EnsureTenantHost::class,
        ];

        if (class_exists(\Spatie\Permission\Middleware\PermissionMiddleware::class)) {
            $middlewareAliases['role'] = \Spatie\Permission\Middleware\RoleMiddleware::class;
            $middlewareAliases['permission'] = \Spatie\Permission\Middleware\PermissionMiddleware::class;
            $middlewareAliases['role_or_permission'] = \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class;
        } elseif (class_exists(\Spatie\Permission\Middlewares\PermissionMiddleware::class)) {
            $middlewareAliases['role'] = \Spatie\Permission\Middlewares\RoleMiddleware::class;
            $middlewareAliases['permission'] = \Spatie\Permission\Middlewares\PermissionMiddleware::class;
            $middlewareAliases['role_or_permission'] = \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class;
        }

        $middleware->alias($middlewareAliases);

        // middlewareGroups / Group Middleware
        // Append middleware to the 'web' group
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            // \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\FilterRequest::class,
            \App\Http\Middleware\EnsureTenantHost::class,
        ]);

        // Append middleware to the 'api' group
        $middleware->appendToGroup('api', [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Exclude specific routes from CSRF protection
        $middleware->validateCsrfTokens(
            except: [
                'plan/paytm/*',
                'invoice/paytm/*',
                'plan-pay-with-paymentwall/*',
                'iyzipay/callback/*',
                'paytab-success/*',
                '/aamarpay*',
                'logout',
                '/logout',
            ]
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

// Load the detected environment file
$app->loadEnvironmentFrom($envFile);

return $app;
