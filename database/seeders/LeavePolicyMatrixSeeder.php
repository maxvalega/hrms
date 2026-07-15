<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeavePolicyService;
use Illuminate\Database\Seeder;

/**
 * Upserts the HR leave policy matrix for every company.
 * Does not delete existing leave types — renames superseded defaults with [OLD] prefix when titles collide.
 */
class LeavePolicyMatrixSeeder extends Seeder
{
    public function run(): void
    {
        $companies = User::where('type', 'company')->pluck('id');
        $definitions = LeavePolicyService::policyDefinitions();

        foreach ($companies as $companyId) {
            $this->seedForCompany((int) $companyId, $definitions);
        }
    }

    public function seedForCompany(int $companyId, ?array $definitions = null): void
    {
        $definitions = $definitions ?? LeavePolicyService::policyDefinitions();

        foreach ($definitions as $code => $def) {
            $existingByCode = LeaveType::where('created_by', $companyId)
                ->where('policy_code', $code)
                ->first();

            // Soft-mark old similarly titled types without policy_code (keep, don't delete)
            if (!$existingByCode) {
                $oldOnes = LeaveType::where('created_by', $companyId)
                    ->whereNull('policy_code')
                    ->where('title', 'like', '%' . $this->titleKeyword($code) . '%')
                    ->get();
                foreach ($oldOnes as $old) {
                    if (strpos($old->title, '[OLD]') !== 0) {
                        // Keep original title in notes-ish way by prefixing — comment equivalent in data
                        $old->title = '[OLD] ' . $old->title;
                        $old->save();
                    }
                }
            }

            $payload = [
                'title' => $def['title'],
                'days' => (float) $def['days'],
                'monthly_credit' => (float) ($def['monthly_credit'] ?? 0),
                'annual_credit' => (float) ($def['annual_credit'] ?? $def['days']),
                'approval_requirement' => $def['approval_requirement'] ?? 'na',
                'credit_frequency' => $def['credit_frequency'] ?? 'monthly',
                'is_prorata' => (bool) ($def['is_prorata'] ?? true),
                'eligible_employee_types' => $def['eligible_employee_types'] ?? [],
                'min_notice_days' => $def['min_notice_days'] ?? null,
                'notice_rules' => $def['notice_rules'] ?? null,
                'max_consecutive_days' => $def['max_consecutive_days'] ?? null,
                'monthly_limit' => $def['monthly_limit'] ?? null,
                'max_encash_on_exit' => $def['max_encash_on_exit'] ?? null,
                'requires_family_relation' => (bool) ($def['requires_family_relation'] ?? false),
                'is_as_earned' => (bool) ($def['is_as_earned'] ?? false),
                'policy_notes' => $def['policy_notes'] ?? null,
                'is_carry_forward' => (int) ($def['is_carry_forward'] ?? 0),
                'max_carry_forward' => $def['max_carry_forward'] ?? null,
                'is_encashable' => (int) ($def['is_encashable'] ?? 0),
                'created_by' => $companyId,
            ];

            if ($existingByCode) {
                $existingByCode->fill($payload);
                $existingByCode->policy_code = $code;
                $existingByCode->save();
            } else {
                $payload['policy_code'] = $code;
                LeaveType::create($payload);
            }
        }
    }

    protected function titleKeyword(string $code): string
    {
        return match ($code) {
            'sick' => 'Sick',
            'pl' => 'Privilege',
            'cl' => 'Casual',
            'comp_off' => 'Compensatory',
            'optional_holiday' => 'Optional Holiday',
            'wfh' => 'Work From Home',
            'bereavement' => 'Bereavement',
            default => $code,
        };
    }
}
