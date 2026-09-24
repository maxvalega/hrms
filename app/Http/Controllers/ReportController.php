<?php

namespace App\Http\Controllers;

use App\Exports\accountstatementExport;
use App\Exports\LeaveExport;
use App\Exports\LeaveReportExport;
use App\Exports\PayrollExport;
use App\Exports\ReimbursementExport;
use App\Exports\TimesheetExport;
use App\Exports\TimesheetReportExport;
use App\Models\AccountList;
use App\Models\AttendanceEmployee;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Deposit;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\PaySlip;
use App\Models\ReimbursementClaim;
use App\Models\TimeSheet;
use App\Services\LeavePolicyService;
use App\Services\VicConsolidateAttendanceImport;
use App\Support\TenantHost;
use App\Models\Utility;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{

    public function incomeVsExpense(Request $request)
    {

        if (\Auth::user()->can('Manage Report')) {
            $deposit = Deposit::where('created_by', \Auth::user()->creatorId());

            $labels       = $data = [];
            $expenseCount = $incomeCount = 0;
            $incomeData = [];
            $expenseData = [];
            if (!empty($request->start_month) && !empty($request->end_month)) {

                $start = strtotime($request->start_month);
                $end   = strtotime($request->end_month);

                $currentdate = $start;
                $month       = [];
                while ($currentdate <= $end) {
                    $month = date('m', $currentdate);
                    $year  = date('Y', $currentdate);

                    $depositFilter = Deposit::where('created_by', \Auth::user()->creatorId())->whereMonth('date', $month)->whereYear('date', $year)->get();

                    $depositsTotal = 0;
                    foreach ($depositFilter as $deposit) {
                        $depositsTotal += $deposit->amount;
                    }

                    $incomeData[] = $depositsTotal;
                    $incomeCount  += $depositsTotal;

                    $expenseFilter = Expense::where('created_by', \Auth::user()->creatorId())->whereMonth('date', $month)->whereYear('date', $year)->get();
                    $expenseTotal  = 0;
                    foreach ($expenseFilter as $expense) {
                        $expenseTotal += $expense->amount;
                    }
                    $expenseData[] = $expenseTotal;
                    $expenseCount  += $expenseTotal;

                    $labels[]    = date('M Y', $currentdate);
                    $currentdate = strtotime('+1 month', $currentdate);
                }

                $filter['startDateRange'] = date('M-Y', strtotime($request->start_month));
                $filter['endDateRange']   = date('M-Y', strtotime($request->end_month));
            } else {
                for ($i = 0; $i < 6; $i++) {

                    $month = date('m', strtotime("-$i month"));
                    $year  = date('Y', strtotime("-$i month"));

                    $depositFilter = Deposit::where('created_by', \Auth::user()->creatorId())->whereMonth('date', $month)->whereYear('date', $year)->get();

                    $depositTotal = 0;
                    foreach ($depositFilter as $deposit) {
                        $depositTotal += $deposit->amount;
                    }

                    $incomeData[] = $depositTotal;
                    $incomeCount  += $depositTotal;

                    $expenseFilter = Expense::where('created_by', \Auth::user()->creatorId())->whereMonth('date', $month)->whereYear('date', $year)->get();
                    $expenseTotal  = 0;
                    foreach ($expenseFilter as $expense) {
                        $expenseTotal += $expense->amount;
                    }
                    $expenseData[] = $expenseTotal;
                    $expenseCount  += $expenseTotal;

                    $labels[] = date('M Y', strtotime("-$i month"));
                }
                $filter['startDateRange'] = date('M-Y');
                $filter['endDateRange']   = date('M-Y', strtotime("-5 month"));
            }

            $incomeArr['name'] = __('Income');
            $incomeArr['data'] = $incomeData;

            $expenseArr['name'] = __('Expense');
            $expenseArr['data'] = $expenseData;

            $data[] = $incomeArr;
            $data[] = $expenseArr;



            return view('report.income_expense', compact('labels', 'data', 'incomeCount', 'expenseCount', 'filter'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function leaveExport(Request $request)
    {
        if (!\Auth::user()->can('Manage Report')) {
            abort(403);
        }

        $cid = \Auth::user()->creatorId();
        $employees = Employee::where('created_by', $cid);
        if (!empty($request->branch)) {
            $employees->where('branch_id', $request->branch);
        }
        if (!empty($request->department)) {
            $employees->where('department_id', $request->department);
        }
        $employees = $employees->orderBy('employee_id')->get();

        $leaveTypes = LeaveType::where('created_by', $cid)
            ->where('title', 'not like', '[OLD]%')
            ->get();

        $period = $this->resolveLeaveReportPeriod($request);
        $pEnd = $period['p_end'];
        $availFrom = $period['avail_from'];
        $label = $period['label'];
        $month = $period['month'];
        $year = $period['year'];

        $asOf = Carbon::parse($pEnd, 'Asia/Kolkata');
        $isSpectal = LeavePolicyService::isSpectalPortal();
        /** @var LeaveController $leaveCtrl */
        $leaveCtrl = app(LeaveController::class);
        $policy = app(LeavePolicyService::class);

        $filename = 'leave-report-' . $label . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($employees, $leaveTypes, $pEnd, $availFrom, $request, $month, $year, $asOf, $isSpectal, $leaveCtrl, $policy) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Keep original export columns
            $header = ['Employee ID', 'Employee', 'Department'];
            foreach ($leaveTypes as $lt) {
                $header[] = $lt->title . ' (Quota)';
                $header[] = $lt->title . ' (Availed)';
                $header[] = $lt->title . ' (Balance)';
            }
            $header = array_merge($header, [
                'Total Opening',
                'Total Availed',
                'Total Remaining',
                'Carry Forward',
                'Lapsed',
                'Encash Days',
                'Encash Basis',
                'Monthly Component',
                'Daily Rate',
                'Encash Amount',
                'Pending',
            ]);
            fputcsv($f, $header);

            foreach ($employees as $emp) {
                $deptName = \DB::table('departments')->where('id', $emp->department_id)->value('name') ?? '—';
                $empSalary = \DB::table('employee_salaries')->where('employee_id', $emp->id)->first();
                $empIdFormatted = \Auth::user()->employeeIdFormat($emp->employee_id);
                $row = [$empIdFormatted, $emp->name, $deptName];

                $ctc = $empSalary ? (float) $empSalary->ctc : 0;
                $basicPct = $empSalary ? (float) ($empSalary->basic_percentage ?? 50) : 50;
                $sumOpening = $sumAvailed = $sumRemaining = $sumCF = $sumLapsed = $sumEncashDays = $sumEncashAmt = 0;
                $encashBasis = '—';
                $empMonthlyComp = 0;
                $empDailyRate = 0;

                foreach ($leaveTypes as $lt) {
                    $quota = max(0, (float) ($lt->annual_credit ?? $lt->days ?? 0));
                    if ($quota <= 0) {
                        $quota = max(0, (float) $lt->days);
                    }

                    $includeInTotals = !($isSpectal && !$policy->shouldShowOnSpectalBalance($lt, $emp, $asOf));
                    if (!$includeInTotals) {
                        $row[] = $quota;
                        $row[] = 0;
                        $row[] = 0;
                        continue;
                    }

                    $metrics = $this->leaveReportTypeMetrics($leaveCtrl, $emp, $lt, $availFrom, $pEnd, $asOf, $isSpectal);
                    $opening = $metrics['opening'];
                    $availed = $metrics['availed'];
                    $remaining = $metrics['remaining'];

                    // Original columns: Quota (policy annual) / Availed / Balance (entitlement − availed)
                    $row[] = $quota;
                    $row[] = $availed;
                    $row[] = $remaining;

                    $cf = 0;
                    if (!empty($lt->is_carry_forward) && $remaining > 0) {
                        $maxCF = !empty($lt->max_carry_forward) && (float) $lt->max_carry_forward > 0
                            ? (float) $lt->max_carry_forward
                            : $remaining;
                        $cf = min($remaining, $maxCF);
                    }

                    $encashDays = 0;
                    $encashAmt = 0;
                    if (!empty($lt->is_encashable) && $remaining > $cf) {
                        $encashDays = $remaining - $cf;
                        $basis = $lt->encash_basis ?? 'basic';
                        $monthlyComp = ($basis === 'gross')
                            ? round($ctc / 12, 2)
                            : round($ctc * $basicPct / 100 / 12, 2);
                        $dailyRate = round($monthlyComp / 26, 2);
                        $encashAmt = round($encashDays * $dailyRate, 2);
                        $encashBasis = ucfirst($basis);
                        $empMonthlyComp = $monthlyComp;
                        $empDailyRate = $dailyRate;
                    }

                    $lapsed = $remaining - $cf - $encashDays;

                    $sumOpening += $opening;
                    $sumAvailed += $availed;
                    $sumRemaining += $remaining;
                    $sumCF += $cf;
                    $sumLapsed += $lapsed;
                    $sumEncashDays += $encashDays;
                    $sumEncashAmt += $encashAmt;
                }

                $allAvailed = $this->sumApprovedLeaveDaysInPeriod((int) $emp->id, $availFrom, $pEnd, null);
                if ($allAvailed > $sumAvailed) {
                    $sumAvailed = $allAvailed;
                    $sumRemaining = max(0, $sumOpening - $sumAvailed);
                }

                $pendingQuery = Leave::where('employee_id', $emp->id)
                    ->whereRaw('LOWER(TRIM(status)) = ?', ['pending'])
                    ->where(function ($q) {
                        $q->whereNull('remark')->orWhere('remark', '!=', 'System-generated substitute block');
                    });
                if ($request->type == 'yearly' && !empty($request->year)) {
                    $pendingQuery->whereYear('start_date', $year);
                } else {
                    $pendingQuery->whereMonth('start_date', $month)->whereYear('start_date', $year);
                }
                $pending = $pendingQuery->count();

                $row = array_merge($row, [
                    round($sumOpening, 2),
                    round($sumAvailed, 2),
                    round($sumRemaining, 2),
                    $sumCF,
                    $sumLapsed,
                    $sumEncashDays,
                    $encashBasis,
                    $empMonthlyComp,
                    $empDailyRate,
                    $sumEncashAmt,
                    $pending,
                ]);
                fputcsv($f, $row);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Report window + entitlement window for Opening / Availed.
     * Opening = full type quotas; Availed = approved leave from entitlement start through period end (YTD).
     *
     * @return array{p_start:string,p_end:string,avail_from:string,label:string,month:?string,year:int}
     */
    protected function resolveLeaveReportPeriod(Request $request): array
    {
        if ($request->type == 'yearly' && !empty($request->year)) {
            $year = (int) $request->year;
            $pStart = $year . '-04-01';
            $pEnd = ($year + 1) . '-03-31';
            $availFrom = $pStart;
            $label = 'FY-' . $year . '-' . ($year + 1);
            $month = null;
        } elseif ($request->type == 'monthly' && !empty($request->month)) {
            $year = (int) date('Y', strtotime($request->month));
            $month = date('m', strtotime($request->month));
            $pStart = date('Y-m-01', strtotime($request->month));
            $pEnd = date('Y-m-t', strtotime($request->month));
            $label = date('M-Y', strtotime($request->month));

            if (\App\Services\LeavePolicyService::isSpectalPortal()) {
                $cycle = \App\Services\LeavePolicyService::spectalCycleDates(Carbon::parse($pEnd, 'Asia/Kolkata'));
                $availFrom = $cycle['cycle_start'];
            } else {
                $availFrom = $year . '-01-01';
            }
        } else {
            $year = (int) date('Y');
            $month = date('m');
            $pStart = date('Y-m-01');
            $pEnd = date('Y-m-t');
            $label = date('M-Y');
            if (\App\Services\LeavePolicyService::isSpectalPortal()) {
                $cycle = \App\Services\LeavePolicyService::spectalCycleDates(Carbon::parse($pEnd, 'Asia/Kolkata'));
                $availFrom = $cycle['cycle_start'];
            } else {
                $availFrom = $year . '-01-01';
            }
        }

        // Never count availed before entitlement window
        if ($availFrom > $pEnd) {
            $availFrom = $pStart;
        }

        return [
            'p_start' => $pStart,
            'p_end' => $pEnd,
            'avail_from' => $availFrom,
            'label' => $label,
            'month' => $month,
            'year' => $year,
        ];
    }

    /**
     * Approved leave days that overlap a report period.
     * Counts all matching leaves (optionally one type) so Availed is not lost when
     * leave_type_id is missing, [OLD], or from another type list.
     */
    protected function sumApprovedLeaveDaysInPeriod(int $employeeId, string $pStart, string $pEnd, ?int $leaveTypeId = null): float
    {
        $query = Leave::where('employee_id', $employeeId)
            ->whereRaw('LOWER(TRIM(status)) = ?', ['approved'])
            ->where(function ($q) {
                $q->whereNull('remark')->orWhere('remark', '!=', 'System-generated substitute block');
            })
            // Overlaps [pStart, pEnd]
            ->where('start_date', '<=', $pEnd)
            ->where(function ($q) use ($pStart) {
                $q->where('end_date', '>=', $pStart)
                    ->orWhere(function ($q2) use ($pStart) {
                        $q2->whereNull('end_date')->where('start_date', '>=', $pStart);
                    });
            });

        if ($leaveTypeId !== null) {
            $query->where('leave_type_id', $leaveTypeId);
        }

        return round((float) $query->sum('total_leave_days'), 2);
    }

    /**
     * Same dataset used by Leave Report UI and Excel export.
     *
     * Spectal Opening = accrued entitlement as of period end (same as Leave dashboard Total).
     * Non-Spectal Opening = annual quota.
     * Availed = approved leave from leave-year/cycle start through selected period end (YTD).
     * Remaining = Opening − Availed.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildLeaveReportRows(Request $request): array
    {
        $employees = Employee::where('created_by', \Auth::user()->creatorId());
        if (!empty($request->branch)) {
            $employees->where('branch_id', $request->branch);
        }
        if (!empty($request->department)) {
            $employees->where('department_id', $request->department);
        }
        $employees = $employees->orderBy('employee_id')->get();

        $leaveTypes = LeaveType::where('created_by', \Auth::user()->creatorId())->get();
        $period = $this->resolveLeaveReportPeriod($request);
        $pEnd = $period['p_end'];
        $availFrom = $period['avail_from'];
        $month = $period['month'];
        $year = $period['year'];
        $asOf = Carbon::parse($pEnd, 'Asia/Kolkata');
        $isSpectal = LeavePolicyService::isSpectalPortal();
        /** @var LeaveController $leaveCtrl */
        $leaveCtrl = app(LeaveController::class);
        $policy = app(LeavePolicyService::class);

        $leaves = [];
        foreach ($employees as $employee) {
            $pendingQuery = Leave::where('employee_id', $employee->id)
                ->whereRaw('LOWER(TRIM(status)) = ?', ['pending'])
                ->where(function ($q) {
                    $q->whereNull('remark')->orWhere('remark', '!=', 'System-generated substitute block');
                });

            if ($request->type == 'yearly' && !empty($request->year)) {
                $pendingQuery->whereYear('start_date', $year);
            } else {
                $pendingQuery->whereMonth('start_date', $month)->whereYear('start_date', $year);
            }

            $pending = $pendingQuery->count();

            $empSalary = \DB::table('employee_salaries')->where('employee_id', $employee->id)->first();
            $sumOpening = $sumAvailed = $sumRemaining = $sumCF = $sumLapsed = $sumEncashDays = $sumEncashAmt = 0;

            foreach ($leaveTypes as $lt) {
                if (str_starts_with((string) ($lt->title ?? ''), '[OLD]')) {
                    continue;
                }

                if ($isSpectal && !$policy->shouldShowOnSpectalBalance($lt, $employee, $asOf)) {
                    continue;
                }

                $metrics = $this->leaveReportTypeMetrics($leaveCtrl, $employee, $lt, $availFrom, $pEnd, $asOf, $isSpectal);
                $opening = $metrics['opening'];
                $availed = $metrics['availed'];
                $remaining = $metrics['remaining'];

                $cf = 0;
                if (!empty($lt->is_carry_forward) && $remaining > 0) {
                    $maxCF = !empty($lt->max_carry_forward) && (float) $lt->max_carry_forward > 0
                        ? (float) $lt->max_carry_forward
                        : $remaining;
                    $cf = min($remaining, $maxCF);
                }

                $encashDays = 0;
                $encashAmt = 0;
                if (!empty($lt->is_encashable) && $remaining > $cf) {
                    $encashDays = $remaining - $cf;
                    $ctc = $empSalary ? (float) $empSalary->ctc : 0;
                    $basicPct = $empSalary ? (float) ($empSalary->basic_percentage ?? 50) : 50;
                    $basis = $lt->encash_basis ?? 'basic';
                    $monthlyComp = ($basis === 'gross')
                        ? round($ctc / 12, 2)
                        : round($ctc * $basicPct / 100 / 12, 2);
                    $dailyRate = round($monthlyComp / 26, 2);
                    $encashAmt = round($encashDays * $dailyRate, 2);
                }

                $lapsed = $remaining - $cf - $encashDays;

                $sumOpening += $opening;
                $sumAvailed += $availed;
                $sumRemaining += $remaining;
                $sumCF += $cf;
                $sumLapsed += $lapsed;
                $sumEncashDays += $encashDays;
                $sumEncashAmt += $encashAmt;
            }

            $allAvailed = $this->sumApprovedLeaveDaysInPeriod((int) $employee->id, $availFrom, $pEnd, null);
            if ($allAvailed > $sumAvailed) {
                $sumAvailed = $allAvailed;
                $sumRemaining = max(0, $sumOpening - $sumAvailed);
            }

            $leaves[] = [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'employee' => $employee->name,
                'pending' => $pending,
                'opening' => round($sumOpening, 2),
                'availed' => round($sumAvailed, 2),
                'remaining' => round($sumRemaining, 2),
                'carry_forward' => $sumCF,
                'lapsed' => $sumLapsed,
                'encash_days' => $sumEncashDays,
                'encash_amount' => $sumEncashAmt,
            ];
        }

        return $leaves;
    }

    /**
     * Per-type Opening / Availed / Remaining for leave reports.
     *
     * @return array{opening: float, availed: float, remaining: float, quota: float}
     */
    protected function leaveReportTypeMetrics(
        LeaveController $leaveCtrl,
        Employee $employee,
        LeaveType $lt,
        string $availFrom,
        string $pEnd,
        Carbon $asOf,
        bool $isSpectal
    ): array {
        $quota = max(0, (float) ($lt->annual_credit ?? $lt->days ?? 0));
        if ($quota <= 0) {
            $quota = max(0, (float) $lt->days);
        }

        if ($isSpectal) {
            try {
                $summary = $leaveCtrl->balanceSummaryAsOf((int) $employee->id, $lt, $asOf);
                $opening = max(0, (float) ($summary['total'] ?? 0));
                $availed = max(0, (float) ($summary['used'] ?? 0));
            } catch (\Throwable $e) {
                \Log::warning('Leave report balance failed for emp ' . $employee->id . ' type ' . $lt->id . ': ' . $e->getMessage());
                $opening = $quota;
                $availed = $this->sumApprovedLeaveDaysInPeriod((int) $employee->id, $availFrom, $pEnd, (int) $lt->id);
            }
        } else {
            $opening = $quota;
            $availed = $this->sumApprovedLeaveDaysInPeriod((int) $employee->id, $availFrom, $pEnd, (int) $lt->id);
        }

        return [
            'quota' => $quota,
            'opening' => $opening,
            'availed' => $availed,
            'remaining' => max(0, round($opening - $availed, 2)),
        ];
    }

    public function leave(Request $request)
    {
        if (\Auth::user()->can('Manage Report')) {

            $branch = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $branch->prepend('All', '');

            $department = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $department->prepend('All', '');

            $filterYear['branch'] = __('All');
            $filterYear['department'] = __('All');
            $filterYear['type'] = __('Monthly');
            $filterYear['dateYearRange'] = date('M-Y');

            if (!empty($request->branch)) {
                $filterYear['branch'] = !empty(Branch::find($request->branch)) ? Branch::find($request->branch)->name : '';
            }
            if (!empty($request->department)) {
                $filterYear['department'] = !empty(Department::find($request->department)) ? Department::find($request->department)->name : '';
            }

            if ($request->type == 'monthly' && !empty($request->month)) {
                $filterYear['dateYearRange'] = date('M-Y', strtotime($request->month));
                $filterYear['type'] = __('Monthly');
            } elseif ($request->type == 'yearly' && !empty($request->year)) {
                $filterYear['dateYearRange'] = $request->year;
                $filterYear['type'] = __('Yearly');
            } elseif (!isset($request->type)) {
                $filterYear['dateYearRange'] = date('M-Y');
                $filterYear['type'] = __('Monthly');
            }

            $leaves = $this->buildLeaveReportRows($request);

            $totalApproved = 0;
            $totalReject = 0;
            $totalPending = 0;
            foreach ($leaves as $row) {
                $totalPending += (int) ($row['pending'] ?? 0);
            }

            // Keep approved/reject totals for legacy filter cards if present
            foreach ($leaves as $row) {
                $q = Leave::where('employee_id', $row['id'])->where('status', 'Approved');
                $r = Leave::where('employee_id', $row['id'])->where('status', 'Reject');
                if ($request->type == 'yearly' && !empty($request->year)) {
                    $q->whereYear('start_date', $request->year);
                    $r->whereYear('start_date', $request->year);
                } elseif ($request->type == 'monthly' && !empty($request->month)) {
                    $m = date('m', strtotime($request->month));
                    $y = date('Y', strtotime($request->month));
                    $q->whereMonth('start_date', $m)->whereYear('start_date', $y);
                    $r->whereMonth('start_date', $m)->whereYear('start_date', $y);
                } else {
                    $q->whereMonth('start_date', date('m'))->whereYear('start_date', date('Y'));
                    $r->whereMonth('start_date', date('m'))->whereYear('start_date', date('Y'));
                }
                $totalApproved += $q->count();
                $totalReject += $r->count();
            }

            $starting_year = date('Y', strtotime('-5 year'));
            $ending_year = date('Y', strtotime('+5 year'));

            $filterYear['starting_year'] = $starting_year;
            $filterYear['ending_year'] = $ending_year;

            $filter['totalApproved'] = $totalApproved;
            $filter['totalReject'] = $totalReject;
            $filter['totalPending'] = $totalPending;

            return view('report.leave', compact('department', 'branch', 'leaves', 'filterYear', 'filter'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function employeeLeave(Request $request, $employee_id, $status, $type, $month, $year)
    {
        if (\Auth::user()->can('Manage Report')) {
            $leaveTypes = LeaveType::where('created_by', \Auth::user()->creatorId())->get();
            $employee = Employee::find($employee_id);

            // Determine date range
            if ($type == 'yearly') {
                $periodStart = $year . '-04-01'; // Financial year Apr-Mar
                $periodEnd   = ($year + 1) . '-03-31';
                $periodLabel = 'Apr ' . $year . ' - Mar ' . ($year + 1);
            } else {
                $m = date('m', strtotime($month));
                $y = date('Y', strtotime($month));
                $periodStart = date('Y-m-01', strtotime($month));
                $periodEnd   = date('Y-m-t', strtotime($month));
                $periodLabel = date('M Y', strtotime($month));
            }

            $leaves = [];
            foreach ($leaveTypes as $leaveType) {
                $leaveInfo = new \stdClass();
                $leaveInfo->title = $leaveType->title;
                $leaveInfo->annual_quota = $leaveType->days;

                // Leaves taken in this period (approved)
                $takenQuery = Leave::where('employee_id', $employee_id)
                    ->where('status', 'Approved')
                    ->where('leave_type_id', $leaveType->id)
                    ->where('start_date', '>=', $periodStart)
                    ->where('start_date', '<=', $periodEnd);
                $leaveInfo->taken_days = (float) $takenQuery->sum('total_leave_days');
                $leaveInfo->taken_count = $takenQuery->count();

                // Leaves before this period (for opening balance)
                $beforeTaken = Leave::where('employee_id', $employee_id)
                    ->where('status', 'Approved')
                    ->where('leave_type_id', $leaveType->id)
                    ->where('start_date', '<', $periodStart)
                    ->sum('total_leave_days');

                $leaveInfo->opening_balance = max(0, $leaveType->days - (float) $beforeTaken);
                $leaveInfo->remaining = max(0, $leaveInfo->opening_balance - $leaveInfo->taken_days);
                $leaveInfo->is_carry_forward = (bool) $leaveType->is_carry_forward;

                // Carry Forward: only if leave type allows, capped at max_carry_forward
                if ($leaveType->is_carry_forward && $leaveInfo->remaining > 0) {
                    $maxCF = $leaveType->max_carry_forward > 0 ? (float) $leaveType->max_carry_forward : $leaveInfo->remaining;
                    $leaveInfo->carry_forward = min($leaveInfo->remaining, $maxCF);
                } else {
                    $leaveInfo->carry_forward = 0;
                }

                // Closing balance = carry forward (what actually carries to next period)
                // For non-carry-forward leaves, remaining days lapse at period end
                $leaveInfo->closing_balance = $leaveInfo->carry_forward;
                $leaveInfo->lapsed = $leaveInfo->remaining - $leaveInfo->carry_forward;

                // Leave Encashment: remaining balance after carry forward (only if encashable)
                $leaveInfo->encashable_days = 0;
                $leaveInfo->encash_amount = 0;
                $leaveInfo->encash_basis = $leaveType->encash_basis ?? 'basic';
                if ($leaveType->is_encashable && $leaveInfo->remaining > 0) {
                    $leaveInfo->encashable_days = $leaveInfo->remaining - $leaveInfo->carry_forward;
                    // Encashed days are not lapsed — reduce lapsed count
                    $leaveInfo->lapsed = max(0, $leaveInfo->lapsed - $leaveInfo->encashable_days);

                    if ($leaveType->encash_rate_per_day > 0) {
                        // Fixed rate per day
                        $leaveInfo->encash_amount = round($leaveInfo->encashable_days * $leaveType->encash_rate_per_day, 2);
                        $leaveInfo->encash_basis = 'fixed';
                    } else {
                        // Calculate: days × (component / 12 / 26)
                        $empSalary = \DB::table('employee_salaries')->where('employee_id', $employee_id)->first();
                        $ctc = $empSalary ? (float) $empSalary->ctc : 0;
                        $basicPct = $empSalary ? (float) ($empSalary->basic_percentage ?? 50) : 50;

                        $basis = $leaveType->encash_basis ?? 'basic';
                        if ($basis === 'basic') {
                            $monthlyComponent = round($ctc * $basicPct / 100 / 12, 2);
                        } elseif ($basis === 'gross') {
                            $monthlyComponent = round($ctc / 12, 2);
                        } else {
                            $monthlyComponent = round($ctc / 12, 2);
                        }

                        $dailyRate = round($monthlyComponent / 26, 2);
                        $leaveInfo->encash_amount = round($leaveInfo->encashable_days * $dailyRate, 2);
                        $leaveInfo->daily_rate = $dailyRate;
                    }
                }

                // Status-specific count
                $statusQuery = Leave::where('employee_id', $employee_id)
                    ->where('status', $status)
                    ->where('leave_type_id', $leaveType->id)
                    ->where('start_date', '>=', $periodStart)
                    ->where('start_date', '<=', $periodEnd);
                $leaveInfo->status_count = $statusQuery->count();
                $leaveInfo->status_days = (float) $statusQuery->sum('total_leave_days');

                $leaves[] = $leaveInfo;
            }

            // Leave detail data
            $leaveData = Leave::where('employee_id', $employee_id)->where('status', $status);
            if ($type == 'yearly') {
                $leaveData->where('start_date', '>=', $periodStart)->where('start_date', '<=', $periodEnd);
            } else {
                $leaveData->where('start_date', '>=', $periodStart)->where('start_date', '<=', $periodEnd);
            }
            $leaveData = $leaveData->orderBy('start_date')->get();

            return view('report.leaveShow', compact('leaves', 'leaveData', 'employee', 'periodLabel', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function accountStatement(Request $request)
    {
        if (\Auth::user()->can('Manage Report')) {
            $accountList = AccountList::where('created_by', \Auth::user()->creatorId())->get()->pluck('account_name', 'id');
            $accountList->prepend('All', '');

            $filterYear['account'] = __('All');
            $filterYear['type']    = __('Income');


            if ($request->type == 'expense') {
                $accountData = Expense::orderBy('id');
                $accounts    = Expense::select('account_lists.id', 'account_lists.account_name')->leftjoin('account_lists', 'expenses.account_id', '=', 'account_lists.id')->groupBy('expenses.account_id')->selectRaw('sum(amount) as total');

                if (!empty($request->start_month) && !empty($request->end_month)) {
                    $start = strtotime($request->start_month);
                    $end   = strtotime($request->end_month);
                } else {
                    $start = strtotime(date('Y-m'));
                    $end   = strtotime(date('Y-m', strtotime("-5 month")));
                }

                $currentdate = $start;

                while ($currentdate <= $end) {
                    $data['month'] = date('m', $currentdate);
                    $data['year']  = date('Y', $currentdate);

                    $accountData->Orwhere(
                        function ($query) use ($data) {
                            $query->whereMonth('date', $data['month'])->whereYear('date', $data['year']);
                        }
                    );

                    $accounts->Orwhere(
                        function ($query) use ($data) {
                            $query->whereMonth('date', $data['month'])->whereYear('date', $data['year']);
                        }
                    );

                    $currentdate = strtotime('+1 month', $currentdate);
                }

                $filterYear['startDateRange'] = date('M-Y', $start);
                $filterYear['endDateRange']   = date('M-Y', $end);

                if (!empty($request->account)) {
                    $accountData->where('account_id', $request->account);
                    $accounts->where('account_lists.id', $request->account);

                    $filterYear['account'] = !empty(AccountList::find($request->account)) ? Department::find($request->account)->account_name : '';
                }

                $accounts->where('expenses.created_by', \Auth::user()->creatorId());

                $filterYear['type'] = __('Expense');
            } else {
                $accountData = Deposit::orderBy('id');
                $accounts    = Deposit::select('account_lists.id', 'account_lists.account_name')->leftjoin('account_lists', 'deposits.account_id', '=', 'account_lists.id')->groupBy('deposits.account_id')->selectRaw('sum(amount) as total');

                if (!empty($request->start_month) && !empty($request->end_month)) {

                    $start = strtotime($request->start_month);
                    $end   = strtotime($request->end_month);
                } else {
                    $start = strtotime(date('Y-m'));
                    $end   = strtotime(date('Y-m', strtotime("-5 month")));
                }

                $currentdate = $start;

                while ($currentdate <= $end) {
                    $data['month'] = date('m', $currentdate);
                    $data['year']  = date('Y', $currentdate);

                    $accountData->Orwhere(
                        function ($query) use ($data) {
                            $query->whereMonth('date', $data['month'])->whereYear('date', $data['year']);
                        }
                    );
                    $currentdate = strtotime('+1 month', $currentdate);

                    $accounts->Orwhere(
                        function ($query) use ($data) {
                            $query->whereMonth('date', $data['month'])->whereYear('date', $data['year']);
                        }
                    );
                    $currentdate = strtotime('+1 month', $currentdate);
                }

                $filterYear['startDateRange'] = date('M-Y', $start);
                $filterYear['endDateRange']   = date('M-Y', $end);

                if (!empty($request->account)) {
                    $accountData->where('account_id', $request->account);
                    $accounts->where('account_lists.id', $request->account);

                    $filterYear['account'] = !empty(AccountList::find($request->account)) ? Department::find($request->account)->account_name : '';
                }
                $accounts->where('deposits.created_by', \Auth::user()->creatorId());
            }

            $accountData->where('created_by', \Auth::user()->creatorId());
            $accountData = $accountData->get();

            $accounts = $accounts->get();


            return view('report.account_statement', compact('accountData', 'accountList', 'accounts', 'filterYear'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function payroll(Request $request)
    {
        if (\Auth::user()->can('Manage Report')) {
            $branch = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $branch->prepend('All', '');

            $department = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $department->prepend('All', '');

            $filterYear['branch']     = __('All');
            $filterYear['department'] = __('All');
            $filterYear['type']       = __('Monthly');

            $payslips = PaySlip::select('pay_slips.*', 'employees.name')->leftjoin('employees', 'pay_slips.employee_id', '=', 'employees.id')->where('pay_slips.created_by', \Auth::user()->creatorId());


            if ($request->type == 'monthly' && !empty($request->month)) {

                $payslips->where('salary_month', $request->month);

                $filterYear['dateYearRange'] = date('M-Y', strtotime($request->month));
                $filterYear['type']          = __('Monthly');
            } elseif (!isset($request->type)) {
                $month = date('Y-m');

                $payslips->where('salary_month', $month);

                $filterYear['dateYearRange'] = date('M-Y', strtotime($month));
                $filterYear['type']          = __('Monthly');
            }

            if ($request->type == 'yearly' && !empty($request->year)) {
                $startMonth = $request->year . '-01';
                $endMonth   = $request->year . '-12';
                $payslips->where('salary_month', '>=', $startMonth)->where('salary_month', '<=', $endMonth);

                $filterYear['dateYearRange'] = $request->year;
                $filterYear['type']          = __('Yearly');
            }

            if (!empty($request->branch)) {
                $payslips->where('employees.branch_id', $request->branch);

                $filterYear['branch'] = !empty(Branch::find($request->branch)) ? Branch::find($request->branch)->name : '';
            }

            if (!empty($request->department)) {
                $payslips->where('employees.department_id', $request->department);

                $filterYear['department'] = !empty(Department::find($request->department)) ? Department::find($request->department)->name : '';
            }

            $payslips = $payslips->get();

            $totalBasicSalary = $totalNetSalary = $totalAllowance = $totalCommision = $totalLoan = $totalSaturationDeduction = $totalOtherPayment = $totalOverTime = 0;

            foreach ($payslips as $payslip) {
                $totalBasicSalary += $payslip->basic_salary;
                $totalNetSalary   += $payslip->net_payble;

                $allowances = json_decode($payslip->allowance);
                foreach ($allowances as $allowance) {
                    $totalAllowance += $allowance->amount;
                }

                $commisions = json_decode($payslip->commission);
                foreach ($commisions as $commision) {
                    $totalCommision += $commision->amount;
                }

                $loans = json_decode($payslip->loan);
                foreach ($loans as $loan) {
                    $totalLoan += $loan->amount;
                }

                $saturationDeductions = json_decode($payslip->saturation_deduction);
                foreach ($saturationDeductions as $saturationDeduction) {
                    $totalSaturationDeduction += $saturationDeduction->amount;
                }

                $otherPayments = json_decode($payslip->other_payment);
                foreach ($otherPayments as $otherPayment) {
                    $totalOtherPayment += $otherPayment->amount;
                }

                $overtimes = json_decode($payslip->overtime);
                foreach ($overtimes as $overtime) {
                    $days  = $overtime->number_of_days;
                    $hours = $overtime->hours;
                    $rate  = $overtime->rate;

                    $totalOverTime += ($rate * $hours) * $days;
                }
            }

            $filterData['totalBasicSalary']         = $totalBasicSalary;
            $filterData['totalNetSalary']           = $totalNetSalary;
            $filterData['totalAllowance']           = $totalAllowance;
            $filterData['totalCommision']           = $totalCommision;
            $filterData['totalLoan']                = $totalLoan;
            $filterData['totalSaturationDeduction'] = $totalSaturationDeduction;
            $filterData['totalOtherPayment']        = $totalOtherPayment;
            $filterData['totalOverTime']            = $totalOverTime;


            $starting_year = date('Y', strtotime('-5 year'));
            $ending_year   = date('Y', strtotime('+5 year'));

            $filterYear['starting_year'] = $starting_year;
            $filterYear['ending_year']   = $ending_year;

            return view('report.payroll', compact('payslips', 'filterData', 'branch', 'department', 'filterYear'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * HR reimbursement payout report — approved claims only.
     */
    public function reimbursement(Request $request)
    {
        if (!\Auth::user()->can('Manage Report')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $branch = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $branch->prepend('All', '');

        $department = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $department->prepend('All', '');

        $filterYear['branch']     = __('All');
        $filterYear['department'] = __('All');
        $filterYear['type']       = __('Monthly');

        $claims = ReimbursementClaim::query()
            ->select('reimbursement_claims.*', 'employees.name', 'employees.employee_id as emp_code', 'employees.account_holder_name', 'employees.account_number', 'employees.bank_name', 'employees.bank_identifier_code')
            ->leftJoin('employees', 'reimbursement_claims.employee_id', '=', 'employees.id')
            ->where('reimbursement_claims.created_by', \Auth::user()->creatorId())
            ->whereIn('reimbursement_claims.status', ['approved', 'paid']);

        if ($request->type == 'monthly' && !empty($request->month)) {
            $claims->where('reimbursement_claims.claim_month', $request->month);
            $filterYear['dateYearRange'] = date('M-Y', strtotime($request->month));
            $filterYear['type']          = __('Monthly');
        } elseif (!isset($request->type)) {
            $month = date('Y-m');
            $claims->where('reimbursement_claims.claim_month', $month);
            $filterYear['dateYearRange'] = date('M-Y', strtotime($month));
            $filterYear['type']          = __('Monthly');
        }

        if ($request->type == 'yearly' && !empty($request->year)) {
            $startMonth = $request->year . '-01';
            $endMonth   = $request->year . '-12';
            $claims->where('reimbursement_claims.claim_month', '>=', $startMonth)
                ->where('reimbursement_claims.claim_month', '<=', $endMonth);
            $filterYear['dateYearRange'] = $request->year;
            $filterYear['type']          = __('Yearly');
        }

        if (!empty($request->branch)) {
            $claims->where('employees.branch_id', $request->branch);
            $filterYear['branch'] = !empty(Branch::find($request->branch)) ? Branch::find($request->branch)->name : '';
        }

        $departmentId = $request->department_id ?? $request->department;
        if (!empty($departmentId)) {
            $claims->where('employees.department_id', $departmentId);
            $filterYear['department'] = !empty(Department::find($departmentId)) ? Department::find($departmentId)->name : '';
        }

        $claims = $claims->orderBy('reimbursement_claims.claim_month')->orderBy('employees.name')->get();

        $filterData['totalAmount'] = $claims->sum('amount');
        $filterData['totalClaims'] = $claims->count();

        $starting_year = date('Y', strtotime('-5 year'));
        $ending_year   = date('Y', strtotime('+5 year'));

        $filterYear['starting_year'] = $starting_year;
        $filterYear['ending_year']   = $ending_year;

        return view('report.reimbursement', compact('claims', 'filterData', 'branch', 'department', 'filterYear'));
    }

    public function ReimbursementReportExport(Request $request)
    {
        if (!\Auth::user()->can('Manage Report')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $type = $request->get('type', 'monthly');
        $data = [
            'type' => $type,
            'month' => $request->get('month', date('Y-m')),
            'year' => $request->get('year', date('Y')),
            'branch' => $request->get('branch', 0),
            'department' => $request->get('department', $request->get('department_id', 0)),
        ];

        $label = $type === 'yearly'
            ? ('Year_' . ($data['year'] ?: date('Y')))
            : ('Month_' . str_replace('-', '', $data['month'] ?: date('Y-m')));

        $name = 'Reimbursement_Report_' . $label . '_' . date('Ymd_His');

        return \Excel::download(new ReimbursementExport($data), $name . '.xlsx');
    }

    public function monthlyAttendance(Request $request)
    {
        if (\Auth::user()->can('Manage Report')) {

            $branch = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $branch->prepend('All', '');

            $department = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $department->prepend('All', '');

            $data['branch']     = __('All');
            $data['department'] = __('All');

            $isVicRegister = TenantHost::isVicPortal();

            $employees = $isVicRegister
                ? Employee::with(['branch', 'department', 'designation'])
                : Employee::select('id', 'name');
            if (!empty($request->employee_id) && $request->employee_id[0] != 0) {
                $employees->whereIn('id', $request->employee_id);
            }

            $employees = $employees->where('created_by', \Auth::user()->creatorId());

            if (!empty($request->branch_id)) {
                $employees->where('branch_id', $request->branch_id);
                $data['branch'] = !empty(Branch::find($request->branch_id)) ? Branch::find($request->branch_id)->name : '';
            }

            if (!empty($request->department)) {
                $employees->where('department_id', $request->department);
                $data['department'] = !empty(Department::find($request->department)) ? Department::find($request->department)->name : '';
            }

            if (!empty($request->employees)) {
                $employees->where('employee_id', $request->employees);
                $data['employees'] = !empty(Employee::find($request->employees)) ? Employee::find($request->employees)->name : '';
            }

            $employeeModels = $isVicRegister ? (clone $employees)->orderBy('name')->get() : collect();
            $employees = $employees->get()->pluck('name', 'id');

            // All employees for dropdown (unfiltered)
            $allEmployees = Employee::where('created_by', \Auth::user()->creatorId())->orderBy('name')->get()->pluck('name', 'id');

            if (!empty($request->month)) {
                $currentdate = strtotime($request->month);
                $month       = date('m', $currentdate);
                $year        = date('Y', $currentdate);
                $curMonth    = date('M-Y', strtotime($request->month));
            } else {
                $month    = date('m');
                $year     = date('Y');
                $curMonth = date('M-Y', strtotime($year . '-' . $month));
            }


            //            $num_of_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $num_of_days = date('t', mktime(0, 0, 0, $month, 1, $year));
            for ($i = 1; $i <= $num_of_days; $i++) {
                $dates[] = str_pad($i, 2, '0', STR_PAD_LEFT);
            }

            // Pre-load holidays for this month
            $holidayDates = [];
            if (class_exists(\App\Models\Holiday::class)) {
                $holidays = \App\Models\Holiday::where('created_by', \Auth::user()->creatorId())
                    ->where(function($q) use ($year, $month) {
                        $q->whereMonth('start_date', $month)->whereYear('start_date', $year);
                    })->get();
                foreach ($holidays as $h) {
                    $s = \Carbon\Carbon::parse($h->start_date);
                    $e = $h->end_date ? \Carbon\Carbon::parse($h->end_date) : $s;
                    while ($s->lte($e)) {
                        if ($s->month == $month && $s->year == $year) {
                            $holidayDates[] = $s->format('d');
                        }
                        $s->addDay();
                    }
                }
            }

            // Pre-load approved leaves for this month per employee
            $approvedLeaves = \App\Models\Leave::where('status', 'Approved')
                ->where(function($q) use ($year, $month) {
                    $q->where(function($q2) use ($year, $month) {
                        $q2->whereMonth('start_date', $month)->whereYear('start_date', $year);
                    })->orWhere(function($q2) use ($year, $month) {
                        $q2->whereMonth('end_date', $month)->whereYear('end_date', $year);
                    });
                })->get();

            // Build per-employee leave date set
            $leaveDatesMap = [];
            foreach ($approvedLeaves as $lv) {
                $s = \Carbon\Carbon::parse($lv->start_date);
                $e = \Carbon\Carbon::parse($lv->end_date);
                while ($s->lte($e)) {
                    if ($s->month == $month && $s->year == $year) {
                        $leaveDatesMap[$lv->employee_id][] = $s->format('d');
                    }
                    $s->addDay();
                }
            }

            $employeesAttendance = [];
            $totalPresent        = $totalLeave = $totalEarlyLeave = 0;
            $ovetimeHours        = $overtimeMins = $earlyleaveHours = $earlyleaveMins = $lateHours = $lateMins = 0;
            foreach ($employees as $id => $employee) {
                $attendances['name'] = $employee;
                $empLeaveDates = $leaveDatesMap[$id] ?? [];

                foreach ($dates as $date) {
                    $dateFormat = $year . '-' . $month . '-' . $date;
                    $dayOfWeek = date('w', strtotime($dateFormat));
                    $isSunday = ($dayOfWeek == 0);
                    $isHoliday = in_array($date, $holidayDates);
                    $isOnLeave = in_array($date, $empLeaveDates);

                    if ($dateFormat <= date('Y-m-d')) {
                        if ($isSunday || $isHoliday) {
                            $attendanceStatus[$date] = 'H';
                        } elseif ($isOnLeave) {
                            $attendanceStatus[$date] = 'L';
                            $totalLeave += 1;
                        } else {
                            $employeeAttendance = AttendanceEmployee::where('employee_id', $id)->where('date', $dateFormat)->first();

                            if (!empty($employeeAttendance) && in_array($employeeAttendance->status, ['present', 'Present'])) {
                                $attendanceStatus[$date] = 'P';
                                $totalPresent            += 1;

                                if ($employeeAttendance->overtime > 0) {
                                    $ovetimeHours += date('h', strtotime($employeeAttendance->overtime));
                                    $overtimeMins += date('i', strtotime($employeeAttendance->overtime));
                                }

                                if ($employeeAttendance->early_leaving > 0) {
                                    $earlyleaveHours += date('h', strtotime($employeeAttendance->early_leaving));
                                    $earlyleaveMins  += date('i', strtotime($employeeAttendance->early_leaving));
                                }

                                if ($employeeAttendance->late > 0) {
                                    $lateHours += date('h', strtotime($employeeAttendance->late));
                                    $lateMins  += date('i', strtotime($employeeAttendance->late));
                                }
                            } elseif (!empty($employeeAttendance) && $employeeAttendance->status == 'Leave') {
                                $attendanceStatus[$date] = 'L';
                                $totalLeave              += 1;
                            } else {
                                $attendanceStatus[$date] = 'A';
                                $totalLeave              += 1;
                            }
                        }
                    } else {
                        $attendanceStatus[$date] = '';
                    }
                }
                $attendances['status'] = $attendanceStatus;
                $employeesAttendance[] = $attendances;
            }

            $totalOverTime   = $ovetimeHours + ($overtimeMins / 60);
            $totalEarlyleave = $earlyleaveHours + ($earlyleaveMins / 60);
            $totalLate       = $lateHours + ($lateMins / 60);

            $data['totalOvertime']   = $totalOverTime;
            $data['totalEarlyLeave'] = $totalEarlyleave;
            $data['totalLate']       = $totalLate;
            $data['totalPresent']    = $totalPresent;
            $data['totalLeave']      = $totalLeave;
            $data['curMonth']        = $curMonth;

            $vicRegister = $isVicRegister
                ? $this->buildVicMonthlyRegister($employeeModels, $year, $month, $dates, $holidayDates, $leaveDatesMap)
                : [];
            if ($isVicRegister && !empty($vicRegister['totals'])) {
                $data['totalPresent'] = $vicRegister['totals']['present'];
                $data['totalLeave'] = $vicRegister['totals']['leave'];
                $data['totalOvertime'] = $vicRegister['totals']['overtime_hours'];
                $data['totalEarlyLeave'] = $vicRegister['totals']['early_hours'];
                $data['totalLate'] = $vicRegister['totals']['late_hours'];
            }

            return view('report.monthlyAttendance', compact('employeesAttendance', 'branch', 'department', 'employees', 'allEmployees', 'dates', 'data', 'isVicRegister', 'vicRegister'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function timesheet(Request $request)
    {
        if (\Auth::user()->can('Manage Report')) {
            $branch = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $branch->prepend('All', '');

            $department = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $department->prepend('All', '');

            $filterYear['branch']     = __('All');
            $filterYear['department'] = __('All');

            $timesheets       = TimeSheet::select('time_sheets.*', 'employees.name')->leftjoin('employees', 'time_sheets.employee_id', '=', 'employees.id')->where('time_sheets.created_by', \Auth::user()->creatorId());
            $timesheetFilters = TimeSheet::select('time_sheets.*', 'employees.name')->groupBy('employee_id')->selectRaw('sum(hours) as total')->leftjoin('employees', 'time_sheets.employee_id', '=', 'employees.id')->where('time_sheets.created_by', \Auth::user()->creatorId());

            if (!empty($request->start_date) && !empty($request->end_date)) {
                $timesheets->where('date', '>=', $request->start_date);
                $timesheets->where('date', '<=', $request->end_date);

                $timesheetFilters->where('date', '>=', $request->start_date);
                $timesheetFilters->where('date', '<=', $request->end_date);

                $filterYear['start_date'] = $request->start_date;
                $filterYear['end_date']   = $request->end_date;
            } else {

                $filterYear['start_date'] = date('Y-m-01');
                $filterYear['end_date']   = date('Y-m-t');

                $timesheets->where('date', '>=', $filterYear['start_date']);
                $timesheets->where('date', '<=', $filterYear['end_date']);

                $timesheetFilters->where('date', '>=', $filterYear['start_date']);
                $timesheetFilters->where('date', '<=', $filterYear['end_date']);
            }

            if (!empty($request->branch)) {
                $timesheets->where('branch_id', $request->branch);
                $timesheetFilters->where('branch_id', $request->branch);
                $filterYear['branch'] = !empty(Branch::find($request->branch)) ? Branch::find($request->branch)->name : '';
            }
            if (!empty($request->department)) {
                $timesheets->where('department_id', $request->department);
                $timesheetFilters->where('department_id', $request->department);
                $filterYear['department'] = !empty(Department::find($request->department)) ? Department::find($request->department)->name : '';
            }

            $timesheets = $timesheets->get();

            $timesheetFilters = $timesheetFilters->get();

            $totalHours = 0;
            foreach ($timesheetFilters as $timesheetFilter) {
                $totalHours += $timesheetFilter->hours;
            }
            $filterYear['totalHours']    = $totalHours;
            $filterYear['totalEmployee'] = count($timesheetFilters);

            return view('report.timesheet', compact('timesheets', 'branch', 'department', 'filterYear', 'timesheetFilters'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function LeaveReportExport()
    {
        $name = 'leave_' . date('Y-m-d i:h:s');
        $data = \Excel::download(new LeaveReportExport(), $name . '.xlsx');

        return $data;
    }

    public function AccountStatementReportExport(Request $request)
    {
        $name = 'Account Statement_' . date('Y-m-d i:h:s');
        $data = \Excel::download(new accountstatementExport(), $name . '.xlsx');

        return $data;
    }

    public function PayrollReportExport($month, $branch, $department)
    {
        $data = [];
        $data['branch'] = __('All');
        $data['department'] = __('All');

        if ($branch != 0) {
            $data['branch'] = !empty(Branch::find($branch)) ? Branch::find($branch)->id : '';
        }

        if ($department != 0) {
            $data['department'] = !empty(Department::find($department)) ? Department::find($department)->id : '';
        }
        $data['month'] = $month;
        $name = 'Payroll_' . date('Y-m-d i:h:s');
        $data = \Excel::download(new PayrollExport($data), $name . '.xlsx');

        return $data;
    }

    public function exportTimeshhetReport(Request $request)
    {
        $name = 'Timesheet_' . date('Y-m-d i:h:s');
        $data = \Excel::download(new TimesheetReportExport(), $name . '.xlsx');

        return $data;
    }

    public function exportCsv($filter_month, $branch, $department, $employee)
    {
        $data['branch'] = __('All');
        $data['department'] = __('All');

        $employees = Employee::select('id', 'name')->where('created_by', \Auth::user()->creatorId());
        if ($branch != 0) {
            $employees->where('branch_id', $branch);
            $data['branch'] = !empty(Branch::find($branch)) ? Branch::find($branch)->name : '';
        }

        if ($department != 0) {
            $employees->where('department_id', $department);
            $data['department'] = !empty(Department::find($department)) ? Department::find($department)->name : '';
        }
        if ($employee != 0) {
            $employeeIds = explode(',', $employee);
            $emp = Employee::whereIn('id', $employeeIds);
        } else {
            $emp = Employee::where('created_by', \Auth::user()->creatorId());
        }

        $employees = $emp->get()->pluck('name', 'id');

        $currentdate = strtotime($filter_month);
        $month       = date('m', $currentdate);
        $year        = date('Y', $currentdate);
        $data['curMonth']    = date('M-Y', strtotime($filter_month));


        $fileName = $data['branch'] . ' ' . __('Branch') . ' ' . $data['curMonth'] . ' ' . __('Attendance Report of') . ' ' . $data['department'] . ' ' . __('Department') . ' ' . '.csv';

        $employeesAttendance = [];
        $num_of_days = date('t', mktime(0, 0, 0, $month, 1, $year));
        for ($i = 1; $i <= $num_of_days; $i++) {
            $dates[] = str_pad($i, 2, '0', STR_PAD_LEFT);
        }

        foreach ($employees as $id => $employee) {
            $attendances['name'] = $employee;

            foreach ($dates as $date) {
                $dateFormat = $year . '-' . $month . '-' . $date;

                if ($dateFormat <= date('Y-m-d')) {
                    $employeeAttendance = AttendanceEmployee::where('employee_id', $id)->where('date', $dateFormat)->first();

                    if (!empty($employeeAttendance) && $employeeAttendance->status == 'Present') {
                        $attendanceStatus[$date] = 'P';
                    } elseif (!empty($employeeAttendance) && $employeeAttendance->status == 'Leave') {
                        $attendanceStatus[$date] = 'A';
                    } else {
                        $attendanceStatus[$date] = '-';
                    }
                } else {
                    $attendanceStatus[$date] = '-';
                }
                $attendances[$date] = $attendanceStatus[$date];
            }

            $employeesAttendance[] = $attendances;
        }

        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0",
        );
        $emp = array(
            'employee',
        );

        $columns = array_merge($emp, $dates);

        $callback = function () use ($employeesAttendance, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($employeesAttendance as $attendance) {
                fputcsv($file, str_replace('"', '', array_values($attendance)));
            }


            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importVicMonthlyAttendance(Request $request)
    {
        if (!TenantHost::isVicPortal($request->getHost())) {
            return redirect()->back()->with('error', __('This import is only available on the Vimal Industrial portal.'));
        }
        if (!\Auth::user()->can('Manage Report')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        if (!in_array(\Auth::user()->type, ['super admin', 'company'], true) && !\Auth::user()->can('Create Attendance')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            $result = (new VicConsolidateAttendanceImport())->import($request->file('file'), (int) \Auth::user()->creatorId());
            $msg = __('Register imported.') . " Employees: {$result['employees']}, Created: {$result['created']}, Updated: {$result['updated']}, Skipped: {$result['skipped']}";
            if (!empty($result['errors'])) {
                $msg .= ' | ' . implode('; ', array_slice($result['errors'], 0, 5));
            }

            return redirect()
                ->route('report.monthly.attendance', ['month' => $result['month']])
                ->with($result['created'] + $result['updated'] > 0 ? 'success' : 'error', $msg);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int,\App\Models\Employee>  $employeeModels
     * @param  array<int,string>  $dates
     * @param  array<int,string>  $holidayDates
     * @param  array<int,array<int,string>>  $leaveDatesMap
     * @return array{rows:array<int,array<string,mixed>>,day_headers:array<int,string>,totals:array<string,float|int>}
     */
    protected function buildVicMonthlyRegister($employeeModels, $year, $month, array $dates, array $holidayDates, array $leaveDatesMap): array
    {
        $monthStart = sprintf('%04d-%02d-01', (int) $year, (int) $month);
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        $ids = $employeeModels->pluck('id')->all();
        $allAttendance = $ids === []
            ? collect()
            : AttendanceEmployee::whereIn('employee_id', $ids)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->get()
                ->groupBy('employee_id');

        $defaultIn = Utility::getValByName('company_start_time') ?: '10:30:00';
        $defaultOut = Utility::getValByName('company_end_time') ?: '19:00:00';
        $shiftLabel = $this->formatVicShiftLabel($defaultIn, $defaultOut);

        $dayHeaders = [];
        foreach ($dates as $day) {
            $dayHeaders[] = sprintf('%02d-%02d-%04d', (int) $day, (int) $month, (int) $year);
        }

        $totals = [
            'present' => 0,
            'leave' => 0,
            'overtime_hours' => 0.0,
            'early_hours' => 0.0,
            'late_hours' => 0.0,
        ];
        $rows = [];
        $sr = 0;

        foreach ($employeeModels as $employee) {
            $sr++;
            $records = $allAttendance->get($employee->id, collect())->keyBy('date');
            $leaveDays = $leaveDatesMap[$employee->id] ?? [];
            $days = [];
            $counts = [
                'present' => 0,
                'absent' => 0,
                'half_day' => 0,
                'miss_punch' => 0,
                'week_off' => 0,
                'holiday' => 0,
                'approved_leave' => 0,
                'pending_leave' => 0,
                'approved_outduty' => 0,
                'pending_outduty' => 0,
            ];

            foreach ($dates as $day) {
                $dateYmd = sprintf('%04d-%02d-%s', (int) $year, (int) $month, $day);
                $rec = $records->get($dateYmd);
                $isSunday = ((int) date('w', strtotime($dateYmd))) === 0;
                $isHoliday = in_array($day, $holidayDates, true);
                $isOnLeave = in_array($day, $leaveDays, true);

                $code = $this->vicStatusCode($rec, $dateYmd, $isSunday, $isHoliday, $isOnLeave);
                $clockIn = ($rec && $rec->clock_in && $rec->clock_in !== '00:00:00') ? $rec->clock_in : '';
                $clockOut = ($rec && $rec->clock_out && $rec->clock_out !== '00:00:00') ? $rec->clock_out : '';
                $inLabel = $clockIn ? (date('d-m-Y', strtotime($dateYmd)) . ' ' . $clockIn) : '';
                $outLabel = $clockOut ? (date('d-m-Y', strtotime($dateYmd)) . ' ' . $clockOut) : '';
                $hours = ($clockIn && $clockOut) ? $this->vicWorkedHours($clockIn, $clockOut) : '';
                $ot = $this->vicOvertimeLabel($rec->overtime ?? null);
                $shift = in_array($code, ['P', 'HFD', 'P+LC', 'MP'], true) ? $shiftLabel : '';

                $days[] = [
                    'code' => $code,
                    'shift' => $shift,
                    'in' => $inLabel,
                    'out' => $outLabel,
                    'hours' => $hours,
                    'ot' => $ot === '' ? '0' : $ot,
                ];

                if ($code === 'P' || $code === 'P+LC') {
                    $counts['present']++;
                    $totals['present']++;
                    if ($code === 'P+LC') {
                        $totals['late_hours'] += $this->vicHoursValue($rec->late ?? null);
                    }
                } elseif ($code === 'HFD') {
                    $counts['half_day']++;
                    $counts['present'] += 0.5;
                    $counts['absent'] += 0.5;
                    $totals['present'] += 0.5;
                } elseif ($code === 'A') {
                    $counts['absent']++;
                } elseif ($code === 'MP') {
                    $counts['miss_punch']++;
                } elseif ($code === 'WO') {
                    $counts['week_off']++;
                } elseif ($code === 'H') {
                    $counts['holiday']++;
                } elseif (in_array($code, ['PL', 'L.W.P.', 'L.W.P. Applied'], true)) {
                    $counts['approved_leave']++;
                    $totals['leave']++;
                }

                if ($rec) {
                    $totals['overtime_hours'] += $this->vicHoursValue($rec->overtime ?? null);
                    $totals['early_hours'] += $this->vicHoursValue($rec->early_leaving ?? null);
                }
            }

            $rows[] = [
                'sr' => $sr,
                'code' => $employee->employee_id,
                'name' => $employee->name,
                'number' => $employee->phone,
                'doj' => $employee->company_doj ? date('d-m-Y', strtotime($employee->company_doj)) : '',
                'branch' => $employee->branch->name ?? '',
                'department' => $employee->department->name ?? '',
                'designation' => $employee->designation->name ?? '',
                'counts' => $counts,
                'days' => $days,
            ];
        }

        return [
            'rows' => $rows,
            'day_headers' => $dayHeaders,
            'totals' => $totals,
        ];
    }

    protected function vicStatusCode($rec, string $dateYmd, bool $isSunday, bool $isHoliday, bool $isOnLeave): string
    {
        if ($rec) {
            $status = strtolower(trim((string) $rec->status));
            if ($status === 'week off') {
                return 'WO';
            }
            if ($status === 'miss punch') {
                return 'MP';
            }
            if ($status === 'lwp') {
                return 'L.W.P.';
            }
            if ($status === 'half day') {
                return 'HFD';
            }
            if ($status === 'leave') {
                return 'PL';
            }
            if ($status === 'absent') {
                return 'A';
            }
            if (in_array($status, ['present', 'p'], true)) {
                return !empty($rec->late_mark) ? 'P+LC' : 'P';
            }
        }

        if ($dateYmd > date('Y-m-d')) {
            return '';
        }
        if ($isSunday) {
            return 'WO';
        }
        if ($isHoliday) {
            return 'H';
        }
        if ($isOnLeave) {
            return 'PL';
        }
        if ($rec) {
            return 'A';
        }

        return '-';
    }

    protected function formatVicShiftLabel(string $start, string $end): string
    {
        $startTs = strtotime($start);
        $endTs = strtotime($end);
        if (!$startTs || !$endTs) {
            return '';
        }

        return date('g:i A', $startTs) . ' - ' . date('g:i A', $endTs);
    }

    protected function vicWorkedHours(string $clockIn, string $clockOut): string
    {
        $in = strtotime($clockIn);
        $out = strtotime($clockOut);
        if (!$in || !$out || $out <= $in) {
            return '';
        }
        $mins = (int) floor(($out - $in) / 60);

        return sprintf('%02d:%02d', intdiv($mins, 60), $mins % 60);
    }

    protected function vicOvertimeLabel($value): string
    {
        if (empty($value) || $value === '00:00:00' || $value === '0') {
            return '0';
        }
        if (is_numeric($value) && (float) $value <= 0) {
            return '0';
        }
        if (preg_match('/^(\d{1,2}):(\d{2})/', (string) $value, $m)) {
            if ((int) $m[1] === 0 && (int) $m[2] === 0) {
                return '0';
            }

            return $m[1] . ':' . $m[2];
        }

        return (string) $value;
    }

    protected function vicHoursValue($value): float
    {
        if (empty($value) || $value === '00:00:00') {
            return 0;
        }
        if (preg_match('/^(\d{1,2}):(\d{2})/', (string) $value, $m)) {
            return ((int) $m[1]) + (((int) $m[2]) / 60);
        }

        return is_numeric($value) ? (float) $value : 0;
    }

    public function getdepartment(Request $request)
    {
        if ($request->branch_id == 0) {
            $departments = Department::where('created_by', '=', Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
        } else {
            $departments = Department::where('created_by', '=', Auth::user()->creatorId())->where('branch_id', $request->branch_id)->get()->pluck('name', 'id')->toArray();
        }
        return response()->json($departments);
    }

    public function getemployee(Request $request)
    {
        if (!$request->department_id) {
            $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
        } else {
            $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->where('department_id', $request->department_id)->get()->pluck('name', 'id')->toArray();
        }

        return response()->json($employees);
    }
}
