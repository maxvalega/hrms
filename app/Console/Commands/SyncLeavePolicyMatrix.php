<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\LeavePolicyMatrixSeeder;
use Illuminate\Console\Command;

class SyncLeavePolicyMatrix extends Command
{
    protected $signature = 'leave:sync-policy-matrix {--company= : Optional company user id}';

    protected $description = 'Upsert leave policy matrix (SL/PL/CL/Comp-off/OH/WFH/Bereavement). Does not delete existing types.';

    public function handle(): int
    {
        $seeder = new LeavePolicyMatrixSeeder();
        $companyId = $this->option('company');

        if ($companyId) {
            $seeder->seedForCompany((int) $companyId);
            $this->info("Synced leave policy matrix for company #{$companyId}");
            return self::SUCCESS;
        }

        $seeder->run();
        $count = User::where('type', 'company')->count();
        $this->info("Synced leave policy matrix for {$count} companies.");
        return self::SUCCESS;
    }
}
