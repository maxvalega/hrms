<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\Leave as LocalLeave;
use App\Models\LeaveType;
use Carbon\Carbon;

/**
 * Company leave policy matrix rules (SL / PL / CL / Comp-off / Optional Holiday / WFH / Bereavement).
 */
class LeavePolicyService
{
    public const IMMEDIATE_FAMILY = [
        'spouse',
        'parent',
        'child',
        'sibling',
        'mother',
        'father',
        'son',
        'daughter',
        'brother',
        'sister',
        'husband',
        'wife',
        'immediate_family',
    ];

    /**
     * Canonical policies from HR matrix. Old global leave settings remain as fallback.
     */
    public static function policyDefinitions(): array
    {
        return [
            'sick' => [
                'title' => 'Sick Leave',
                'days' => 7,
                'monthly_credit' => round(7 / 12, 2),
                'annual_credit' => 7,
                'credit_frequency' => 'monthly',
                'is_prorata' => true,
                'is_carry_forward' => 0,
                'max_carry_forward' => 0,
                'is_encashable' => 0,
                'eligible_employee_types' => ['intern', 'full_time'],
                'policy_notes' => 'Yearly 7 days. Prorata on joining. Monthly credit. No CF. No encashment. Intern + Full time.',
            ],
            'pl' => [
                'title' => 'Privilege Leave (PL)',
                'days' => 18,
                'monthly_credit' => round(18 / 12, 2),
                'annual_credit' => 18,
                'credit_frequency' => 'monthly',
                'is_prorata' => true,
                'is_carry_forward' => 1,
                'max_carry_forward' => 30,
                'is_encashable' => 1,
                'max_encash_on_exit' => 30,
                'eligible_employee_types' => ['full_time'],
                // OLD vague rule kept for reference:
                // ['min' => 5.01, 'max' => null, 'calendar_days' => 21], // "More than 5 days = 3 weeks"
                'notice_rules' => [
                    ['min' => 0.5, 'max' => 2, 'working_days' => 3],
                    ['min' => 3, 'max' => 5, 'calendar_days' => 7],
                    ['min' => 5.01, 'max' => 10, 'calendar_days' => 28], // 4 weeks
                    ['min' => 10.01, 'max' => null, 'calendar_days' => 42], // 6 weeks
                ],
                'policy_notes' => 'Yearly 18. Monthly credit. CF yes. Encash on exit max 30 days. Full time only. Notice by leave length.',
            ],
            'cl' => [
                'title' => 'Casual Leave (CL)',
                'days' => 7,
                'monthly_credit' => round(7 / 12, 2),
                'annual_credit' => 7,
                'credit_frequency' => 'monthly',
                'is_prorata' => true,
                'is_carry_forward' => 0,
                'max_carry_forward' => 0,
                'is_encashable' => 0,
                'eligible_employee_types' => ['full_time'],
                'min_notice_days' => 14, // 2 weeks in advance
                'policy_notes' => 'Yearly 7. Monthly credit. No CF/encash. Full time. Apply 2 weeks in advance.',
            ],
            'comp_off' => [
                'title' => 'Compensatory Off',
                'days' => 0,
                'monthly_credit' => 0,
                'annual_credit' => 0,
                'credit_frequency' => 'earned',
                'is_prorata' => false,
                'is_carry_forward' => 0,
                'is_encashable' => 0,
                'is_as_earned' => true,
                'eligible_employee_types' => ['intern', 'full_time'],
                'min_notice_days' => 7, // A week in advance
                'policy_notes' => 'As and when earned. Week off/Holidays attendance mandatory. 4 hrs = 1/2 day, 8 hrs = Full day. Apply 1 week in advance. Intern + Full time.',
            ],
            'optional_holiday' => [
                'title' => 'Optional Holiday',
                'days' => 2,
                'monthly_credit' => 0,
                'annual_credit' => 2,
                'credit_frequency' => 'annual',
                'is_prorata' => true,
                'is_carry_forward' => 0,
                'is_encashable' => 0,
                'eligible_employee_types' => ['intern', 'full_time'],
                'policy_notes' => 'Yearly 2. Annual credit. Prorata on joining. Intern + Full time.',
            ],
            'wfh' => [
                'title' => 'Work From Home (WFH)',
                'days' => 24, // 2 per month × 12 (tracking yearly pool with monthly_limit)
                'monthly_credit' => 2,
                'annual_credit' => 24,
                'credit_frequency' => 'monthly_cap',
                'is_prorata' => true,
                'is_carry_forward' => 0,
                'is_encashable' => 0,
                'monthly_limit' => 2,
                'max_consecutive_days' => 2, // can only apply 2 together
                'eligible_employee_types' => ['intern', 'full_time'],
                'policy_notes' => 'Monthly 2 days. Annual credit pool. Max 2 days together. Intern + Full time.',
            ],
            'bereavement' => [
                'title' => 'Bereavement Leave',
                'days' => 7,
                'monthly_credit' => round(7 / 12, 2),
                'annual_credit' => 7,
                'credit_frequency' => 'monthly',
                'is_prorata' => true,
                'is_carry_forward' => 0,
                'is_encashable' => 0,
                'eligible_employee_types' => [], // empty = all employees
                'requires_family_relation' => true,
                'policy_notes' => 'Yearly 7. Monthly credit. All employees. Immediate family only.',
            ],
        ];
    }

    public static function hoursToCompOffDays(float $hours): float
    {
        // OLD: free-form days input in award UI (still accepted if hours not provided)
        // NEW matrix: 4 hrs = 1/2 day, 8 hrs = Full day
        if ($hours >= 8) {
            return 1.0;
        }
        if ($hours >= 4) {
            return 0.5;
        }

        return 0.0;
    }

    public function validateApplication(LeaveType $leaveType, Employee $employee, string $startDate, string $endDate, float $totalDays, ?string $familyRelation = null, ?string $appliedOn = null): ?string
    {
        if ($error = $this->validateEligibility($leaveType, $employee)) {
            return $error;
        }

        if ($error = $this->validateNotice($leaveType, $startDate, $totalDays, $appliedOn)) {
            return $error;
        }

        if ($error = $this->validateConsecutiveAndMonthly($leaveType, $employee, $startDate, $endDate, $totalDays)) {
            return $error;
        }

        if (!empty($leaveType->requires_family_relation)) {
            $relation = strtolower(trim((string) $familyRelation));
            if ($relation === '' || !in_array($relation, self::IMMEDIATE_FAMILY, true)) {
                return __('Bereavement leave is allowed only for immediate family members (spouse, parent, child, sibling).');
            }
        }

        // Comp-off applications go through compensatory bank; warn if applying as normal quota type
        if (!empty($leaveType->is_as_earned) || ($leaveType->credit_frequency === 'earned')) {
            // Allowed via normal leave create only when linked to compensatory bank elsewhere.
            // Keep permissive here; claim flow remains primary.
        }

        return null;
    }

    public function validateEligibility(LeaveType $leaveType, Employee $employee): ?string
    {
        $codes = $leaveType->eligible_employee_types;
        if (empty($codes) || !is_array($codes)) {
            return null; // all employees
        }

        $empTypeCode = null;
        if (!empty($employee->employee_type_id)) {
            $empTypeCode = EmployeeType::where('id', $employee->employee_type_id)->value('code');
        }

        if (empty($empTypeCode)) {
            return __('Employee type is not set. This leave type is restricted to: :types', [
                'types' => implode(', ', $codes),
            ]);
        }

        if (!in_array($empTypeCode, $codes, true)) {
            return __('This leave type is not applicable for your employment type (:type). Allowed: :types', [
                'type' => $empTypeCode,
                'types' => implode(', ', $codes),
            ]);
        }

        return null;
    }

    public function validateNotice(LeaveType $leaveType, string $startDate, float $totalDays, ?string $appliedOn = null): ?string
    {
        $applied = Carbon::parse($appliedOn ?? now()->toDateString())->startOfDay();
        $start = Carbon::parse($startDate)->startOfDay();

        $requiredCalendarDays = null;
        $requiredWorkingDays = null;

        $rules = $leaveType->notice_rules;
        if (!empty($rules) && is_array($rules)) {
            foreach ($rules as $rule) {
                $min = isset($rule['min']) ? (float) $rule['min'] : 0;
                $max = array_key_exists('max', $rule) && $rule['max'] !== null ? (float) $rule['max'] : null;
                if ($totalDays >= $min && ($max === null || $totalDays <= $max)) {
                    if (!empty($rule['working_days'])) {
                        $requiredWorkingDays = (int) $rule['working_days'];
                    }
                    if (!empty($rule['calendar_days'])) {
                        $requiredCalendarDays = (int) $rule['calendar_days'];
                    }
                    break;
                }
            }
        } elseif (!empty($leaveType->min_notice_days)) {
            $requiredCalendarDays = (int) $leaveType->min_notice_days;
        }

        if ($requiredWorkingDays !== null) {
            $working = $this->countWorkingDaysBetween($applied->copy()->addDay(), $start);
            if ($working < $requiredWorkingDays) {
                return __('This leave requires at least :days working days\' notice.', ['days' => $requiredWorkingDays]);
            }
        }

        if ($requiredCalendarDays !== null) {
            $diff = $applied->diffInDays($start, false);
            if ($diff < $requiredCalendarDays) {
                return __('This leave requires at least :days days\' advance notice.', ['days' => $requiredCalendarDays]);
            }
        }

        return null;
    }

    public function validateConsecutiveAndMonthly(LeaveType $leaveType, Employee $employee, string $startDate, string $endDate, float $totalDays): ?string
    {
        if (!empty($leaveType->max_consecutive_days) && $totalDays > (float) $leaveType->max_consecutive_days) {
            return __('You can apply a maximum of :days consecutive day(s) for :type.', [
                'days' => $leaveType->max_consecutive_days,
                'type' => $leaveType->title,
            ]);
        }

        if (!empty($leaveType->monthly_limit)) {
            $monthStart = Carbon::parse($startDate)->startOfMonth()->toDateString();
            $monthEnd = Carbon::parse($startDate)->endOfMonth()->toDateString();
            $usedThisMonth = (float) LocalLeave::where('employee_id', $employee->id)
                ->where('leave_type_id', $leaveType->id)
                ->whereIn('status', ['Approved', 'Pending'])
                ->where(function ($q) {
                    $q->whereNull('remark')->orWhere('remark', '!=', 'System-generated substitute block');
                })
                ->whereBetween('start_date', [$monthStart, $monthEnd])
                ->sum('total_leave_days');

            if (($usedThisMonth + $totalDays) > (float) $leaveType->monthly_limit) {
                return __('Monthly limit for :type is :limit day(s). Already used/pending: :used.', [
                    'type' => $leaveType->title,
                    'limit' => $leaveType->monthly_limit,
                    'used' => $usedThisMonth,
                ]);
            }
        }

        return null;
    }

    protected function countWorkingDaysBetween(Carbon $from, Carbon $to): int
    {
        if ($to->lt($from)) {
            return 0;
        }
        $count = 0;
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            if (!$cursor->isWeekend()) {
                $count++;
            }
            $cursor->addDay();
        }

        return $count;
    }
}
