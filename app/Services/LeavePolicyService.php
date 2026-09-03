<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\Leave as LocalLeave;
use App\Models\LeaveBalanceEntry;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

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
     * Optional notice bands selectable on Create/Edit Leave Type.
     * Stored as leave_types.notice_rules JSON and enforced by validateNotice().
     */
    public static function noticeRulePresets(): array
    {
        return [
            '1_2_days' => [
                'label' => '1–2 days of leave: Requires 3 working days\' notice.',
                'rule' => ['min' => 1, 'max' => 2, 'working_days' => 3],
            ],
            '3_5_days' => [
                'label' => '3–5 days of leave: Requires 1 week\'s notice.',
                'rule' => ['min' => 3, 'max' => 5, 'calendar_days' => 7],
            ],
            'more_5_days' => [
                'label' => 'More than 5 days of leave: Requires 3 weeks\' notice.',
                'rule' => ['min' => 5.01, 'max' => null, 'calendar_days' => 21],
            ],
            '5_10_days' => [
                'label' => '5–10 days of leave: Require 4 weeks\' notice.',
                'rule' => ['min' => 5, 'max' => 10, 'calendar_days' => 28],
            ],
            'over_10_days' => [
                'label' => 'Over 10 days of leave: Require 6 weeks\' notice.',
                'rule' => ['min' => 10.01, 'max' => null, 'calendar_days' => 42],
            ],
        ];
    }

    /**
     * Map stored notice_rules back to preset keys for edit form selection.
     */
    public static function selectedNoticeRulePresetKeys(?array $rules): array
    {
        if (empty($rules) || !is_array($rules)) {
            return [];
        }

        $selected = [];
        foreach (self::noticeRulePresets() as $key => $preset) {
            foreach ($rules as $rule) {
                if (!is_array($rule)) {
                    continue;
                }
                if (self::noticeRulesMatch($preset['rule'], $rule)) {
                    $selected[] = $key;
                    break;
                }
            }
        }

        return $selected;
    }

    protected static function noticeRulesMatch(array $a, array $b): bool
    {
        $aWorking = !empty($a['working_days']) ? (int) $a['working_days'] : null;
        $bWorking = !empty($b['working_days']) ? (int) $b['working_days'] : null;
        $aCalendar = !empty($a['calendar_days']) ? (int) $a['calendar_days'] : null;
        $bCalendar = !empty($b['calendar_days']) ? (int) $b['calendar_days'] : null;

        $sameNotice = ($aWorking !== null && $aWorking === $bWorking)
            || ($aCalendar !== null && $aCalendar === $bCalendar);

        if (!$sameNotice) {
            return false;
        }

        $aMin = (float) ($a['min'] ?? 0);
        $bMin = (float) ($b['min'] ?? 0);
        $aMax = array_key_exists('max', $a) ? $a['max'] : null;
        $bMax = array_key_exists('max', $b) ? $b['max'] : null;

        $minClose = abs($aMin - $bMin) < 1.0;
        $maxSame = ($aMax === null && $bMax === null)
            || ($aMax !== null && $bMax !== null && abs((float) $aMax - (float) $bMax) < 0.5);

        return $minClose && $maxSame;
    }

    /**
     * Canonical policies from HR matrix. Old global leave settings remain as fallback.
     */
    public static function policyDefinitions(): array
    {
        return [
            'sick' => [
                'title' => 'Sick Leave',
                'days' => 7,
                'monthly_credit' => round(7 / 12, 2), // ~0.58 / month
                'annual_credit' => 7,
                'credit_frequency' => 'monthly',
                'is_prorata' => true,
                'is_carry_forward' => 0,
                'max_carry_forward' => 0,
                'is_encashable' => 0,
                'eligible_employee_types' => ['intern', 'full_time'],
                'policy_notes' => 'Yearly 7 days. Credited monthly (~0.58), not loaded as a period total. No CF. Intern + Full time.',
            ],
            'pl' => [
                'title' => 'Privilege Leave (PL)',
                'days' => 18,
                'monthly_credit' => 1.5,
                'annual_credit' => 18,
                'credit_frequency' => 'monthly',
                'is_prorata' => true,
                'is_carry_forward' => 1,
                'max_carry_forward' => 30,
                'is_encashable' => 1,
                'max_encash_on_exit' => 30,
                'eligible_employee_types' => ['full_time'],
                'notice_rules' => [
                    ['min' => 0.5, 'max' => 2, 'working_days' => 3],
                    ['min' => 3, 'max' => 5, 'calendar_days' => 7],
                    ['min' => 5.01, 'max' => 10, 'calendar_days' => 28],
                    ['min' => 10.01, 'max' => null, 'calendar_days' => 42],
                ],
                'policy_notes' => 'Yearly 18. Monthly 1.5 credit (not loaded upfront). Full time only.',
            ],
            'cl' => [
                'title' => 'Casual Leave (CL)',
                'days' => 0,
                'monthly_credit' => 0,
                'annual_credit' => 0,
                'credit_frequency' => 'seasonal',
                'is_prorata' => false,
                'is_carry_forward' => 0,
                'max_carry_forward' => 0,
                'is_encashable' => 0,
                'eligible_employee_types' => ['full_time'],
                'seasonal_months' => [5, 6, 7], // May–Jul only
                'min_notice_days' => 14,
                'policy_notes' => 'Not a separate annual bank. Apply only during May–Jul summer window.',
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
                'min_notice_days' => 7,
                'policy_notes' => 'As earned. Intern + Full time.',
            ],
            'optional_holiday' => [
                'title' => 'Optional Holiday',
                'days' => 2,
                'monthly_credit' => 0,
                'annual_credit' => 2,
                'credit_frequency' => 'annual',
                'is_prorata' => false,
                'is_carry_forward' => 0,
                'is_encashable' => 0,
                'min_notice_days' => 14,
                'eligible_employee_types' => ['intern', 'full_time'],
                'policy_notes' => 'Choose any 2 optional public holidays from the published list. Inform your reporting manager at least 2 weeks in advance.',
            ],
            'wfh' => [
                'title' => 'Work From Home (WFH)',
                'days' => 24,
                'monthly_credit' => 2,
                'annual_credit' => 24,
                'credit_frequency' => 'monthly_cap',
                'is_prorata' => false,
                'is_carry_forward' => 0,
                'is_encashable' => 0,
                'monthly_limit' => 2,
                'max_consecutive_days' => 2,
                'eligible_employee_types' => ['intern', 'full_time'],
                'policy_notes' => '2 days per month only (not an accumulating annual bank). Available to all employees.',
            ],
            'bereavement' => [
                'title' => 'Bereavement Leave',
                'days' => 7,
                'monthly_credit' => 0,
                'annual_credit' => 7,
                'credit_frequency' => 'event',
                'is_prorata' => false,
                'is_carry_forward' => 0,
                'is_encashable' => 0,
                'eligible_employee_types' => ['full_time'],
                'requires_family_relation' => true,
                'policy_notes' => 'Event-based 7 days when a qualifying bereavement occurs. No monthly accrual.',
            ],
            'on_ground' => [
                'title' => 'On Ground',
                'days' => 0,
                'monthly_credit' => 0,
                'annual_credit' => 0,
                'credit_frequency' => 'hidden',
                'is_prorata' => false,
                'eligible_employee_types' => [],
                'hide_from_balance' => true,
                'policy_notes' => 'Not a leave entitlement. Use attendance regularisation instead.',
            ],
        ];
    }

    public static function isSpectalPortal(?string $host = null): bool
    {
        return \App\Support\TenantHost::subdomainFromHost($host) === 'spectal';
    }

    /**
     * Spectal 2026 go-live is 1 Aug — transition cycle Aug–Dec 2026.
     * From 2027 onward: full calendar year.
     */
    public static function spectalCycleDates(?Carbon $asOf = null): array
    {
        $asOf = $asOf ? $asOf->copy() : Carbon::now('Asia/Kolkata');
        $year = (int) $asOf->year;

        if ($year === 2026) {
            $cycleStart = '2026-08-01';
            $cycleEnd = '2026-12-31';
            $label = '2026 (Aug–Dec transition)';
        } else {
            $cycleStart = $year . '-01-01';
            $cycleEnd = $year . '-12-31';
            $label = (string) $year;
        }

        return [
            'start_date' => date('Y-m-d', strtotime($cycleStart . ' -1 day')),
            'end_date' => date('Y-m-d', strtotime($cycleEnd . ' +1 day')),
            'cycle_start' => $cycleStart,
            'cycle_end' => $cycleEnd,
            'year' => (string) $year,
            'label' => $label,
        ];
    }

    public static function resolvePolicyCode(LeaveType $leaveType): ?string
    {
        if (!empty($leaveType->policy_code)) {
            return strtolower(trim((string) $leaveType->policy_code));
        }

        $t = strtolower((string) $leaveType->title);
        if (str_contains($t, 'privilege') || preg_match('/\bpl\b/', $t)) {
            return 'pl';
        }
        if (str_contains($t, 'sick') || str_contains($t, 'seek')) {
            return 'sick';
        }
        if (str_contains($t, 'casual')) {
            return 'cl';
        }
        if (str_contains($t, 'comp')) {
            return 'comp_off';
        }
        if (str_contains($t, 'optional')) {
            return 'optional_holiday';
        }
        if (str_contains($t, 'wfh') || str_contains($t, 'work from home')) {
            return 'wfh';
        }
        if (str_contains($t, 'bereavement')) {
            return 'bereavement';
        }
        if (str_contains($t, 'on ground') || str_contains($t, 'onground')) {
            return 'on_ground';
        }

        return null;
    }

    public static function employeeTypeCode(?Employee $employee): ?string
    {
        if (!$employee || empty($employee->employee_type_id)) {
            return null;
        }

        $raw = EmployeeType::where('id', $employee->employee_type_id)->value('code');

        return self::normalizeEmployeeTypeCode($raw !== null ? (string) $raw : null);
    }

    /**
     * Normalize messy DB codes (e.g. ConsultantID) to canonical leave-policy codes.
     */
    public static function normalizeEmployeeTypeCode(?string $code): ?string
    {
        if ($code === null || trim($code) === '') {
            return null;
        }

        $c = strtolower(trim($code));
        $c = str_replace([' ', '-'], '_', $c);

        if ($c === 'intern' || str_contains($c, 'intern')) {
            return 'intern';
        }
        if (str_contains($c, 'consultant')) {
            return 'consultant';
        }
        if (in_array($c, ['full_time', 'fulltime', 'permanent', 'ft', 'full_time_employee'], true)
            || str_contains($c, 'full_time')
            || str_contains($c, 'permanent')) {
            return 'full_time';
        }

        return $c;
    }

    /**
     * Codes that satisfy a "full_time" leave eligibility slot on Spectal.
     * Consultants are permanent-equivalent for leave balances (not interns).
     */
    public static function spectalFullTimeEquivalentCodes(): array
    {
        return ['full_time', 'consultant'];
    }

    /**
     * Leave categories visible on Spectal balance while on probation.
     */
    public static function spectalProbationVisibleCodes(): array
    {
        return ['sick', 'comp_off', 'wfh', 'optional_holiday'];
    }

    /**
     * Whether this leave type should appear on Spectal Leave Balance Summary.
     */
    public function shouldShowOnSpectalBalance(LeaveType $leaveType, ?Employee $employee = null, ?Carbon $asOf = null): bool
    {
        $code = self::resolvePolicyCode($leaveType);
        $defs = self::policyDefinitions();

        if ($code === 'on_ground' || (!empty($defs[$code]['hide_from_balance']))) {
            return false;
        }

        // Casual Leave: balance only during May–Jul window
        if ($code === 'cl') {
            $asOf = $asOf ? $asOf->copy() : Carbon::now('Asia/Kolkata');
            if (!in_array((int) $asOf->month, [5, 6, 7], true)) {
                return false;
            }
        }

        // Bereavement: only after manager grants days
        if ($code === 'bereavement') {
            if (!$employee) {
                return true; // company-wide dashboard still lists the type
            }
            if (!$this->employeeEligibleForSpectalType($leaveType, $employee)) {
                return false;
            }

            return $this->grantedDays($employee->id, $leaveType->id, LeaveBalanceEntry::TYPE_GRANT) > 0;
        }

        if (!$this->employeeEligibleForSpectalType($leaveType, $employee)) {
            return false;
        }

        // Probation: only Sick + Comp (bereavement handled above when granted)
        if ($employee && $this->isEmployeeInProbation($employee)) {
            $visible = self::spectalProbationVisibleCodes();
            if ($code === 'bereavement') {
                return true;
            }

            return in_array($code, $visible, true);
        }

        return true;
    }

    /**
     * Lightweight probation check (mirrors LeaveController without circular deps).
     */
    public function isEmployeeInProbation(?Employee $employee): bool
    {
        if (!$employee || empty($employee->company_doj)) {
            return false;
        }

        $settings = \App\Models\Utility::settings();
        $probationMonths = (int) ($settings['probation_months'] ?? 0);
        if ($probationMonths <= 0) {
            return false;
        }

        $doj = Carbon::parse($employee->company_doj);
        $probationEnd = $doj->copy()->addMonths($probationMonths);

        return Carbon::now()->lt($probationEnd);
    }

    public function grantedDays(int $employeeId, int $leaveTypeId, string $entryType = LeaveBalanceEntry::TYPE_GRANT, ?string $periodKey = null): float
    {
        if (!Schema::hasTable('leave_balance_entries')) {
            return 0.0;
        }

        $q = LeaveBalanceEntry::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('entry_type', $entryType);

        if ($periodKey !== null) {
            $q->where('period_key', $periodKey);
        }

        return (float) $q->sum('days');
    }

    public function openingBalanceDays(int $employeeId, int $leaveTypeId, ?string $periodKey = null): float
    {
        return $this->grantedDays($employeeId, $leaveTypeId, LeaveBalanceEntry::TYPE_OPENING, $periodKey)
            + $this->grantedDays($employeeId, $leaveTypeId, LeaveBalanceEntry::TYPE_ADJUSTMENT, $periodKey);
    }

    public function employeeEligibleForSpectalType(LeaveType $leaveType, ?Employee $employee): bool
    {
        if (!$employee) {
            return true;
        }

        $code = self::resolvePolicyCode($leaveType);
        $defs = self::policyDefinitions();
        $allowed = $leaveType->eligible_employee_types;
        if (empty($allowed) || !is_array($allowed)) {
            $allowed = $defs[$code]['eligible_employee_types'] ?? null;
        }

        // Empty allowed = all types (legacy); Spectal on_ground has []
        if ($code === 'on_ground') {
            return false;
        }
        if ($allowed === null) {
            return true;
        }
        if ($allowed === []) {
            return true; // bereavement historically all — Spectal defs set full_time
        }

        $empCode = self::employeeTypeCode($employee);
        if (!$empCode) {
            // Without classification, hide restricted types rather than over-grant
            return in_array($code, ['sick', 'comp_off'], true);
        }

        // Interns: Sick, Comp-off, and WFH
        if ($empCode === 'intern') {
            return in_array($code, ['sick', 'comp_off', 'wfh', 'optional_holiday'], true);
        }

        // Normalize allowed list; consultants count as full_time for Spectal leave matrix
        $allowedNorm = array_values(array_filter(array_map(
            fn ($a) => self::normalizeEmployeeTypeCode((string) $a) ?? strtolower((string) $a),
            $allowed
        )));
        $matchCodes = [$empCode];
        if (in_array($empCode, self::spectalFullTimeEquivalentCodes(), true)) {
            $matchCodes = array_values(array_unique(array_merge($matchCodes, self::spectalFullTimeEquivalentCodes())));
        }

        return count(array_intersect($matchCodes, $allowedNorm)) > 0;
    }

    /**
     * Mandatory (non-optional) public holiday covering a date, if any.
     */
    public static function getHolidayOnDate(string $date, int $createdBy, bool $includeOptional = false): ?string
    {
        $holiday = self::getHolidayRecordOnDate($date, $createdBy, $includeOptional);

        if ($holiday) {
            return (string) ($holiday->occasion ?? $holiday->title ?? __('Public Holiday'));
        }

        return null;
    }

    public static function getHolidayRecordOnDate(string $date, int $createdBy, bool $includeOptional = false): ?\App\Models\Holiday
    {
        if (!Schema::hasTable('holidays')) {
            return null;
        }

        $d = Carbon::parse($date)->toDateString();
        $query = \App\Models\Holiday::query()
            ->where(function ($q) use ($createdBy) {
                if (Schema::hasColumn('holidays', 'created_by')) {
                    $q->where('created_by', $createdBy)->orWhereNull('created_by');
                }
            })
            ->where(function ($q) use ($d) {
                $q->where(function ($sq) use ($d) {
                    $sq->where('start_date', '<=', $d)->where('end_date', '>=', $d);
                });
                if (Schema::hasColumn('holidays', 'holiday_date')) {
                    $q->orWhere('holiday_date', $d);
                }
            });

        if (!$includeOptional && Schema::hasColumn('holidays', 'is_optional')) {
            $query->where(function ($q) {
                $q->where('is_optional', 0)->orWhereNull('is_optional');
            });
        }

        return $query->first();
    }

    /**
     * Optional public holiday dates employees may claim (max 2 / year).
     *
     * @return array<int, array{date:string,label:string}>
     */
    public static function optionalHolidayDateOptions(int $createdBy, ?int $year = null): array
    {
        $year = $year ?: (int) Carbon::now('Asia/Kolkata')->year;
        if (!Schema::hasTable('holidays') || !Schema::hasColumn('holidays', 'is_optional')) {
            return [];
        }

        $rows = \App\Models\Holiday::query()
            ->where('created_by', $createdBy)
            ->where('is_optional', 1)
            ->whereYear('start_date', $year)
            ->orderBy('start_date')
            ->get();

        $options = [];
        foreach ($rows as $holiday) {
            $date = Carbon::parse($holiday->start_date);
            $options[] = [
                'date' => $date->toDateString(),
                'label' => $date->format('d M Y') . ' — ' . ($holiday->occasion ?? __('Optional Holiday')),
            ];
        }

        return $options;
    }

    public function validateSpectalApplication(LeaveType $leaveType, Employee $employee, string $startDate, ?string $endDate = null, float $totalDays = 1.0, string $dayType = 'full_day'): ?string
    {
        if (!self::isSpectalPortal()) {
            return null;
        }

        $code = self::resolvePolicyCode($leaveType);

        if ($code === 'on_ground') {
            return __('On Ground is not a leave entitlement. Please use Attendance Regularisation (On Ground).');
        }

        if (!$this->employeeEligibleForSpectalType($leaveType, $employee)) {
            return __('This leave type is not applicable for your employment type.');
        }

        if ($this->isEmployeeInProbation($employee)) {
            if ($code === 'pl') {
                return __('Privilege Leave (PL) is not available during probation.');
            }
            $visible = self::spectalProbationVisibleCodes();
            if ($code && !in_array($code, $visible, true) && $code !== 'bereavement' && $code !== 'optional_holiday') {
                return __('This leave type is not available during probation.');
            }
        }

        $endDate = $endDate ?? $startDate;
        $start = Carbon::parse($startDate, 'Asia/Kolkata')->startOfDay();
        $end = Carbon::parse($endDate, 'Asia/Kolkata')->startOfDay();
        $createdBy = (int) ($employee->created_by ?? \Auth::user()->creatorId());

        if ($code === 'optional_holiday') {
            if (!$start->eq($end)) {
                return __('Optional Holiday must be applied for a single date from the published optional public holiday list.');
            }
            $match = \App\Models\Holiday::query()
                ->where('created_by', $createdBy)
                ->where('is_optional', 1)
                ->where('start_date', '<=', $start->toDateString())
                ->where('end_date', '>=', $start->toDateString())
                ->first();
            if (!$match) {
                return __('Optional Holiday can only be taken on one of the published optional public holidays. You may choose any 2, with at least 2 weeks’ notice to your reporting manager.');
            }

            return null;
        }

        // Single day Sunday check for all leave types
        if ($start->eq($end) && $start->dayOfWeek === Carbon::SUNDAY) {
            return __('Leave cannot be applied on Sunday.');
        }

        // Single day Public Holiday check (optional holidays are working days unless claimed)
        if ($start->eq($end)) {
            $holiday = self::getHolidayRecordOnDate($startDate, $createdBy, false);
            if ($holiday) {
                $holidayDayType = (string) ($holiday->day_type ?? 'full_day');
                $blocks = $holidayDayType === 'full_day'
                    || $dayType === 'full_day'
                    || $holidayDayType === $dayType;
                if ($blocks) {
                    $name = (string) ($holiday->occasion ?? __('Public Holiday'));
                    return __('Cannot apply leave on a public holiday (:holiday).', ['holiday' => $name]);
                }
            }
        }

        // WFH specific restrictions:
        // 1. Allowed ONLY on Tuesday (2), Wednesday (3), Thursday (4)
        // 2. Blocked on Monday (1), Friday (5), Saturday (6), Sunday (0), and Public Holidays
        if ($code === 'wfh') {
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $dow = $d->dayOfWeek;
                if (!in_array($dow, [Carbon::TUESDAY, Carbon::WEDNESDAY, Carbon::THURSDAY], true)) {
                    return __('Work From Home (WFH) can only be applied for Tuesday, Wednesday, or Thursday.');
                }
                $holidayName = self::getHolidayOnDate($d->toDateString(), $createdBy, false);
                if ($holidayName !== null) {
                    return __('Work From Home cannot be applied on public holidays (:holiday).', ['holiday' => $holidayName]);
                }
            }
        }

        if ($code === 'bereavement') {
            $granted = $this->grantedDays((int) $employee->id, (int) $leaveType->id, LeaveBalanceEntry::TYPE_GRANT);
            if ($granted <= 0) {
                return __('Bereavement leave cannot be applied from Create Leave. HR must use “Grant Bereavement Leave” on Manage Leave (grants entitlement and creates the leave).');
            }
        }

        if ($code === 'cl') {
            $month = (int) $start->month;
            if (!in_array($month, [5, 6, 7], true)) {
                return __('Casual Leave can only be applied between May and July.');
            }
        }

        return null;
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

    /**
     * Calendar quarter bounds (Asia/Kolkata) for a given date.
     * Q1 Jan–Mar, Q2 Apr–Jun, Q3 Jul–Sep, Q4 Oct–Dec.
     */
    public static function calendarQuarterBounds(?Carbon $asOf = null): array
    {
        $asOf = $asOf ? $asOf->copy()->timezone('Asia/Kolkata') : Carbon::now('Asia/Kolkata');
        $year = (int) $asOf->year;
        $month = (int) $asOf->month;
        $q = (int) ceil($month / 3);
        $startMonth = (($q - 1) * 3) + 1;

        $start = Carbon::create($year, $startMonth, 1, 0, 0, 0, 'Asia/Kolkata')->startOfDay();
        $end = $start->copy()->addMonths(3)->subDay()->endOfDay();

        return [
            'quarter' => $q,
            'year' => $year,
            'label' => 'Q' . $q . ' ' . $year,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'start_carbon' => $start,
            'end_carbon' => $end,
        ];
    }

    public static function sameCalendarQuarter(string $dateA, string $dateB): bool
    {
        $a = self::calendarQuarterBounds(Carbon::parse($dateA, 'Asia/Kolkata'));
        $b = self::calendarQuarterBounds(Carbon::parse($dateB, 'Asia/Kolkata'));

        return $a['year'] === $b['year'] && $a['quarter'] === $b['quarter'];
    }

    /**
     * Eligible "worked" dates for Spectal comp-off: Sundays + company holidays
     * within the current quarter (and past / today only).
     *
     * @return array<int, array{date:string,label:string,kind:string}>
     */
    public static function spectalCompOffWorkedDateOptions(int $createdBy, ?Carbon $asOf = null): array
    {
        $asOf = $asOf ? $asOf->copy()->timezone('Asia/Kolkata')->startOfDay() : Carbon::now('Asia/Kolkata')->startOfDay();
        $q = self::calendarQuarterBounds($asOf);
        $start = $q['start_carbon']->copy();
        $end = $asOf->lt($q['end_carbon']) ? $asOf->copy() : $q['end_carbon']->copy()->startOfDay();

        $holidayLabels = [];
        if (\Schema::hasTable('holidays')) {
            $query = \App\Models\Holiday::query();
            if (\Schema::hasColumn('holidays', 'created_by')) {
                $query->where('created_by', $createdBy);
            }

            $holidays = $query->get();
            foreach ($holidays as $holiday) {
                $title = (string) ($holiday->occasion ?? $holiday->title ?? __('Holiday'));
                if (!empty($holiday->start_date) && !empty($holiday->end_date)) {
                    $hStart = Carbon::parse($holiday->start_date)->startOfDay();
                    $hEnd = Carbon::parse($holiday->end_date)->startOfDay();
                } elseif (!empty($holiday->holiday_date)) {
                    $hStart = Carbon::parse($holiday->holiday_date)->startOfDay();
                    $hEnd = $hStart->copy();
                } else {
                    continue;
                }

                for ($d = $hStart->copy(); $d->lte($hEnd); $d->addDay()) {
                    if ($d->lt($start) || $d->gt($end)) {
                        continue;
                    }
                    $holidayLabels[$d->toDateString()] = $title;
                }
            }
        }

        $options = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key = $d->toDateString();
            $isSunday = $d->dayOfWeek === Carbon::SUNDAY;
            $isHoliday = isset($holidayLabels[$key]);
            if (!$isSunday && !$isHoliday) {
                continue;
            }

            $kindParts = [];
            if ($isSunday) {
                $kindParts[] = __('Sunday');
            }
            if ($isHoliday) {
                $kindParts[] = $holidayLabels[$key];
            }
            $kind = implode(' · ', $kindParts);
            $options[] = [
                'date' => $key,
                'label' => $d->format('d M Y') . ' — ' . $kind,
                'kind' => $kind,
            ];
        }

        return array_reverse($options); // newest first
    }

    /**
     * Mutate leave type with Spectal canonical defaults (in-memory only).
     * Ensures WFH monthly caps / consecutive limits apply even when DB is misconfigured.
     */
    public function applySpectalLeaveTypeDefaults(LeaveType $leaveType): LeaveType
    {
        if (!self::isSpectalPortal()) {
            return $leaveType;
        }

        $code = self::resolvePolicyCode($leaveType);
        $defs = self::policyDefinitions();
        if (!$code || !isset($defs[$code])) {
            return $leaveType;
        }

        $def = $defs[$code];
        if (empty($leaveType->credit_frequency) || in_array($code, ['sick', 'pl', 'wfh', 'bereavement', 'cl'], true)) {
            $leaveType->credit_frequency = $def['credit_frequency'] ?? $leaveType->credit_frequency;
        }

        if ($code === 'pl') {
            $leaveType->monthly_credit = 1.5;
            $leaveType->annual_credit = 18;
            $leaveType->days = 18;
        }

        if ($code === 'sick') {
            $leaveType->annual_credit = 7;
            $leaveType->days = 7;
            $leaveType->monthly_credit = round(7 / 12, 2);
            $leaveType->credit_frequency = 'monthly';
            $leaveType->is_prorata = true;
        }

        if ($code === 'wfh') {
            $leaveType->monthly_limit = 2;
            $leaveType->monthly_credit = 2;
            $leaveType->max_consecutive_days = 2;
            $leaveType->credit_frequency = 'monthly_cap';
            $leaveType->days = 24; // yearly ceiling for withinMax; monthly_cap governs available
            $leaveType->annual_credit = 24;
            // WFH is same-/short-notice — strip accidental PL/CL notice rules from DB
            $leaveType->min_notice_days = 0;
            $leaveType->notice_rules = null;
        }

        if ($code === 'bereavement') {
            $leaveType->credit_frequency = 'event';
            $leaveType->annual_credit = 7;
            $leaveType->days = 7;
            $leaveType->monthly_credit = 0;
            $leaveType->min_notice_days = 0;
            $leaveType->notice_rules = null;
        }

        if ($code === 'optional_holiday') {
            $leaveType->credit_frequency = 'annual';
            $leaveType->annual_credit = 2;
            $leaveType->days = 2;
            $leaveType->monthly_credit = 0;
            $leaveType->min_notice_days = 14;
            $leaveType->notice_rules = null;
            $leaveType->is_prorata = false;
        }

        return $leaveType;
    }

    public function validateApplication(LeaveType $leaveType, Employee $employee, string $startDate, string $endDate, float $totalDays, ?string $familyRelation = null, ?string $appliedOn = null): ?string
    {
        $this->applySpectalLeaveTypeDefaults($leaveType);

        if ($error = $this->validateEligibility($leaveType, $employee)) {
            return $error;
        }

        $policyCode = self::resolvePolicyCode($leaveType);
        // Spectal WFH: no advance-notice requirement (monthly 2-day cap only)
        $skipNotice = self::isSpectalPortal() && $policyCode === 'wfh';
        if (!$skipNotice && ($error = $this->validateNotice($leaveType, $startDate, $totalDays, $appliedOn))) {
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
        // Spectal uses its own employment-type matrix (consultants = full_time, etc.)
        if (self::isSpectalPortal()) {
            if (!$this->employeeEligibleForSpectalType($leaveType, $employee)) {
                return __('This leave type is not applicable for your employment type.');
            }

            return null;
        }

        $codes = $leaveType->eligible_employee_types;
        if (empty($codes) || !is_array($codes)) {
            return null; // all employees
        }

        $empTypeCode = null;
        if (!empty($employee->employee_type_id)) {
            $raw = EmployeeType::where('id', $employee->employee_type_id)->value('code');
            $empTypeCode = self::normalizeEmployeeTypeCode($raw !== null ? (string) $raw : null);
        }

        if (empty($empTypeCode)) {
            return __('Employee type is not set. This leave type is restricted to: :types', [
                'types' => implode(', ', $codes),
            ]);
        }

        $allowedNorm = array_values(array_filter(array_map(
            fn ($a) => self::normalizeEmployeeTypeCode((string) $a) ?? strtolower((string) $a),
            $codes
        )));
        $matchCodes = [$empTypeCode];
        if (in_array($empTypeCode, self::spectalFullTimeEquivalentCodes(), true)) {
            $matchCodes = array_values(array_unique(array_merge($matchCodes, self::spectalFullTimeEquivalentCodes())));
        }

        if (count(array_intersect($matchCodes, $allowedNorm)) === 0) {
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
