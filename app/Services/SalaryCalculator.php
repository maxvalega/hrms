<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\PayrollSpecialAllowance;
use App\Models\PayrollSpecialDeduction;
use App\Models\ReimbursementClaim;

/**
 * Indian CTC Salary Calculator — Formula-Driven Rule Engine
 *
 * All values are computed from CTC using standard Indian payroll rules.
 * No dependency on DB component definitions for amounts — prevents
 * duplicate/inconsistent component issues.
 *
 * Rules:
 *  Basic           = CTC × basic_percentage%
 *  HRA             = 50% of Basic
 *  Conveyance      = ₹19,200 fixed (₹1,600/month)
 *  Medical         = ₹40,000 fixed (₹3,333/month)
 *  Employer PF     = 12% of min(Basic monthly, ₹15,000) × 12
 *  Gratuity        = 4.81% of Basic
 *  ESIC Employer   = 3.25% of Gross  (ONLY if Gross monthly ≤ ₹21,000)
 *  Gross           = CTC − (Employer PF + Gratuity + ESIC Employer)
 *  Special Allow.  = Gross − Basic − HRA − Conveyance − Medical  (balancing)
 *  PF Employee     = 12% of min(Basic monthly, ₹15,000) × 12
 *  ESIC Employee   = 0.75% of Gross  (ONLY if Gross monthly ≤ ₹21,000)
 *  Professional Tax= ₹200/month = ₹2,400/year
 *  Net Pay         = Gross − PF Employee − ESIC Employee − PT
 */
class SalaryCalculator
{
    // ── Statutory constants ──
    const PF_RATE              = 0.12;
    const PF_BASIC_CAP_MONTHLY = 15000;
    const ESIC_EMPLOYEE_RATE   = 0.0075;
    const ESIC_EMPLOYER_RATE   = 0.0325;
    const ESIC_GROSS_LIMIT     = 21000; // monthly ceiling
    const GRATUITY_RATE        = 0.0481;
    const PT_MONTHLY           = 200;
    const CONVEYANCE_ANNUAL    = 19200;
    const MEDICAL_ANNUAL       = 40000;

    // States/UTs where Professional Tax is NOT applicable (codes + full names)
    const PT_EXEMPT_STATES = [
        'RJ', 'Rajasthan',
        'UP', 'Uttar Pradesh',
        'DL', 'Delhi',
        'HR', 'Haryana',
        'PB', 'Punjab',
        'HP', 'Himachal Pradesh',
        'UT', 'Uttarakhand',
        'JK', 'Jammu and Kashmir', 'Jammu & Kashmir',
        'LA', 'Ladakh',
        'CH', 'Chandigarh',
        'AR', 'Arunachal Pradesh',
        'NL', 'Nagaland',
        'MZ', 'Mizoram',
        'AN', 'Andaman and Nicobar Islands', 'Andaman & Nicobar',
        'LD', 'Lakshadweep',
    ];

    protected SalaryCalculatorService $formulaEngine;

    public function __construct(SalaryCalculatorService $formulaEngine)
    {
        $this->formulaEngine = $formulaEngine;
    }

    public function calculate(int $employeeId, ?string $month = null, ?float $overrideCtc = null): array
    {
        $salary = EmployeeSalary::where('employee_id', $employeeId)->first();
        if (!$salary) {
            return ['employee_id' => $employeeId, 'error' => 'Employee salary setup not found.'];
        }

        // Allow callers to override the CTC (payroll run for a past month
        // should use the CTC that was active then, not today's value).
        $ctc             = ($overrideCtc !== null && $overrideCtc > 0)
            ? (float) $overrideCtc
            : (float) $salary->ctc;
        $basicPercentage = (float)$salary->basic_percentage;
        $pfEnabled       = (bool)$salary->is_pf_enabled;
        $esicEnabled     = (bool)$salary->is_esic_enabled;

        // ════════════════════════════════════════════════════
        // STEP 1 — Basic
        // ════════════════════════════════════════════════════
        $basicAnnual  = round($ctc * ($basicPercentage / 100));
        $basicMonthly = $basicAnnual / 12;

        // ════════════════════════════════════════════════════
        // STEP 2 — Employer contributions (these come OUT of CTC)
        // ════════════════════════════════════════════════════

        // PF Employer: 12% of min(Basic, ₹15,000) per month
        $pfBaseMonthly     = min($basicMonthly, self::PF_BASIC_CAP_MONTHLY);
        $pfEmployerMonthly = $pfEnabled ? round($pfBaseMonthly * self::PF_RATE) : 0;
        $pfEmployerAnnual  = $pfEmployerMonthly * 12;

        // Gratuity: 4.81% of Basic annual
        $gratuityAnnual = round($basicAnnual * self::GRATUITY_RATE);

        // Estimate gross without ESIC first, to check eligibility
        $grossWithoutEsic        = $ctc - $pfEmployerAnnual - $gratuityAnnual;
        $grossWithoutEsicMonthly = $grossWithoutEsic / 12;

        // ESIC Employer: 3.25% of Gross — ONLY if gross monthly ≤ ₹21,000
        // ESIC applies only when employee is ESIC-enabled and falls under ESIC gross ceiling.
        $esicApplicable      = $esicEnabled && ($grossWithoutEsicMonthly <= self::ESIC_GROSS_LIMIT);
        $esicEmployerAnnual  = 0;
        $esicEmployeeAnnual  = 0;

        if ($esicApplicable) {
            // When ESIC applies, gross needs recalculation:
            // CTC = Gross + PF + Gratuity + 0.0325×Gross
            // CTC - PF - Gratuity = Gross × 1.0325
            $grossAnnual        = round(($ctc - $pfEmployerAnnual - $gratuityAnnual) / (1 + self::ESIC_EMPLOYER_RATE));
            $esicEmployerAnnual = round($grossAnnual * self::ESIC_EMPLOYER_RATE);
            $esicEmployeeAnnual = round($grossAnnual * self::ESIC_EMPLOYEE_RATE);
        } else {
            $grossAnnual = round($grossWithoutEsic);
        }

        $totalEmployerCost = $pfEmployerAnnual + $gratuityAnnual + $esicEmployerAnnual;

        // ════════════════════════════════════════════════════
        // STEP 3 — Earnings breakdown
        // ════════════════════════════════════════════════════
        $hraAnnual         = round($basicAnnual * 0.50);
        $conveyanceAnnual  = self::CONVEYANCE_ANNUAL;
        $medicalAnnual     = self::MEDICAL_ANNUAL;

        // Adjust fixed components if CTC is too small
        $remainingForFixed = $grossAnnual - $basicAnnual - $hraAnnual;
        if ($remainingForFixed < $conveyanceAnnual + $medicalAnnual) {
            if ($remainingForFixed >= $conveyanceAnnual) {
                $medicalAnnual = max(0, $remainingForFixed - $conveyanceAnnual);
            } else {
                $conveyanceAnnual = max(0, $remainingForFixed);
                $medicalAnnual    = 0;
            }
        }

        // Special Allowance = balancing figure
        $specialAnnual = max(0, $grossAnnual - $basicAnnual - $hraAnnual - $conveyanceAnnual - $medicalAnnual);

        $earnings = [
            ['name' => 'Basic',                'amount' => round($basicAnnual, 2),       'frequency' => 'monthly'],
            ['name' => 'HRA',                  'amount' => round($hraAnnual, 2),         'frequency' => 'monthly'],
            ['name' => 'Conveyance Allowance',  'amount' => round($conveyanceAnnual, 2),  'frequency' => 'monthly'],
            ['name' => 'Medical Allowance',     'amount' => round($medicalAnnual, 2),     'frequency' => 'monthly'],
            ['name' => 'Special Allowance',     'amount' => round($specialAnnual, 2),     'frequency' => 'monthly'],
        ];

        $baseGrossAnnual = $grossAnnual;
        $baseGrossMonthly = round($baseGrossAnnual / 12, 2);
        $specialAllowance = $this->computeSpecialAllowances($employeeId, $month);
        $totalSpecialAllowanceAnnual = $specialAllowance['annual_total'];
        $totalSpecialAllowanceMonthly = $specialAllowance['monthly_total'];
        if (!empty($specialAllowance['items'])) {
            $earnings = array_merge($earnings, $specialAllowance['items']);
        }
        $grossAnnual = $baseGrossAnnual + $totalSpecialAllowanceAnnual;

        $specialDeductions = $this->computeSpecialDeductions($employeeId, $month);
        $specialDeductionsAnnual = $specialDeductions['annual_total'];
        $specialDeductionsMonthly = $specialDeductions['monthly_total'];

        // ════════════════════════════════════════════════════
        // STEP 4 — Employee deductions
        // ════════════════════════════════════════════════════

        // PF Employee = same as Employer PF
        $pfEmployeeMonthly = $pfEmployerMonthly;
        $pfEmployeeAnnual  = $pfEmployeeMonthly * 12;

        // Professional Tax — exempt in certain states
        $employee = Employee::find($employeeId);
        $ptExempt = $employee && in_array($employee->present_state, self::PT_EXEMPT_STATES, true);
        $ptAnnual = $ptExempt ? 0 : self::PT_MONTHLY * 12;

        // ESIC must be calculated on total gross earnings of the selected month (base + additional one-time earnings).
        $extraGrossMonthly = $totalSpecialAllowanceMonthly;
        $esicEmployeeBaseMonthly = $esicApplicable ? round($baseGrossMonthly * self::ESIC_EMPLOYEE_RATE, 2) : 0.0;
        $esicEmployerBaseMonthly = $esicApplicable ? round($baseGrossMonthly * self::ESIC_EMPLOYER_RATE, 2) : 0.0;
        $esicEmployeeExtraMonthly = $esicApplicable ? round($extraGrossMonthly * self::ESIC_EMPLOYEE_RATE, 2) : 0.0;
        $esicEmployerExtraMonthly = $esicApplicable ? round($extraGrossMonthly * self::ESIC_EMPLOYER_RATE, 2) : 0.0;

        $esicEmployeeMonthly = round($esicEmployeeBaseMonthly + $esicEmployeeExtraMonthly, 2);
        $esicEmployerMonthly = round($esicEmployerBaseMonthly + $esicEmployerExtraMonthly, 2);

        $esicEmployeeAnnual = round(($esicEmployeeBaseMonthly * 12) + $esicEmployeeExtraMonthly, 2);
        $esicEmployerAnnual = round(($esicEmployerBaseMonthly * 12) + $esicEmployerExtraMonthly, 2);

        $totalEmployerCost = $pfEmployerAnnual + $gratuityAnnual + $esicEmployerAnnual;

        $baseDeductionAnnual = $pfEmployeeAnnual + $esicEmployeeAnnual + $ptAnnual;
        $totalDeductionAnnual = $baseDeductionAnnual + $specialDeductionsAnnual;

        // ════════════════════════════════════════════════════
        // STEP 5 — Net pay
        // ════════════════════════════════════════════════════
        $reimbursement           = $this->computeReimbursements($employeeId, $month);
        $totalReimbursementAnnual = $reimbursement['annual_total'];
        $reimbursementMonthly = round($totalReimbursementAnnual / 12, 2);
        $grossMonthly = round($baseGrossMonthly + $totalSpecialAllowanceMonthly, 2);
        $ptMonthly = $ptExempt ? 0 : self::PT_MONTHLY;
        $deductionsMonthly = round($pfEmployeeMonthly + $ptMonthly + $esicEmployeeMonthly + $specialDeductionsMonthly, 2);
        $netAnnual               = $grossAnnual - $totalDeductionAnnual + $totalReimbursementAnnual;
        $netMonthly = round($grossMonthly - $deductionsMonthly + $reimbursementMonthly, 2);

        // ════════════════════════════════════════════════════
        // STEP 6 — Build statutory arrays for view
        // ════════════════════════════════════════════════════
        $deductionStatutory = [];
        $deductionStatutory[] = ['name' => 'Professional Tax', 'amount' => round($ptAnnual, 2), 'frequency' => 'monthly'];
        $deductionStatutory[] = ['name' => 'PF Employee',      'amount' => round($pfEmployeeAnnual, 2), 'frequency' => 'monthly'];
        if ($esicApplicable) {
            $deductionStatutory[] = ['name' => 'ESIC Employee', 'amount' => round($esicEmployeeBaseMonthly * 12, 2), 'frequency' => 'monthly'];
            if ($esicEmployeeExtraMonthly > 0) {
                $deductionStatutory[] = ['name' => 'ESIC Employee (Additional Earning)', 'amount' => round($esicEmployeeExtraMonthly, 2), 'frequency' => 'one-time'];
            }
        }

        $benefitStatutory = [];
        $benefitStatutory[] = ['name' => 'Employer PF', 'amount' => round($pfEmployerAnnual, 2), 'frequency' => 'monthly'];
        $benefitStatutory[] = ['name' => 'Gratuity',    'amount' => round($gratuityAnnual, 2), 'frequency' => 'monthly'];
        if ($esicApplicable) {
            $benefitStatutory[] = ['name' => 'ESIC Employer', 'amount' => round($esicEmployerBaseMonthly * 12, 2), 'frequency' => 'monthly'];
            if ($esicEmployerExtraMonthly > 0) {
                $benefitStatutory[] = ['name' => 'ESIC Employer (Additional Earning)', 'amount' => round($esicEmployerExtraMonthly, 2), 'frequency' => 'one-time'];
            }
        }

        // ════════════════════════════════════════════════════
        // RETURN
        // ════════════════════════════════════════════════════
        return [
            'employee_id'    => $employeeId,
            'month'          => $month,
            'ctc_annual'     => round($ctc, 2),
            'earnings'       => $earnings,
            'deductions'     => $specialDeductions['items'],
            'benefits'       => [],
            'reimbursements' => $reimbursement['items'],
            'statutory' => [
                'rules' => [
                    'basic_percentage'     => $basicPercentage,
                    'pf_enabled'           => $pfEnabled,
                    'pf_rate'              => self::PF_RATE,
                    'pf_basic_cap_monthly' => self::PF_BASIC_CAP_MONTHLY,
                    'esic_applicable'      => $esicApplicable,
                    'esic_employee_rate'   => self::ESIC_EMPLOYEE_RATE,
                    'esic_employer_rate'   => self::ESIC_EMPLOYER_RATE,
                    'esic_gross_limit'     => self::ESIC_GROSS_LIMIT,
                    'gratuity_rate'        => self::GRATUITY_RATE,
                    'pt_monthly'           => $ptExempt ? 0 : self::PT_MONTHLY,
                    'pt_exempt'            => $ptExempt,
                ],
                'deductions' => $deductionStatutory,
                'benefits'   => $benefitStatutory,
            ],
            'totals' => [
                'gross_annual'          => round($grossAnnual, 2),
                'gross_monthly'         => $grossMonthly,
                'deductions_annual'     => round($totalDeductionAnnual, 2),
                'deductions_monthly'    => $deductionsMonthly,
                'benefits_annual'       => round($totalEmployerCost, 2),
                'benefits_monthly'      => round($totalEmployerCost / 12, 2),
                'reimbursements_annual' => round($totalReimbursementAnnual, 2),
                'net_annual'            => round($netAnnual, 2),
                'net_monthly'           => $netMonthly,
            ],
        ];
    }

    protected function computeReimbursements(int $employeeId, ?string $month): array
    {
        if (empty($month)) {
            return ['annual_total' => 0.0, 'items' => []];
        }

        $claims = ReimbursementClaim::query()
            ->where('employee_id', $employeeId)
            ->where('claim_month', $month)
            ->where('status', 'approved')
            ->get();

        $items = [];
        $sum   = 0.0;
        foreach ($claims as $claim) {
            $items[] = [
                'name'      => !empty($claim->component_name)
                    ? $claim->component_name
                    : ('Reimbursement #' . $claim->id),
                'amount'    => round((float)$claim->amount * 12, 2),
                'frequency' => 'one-time',
            ];
            $sum += (float)$claim->amount * 12;
        }

        return ['annual_total' => round($sum, 2), 'items' => $items];
    }

    protected function computeSpecialAllowances(int $employeeId, ?string $month): array
    {
        if (empty($month)) {
            return ['annual_total' => 0.0, 'monthly_total' => 0.0, 'items' => []];
        }

        $allowances = PayrollSpecialAllowance::query()
            ->where('employee_id', $employeeId)
            ->where('month', $month)
            ->get();

        $items = [];
        $sum = 0.0;
        foreach ($allowances as $allowance) {
            $oneTime = round((float)$allowance->amount, 2);
            $title = trim((string)($allowance->title ?? ''));
            $items[] = [
                'name' => (!empty($title) ? $title : 'Bonus') . ' (' . $allowance->month . ')',
                'amount' => $oneTime,
                'frequency' => 'one-time',
            ];
            $sum += $oneTime;
        }

        $sum = round($sum, 2);
        return ['annual_total' => $sum, 'monthly_total' => $sum, 'items' => $items];
    }

    protected function computeSpecialDeductions(int $employeeId, ?string $month): array
    {
        if (empty($month)) {
            return ['annual_total' => 0.0, 'monthly_total' => 0.0, 'items' => []];
        }

        $deductions = PayrollSpecialDeduction::query()
            ->where('employee_id', $employeeId)
            ->where('month', $month)
            ->get();

        $items = [];
        $sum = 0.0;
        foreach ($deductions as $deduction) {
            $oneTime = round((float)$deduction->amount, 2);
            $title = trim((string)($deduction->title ?? ''));
            $items[] = [
                'name' => (!empty($title) ? $title : 'Penalty') . ' (' . $deduction->month . ')',
                'amount' => $oneTime,
                'frequency' => 'one-time',
            ];
            $sum += $oneTime;
        }

        $sum = round($sum, 2);
        return ['annual_total' => $sum, 'monthly_total' => $sum, 'items' => $items];
    }
}
