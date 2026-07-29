<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base domain (company portals = {subdomain}.{base})
    |--------------------------------------------------------------------------
    */
    'base_domain' => env('TENANT_BASE_DOMAIN', 'jemini.co.in'),

    /*
    |--------------------------------------------------------------------------
    | Hosts treated as the main platform (super admin portal)
    |--------------------------------------------------------------------------
    | Comma-separated list in .env: MAIN_DOMAINS=jemini.co.in,www.jemini.co.in
    */
    'main_domains' => array_values(array_filter(array_map(
        static fn ($d) => strtolower(trim($d)),
        explode(',', (string) env('MAIN_DOMAINS', 'jemini.co.in,www.jemini.co.in,localhost,127.0.0.1'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Reserved subdomains (cannot be assigned to a company)
    |--------------------------------------------------------------------------
    */
    'reserved_subdomains' => [
        'www',
        'mail',
        'ftp',
        'api',
        'admin',
        'app',
        'cdn',
        'static',
        'jemini',
        'support',
        'status',
    ],

    /*
    |--------------------------------------------------------------------------
    | Enforce host checks on local / artisan serve
    |--------------------------------------------------------------------------
    | When false, login host checks are skipped on localhost (easier local dev).
    */
    'enforce_on_local' => (bool) env('TENANCY_ENFORCE_ON_LOCAL', false),
];
