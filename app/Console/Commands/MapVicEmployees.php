<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\VicEmployeeMapper;
use Illuminate\Console\Command;

class MapVicEmployees extends Command
{
    protected $signature = 'vic:map-employees {--company= : Company user id (defaults to subdomain vic)}';

    protected $description = 'Create or update Vimal Industrial staff on vic.jemini.co.in from the employee CSV.';

    public function handle(VicEmployeeMapper $mapper): int
    {
        $company = $this->resolveCompany();
        if (!$company) {
            $this->error('Vimal Industrial company (subdomain vic) was not found.');

            return self::FAILURE;
        }

        $this->info('Mapping staff for ' . $company->name . ' (#' . $company->id . ')');
        $result = $mapper->map((int) $company->id);
        $this->info('Created: ' . $result['created'] . ', Updated: ' . $result['updated'] . ', Skipped: ' . $result['skipped']);
        foreach (array_slice($result['errors'], 0, 10) as $error) {
            $this->warn($error);
        }

        return self::SUCCESS;
    }

    protected function resolveCompany(): ?User
    {
        $id = (int) $this->option('company');
        if ($id > 0) {
            return User::where('type', 'company')->find($id);
        }

        return User::where('type', 'company')
            ->whereIn('subdomain', ['vic', 'vimalindustrial'])
            ->first();
    }
}
