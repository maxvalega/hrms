<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\TenantHost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SetCompanySubdomain extends Command
{
    protected $signature = 'tenancy:set-subdomain
                            {company : Company user id or email}
                            {subdomain : Portal subdomain (e.g. spectal)}';

    protected $description = 'Assign a unique company portal subdomain (super-admin ops).';

    public function handle(): int
    {
        if (!Schema::hasColumn('users', 'subdomain')) {
            $this->error('users.subdomain column missing. Run: php artisan migrate');

            return self::FAILURE;
        }

        $key = $this->argument('company');
        $subdomain = TenantHost::normalizeSubdomain($this->argument('subdomain'));

        if ($subdomain === null) {
            $this->error('Invalid subdomain.');

            return self::FAILURE;
        }

        if (in_array($subdomain, config('tenancy.reserved_subdomains', []), true)) {
            $this->error('Reserved subdomain.');

            return self::FAILURE;
        }

        $company = is_numeric($key)
            ? User::where('type', 'company')->find($key)
            : User::where('type', 'company')->where('email', $key)->first();

        if (!$company) {
            $this->error('Company not found.');

            return self::FAILURE;
        }

        $taken = User::where('subdomain', $subdomain)->where('id', '!=', $company->id)->exists();
        if ($taken) {
            $this->error('Subdomain already in use.');

            return self::FAILURE;
        }

        $company->subdomain = $subdomain;
        $company->save();

        $url = 'https://' . $subdomain . '.' . TenantHost::baseDomain();
        $this->info("Assigned {$company->name} (#{$company->id}) → {$url}");
        $this->line('Company users can no longer sign in on the main domain.');

        return self::SUCCESS;
    }
}
