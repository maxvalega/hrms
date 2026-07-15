<?php

namespace App\Http\Controllers;

use App\Exports\PayrollProcessExport;
use App\Exports\PayrollDetailedExport;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\PaySchedule;
use App\Models\Payroll;
use App\Models\ReimbursementClaim;
use App\Models\PayrollSupplementaryAdjustment;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Models\StructureComponent;
use App\Models\AttendanceEmployee;
use App\Models\PayrollAttendanceSync;
use App\Models\PayrollSpecialAllowance;
use App\Models\PayrollSpecialDeduction;
use App\Models\SalaryIncrementHistory;
use App\Models\User;
use App\Models\Utility;
use App\Services\SalaryCalculator;
use App\Services\StatutoryCalculator;
use App\Services\TDSCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PayrollModuleController extends Controller
{
    private function ensurePayScheduleCycleColumn(): void
    {
        $columnExists = DB::selectOne(
            "SELECT COUNT(*) AS aggregate
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'pay_schedule'
               AND COLUMN_NAME = 'attendance_cycle_start_day'"
        );

        if ((int)($columnExists->aggregate ?? 0) > 0) {
            return;
        }

        try {
            DB::statement(
                "ALTER TABLE pay_schedule
                 ADD COLUMN attendance_cycle_start_day TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER pay_day"
            );
        } catch (\Throwable $exception) {
            if (stripos($exception->getMessage(), 'Duplicate column') === false) {
                throw $exception;
            }
        }
    }

    /**
     * Rebuild a single payroll row's "Total Gross (Earned)" value the same way
     * the salary statement export does: each earning component is treated as
     * monthly (annual/12) or one-time, and then pro-rated by attendance
     * (paid_days / calendar_days). Guarantees reconciliation with the export.
     */
    private function earnedGrossForPayrollRow($row): float
    {
        $stat = $row->statutory_json ?? [];
        if (is_string($stat)) {
            $stat = json_decode($stat, true) ?: [];
        }
        $attn = $stat['attendance'] ?? [];
        $monthCal = (int) ($attn['month_calendar_days'] ?? date('t', strtotime(($row->month ?? '2026-01') . '-01')));
        $paidDays = (float) ($attn['paid_days'] ?? $monthCal);

        $earnings = $row->earnings_json ?? [];
        if (is_string($earnings)) {
            $earnings = json_decode($earnings, true) ?: [];
        }

        $total = 0.0;
        foreach ($earnings as $item) {
            $annual    = (float) ($item['amount'] ?? 0);
            $isOneTime = (($item['frequency'] ?? 'monthly') === 'one-time');
            $monthly   = $isOneTime ? $annual : round($annual / 12, 2);
            $paid      = $isOneTime
                ? $monthly
                : ($monthCal > 0 ? round(($monthly / $monthCal) * $paidDays, 2) : $monthly);
            $total += $paid;
        }
        return round($total, 2);
    }

    /**
     * Resolve the CTC that was active for an employee during a given month by
     * walking `salary_increment_history`. Returns the latest increment's
     * `new_ctc` whose effective_date ≤ last day of the target month. If the
     * target month is before any history row, falls back to that first row's
     * `old_ctc` (joining CTC). If there is no history at all, returns null
     * so the caller can fall back to the live `employee_salaries.ctc`.
     */
    private function ctcEffectiveForMonth(int $employeeId, string $month): ?float
    {
        $monthEnd = \Carbon\Carbon::parse($month . '-01')->endOfMonth()->toDateString();

        $latest = SalaryIncrementHistory::where('employee_id', $employeeId)
            ->where('effective_date', '<=', $monthEnd)
            ->orderByDesc('effective_date')
            ->first();
        if ($latest) {
            return (float) $latest->new_ctc;
        }

        $first = SalaryIncrementHistory::where('employee_id', $employeeId)
            ->orderBy('effective_date')
            ->first();
        if ($first) {
            return (float) $first->old_ctc;
        }

        return null;
    }

    public function paySchedule()
    {
        $this->ensurePayScheduleCycleColumn();

        $creatorId = \Auth::user()->creatorId();
        $schedule = PaySchedule::firstOrCreate(
            ['created_by' => $creatorId],
            [
                'pay_frequency' => 'monthly',
                'pay_day' => 27,
                'working_days' => 'mon,tue,wed,thu,fri,sat',
                'start_month' => now()->format('Y-m'),
                'status' => 1,
            ]
        );

        return view('payroll.schedule', compact('schedule'));
    }

    public function savePaySchedule(Request $request)
    {
        $this->ensurePayScheduleCycleColumn();

        $creatorId = \Auth::user()->creatorId();
        $schedule = PaySchedule::where('created_by', $creatorId)->firstOrFail();

        $data = $request->validate([
            'pay_day' => 'required|integer|min:1|max:31',
            'attendance_cycle_start_day' => 'nullable|integer|min:1|max:28',
            'working_days' => 'required|array|min:1',
            'start_month' => 'required|string|max:7',
            'status' => 'nullable|boolean',
        ]);

        $updateData = [
            'pay_day' => (int)$data['pay_day'],
            'attendance_cycle_start_day' => (int)($data['attendance_cycle_start_day'] ?? 1),
            'working_days' => implode(',', $data['working_days']),
            'start_month' => $data['start_month'],
            'status' => $request->has('status') ? 1 : 0,
        ];

        $schedule->update($updateData);

        return back()->with('success', __('Pay schedule updated.'));
    }

    public function components(Request $request)
    {
        $creatorId = \Auth::user()->creatorId();
        $defaultStructure = SalaryStructure::firstOrCreate(
            ['name' => 'India Standard Structure', 'created_by' => $creatorId],
            ['country' => 'India']
        );
        $structures = SalaryStructure::where('created_by', $creatorId)->orderBy('id')->get();
        $category = $request->get('category', 'earning');

        $components = SalaryComponent::where('created_by', $creatorId)
            ->when($category !== 'all', function ($q) use ($category) {
                $q->where('category', $category);
            })
            ->orderBy('id', 'desc')
            ->get();

        return view('payroll.components', compact('components', 'structures', 'category', 'defaultStructure'));
    }

    public function storeComponent(Request $request)
    {
        $creatorId = \Auth::user()->creatorId();
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'category' => 'required|in:earning,deduction,benefit,reimbursement',
            'calculation_type' => 'required|in:fixed,percentage,variable,formula',
            'value' => 'nullable|numeric|min:0',
            'formula' => 'nullable|string',
            'max_limit' => 'nullable|numeric|min:0',
            'frequency' => 'required|in:monthly,yearly,one-time',
            'is_taxable' => 'nullable|boolean',
            'is_pf_applicable' => 'nullable|boolean',
            'is_esic_applicable' => 'nullable|boolean',
            'structure_id' => 'required|integer',
            'status' => 'nullable|boolean',
        ]);

        $type = $data['category'] === 'benefit' ? 'employer' : ($data['category'] === 'deduction' ? 'deduction' : 'earning');
        $normalizedCalcType = $data['calculation_type'] === 'variable' ? 'fixed' : $data['calculation_type'];

        $component = SalaryComponent::create([
            'name' => $data['name'],
            'category' => $data['category'],
            'type' => $type,
            'calculation_type' => $normalizedCalcType,
            'value' => $data['value'] ?? null,
            'formula' => $data['formula'] ?? null,
            'max_limit' => $data['max_limit'] ?? null,
            'is_taxable' => $request->has('is_taxable') ? 1 : 0,
            'is_pf_applicable' => $request->has('is_pf_applicable') ? 1 : 0,
            'is_esic_applicable' => $request->has('is_esic_applicable') ? 1 : 0,
            'frequency' => $data['frequency'],
            'status' => $request->has('status') ? 1 : 0,
            'created_by' => $creatorId,
        ]);

        StructureComponent::updateOrCreate(
            ['structure_id' => (int)$data['structure_id'], 'component_id' => $component->id],
            ['priority' => (int)$request->get('priority', 999)]
        );

        return back()->with('success', __('Salary component added.'));
    }

    public function bulkActionComponents(Request $request)
    {
        $creatorId = \Auth::user()->creatorId();
        $data = $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer',
        ]);

        $query = SalaryComponent::where('created_by', $creatorId)->whereIn('id', $data['ids']);

        if ($data['action'] === 'delete') {
            $count = $query->count();
            // Also remove from structure_components
            StructureComponent::whereIn('component_id', $data['ids'])->delete();
            $query->delete();
            return back()->with('success', __(':count component(s) deleted.', ['count' => $count]));
        }

        $status = $data['action'] === 'activate' ? 1 : 0;
        $count = $query->count();
        $query->update(['status' => $status]);

        $label = $data['action'] === 'activate' ? __('activated') : __('deactivated');
        return back()->with('success', __(':count component(s) :label.', ['count' => $count, 'label' => $label]));
    }

    public function seedDefaultComponents()
    {
        $creatorId = \Auth::user()->creatorId();
        $structure = SalaryStructure::firstOrCreate(
            ['name' => 'India Standard Structure', 'created_by' => $creatorId],
            ['country' => 'India']
        );

        $defaults = [
            ['name' => 'House Rent Allowance', 'category' => 'earning', 'calculation_type' => 'percentage', 'value' => 50, 'formula' => 'BASIC', 'is_pf_applicable' => 0, 'is_esic_applicable' => 1, 'is_taxable' => 1, 'frequency' => 'monthly', 'priority' => 20],
            ['name' => 'Conveyance Allowance', 'category' => 'earning', 'calculation_type' => 'fixed', 'value' => 19200, 'formula' => null, 'is_pf_applicable' => 1, 'is_esic_applicable' => 0, 'is_taxable' => 1, 'frequency' => 'monthly', 'priority' => 30],
            ['name' => 'Children Education Allowance', 'category' => 'earning', 'calculation_type' => 'fixed', 'value' => 2400, 'formula' => null, 'is_pf_applicable' => 1, 'is_esic_applicable' => 1, 'is_taxable' => 1, 'frequency' => 'monthly', 'priority' => 35],
            ['name' => 'Transport Allowance', 'category' => 'earning', 'calculation_type' => 'fixed', 'value' => 1600, 'formula' => null, 'is_pf_applicable' => 1, 'is_esic_applicable' => 1, 'is_taxable' => 1, 'frequency' => 'monthly', 'priority' => 40],
            ['name' => 'Travelling Allowance', 'category' => 'earning', 'calculation_type' => 'fixed', 'value' => 0, 'formula' => null, 'is_pf_applicable' => 1, 'is_esic_applicable' => 0, 'is_taxable' => 1, 'frequency' => 'monthly', 'priority' => 45],
            ['name' => 'Medical Allowance', 'category' => 'earning', 'calculation_type' => 'fixed', 'value' => 15000, 'formula' => null, 'is_pf_applicable' => 1, 'is_esic_applicable' => 1, 'is_taxable' => 1, 'frequency' => 'monthly', 'priority' => 50],
            ['name' => 'Fixed Allowance', 'category' => 'earning', 'calculation_type' => 'fixed', 'value' => 0, 'formula' => null, 'is_pf_applicable' => 1, 'is_esic_applicable' => 1, 'is_taxable' => 1, 'frequency' => 'monthly', 'priority' => 55],
            ['name' => 'Overtime Allowance', 'category' => 'earning', 'calculation_type' => 'fixed', 'value' => 0, 'formula' => null, 'is_pf_applicable' => 0, 'is_esic_applicable' => 1, 'is_taxable' => 1, 'frequency' => 'monthly', 'priority' => 60],
            ['name' => 'Gratuity', 'category' => 'benefit', 'calculation_type' => 'formula', 'value' => null, 'formula' => 'BASIC * 0.0481', 'is_pf_applicable' => 0, 'is_esic_applicable' => 0, 'is_taxable' => 0, 'frequency' => 'monthly', 'priority' => 70],
            ['name' => 'Bonus', 'category' => 'earning', 'calculation_type' => 'fixed', 'value' => 0, 'formula' => null, 'is_pf_applicable' => 0, 'is_esic_applicable' => 0, 'is_taxable' => 1, 'frequency' => 'yearly', 'priority' => 80],
            ['name' => 'Commission', 'category' => 'earning', 'calculation_type' => 'fixed', 'value' => 0, 'formula' => null, 'is_pf_applicable' => 0, 'is_esic_applicable' => 1, 'is_taxable' => 1, 'frequency' => 'monthly', 'priority' => 82],
            ['name' => 'Leave Encashment', 'category' => 'earning', 'calculation_type' => 'fixed', 'value' => 0, 'formula' => null, 'is_pf_applicable' => 0, 'is_esic_applicable' => 0, 'is_taxable' => 1, 'frequency' => 'one-time', 'priority' => 84],
            ['name' => 'Notice Pay', 'category' => 'deduction', 'calculation_type' => 'fixed', 'value' => 0, 'formula' => null, 'is_pf_applicable' => 0, 'is_esic_applicable' => 0, 'is_taxable' => 0, 'frequency' => 'one-time', 'priority' => 90],
        ];

        foreach ($defaults as $item) {
            $type = $item['category'] === 'benefit' ? 'employer' : ($item['category'] === 'deduction' ? 'deduction' : 'earning');
            $component = SalaryComponent::updateOrCreate(
                ['name' => $item['name'], 'created_by' => $creatorId],
                [
                    'category' => $item['category'],
                    'type' => $type,
                    'calculation_type' => $item['calculation_type'],
                    'value' => $item['value'],
                    'formula' => $item['formula'],
                    'is_taxable' => $item['is_taxable'],
                    'is_pf_applicable' => $item['is_pf_applicable'],
                    'is_esic_applicable' => $item['is_esic_applicable'],
                    'frequency' => $item['frequency'],
                    'status' => 1,
                ]
            );

            StructureComponent::updateOrCreate(
                ['structure_id' => $structure->id, 'component_id' => $component->id],
                ['priority' => $item['priority']]
            );
        }

        return redirect()->route('payroll.components', ['category' => 'earning'])
            ->with('success', __('Default salary components added successfully.'));
    }

    public function employeeSalary()
    {
        $creatorId = \Auth::user()->creatorId();
        $structures = SalaryStructure::where('created_by', $creatorId)->orderBy('id')->get();
        $employees = Employee::where('created_by', $creatorId)->orderBy('name')->get();
        $salaries = EmployeeSalary::query()->whereIn('employee_id', $employees->pluck('id'))->get()->keyBy('employee_id');
        $exportMonth = request()->get('export_month', now()->format('Y-m'));

        return view('payroll.employee_salary', compact('employees', 'structures', 'salaries', 'exportMonth'));
    }

    public function saveEmployeeSalary(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|integer',
            'structure_id' => 'required|integer',
            'ctc' => 'required|numeric|min:0',
            'basic_percentage' => 'required|numeric|min:1|max:100',
            'is_pf_enabled' => 'nullable|boolean',
            'is_esic_enabled' => 'nullable|boolean',
            'overtime_enabled' => 'nullable|boolean',
            'overtime_formula' => 'nullable|in:basic,gross',
        ]);

        $empId = (int)$data['employee_id'];
        $newCtc = (float)$data['ctc'];

        // Log salary history if CTC changed
        $existing = EmployeeSalary::where('employee_id', $empId)->first();
        if ($existing && (float)$existing->ctc != $newCtc && (float)$existing->ctc > 0) {
            $oldCtc = (float)$existing->ctc;
            $diff = $newCtc - $oldCtc;
            $pct = $oldCtc > 0 ? round(($diff / $oldCtc) * 100, 2) : 0;
            SalaryIncrementHistory::create([
                'employee_id' => $empId,
                'old_ctc' => $oldCtc,
                'new_ctc' => $newCtc,
                'increment_amount' => $diff,
                'increment_percentage' => $pct,
                'effective_date' => now()->toDateString(),
                'remarks' => 'Salary updated via Employee Salary page',
                'created_by' => \Auth::user()->creatorId(),
            ]);
        }

        EmployeeSalary::updateOrCreate(
            ['employee_id' => $empId],
            [
                'structure_id' => (int)$data['structure_id'],
                'ctc' => $newCtc,
                'basic_percentage' => (float)$data['basic_percentage'],
                'is_pf_enabled' => $request->has('is_pf_enabled') ? 1 : 0,
                'is_esic_enabled' => $request->has('is_esic_enabled') ? 1 : 0,
                'overtime_enabled' => $request->has('overtime_enabled') ? 1 : 0,
                'overtime_formula' => $data['overtime_formula'] ?? 'basic',
            ]
        );

        return back()->with('success', __('Employee salary saved.'));
    }

    // ── Salary Increment ──

    public function salaryIncrement(Request $request)
    {
        $creatorId = \Auth::user()->creatorId();
        $employees = Employee::where('created_by', $creatorId)->orderBy('name')->get();

        $history = SalaryIncrementHistory::where('created_by', $creatorId)
            ->with('employee')
            ->orderByDesc('created_at')
            ->get();

        return view('payroll.salary_increment', compact('employees', 'history'));
    }

    public function storeSalaryIncrement(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|integer',
            'new_ctc' => 'required|numeric|min:1',
            'effective_date' => 'required|date',
            'arrears_month' => 'nullable|string|size:7',
            'remarks' => 'nullable|string|max:500',
        ]);

        $creatorId = \Auth::user()->creatorId();
        $empId = (int)$data['employee_id'];

        $empSalary = EmployeeSalary::where('employee_id', $empId)->first();
        if (!$empSalary) {
            return back()->with('error', __('Employee salary not configured. Set up salary first.'));
        }

        $oldCtc = (float)$empSalary->ctc;
        $newCtc = (float)$data['new_ctc'];
        $incrementAmount = $newCtc - $oldCtc;
        $incrementPercentage = $oldCtc > 0 ? round(($incrementAmount / $oldCtc) * 100, 2) : 0;

        $effectiveDate = $data['effective_date'];
        $arrearsMonth = $data['arrears_month'] ?: null;

        // Calculate arrears amount if arrears_month is set
        $arrearsAmount = 0;
        if ($arrearsMonth) {
            // Arrears = monthly increment difference × number of months from effective_date to arrears_month
            $effectiveMonth = \Carbon\Carbon::parse($effectiveDate)->startOfMonth();
            $payoutMonth = \Carbon\Carbon::parse($arrearsMonth . '-01');
            $arrearsMonthsCount = $effectiveMonth->diffInMonths($payoutMonth);

            $oldMonthly = round($oldCtc / 12, 2);
            $newMonthly = round($newCtc / 12, 2);
            $monthlyDiff = $newMonthly - $oldMonthly;
            $arrearsAmount = round($monthlyDiff * $arrearsMonthsCount, 2);
        }

        // Save increment history
        SalaryIncrementHistory::create([
            'employee_id' => $empId,
            'old_ctc' => $oldCtc,
            'new_ctc' => $newCtc,
            'increment_amount' => $incrementAmount,
            'increment_percentage' => $incrementPercentage,
            'effective_date' => $effectiveDate,
            'arrears_month' => $arrearsMonth,
            'arrears_paid' => false,
            'arrears_amount' => $arrearsAmount,
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $creatorId,
        ]);

        // Update employee salary to new CTC
        $empSalary->update(['ctc' => $newCtc]);

        return back()->with('success', __('Salary increment applied. Old CTC: :old, New CTC: :new (:pct% increase)', [
            'old' => number_format($oldCtc, 2),
            'new' => number_format($newCtc, 2),
            'pct' => $incrementPercentage,
        ]));
    }

    public function deleteSalaryIncrement($id)
    {
        $creatorId = \Auth::user()->creatorId();
        $record = SalaryIncrementHistory::where('id', $id)->where('created_by', $creatorId)->firstOrFail();

        if ($record->arrears_paid) {
            return back()->with('error', __('Cannot delete — arrears already paid in payroll.'));
        }

        // Revert salary to old CTC
        $empSalary = EmployeeSalary::where('employee_id', $record->employee_id)->first();
        if ($empSalary) {
            $empSalary->update(['ctc' => $record->old_ctc]);
        }

        $record->delete();

        return back()->with('success', __('Increment reverted. Salary restored to previous CTC.'));
    }

    public function processPayroll(Request $request)
    {
        $creatorId = \Auth::user()->creatorId();
        $schedule = PaySchedule::where('created_by', $creatorId)->first();

        $filterYear = $request->get('filter_year', '');
        $filterMonth = $request->get('filter_month', '');
        $filterEmployee = $request->get('filter_employee', '');
        $filterStatus = $request->get('filter_status', '');

        $query = Payroll::where('created_by', $creatorId)->with('employee');

        if ($filterYear && $filterMonth) {
            $query->where('month', $filterYear . '-' . $filterMonth);
        } elseif ($filterYear) {
            $query->where('month', 'like', $filterYear . '-%');
        } elseif ($filterMonth) {
            $query->where('month', 'like', '%-' . $filterMonth);
        }
        if ($filterEmployee) {
            $query->where('employee_id', $filterEmployee);
        }
        if ($filterStatus === 'processed') {
            $query->where('is_locked', 1);
        } elseif ($filterStatus === 'draft') {
            $query->where('is_locked', 0);
        }

        $recent = $query->orderByDesc('month')->orderByDesc('id')->get();

        $employees = Employee::where('created_by', $creatorId)->orderBy('name')->get();

        $availableMonths = Payroll::where('created_by', $creatorId)
            ->select('month')->distinct()->orderByDesc('month')->pluck('month');

        $availableYears = $availableMonths->map(fn($m) => substr($m, 0, 4))->unique()->sortDesc()->values();

        return view('payroll.process', compact('schedule', 'recent', 'employees', 'availableMonths', 'availableYears', 'filterYear', 'filterMonth', 'filterEmployee', 'filterStatus'));
    }

    public function exportProcessPayroll(Request $request)
    {
        $creatorId = \Auth::user()->creatorId();
        $filterYear = (string) $request->get('filter_year', '');
        $filterMonth = (string) $request->get('filter_month', '');
        $filterEmployee = (int) $request->get('filter_employee', 0);
        $filterStatus = (string) $request->get('filter_status', '');

        $filenameMonth = ($filterYear ?: 'all') . ($filterMonth ? '-' . $filterMonth : '');
        $filename = 'salary_statement_' . $filenameMonth . '.xlsx';

        return Excel::download(
            new PayrollDetailedExport(
                $creatorId,
                $filterYear ?: null,
                $filterMonth ?: null,
                $filterEmployee > 0 ? $filterEmployee : null,
                $filterStatus !== '' ? $filterStatus : null
            ),
            $filename
        );
    }

    public function pdfSalaryStatement(Request $request)
    {
        $creatorId = \Auth::user()->creatorId();
        $filterYear = (string) $request->get('filter_year', '');
        $filterMonth = (string) $request->get('filter_month', '');
        $filterEmployee = (int) $request->get('filter_employee', 0);
        $filterStatus = (string) $request->get('filter_status', '');

        $query = Payroll::query()
            ->where('created_by', $creatorId)
            ->with(['employee.branch', 'employee.department', 'employee.designation']);

        if ($filterYear && $filterMonth) {
            $query->where('month', $filterYear . '-' . $filterMonth);
        } elseif ($filterYear) {
            $query->where('month', 'like', $filterYear . '-%');
        } elseif ($filterMonth) {
            $query->where('month', 'like', '%-' . $filterMonth);
        }
        if ($filterEmployee > 0) {
            $query->where('employee_id', $filterEmployee);
        }
        if ($filterStatus === 'processed') {
            $query->where('is_locked', 1);
        } elseif ($filterStatus === 'draft') {
            $query->where('is_locked', 0);
        }

        $payrolls = $query->orderBy('month')->orderBy('employee_id')->get();

        $monthLabel = '';
        if ($filterYear && $filterMonth) {
            $monthLabel = date('F Y', strtotime($filterYear . '-' . $filterMonth . '-01'));
        } elseif ($filterYear) {
            $monthLabel = $filterYear;
        }

        $settings = \App\Models\Utility::settings();
        $companyName = $settings['company_name'] ?? config('app.name');

        return view('payroll.salary_statement_pdf', compact('payrolls', 'monthLabel', 'companyName'));
    }

    public function taxComputation(Request $request, int $employeeId, ?string $fy = null)
    {
        $authUser = \Auth::user();
        $creatorId = $authUser->creatorId();

        $employee = Employee::where('created_by', $creatorId)->findOrFail($employeeId);
        $empSalary = EmployeeSalary::where('employee_id', $employeeId)->first();

        // FY from query string > URL param > auto-detect
        $fy = $request->get('fy', $fy);
        if (!$fy) {
            $now = now();
            $fy = $now->month >= 4
                ? $now->year . '-' . ($now->year + 1)
                : ($now->year - 1) . '-' . $now->year;
        }

        $tdsCalc = app(TDSCalculator::class);
        $ctc = $empSalary ? (float) $empSalary->ctc : 0;
        $basicPct = $empSalary ? (float) $empSalary->basic_percentage : 50;

        // ── Gross Annual Income for the FY ──
        // Rule (matching user intent): processed months use the *actual earned*
        // amount (attendance-prorated, same math as the salary statement export
        // "TOTAL GROSS (Earned)" column). Unprocessed months use an assumption
        // of one full month at the current CTC. As soon as a month's payroll
        // is processed, its assumption is replaced by the real earned value.
        $fyStartMonth = substr($fy, 0, 4) . '-04';
        $fyEndMonth = (substr($fy, 0, 4) + 1) . '-03';

        $fyPayrolls = Payroll::where('employee_id', $employeeId)
            ->where('month', '>=', $fyStartMonth)
            ->where('month', '<=', $fyEndMonth)
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Earned (prorated) sum for processed months, built the same way as
        // PayrollDetailedExport so numbers reconcile exactly.
        $actualEarnedProcessed = 0.0;
        foreach ($fyPayrolls as $row) {
            $actualEarnedProcessed += $this->earnedGrossForPayrollRow($row);
        }
        $actualEarnedProcessed = round($actualEarnedProcessed, 2);

        $monthsProcessed = $fyPayrolls->count();

        // Salary change detection (for the info row in the view)
        $increments = SalaryIncrementHistory::where('employee_id', $employeeId)
            ->where('effective_date', '>=', substr($fy, 0, 4) . '-04-01')
            ->where('effective_date', '<=', (substr($fy, 0, 4) + 1) . '-03-31')
            ->orderBy('effective_date')
            ->get();

        $salaryChanged = $increments->isNotEmpty();

        // Assumption for unprocessed months: one full month at the current CTC.
        $assumedMonthlyGross = $tdsCalc->ctcToGross($ctc, $basicPct) / 12;
        $monthsUnprocessed   = max(12 - $monthsProcessed, 0);
        $assumedGrossRemaining = round($assumedMonthlyGross * $monthsUnprocessed, 2);

        $grossAnnual = round($actualEarnedProcessed + $assumedGrossRemaining, 2);

        // Expose the two parts so the view can label them clearly.
        $actualGrossFromPayroll = $actualEarnedProcessed;

        $grossAnnualFromCTC = $tdsCalc->ctcToGross($ctc, $basicPct);

        // Tax declaration
        $declaration = \App\Models\TaxDeclaration::where('employee_id', $employeeId)
            ->where('financial_year', $fy)->first();
        if (!$declaration) {
            $declaration = \App\Models\TaxDeclaration::where('employee_id', $employeeId)
                ->orderByDesc('financial_year')->first();
        }
        $regime = $declaration->tax_regime ?? 'new';

        // Compute new regime slab-wise
        $stdDeductionNew = 75000;
        $taxableNew = max($grossAnnual - $stdDeductionNew, 0);
        $newSlabs = [
            ['from' => 0, 'to' => 400000, 'rate' => 0],
            ['from' => 400000, 'to' => 800000, 'rate' => 5],
            ['from' => 800000, 'to' => 1200000, 'rate' => 10],
            ['from' => 1200000, 'to' => 1600000, 'rate' => 15],
            ['from' => 1600000, 'to' => 2000000, 'rate' => 20],
            ['from' => 2000000, 'to' => 2400000, 'rate' => 25],
            ['from' => 2400000, 'to' => null, 'rate' => 30],
        ];
        $newSlabCalc = $this->computeSlabBreakdown($taxableNew, $newSlabs);
        $newTaxBeforeCess = $newSlabCalc['total'];
        $newRebate = ($taxableNew <= 700000) ? $newTaxBeforeCess : 0;
        $newTaxAfterRebate = $newTaxBeforeCess - $newRebate;
        $newCess = round($newTaxAfterRebate * 0.04);
        $newTotalTax = round($newTaxAfterRebate + $newCess);

        // Compute old regime slab-wise
        $oldDeductions = 0;
        $oldDeductionDetails = [];
        $oldDeductionDetails[] = ['name' => 'Standard Deduction', 'amount' => 50000];
        $oldDeductions += 50000;

        if ($declaration) {
            $inv80c = \App\Models\InvestmentDetail::where('tax_declaration_id', $declaration->id)
                ->where('section_code', '80C')->sum('amount');
            $inv80c = min((float) $inv80c, 150000);
            if ($inv80c > 0) {
                $oldDeductionDetails[] = ['name' => 'Section 80C (Investments)', 'amount' => $inv80c];
                $oldDeductions += $inv80c;
            }

            $ex80d = \App\Models\ExemptionDetail::where('tax_declaration_id', $declaration->id)
                ->where('section_code', '80D')->sum('amount');
            $ex80d = min((float) $ex80d, 100000);
            if ($ex80d > 0) {
                $oldDeductionDetails[] = ['name' => 'Section 80D (Medical Insurance)', 'amount' => $ex80d];
                $oldDeductions += $ex80d;
            }

            if ($declaration->is_rented_house && $declaration->rent_paid > 0) {
                $basicAnnual = round($ctc * ($basicPct / 100));
                $hraExemption = min($basicAnnual * 0.50, $basicAnnual * 0.40, max(($declaration->rent_paid * 12) - ($basicAnnual * 0.10), 0));
                if ($hraExemption > 0) {
                    $oldDeductionDetails[] = ['name' => 'HRA Exemption', 'amount' => round($hraExemption)];
                    $oldDeductions += $hraExemption;
                }
            }

            if ($declaration->is_home_loan && $declaration->home_loan_interest > 0) {
                $hlInterest = min((float) $declaration->home_loan_interest, 200000);
                $oldDeductionDetails[] = ['name' => 'Home Loan Interest (Sec 24b)', 'amount' => $hlInterest];
                $oldDeductions += $hlInterest;
            }
        }

        $taxableOld = max($grossAnnual - $oldDeductions, 0);
        $oldSlabs = [
            ['from' => 0, 'to' => 250000, 'rate' => 0],
            ['from' => 250000, 'to' => 500000, 'rate' => 5],
            ['from' => 500000, 'to' => 1000000, 'rate' => 20],
            ['from' => 1000000, 'to' => null, 'rate' => 30],
        ];
        $oldSlabCalc = $this->computeSlabBreakdown($taxableOld, $oldSlabs);
        $oldTaxBeforeCess = $oldSlabCalc['total'];
        $oldRebate = ($taxableOld <= 500000 && $oldTaxBeforeCess <= 12500) ? $oldTaxBeforeCess : 0;
        $oldTaxAfterRebate = $oldTaxBeforeCess - $oldRebate;
        $oldCess = round($oldTaxAfterRebate * 0.04);
        $oldTotalTax = round($oldTaxAfterRebate + $oldCess);

        // FY payroll months TDS paid so far
        $fyStart = substr($fy, 0, 4) . '-04';
        $fyEnd = (substr($fy, 0, 4) + 1) . '-03';
        $payrollMonths = Payroll::where('employee_id', $employeeId)
            ->where('month', '>=', $fyStart)
            ->where('month', '<=', $fyEnd)
            ->orderBy('month')
            ->get();

        $tdsPaidSoFar = 0;
        $monthlyTdsHistory = [];
        foreach ($payrollMonths as $p) {
            $tds = $p->statutory_json['tds'] ?? [];
            $total = (float) ($tds['total_tds'] ?? 0);
            $tdsPaidSoFar += $total;
            $monthlyTdsHistory[] = [
                'month' => $p->month,
                'base_tds' => (float) ($tds['base_monthly_tds'] ?? ($tds['monthly_tds'] ?? 0)),
                'additional_tds' => (float) ($tds['additional_tds'] ?? 0),
                'total_tds' => $total,
            ];
        }

        $chosenTax = $regime === 'old' ? $oldTotalTax : $newTotalTax;
        $remainingTax = max($chosenTax - $tdsPaidSoFar, 0);
        $monthsRemaining = max(12 - count($monthlyTdsHistory), 1);
        $projectedMonthlyTds = round($remainingTax / $monthsRemaining, 2);

        return view('payroll.tax_computation', compact(
            'employee', 'empSalary', 'fy', 'ctc', 'grossAnnual', 'grossAnnualFromCTC', 'basicPct',
            'regime', 'declaration',
            'salaryChanged', 'increments', 'monthsProcessed', 'actualGrossFromPayroll',
            'monthsUnprocessed', 'assumedGrossRemaining', 'assumedMonthlyGross',
            'stdDeductionNew', 'taxableNew', 'newSlabs', 'newSlabCalc', 'newTaxBeforeCess', 'newRebate', 'newTaxAfterRebate', 'newCess', 'newTotalTax',
            'oldDeductions', 'oldDeductionDetails', 'taxableOld', 'oldSlabs', 'oldSlabCalc', 'oldTaxBeforeCess', 'oldRebate', 'oldTaxAfterRebate', 'oldCess', 'oldTotalTax',
            'monthlyTdsHistory', 'tdsPaidSoFar', 'chosenTax', 'remainingTax', 'monthsRemaining', 'projectedMonthlyTds'
        ));
    }

    private function computeSlabBreakdown(float $taxableIncome, array $slabs): array
    {
        $breakdown = [];
        $total = 0;
        foreach ($slabs as $slab) {
            $from = $slab['from'];
            $to = $slab['to'] ?? PHP_INT_MAX;
            $rate = $slab['rate'];

            if ($taxableIncome <= $from) {
                $breakdown[] = ['from' => $from, 'to' => $slab['to'], 'rate' => $rate, 'taxable' => 0, 'tax' => 0];
                continue;
            }

            $slabAmount = min($taxableIncome, $to) - $from;
            $slabTax = round($slabAmount * ($rate / 100));
            $total += $slabTax;
            $breakdown[] = ['from' => $from, 'to' => $slab['to'], 'rate' => $rate, 'taxable' => $slabAmount, 'tax' => $slabTax];
        }
        return ['breakdown' => $breakdown, 'total' => $total];
    }

    public function runPayroll(Request $request, SalaryCalculator $calculator, StatutoryCalculator $statutoryCalculator, TDSCalculator $tdsCalculator)
    {
        $creatorId = \Auth::user()->creatorId();
        $data = $request->validate([
            'month' => 'required|string|size:7',
        ]);
        $month = $data['month'];

        $schedule = PaySchedule::where('created_by', $creatorId)->first();
        if (!$schedule || !$schedule->status) {
            return back()->with('error', __('Active pay schedule not found.'));
        }

        // Delete old payroll for this month so we can regenerate
        Payroll::where('created_by', $creatorId)->where('month', $month)->delete();

        // Reset arrears_paid flag for this month so breakup is recomputed on re-run
        SalaryIncrementHistory::where('created_by', $creatorId)
            ->where('arrears_month', $month)
            ->update(['arrears_paid' => false]);

        $employeeSalaryRows = EmployeeSalary::query()
            ->join('employees', 'employees.id', '=', 'employee_salaries.employee_id')
            ->where('employees.created_by', $creatorId)
            ->get(['employee_salaries.*', 'employees.gender']);

        DB::beginTransaction();
        try {
            // Calculate attendance date range — respects the configurable
            // attendance cut-off day on the pay schedule (e.g. 26th prev ->
            // 25th current). Defaults to calendar month when start day = 1.
            [$monthStart, $monthEnd] = PaySchedule::attendanceRangeFor(
                $month,
                null,
                (int) ($schedule->attendance_cycle_start_day ?? 1)
            );

            // Build holiday dates set for this month (used to exclude from present count)
            $holidayDatesSet = [];
            $holidayRecords = DB::table('holidays')
                ->where('created_by', $creatorId)
                ->where(function ($q) use ($monthStart, $monthEnd) {
                    $q->whereBetween('start_date', [$monthStart, $monthEnd])
                      ->orWhereBetween('end_date', [$monthStart, $monthEnd]);
                })->get();
            foreach ($holidayRecords as $h) {
                $hStart = max(strtotime($h->start_date), strtotime($monthStart));
                $hEnd = min(strtotime($h->end_date), strtotime($monthEnd));
                for ($ts = $hStart; $ts <= $hEnd; $ts += 86400) {
                    $holidayDatesSet[date('Y-m-d', $ts)] = true;
                }
            }

            foreach ($employeeSalaryRows as $employeeSalary) {
                $empId = (int)$employeeSalary->employee_id;

                // Skip employees who haven't joined yet in this month
                $empModel = Employee::with('employeeType')->find($empId);
                if ($empModel && !empty($empModel->company_doj)) {
                    $empDojDate = $empModel->company_doj;
                    if ($empDojDate > $monthEnd) {
                        continue; // Employee hasn't joined yet, skip payroll
                    }
                }

                // ── EMPLOYEE TYPE ROUTING ──
                // Intern / Consultant don't use CTC structure. They get a flat
                // monthly amount (stipend or retainer) prorated by attendance,
                // optional flat TDS, and no statutory deductions.
                $empType = $empModel ? $empModel->employeeType : null;
                if ($empType && (!$empType->ctc_applicable || $empType->attendance_prorata)) {
                    $this->processNonCtcEmployee(
                        $empModel,
                        $empType,
                        $month,
                        $monthStart,
                        $monthEnd,
                        $creatorId,
                        $holidayDatesSet
                    );
                    continue; // skip the standard CTC pipeline below
                }

                $effectiveCtc = $this->ctcEffectiveForMonth($empId, $month);
                $result = $calculator->calculate($empId, $month, $effectiveCtc);
                if (isset($result['error'])) {
                    continue;
                }

                // â”€â”€ ATTENDANCE DATA (use synced snapshot if available) â”€â”€
                $syncedData = PayrollAttendanceSync::where('employee_id', $empId)
                    ->where('month', $month)
                    ->where('created_by', $creatorId)
                    ->first();

                if ($syncedData) {
                    $totalWorkingDays = $syncedData->working_days;
                    $presentDays = $syncedData->present;
                    $halfDays = $syncedData->half_day;
                    $absentDays = $syncedData->absent;
                    $leaveDays = $syncedData->leave;
                    $lateMarks = $syncedData->late_marks;
                    $earlyMarks = $syncedData->early_marks;
                    $totalDeductionUnits = (float)$syncedData->deduction_units;
                    $earlyHalfDayCount = $syncedData->early_half_day;

                    // Use policy-effective counts from synced data (same as attendance page)
                    $presentEffective = (float)($syncedData->present_effective ?? $presentDays);
                    $leaveEffective = (float)($syncedData->leave_effective ?? $leaveDays);
                    $absentEffective = (float)($syncedData->absent_effective ?? $absentDays);
                    $hdDeduction = (float)($syncedData->hd_deduction ?? $halfDays);
                    $weeklyOffs = (int)($syncedData->weekly_offs ?? 0);
                    $monthTotalDays = (int)($syncedData->month_total_days ?? 0);
                    $policySummary = $syncedData->policy_summary_json;
                } else {
                    $attendance = AttendanceEmployee::where('employee_id', $empId)
                        ->whereBetween('date', [$monthStart, $monthEnd])
                        ->get();

                    // Exclude holiday dates from present count
                    $holidayPresentCount = 0;
                    $presentDays = 0;
                    $halfDays = 0;
                    $absentDays = 0;
                    $leaveDays = 0;
                    foreach ($attendance as $aRec) {
                        $aDate = (string)$aRec->date;
                        $aStatus = strtolower((string)$aRec->status);
                        if (isset($holidayDatesSet[$aDate]) && ($aStatus === 'present' || $aStatus === 'half day')) {
                            $holidayPresentCount++;
                            continue;
                        }
                        if ($aStatus === 'present') $presentDays++;
                        elseif ($aStatus === 'half day') $halfDays++;
                        elseif ($aStatus === 'absent') $absentDays++;
                        elseif ($aStatus === 'leave') $leaveDays++;
                    }
                    $totalWorkingDays = $attendance->count() - $holidayPresentCount;
                    $lateMarks = (int)$attendance->sum('late_mark');
                    $earlyMarks = (int)$attendance->sum('early_mark');
                    $totalDeductionUnits = (float)$attendance->sum('deduction_units');

                    // Early Â½ Day count (for display only â€” deduction is already in deduction_units)
                    $companyEndTime = (string)Utility::getValByName('company_end_time');
                    $halfDayDeductionMinutes = (int)(Utility::getValByName('attendance_half_day_deduction_minutes') ?: 60);
                    $earlyHalfDayCount = 0;
                    foreach ($attendance as $rec) {
                        if (strtolower($rec->status) !== 'present') continue;
                        if ($rec->clock_out === '00:00:00') continue;
                        $endTime = \Carbon\Carbon::parse($rec->date . ' ' . $companyEndTime);
                        $clockOut = \Carbon\Carbon::parse($rec->date . ' ' . $rec->clock_out);
                        if ($clockOut->lt($endTime)) {
                            $earlyMin = abs($endTime->diffInMinutes($clockOut));
                            if ($earlyMin >= $halfDayDeductionMinutes) {
                                $earlyHalfDayCount++;
                            }
                        }
                    }

                    // Calculate month total days & weekly offs respecting DOJ
                    $empModel = Employee::find($empId);
                    $empDoj = !empty($empModel) ? \Carbon\Carbon::parse($empModel->company_doj) : null;
                    $mStart = \Carbon\Carbon::parse($monthStart)->startOfDay();
                    $mEnd = \Carbon\Carbon::parse($monthEnd)->startOfDay();

                    if ($empDoj && $empDoj->gt($mStart) && $empDoj->lte($mEnd)) {
                        $effectiveStart = $empDoj->copy()->startOfDay();
                    } else {
                        $effectiveStart = $mStart->copy();
                    }

                    $monthTotalDays = (int)round($effectiveStart->diffInDays($mEnd)) + 1;

                    // Count weekly off days in effective range
                    $weeklyOffDaysSetting = array_map('intval', array_filter(
                        explode(',', (string)Utility::getValByName('weekly_off_days')),
                        fn($v) => $v !== ''
                    ));
                    if (empty($weeklyOffDaysSetting)) {
                        $weeklyOffDaysSetting = [\Carbon\Carbon::SUNDAY];
                    }
                    $weeklyOffs = 0;
                    $dd = $effectiveStart->copy();
                    while ($dd->lte($mEnd)) {
                        if (in_array($dd->dayOfWeek, $weeklyOffDaysSetting)) {
                            $weeklyOffs++;
                        }
                        $dd->addDay();
                    }

                    // Fallback: use raw counts as effective, HD = 0.5 per half day
                    $presentEffective = (float)$presentDays;
                    $leaveEffective = (float)$leaveDays;
                    $absentEffective = (float)$absentDays;
                    $hdDeduction = (float)$halfDays * 0.5;
                    $policySummary = null;
                }

                // â”€â”€ SALARY FORMULA â”€â”€
                // Salary = (Monthly Component / Days in Month) Ã— Paid Days
                // Paid Days = Present + Weekly Off + Approved Leave + Public Holidays
                // Unpaid Days = Absent + HD (policy deductions)
                // Approved leave is PAID â€” no leave deduction.

                $attendanceForOt = AttendanceEmployee::where('employee_id', $empId)
                    ->whereBetween('date', [$monthStart, $monthEnd])
                    ->get(['overtime', 'status']);

                $overtimeHours = 0.0;
                foreach ($attendanceForOt as $otRow) {
                    $st = strtolower((string)($otRow->status ?? ''));
                    if ($st === 'absent' || $st === 'leave') {
                        continue;
                    }
                    $overtimeHours += $this->timeToHours((string)($otRow->overtime ?? '00:00:00'));
                }

                $basicMonthlyForOt = 0.0;
                foreach (($result['earnings'] ?? []) as $earningRow) {
                    if (isset($earningRow['name']) && strtolower((string)$earningRow['name']) === 'basic') {
                        $basicMonthlyForOt = ((float)($earningRow['amount'] ?? 0)) / 12;
                        break;
                    }
                }

                $otEnabled = !empty($employeeSalary->overtime_enabled);
                $overtimeAmount = 0;
                $otFormula = '';
                $otBaseMonthly = 0;
                $otPerHour = 0;

                if ($otEnabled && $overtimeHours > 0) {
                    $otFormula = strtolower((string)($employeeSalary->overtime_formula ?? 'basic'));
                    $otBaseMonthly = $otFormula === 'gross'
                        ? (float)($result['totals']['gross_monthly'] ?? 0)
                        : $basicMonthlyForOt;

                    $otPerHour = round(($otBaseMonthly / 26) / 8, 2);
                    $overtimeAmount = round($otPerHour * $overtimeHours * 1.5, 2);
                    if ($overtimeAmount > 0) {
                        $result['earnings'][] = [
                            'name' => 'Overtime',
                            'amount' => $overtimeAmount,
                            'frequency' => 'one-time',
                        ];
                    }
                }

                // ── SALARY INCREMENT ARREARS (component-wise breakup) ──
                $arrearsTotal = 0;
                $arrearsDetails = [];
                $arrearsComponents = []; // name => total arrear amount
                $pendingArrears = SalaryIncrementHistory::where('employee_id', $empId)
                    ->where('created_by', $creatorId)
                    ->where('arrears_month', $month)
                    ->where('arrears_paid', false)
                    ->where('arrears_amount', '>', 0)
                    ->get();

                $basicPct = (float)($employeeSalary->basic_percentage ?: 50);

                foreach ($pendingArrears as $arrear) {
                    $ab = $this->computeArrearBreakup($arrear, $basicPct, $empId, $creatorId);

                    foreach ($ab['components'] as $name => $amt) {
                        $arrearsComponents[$name] = ($arrearsComponents[$name] ?? 0) + $amt;
                    }

                    $arrearsTotal += $ab['total'];
                    $arrearsDetails[] = [
                        'id' => $arrear->id,
                        'old_ctc' => (float)$arrear->old_ctc,
                        'new_ctc' => (float)$arrear->new_ctc,
                        'effective_date' => \Carbon\Carbon::parse($arrear->effective_date)->format('Y-m-d'),
                        'months' => $ab['months_count'],
                        'months_multiplier' => $ab['months_multiplier'] ?? $ab['months_count'],
                        'months_multiplier_label' => $ab['months_multiplier_label'] ?? (string)$ab['months_count'],
                        'monthly_diff' => round(((float)$arrear->new_ctc - (float)$arrear->old_ctc) / 12, 2),
                        'amount' => $ab['total'],
                        'breakup' => $ab['breakup'],
                        'per_month' => $ab['per_month'],
                    ];
                }

                if ($arrearsTotal > 0) {
                    $result['earnings'][] = [
                        'name' => 'Salary Arrears',
                        'amount' => round($arrearsTotal, 2),
                        'frequency' => 'one-time',
                    ];
                    // Mark arrears as paid
                    foreach ($pendingArrears as $arrear) {
                        $arrear->update(['arrears_paid' => true]);
                    }
                }

                $monthlyGrossBase = (float)$result['totals']['gross_monthly'];
                $fixedGrossMonthly = 0.0;
                $oneTimeGrossMonthly = 0.0;
                foreach (($result['earnings'] ?? []) as $earningRow) {
                    $annualAmount = (float)($earningRow['amount'] ?? 0);
                    $freq = strtolower((string)($earningRow['frequency'] ?? 'monthly'));
                    if ($freq === 'one-time') {
                        $oneTimeGrossMonthly += $annualAmount;
                    } else {
                        $fixedGrossMonthly += ($annualAmount / 12);
                    }
                }
                $totalGrossMonthly = round($fixedGrossMonthly + $oneTimeGrossMonthly, 2);

                // Days in month (use monthTotalDays from policy summary, fallback to DOJ-aware calendar)
                if ($monthTotalDays <= 0) {
                    $mStartCarbon = \Carbon\Carbon::parse($monthStart)->startOfDay();
                    $mEndCarbon = \Carbon\Carbon::parse($monthEnd)->startOfDay();
                    $dojCarbon = !empty($empModel) ? \Carbon\Carbon::parse($empModel->company_doj)->startOfDay() : null;
                    if ($dojCarbon && $dojCarbon->gt($mStartCarbon) && $dojCarbon->lte($mEndCarbon)) {
                        $monthTotalDays = (int)round($dojCarbon->diffInDays($mEndCarbon)) + 1;
                    } else {
                        $monthTotalDays = (int)date('t', strtotime($monthStart));
                    }
                }

                // Public holidays in this month
                $publicHolidays = (int)DB::table('holidays')
                    ->where('created_by', $creatorId)
                    ->where(function ($q) use ($monthStart, $monthEnd) {
                        $q->whereBetween('start_date', [$monthStart, $monthEnd])
                          ->orWhereBetween('end_date', [$monthStart, $monthEnd]);
                    })
                    ->get()
                    ->reduce(function ($carry, $holiday) use ($monthStart, $monthEnd) {
                        // Count days of each holiday that fall within this month
                        $hStart = max(strtotime($holiday->start_date), strtotime($monthStart));
                        $hEnd = min(strtotime($holiday->end_date), strtotime($monthEnd));
                        return $carry + max(0, (int)(($hEnd - $hStart) / 86400) + 1);
                    }, 0);

                // Per day salary is always based on full calendar month days (not DOJ-adjusted)
                $monthCalendarDays = (int)date('t', strtotime($monthStart));
                $perDaySalary = $monthCalendarDays > 0 ? round($monthlyGrossBase / $monthCalendarDays, 2) : 0;

                // Paid days = total days − unpaid (absent + HD deduction)
                // present_effective already counts HD as 0.5, so use subtraction method
                $unpaidDays = $absentEffective + $hdDeduction;
                $paidDays = max($monthTotalDays - $unpaidDays, 0);

                // â”€â”€ ATTENDANCE DEDUCTIONS â”€â”€
                $attendanceDeductions = [];
                $attendanceDeductionTotal = 0;

                // Absent deduction (full absents + regular HD absents)
                if ($absentEffective > 0) {
                    $amt = round($perDaySalary * $absentEffective, 2);
                    $attendanceDeductions[] = ['name' => 'Absent Deduction (' . $absentEffective . ' days)', 'amount' => $amt];
                    $attendanceDeductionTotal += $amt;
                }

                // HD deduction (non-exempt marks + early Â½ day + late Â½ day)
                // hdDeduction is already in "days" (each penalty = 0.5 day)
                if ($hdDeduction > 0) {
                    $amt = round($perDaySalary * $hdDeduction, 2);
                    $attendanceDeductions[] = ['name' => 'Half Day Deduction (' . $hdDeduction . ' days)', 'amount' => $amt];
                    $attendanceDeductionTotal += $amt;
                }

                // Leave is PAID â€” no leave deduction

                // Attendance summary for JSON storage
                $attendanceSummary = [
                    'working_days' => $totalWorkingDays,
                    'month_total_days' => $monthTotalDays,
                    'weekly_offs' => $weeklyOffs,
                    'public_holidays' => $publicHolidays,
                    'present' => $presentDays,
                    'half_day' => $halfDays,
                    'absent' => $absentDays,
                    'leave' => $leaveDays,
                    'present_effective' => $presentEffective,
                    'leave_effective' => $leaveEffective,
                    'absent_effective' => $absentEffective,
                    'hd_deduction' => $hdDeduction,
                    'paid_days' => $paidDays,
                    'unpaid_days' => $unpaidDays,
                    'late_marks' => $lateMarks,
                    'early_marks' => $earlyMarks,
                    'deduction_units' => $totalDeductionUnits,
                    'month_calendar_days' => $monthCalendarDays,
                    'per_day_salary' => $perDaySalary,
                    'early_half_day' => $earlyHalfDayCount,
                    'overtime_enabled' => $otEnabled,
                    'overtime_hours' => round($overtimeHours, 2),
                    'overtime_amount' => $overtimeAmount,
                    'overtime_formula' => $otFormula,
                    'overtime_base_monthly' => $otBaseMonthly,
                    'overtime_per_hour' => $otPerHour,
                    'arrears_total' => $arrearsTotal,
                    'arrears_details' => $arrearsDetails,
                    'arrears_components' => $arrearsComponents,
                    'policy_summary' => $policySummary,
                    'formula' => 'Monthly / ' . $monthCalendarDays . ' calendar days x ' . $paidDays . ' paid days',
                ];

                // â”€â”€ STATUTORY â”€â”€
                $statutory = $statutoryCalculator->calculateForEmployee(
                    $empId,
                    $totalGrossMonthly,
                    $totalGrossMonthly,
                    null,
                    !empty($employeeSalary->gender) ? strtolower((string)$employeeSalary->gender) : null,
                    $month
                );
                $basicMonthly = 0.0;
                foreach (($result['earnings'] ?? []) as $earningRow) {
                    if (isset($earningRow['name']) && strtolower((string)$earningRow['name']) === 'basic') {
                        $basicMonthly = ((float)($earningRow['amount'] ?? 0)) / 12;
                        break;
                    }
                }
                if ($basicMonthly > 0) {
                    $statutory = $statutoryCalculator->calculateForEmployee(
                        $empId,
                        $basicMonthly,
                        $totalGrossMonthly,
                        null,
                        !empty($employeeSalary->gender) ? strtolower((string)$employeeSalary->gender) : null,
                        $month
                    );
                }

                // Custom policy:
                // 1) ESIC eligibility is based on fixed monthly salary (without one-time additions).
                //    If eligible, ESIC is calculated on the month's total earnings (fixed + one-time additions).
                // 2) PF should remain on BASIC basis (as per statutory/basic rule), not on total gross.
                $esicEnabled = (bool)($employeeSalary->is_esic_enabled ?? false);

                if ($esicEnabled && $fixedGrossMonthly <= 21000) {
                    $statutory['esic_employee'] = round($totalGrossMonthly * 0.0075, 2);
                    $statutory['esic_employer'] = round($totalGrossMonthly * 0.0325, 2);
                } else {
                    $statutory['esic_employee'] = 0.0;
                    $statutory['esic_employer'] = 0.0;
                }

                // â”€â”€ BUILD FINAL ARRAYS â”€â”€
                // Use StatutoryCalculator as the authoritative source for statutory items
                // (skip SalaryCalculator's statutory arrays to avoid duplicates)
                $baseDeductions = $result['deductions'];
                $baseBenefits = $result['benefits'];

                // Non-statutory monthly totals from SalaryCalculator output (annual -> monthly)
                $nonStatutoryDeductionMonthly = round(array_reduce($baseDeductions, static function ($carry, $item) {
                    $amount = (float)($item['amount'] ?? 0);
                    $freq = strtolower((string)($item['frequency'] ?? 'monthly'));
                    return $carry + ($freq === 'one-time' ? $amount : ($amount / 12));
                }, 0.0), 2);
                $nonStatutoryBenefitsMonthly = round(array_reduce($baseBenefits, static function ($carry, $item) {
                    $amount = (float)($item['amount'] ?? 0);
                    $freq = strtolower((string)($item['frequency'] ?? 'monthly'));
                    return $carry + ($freq === 'one-time' ? $amount : ($amount / 12));
                }, 0.0), 2);

                // Statutory deductions from StatutoryCalculator
                $baseDeductions[] = ['name' => 'EPF Contribution', 'amount' => (float)$statutory['epf_employee']];
                $baseDeductions[] = ['name' => 'ESIC Employee', 'amount' => (float)$statutory['esic_employee']];
                $baseDeductions[] = ['name' => 'Professional Tax', 'amount' => (float)$statutory['pt']];
                $baseDeductions[] = ['name' => 'LWF Employee', 'amount' => (float)$statutory['lwf_employee']];
                // Add attendance deductions
                $baseDeductions = array_merge($baseDeductions, $attendanceDeductions);

                // ── TDS / Income Tax ──
                // TDS is calculated on ALL earnings: fixed salary + OT + arrears + bonus + special allowances
                //
                // Method: Marginal relief approach
                //  1) Base TDS = tax on (fixed monthly × 12) / 12
                //  2) Additional TDS = tax on (fixed annual + one-time) − tax on (fixed annual)
                //     This captures the marginal tax on OT, arrears, bonus etc.
                //  3) Total TDS this month = base monthly + additional

                $payrollDate = \Carbon\Carbon::parse($month . '-01');
                $fy = $payrollDate->month >= 4
                    ? $payrollDate->year . '-' . ($payrollDate->year + 1)
                    : ($payrollDate->year - 1) . '-' . $payrollDate->year;

                // Fixed annual = regular monthly components × 12 (Basic, HRA, etc.)
                $fixedAnnualForTds = round($fixedGrossMonthly * 12, 2);
                // One-time this month = OT + Arrears + Bonus + Special Allowances etc.
                $oneTimeForTds = round($oneTimeGrossMonthly, 2);

                // Check employee's tax regime preference from IT Declaration
                // Try exact FY match first, then fallback to latest declaration
                $taxDeclaration = \App\Models\TaxDeclaration::where('employee_id', $empId)
                    ->where('financial_year', $fy)->first();
                if (!$taxDeclaration) {
                    $taxDeclaration = \App\Models\TaxDeclaration::where('employee_id', $empId)
                        ->orderByDesc('financial_year')->first();
                }
                $taxRegime = $taxDeclaration->tax_regime ?? 'new';

                // Compute tax using chosen regime
                if ($taxRegime === 'old' && $taxDeclaration) {
                    // Old regime: gather 80C/80D/HRA/home loan deductions from declaration
                    $oldDeductions = $tdsCalculator->getOldRegimeDeductionsPublic($taxDeclaration, $employeeSalary, $fixedAnnualForTds);
                    $baseTaxAnnual = $tdsCalculator->calculateOldRegime($fixedAnnualForTds, $oldDeductions);
                    $baseMonthlyTds = $baseTaxAnnual > 0 ? round($baseTaxAnnual / 12, 2) : 0;

                    $additionalTds = 0;
                    if ($oneTimeForTds > 0) {
                        $taxWithOneTime = $tdsCalculator->calculateOldRegime($fixedAnnualForTds + $oneTimeForTds, $oldDeductions);
                        $additionalTds = round(max($taxWithOneTime - $baseTaxAnnual, 0), 2);
                    }
                } else {
                    // New regime (default)
                    $baseTaxAnnual = $tdsCalculator->calculateNewRegime($fixedAnnualForTds);
                    $baseMonthlyTds = $baseTaxAnnual > 0 ? round($baseTaxAnnual / 12, 2) : 0;

                    $additionalTds = 0;
                    if ($oneTimeForTds > 0) {
                        $taxWithOneTime = $tdsCalculator->calculateNewRegime($fixedAnnualForTds + $oneTimeForTds);
                        $additionalTds = round(max($taxWithOneTime - $baseTaxAnnual, 0), 2);
                    }
                }

                $totalTdsThisMonth = round($baseMonthlyTds + $additionalTds, 2);

                if ($totalTdsThisMonth > 0) {
                    $baseDeductions[] = ['name' => 'TDS / Income Tax', 'amount' => $baseMonthlyTds];
                    if ($additionalTds > 0) {
                        $baseDeductions[] = ['name' => 'TDS on Additional Earnings', 'amount' => $additionalTds];
                    }
                }

                // Employer contributions from StatutoryCalculator
                $baseBenefits[] = ['name' => 'EPF Employer', 'amount' => (float)$statutory['epf_employer']];
                $baseBenefits[] = ['name' => 'ESIC Employer', 'amount' => (float)$statutory['esic_employer']];
                $baseBenefits[] = ['name' => 'LWF Employer', 'amount' => (float)$statutory['lwf_employer']];

                $statDeductionTotal = (float)$statutory['epf_employee'] + (float)$statutory['esic_employee'] + (float)$statutory['pt'] + (float)$statutory['lwf_employee'] + $totalTdsThisMonth;
                $statEmployerTotal = (float)$statutory['epf_employer'] + (float)$statutory['esic_employer'] + (float)$statutory['lwf_employer'];
                $monthlyGross = $totalGrossMonthly > 0 ? $totalGrossMonthly : $monthlyGrossBase;
                // Deduct ONLY employee-side deductions from net pay.
                $monthlyDeductions = $nonStatutoryDeductionMonthly + $statDeductionTotal + $attendanceDeductionTotal;
                $monthlyBenefits = $nonStatutoryBenefitsMonthly + $statEmployerTotal;
                $monthlyNet = $monthlyGross - $monthlyDeductions;

                // Merge statutory with attendance summary + TDS details
                $statutoryWithAttendance = array_merge($statutory, [
                    'attendance' => $attendanceSummary,
                    'tds' => [
                        'base_monthly_tds' => $baseMonthlyTds,
                        'additional_tds' => $additionalTds,
                        'total_tds' => $totalTdsThisMonth,
                        'annual_tax_on_fixed' => $baseTaxAnnual,
                        'fixed_annual' => $fixedAnnualForTds,
                        'one_time_this_month' => $oneTimeForTds,
                        'regime' => $taxRegime,
                    ],
                ]);

                Payroll::updateOrCreate(
                    ['employee_id' => $empId, 'month' => $month],
                    [
                        'earnings_json' => $result['earnings'],
                        'deductions_json' => $baseDeductions,
                        'benefits_json' => $baseBenefits,
                        'reimbursements_json' => $result['reimbursements'],
                        'statutory_json' => $statutoryWithAttendance,
                        'gross_salary' => round($monthlyGross, 2),
                        'total_deductions' => round($monthlyDeductions, 2),
                        'employer_contribution' => round($monthlyBenefits, 2),
                        'net_salary' => round($monthlyNet, 2),
                        'is_locked' => 1,
                        'created_by' => $creatorId,
                    ]
                );
            }

            DB::table('payroll_audit_logs')->insert([
                'company_id' => $creatorId,
                'user_id' => \Auth::id(),
                'action' => 'RUN_PAYROLL',
                'entity_type' => 'payroll',
                'entity_id' => null,
                'meta' => json_encode(['month' => $month]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', __('Payroll run failed: ') . $e->getMessage());
        }

        $monthLabel = \Carbon\Carbon::parse($month . '-01')->format('F Y');
        $empCount = Payroll::where('created_by', $creatorId)->where('month', $month)->count();
        return back()->with('success', __(':month payroll has been generated successfully for :count employees.', ['month' => $monthLabel, 'count' => $empCount]));
    }

    /**
     * Build a payroll record for an employee whose type does NOT use the
     * standard CTC structure (Intern, Consultant, etc.). The pay is the
     * employee's flat monthly amount (stipend or retainer) prorated by
     * attendance, with optional flat-rate TDS and zero statutory deductions.
     *
     * Paid days = month_calendar_days − (Absent + HD-deduction)
     * Per-day = monthly_amount / month_calendar_days
     * Earned = per_day × paid_days
     * TDS = flat_tds_rate% × earned (if tds_applicable)
     * Net = Earned − TDS
     */
    private function processNonCtcEmployee(
        Employee $employee,
        \App\Models\EmployeeType $type,
        string $month,
        string $monthStart,
        string $monthEnd,
        int $creatorId,
        array $holidayDatesSet
    ): void {
        $empId = (int) $employee->id;

        // Determine the monthly amount source from the type.
        // Intern → monthly_stipend; everyone else (Consultant) → employees.salary.
        $monthlyAmount = $type->code === 'intern'
            ? (float) ($employee->monthly_stipend ?? 0)
            : (float) ($employee->salary ?? 0);

        if ($monthlyAmount <= 0) {
            // Nothing to compute — skip this employee silently.
            return;
        }

        // ── Attendance: prefer synced snapshot, fallback to raw counting ──
        $syncedData = PayrollAttendanceSync::where('employee_id', $empId)
            ->where('month', $month)
            ->where('created_by', $creatorId)
            ->first();

        if ($syncedData) {
            $presentDays      = (int)   $syncedData->present;
            $halfDays         = (int)   $syncedData->half_day;
            $absentDays       = (int)   $syncedData->absent;
            $leaveDays        = (int)   $syncedData->leave;
            $absentEffective  = (float) ($syncedData->absent_effective ?? $absentDays);
            $hdDeduction      = (float) ($syncedData->hd_deduction ?? ($halfDays * 0.5));
            $weeklyOffs       = (int)   ($syncedData->weekly_offs ?? 0);
            $monthTotalDays   = (int)   ($syncedData->month_total_days ?? (int) date('t', strtotime($monthStart)));
            $totalWorkingDays = (int)   $syncedData->working_days;
        } else {
            $attendance = AttendanceEmployee::where('employee_id', $empId)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->get();

            $presentDays = $halfDays = $absentDays = $leaveDays = 0;
            $holidayPresentCount = 0;
            foreach ($attendance as $aRec) {
                $aDate   = (string) $aRec->date;
                $aStatus = strtolower((string) $aRec->status);
                if (isset($holidayDatesSet[$aDate]) && ($aStatus === 'present' || $aStatus === 'half day')) {
                    $holidayPresentCount++;
                    continue;
                }
                if ($aStatus === 'present')      $presentDays++;
                elseif ($aStatus === 'half day') $halfDays++;
                elseif ($aStatus === 'absent')   $absentDays++;
                elseif ($aStatus === 'leave')    $leaveDays++;
            }
            $totalWorkingDays = $attendance->count() - $holidayPresentCount;

            // DOJ-aware month total + weekly offs
            $empDoj = !empty($employee->company_doj) ? \Carbon\Carbon::parse($employee->company_doj) : null;
            $mStart = \Carbon\Carbon::parse($monthStart)->startOfDay();
            $mEnd   = \Carbon\Carbon::parse($monthEnd)->startOfDay();
            $effectiveStart = ($empDoj && $empDoj->gt($mStart) && $empDoj->lte($mEnd))
                ? $empDoj->copy()->startOfDay()
                : $mStart->copy();
            $monthTotalDays = (int) round($effectiveStart->diffInDays($mEnd)) + 1;

            $weeklyOffDaysSetting = array_map('intval', array_filter(
                explode(',', (string) Utility::getValByName('weekly_off_days')),
                fn($v) => $v !== ''
            ));
            if (empty($weeklyOffDaysSetting)) {
                $weeklyOffDaysSetting = [\Carbon\Carbon::SUNDAY];
            }
            $weeklyOffs = 0;
            $dd = $effectiveStart->copy();
            while ($dd->lte($mEnd)) {
                if (in_array($dd->dayOfWeek, $weeklyOffDaysSetting)) {
                    $weeklyOffs++;
                }
                $dd->addDay();
            }

            $absentEffective = (float) $absentDays;
            $hdDeduction     = (float) $halfDays * 0.5;
        }

        // ── Salary math ──
        // Gross = full monthly amount (Stipend / Retainer)
        // Deductions = attendance-based + flat TDS
        // Net = Gross − Deductions
        // (Same shape as existing CTC payslips — keeps the view template happy.)
        $monthCalendarDays = (int) date('t', strtotime($monthStart));
        $perDay            = $monthCalendarDays > 0 ? round($monthlyAmount / $monthCalendarDays, 2) : 0;
        $unpaidDays        = $absentEffective + $hdDeduction;
        $paidDays          = max($monthTotalDays - $unpaidDays, 0);
        $earnedGross       = round($perDay * $paidDays, 2);

        // Attendance-based deductions
        $absentDeduction = $absentEffective > 0 ? round($perDay * $absentEffective, 2) : 0;
        $hdAmount        = $hdDeduction > 0     ? round($perDay * $hdDeduction, 2)     : 0;

        // ── TDS (flat rate, e.g. 10% for consultants) — applied to EARNED gross ──
        $tdsAmount = 0.0;
        if ($type->tds_applicable && $type->flat_tds_rate > 0 && $earnedGross > 0) {
            $tdsAmount = round($earnedGross * ((float) $type->flat_tds_rate / 100), 2);
        }

        // ── Build payslip JSON arrays ──
        // Earnings: single line at full monthly amount (annual = ×12 to match
        // existing template which divides by 12 to display monthly).
        $earningsJson = [[
            'name'      => $type->code === 'intern' ? 'Stipend' : 'Retainer',
            'amount'    => round($monthlyAmount * 12, 2),
            'frequency' => 'monthly',
        ]];

        $deductionsJson = [];
        if ($absentDeduction > 0) {
            $deductionsJson[] = ['name' => 'Absent Deduction (' . $absentEffective . ' days)', 'amount' => $absentDeduction];
        }
        if ($hdAmount > 0) {
            $deductionsJson[] = ['name' => 'Half Day Deduction (' . $hdDeduction . ' days)', 'amount' => $hdAmount];
        }
        if ($tdsAmount > 0) {
            $deductionsJson[] = [
                'name'   => 'TDS @ ' . rtrim(rtrim(number_format((float) $type->flat_tds_rate, 2), '0'), '.') . '%',
                'amount' => $tdsAmount,
            ];
        }

        $totalDeductions = round(array_sum(array_column($deductionsJson, 'amount')), 2);
        $netSalary       = round($monthlyAmount - $totalDeductions, 2);

        $statutoryJson = [
            'epf_employee'  => 0, 'epf_employer'  => 0,
            'esic_employee' => 0, 'esic_employer' => 0,
            'pt'            => 0, 'lwf_employee'  => 0, 'lwf_employer' => 0,
            'attendance' => [
                'working_days'        => $totalWorkingDays,
                'month_total_days'    => $monthTotalDays,
                'weekly_offs'         => $weeklyOffs,
                'present'             => $presentDays,
                'half_day'            => $halfDays,
                'absent'              => $absentDays,
                'leave'               => $leaveDays,
                'absent_effective'    => $absentEffective,
                'hd_deduction'        => $hdDeduction,
                'paid_days'           => $paidDays,
                'unpaid_days'         => $unpaidDays,
                'month_calendar_days' => $monthCalendarDays,
                'per_day_salary'      => $perDay,
            ],
            'tds' => [
                'flat_rate'  => (float) $type->flat_tds_rate,
                'tds_amount' => $tdsAmount,
                'mode'       => $tdsAmount > 0 ? 'flat' : 'none',
            ],
            'employee_type' => [
                'id'                 => $type->id,
                'code'               => $type->code,
                'name'               => $type->name,
                'monthly_amount'     => $monthlyAmount,
                'attendance_prorata' => true,
                'ctc_applicable'     => false,
            ],
        ];

        Payroll::updateOrCreate(
            ['employee_id' => $empId, 'month' => $month],
            [
                'earnings_json'         => $earningsJson,
                'deductions_json'       => $deductionsJson,
                'benefits_json'         => [],
                'reimbursements_json'   => [],
                'statutory_json'        => $statutoryJson,
                'gross_salary'          => $monthlyAmount,
                'total_deductions'      => $totalDeductions,
                'employer_contribution' => 0,
                'net_salary'            => $netSalary,
                'is_locked'             => 1,
                'created_by'            => $creatorId,
            ]
        );
    }

    public function deletePayroll($id)
    {
        $creatorId = \Auth::user()->creatorId();
        $payroll = Payroll::where('created_by', $creatorId)->find($id);
        if (!$payroll) {
            return back()->with('error', __('Payroll record not found.'));
        }

        DB::beginTransaction();
        try {
            $month = $payroll->month;

            // Unpay any arrears that were paid in this month so they'll be
            // picked up again if the user re-runs payroll for this month.
            SalaryIncrementHistory::where('created_by', $creatorId)
                ->where('arrears_month', $month)
                ->where('employee_id', $payroll->employee_id)
                ->update(['arrears_paid' => false]);

            $payroll->delete();

            DB::table('payroll_audit_logs')->insert([
                'company_id'  => $creatorId,
                'user_id'     => \Auth::id(),
                'action'      => 'DELETE_PAYROLL',
                'entity_type' => 'payroll',
                'entity_id'   => $id,
                'meta'        => json_encode(['month' => $month, 'employee_id' => $payroll->employee_id]),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', __('Failed to delete payroll: ') . $e->getMessage());
        }

        $monthLabel = \Carbon\Carbon::parse($month . '-01')->format('F Y');
        return back()->with('success', __(':month payroll record deleted.', ['month' => $monthLabel]));
    }

    public function deletePayrollFiltered(Request $request)
    {
        $creatorId = \Auth::user()->creatorId();

        $filterYear     = (string) $request->get('filter_year', '');
        $filterMonth    = (string) $request->get('filter_month', '');
        $filterEmployee = (int)    $request->get('filter_employee', 0);
        $filterStatus   = (string) $request->get('filter_status', '');

        // Safety gate: at least year AND month must be specified. Prevents
        // an empty-filter "delete everything" click.
        if ($filterYear === '' || $filterMonth === '') {
            return back()->with('error', __('Please select year and month before deleting.'));
        }

        $monthKey = $filterYear . '-' . str_pad($filterMonth, 2, '0', STR_PAD_LEFT);

        $query = Payroll::where('created_by', $creatorId)->where('month', $monthKey);
        if ($filterEmployee > 0) {
            $query->where('employee_id', $filterEmployee);
        }
        if ($filterStatus === 'processed') {
            $query->where('is_locked', 1);
        } elseif ($filterStatus === 'draft') {
            $query->where('is_locked', 0);
        }

        $matching = $query->get();
        $count = $matching->count();
        $monthLabel = \Carbon\Carbon::parse($monthKey . '-01')->format('F Y');

        if ($count === 0) {
            return back()->with('error', __('No payroll records match the filter for :month.', [
                'month' => $monthLabel,
            ]));
        }

        DB::beginTransaction();
        try {
            // Reset arrears_paid for every increment that paid out in this month
            // for the affected employees, so a subsequent re-run reapplies them.
            $empIds = $matching->pluck('employee_id')->unique()->values();
            SalaryIncrementHistory::where('created_by', $creatorId)
                ->where('arrears_month', $monthKey)
                ->whereIn('employee_id', $empIds)
                ->update(['arrears_paid' => false]);

            // Bulk-delete using the same filtered query.
            $query->delete();

            DB::table('payroll_audit_logs')->insert([
                'company_id'  => $creatorId,
                'user_id'     => \Auth::id(),
                'action'      => 'DELETE_PAYROLL_FILTERED',
                'entity_type' => 'payroll',
                'entity_id'   => null,
                'meta'        => json_encode([
                    'month'           => $monthKey,
                    'filter_employee' => $filterEmployee ?: null,
                    'filter_status'   => $filterStatus ?: null,
                    'deleted_count'   => $count,
                ]),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', __('Failed to delete payroll: ') . $e->getMessage());
        }

        return back()->with('success', __('Deleted :count payroll records for :month.', [
            'count' => $count,
            'month' => $monthLabel,
        ]));
    }

    public function reimbursements()
    {
        $creatorId = \Auth::user()->creatorId();
        $employees = Employee::where('created_by', $creatorId)->orderBy('name')->get();
        $components = SalaryComponent::where('created_by', $creatorId)->where('category', 'reimbursement')->where('status', 1)->get();
        $claims = ReimbursementClaim::where('created_by', $creatorId)->orderByDesc('id')->limit(30)->get();

        return view('payroll.reimbursements', compact('employees', 'components', 'claims'));
    }

    public function supplementary()
    {
        $creatorId = \Auth::user()->creatorId();
        $employees = Employee::where('created_by', $creatorId)->orderBy('name')->get();
        $adjustments = PayrollSupplementaryAdjustment::with('employee')
            ->where('created_by', $creatorId)
            ->orderByDesc('payout_month')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('payroll.supplementary', compact('employees', 'adjustments'));
    }

    public function storeSupplementary(Request $request)
    {
        $creatorId = \Auth::user()->creatorId();
        $data = $request->validate([
            'employee_id' => 'required|integer',
            'source_month' => 'required|string|size:7',
            'payout_month' => 'required|string|size:7',
            'adjustment_type' => 'required|in:credit,debit',
            'title' => 'required|string|max:120',
            'days' => 'nullable|numeric|min:0|max:31',
            'amount' => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string|max:255',
        ]);

        $employee = Employee::where('created_by', $creatorId)->findOrFail((int)$data['employee_id']);

        PayrollSupplementaryAdjustment::create([
            'employee_id' => $employee->id,
            'source_month' => $data['source_month'],
            'payout_month' => $data['payout_month'],
            'adjustment_type' => $data['adjustment_type'],
            'title' => $data['title'],
            'days' => (float)($data['days'] ?? 0),
            'amount' => (float)$data['amount'],
            'remarks' => $data['remarks'] ?? null,
            'status' => 1,
            'created_by' => $creatorId,
        ]);

        return back()->with('success', __('Supplementary adjustment added. Re-run payroll for :month to include it.', ['month' => $data['payout_month']]));
    }

    public function deleteSupplementary(int $id)
    {
        $creatorId = \Auth::user()->creatorId();
        PayrollSupplementaryAdjustment::where('created_by', $creatorId)
            ->where('id', $id)
            ->delete();

        return back()->with('success', __('Supplementary adjustment removed. Re-run payroll if this month was already generated.'));
    }

    public function storeReimbursement(Request $request)
    {
        $creatorId = \Auth::user()->creatorId();
        $data = $request->validate([
            'employee_id' => 'required|integer',
            'component_id' => 'required|integer',
            'claim_month' => 'required|string|size:7',
            'amount' => 'required|numeric|min:0',
            'remarks' => 'nullable|string|max:255',
            // NEW: receipt attachment (JPEG / PDF). Keep optional so pending claims without files still work.
            'attachment' => 'nullable|file|mimes:jpeg,jpg,pdf|max:5120',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            try {
                $file = $request->file('attachment');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $attachmentPath = $file->storeAs('payroll/reimbursements', $fileName, 'public');
            } catch (\Exception $e) {
                \Log::error('Failed to upload reimbursement receipt: ' . $e->getMessage());

                return back()->with('error', __('Failed to upload receipt. Please try again.'));
            }
        }

        $payload = [
            'employee_id' => (int) $data['employee_id'],
            'component_id' => (int) $data['component_id'],
            'claim_month' => $data['claim_month'],
            'amount' => (float) $data['amount'],
            'status' => 'pending',
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $creatorId,
        ];

        // Only set attachment when column exists (older live DBs before migration)
        if (\Illuminate\Support\Facades\Schema::hasColumn('reimbursement_claims', 'attachment')) {
            $payload['attachment'] = $attachmentPath;
        }

        ReimbursementClaim::create($payload);

        return back()->with('success', __('Reimbursement claim submitted.'));
    }

    public function viewSalaryStructure(int $id, SalaryCalculator $calculator)
    {
        $creatorId = \Auth::user()->creatorId();
        $employee = Employee::where('created_by', $creatorId)->findOrFail($id);
        $salaryConfig = EmployeeSalary::where('employee_id', $id)->first();
        $previewMonth = request()->get('preview_month', now()->format('Y-m'));

        if (!$salaryConfig || $salaryConfig->ctc <= 0) {
            return back()->with('error', __('Salary not configured for this employee. Please set CTC first.'));
        }

        $structure = SalaryStructure::find($salaryConfig->structure_id);
        if (!$structure) {
            return back()->with('error', __('Salary structure not found. Please select a valid structure.'));
        }

        try {
            $breakdown = $calculator->calculate($id, $previewMonth);
        } catch (\Throwable $e) {
            return back()->with('error', __('Error calculating salary: ') . $e->getMessage());
        }

        if (isset($breakdown['error'])) {
            return back()->with('error', $breakdown['error']);
        }

        $specialAllowances = PayrollSpecialAllowance::where('created_by', $creatorId)
            ->where('employee_id', $id)
            ->where('month', $previewMonth)
            ->orderByDesc('id')
            ->get();

        $specialDeductions = PayrollSpecialDeduction::where('created_by', $creatorId)
            ->where('employee_id', $id)
            ->where('month', $previewMonth)
            ->orderByDesc('id')
            ->get();

        // Year-wise increment history built strictly from rows the user has
        // actually recorded in `salary_increment_history` — no synthetic joining
        // row, no inferred values. For each event we also compute the monthly
        // component breakdown of the "new CTC" using the same split rules as
        // SalaryCalculator, so the row can expand to show a structure preview.
        $basicPct = (float) ($salaryConfig->basic_percentage ?? 50);
        $splitter = function (float $ctc) use ($basicPct) {
            return $this->splitCtcToComponents($ctc, $basicPct);
        };

        $yearlyIncrements = SalaryIncrementHistory::where('employee_id', $id)
            ->orderBy('effective_date')
            ->get()
            ->groupBy(fn($row) => \Carbon\Carbon::parse($row->effective_date)->format('Y'))
            ->map(function ($rows) use ($splitter) {
                $sorted = $rows->sortBy('effective_date')->values();
                $first  = $sorted->first();
                $last   = $sorted->last();
                return [
                    'year'                 => \Carbon\Carbon::parse($last->effective_date)->format('Y'),
                    'old_ctc'              => $first->old_ctc,
                    'new_ctc'              => $last->new_ctc,
                    'increment_amount'     => $sorted->sum('increment_amount'),
                    'increment_percentage' => $last->increment_percentage,
                    'count'                => $sorted->count(),
                    'remarks'              => $last->remarks,
                    'components'           => $splitter((float) $last->new_ctc),
                    'events'               => $sorted->map(fn($r) => [
                        'effective_date'       => $r->effective_date,
                        'month'                => \Carbon\Carbon::parse($r->effective_date)->format('M Y'),
                        'old_ctc'              => $r->old_ctc,
                        'new_ctc'              => $r->new_ctc,
                        'increment_amount'     => $r->increment_amount,
                        'increment_percentage' => $r->increment_percentage,
                        'remarks'              => $r->remarks,
                        'components'           => $splitter((float) $r->new_ctc),
                    ])->values()->all(),
                ];
            })
            ->sortByDesc('year')
            ->values();

        return view('payroll.salary_view', compact('employee', 'salaryConfig', 'structure', 'breakdown', 'previewMonth', 'specialAllowances', 'specialDeductions', 'yearlyIncrements'));
    }

    public function storeSpecialAllowance(Request $request, int $id)
    {
        $creatorId = \Auth::user()->creatorId();
        $employee = Employee::where('created_by', $creatorId)->findOrFail($id);

        $data = $request->validate([
            'month' => 'required|string|size:7',
            'title' => 'required|string|max:120',
            'amount' => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string|max:255',
        ]);

        PayrollSpecialAllowance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'month' => $data['month'],
                'created_by' => $creatorId,
            ],
            [
                'title' => $data['title'],
                'amount' => (float)$data['amount'],
                'remarks' => $data['remarks'] ?? null,
            ]
        );

        return back()->with('success', __('Special allowance saved for :month.', ['month' => $data['month']]));
    }

    public function deleteSpecialAllowance(int $id, int $allowanceId)
    {
        $creatorId = \Auth::user()->creatorId();
        $employee = Employee::where('created_by', $creatorId)->findOrFail($id);

        PayrollSpecialAllowance::where('created_by', $creatorId)
            ->where('employee_id', $employee->id)
            ->where('id', $allowanceId)
            ->delete();

        return back()->with('success', __('Special allowance removed.'));
    }

    public function storeSpecialDeduction(Request $request, int $id)
    {
        $creatorId = \Auth::user()->creatorId();
        $employee = Employee::where('created_by', $creatorId)->findOrFail($id);

        $data = $request->validate([
            'month' => 'required|string|size:7',
            'title' => 'required|string|max:120',
            'amount' => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string|max:255',
        ]);

        PayrollSpecialDeduction::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'month' => $data['month'],
                'title' => $data['title'],
                'created_by' => $creatorId,
            ],
            [
                'amount' => (float)$data['amount'],
                'remarks' => $data['remarks'] ?? null,
            ]
        );

        return back()->with('success', __('Special deduction saved for :month.', ['month' => $data['month']]));
    }

    public function deleteSpecialDeduction(int $id, int $deductionId)
    {
        $creatorId = \Auth::user()->creatorId();
        $employee = Employee::where('created_by', $creatorId)->findOrFail($id);

        PayrollSpecialDeduction::where('created_by', $creatorId)
            ->where('employee_id', $employee->id)
            ->where('id', $deductionId)
            ->delete();

        return back()->with('success', __('Special deduction removed.'));
    }

    public function salarySlip(int $id)
    {
        $authUser = \Auth::user();
        $creatorId = $authUser->creatorId();

        $payroll = Payroll::with('employee')->findOrFail($id);

        // Employee can only see their own payslip
        if ($authUser->type === 'employee') {
            $emp = $authUser->employee;
            if (!$emp || (int)$payroll->employee_id !== (int)$emp->id) {
                return redirect()->route('dashboard')->with('error', __('Access denied.'));
            }
        } else {
            // Admin/HR can see payslips of their company employees
            if ((int)$payroll->created_by !== (int)$creatorId) {
                return redirect()->back()->with('error', __('Access denied.'));
            }
        }

        $company = User::find($creatorId);

        // ── Auto-backfill component-wise arrears breakup if missing ──
        // Old payroll runs stored only a lumpsum arrears amount. If this payroll
        // has arrears but no per-component breakup, recompute it from the
        // salary increment history and persist back to statutory_json.
        $statJson = $payroll->statutory_json ?? [];
        $attnNode = $statJson['attendance'] ?? [];
        $arrearsTotal = (float)($attnNode['arrears_total'] ?? 0);
        $arrearsDetails = $attnNode['arrears_details'] ?? [];
        $needsBackfill = $arrearsTotal > 0 && (
            empty($arrearsDetails) ||
            empty($arrearsDetails[0]['breakup'] ?? null)
        );

        if ($needsBackfill) {
            $empSalary = EmployeeSalary::where('employee_id', $payroll->employee_id)->first();
            $basicPct = (float)($empSalary->basic_percentage ?? 50);

            $increments = SalaryIncrementHistory::where('employee_id', $payroll->employee_id)
                ->where('arrears_month', $payroll->month)
                ->get();

            $newDetails = [];
            $newComponents = [];
            $newTotal = 0;

            foreach ($increments as $arrear) {
                $ab = $this->computeArrearBreakup($arrear, $basicPct, (int)$payroll->employee_id, (int)$payroll->created_by);

                foreach ($ab['components'] as $name => $amt) {
                    $newComponents[$name] = ($newComponents[$name] ?? 0) + $amt;
                }

                $newDetails[] = [
                    'id' => $arrear->id,
                    'old_ctc' => (float)$arrear->old_ctc,
                    'new_ctc' => (float)$arrear->new_ctc,
                    'effective_date' => \Carbon\Carbon::parse($arrear->effective_date)->format('Y-m-d'),
                    'months' => $ab['months_count'],
                    'monthly_diff' => round(((float)$arrear->new_ctc - (float)$arrear->old_ctc) / 12, 2),
                    'amount' => $ab['total'],
                    'breakup' => $ab['breakup'],
                    'per_month' => $ab['per_month'],
                ];
                $newTotal += $ab['total'];
            }

            if (!empty($newDetails)) {
                $attnNode['arrears_details'] = $newDetails;
                $attnNode['arrears_components'] = $newComponents;
                $attnNode['arrears_total'] = round($newTotal, 2);
                $statJson['attendance'] = $attnNode;
                $payroll->statutory_json = $statJson;
                $payroll->save();
                $payroll->refresh();
            }
        }

        return view('payroll.salary_slip', compact('payroll', 'company'));
    }

    public function breakdown(int $id)
    {
        $authUser = \Auth::user();
        $creatorId = $authUser->creatorId();
        $payroll = Payroll::with('employee')->findOrFail($id);

        if ($authUser->type === 'employee') {
            $emp = $authUser->employee;
            if (!$emp || (int)$payroll->employee_id !== (int)$emp->id) {
                return redirect()->route('dashboard')->with('error', __('Access denied.'));
            }
        } elseif ((int)$payroll->created_by !== (int)$creatorId) {
            return redirect()->back()->with('error', __('Access denied.'));
        }

        return view('payroll.breakdown', compact('payroll'));
    }

    public function myPayslips()
    {
        $authUser = \Auth::user();
        if ($authUser->type !== 'employee') {
            return redirect()->route('dashboard');
        }

        $emp = $authUser->employee;
        if (!$emp) {
            return redirect()->route('dashboard')->with('error', __('Employee record not found.'));
        }

        $payslips = Payroll::where('employee_id', $emp->id)
            ->orderByDesc('month')
            ->get();

        return view('payroll.my_payslips', compact('payslips'));
    }

    public function updateReimbursementStatus(Request $request, int $id)
    {
        $creatorId = \Auth::user()->creatorId();
        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);
        $claim = ReimbursementClaim::where('created_by', $creatorId)->findOrFail($id);
        $claim->update([
            'status' => $data['status'],
            'approved_by' => \Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', __('Claim status updated.'));
    }

    /**
     * Compute full arrears breakup for a single salary increment record.
     * Returns:
     *   [
     *     'total' => float,
     *     'components' => ['Basic' => amount, 'HRA' => ..., 'Overtime' => ..., 'Attendance Adjustment' => ...],
     *     'breakup' => [ ['name','old_monthly','new_monthly','diff_monthly','months','amount'], ... ],
     *     'per_month' => [ ['month' => 'YYYY-MM', 'ot_diff' => .., 'attn_diff' => .., 'details' => ...], ... ],
     *   ]
     *
     * Per-month adjustments included:
     *  - OT arrear: (new_basic_OT_rate − old_basic_OT_rate) × hours × 1.5
     *  - Attendance impact is applied by prorating fixed-component arrears by each
     *    month's paid-day fraction: (calendar_days - unpaid_days) / calendar_days.
     *    (So arrears aren't blindly diff/month × number_of_months.)
     *
     * Note: We still return per-month attendance "adj" as an informational figure:
     *   −(new_per_day − old_per_day) × unpaid_days
     * but we do not add it as a separate breakup line to avoid double-adjusting.
     */
    public function computeArrearBreakup($arrear, float $basicPct, int $employeeId, int $creatorId): array
    {
        $oldCtc = (float)$arrear->old_ctc;
        $newCtc = (float)$arrear->new_ctc;
        $effectiveMonth = \Carbon\Carbon::parse($arrear->effective_date)->startOfMonth();
        $payoutMonth = \Carbon\Carbon::parse($arrear->arrears_month . '-01');
        $monthsCount = (int)$effectiveMonth->diffInMonths($payoutMonth);
        if ($monthsCount <= 0) { $monthsCount = 1; }

        // ── Component split (monthly) ──
        $oldSplit = $this->splitCtcToComponents($oldCtc, $basicPct);
        $newSplit = $this->splitCtcToComponents($newCtc, $basicPct);

        $breakup = [];
        $components = [];
        $total = 0.0;

        // ── Per-past-month adjustments for OT & unpaid attendance ──
        // New vs old per-day (gross-based) & new vs old OT-per-hour (basic-based).
        $oldBasicMonthly = $oldSplit['Basic'] ?? 0;
        $newBasicMonthly = $newSplit['Basic'] ?? 0;
        $oldGrossMonthly = array_sum($oldSplit);
        $newGrossMonthly = array_sum($newSplit);

        // OT rate = (basic_monthly / 26 / 8) × 1.5 per hour
        $oldOtRatePerHour = $oldBasicMonthly > 0 ? round(($oldBasicMonthly / 26) / 8 * 1.5, 4) : 0;
        $newOtRatePerHour = $newBasicMonthly > 0 ? round(($newBasicMonthly / 26) / 8 * 1.5, 4) : 0;
        $otRateDiff = round($newOtRatePerHour - $oldOtRatePerHour, 4);

        $perMonth = [];
        $otArrearTotal = 0.0;
        $paidMonthsEquivalent = 0.0;

        // Walk each past month from effective_date to arrears_month (exclusive)
        $cursor = $effectiveMonth->copy();
        while ($cursor->lt($payoutMonth)) {
            $monthKey = $cursor->format('Y-m');
            $monthStart = $monthKey . '-01';
            $monthEnd = date('Y-m-t', strtotime($monthStart));
            $monthCalendarDays = (int)date('t', strtotime($monthStart));

            // Try sync table first, then raw attendance
            $sync = \App\Models\PayrollAttendanceSync::where('employee_id', $employeeId)
                ->where('month', $monthKey)
                ->where('created_by', $creatorId)
                ->first();

            if ($sync) {
                $otHours = 0.0; // Sync doesn't store OT hours; fetch from attendance
                $unpaidDays = (float)($sync->absent_effective ?? 0) + (float)($sync->hd_deduction ?? 0);
            } else {
                $unpaidDays = 0.0;
            }

            // Fetch OT hours and unpaid days from raw attendance (authoritative)
            $attRows = \App\Models\AttendanceEmployee::where('employee_id', $employeeId)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->get(['overtime', 'status']);

            $otHours = 0.0;
            $rawAbsent = 0;
            $rawHalfDay = 0;
            foreach ($attRows as $r) {
                $st = strtolower((string)$r->status);
                if ($st === 'absent') $rawAbsent++;
                if ($st === 'half day') $rawHalfDay++;
                if ($st !== 'absent' && $st !== 'leave') {
                    $otHours += $this->timeToHours((string)($r->overtime ?? '00:00:00'));
                }
            }
            if (!$sync) {
                $unpaidDays = $rawAbsent + ($rawHalfDay * 0.5);
            }

            // Paid-day fraction for this month (used to prorate fixed arrears)
            if ($monthCalendarDays > 0) {
                $paidFraction = ($monthCalendarDays - $unpaidDays) / $monthCalendarDays;
                if ($paidFraction < 0) { $paidFraction = 0.0; }
                if ($paidFraction > 1) { $paidFraction = 1.0; }
                $paidMonthsEquivalent += $paidFraction;
            }

            // OT arrear for this month: diff rate × hours
            $otArrearMonth = 0.0;
            if ($otHours > 0 && $otRateDiff > 0) {
                $otArrearMonth = round($otRateDiff * $otHours, 2);
            }

            // Attendance adjustment: new salary has higher per-day, so unpaid days
            // cost MORE on new salary → arrear is REDUCED by that extra deduction.
            $attnAdjustMonth = 0.0;
            if ($unpaidDays > 0 && $monthCalendarDays > 0) {
                $oldPerDay = $oldGrossMonthly / $monthCalendarDays;
                $newPerDay = $newGrossMonthly / $monthCalendarDays;
                $perDayDiff = $newPerDay - $oldPerDay;
                $attnAdjustMonth = -1 * round($perDayDiff * $unpaidDays, 2);
            }

            if ($otArrearMonth != 0 || $attnAdjustMonth != 0) {
                $perMonth[] = [
                    'month' => $monthKey,
                    'ot_hours' => round($otHours, 2),
                    'ot_old_rate' => $oldOtRatePerHour,
                    'ot_new_rate' => $newOtRatePerHour,
                    'ot_arrear' => $otArrearMonth,
                    'unpaid_days' => round($unpaidDays, 2),
                    'attn_adjust' => $attnAdjustMonth,
                ];
            }

            $otArrearTotal += $otArrearMonth;

            $cursor->addMonth();
        }

        // ── Fixed component-wise salary arrears (attendance prorated) ──
        $effectiveMonthsMultiplier = $paidMonthsEquivalent > 0 ? $paidMonthsEquivalent : (float)$monthsCount;
        $effectiveMonthsLabel = rtrim(rtrim(number_format($effectiveMonthsMultiplier, 2, '.', ''), '0'), '.');

        foreach ($newSplit as $name => $newMonthly) {
            $oldMonthly = $oldSplit[$name] ?? 0;
            $diffMonthly = round($newMonthly - $oldMonthly, 2);
            if ($diffMonthly <= 0) continue;
            $componentArrear = round($diffMonthly * $effectiveMonthsMultiplier, 2);
            $breakup[] = [
                'name' => $name,
                'old_monthly' => round($oldMonthly, 2),
                'new_monthly' => round($newMonthly, 2),
                'diff_monthly' => $diffMonthly,
                'months' => $effectiveMonthsLabel,
                'amount' => $componentArrear,
            ];
            $components[$name] = ($components[$name] ?? 0) + $componentArrear;
            $total += $componentArrear;
        }

        // Add OT arrear as a breakup line (if any)
        if ($otArrearTotal != 0) {
            $otArrearTotal = round($otArrearTotal, 2);
            $breakup[] = [
                'name' => 'Overtime Arrears',
                'old_monthly' => $oldOtRatePerHour,
                'new_monthly' => $newOtRatePerHour,
                'diff_monthly' => round($newOtRatePerHour - $oldOtRatePerHour, 2),
                'months' => $monthsCount,
                'amount' => $otArrearTotal,
            ];
            $components['Overtime Arrears'] = ($components['Overtime Arrears'] ?? 0) + $otArrearTotal;
            $total += $otArrearTotal;
        }

        return [
            'total' => round($total, 2),
            'components' => $components,
            'breakup' => $breakup,
            'per_month' => $perMonth,
            'months_count' => $monthsCount,
            'months_multiplier' => $effectiveMonthsMultiplier,
            'months_multiplier_label' => $effectiveMonthsLabel,
        ];
    }

    /**
     * Split a CTC (annual) into monthly component amounts using the same
     * rules as SalaryCalculator. Returns ['Basic' => monthly, ...].
     * Used to compute component-wise arrears breakup.
     */
    private function splitCtcToComponents(float $ctc, float $basicPct): array
    {
        if ($ctc <= 0) return [];

        $basicAnnual = round($ctc * ($basicPct / 100));
        $basicMonthly = $basicAnnual / 12;

        // Employer contributions that come out of CTC
        $pfBaseMonthly = min($basicMonthly, \App\Services\SalaryCalculator::PF_BASIC_CAP_MONTHLY);
        $pfEmployerAnnual = round($pfBaseMonthly * \App\Services\SalaryCalculator::PF_RATE) * 12;
        $gratuityAnnual = round($basicAnnual * \App\Services\SalaryCalculator::GRATUITY_RATE);

        $grossAnnual = $ctc - $pfEmployerAnnual - $gratuityAnnual;

        $hraAnnual = round($basicAnnual * 0.50);
        $conveyanceAnnual = \App\Services\SalaryCalculator::CONVEYANCE_ANNUAL;
        $medicalAnnual = \App\Services\SalaryCalculator::MEDICAL_ANNUAL;

        $remaining = $grossAnnual - $basicAnnual - $hraAnnual;
        if ($remaining < $conveyanceAnnual + $medicalAnnual) {
            if ($remaining >= $conveyanceAnnual) {
                $medicalAnnual = max(0, $remaining - $conveyanceAnnual);
            } else {
                $conveyanceAnnual = max(0, $remaining);
                $medicalAnnual = 0;
            }
        }

        $specialAnnual = max(0, $grossAnnual - $basicAnnual - $hraAnnual - $conveyanceAnnual - $medicalAnnual);

        return [
            'Basic'                => round($basicAnnual / 12, 2),
            'HRA'                  => round($hraAnnual / 12, 2),
            'Conveyance Allowance' => round($conveyanceAnnual / 12, 2),
            'Medical Allowance'    => round($medicalAnnual / 12, 2),
            'Special Allowance'    => round($specialAnnual / 12, 2),
        ];
    }

    private function timeToHours(string $time): float
    {
        $time = trim($time);
        if ($time === '' || $time === '00:00:00') {
            return 0.0;
        }

        $parts = explode(':', $time);
        if (count($parts) < 2) {
            return 0.0;
        }

        $h = (int)($parts[0] ?? 0);
        $m = (int)($parts[1] ?? 0);
        $s = (int)($parts[2] ?? 0);

        return round($h + ($m / 60) + ($s / 3600), 4);
    }
}

