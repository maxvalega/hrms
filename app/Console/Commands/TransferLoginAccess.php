<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class TransferLoginAccess extends Command
{
    protected $signature = 'users:transfer-login
                            {from=sainisoniya813@gmail.com : Login to remove}
                            {to=sapna@jemini.co.in : Login that should keep all access}
                            {--delete-from : Delete the from-user after transfer (default true)}
                            {--keep-from : Do not delete the from-user}';

    protected $description = 'Give sapna@jemini.co.in all access from sainisoniya813@gmail.com, then delete that login.';

    public function handle(): int
    {
        $fromEmail = strtolower(trim((string) $this->argument('from')));
        $toEmail = strtolower(trim((string) $this->argument('to')));
        $deleteFrom = !$this->option('keep-from');

        $from = User::whereRaw('LOWER(email) = ?', [$fromEmail])->first();
        if (!$from) {
            $this->error("From user not found: {$fromEmail}");

            return self::FAILURE;
        }

        $to = User::whereRaw('LOWER(email) = ?', [$toEmail])->first();
        if (!$to) {
            $this->info("{$toEmail} does not exist yet — renaming {$fromEmail} to {$toEmail}.");
            $from->email = $toEmail;
            $from->save();
            $this->info("Login is now {$toEmail}. Old email {$fromEmail} is gone.");

            return self::SUCCESS;
        }

        if ((int) $from->id === (int) $to->id) {
            $this->info('Both emails are already the same user.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($from, $to, $deleteFrom) {
            $this->promoteAccess($from, $to);
            $this->copyRoles($from, $to);
            $this->reassignOwnership($from, $to);
            $this->relinkEmployee($from, $to);

            if ($deleteFrom) {
                $this->deleteFromUser($from);
            }
        });

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->info("Access from {$fromEmail} is now on {$toEmail}.");
        if ($deleteFrom) {
            $this->info("Deleted user {$fromEmail}.");
        }

        return self::SUCCESS;
    }

    protected function promoteAccess(User $from, User $to): void
    {
        $rank = ['employee' => 1, 'hr' => 2, 'company' => 3, 'super admin' => 4];
        $fromRank = $rank[$from->type] ?? 0;
        $toRank = $rank[$to->type] ?? 0;

        $copy = [
            'plan', 'plan_expire_date', 'storage_limit', 'trial_expire_date',
            'is_trial_done', 'is_login_enable', 'referral_code', 'used_referral_code',
            'commission_amount', 'paid_amount', 'lang',
        ];
        foreach ($copy as $col) {
            if (Schema::hasColumn('users', $col) && !empty($from->{$col}) && empty($to->{$col})) {
                $to->{$col} = $from->{$col};
            }
        }

        if ($fromRank > $toRank) {
            $to->type = $from->type;
            if ($from->type === 'company') {
                $to->created_by = $from->created_by;
            }
        }

        $to->save();
        $this->line("Promoted {$to->email} to type={$to->type}.");
    }

    protected function copyRoles(User $from, User $to): void
    {
        foreach ($from->roles as $role) {
            if (!$to->hasRole($role->name)) {
                $to->assignRole($role);
            }
        }

        if ($to->type === 'company') {
            $companyRole = Role::where('name', 'company')->first();
            if ($companyRole && !$to->hasRole('company')) {
                $to->assignRole($companyRole);
            }
        }

        foreach ($from->getDirectPermissions() as $permission) {
            if (!$to->hasPermissionTo($permission)) {
                $to->givePermissionTo($permission);
            }
        }
    }

    protected function reassignOwnership(User $from, User $to): void
    {
        $fromId = (int) $from->id;
        $toId = (int) $to->id;
        if ($from->type !== 'company' && (int) $to->created_by !== $fromId) {
            User::where('created_by', $fromId)->where('id', '!=', $toId)->update(['created_by' => $toId]);

            return;
        }

        foreach ($this->tablesWithColumn('created_by') as $table) {
            if ($table === 'users') {
                User::where('created_by', $fromId)->where('id', '!=', $toId)->update(['created_by' => $toId]);
                continue;
            }
            try {
                DB::table($table)->where('created_by', $fromId)->update(['created_by' => $toId]);
            } catch (\Throwable $e) {
                $this->warn("Skip {$table}.created_by: ".$e->getMessage());
            }
        }
    }

    protected function relinkEmployee(User $from, User $to): void
    {
        $toEmp = Employee::where('user_id', $to->id)->first();
        $fromEmp = Employee::where('user_id', $from->id)->first();

        if ($fromEmp && !$toEmp) {
            $fromEmp->user_id = $to->id;
            if (empty($fromEmp->email)) {
                $fromEmp->email = $to->email;
            }
            $fromEmp->save();
        }
    }

    protected function deleteFromUser(User $from): void
    {
        $fromId = (int) $from->id;

        if (Schema::hasTable('model_has_roles')) {
            DB::table('model_has_roles')->where('model_id', $fromId)->where('model_type', User::class)->delete();
        }
        if (Schema::hasTable('model_has_permissions')) {
            DB::table('model_has_permissions')->where('model_id', $fromId)->where('model_type', User::class)->delete();
        }
        if (Schema::hasTable('personal_access_tokens')) {
            DB::table('personal_access_tokens')->where('tokenable_id', $fromId)->where('tokenable_type', User::class)->delete();
        }
        if (Schema::hasTable('login_details')) {
            DB::table('login_details')->where('user_id', $fromId)->delete();
        }
        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
            DB::table('sessions')->where('user_id', $fromId)->delete();
        }

        Employee::where('user_id', $fromId)->update(['user_id' => 0]);

        $from->delete();
    }

    protected function tablesWithColumn(string $column): array
    {
        $tables = [];
        try {
            $tables = Schema::getTableListing();
        } catch (\Throwable $e) {
            $rows = DB::select('SHOW TABLES');
            foreach ($rows as $row) {
                $tables[] = array_values((array) $row)[0];
            }
        }

        $matched = [];
        foreach ($tables as $table) {
            try {
                if (Schema::hasColumn($table, $column)) {
                    $matched[] = $table;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $matched;
    }
}
