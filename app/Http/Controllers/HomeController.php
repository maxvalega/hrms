<?php

namespace App\Http\Controllers;

use App\Models\AccountList;
use App\Models\Announcement;
use App\Models\AttendanceEmployee;
use App\Models\Employee;
use App\Models\Event;
use App\Models\LandingPageSection;
use App\Models\Meeting;
use App\Models\Job;
use App\Models\Leave as LocalLeave;
use App\Models\Order;
use App\Models\Payees;
use App\Models\Payer;
use App\Models\Plan;
use App\Models\Ticket;
use App\Models\User;
use App\Models\LoginDetail;
use App\Models\Utility;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {

        if (Auth::check()) {
            $user = Auth::user();
            $hasLogoutColumn = Schema::hasColumn('login_details', 'logout_at');
            if ($hasLogoutColumn) {
                $currentLoginDetail = LoginDetail::where('user_id', $user->id)->whereNull('logout_at')->orderBy('date', 'desc')->first();
                $lastLogoutDetail = LoginDetail::where('user_id', $user->id)->whereNotNull('logout_at')->orderBy('logout_at', 'desc')->first();
            } else {
                $currentLoginDetail = LoginDetail::where('user_id', $user->id)->orderBy('date', 'desc')->first();
                $lastLogoutDetail = null;
            }

            // Handle AJAX request for notification counts
            if (request()->ajax() && request()->header('X-Requested-With') === 'XMLHttpRequest') {
                $pendingSubstituteLeaves = collect();
                $pendingSubstituteCount = 0;
                if ($user->type === 'employee') {
                    $employee = Employee::where('user_id', $user->id)->first();
                    if (!empty($employee)) {
                        $pendingSubstituteLeaves = \App\Models\Leave::with(['employees', 'leaveType'])
                            ->where('substitute_employee_id', $employee->id)
                            ->where('substitute_status', 'Pending')
                            ->orderBy('created_at', 'desc')
                            ->get();
                        $pendingSubstituteCount = $pendingSubstituteLeaves->count();
                    }
                }

                $managerPendingLeaves = collect();
                $managerPendingLeaveCount = 0;
                $isManager = false;
                
                if ($user->type !== 'super admin') {
                    $currentEmployee = Employee::where('user_id', $user->id)->first();
                    
                    if ($currentEmployee) {
                        $subordinateIds = Employee::where('reporting_manager_id', $currentEmployee->id)
                            ->pluck('id')
                            ->toArray();
                        
                        if (!empty($subordinateIds)) {
                            $isManager = true;
                            $managerPendingLeaves = \App\Models\Leave::with(['employees', 'leaveType'])
                                ->whereIn('employee_id', $subordinateIds)
                                ->where('status', 'Pending')
                                ->orderBy('created_at', 'desc')
                                ->get();
                            $managerPendingLeaveCount = $managerPendingLeaves->count();
                        }
                    }
                    
                    if (in_array($user->type, ['company', 'hr'])) {
                        $isManager = true;
                        $managerPendingLeaves = \App\Models\Leave::with(['employees', 'leaveType'])
                            ->where('created_by', $user->creatorId())
                            ->where('status', 'Pending')
                            ->orderBy('created_at', 'desc')
                            ->get();
                        $managerPendingLeaveCount = $managerPendingLeaves->count();
                    }
                }

                $exitPendingItems = collect();
                $exitPendingCount = 0;
                if (\Schema::hasTable('exit_resignations')) {
                    $myEmpRow = Employee::where('user_id', $user->id)->first();
                    if ($myEmpRow) {
                        $reportUserIds = Employee::where(function ($q) use ($myEmpRow) {
                                $q->where('reporting_manager_id', $myEmpRow->id)
                                  ->orWhere('hod_id', $myEmpRow->id)
                                  ->orWhere('management_id', $myEmpRow->id);
                            })
                            ->pluck('user_id')
                            ->filter();
                        if ($reportUserIds->isNotEmpty()) {
                            $mgrItems = \App\Models\ExitResignation::with('user')
                                ->where('created_by', $user->creatorId())
                                ->whereIn('user_id', $reportUserIds)
                                ->where('status', 'pending')
                                ->orderByDesc('created_at')
                                ->get();
                            $exitPendingItems = $exitPendingItems->concat($mgrItems);
                        }
                    }

                    if (in_array($user->type, ['company', 'hr', 'super admin'])) {
                        $hrItems = \App\Models\ExitResignation::with('user')
                            ->where('created_by', $user->creatorId())
                            ->where('status', 'manager_approved')
                            ->orderByDesc('created_at')
                            ->get();
                        $exitPendingItems = $exitPendingItems->concat($hrItems);
                    }

                    $exitPendingCount = $exitPendingItems->unique('id')->values()->count();
                }

                $rnSummary = \App\Support\RecruitmentNotifications::summary();

                return response()->json([
                    'notifications' => [
                        'substitute_count' => $pendingSubstituteCount,
                        'leave_count' => $managerPendingLeaveCount,
                        'exit_count' => $exitPendingCount,
                        'recruitment_count' => $rnSummary['total'] ?? 0
                    ]
                ]);
            }

            if ($user->type == 'employee') {
                $emp = Employee::where('user_id', '=', $user->id)->first();

                $date = date("Y-m-d");
                $officeTime = [
                    'startTime' => Utility::getValByName('company_start_time'),
                    'endTime'   => Utility::getValByName('company_end_time'),
                ];
                $employeeAttendance = null;
                $todaySessions = collect();
                $announcements = collect();
                $employees = collect();
                $meetings = collect();
                $arrEvents = [];
                $pendingSubstituteLeaves = collect();

                if (!empty($emp)) {
                    $announcements = Announcement::orderBy('announcements.id', 'desc')->take(5)->leftjoin('announcement_employees', 'announcements.id', '=', 'announcement_employees.announcement_id')->where('announcement_employees.employee_id', '=', $emp->id)->orWhere(
                        function ($q) {
                            $q->where('announcements.department_id', 0)->where('announcements.employee_id', 0);
                        }
                    )->get();

                    $employees = Employee::get();
                    $meetings  = Meeting::orderBy('meetings.id', 'desc')->take(5)->leftjoin('meeting_employees', 'meetings.id', '=', 'meeting_employees.meeting_id')->where('meeting_employees.employee_id', '=', $emp->id)->orWhere(
                        function ($q) {
                            $q->where('meetings.department_id', 0)->where('meetings.employee_id', 0);
                        }
                    )->get();
                    $events = Event::select('events.*', 'events.id as event_id', 'event_employees.*')->leftjoin('event_employees', 'events.id', '=', 'event_employees.event_id')->where('event_employees.employee_id', '=', $emp->id)->orWhere(
                        function ($q) {
                            $q->where('events.department_id', 0)->where('events.employee_id', 0);
                        }
                    )->get();

                    foreach ($events as $event) {
                        $arr['id']        = $event['event_id'];
                        $arr['title']     = $event['title'];
                        $arr['start']     = $event['start_date'];
                        $arr['end']       = $event['end_date'];
                        $arr['className'] = $event['color'];
                        $arr['url']       = (!empty($event['event_id'])) ? route('eventsshow', $event['event_id']) : '0';
                        $arrEvents[]      = $arr;
                    }

                    // All today's attendance records (multiple clock in/out per day)
                    $todaySessions = AttendanceEmployee::where('employee_id', '=', $emp->id)
                        ->where('date', '=', $date)
                        ->orderBy('id')
                        ->get();
                    // Current open session (clocked in but not out) for Clock Out button
                    $employeeAttendance = $todaySessions->where('clock_out', '00:00:00')->sortByDesc('id')->first();

                    $pendingSubstituteLeaves = LocalLeave::with(['employees', 'leaveType'])
                        ->where('substitute_employee_id', $emp->id)
                        ->where('substitute_status', 'Pending')
                        ->orderBy('created_at', 'desc')
                        ->get();
                }

                $showAttendanceCard = true; // employee type always sees clock in/out
                return view('dashboard.dashboard', compact('arrEvents', 'announcements', 'employees', 'meetings', 'employeeAttendance', 'officeTime', 'todaySessions', 'pendingSubstituteLeaves', 'currentLoginDetail', 'lastLogoutDetail', 'showAttendanceCard'));
            } else if ($user->type == 'super admin') {
                $user                       = \Auth::user();
                $user['total_user']         = $user->countCompany();
                $user['total_paid_user']    = $user->countPaidCompany();
                $user['total_orders']       = Order::total_orders();
                $user['total_orders_price'] = Order::total_orders_price();
                $user['total_plan']         = Plan::total_plan();
                $user['most_purchese_plan'] = (!empty(Plan::most_purchese_plan()) ? Plan::most_purchese_plan()->name : '');

                $chartData = $this->getOrderChart(['duration' => 'week']);

                return view('dashboard.super_admin', compact('user', 'chartData'));
            } else {
                // Company/HR branch: also load attendance card data if this user has an employee record (so clock in/out shows after re-login or when same user is both company and employee)
                $empForAttendance = Employee::where('user_id', '=', $user->id)->first();
                $employeeAttendance = null;
                $officeTime = [
                    'startTime' => Utility::getValByName('company_start_time'),
                    'endTime'   => Utility::getValByName('company_end_time'),
                ];
                $showAttendanceCard = false;
                $todaySessions = collect();
                if (!empty($empForAttendance)) {
                    $showAttendanceCard = true;
                    $todaySessions = AttendanceEmployee::where('employee_id', '=', $empForAttendance->id)
                        ->where('date', '=', date('Y-m-d'))
                        ->orderBy('id')
                        ->get();
                    $employeeAttendance = $todaySessions->where('clock_out', '00:00:00')->sortByDesc('id')->first();
                }

                $events    = Event::where('created_by', '=', \Auth::user()->creatorId())->get();
                $arrEvents = [];

                foreach ($events as $event) {
                    $arr['id']    = $event['id'];
                    $arr['title'] = $event['title'];
                    $arr['start'] = $event['start_date'];
                    $arr['end']   = $event['end_date'];
                    $arr['className'] = $event['color'];
                    // $arr['borderColor']     = "#fff";
                    // $arr['textColor']       = "white";
                    $arr['url']             = route('event.edit', $event['id']);

                    $arrEvents[] = $arr;
                }

                $announcements = Announcement::orderBy('announcements.id', 'desc')->take(5)->where('created_by', '=', \Auth::user()->creatorId())->get();

                $employees = User::where('type', '=', 'employee')->where('created_by', '=', \Auth::user()->creatorId())->get();
                $countEmployee = count($employees);

                $user      = User::where('type', '!=', 'employee')->where('created_by', '=', \Auth::user()->creatorId())->get();
                $countUser = count($user);

                // Staff bifurcation by Employee Type (for the dashboard "Total Staff" card).
                // Builds an array keyed by short_code (FT/PT/CON/MT/INT) with counts of
                // employees of that type. "Other Users" = company-side users (HR/admin)
                // who are not in the employees table.
                $staffBreakdown = [];
                if (\Schema::hasTable('employee_types')) {
                    $types = \App\Models\EmployeeType::orderBy('sort_order')->get();
                    $shortCodeMap = [
                        'full_time'    => 'FT',
                        'part_time'    => 'PT',
                        'consultant'   => 'CON',
                        'mgmt_trainee' => 'MT',
                        'intern'       => 'INT',
                    ];
                    foreach ($types as $t) {
                        $count = Employee::where('created_by', \Auth::user()->creatorId())
                            ->where('employee_type_id', $t->id)
                            ->count();
                        $staffBreakdown[] = [
                            'code'  => $shortCodeMap[$t->code] ?? strtoupper(substr($t->code, 0, 3)),
                            'name'  => $t->name,
                            'count' => $count,
                        ];
                    }
                    // Employees with NULL type (legacy data) — bucket as "Other"
                    $unset = Employee::where('created_by', \Auth::user()->creatorId())
                        ->whereNull('employee_type_id')->count();
                    if ($unset > 0) {
                        $staffBreakdown[] = ['code' => 'OTH', 'name' => __('Untyped'), 'count' => $unset];
                    }
                }
                // Total Staff = employees only (not HR/company login users).
                $totalStaff = Employee::where('created_by', \Auth::user()->creatorId())->count();

                $countTicket      = Ticket::where('created_by', '=', \Auth::user()->creatorId())->count();
                $countOpenTicket  = Ticket::where('status', '=', 'open')->where('created_by', '=', \Auth::user()->creatorId())->count();
                $countCloseTicket = Ticket::where('status', '=', 'close')->where('created_by', '=', \Auth::user()->creatorId())->count();

                $currentDate = date('Y-m-d');

                // $employees     = User::where('type', '=', 'employee')->where('created_by', '=', \Auth::user()->creatorId())->get();
                // $countEmployee = count($employees);
                $notClockIn    = AttendanceEmployee::where('date', '=', $currentDate)->get()->pluck('employee_id');

                $notClockIns = Employee::where('created_by', '=', \Auth::user()->creatorId())->whereNotIn('id', $notClockIn)->get();

                $accountBalance = AccountList::where('created_by', '=', \Auth::user()->creatorId())->sum('initial_balance');
                $activeJob   = Job::where('status', 'active')->where('created_by', '=', \Auth::user()->creatorId())->count();
                $inActiveJOb = Job::where('status', 'in_active')->where('created_by', '=', \Auth::user()->creatorId())->count();

                $totalPayee = Payees::where('created_by', '=', \Auth::user()->creatorId())->count();
                $totalPayer = Payer::where('created_by', '=', \Auth::user()->creatorId())->count();

                $meetings = Meeting::where('created_by', '=', \Auth::user()->creatorId())->limit(8)->get();

                // Get pending leave approvals
                $pendingLeaveApprovals = LocalLeave::with(['employees', 'leaveType'])
                    ->where('created_by', '=', \Auth::user()->creatorId())
                    ->where('status', 'Pending')
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get();

                $users = User::find(\Auth::user()->creatorId());
                $plan = Plan::find($users->plan);
                if ($plan->storage_limit > 0) {
                    $storage_limit = ($users->storage_limit / $plan->storage_limit) * 100;
                } else {
                    $storage_limit = 0;
                }

                // Month-wise employee joining count (from Apr 2025 to current month)
                $monthlyEmployeeData = [];
                $empChartStart = \Carbon\Carbon::create(2025, 4, 1);
                $empChartEnd = now()->startOfMonth();
                $empChartCursor = $empChartStart->copy();
                while ($empChartCursor->lte($empChartEnd)) {
                    $monthLabel = $empChartCursor->format('M Y');
                    $count = Employee::where('created_by', \Auth::user()->creatorId())
                        ->whereYear('company_doj', $empChartCursor->year)
                        ->whereMonth('company_doj', $empChartCursor->month)
                        ->count();
                    $monthlyEmployeeData[] = ['label' => $monthLabel, 'count' => $count];
                    $empChartCursor->addMonth();
                }

                // Total active employees at end of each month (from Apr 2025 to current month)
                $monthlyEmployeeTotal = [];
                $empChartCursor = $empChartStart->copy();
                while ($empChartCursor->lte($empChartEnd)) {
                    $mEnd = $empChartCursor->copy()->endOfMonth();
                    $monthLabel = $empChartCursor->format('M Y');
                    $total = Employee::where('created_by', \Auth::user()->creatorId())
                        ->where('company_doj', '<=', $mEnd->format('Y-m-d'))
                        ->count();
                    $monthlyEmployeeTotal[] = ['label' => $monthLabel, 'total' => $total];
                    $empChartCursor->addMonth();
                }

                // ── Daily Department-wise Attendance (today) ──
                // For each department: count of Present / Absent / Leave / Half Day
                // employees today. Headcount per department is the denominator so HR
                // can spot under-staffed teams at a glance.
                $today = now()->format('Y-m-d');
                $dailyAttendanceByDept = [];
                $departments = \App\Models\Department::where('created_by', \Auth::user()->creatorId())
                    ->orderBy('name')
                    ->get(['id', 'name']);

                foreach ($departments as $dept) {
                    $deptEmpIds = Employee::where('created_by', \Auth::user()->creatorId())
                        ->where('department_id', $dept->id)
                        ->pluck('id');

                    $headcount = $deptEmpIds->count();
                    if ($headcount === 0) continue; // skip empty departments

                    $todayAttn = AttendanceEmployee::whereIn('employee_id', $deptEmpIds)
                        ->where('date', $today)
                        ->get(['status']);

                    $present = $half = $absent = $leave = 0;
                    foreach ($todayAttn as $a) {
                        $s = strtolower((string) $a->status);
                        if ($s === 'present')      $present++;
                        elseif ($s === 'half day') $half++;
                        elseif ($s === 'absent')   $absent++;
                        elseif ($s === 'leave')    $leave++;
                    }
                    // Anyone not in attendance today is treated as "not marked"
                    $notMarked = max(0, $headcount - ($present + $half + $absent + $leave));

                    $dailyAttendanceByDept[] = [
                        'department' => $dept->name,
                        'headcount'  => $headcount,
                        'present'    => $present,
                        'half_day'   => $half,
                        'absent'     => $absent,
                        'leave'      => $leave,
                        'not_marked' => $notMarked,
                    ];
                }

                // ── Team distribution by Employee Type (for the donut chart) ──
                // Reuses the staffBreakdown values but in chart-ready shape.
                $teamDistribution = [];
                foreach ($staffBreakdown as $row) {
                    if ($row['count'] > 0) {
                        $teamDistribution[] = ['label' => $row['name'], 'count' => $row['count'], 'code' => $row['code']];
                    }
                }

                // ── Attrition Analysis (Full-time, last 12 months) ──
                // Annual attrition % = (employees who left / avg headcount) × 100
                // "Left" = has termination row in window, OR is_active=0 with updated_at in window.
                // Scope: only Full-time employees (industry-standard view).
                $fullTimeId = optional(\App\Models\EmployeeType::where('code', 'full_time')->first())->id;
                $attritionRate = 0.0;
                $attritionLeft = 0;
                $attritionAvgHc = 0;
                if ($fullTimeId) {
                    $windowStart = now()->subMonths(12)->startOfDay();
                    $windowEnd   = now()->endOfDay();

                    // Step 1: who LEFT in this window (Full-time only)
                    $terminatedIds = \DB::table('terminations as t')
                        ->join('employees as e', 'e.id', '=', 't.employee_id')
                        ->where('e.created_by', \Auth::user()->creatorId())
                        ->where('e.employee_type_id', $fullTimeId)
                        ->whereBetween('t.termination_date', [$windowStart, $windowEnd])
                        ->pluck('e.id');

                    $inactiveIds = Employee::where('created_by', \Auth::user()->creatorId())
                        ->where('employee_type_id', $fullTimeId)
                        ->where('is_active', 0)
                        ->whereBetween('updated_at', [$windowStart, $windowEnd])
                        ->pluck('id');

                    $leftIds = $terminatedIds->merge($inactiveIds)->unique();
                    $attritionLeft = $leftIds->count();

                    // Step 2: average Full-time headcount across the window (avg of start + end)
                    $hcStart = Employee::where('created_by', \Auth::user()->creatorId())
                        ->where('employee_type_id', $fullTimeId)
                        ->where('company_doj', '<=', $windowStart->format('Y-m-d'))
                        ->where(function ($q) use ($windowStart) {
                            // Active at start: either still active, or left after windowStart
                            $q->where('is_active', 1)
                              ->orWhere('updated_at', '>=', $windowStart);
                        })
                        ->count();
                    $hcEnd = Employee::where('created_by', \Auth::user()->creatorId())
                        ->where('employee_type_id', $fullTimeId)
                        ->where('is_active', 1)
                        ->where('company_doj', '<=', $windowEnd->format('Y-m-d'))
                        ->count();

                    $attritionAvgHc = (int) round(($hcStart + $hcEnd) / 2);
                    $attritionRate = $attritionAvgHc > 0
                        ? round(($attritionLeft / $attritionAvgHc) * 100, 1)
                        : 0.0;
                }

                return view('dashboard.dashboard', compact('arrEvents', 'announcements', 'employees', 'activeJob', 'inActiveJOb', 'meetings', 'countEmployee', 'countUser', 'totalStaff', 'countTicket', 'countOpenTicket', 'countCloseTicket', 'notClockIns', 'accountBalance', 'totalPayee', 'totalPayer', 'users', 'plan', 'storage_limit', 'pendingLeaveApprovals', 'currentLoginDetail', 'lastLogoutDetail', 'employeeAttendance', 'officeTime', 'todaySessions', 'showAttendanceCard', 'monthlyEmployeeData', 'monthlyEmployeeTotal', 'staffBreakdown', 'dailyAttendanceByDept', 'teamDistribution', 'attritionRate', 'attritionLeft', 'attritionAvgHc'));
            }
        } else {
            if (!file_exists(storage_path() . "/installed")) {
                header('location:install');
                die;
            } else {
                $settings = Utility::settings();
                if ($settings['display_landing_page'] == 'on' && \Schema::hasTable('landing_page_settings')) {
                    $plans = Plan::get();
                    $get_section = LandingPageSection::orderBy('section_order', 'ASC')->get();

                    return view('landingpage::layouts.landingpage', compact('plans', 'get_section'));
                } else {
                    return redirect('login');
                }
            }
        }
    }

    public function getOrderChart($arrParam)
    {
        $arrDuration = [];
        if ($arrParam['duration']) {
            if ($arrParam['duration'] == 'week') {
                $previous_week = strtotime("-2 week +1 day");
                for ($i = 0; $i < 14; $i++) {
                    $arrDuration[date('Y-m-d', $previous_week)] = date('d-M', $previous_week);
                    $previous_week                              = strtotime(date('Y-m-d', $previous_week) . " +1 day");
                }
            }
        }

        $arrTask          = [];
        $arrTask['label'] = [];
        $arrTask['data']  = [];
        foreach ($arrDuration as $date => $label) {

            $data               = Order::select(\DB::raw('count(*) as total'))->whereDate('created_at', '=', $date)->first();
            $arrTask['label'][] = $label;
            $arrTask['data'][]  = $data->total;
        }

        return $arrTask;
    }
}
