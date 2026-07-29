<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TenantHost
{
    public static function currentHost(?Request $request = null): string
    {
        $request ??= request();
        $host = strtolower((string) ($request?->getHost() ?? ''));

        return preg_replace('/:\d+$/', '', $host) ?: $host;
    }

    public static function baseDomain(): string
    {
        return strtolower((string) config('tenancy.base_domain', 'jemini.co.in'));
    }

    public static function mainDomains(): array
    {
        $configured = config('tenancy.main_domains', []);
        $base = self::baseDomain();

        return array_values(array_unique(array_filter(array_merge($configured, [$base, 'www.' . $base]))));
    }

    public static function isLocalHost(?string $host = null): bool
    {
        $host = strtolower($host ?? self::currentHost());

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    public static function shouldEnforce(?Request $request = null): bool
    {
        if (!Schema::hasColumn('users', 'subdomain')) {
            return false;
        }

        $host = self::currentHost($request);
        if (self::isLocalHost($host) && !config('tenancy.enforce_on_local', false)) {
            return false;
        }

        return true;
    }

    public static function isMainDomain(?string $host = null): bool
    {
        $host = strtolower($host ?? self::currentHost());

        return in_array($host, self::mainDomains(), true);
    }

    /**
     * Extract company subdomain from host, e.g. spectal.jemini.co.in → spectal.
     */
    public static function subdomainFromHost(?string $host = null): ?string
    {
        $host = strtolower($host ?? self::currentHost());

        if (self::isMainDomain($host) || self::isLocalHost($host)) {
            return null;
        }

        $base = self::baseDomain();
        $suffix = '.' . $base;
        if (!str_ends_with($host, $suffix)) {
            return null;
        }

        $sub = substr($host, 0, -strlen($suffix));
        if ($sub === '' || str_contains($sub, '.')) {
            // Only single-level company subdomains are supported.
            return null;
        }

        return self::normalizeSubdomain($sub);
    }

    public static function normalizeSubdomain(?string $subdomain): ?string
    {
        if ($subdomain === null) {
            return null;
        }

        $subdomain = strtolower(trim($subdomain));
        if ($subdomain === '') {
            return null;
        }

        return $subdomain;
    }

    public static function findCompanyBySubdomain(?string $subdomain): ?User
    {
        $subdomain = self::normalizeSubdomain($subdomain);
        if ($subdomain === null) {
            return null;
        }

        return User::query()
            ->where('type', 'company')
            ->where('subdomain', $subdomain)
            ->first();
    }

    public static function companyForUser(User $user): ?User
    {
        if ($user->type === 'super admin') {
            return null;
        }

        if ($user->type === 'company') {
            return $user;
        }

        $company = User::query()->find($user->created_by);

        return ($company && $company->type === 'company') ? $company : null;
    }

    public static function portalUrlForCompany(?User $company): ?string
    {
        if (!$company || empty($company->subdomain)) {
            return null;
        }

        $scheme = request()?->isSecure() || env('FORCE_HTTPS', false) ? 'https' : 'http';

        return $scheme . '://' . $company->subdomain . '.' . self::baseDomain();
    }

    public static function portalUrlForUser(User $user): ?string
    {
        return self::portalUrlForCompany(self::companyForUser($user));
    }

    /**
     * @throws ValidationException
     */
    public static function assertUserMayLoginOnHost(User $user, ?Request $request = null): void
    {
        if (!self::shouldEnforce($request)) {
            return;
        }

        $host = self::currentHost($request);
        $subdomain = self::subdomainFromHost($host);
        $isMain = self::isMainDomain($host);

        if ($user->type === 'super admin') {
            if (!$isMain) {
                self::throwPortalNotice([
                    'variant' => 'admin',
                    'title' => __('Platform admin login'),
                    'message' => __('Super admin accounts sign in on the main Jemini portal only.'),
                    'url' => 'https://' . self::baseDomain() . '/login',
                    'cta' => __('Go to Jemini login'),
                ]);
            }

            return;
        }

        $company = self::companyForUser($user);
        $companySub = self::normalizeSubdomain($company?->subdomain);
        $companyName = $company?->name ?: __('your company');

        if ($isMain) {
            if ($companySub) {
                $portal = self::portalUrlForCompany($company) ?: ('https://' . $companySub . '.' . self::baseDomain());
                self::throwPortalNotice([
                    'variant' => 'company',
                    'title' => __('Wrong portal'),
                    'message' => __('This account belongs to :company. For security, company users must sign in on their own portal not on jemini.co.in.', [
                        'company' => $companyName,
                    ]),
                    'url' => $portal,
                    'host' => $companySub . '.' . self::baseDomain(),
                    'company' => $companyName,
                    'cta' => __('Continue to company portal'),
                ]);
            }

            // Legacy companies without a subdomain may still use the main domain.
            return;
        }

        if ($subdomain === null) {
            self::throwPortalNotice([
                'variant' => 'warning',
                'title' => __('Invalid portal address'),
                'message' => __('This address is not a valid company portal. Please use your company subdomain or contact your administrator.'),
                'url' => 'https://' . self::baseDomain() . '/login',
                'cta' => __('Go to main login'),
            ]);
        }

        if ($companySub === null) {
            self::throwPortalNotice([
                'variant' => 'warning',
                'title' => __('Portal not configured'),
                'message' => __('Your company portal has not been set up yet. Please contact support, or try the main portal if your administrator has not assigned a subdomain.'),
                'url' => 'https://' . self::baseDomain() . '/login',
                'cta' => __('Go to main login'),
            ]);
        }

        if ($companySub !== $subdomain) {
            $portal = self::portalUrlForCompany($company) ?: ('https://' . $companySub . '.' . self::baseDomain());
            self::throwPortalNotice([
                'variant' => 'company',
                'title' => __('Wrong company portal'),
                'message' => __('This account belongs to :company. You opened a different company portal by mistake.', [
                    'company' => $companyName,
                ]),
                'url' => $portal,
                'host' => $companySub . '.' . self::baseDomain(),
                'company' => $companyName,
                'cta' => __('Go to the correct portal'),
            ]);
        }
    }

    /**
     * Flash structured portal notice for the login UI, then fail validation.
     *
     * @param  array{variant:string,title:string,message:string,url:string,cta:string,host?:string,company?:string}  $payload
     *
     * @throws ValidationException
     */
    protected static function throwPortalNotice(array $payload): void
    {
        session()->flash('tenant_portal_notice', $payload);

        throw ValidationException::withMessages([
            'portal' => $payload['message'],
        ]);
    }

    /**
     * After session auth: logout + redirect if the host does not match the user.
     */
    public static function sessionHostMismatchRedirect(User $user, ?Request $request = null): ?string
    {
        if (!self::shouldEnforce($request)) {
            return null;
        }

        try {
            self::assertUserMayLoginOnHost($user, $request);
        } catch (ValidationException $e) {
            $notice = session('tenant_portal_notice');
            if (is_array($notice) && !empty($notice['message'])) {
                return (string) $notice['message'];
            }

            $message = collect($e->errors())->flatten()->first() ?: __('Permission denied.');

            return $message;
        }

        return null;
    }
}
