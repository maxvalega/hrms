<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Leave as LocalLeave;
use App\Models\LeaveType;
use App\Models\Holiday;
use App\Mail\LeaveActionSend;
use App\Mail\LeaveSubstituteRequest;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Imports\EmployeesImport;
use App\Exports\LeaveExport;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\GoogleCalendar\Event as GoogleEvent;
use App\Services\LeavePolicyService;

class LeaveController extends Controller
{
    protected function leavePolicy(): LeavePolicyService
    {
        return app(LeavePolicyService::class);
    }

    public function index()
    {

        if (\Auth::user()->can('Manage Leave')) {
            $leaveBalance = [];
            $showEmployeeColumn = \Auth::user()->type != 'employee';
            $date = Utility::AnnualLeaveCycle();
            $settings = Utility::settings();
            $leavePolicy = [
                'carry_forward' => ($settings['leave_carry_forward'] ?? 'off') === 'on',
                'carry_forward_max' => (float) ($settings['leave_carry_forward_max'] ?? 0),
                'encashment' => ($settings['leave_encashment'] ?? 'off') === 'on',
                'encashment_min_balance' => (float) ($settings['leave_encashment_min_balance'] ?? 0),
            ];

            if (\Auth::user()->type == 'employee') {
                $user     = \Auth::user();
                $employee = Employee::where('user_id', '=', $user->id)->first();

                $employeeIds = [];
                if (!empty($employee)) {
                    $employeeIds[] = (int) $employee->id;

                    if (Schema::hasColumn('employees', 'reporting_manager_id')) {
                        $subordinateIds = Employee::where('created_by', \Auth::user()->creatorId())
                            ->where('reporting_manager_id', $employee->id)
                            ->pluck('id')
                            ->map(function ($id) {
                                return (int) $id;
                            })
                            ->toArray();

                        if (!empty($subordinateIds)) {
                            $employeeIds = array_values(array_unique(array_merge($employeeIds, $subordinateIds)));
                        }
                    }
                }

                $showEmployeeColumn = count($employeeIds) > 1;

                $leaves = LocalLeave::whereIn('employee_id', $employeeIds)
                    ->with(['employees', 'leaveType'])
                    ->orderByRaw("CASE WHEN status = 'Pending' THEN 0 WHEN status = 'Approved' THEN 1 WHEN status = 'Reject' THEN 2 ELSE 3 END")
                    ->orderByDesc('applied_on')
                    ->orderByDesc('id')
                    ->get();

                // Calculate leave balance for employee
                $leaveTypes = LeaveType::where('created_by', '=', \Auth::user()->creatorId())->get();
                foreach ($leaveTypes as $leaveType) {
                    $summary = $this->calculateLeaveBalanceSummary((int) $employee->id, $leaveType, $date);

                    $leaveBalance[] = [
                        'leave_type' => $leaveType->title,
                        'total' => $summary['total'],
                        'monthly_accrual' => $summary['monthly_accrual'],
                        'used' => $summary['used'],
                        'pending' => $summary['pending'],
                        'available' => $summary['available'],
                        'credit_mode' => $summary['credit_mode'] ?? 'lump_sum',
                        'carry_forward' => $summary['carry_forward'] ?? 0,
                        'encashable_leave' => $summary['encashable_leave'] ?? 0,
                    ];
                }
            } else {
                $leaves = LocalLeave::where('created_by', '=', \Auth::user()->creatorId())
                    ->with(['employees', 'leaveType'])
                    ->orderByRaw("CASE WHEN status = 'Pending' THEN 0 WHEN status = 'Approved' THEN 1 WHEN status = 'Reject' THEN 2 ELSE 3 END")
                    ->orderByDesc('applied_on')
                    ->orderByDesc('id')
                    ->get();

                // Calculate leave balance for all leave types
                $leaveTypes = LeaveType::where('created_by', '=', \Auth::user()->creatorId())->get();
                foreach ($leaveTypes as $leaveType) {
                    $allowance = $this->getLeaveAllowanceDetails($leaveType, null, $date);
                    $usage = $this->getLeaveUsageByCycle(null, (int) $leaveType->id, $date);
                    $used = $usage['used'];
                    $pending = $usage['pending'];
                    $available = $this->calculateAvailableLeaveByCreditMode($allowance, $usage);

                    $leaveBalance[] = [
                        'leave_type' => $leaveType->title,
                        'total' => $allowance['total_annual'],
                        'monthly_accrual' => $allowance['monthly_accrual'],
                        'used' => $used,
                        'pending' => $pending,
                        'available' => $available,
                        'credit_mode' => $allowance['credit_mode'] ?? 'lump_sum',
                        'carry_forward' => $allowance['carry_forward'] ?? 0,
                        'encashable_leave' => $this->calculateEncashableLeave($available, $leaveType),
                    ];
                }
            }

            return view('leave.index', compact('leaves', 'leaveBalance', 'date', 'leavePolicy', 'showEmployeeColumn'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('Create Leave')) {
            $isProbationRestricted = false;
            $probationWarningMessage = null;

            if (Auth::user()->type == 'employee') {
                $employees = Employee::where('user_id', '=', \Auth::user()->id)->first();
                if (!empty($employees) && $this->isEmployeeInProbation($employees)) {
                    $isProbationRestricted = true;
                    $probationWarningMessage = $this->getProbationLeaveNotAllowedMessage($employees);
                }
            } else {
                $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            }
            $leavetypes      = LeaveType::where('created_by', '=', \Auth::user()->creatorId())->get();
            // $leavetypes_days = LeaveType::where('created_by', '=', \Auth::user()->creatorId())->get();

            $substitutes = [];
            if (Auth::user()->type == 'employee' && !empty($employees)) {
                $substitutes = $this->getSubstituteList($employees->id);
            }

            return view('leave.create', compact('employees', 'leavetypes', 'substitutes', 'isProbationRestricted', 'probationWarningMessage'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('Create Leave')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'employee_id' => 'required',
                    'leave_type_id' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required|after_or_equal:start_date',
                    'day_type' => 'required|in:full_day,first_half,second_half',
                    'substitute_employee_id' => 'nullable',
                    'leave_reason' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            // $employee = Employee::where('created_by', '=', \Auth::user()->id)->first();
            $leave_type = LeaveType::find($request->leave_type_id);
            if (empty($leave_type)) {
                return redirect()->back()->with('error', __('Invalid leave type selected.'));
            }
            $approvalRequirement = $leave_type->approval_requirement ?? 'na';
            // Check if it's a Vacation leave type (substitute required) - handle both "vacation" and "vaction" typo
            $titleLower = strtolower($leave_type->title ?? '');
            $isVacationLeave = strpos($titleLower, 'vacation') !== false || strpos($titleLower, 'vaction') !== false;
            $isSickLeave = preg_match('/(sick|seek)/', $titleLower) === 1;

            $total_leave_days = $this->calculateLeaveDays($request->start_date, $request->end_date, $request->day_type, \Auth::user()->creatorId());
            $is_half_day = in_array($request->day_type, ['first_half', 'second_half'], true);
            if ($is_half_day && $request->start_date !== $request->end_date) {
                return redirect()->back()->with('error', __('Half day leave must be for a single date.'));
            }
            if ($is_half_day) {
                $total_leave_days = 0.5;
            }
            
            if ($isVacationLeave) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'substitute_employee_id' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }
            }

            // Validate medical certificate for sick leave when requested leave is 3+ days
            if ($isSickLeave && $total_leave_days >= 3) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'medical_certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }
            } elseif ($request->hasFile('medical_certificate')) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'medical_certificate' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }
            }

            $startDate = new \DateTime($request->start_date);
            $endDate = new \DateTime($request->end_date);
            $endDate->add(new \DateInterval('P1D'));
            // $total_leave_days = !empty($startDate->diff($endDate)) ? $startDate->diff($endDate)->days : 0;
            $date = Utility::AnnualLeaveCycle();

            if (\Auth::user()->type == 'employee') {
                $employee = Employee::where('user_id', '=', \Auth::id())->first();
                if (empty($employee)) {
                    return redirect()->back()->with('error', __('Employee record not found.'));
                }

                if ((int) $request->employee_id !== (int) $employee->id) {
                    return redirect()->back()->with('error', __('Permission denied.'));
                }
            } else {
                $employee = Employee::where('id', $request->employee_id)->first();
                if (empty($employee)) {
                    return redirect()->back()->with('error', __('Employee not found.'));
                }
            }

            if ($this->isEmployeeInProbation($employee)) {
                return redirect()->back()->with('error', $this->getProbationLeaveNotAllowedMessage($employee));
            }

            // NEW: per-type policy matrix (eligibility, notice, WFH caps, bereavement family)
            $policyError = $this->leavePolicy()->validateApplication(
                $leave_type,
                $employee,
                $request->start_date,
                $request->end_date,
                (float) $total_leave_days,
                $request->input('family_relation'),
                date('Y-m-d')
            );
            if ($policyError) {
                return redirect()->back()->with('error', $policyError);
            }

            $usage = $this->getLeaveUsageByCycle((int) $employee->id, (int) $leave_type->id, $date);
            $leaves_used = $usage['used'];
            $leaves_pending = $usage['pending'];

            if ($this->hasSubstituteBlock((int) $employee->id, $request->start_date, $request->end_date)) {
                return redirect()->back()->with('error', __('You are assigned as a substitute for these dates and cannot apply leave.'));
            }

            $allowance = $this->getLeaveAllowanceDetails($leave_type, $employee, $date);
            $available = $this->calculateAvailableLeaveByCreditMode($allowance, $usage);

            // Comp-off / as-earned: use compensatory claim flow rather than normal balance quota
            $isAsEarned = !empty($leave_type->is_as_earned) || ($leave_type->credit_frequency === 'earned');
            $isMonthlyCap = ($leave_type->credit_frequency === 'monthly_cap');
            if (!$isAsEarned && !$isMonthlyCap && $total_leave_days > $available) {
                return redirect()->back()->with('error', __('You cannot apply leave more than your available balance.'));
            }
            // WFH monthly_cap: balance enforced via LeavePolicyService monthly_limit (not cycle pool)

            // OLD: if ($leave_type->days >= $total_leave_days) {
            // NEW: allow as-earned or when within type max days (or unlimited max when days=0 and as-earned)
            $maxDaysAllowed = (float) $leave_type->days;
            $withinMax = $isAsEarned || $maxDaysAllowed <= 0 || $maxDaysAllowed >= $total_leave_days;

            if ($withinMax) {

                $leave    = new LocalLeave();
                if (\Auth::user()->type == "employee") {
                    $leave->employee_id = $request->employee_id;
                } else {
                    $leave->employee_id = $request->employee_id;
                }
                $leave->leave_type_id    = $request->leave_type_id;
                $leave->applied_on       = date('Y-m-d');
                $leave->start_date       = $request->start_date;
                $leave->end_date         = $request->end_date;
                $leave->day_type         = $request->day_type;
                $substituteId = $request->substitute_employee_id;
                if (!empty($substituteId)) {
                    $leave->substitute_employee_id = $substituteId;
                    $leave->substitute_status = 'Pending';
                    $leave->substitute_token = Str::random(32);
                } else {
                    $leave->substitute_employee_id = null;
                    $leave->substitute_status = $isVacationLeave ? 'Pending' : 'Accepted';
                    $leave->substitute_token = null;
                }
                $leave->total_leave_days = $total_leave_days;
                $leave->leave_reason     = $request->leave_reason;
                if ($request->filled('family_relation')) {
                    // Append family relation for bereavement audit (keep leave_reason intact)
                    $leave->leave_reason = trim($leave->leave_reason . ' [Family: ' . $request->family_relation . ']');
                }
                $leave->status = 'Pending';
                
                // Handle medical certificate upload
                if ($isSickLeave && $request->hasFile('medical_certificate')) {
                    try {
                        $file = $request->file('medical_certificate');
                        $fileName = time() . '_' . $leave->id . '_' . $file->getClientOriginalName();
                        $filePath = $file->storeAs('leaves/medical_certificates', $fileName, 'public');
                        $leave->medical_certificate = $filePath;
                        $leave->certificate_verified = false;
                    } catch (\Exception $e) {
                        \Log::error('Failed to upload medical certificate: ' . $e->getMessage());
                        return redirect()->back()->with('error', __('Failed to upload medical certificate.'));
                    }
                }
                
                // Calculate and store professional period (only if columns exist)
                if (\Schema::hasColumn('leaves', 'professional_years')) {
                    $professionalPeriod = $this->calculateProfessionalPeriod($employee);
                    $leave->professional_years = $professionalPeriod['professional_years'];
                    $leave->professional_months = $professionalPeriod['professional_months'];
                    $leave->professional_days = $professionalPeriod['professional_days'];
                    $leave->calculated_at = now();
                }
                
                $leave->created_by       = Auth::user()->creatorId();
                $leave->save();

                $employee = Employee::where('id', $leave->employee_id)->first();

                // Send email to substitute ONLY (not to manager yet)
                if (!empty($leave->substitute_employee_id)) {
                    $substitute = Employee::find($leave->substitute_employee_id);
                    if (!empty($substitute) && !empty($substitute->email)) {
                        try {
                            Mail::to($substitute->email)->send(new LeaveSubstituteRequest($leave, $employee, $substitute));
                        } catch (\Exception $e) {
                            \Log::error('Failed to send substitute leave request email: ' . $e->getMessage());
                            // Continue without failing - leave is created successfully
                        }
                    }
                }

                if ($approvalRequirement !== 'subordinate') {
                    $this->notifyManagerOfLeaveRequest($leave);
                }

                // Google calendar
                if ($request->get('synchronize_type')  == 'google_calender') {

                    $type = 'leave';
                    $request1 = new GoogleEvent();
                    $request1->title = !empty(\Auth::user()->getLeaveType($leave->leave_type_id)) ? \Auth::user()->getLeaveType($leave->leave_type_id)->title : '';
                    $request1->start_date = $request->start_date;
                    $request1->end_date = $request->end_date;
                    Utility::addCalendarData($request1, $type);
                }

                if (!empty($leave->substitute_employee_id)) {
                    return redirect()->route('leave.index')->with('success', __('Leave successfully created. Waiting for substitute approval.'));
                }
                return redirect()->route('leave.index')->with('success', __('Leave successfully created. Waiting for manager approval.'));
            } else {
                return redirect()->back()->with('error', __('Leave type ' . $leave_type->title . ' is provide maximum ' . $leave_type->days . "  days please make sure your selected days is under " . $leave_type->days . ' days.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(LocalLeave $leave)
    {
        return redirect()->route('leave.index');
    }

    public function edit(LocalLeave $leave)
    {
        if (\Auth::user()->can('Edit Leave')) {
            if ($leave->created_by == \Auth::user()->creatorId()) {

                if (Auth::user()->type == 'employee') {
                    $employees = Employee::where('employee_id', '=', \Auth::user()->creatorId())->first();
                } else {
                    $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                }

                // $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');

                // $leavetypes = LeaveType::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('title', 'id');
                $leavetypes      = LeaveType::where('created_by', '=', \Auth::user()->creatorId())->get();

                $substitutes = $this->getSubstituteList($leave->employee_id);

                return view('leave.edit', compact('leave', 'employees', 'leavetypes', 'substitutes'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, $leave)
    {
        $leave = LocalLeave::find($leave);
        if (\Auth::user()->can('Edit Leave')) {
            if ($leave->created_by == Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'employee_id' => 'required',
                        'leave_type_id' => 'required',
                        'start_date' => 'required',
                        'end_date' => 'required|after_or_equal:start_date',
                        'day_type' => 'required|in:full_day,first_half,second_half',
                        'substitute_employee_id' => 'nullable',
                        'leave_reason' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }
                $leave_type = LeaveType::find($request->leave_type_id);
                if (empty($leave_type)) {
                    return redirect()->back()->with('error', __('Invalid leave type selected.'));
                }
                $approvalRequirement = $leave_type->approval_requirement ?? 'na';
                // Check if it's a Vacation leave type (substitute required) - handle both "vacation" and "vaction" typo
                $titleLower = strtolower($leave_type->title ?? '');
                $isVacationLeave = strpos($titleLower, 'vacation') !== false || strpos($titleLower, 'vaction') !== false;
                $isSickLeave = preg_match('/(sick|seek)/', $titleLower) === 1;

                $total_leave_days = $this->calculateLeaveDays($request->start_date, $request->end_date, $request->day_type, \Auth::user()->creatorId());
                $is_half_day = in_array($request->day_type, ['first_half', 'second_half'], true);
                if ($is_half_day && $request->start_date !== $request->end_date) {
                    return redirect()->back()->with('error', __('Half day leave must be for a single date.'));
                }
                if ($is_half_day) {
                    $total_leave_days = 0.5;
                }
                
                if ($isVacationLeave) {
                    $validator = \Validator::make(
                        $request->all(),
                        [
                            'substitute_employee_id' => 'required',
                        ]
                    );
                    if ($validator->fails()) {
                        $messages = $validator->getMessageBag();

                        return redirect()->back()->with('error', $messages->first());
                    }
                }

                // Validate medical certificate for sick leave when requested leave is 3+ days
                if ($isSickLeave && $total_leave_days >= 3 && !$request->hasFile('medical_certificate') && empty($leave->medical_certificate)) {
                    return redirect()->back()->with('error', __('Medical certificate is required for sick leave of 3 or more days.'));
                }

                if ($request->hasFile('medical_certificate')) {
                    $validator = \Validator::make(
                        $request->all(),
                        [
                            'medical_certificate' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
                        ]
                    );
                    if ($validator->fails()) {
                        $messages = $validator->getMessageBag();
                        return redirect()->back()->with('error', $messages->first());
                    }
                }
                $startDate = new \DateTime($request->start_date);
                $endDate = new \DateTime($request->end_date);
                $endDate->add(new \DateInterval('P1D'));
                // $total_leave_days = !empty($startDate->diff($endDate)) ? $startDate->diff($endDate)->days : 0;
                $date = Utility::AnnualLeaveCycle();

                if (\Auth::user()->type == 'employee') {
                    $employee = Employee::where('user_id', '=', \Auth::id())->first();
                    if (empty($employee)) {
                        return redirect()->back()->with('error', __('Employee record not found.'));
                    }

                    if ((int) $request->employee_id !== (int) $employee->id) {
                        return redirect()->back()->with('error', __('Permission denied.'));
                    }
                } else {
                    $employee = Employee::where('id', '=', $request->employee_id)->first();
                    if (empty($employee)) {
                        return redirect()->back()->with('error', __('Employee not found.'));
                    }
                }

                if ($this->isEmployeeInProbation($employee)) {
                    return redirect()->back()->with('error', $this->getProbationLeaveNotAllowedMessage($employee));
                }

                $usage = $this->getLeaveUsageByCycle((int) $employee->id, (int) $leave_type->id, $date, (int) $leave->id);
                $leaves_used = $usage['used'];
                $leaves_pending = $usage['pending'];

                if ($this->hasSubstituteBlock((int) $employee->id, $request->start_date, $request->end_date, (int) $leave->id)) {
                    return redirect()->back()->with('error', __('You are assigned as a substitute for these dates and cannot apply leave.'));
                }

                $allowance = $this->getLeaveAllowanceDetails($leave_type, $employee, $date);

                $available = $this->calculateAvailableLeaveByCreditMode($allowance, $usage);
                if ($total_leave_days > $available) {
                    return redirect()->back()->with('error', __('You cannot apply leave more than your available balance.'));
                }

                if ($leave_type->days >= $total_leave_days) {
                    if (\Auth::user()->type == 'employee') {
                        $leave->employee_id = $employee->id;
                    } else {
                        $leave->employee_id      = $request->employee_id;
                    }
                    $leave->leave_type_id    = $request->leave_type_id;
                    $leave->start_date       = $request->start_date;
                    $leave->end_date         = $request->end_date;
                    $leave->day_type         = $request->day_type;
                    $leave->total_leave_days = $total_leave_days;
                    $leave->leave_reason     = $request->leave_reason;
                    if ((int) $leave->substitute_employee_id !== (int) $request->substitute_employee_id) {
                        if ($isVacationLeave) {
                            $leave->substitute_employee_id = $request->substitute_employee_id;
                            $leave->substitute_status = 'Pending';
                            $leave->substitute_token = Str::random(32);
                            $leave->substitute_responded_at = null;

                            $substitute = Employee::find($leave->substitute_employee_id);
                            if (!empty($substitute) && !empty($substitute->email)) {
                                $requester = Employee::find($leave->employee_id);
                                if (!empty($requester)) {
                                    Mail::to($substitute->email)->send(new LeaveSubstituteRequest($leave, $requester, $substitute));
                                }
                            }
                        } else {
                            $leave->substitute_employee_id = null;
                            $leave->substitute_status = 'Accepted';
                            $leave->substitute_token = null;
                            $leave->substitute_responded_at = null;
                        }
                    }
                    if (!$isVacationLeave) {
                        $leave->substitute_employee_id = null;
                        $leave->substitute_status = 'Accepted';
                        $leave->substitute_token = null;
                        $leave->substitute_responded_at = null;
                    }
                    // $leave->status           = $request->status;

                    // Handle medical certificate upload
                    if ($isSickLeave && $request->hasFile('medical_certificate')) {
                        try {
                            // Delete old file if exists
                            if (!empty($leave->medical_certificate) && \Storage::disk('public')->exists($leave->medical_certificate)) {
                                \Storage::disk('public')->delete($leave->medical_certificate);
                            }

                            $file = $request->file('medical_certificate');
                            $fileName = time() . '_' . $leave->id . '_' . $file->getClientOriginalName();
                            $filePath = $file->storeAs('leaves/medical_certificates', $fileName, 'public');
                            $leave->medical_certificate = $filePath;
                            $leave->certificate_verified = false;
                        } catch (\Exception $e) {
                            \Log::error('Failed to upload medical certificate: ' . $e->getMessage());
                            return redirect()->back()->with('error', __('Failed to upload medical certificate.'));
                        }
                    }

                    $leave->save();

                    return redirect()->route('leave.index')->with('success', __('Leave successfully updated.'));
                } else {
                    return redirect()->back()->with('error', __('Leave type ' . $leave_type->name . ' is provide maximum ' . $leave_type->days . "  days please make sure your selected days is under " . $leave_type->days . ' days.'));
                }
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(LocalLeave $leave)
    {
        if (\Auth::user()->can('Delete Leave')) {
            if ($leave->created_by == \Auth::user()->creatorId()) {
                // Clean up system-generated substitute blocks when leave is deleted
                $this->removeSubstituteLeaveBlock($leave);
                $leave->delete();

                return redirect()->route('leave.index')->with('success', __('Leave successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function export()
    {
        $name = 'leave_' . date('Y-m-d i:h:s');
        $data = Excel::download(new LeaveExport(), $name . '.xlsx');

        return $data;
    }

    public function action($id)
    {
        $leave     = LocalLeave::find($id);
        if (empty($leave) || (int) $leave->created_by !== (int) \Auth::user()->creatorId()) {
            return redirect()->route('leave.index')->with('error', __('Leave request not found.'));
        }

        $employee  = Employee::find($leave->employee_id);
        $leavetype = LeaveType::find($leave->leave_type_id);
        $canTakeAction = \Auth::user()->can('Manage Leave');

        return view('leave.action', compact('employee', 'leavetype', 'leave', 'canTakeAction'));
    }

    public function changeaction(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'leave_id' => 'required|integer|exists:leaves,id',
            'status' => 'required|in:Approved,Reject',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        $leave = LocalLeave::find($request->leave_id);
        if (empty($leave)) {
            return redirect()->route('leave.index')->with('error', __('Leave request not found.'));
        }

        if (!\Auth::user()->can('Manage Leave') || (int) $leave->created_by !== (int) \Auth::user()->creatorId()) {
            return redirect()->route('leave.action', $leave->id)->with('error', __('Permission denied.'));
        }

        if ($leave->status !== 'Pending') {
            return redirect()->route('leave.action', $leave->id)->with('error', __('Leave has already been processed.'));
        }

        $leaveType = LeaveType::find($leave->leave_type_id);
        $approvalRequirement = $leaveType->approval_requirement ?? 'na';
        // Check if it's a Vacation leave type - handle both "vacation" and "vaction" typo
        $titleLower = strtolower($leaveType->title ?? '');
        $isVacationLeave = strpos($titleLower, 'vacation') !== false || strpos($titleLower, 'vaction') !== false;

        $oldStatus = $leave->status;
        $leave->status = $request->status;
        if ($leave->status == 'Approved') {
            $total_leave_days = $this->calculateLeaveDays(
                $leave->start_date,
                $leave->end_date,
                $leave->day_type ?? 'full_day',
                (int) ($leave->created_by ?? \Auth::user()->creatorId())
            );
            $leave->total_leave_days = $total_leave_days;
            $leave->status           = 'Approved';
        } elseif ($leave->status == 'Reject') {
            // Clean up system-generated substitute blocks when leave is rejected
            $this->removeSubstituteLeaveBlock($leave);
        }

        $leave->save();

        // twilio
        $setting = Utility::settings(\Auth::user()->creatorId());
        $emp = Employee::find($leave->employee_id);
        if (isset($setting['twilio_leave_approve_notification']) && $setting['twilio_leave_approve_notification'] == 1) {
            // $msg = __("Your leave has been") . ' ' . $leave->status . '.';

            $uArr = [
                'leave_status' => $leave->status,
            ];

            // Utility::send_twilio_msg($emp->phone, 'leave_approve_reject', $uArr);
            if (!empty($emp->phone)) {
                Utility::send_twilio_msg($emp->phone, 'leave_approve_reject', $uArr);
            } else {
                \Log::warning('Leave status updated but employee phone number is missing for Twilio.', [
                    'leave_id' => $leave->id,
                    'employee_id' => $leave->employee_id,
                ]);
            }
        }

        $setings = Utility::settings();

        if ($setings['leave_status'] == 1) {
            $employee     = Employee::where('id', $leave->employee_id)->where('created_by', '=', \Auth::user()->creatorId())->first();

            $uArr = [
                'leave_email' => $employee->email,
                'leave_status_name' => $employee->name,
                'leave_status' => $request->status,
                'leave_reason' => $leave->leave_reason,
                'leave_start_date' => $leave->start_date,
                'leave_end_date' => $leave->end_date,
                'total_leave_days' => $leave->total_leave_days,

            ];
            $resp = Utility::sendEmailTemplate('leave_status', [$employee->email], $uArr);
            return redirect()->route('leave.action', $leave->id)->with('success', __('Leave status successfully updated.') . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
        }

        return redirect()->route('leave.action', $leave->id)->with('success', __('Leave status successfully updated.'));
    }

    public function substituteEmployees(Request $request)
    {
        if (!\Auth::user()->can('Create Leave')) {
            return response()->json([], 403);
        }

        $employee = Employee::find($request->employee_id);
        if (empty($employee) || empty($employee->department_id)) {
            return response()->json([]);
        }

        $substitutes = Employee::where('department_id', $employee->department_id)
            ->where('created_by', \Auth::user()->creatorId())
            ->where('id', '!=', $employee->id)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                ];
            });

        return response()->json($substitutes);
    }

    public function substituteAction($leave, $token, $action)
    {
        $leave = LocalLeave::find($leave);
        if (empty($leave) || $leave->substitute_token !== $token) {
            return view('leave.substitute_action_result', [
                'title' => __('Invalid Link'),
                'message' => __('This link is invalid or expired.'),
            ]);
        }

        if (!in_array($action, ['accept', 'reject'], true)) {
            return view('leave.substitute_action_result', [
                'title' => __('Invalid Action'),
                'message' => __('This action is not supported.'),
            ]);
        }

        $result = $this->applySubstituteDecision($leave, $action);

        return view('leave.substitute_action_result', [
            'title' => $result['title'],
            'message' => $result['message'],
        ]);
    }

    public function substituteRespond(Request $request)
    {
        if (!Auth::check() || Auth::user()->type !== 'employee') {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = \Validator::make(
            $request->all(),
            [
                'leave_id' => 'required|integer',
                'action' => 'required|in:accept,reject',
            ]
        );

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        $leave = LocalLeave::find($request->leave_id);
        if (empty($leave)) {
            return redirect()->back()->with('error', __('Leave request not found.'));
        }

        $employee = Employee::where('user_id', Auth::id())->first();
        if (empty($employee) || (int) $leave->substitute_employee_id !== (int) $employee->id) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if ($leave->substitute_status !== 'Pending') {
            return redirect()->back()->with('error', __('This substitute request has already been handled.'));
        }

        $result = $this->applySubstituteDecision($leave, $request->action);

        return redirect()->back()->with('success', $result['message']);
    }

    protected function applySubstituteDecision(LocalLeave $leave, string $action): array
    {
        $leave->substitute_status = $action === 'accept' ? 'Accepted' : 'Rejected';
        $leave->substitute_responded_at = now();

        if ($action === 'reject') {
            $leave->status = 'Reject';
            $leave->save();

            return [
                'title' => __('Rejected'),
                'message' => __('You have rejected the substitute request. The leave has been rejected.'),
            ];
        }

        $leave->save();
        $this->createSubstituteLeaveBlock($leave);
        $this->notifyManagerOfLeaveRequest($leave);

        return [
            'title' => __('Accepted'),
            'message' => __('You have accepted the substitute request. The leave request has been sent to the company for approval.'),
        ];
    }

    protected function notifyManagerOfLeaveRequest(LocalLeave $leave): void
    {
        $employee = Employee::where('id', $leave->employee_id)->first();
        if (empty($employee)) {
            return;
        }

        $user = User::where('id', $employee->created_by)->first();
        if (empty($user)) {
            return;
        }

        try {
            $settings = Utility::settings();
            if ($settings['new_leave_request'] == 1) {
                $uArr = [
                    'employee_name' => $employee->name,
                    'leave_type' => $leave->leaveType->title ?? 'Leave',
                    'leave_start_end_time' => ($leave->start_date ?? '') . ' to ' . ($leave->end_date ?? ''),
                    'leave_reason' => $leave->leave_reason ?? '',
                ];
                Utility::sendEmailTemplate('new_leave_request', [$user->id => $user->email], $uArr);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send leave request to manager: ' . $e->getMessage());
        }
    }

    protected function createSubstituteLeaveBlock($leave)
    {
        try {
            $substitute = Employee::find($leave->substitute_employee_id);
            $employee = Employee::find($leave->employee_id);
            if (empty($substitute) || empty($employee)) {
                return;
            }

            $startDate = new \DateTime($leave->start_date);
            $endDate = new \DateTime($leave->end_date);
            $endDate->add(new \DateInterval('P1D'));
            
            $current = clone $startDate;
            while ($current < $endDate) {
                $blockDate = $current->format('Y-m-d');
                $existingBlock = LocalLeave::where('employee_id', $substitute->id)
                    ->where('start_date', $blockDate)
                    ->where('end_date', $blockDate)
                    ->where('remark', 'System-generated substitute block')
                    ->exists();

                if ($existingBlock) {
                    $current->add(new \DateInterval('P1D'));
                    continue;
                }

                // Mark this date as blocked for substitute (create a system leave entry)
                $blockLeave = new LocalLeave();
                $blockLeave->employee_id = $substitute->id;
                $blockLeave->leave_type_id = $leave->leave_type_id;
                $blockLeave->applied_on = date('Y-m-d');
                $blockLeave->start_date = $blockDate;
                $blockLeave->end_date = $blockDate;
                $blockLeave->day_type = $leave->day_type;
                $blockLeave->total_leave_days = $leave->day_type === 'full_day' ? 1 : 0.5;
                $blockLeave->leave_reason = 'Substitute leave block for ' . $employee->name;
                $blockLeave->status = 'Approved'; // Auto-approved blocking
                $blockLeave->remark = 'System-generated substitute block';
                $creatorId = Auth::check() ? Auth::user()->creatorId() : $leave->created_by;
                $blockLeave->created_by = $creatorId;
                $blockLeave->save();
                
                $current->add(new \DateInterval('P1D'));
            }
        } catch (\Exception $e) {
            // Log error but don't break the flow
            \Log::error('Error creating substitute leave block: ' . $e->getMessage());
        }
    }

    protected function removeSubstituteLeaveBlock(LocalLeave $leave): void
    {
        try {
            // Delete all system-generated substitute blocks for this leave request
            LocalLeave::where('employee_id', $leave->substitute_employee_id)
                ->where('leave_type_id', $leave->leave_type_id)
                ->where('remark', 'System-generated substitute block')
                ->where('leave_reason', 'Substitute leave block for ' . ($leave->employees->name ?? ''))
                ->whereBetween('start_date', [$leave->start_date, $leave->end_date])
                ->delete();
        } catch (\Exception $e) {
            // Log error but don't break the flow
            \Log::error('Error removing substitute leave block: ' . $e->getMessage());
        }
    }

    protected function getSubstituteList($employeeId)
    {
        $employee = Employee::find($employeeId);
        if (empty($employee) || empty($employee->department_id)) {
            return [];
        }

        return Employee::where('department_id', $employee->department_id)
            ->where('created_by', \Auth::user()->creatorId())
            ->where('id', '!=', $employee->id)
            ->get()
            ->pluck('name', 'id');
    }

    protected function hasSubstituteBlock(int $employeeId, string $startDate, string $endDate, ?int $excludeLeaveId = null): bool
    {
        $query = LocalLeave::where('employee_id', $employeeId)
            ->where('remark', 'System-generated substitute block')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            });

        if (!empty($excludeLeaveId)) {
            $query->where('id', '!=', $excludeLeaveId);
        }

        return $query->exists();
    }

    public function jsoncount(Request $request)
    {
        if (empty($request->employee_id)) {
            return response()->json([]);
        }

        $date = Utility::AnnualLeaveCycle();
        $employee = Employee::find($request->employee_id);
        if (empty($employee)) {
            return response()->json([]);
        }

        $leaveTypes = LeaveType::where('created_by', '=', \Auth::user()->creatorId())->get();
        $leaveCounts = [];

        foreach ($leaveTypes as $leaveType) {
            $summary = $this->calculateLeaveBalanceSummary((int) $request->employee_id, $leaveType, $date);
            $leaveCounts[] = [
                'id' => $leaveType->id,
                'title' => $leaveType->title,
                'days' => $summary['total'],
                'total_leave' => $summary['used'],
                'pending_leave' => $summary['pending'],
                'available_leave' => $summary['available'],
                'monthly_accrual' => $summary['monthly_accrual'],
                'annual_leave' => $summary['total'],
                'credit_mode' => $summary['credit_mode'],
                'carry_forward' => $summary['carry_forward'],
                'encashable_leave' => $summary['encashable_leave'],
            ];
        }

        return $leaveCounts;
    }

    public function calender(Request $request)
    {
        $created_by = \Auth::user()->creatorId();
        $Meetings = LocalLeave::where('created_by', $created_by)->get();

        $today_date = date('m');
        $current_month_event = LocalLeave::select('id', 'start_date', 'employee_id', 'created_at')->whereRaw('MONTH(start_date)=' . $today_date)->get();

        $arrMeeting = [];

        foreach ($Meetings as $meeting) {
            $arr['id']        = $meeting['id'];
            $arr['employee_id']     = $meeting['employee_id'];
            // $arr['leave_type_id']     = date('Y-m-d', strtotime($meeting['start_date']));
        }

        $leaves = LocalLeave::where('created_by', '=', \Auth::user()->creatorId())->get();
        if (\Auth::user()->type == 'employee') {
            $user     = \Auth::user();
            $employee = Employee::where('user_id', '=', $user->id)->first();
            $leaves   = LocalLeave::where('employee_id', '=', $employee->id)->get();
        } else {
            $leaves = LocalLeave::where('created_by', '=', \Auth::user()->creatorId())->get();
        }

        return view('leave.calender', compact('leaves'));
    }

    public function get_leave_data(Request $request)
    {
        $arrayJson = [];
        if ($request->get('calender_type') == 'google_calender') {
            $type = 'leave';
            $arrayJson =  Utility::getCalendarData($type);
        } else {
            $data = LocalLeave::where('created_by', \Auth::user()->creatorId())->get();

            foreach ($data as $val) {
                $end_date = date_create($val->end_date);
                date_add($end_date, date_interval_create_from_date_string("1 days"));
                $arrayJson[] = [
                    "id" => $val->id,
                    "title" => !empty(\Auth::user()->getLeaveType($val->leave_type_id)) ? \Auth::user()->getLeaveType($val->leave_type_id)->title : '',
                    "start" => $val->start_date,
                    "end" => date_format($end_date, "Y-m-d H:i:s"),
                    "className" => $val->color,
                    "textColor" => '#FFF',
                    "allDay" => true,
                    "url" => route('leave.action', $val['id']),
                ];
            }
        }

        return $arrayJson;
    }

    protected function calculateLeaveDays(string $startDate, string $endDate, string $dayType, int $createdBy): float
    {
        $settings = Utility::settings();
        $countRule = $settings['leave_count_rule'] ?? 'working_days';
        $sandwichPolicy = ($settings['leave_sandwich_policy'] ?? 'off') === 'on';
        $holidayClubbing = ($settings['leave_holiday_clubbing'] ?? 'off') === 'on';

        if ($dayType !== 'full_day') {
            return 0.5;
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        if ($end->lt($start)) {
            return 0;
        }

        if ($countRule === 'calendar_days') {
            return $start->diffInDays($end) + 1;
        }

        $weeklyOffDays = array_filter(
            array_map('trim', explode(',', (string) ($settings['weekly_off_days'] ?? '0'))),
            static fn($value) => $value !== ''
        );
        $weeklyOffDays = array_map('intval', $weeklyOffDays);

        $holidayDates = [];
        $holidays = Holiday::where('created_by', $createdBy)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($query) use ($startDate, $endDate) {
                        $query->where('start_date', '<=', $startDate)->where('end_date', '>=', $endDate);
                    });
            })
            ->get();

        foreach ($holidays as $holiday) {
            $hStart = Carbon::parse($holiday->start_date)->startOfDay();
            $hEnd = Carbon::parse($holiday->end_date)->startOfDay();
            for ($date = $hStart->copy(); $date->lte($hEnd); $date->addDay()) {
                $holidayDates[$date->toDateString()] = true;
            }
        }

        if ($sandwichPolicy) {
            return $start->diffInDays($end) + 1;
        }

        $total = 0;
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateKey = $date->toDateString();

            if (in_array($date->dayOfWeek, $weeklyOffDays, true)) {
                continue;
            }

            if (!$holidayClubbing && isset($holidayDates[$dateKey])) {
                continue;
            }

            $total++;
        }

        return $total;
    }

    protected function getLeaveAllowance(LeaveType $leaveType, ?Employee $employee, array $cycleDates): float
    {
        $allowance = $this->getLeaveAllowanceDetails($leaveType, $employee, $cycleDates);

        return $allowance['allowed'];
    }

    protected function getLeaveAllowanceDetails(LeaveType $leaveType, ?Employee $employee, array $cycleDates, bool $includeCarryForward = true): array
    {
        $settings = Utility::settings();

        // NEW: per-type credit frequency from policy matrix
        // OLD (kept for fallback when policy_code / credit_frequency not set):
        // $creditMode = $settings['leave_credit_mode'] ?? 'lump_sum';
        $typeFrequency = $leaveType->credit_frequency ?? null;
        if ($typeFrequency === 'annual') {
            $creditMode = 'lump_sum';
        } elseif (in_array($typeFrequency, ['monthly', 'monthly_cap'], true)) {
            $creditMode = 'monthly';
        } elseif ($typeFrequency === 'earned') {
            $creditMode = 'earned';
        } else {
            $creditMode = $settings['leave_credit_mode'] ?? 'lump_sum';
        }

        // As-earned types (comp-off) have no normal annual quota
        if ($creditMode === 'earned' || !empty($leaveType->is_as_earned)) {
            return [
                'allowed' => 0,
                'total_annual' => 0,
                'monthly_accrual' => 0,
                'eligible_months' => 0,
                'credited_months' => 0,
                'credit_mode' => 'earned',
                'carry_forward' => 0,
            ];
        }

        $annualCredit = (float) ($leaveType->annual_credit ?? 0);
        if ($annualCredit <= 0) {
            $annualCredit = (float) ($leaveType->days ?? 0);
        }

        $monthlyAccrual = $annualCredit > 0
            ? round($annualCredit / 12, 2)
            : (float) ($leaveType->monthly_credit ?? 0);

        // WFH: monthly pool of monthly_limit (default 2), not annual/12 from days
        if ($typeFrequency === 'monthly_cap' && !empty($leaveType->monthly_limit)) {
            $monthlyAccrual = (float) $leaveType->monthly_limit;
            // Annual tracking still uses annual_credit / days for reports
        }

        $normalizedCycle = $this->normalizeCycleDates($cycleDates);
        $cycleStart = Carbon::parse($normalizedCycle['start_date'])->startOfMonth();
        $cycleEnd = Carbon::parse($normalizedCycle['end_date'])->startOfMonth();

        $accrualStart = $cycleStart->copy();
        // NEW: prorata only when leave type allows it (default true)
        // OLD: always prorated from join month
        $useProrata = !isset($leaveType->is_prorata) || (bool) $leaveType->is_prorata;
        if ($useProrata && !empty($employee) && !empty($employee->company_doj)) {
            $joinMonth = Carbon::parse($employee->company_doj)->startOfMonth();
            if ($joinMonth->greaterThan($accrualStart)) {
                $accrualStart = $joinMonth;
            }
        }

        if ($accrualStart->greaterThan($cycleEnd)) {
            $eligibleMonths = 0;
        } else {
            $eligibleMonths = $accrualStart->diffInMonths($cycleEnd) + 1;
        }

        $asOfMonth = Carbon::now()->startOfMonth();
        if ($asOfMonth->greaterThan($cycleEnd)) {
            $asOfMonth = $cycleEnd->copy();
        }

        // Calculate credited months based on calendar months from cycle start to now
        if ($eligibleMonths <= 0 || $asOfMonth->lessThan($accrualStart)) {
            $creditedMonths = 0;
        } else {
            // Count months from accrualStart to asOfMonth (inclusive)
            $creditedMonths = min($eligibleMonths, $accrualStart->diffInMonths($asOfMonth) + 1);
        }

        // For Lump Sum: eligible for full year; For Monthly: eligible for months worked
        if ($creditMode === 'lump_sum') {
            // Lump sum gets paid on cycle start (or first month), but only if they worked that month
            $proratedTotal = round($monthlyAccrual * $eligibleMonths, 2);
            $allowed = $proratedTotal; // Full annual amount
        } else {
            // Monthly accrual: gets paid each month they work
            $accruedToDate = round($monthlyAccrual * $creditedMonths, 2);
            $allowed = min($accruedToDate, round($monthlyAccrual * $eligibleMonths, 2));
        }

        // monthly_cap (WFH): available is current month's limit residual handled in validate; allowance still accrues
        if ($typeFrequency === 'monthly_cap' && !empty($leaveType->monthly_limit)) {
            $allowed = (float) $leaveType->monthly_limit;
            $proratedTotal = (float) ($leaveType->annual_credit ?? ($leaveType->monthly_limit * 12));
        } else {
            $proratedTotal = round($monthlyAccrual * $eligibleMonths, 2);
        }

        if (!empty($employee) && $this->isEmployeeInProbation($employee)) {
            if (($settings['probation_leave_accumulation'] ?? 'during') === 'after') {
                $allowed = 0;
            }
        }

        $carryForward = 0.0;
        // NEW: prefer per-type is_carry_forward
        // OLD (company-wide only):
        // if ($includeCarryForward && ($settings['leave_carry_forward'] ?? 'off') === 'on') { ... }
        $typeAllowsCf = isset($leaveType->is_carry_forward)
            ? ((int) $leaveType->is_carry_forward === 1)
            : (($settings['leave_carry_forward'] ?? 'off') === 'on');

        if ($includeCarryForward && $typeAllowsCf) {
            $previousCycle = $this->getPreviousCycleDates($cycleDates);
            if (!empty($previousCycle)) {
                $previousAllowance = $this->getLeaveAllowanceDetails($leaveType, $employee, $previousCycle, false);
                $previousUsage = $this->getLeaveUsageByCycle($employee ? (int) $employee->id : null, (int) $leaveType->id, $previousCycle);
                $previousAvailable = max(0, round(($previousAllowance['allowed'] ?? 0) - ($previousUsage['used'] ?? 0) - ($previousUsage['pending'] ?? 0), 2));

                // Prefer type max_carry_forward, else company setting
                $carryForwardMax = (float) ($leaveType->max_carry_forward ?? 0);
                if ($carryForwardMax <= 0) {
                    $carryForwardMax = (float) ($settings['leave_carry_forward_max'] ?? 0);
                }
                $carryForward = $carryForwardMax > 0 ? min($previousAvailable, $carryForwardMax) : $previousAvailable;
                $allowed = round($allowed + $carryForward, 2);
            }
        }

        return [
            'allowed' => max(0, round($allowed, 2)),
            'total_annual' => max(0, round($proratedTotal, 2)),
            'monthly_accrual' => max(0, round($monthlyAccrual, 2)),
            'eligible_months' => $eligibleMonths,
            'credited_months' => $creditedMonths,
            'credit_mode' => $creditMode,
            'carry_forward' => max(0, round($carryForward, 2)),
        ];
    }

    protected function calculateLeaveBalanceSummary(int $employeeId, LeaveType $leaveType, array $cycleDates): array
    {
        $employee = Employee::find($employeeId);
        $allowance = $this->getLeaveAllowanceDetails($leaveType, $employee, $cycleDates);
        $usage = $this->getLeaveUsageByCycle($employeeId, (int) $leaveType->id, $cycleDates);

        $available = $this->calculateAvailableLeaveByCreditMode($allowance, $usage);

        return [
            'total' => $allowance['total_annual'],
            'monthly_accrual' => $allowance['monthly_accrual'],
            'used' => $usage['used'],
            'pending' => $usage['pending'],
            'available' => $available,
            'credit_mode' => $allowance['credit_mode'] ?? 'lump_sum',
            'carry_forward' => $allowance['carry_forward'] ?? 0,
            'encashable_leave' => $this->calculateEncashableLeave($available, $leaveType),
        ];
    }

    protected function calculateAvailableLeaveByCreditMode(array $allowance, array $usage): float
    {
        $creditMode = $allowance['credit_mode'] ?? (Utility::settings()['leave_credit_mode'] ?? 'lump_sum');
        $allowed = (float) ($allowance['allowed'] ?? 0);
        $used = (float) ($usage['used'] ?? 0);
        $pending = (float) ($usage['pending'] ?? 0);

        return max(0, round($allowed - $used - $pending, 2));
    }

    protected function calculateEncashableLeave(float $available, ?LeaveType $leaveType = null): float
    {
        $settings = Utility::settings();

        // NEW: per-type encash (e.g. PL on exit max 30)
        // OLD company-wide only:
        // if (($settings['leave_encashment'] ?? 'off') !== 'on') { return 0; }
        if ($leaveType && isset($leaveType->is_encashable)) {
            if ((int) $leaveType->is_encashable !== 1) {
                return 0;
            }
            $max = (float) ($leaveType->max_encash_on_exit ?? 0);
            if ($max > 0) {
                return max(0, round(min($available, $max), 2));
            }
            return max(0, round($available, 2));
        }

        if (($settings['leave_encashment'] ?? 'off') !== 'on') {
            return 0;
        }

        $minBalance = (float) ($settings['leave_encashment_min_balance'] ?? 0);
        return max(0, round($available - $minBalance, 2));
    }

    protected function getPreviousCycleDates(array $cycleDates): ?array
    {
        if (empty($cycleDates['start_date']) || empty($cycleDates['end_date'])) {
            return null;
        }

        return [
            'start_date' => Carbon::parse($cycleDates['start_date'])->subYear()->toDateString(),
            'end_date' => Carbon::parse($cycleDates['end_date'])->subYear()->toDateString(),
        ];
    }

    protected function getLeaveUsageByCycle(?int $employeeId, int $leaveTypeId, array $cycleDates, ?int $excludeLeaveId = null): array
    {
        $normalizedCycle = $this->normalizeCycleDates($cycleDates);

        $query = LocalLeave::where('leave_type_id', $leaveTypeId)
            ->where(function ($q) {
                $q->whereNull('remark')
                    ->orWhere('remark', '!=', 'System-generated substitute block');
            })
            ->whereBetween('start_date', [$normalizedCycle['start_date'], $normalizedCycle['end_date']]);

        if (!empty($employeeId)) {
            $query->where('employee_id', $employeeId);
        } else {
            $query->where('created_by', \Auth::user()->creatorId());
        }

        if (!empty($excludeLeaveId)) {
            $query->where('id', '!=', $excludeLeaveId);
        }

        $usedLeaves = (clone $query)
            ->where('status', 'Approved')
            ->get(['start_date', 'end_date', 'day_type', 'created_by']);

        $pendingLeaves = (clone $query)
            ->where('status', 'Pending')
            ->get(['start_date', 'end_date', 'day_type', 'created_by']);

        $used = (float) $usedLeaves->sum(function ($leave) {
            return $this->calculateLeaveDays(
                $leave->start_date,
                $leave->end_date,
                $leave->day_type ?? 'full_day',
                (int) ($leave->created_by ?? \Auth::user()->creatorId())
            );
        });

        $pending = (float) $pendingLeaves->sum(function ($leave) {
            return $this->calculateLeaveDays(
                $leave->start_date,
                $leave->end_date,
                $leave->day_type ?? 'full_day',
                (int) ($leave->created_by ?? \Auth::user()->creatorId())
            );
        });

        return [
            'used' => round($used, 2),
            'pending' => round($pending, 2),
        ];
    }

    protected function normalizeCycleDates(array $cycleDates): array
    {
        $startDate = Carbon::parse($cycleDates['start_date'])->addDay()->toDateString();
        $endDate = Carbon::parse($cycleDates['end_date'])->subDay()->toDateString();

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    protected function isEmployeeInProbation(?Employee $employee): bool
    {
        if (empty($employee) || empty($employee->company_doj)) {
            return false;
        }

        $settings = Utility::settings();
        $probationMonths = (int) ($settings['probation_months'] ?? 0);
        if ($probationMonths <= 0) {
            return false;
        }

        $doj = Carbon::parse($employee->company_doj)->startOfDay();
        $probationEnd = $doj->copy()->addMonths($probationMonths);

        return Carbon::now()->lt($probationEnd);
    }

    protected function getProbationLeaveNotAllowedMessage(?Employee $employee): string
    {
        if (empty($employee) || empty($employee->company_doj)) {
            return __('You cannot apply for leave until your probation period is completed.');
        }

        $settings = Utility::settings();
        $probationMonths = (int) ($settings['probation_months'] ?? 0);

        if ($probationMonths <= 0) {
            return __('You cannot apply for leave until your probation period is completed.');
        }

        $probationEndDate = Carbon::parse($employee->company_doj)
            ->addMonths($probationMonths)
            ->toDateString();

        return __('You cannot apply for leave until your probation period is completed. You can apply after :date.', ['date' => $probationEndDate]);
    }

    /**
     * Calculate professional period (years, months, days since joining)
     * Returns array with professional_years, professional_months, professional_days
     */
    protected function calculateProfessionalPeriod(?Employee $employee): array
    {
        if (empty($employee) || empty($employee->company_doj)) {
            return [
                'professional_years' => 0,
                'professional_months' => 0,
                'professional_days' => 0,
            ];
        }

        $doj = Carbon::parse($employee->company_doj)->startOfDay();
        $now = Carbon::now()->startOfDay();

        // Calculate total days since joining
        $totalDays = $doj->diffInDays($now);

        // Calculate years, months, and remaining days
        $years = $now->copy()->subYears(intval($now->diffInYears($doj)))->diffInYears($doj);
        if ($years < 0) $years = 0;

        $tempDate = $doj->copy()->addYears($years);
        $months = $tempDate->diffInMonths($now);
        if ($months < 0) $months = 0;

        $tempDate->addMonths($months);
        $days = $tempDate->diffInDays($now);
        if ($days < 0) $days = 0;

        return [
            'professional_years' => $years,
            'professional_months' => $months,
            'professional_days' => $totalDays,
        ];
    }

    /**
     * Get available compensatory leaves for claim
     */
    public function claimCompensatoryLeaveView()
    {
        if (\Auth::user()->can('Create Leave')) {
            if (\Auth::user()->type == 'employee') {
                $employee = Employee::where('user_id', '=', \Auth::user()->id)->first();
                if (!$employee) {
                    return response()->json(['error' => __('Employee not found.')], 401);
                }

                // Get available compensatory leaves
                $compensatoryLeaves = \App\Models\CompensatoryLeave::where('employee_id', $employee->id)
                    ->where('status', 'earned')
                    ->where(function ($q) {
                        $q->whereNull('expiry_date')
                            ->orWhere('expiry_date', '>=', now()->startOfDay());
                    })
                    ->get();

                return view('leave.claim_compensatory', compact('compensatoryLeaves', 'employee'));
            } else {
                return response()->json(['error' => __('Only employees can claim compensatory leaves.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    /**
     * Claim compensatory leave
     */
    public function storeCompensatoryLeaveClaim(Request $request)
    {
        if (\Auth::user()->can('Create Leave')) {
            if (\Auth::user()->type != 'employee') {
                return redirect()->back()->with('error', __('Only employees can claim compensatory leaves.'));
            }

            $validator = \Validator::make(
                $request->all(),
                [
                    'compensatory_leave_ids' => 'required|array|min:1',
                    'compensatory_leave_ids.*' => 'integer|exists:compensatory_leaves,id',
                    'start_date' => 'required|date',
                    'claim_days' => 'required|numeric|min:0.5',
                ]
            );

            if ($validator->fails()) {
                return redirect()->back()->with('error', $validator->getMessageBag()->first());
            }

            $employee = Employee::where('user_id', '=', \Auth::user()->id)->first();
            if (!$employee) {
                return redirect()->back()->with('error', __('Employee not found.'));
            }

            // Verify compensatory leaves belong to employee
            $compLeaves = \App\Models\CompensatoryLeave::whereIn('id', $request->compensatory_leave_ids)
                ->where('employee_id', $employee->id)
                ->where('status', 'earned')
                ->get();

            if ($compLeaves->count() !== count($request->compensatory_leave_ids)) {
                return redirect()->back()->with('error', __('Invalid compensatory leaves selected.'));
            }

            // Verify total days
            $totalAvailable = $compLeaves->sum('days');
            if ($request->claim_days > $totalAvailable) {
                return redirect()->back()->with('error', __('Insufficient compensatory leave days.'));
            }

            try {
                // Create leave record for compensatory leave
                $leave = new LocalLeave();
                $leave->employee_id = $employee->id;
                
                // Create a special leave type for compensatory or use existing
                $leaveType = LeaveType::where('created_by', \Auth::user()->creatorId())
                    ->where('title', 'like', '%Compensatory%')
                    ->first();

                if (!$leaveType) {
                    $leaveType = LeaveType::where('created_by', \Auth::user()->creatorId())
                        ->first();
                }

                if (!$leaveType) {
                    return redirect()->back()->with('error', __('No leave type configured.'));
                }

                $leave->leave_type_id = $leaveType->id;
                $leave->applied_on = date('Y-m-d');
                $leave->start_date = $request->start_date;
                $leave->end_date = $request->start_date;
                $leave->day_type = 'full_day';
                $leave->total_leave_days = $request->claim_days;
                $leave->leave_reason = 'Compensatory leave claim';
                $leave->status = 'Pending';
                $leave->is_compensatory = true;
                $leave->compensatory_leave_id = $compLeaves->first()->id;
                $leave->substitute_employee_id = null;
                $leave->substitute_status = 'Accepted';
                $leave->created_by = \Auth::user()->creatorId();
                $leave->save();

                // Update compensatory leave status to claimed
                $compLeaves->each(function ($compLeave) {
                    $compLeave->status = 'claimed';
                    $compLeave->save();
                });

                // Notify manager
                $this->notifyManagerOfLeaveRequest($leave);

                return redirect()->route('leave.index')->with('success', __('Compensatory leave claim submitted successfully. Waiting for manager approval.'));
            } catch (\Exception $e) {
                \Log::error('Error claiming compensatory leave: ' . $e->getMessage());
                return redirect()->back()->with('error', __('Failed to claim compensatory leave. Please try again.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Award compensatory leave to employee (manager/admin function)
     */
    public function awardCompensatoryLeaveView()
    {
        if (!\Auth::user()->can('Manage Leave') || \Auth::user()->type== 'employee') {
            return response()->json(['error' => __('Permission denied.')], 401);
        }

        $employees = Employee::where('created_by', \Auth::user()->creatorId())
            ->get()
            ->pluck('name', 'id');

        return view('leave.award_compensatory', compact('employees'));
    }

    /**
     * Store awarded compensatory leave
     */
    public function storeAwardCompensatoryLeave(Request $request)
    {
        if (!\Auth::user()->can('Manage Leave') || \Auth::user()->type == 'employee') {
            return response()->json(['error' => __('Permission denied.')], 401);
        }

        $validator = \Validator::make(
            $request->all(),
            [
                'employee_id' => 'required|integer|exists:employees,id',
                // OLD: 'days' => 'required|numeric|min:0.5',
                'days' => 'nullable|numeric|min:0.5',
                'hours' => 'nullable|numeric|min:0',
                'earned_date' => 'required|date',
                'reason' => 'required|string|max:500',
            ]
        );

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        $employee = Employee::find($request->employee_id);
        if ($employee->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        // NEW matrix: 4 hrs = 1/2 day, 8 hrs = Full day (falls back to days if provided)
        $days = null;
        if ($request->filled('hours')) {
            $days = LeavePolicyService::hoursToCompOffDays((float) $request->hours);
            if ($days <= 0) {
                return redirect()->back()->with('error', __('Comp-off requires at least 4 hours (half day) or 8 hours (full day).'));
            }
        } elseif ($request->filled('days')) {
            $days = (float) $request->days;
        } else {
            return redirect()->back()->with('error', __('Please provide hours (preferred) or days for compensatory leave.'));
        }

        // Eligibility: Intern + Full time
        $compType = LeaveType::where('created_by', \Auth::user()->creatorId())
            ->where('policy_code', 'comp_off')
            ->first();
        if ($compType) {
            $eligError = $this->leavePolicy()->validateEligibility($compType, $employee);
            if ($eligError) {
                return redirect()->back()->with('error', $eligError);
            }
        }

        try {
            $settings = Utility::settings();
            $compOffValidity = $settings['compensatory_leave_validity'] ?? 30;
            $expiryDate = \Carbon\Carbon::parse($request->earned_date)->addDays($compOffValidity);

            $compLeave = new \App\Models\CompensatoryLeave();
            $compLeave->employee_id = $request->employee_id;
            $compLeave->days = $days;
            $compLeave->earned_date = $request->earned_date;
            $compLeave->expiry_date = $expiryDate;
            $compLeave->reason = $request->reason;
            $compLeave->status = 'earned';
            $compLeave->notes = $request->notes ?? null;
            $compLeave->created_by = \Auth::user()->creatorId();
            $compLeave->save();

            // Send email to employee
            if ($employee->email) {
                try {
                    $uArr = [
                        'employee_name' => $employee->name,
                        'comp_days' => $compLeave->days,
                        'earned_date' => \Auth::user()->dateFormat($compLeave->earned_date),
                        'expiry_date' => \Auth::user()->dateFormat($compLeave->expiry_date),
                        'reason' => $compLeave->reason,
                    ];
                    Utility::sendEmailTemplate('compensatory_leave_awarded', [$employee->email], $uArr);
                } catch (\Exception $e) {
                    \Log::error('Failed to send comp leave awarded email: ' . $e->getMessage());
                }
            }

            return redirect()->back()->with('success', __('Compensatory leave awarded successfully to ') . $employee->name . '.');
        } catch (\Exception $e) {
            \Log::error('Error awarding compensatory leave: ' . $e->getMessage());
            return redirect()->back()->with('error', __('Failed to award compensatory leave. Please try again.'));
        }
    }

    /**
     * Get pending leaves from subordinates for manager notification
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPendingSubordinateLeaves(Request $request)
    {
        try {
            $user = \Auth::user();
            $pendingLeaves = collect();

            // Get the manager's employee record
            $managerEmployee = Employee::where('user_id', $user->id)->first();

            if ($managerEmployee) {
                // Find all employees who report to this manager
                $subordinateIds = Employee::where('reporting_manager_id', $managerEmployee->id)
                    ->pluck('id')
                    ->toArray();

                if (!empty($subordinateIds)) {
                    $pendingLeaves = LocalLeave::with(['employees', 'leaveType'])
                        ->whereIn('employee_id', $subordinateIds)
                        ->where('status', 'Pending')
                        ->orderBy('created_at', 'desc')
                        ->get();
                }
            }

            // For admin/company users, show all pending leaves
            if (in_array($user->type, ['company', 'hr'])) {
                $pendingLeaves = LocalLeave::with(['employees', 'leaveType'])
                    ->where('created_by', $user->creatorId())
                    ->where('status', 'Pending')
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            // Check for last known count to detect new leaves
            $lastKnownCount = (int) $request->input('last_count', 0);
            $currentCount = $pendingLeaves->count();
            $hasNew = $currentCount > $lastKnownCount;

            // Build HTML for dropdown
            $html = '';
            if ($pendingLeaves->isEmpty()) {
                $html = '<div class="px-3 py-2 text-muted">' . __('No pending leave requests.') . '</div>';
            } else {
                foreach ($pendingLeaves as $leave) {
                    $employeeName = optional($leave->employees)->name ?? 'Unknown';
                    $leaveTypeName = optional($leave->leaveType)->title ?? 'Leave';
                    $startDate = $leave->start_date;
                    $endDate = $leave->end_date;
                    $totalDays = $leave->total_leave_days ?? '-';
                    $reason = Str::limit($leave->leave_reason ?? '', 50);

                    $html .= '<div class="px-3 py-3 border-bottom leave-request-item" data-leave-id="' . $leave->id . '">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="w-100">
                                <div class="fw-bold">' . e($employeeName) . '</div>
                                <div class="text-muted small">' . e($leaveTypeName) . ' • ' . e((string) $totalDays) . ' ' . __('days') . '</div>
                                <div class="text-muted small">' . e($startDate) . ' - ' . e($endDate) . '</div>
                                <div class="text-muted small fst-italic">' . e($reason) . '</div>
                            </div>
                        </div>
                        <div class="mt-2 d-flex flex-wrap gap-1 leave-request-actions">
                            <button type="button" class="btn btn-sm btn-success leave-action-btn leave-accept-btn" data-leave-id="' . $leave->id . '" data-action="Approved">
                                <i class="ti ti-check"></i> ' . __('Accept') . '
                            </button>
                            <button type="button" class="btn btn-sm btn-danger leave-action-btn leave-reject-btn" data-leave-id="' . $leave->id . '" data-action="Reject">
                                <i class="ti ti-x"></i> ' . __('Reject') . '
                            </button>
                            <a href="' . route('leave.action', $leave->id) . '" class="btn btn-sm btn-outline-primary leave-open-btn">
                                <i class="ti ti-eye"></i> ' . __('Open') . '
                            </a>
                        </div>
                    </div>';
                }
            }

            return response()->json([
                'success' => true,
                'count' => $currentCount,
                'has_new' => $hasNew,
                'html' => $html,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching pending leaves: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'count' => 0,
                'has_new' => false,
                'html' => '<div class="px-3 py-2 text-danger">' . __('Error loading notifications.') . '</div>',
            ], 500);
        }
    }

    /**
     * AJAX endpoint to approve or reject a leave
     * @return \Illuminate\Http\JsonResponse
     */
    public function approveLeaveAjax(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'leave_id' => 'required|integer|exists:leaves,id',
                'status' => 'required|in:Approved,Reject',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => $validator->errors()->first(),
                ], 422);
            }

            $leave = LocalLeave::find($request->leave_id);

            if (!$leave) {
                return response()->json([
                    'success' => false,
                    'error' => __('Leave not found.'),
                ], 404);
            }

            if ($leave->status !== 'Pending') {
                return response()->json([
                    'success' => false,
                    'error' => __('Leave has already been processed.'),
                ], 400);
            }

            $leaveType = LeaveType::find($leave->leave_type_id);
            $titleLower = strtolower($leaveType->title ?? '');
            $isVacationLeave = strpos($titleLower, 'vacation') !== false || strpos($titleLower, 'vaction') !== false;

            $oldStatus = $leave->status;
            $leave->status = $request->status;

            if ($leave->status == 'Approved') {
                $total_leave_days = $this->calculateLeaveDays(
                    $leave->start_date,
                    $leave->end_date,
                    $leave->day_type ?? 'full_day',
                    (int) ($leave->created_by ?? \Auth::user()->creatorId())
                );
                $leave->total_leave_days = $total_leave_days;
            } elseif ($leave->status == 'Reject') {
                $this->removeSubstituteLeaveBlock($leave);
            }

            $leave->save();

            // Send email notification
            $settings = Utility::settings();
            if (($settings['leave_status'] ?? 0) == 1) {
                $employee = Employee::find($leave->employee_id);
                if ($employee && $employee->email) {
                    $uArr = [
                        'leave_email' => $employee->email,
                        'leave_status_name' => $employee->name,
                        'leave_status' => $request->status,
                        'leave_reason' => $leave->leave_reason,
                        'leave_start_date' => $leave->start_date,
                        'leave_end_date' => $leave->end_date,
                        'total_leave_days' => $leave->total_leave_days,
                    ];
                    Utility::sendEmailTemplate('leave_status', [$employee->email], $uArr);
                }
            }

            // Send Twilio notification
            $setting = Utility::settings(\Auth::user()->creatorId());
            $emp = Employee::find($leave->employee_id);
            if (isset($setting['twilio_leave_approve_notification']) && $setting['twilio_leave_approve_notification'] == 1) {
                if (!empty($emp->phone)) {
                    $uArr = ['leave_status' => $leave->status];
                    Utility::send_twilio_msg($emp->phone, 'leave_approve_reject', $uArr);
                }
            }

            return response()->json([
                'success' => true,
                'message' => __('Leave has been :status successfully.', ['status' => strtolower($request->status)]),
                'new_status' => $leave->status,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error approving leave: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => __('Failed to process leave. Please try again.'),
            ], 500);
        }
    }
}

