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
                throw ValidationException::withMessages([
                    'email' => __('Super admin must sign in at :url', [
                        'url' => 'https://' . self::baseDomain() . '/login',
                    ]),
                ]);
            }

            return;
        }

        $company = self::companyForUser($user);
        $companySub = self::normalizeSubdomain($company?->subdomain);

        if ($isMain) {
            if ($companySub) {
                $portal = self::portalUrlForCompany($company);
                throw ValidationException::withMessages([
                    'email' => __('Please sign in at your company portal: :url', [
                        'url' => $portal ?: ('https://' . $companySub . '.' . self::baseDomain()),
                    ]),
                ]);
            }

            // Legacy companies without a subdomain may still use the main domain.
            return;
        }

        if ($subdomain === null) {
            throw ValidationException::withMessages([
                'email' => __('Invalid portal address. Please use your company subdomain.'),
            ]);
        }

        if ($companySub === null) {
            throw ValidationException::withMessages([
                'email' => __('Your company portal is not configured yet. Please contact support or sign in at :url', [
                    'url' => 'https://' . self::baseDomain() . '/login',
                ]),
            ]);
        }

        if ($companySub !== $subdomain) {
            $portal = self::portalUrlForCompany($company);
            throw ValidationException::withMessages([
                'email' => __('This account belongs to another company. Please sign in at :url', [
                    'url' => $portal ?: ('https://' . $companySub . '.' . self::baseDomain()),
                ]),
            ]);
        }
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
            $message = collect($e->errors())->flatten()->first() ?: __('Permission denied.');

            return $message;
        }

        return null;
    }
}
