<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeStatutoryConfig;
use App\Models\State;
use App\Models\StatutoryComponent;
use App\Models\StatutoryRule;
use Carbon\Carbon;

class StatutoryCalculator
{
    public function calculateForEmployee(int $employeeId, float $basicMonthly, float $grossMonthly, ?int $stateId, ?string $gender, string $month): array
    {
        $config = EmployeeStatutoryConfig::where('employee_id', $employeeId)->first();

        $state = $config && $config->state_id
            ? (int)$config->state_id
            : $this->resolveStateId($stateId, $employeeId);
        $gender = $gender ? strtolower($gender) : null;

        $epf = ($config ? $config->pf_enabled : true) ? $this->calculateEPF($basicMonthly, $state, $gender, $month) : ['employee' => 0.0, 'employer' => 0.0];
        $esic = ($config ? $config->esic_enabled : true) ? $this->calculateESIC($basicMonthly, $grossMonthly, $state, $gender, $month) : ['employee' => 0.0, 'employer' => 0.0];
        $pt = ($config ? $config->pt_enabled : true) ? $this->calculatePT($grossMonthly, $state, $gender, $month, $employeeId) : ['employee' => 0.0, 'employer' => 0.0];
        $lwf = ($config ? $config->lwf_enabled : true) ? $this->calculateLWF($month, $grossMonthly, $state, $gender) : ['employee' => 0.0, 'employer' => 0.0];

        return [
            'epf_employee' => round($epf['employee'], 2),
            'epf_employer' => round($epf['employer'], 2),
            'esic_employee' => round($esic['employee'], 2),
            'esic_employer' => round($esic['employer'], 2),
            'pt' => round($pt['employee'], 2),
            'pt_employer' => round($pt['employer'], 2),
            'lwf_employee' => round($lwf['employee'], 2),
            'lwf_employer' => round($lwf['employer'], 2),
        ];
    }

    public function calculateEPF(float $basicMonthly, ?int $stateId = null, ?string $gender = null, ?string $month = null): array
    {
        // For EPF, max_salary on rule = cap on base amount, not eligibility filter.
        // Pass 0 so resolveRule doesn't filter by salary range, then cap manually.
        $rule = $this->resolveRule('EPF', 0, $stateId, $gender, $month);
        if (!$rule) {
            return ['employee' => 0.0, 'employer' => 0.0];
        }

        // Cap the base at max_salary (e.g., 15000) if set.
        $cappedBase = $basicMonthly;
        if (!empty($rule->max_salary) && $rule->max_salary > 0) {
            $cappedBase = min($basicMonthly, (float)$rule->max_salary);
        }

        $employee = $this->applyContribution($cappedBase, $rule->employee_contribution_type, (float)$rule->employee_value);
        $employer = $this->applyContribution($cappedBase, $rule->employer_contribution_type, (float)$rule->employer_value);

        if (!empty($rule->max_limit)) {
            $employee = min($employee, (float)$rule->max_limit);
            $employer = min($employer, (float)$rule->max_limit);
        }

        return ['employee' => $employee, 'employer' => $employer];
    }

    public function calculateESIC(float $basicMonthly, float $grossMonthly, ?int $stateId = null, ?string $gender = null, ?string $month = null): array
    {
        // Eligibility uses Gross; contribution amounts use Basic.
        $rule = $this->resolveRule('ESIC', $grossMonthly, $stateId, $gender, $month);
        if (!$rule) {
            return ['employee' => 0.0, 'employer' => 0.0];
        }

        $threshold = !empty($rule->max_salary) ? (float)$rule->max_salary : 21000;
        if ($grossMonthly > $threshold) {
            return ['employee' => 0.0, 'employer' => 0.0];
        }

        return [
            'employee' => $this->applyContribution($basicMonthly, $rule->employee_contribution_type, (float)$rule->employee_value),
            'employer' => $this->applyContribution($basicMonthly, $rule->employer_contribution_type, (float)$rule->employer_value),
        ];
    }

    public function calculatePT(float $grossMonthly, ?int $stateId = null, ?string $gender = null, ?string $month = null, ?int $employeeId = null): array
    {
        $resolvedStateId = $this->resolveStateId($stateId, $employeeId);
        $rule = $this->resolveRule('PT', $grossMonthly, $resolvedStateId, $gender, $month);

        if (!$rule) {
            return ['employee' => 0.0, 'employer' => 0.0];
        }

        $employeeAmount = $this->applyContribution($grossMonthly, $rule->employee_contribution_type, (float)$rule->employee_value);
        $employerAmount = $this->applyContribution($grossMonthly, $rule->employer_contribution_type, (float)$rule->employer_value);

        // Maharashtra special case: in February, salary above 10,000 attracts Rs 300 PT.
        if ($month && $resolvedStateId) {
            $monthNo = (int)substr($month, 5, 2);
            $stateName = optional(State::find($resolvedStateId))->state_name;
            if ($monthNo === 2 && $stateName && strcasecmp($stateName, 'Maharashtra') === 0 && $grossMonthly > 10000) {
                $employeeAmount = 300.0;
            }
        }

        return [
            'employee' => $employeeAmount,
            'employer' => $employerAmount,
        ];
    }

    public function calculateLWF(string $month, float $grossMonthly = 0, ?int $stateId = null, ?string $gender = null): array
    {
        $rule = $this->resolveRule('LWF', $grossMonthly, $stateId, $gender, $month);
        if (!$rule) {
            return ['employee' => 0.0, 'employer' => 0.0];
        }

        $m = (int)substr($month, 5, 2);
        if ($rule->frequency === 'half-yearly' && !in_array($m, [6, 12], true)) {
            return ['employee' => 0.0, 'employer' => 0.0];
        }

        return [
            'employee' => $this->applyContribution($grossMonthly, $rule->employee_contribution_type, (float)$rule->employee_value),
            'employer' => $this->applyContribution($grossMonthly, $rule->employer_contribution_type, (float)$rule->employer_value),
        ];
    }

    protected function resolveRule(string $componentCode, float $salaryAmount, ?int $stateId, ?string $gender, ?string $month): ?StatutoryRule
    {
        $component = StatutoryComponent::where('code', strtoupper($componentCode))->where('status', 1)->first();
        if (!$component) {
            return null;
        }

        $date = $month ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString() : now()->toDateString();

        return StatutoryRule::query()
            ->where('component_id', $component->id)
            ->where('status', 1)
            ->where(function ($q) use ($stateId) {
                if ($stateId) {
                    $q->where('state_id', $stateId)->orWhereNull('state_id');
                } else {
                    $q->whereNull('state_id');
                }
            })
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($q) use ($salaryAmount) {
                $q->whereNull('min_salary')->orWhere('min_salary', '<=', $salaryAmount);
            })
            ->where(function ($q) use ($salaryAmount) {
                $q->whereNull('max_salary')->orWhere('max_salary', '>=', $salaryAmount);
            })
            ->where(function ($q) use ($gender) {
                if ($gender) {
                    $q->whereNull('applicable_gender')->orWhere('applicable_gender', $gender);
                    return;
                }
                $q->whereNull('applicable_gender');
            })
            ->orderByRaw('CASE WHEN created_by IS NULL THEN 0 ELSE 1 END ASC')
            ->orderByRaw('CASE WHEN state_id IS NULL THEN 1 ELSE 0 END ASC')
            ->orderByRaw('CASE WHEN applicable_gender IS NULL THEN 1 ELSE 0 END ASC')
            ->orderByRaw('COALESCE(min_salary, 0) DESC')
            ->orderByRaw('COALESCE(max_salary, 999999999) ASC')
            ->orderByDesc('effective_from')
            ->first();
    }

    protected function resolveStateId(?int $stateId, ?int $employeeId = null): ?int
    {
        if (!empty($stateId)) {
            return (int)$stateId;
        }

        if (empty($employeeId)) {
            return null;
        }

        $emp = Employee::find($employeeId);
        if (!$emp || empty($emp->present_state)) {
            return null;
        }

        $normalized = $this->normalizeStateName((string)$emp->present_state);
        if ($normalized === '') {
            return null;
        }

        $state = State::query()
            ->whereRaw('LOWER(state_name) = ?', [strtolower($normalized)])
            ->first();

        return $state ? (int)$state->id : null;
    }

    protected function normalizeStateName(string $value): string
    {
        $raw = trim($value);
        if ($raw === '') {
            return '';
        }

        $upper = strtoupper($raw);
        $aliases = [
            'RJ' => 'Rajasthan',
            'MH' => 'Maharashtra',
            'KA' => 'Karnataka',
            'GJ' => 'Gujarat',
            'WB' => 'West Bengal',
            'TN' => 'Tamil Nadu',
            'AP' => 'Andhra Pradesh',
            'TS' => 'Telangana',
            'MP' => 'Madhya Pradesh',
            'DL' => 'Delhi',
            'UP' => 'Uttar Pradesh',
            'UK' => 'Uttarakhand',
            'UT' => 'Uttarakhand',
            'JK' => 'Jammu and Kashmir',
            'OD' => 'Odisha',
            'OR' => 'Odisha',
            'CG' => 'Chhattisgarh',
            'PB' => 'Punjab',
            'HR' => 'Haryana',
            'HP' => 'Himachal Pradesh',
            'AS' => 'Assam',
            'BR' => 'Bihar',
            'JH' => 'Jharkhand',
            'KL' => 'Kerala',
            'GA' => 'Goa',
            'MN' => 'Manipur',
            'ML' => 'Meghalaya',
            'MZ' => 'Mizoram',
            'NL' => 'Nagaland',
            'SK' => 'Sikkim',
            'TR' => 'Tripura',
            'AR' => 'Arunachal Pradesh',
            'AN' => 'Andaman and Nicobar Islands',
            'CH' => 'Chandigarh',
            'DN' => 'Dadra and Nagar Haveli and Daman and Diu',
            'DD' => 'Dadra and Nagar Haveli and Daman and Diu',
            'LD' => 'Lakshadweep',
            'PY' => 'Puducherry',
            'LA' => 'Ladakh',
        ];

        if (isset($aliases[$upper])) {
            return $aliases[$upper];
        }

        return ucwords(strtolower($raw));
    }

    protected function applyContribution(float $base, string $type, float $value): float
    {
        if ($type === 'fixed') {
            return max($value, 0);
        }
        return max(($base * $value) / 100, 0);
    }
}
