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

    public function leave(Request $request)
    {
        if (\Auth::user()->can('Manage Report')) {

            $branch = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $branch->prepend('All', '');

            $department = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $department->prepend('All', '');

            $filterYear['branch']        = __('All');
            $filterYear['department']    = __('All');
            $filterYear['type']          = __('Monthly');
            $filterYear['dateYearRange'] = date('M-Y');
            $employees                   = Employee::where('created_by', \Auth::user()->creatorId());
            if (!empty($request->branch)) {
                $employees->where('branch_id', $request->branch);
                $filterYear['branch'] = !empty(Branch::find($request->branch)) ? Branch::find($request->branch)->name : '';
            }
            if (!empty($request->department)) {
                $employees->where('department_id', $request->department);
                $filterYear['department'] = !empty(Department::find($request->department)) ? Department::find($request->department)->name : '';
            }

            $employees = $employees->get();

            $leaves        = [];
            $totalApproved = $totalReject = $totalPending = 0;
            foreach ($employees as $employee) {

                $employeeLeave['id']          = $employee->id;
                $employeeLeave['employee_id'] = $employee->employee_id;
                $employeeLeave['employee']    = $employee->name;

                $approved = Leave::where('employee_id', $employee->id)->where('status', 'Approved');
                $reject   = Leave::where('employee_id', $employee->id)->where('status', 'Reject');
                $pending  = Leave::where('employee_id', $employee->id)->where('status', 'Pending');

                // Attendance module: count days marked as 'Leave' in attendance_employees
                $attLeave = AttendanceEmployee::where('employee_id', $employee->id)->where('status', 'Leave');

                if ($request->type == 'monthly' && !empty($request->month)) {
                    $month = date('m', strtotime($request->month));
                    $year  = date('Y', strtotime($request->month));

                    $approved->whereMonth('start_date', $month)->whereYear('start_date', $year);
                    $reject->whereMonth('start_date', $month)->whereYear('start_date', $year);
                    $pending->whereMonth('start_date', $month)->whereYear('start_date', $year);
                    $attLeave->whereMonth('date', $month)->whereYear('date', $year);

                    $filterYear['dateYearRange'] = date('M-Y', strtotime($request->month));
                    $filterYear['type']          = __('Monthly');
                } elseif (!isset($request->type)) {
                    $month     = date('m');
                    $year      = date('Y');
                    $monthYear = date('Y-m');

                    $approved->whereMonth('start_date', $month)->whereYear('start_date', $year);
                    $reject->whereMonth('start_date', $month)->whereYear('start_date', $year);
                    $pending->whereMonth('start_date', $month)->whereYear('start_date', $year);
                    $attLeave->whereMonth('date', $month)->whereYear('date', $year);

                    $filterYear['dateYearRange'] = date('M-Y', strtotime($monthYear));
                    $filterYear['type']          = __('Monthly');
                }

                if ($request->type == 'yearly' && !empty($request->year)) {
                    $approved->whereYear('start_date', $request->year);
                    $reject->whereYear('start_date', $request->year);
                    $pending->whereYear('start_date', $request->year);
                    $attLeave->whereYear('date', $request->year);

                    $filterYear['dateYearRange'] = $request->year;
                    $filterYear['type']          = __('Yearly');
                }

                $approved = $approved->count();
                $reject   = $reject->count();
                $pending  = $pending->count();
                $attLeaveCount = $attLeave->count();

                // Also count total leave days from approved leaves (sum of total_leave_days)
                $approvedDays = Leave::where('employee_id', $employee->id)->where('status', 'Approved');
                if ($request->type == 'monthly' && !empty($request->month)) {
                    $approvedDays->whereMonth('start_date', $month)->whereYear('start_date', $year);
                } elseif ($request->type == 'yearly' && !empty($request->year)) {
                    $approvedDays->whereYear('start_date', $request->year);
                } else {
                    $approvedDays->whereMonth('start_date', date('m'))->whereYear('start_date', date('Y'));
                }
                $totalDays = $approvedDays->sum('total_leave_days');

                $totalApproved += $approved;
                $totalReject   += $reject;
                $totalPending  += $pending;

                $employeeLeave['approved']    = $approved;
                $employeeLeave['reject']      = $reject;
                $employeeLeave['pending']     = $pending;
                $employeeLeave['total_days']  = $totalDays;
                $employeeLeave['att_leave']   = $attLeaveCount;

                // Compute per-employee leave summary (opening, remaining, CF, lapsed, encash)
                $leaveTypes = LeaveType::where('created_by', \Auth::user()->creatorId())->get();
                // Determine period
                if ($request->type == 'yearly' && !empty($request->year)) {
                    $pStart = $request->year . '-04-01';
                    $pEnd   = ($request->year + 1) . '-03-31';
                } elseif ($request->type == 'monthly' && !empty($request->month)) {
                    $pStart = date('Y-m-01', strtotime($request->month));
                    $pEnd   = date('Y-m-t', strtotime($request->month));
                } else {
                    $pStart = date('Y-m-01');
                    $pEnd   = date('Y-m-t');
                }

                $empSalary = \DB::table('employee_salaries')->where('employee_id', $employee->id)->first();
                $sumOpening = $sumAvailed = $sumRemaining = $sumCF = $sumLapsed = $sumEncashDays = $sumEncashAmt = 0;
                foreach ($leaveTypes as $lt) {
                    $beforeTaken = (float) Leave::where('employee_id', $employee->id)->where('status', 'Approved')
                        ->where('leave_type_id', $lt->id)->where('start_date', '<', $pStart)->sum('total_leave_days');
                    $opening = max(0, $lt->days - $beforeTaken);

                    $availed = (float) Leave::where('employee_id', $employee->id)->where('status', 'Approved')
                        ->where('leave_type_id', $lt->id)
                        ->where('start_date', '>=', $pStart)->where('start_date', '<=', $pEnd)->sum('total_leave_days');

                    $remaining = max(0, $opening - $availed);

                    $cf = 0;
                    if ($lt->is_carry_forward && $remaining > 0) {
                        $maxCF = $lt->max_carry_forward > 0 ? (float) $lt->max_carry_forward : $remaining;
                        $cf = min($remaining, $maxCF);
                    }

                    $encashDays = 0; $encashAmt = 0;
                    if ($lt->is_encashable && $remaining > $cf) {
                        $encashDays = $remaining - $cf;
                        $ctc = $empSalary ? (float) $empSalary->ctc : 0;
                        $basicPct = $empSalary ? (float) ($empSalary->basic_percentage ?? 50) : 50;
                        $basis = $lt->encash_basis ?? 'basic';
                        $monthlyComp = ($basis === 'gross') ? round($ctc / 12, 2) : round($ctc * $basicPct / 100 / 12, 2);
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

                $employeeLeave['opening']      = $sumOpening;
                $employeeLeave['availed']       = $sumAvailed;
                $employeeLeave['remaining']     = $sumRemaining;
                $employeeLeave['carry_forward'] = $sumCF;
                $employeeLeave['lapsed']        = $sumLapsed;
                $employeeLeave['encash_days']   = $sumEncashDays;
                $employeeLeave['encash_amount'] = $sumEncashAmt;

                $leaves[] = $employeeLeave;
            }

            $starting_year = date('Y', strtotime('-5 year'));
            $ending_year   = date('Y', strtotime('+5 year'));

            $filterYear['starting_year'] = $starting_year;
            $filterYear['ending_year']   = $ending_year;

            $filter['totalApproved'] = $totalApproved;
            $filter['totalReject']   = $totalReject;
            $filter['totalPending']  = $totalPending;

            return view('report.leave', compact('department', 'branch', 'leaves', 'filterYear', 'filter'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function leaveExport(Request $request)
    {
        if (!\Auth::user()->can('Manage Report')) abort(403);

        $cid = \Auth::user()->creatorId();
        $employees = Employee::where('created_by', $cid);
        if (!empty($request->branch)) $employees->where('branch_id', $request->branch);
        if (!empty($request->department)) $employees->where('department_id', $request->department);
        $employees = $employees->get();

        $leaveTypes = LeaveType::where('created_by', $cid)->get();

        if ($request->type == 'yearly' && !empty($request->year)) {
            $pStart = $request->year . '-04-01';
            $pEnd   = ($request->year + 1) . '-03-31';
            $label  = 'FY-' . $request->year . '-' . ($request->year + 1);
        } elseif ($request->type == 'monthly' && !empty($request->month)) {
            $pStart = date('Y-m-01', strtotime($request->month));
            $pEnd   = date('Y-m-t', strtotime($request->month));
            $label  = date('M-Y', strtotime($request->month));
        } else {
            $pStart = date('Y-m-01');
            $pEnd   = date('Y-m-t');
            $label  = date('M-Y');
        }

        $filename = 'leave-report-' . $label . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($employees, $leaveTypes, $pStart, $pEnd) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            $header = ['Employee ID', 'Employee', 'Department'];
            foreach ($leaveTypes as $lt) {
                $header[] = $lt->title . ' (Quota)';
                $header[] = $lt->title . ' (Availed)';
                $header[] = $lt->title . ' (Balance)';
            }
            $header = array_merge($header, ['Total Opening', 'Total Availed', 'Total Remaining', 'Carry Forward', 'Lapsed', 'Encash Days', 'Encash Basis', 'Monthly Component', 'Daily Rate', 'Encash Amount', 'Pending']);
            fputcsv($f, $header);

            foreach ($employees as $emp) {
                $deptName = \DB::table('departments')->where('id', $emp->department_id)->value('name') ?? '—';
                $empSalary = \DB::table('employee_salaries')->where('employee_id', $emp->id)->first();
                $row = [$emp->employee_id, $emp->name, $deptName];

                $ctc = $empSalary ? (float) $empSalary->ctc : 0;
                $basicPct = $empSalary ? (float) ($empSalary->basic_percentage ?? 50) : 50;
                $sumOpening = $sumAvailed = $sumRemaining = $sumCF = $sumLapsed = $sumEncashDays = $sumEncashAmt = 0;
                $encashBasis = '—'; $empMonthlyComp = 0; $empDailyRate = 0;

                foreach ($leaveTypes as $lt) {
                    $beforeTaken = (float) Leave::where('employee_id', $emp->id)->where('status', 'Approved')
                        ->where('leave_type_id', $lt->id)->where('start_date', '<', $pStart)->sum('total_leave_days');
                    $opening = max(0, $lt->days - $beforeTaken);
                    $availed = (float) Leave::where('employee_id', $emp->id)->where('status', 'Approved')
                        ->where('leave_type_id', $lt->id)
                        ->where('start_date', '>=', $pStart)->where('start_date', '<=', $pEnd)->sum('total_leave_days');
                    $remaining = max(0, $opening - $availed);

                    $row[] = $lt->days;
                    $row[] = $availed;
                    $row[] = $remaining;

                    $cf = 0;
                    if ($lt->is_carry_forward && $remaining > 0) {
                        $maxCF = $lt->max_carry_forward > 0 ? (float) $lt->max_carry_forward : $remaining;
                        $cf = min($remaining, $maxCF);
                    }
                    $encashDays = 0; $encashAmt = 0;
                    if ($lt->is_encashable && $remaining > $cf) {
                        $encashDays = $remaining - $cf;
                        $basis = $lt->encash_basis ?? 'basic';
                        if ($basis === 'basic') {
                            $monthlyComp = round($ctc * $basicPct / 100 / 12, 2);
                        } elseif ($basis === 'gross') {
                            $monthlyComp = round($ctc / 12, 2);
                        } else {
                            $monthlyComp = round($ctc / 12, 2);
                        }
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

                $pending = Leave::where('employee_id', $emp->id)->where('status', 'Pending')
                    ->where('start_date', '>=', $pStart)->where('start_date', '<=', $pEnd)->count();

                $row = array_merge($row, [$sumOpening, $sumAvailed, $sumRemaining, $sumCF, $sumLapsed, $sumEncashDays, $encashBasis, $empMonthlyComp, $empDailyRate, $sumEncashAmt, $pending]);
                fputcsv($f, $row);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
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
            ->where('reimbursement_claims.status', 'approved');

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

            $employees = Employee::select('id', 'name');
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

            // dd($employeesAttendance, $branch, $department, $employees, $dates, $data);
            return view('report.monthlyAttendance', compact('employeesAttendance', 'branch', 'department', 'employees', 'allEmployees', 'dates', 'data'));
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
