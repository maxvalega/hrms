<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Leave as LocalLeave;
use App\Models\LeaveType;
use App\Models\Holiday;
use App\Mail\LeaveActionSend;
use App\Mail\LeaveSubstituteRequest;
use App\Mail\LeaveManagerRequest;
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

    protected function isSpectalPortal(): bool
    {
        return LeavePolicyService::isSpectalPortal();
    }

    protected function leaveCycleDates(): array
    {
        if ($this->isSpectalPortal()) {
            return LeavePolicyService::spectalCycleDates();
        }

        return Utility::AnnualLeaveCycle();
    }

    protected function employmentStatusMeta(?Employee $employee): array
    {
        $typeCode = LeavePolicyService::employeeTypeCode($employee);
        $onProbation = $this->isEmployeeInProbation($employee);

        if ($typeCode === 'intern') {
            $label = __('Intern');
            $badge = 'warning';
        } elseif ($typeCode === 'consultant') {
            $label = $onProbation ? __('Consultant · Probation') : __('Consultant');
            $badge = $onProbation ? 'info' : 'success';
        } elseif ($onProbation) {
            $label = __('Probation');
            $badge = 'info';
        } else {
            $label = __('Permanent');
            $badge = 'success';
        }

        return [
            'type_code' => $typeCode,
            'on_probation' => $onProbation,
            'label' => $label,
            'badge' => $badge,
            'can_apply' => !$onProbation || \Auth::user()->type !== 'employee',
            'note' => $onProbation
                ? __('During probation you can view Sick Leave and Comp-off. Leave applications are restricted until probation ends.')
                : null,
        ];
    }

    public function index()
    {

        if (\Auth::user()->can('Manage Leave')) {
            $leaveBalance = [];
            $showEmployeeColumn = \Auth::user()->type != 'employee';
            $date = $this->leaveCycleDates();
            $settings = Utility::settings();
            $leavePolicy = [
                'carry_forward' => ($settings['leave_carry_forward'] ?? 'off') === 'on',
                'carry_forward_max' => (float) ($settings['leave_carry_forward_max'] ?? 0),
                'encashment' => ($settings['leave_encashment'] ?? 'off') === 'on',
                'encashment_min_balance' => (float) ($settings['leave_encashment_min_balance'] ?? 0),
            ];
            $employmentStatus = null;
            $isSpectal = $this->isSpectalPortal();

            if (\Auth::user()->type == 'employee') {
                $user     = \Auth::user();
                $employee = Employee::where('user_id', '=', $user->id)->first();
                $employmentStatus = $this->employmentStatusMeta($employee);

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

                $leaveTypes = LeaveType::where('created_by', '=', \Auth::user()->creatorId())->get();
                foreach ($leaveTypes as $leaveType) {
                    if ($isSpectal && !$this->leavePolicy()->shouldShowOnSpectalBalance($leaveType, $employee)) {
                        continue;
                    }

                    $summary = $this->calculateLeaveBalanceSummary((int) $employee->id, $leaveType, $date);

                    $leaveBalance[] = [
                        'leave_type' => $leaveType->title,
                        'policy_code' => LeavePolicyService::resolvePolicyCode($leaveType),
                        'total' => $summary['total'],
                        'monthly_accrual' => $summary['monthly_accrual'],
                        'used' => $summary['used'],
                        'pending' => $summary['pending'],
                        'available' => $summary['available'],
                        'credit_mode' => $summary['credit_mode'] ?? 'lump_sum',
                        'carry_forward' => $summary['carry_forward'] ?? 0,
                        'encashable_leave' => $summary['encashable_leave'] ?? 0,
                        'opening_balance' => $summary['opening_balance'] ?? 0,
                        'accrued_to_date' => $summary['accrued_to_date'] ?? 0,
                        'note' => $summary['note'] ?? null,
                    ];
                }
            } else {
                $leaves = LocalLeave::where('created_by', '=', \Auth::user()->creatorId())
                    ->with(['employees', 'leaveType'])
                    ->orderByRaw("CASE WHEN status = 'Pending' THEN 0 WHEN status = 'Approved' THEN 1 WHEN status = 'Reject' THEN 2 ELSE 3 END")
                    ->orderByDesc('applied_on')
                    ->orderByDesc('id')
                    ->get();

                // Spectal HR/admin: show personal balances if they have an employee profile,
                // otherwise (or when picking someone) preview that employee's balances.
                if ($isSpectal) {
                    $selfEmployee = Employee::where('user_id', \Auth::id())
                        ->where('created_by', \Auth::user()->creatorId())
                        ->first();

                    $previewEmployeeId = (int) request()->get('balance_employee_id', 0);
                    if ($previewEmployeeId <= 0 && $selfEmployee) {
                        $previewEmployeeId = (int) $selfEmployee->id;
                    }

                    $previewEmployees = Employee::where('created_by', \Auth::user()->creatorId())
                        ->orderBy('name')
                        ->get()
                        ->pluck('name', 'id');

                    $previewEmployee = null;
                    if ($previewEmployeeId > 0) {
                        $previewEmployee = Employee::where('created_by', \Auth::user()->creatorId())
                            ->where('id', $previewEmployeeId)
                            ->first();
                    }

                    if ($previewEmployee) {
                        $employmentStatus = $this->employmentStatusMeta($previewEmployee);
                        if ($selfEmployee && (int) $previewEmployee->id === (int) $selfEmployee->id) {
                            $employmentStatus['note'] = __('Your personal leave balance dashboard.');
                        } else {
                            $employmentStatus['note'] = __('Viewing leave balances for :name.', ['name' => $previewEmployee->name]);
                        }

                        $leaveTypes = LeaveType::where('created_by', '=', \Auth::user()->creatorId())->get();
                        foreach ($leaveTypes as $leaveType) {
                            if (!$this->leavePolicy()->shouldShowOnSpectalBalance($leaveType, $previewEmployee)) {
                                continue;
                            }
                            $summary = $this->calculateLeaveBalanceSummary((int) $previewEmployee->id, $leaveType, $date);
                            $leaveBalance[] = [
                                'leave_type' => $leaveType->title,
                                'policy_code' => LeavePolicyService::resolvePolicyCode($leaveType),
                                'total' => $summary['total'],
                                'monthly_accrual' => $summary['monthly_accrual'],
                                'used' => $summary['used'],
                                'pending' => $summary['pending'],
                                'available' => $summary['available'],
                                'credit_mode' => $summary['credit_mode'] ?? 'lump_sum',
                                'carry_forward' => $summary['carry_forward'] ?? 0,
                                'encashable_leave' => $summary['encashable_leave'] ?? 0,
                                'opening_balance' => $summary['opening_balance'] ?? 0,
                                'accrued_to_date' => $summary['accrued_to_date'] ?? 0,
                                'note' => $summary['note'] ?? null,
                            ];
                        }
                    }

                    view()->share('previewEmployees', $previewEmployees);
                    view()->share('previewEmployeeId', $previewEmployeeId);
                    view()->share('selfEmployeeId', $selfEmployee ? (int) $selfEmployee->id : 0);
                } elseif (!$isSpectal) {
                    $leaveTypes = LeaveType::where('created_by', '=', \Auth::user()->creatorId())->get();
                    foreach ($leaveTypes as $leaveType) {
                        $summary = $this->calculateLeaveBalanceSummary(0, $leaveType, $date, true);

                        $leaveBalance[] = [
                            'leave_type' => $leaveType->title,
                            'policy_code' => LeavePolicyService::resolvePolicyCode($leaveType),
                            'total' => $summary['total'],
                            'monthly_accrual' => $summary['monthly_accrual'],
                            'used' => $summary['used'],
                            'pending' => $summary['pending'],
                            'available' => $summary['available'],
                            'credit_mode' => $summary['credit_mode'] ?? 'lump_sum',
                            'carry_forward' => $summary['carry_forward'] ?? 0,
                            'encashable_leave' => $summary['encashable_leave'] ?? 0,
                            'opening_balance' => $summary['opening_balance'] ?? 0,
                            'accrued_to_date' => $summary['accrued_to_date'] ?? 0,
                            'note' => $summary['note'] ?? null,
                        ];
                    }
                }
            }

            // Attach employment status label for table / filters (Spectal)
            if ($isSpectal) {
                $leaves->each(function ($leave) {
                    $leave->employment_status_meta = $this->employmentStatusMeta($leave->employees);
                });
            }

            return view('leave.index', compact(
                'leaves',
                'leaveBalance',
                'date',
                'leavePolicy',
                'showEmployeeColumn',
                'employmentStatus',
                'isSpectal'
            ));
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
            if ($this->isSpectalPortal() && Auth::user()->type == 'employee' && !empty($employees)) {
                $leavetypes = $leavetypes->filter(function ($lt) use ($employees) {
                    return $this->leavePolicy()->shouldShowOnSpectalBalance($lt, $employees);
                })->values();
            }

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
            $date = $this->leaveCycleDates();

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

            if ($spectalError = $this->leavePolicy()->validateSpectalApplication($leave_type, $employee, $request->start_date)) {
                return redirect()->back()->with('error', $spectalError);
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

            // For monthly_cap (WFH), compare against this month's usage
            $policyCode = LeavePolicyService::resolvePolicyCode($leave_type);
            if (($leave_type->credit_frequency === 'monthly_cap') || $policyCode === 'wfh') {
                $usage = $this->getLeaveUsageForCurrentMonth((int) $employee->id, (int) $leave_type->id, $date);
            } else {
                $usage = $this->getLeaveUsageByCycle((int) $employee->id, (int) $leave_type->id, $date);
            }
            $leaves_used = $usage['used'];
            $leaves_pending = $usage['pending'];

            if ($this->hasSubstituteBlock((int) $employee->id, $request->start_date, $request->end_date)) {
                return redirect()->back()->with('error', __('You are assigned as a substitute for these dates and cannot apply leave.'));
            }

            $allowance = $this->getLeaveAllowanceDetails($leave_type, $employee, $date);
            $available = max(0, round(((float) ($allowance['display_total'] ?? $allowance['allowed'] ?? 0)) - $leaves_used - $leaves_pending, 2));

            // Comp-off / as-earned: use compensatory claim flow rather than normal balance quota
            $isAsEarned = !empty($leave_type->is_as_earned) || ($leave_type->credit_frequency === 'earned');
            $isMonthlyCap = ($leave_type->credit_frequency === 'monthly_cap');
            if (!$isAsEarned && $total_leave_days > $available) {
                return redirect()->back()->with('error', __('You cannot apply leave more than your available balance.'));
            }
            // WFH monthly_cap uses current-month available above.

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
                    if (!empty($substitute) && !empty($substitute->user_id)) {
                        \App\Services\InAppNotifier::notifyUser($substitute->user_id, [
                            'module' => 'leave',
                            'action' => 'substitute_request',
                            'title' => __('Substitute Leave Request'),
                            'message' => ($employee->name ?? '') . ' — ' . ($leave->start_date ?? '') . ' to ' . ($leave->end_date ?? ''),
                            'link' => route('dashboard'),
                        ]);
                    }
                }

                // Always notify reporting manager by email + in-app when leave is applied
                $this->notifyManagerOfLeaveRequest($leave);

                \App\Services\InAppNotifier::notifyCompanyHr(Auth::user()->creatorId(), [
                    'module' => 'leave',
                    'action' => 'created',
                    'title' => __('New Leave Request'),
                    'message' => ($employee->name ?? '') . ' — ' . ($leave->start_date ?? '') . ' to ' . ($leave->end_date ?? ''),
                    'link' => route('leave.index'),
                ]);

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
                $date = $this->leaveCycleDates();

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

                if ($spectalError = $this->leavePolicy()->validateSpectalApplication($leave_type, $employee, $request->start_date)) {
                    return redirect()->back()->with('error', $spectalError);
                }

                $policyCode = LeavePolicyService::resolvePolicyCode($leave_type);
                if (($leave_type->credit_frequency === 'monthly_cap') || $policyCode === 'wfh') {
                    $usage = $this->getLeaveUsageForCurrentMonth((int) $employee->id, (int) $leave_type->id, $date, (int) $leave->id);
                } else {
                    $usage = $this->getLeaveUsageByCycle((int) $employee->id, (int) $leave_type->id, $date, (int) $leave->id);
                }
                $leaves_used = $usage['used'];
                $leaves_pending = $usage['pending'];

                if ($this->hasSubstituteBlock((int) $employee->id, $request->start_date, $request->end_date, (int) $leave->id)) {
                    return redirect()->back()->with('error', __('You are assigned as a substitute for these dates and cannot apply leave.'));
                }

                $allowance = $this->getLeaveAllowanceDetails($leave_type, $employee, $date);

                $available = max(0, round(((float) ($allowance['display_total'] ?? $allowance['allowed'] ?? 0)) - $leaves_used - $leaves_pending, 2));
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
        $employmentStatus = $this->isSpectalPortal() ? $this->employmentStatusMeta($employee) : null;

        return view('leave.action', compact('employee', 'leavetype', 'leave', 'canTakeAction', 'employmentStatus'));
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

        // In-app: notify employee of approve/reject
        $empNotified = Employee::find($leave->employee_id);
        if ($empNotified && $empNotified->user_id) {
            \App\Services\InAppNotifier::notifyUser($empNotified->user_id, [
                'module' => 'leave',
                'action' => strtolower($leave->status),
                'title' => __('Leave') . ' ' . __($leave->status),
                'message' => ($leave->start_date ?? '') . ' to ' . ($leave->end_date ?? ''),
                'link' => route('leave.index'),
            ]);
        }

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
        // Manager was already emailed/notified when leave was applied

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

        if (empty($leave->relationLoaded('leaveType'))) {
            $leave->load('leaveType');
        }

        if (empty($employee->reporting_manager_id)) {
            \Log::warning('Leave applied but no reporting manager set for employee.', [
                'leave_id' => $leave->id,
                'employee_id' => $employee->id,
            ]);
            return;
        }

        $managerEmp = Employee::find($employee->reporting_manager_id);
        if (empty($managerEmp)) {
            \Log::warning('Leave applied but reporting manager employee record not found.', [
                'leave_id' => $leave->id,
                'reporting_manager_id' => $employee->reporting_manager_id,
            ]);
            return;
        }

        // In-app notification
        if (!empty($managerEmp->user_id)) {
            \App\Services\InAppNotifier::notifyUser($managerEmp->user_id, [
                'module' => 'leave',
                'action' => 'created',
                'title' => __('New Leave Request'),
                'message' => ($employee->name ?? '') . ' — ' . ($leave->start_date ?? '') . ' to ' . ($leave->end_date ?? ''),
                'link' => route('leave.index'),
            ]);
        }

        $managerUser = !empty($managerEmp->user_id) ? User::find($managerEmp->user_id) : null;
        $managerEmail = $managerUser->email ?? $managerEmp->email ?? null;

        if (empty($managerEmail)) {
            \Log::warning('Leave applied but reporting manager has no email.', [
                'leave_id' => $leave->id,
                'manager_employee_id' => $managerEmp->id,
                'manager_user_id' => $managerEmp->user_id,
            ]);
            return;
        }

        // Direct mail (same approach as substitute request — more reliable than template settings)
        try {
            Mail::to($managerEmail)->send(new LeaveManagerRequest($leave, $employee, $managerEmp));
            \Log::info('Leave request email sent to reporting manager.', [
                'leave_id' => $leave->id,
                'manager_email' => $managerEmail,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send leave request email to reporting manager: ' . $e->getMessage(), [
                'leave_id' => $leave->id,
                'manager_email' => $managerEmail,
            ]);
        }

        // Also try company email template if enabled (optional secondary)
        try {
            $settings = Utility::settings();
            if (($settings['new_leave_request'] ?? 0) == 1 && !empty($managerUser)) {
                $uArr = [
                    'employee_name' => $employee->name,
                    'leave_type' => $leave->leaveType->title ?? 'Leave',
                    'leave_start_end_time' => ($leave->start_date ?? '') . ' to ' . ($leave->end_date ?? ''),
                    'leave_reason' => $leave->leave_reason ?? '',
                ];
                Utility::sendEmailTemplate('new_leave_request', [$managerUser->id => $managerEmail], $uArr);
            }
        } catch (\Exception $e) {
            \Log::warning('Optional leave template email failed: ' . $e->getMessage());
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

        $date = $this->leaveCycleDates();
        $employee = Employee::find($request->employee_id);
        if (empty($employee)) {
            return response()->json([]);
        }

        $leaveTypes = LeaveType::where('created_by', '=', \Auth::user()->creatorId())->get();
        $leaveCounts = [];
        $isSpectal = $this->isSpectalPortal();

        foreach ($leaveTypes as $leaveType) {
            if ($isSpectal && !$this->leavePolicy()->shouldShowOnSpectalBalance($leaveType, $employee)) {
                continue;
            }

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
                'note' => $summary['note'] ?? null,
                'approval_requirement' => $leaveType->approval_requirement ?? 'na',
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
        $isSpectal = $this->isSpectalPortal();
        $policyCode = LeavePolicyService::resolvePolicyCode($leaveType);
        $defs = LeavePolicyService::policyDefinitions();
        $note = null;

        // Spectal: apply canonical policy defaults when DB leave types are misconfigured
        if ($isSpectal && $policyCode && isset($defs[$policyCode])) {
            $def = $defs[$policyCode];
            if (empty($leaveType->credit_frequency) || $policyCode === 'sick' || $policyCode === 'pl'
                || $policyCode === 'wfh' || $policyCode === 'bereavement' || $policyCode === 'cl') {
                $leaveType->credit_frequency = $def['credit_frequency'] ?? $leaveType->credit_frequency;
            }
            if ($policyCode === 'pl') {
                $leaveType->monthly_credit = 1.5;
                $leaveType->annual_credit = 18;
                $leaveType->days = 18;
            }
            if ($policyCode === 'sick') {
                $leaveType->annual_credit = 7;
                $leaveType->days = 7;
                $leaveType->credit_frequency = 'annual';
                $leaveType->is_prorata = true;
            }
            if ($policyCode === 'wfh') {
                $leaveType->monthly_limit = 2;
                $leaveType->monthly_credit = 2;
                $leaveType->credit_frequency = 'monthly_cap';
            }
            if ($policyCode === 'bereavement') {
                $leaveType->credit_frequency = 'event';
                $leaveType->annual_credit = 7;
                $leaveType->days = 7;
                $leaveType->monthly_credit = 0;
                $note = __('Event-based: appears after manager grants 7 paid days for a qualifying bereavement.');
            }
            if ($policyCode === 'cl') {
                $leaveType->credit_frequency = 'seasonal';
                $leaveType->days = 0;
                $leaveType->annual_credit = 0;
                $leaveType->monthly_credit = 0;
                $note = __('Casual Leave is only available during the May–July window and is not an annual leave bank.');
            }
        }

        // NEW: per-type credit frequency from policy matrix
        $typeFrequency = $leaveType->credit_frequency ?? null;
        if ($typeFrequency === 'annual' || $typeFrequency === 'event') {
            $creditMode = 'lump_sum';
        } elseif (in_array($typeFrequency, ['monthly', 'monthly_cap'], true)) {
            $creditMode = 'monthly';
        } elseif ($typeFrequency === 'earned' || $typeFrequency === 'seasonal') {
            $creditMode = $typeFrequency === 'seasonal' ? 'seasonal' : 'earned';
        } else {
            $creditMode = $settings['leave_credit_mode'] ?? 'lump_sum';
        }

        // As-earned types (comp-off) have no normal annual quota
        if ($creditMode === 'earned' || !empty($leaveType->is_as_earned)) {
            return [
                'allowed' => 0,
                'display_total' => 0,
                'total_annual' => 0,
                'monthly_accrual' => 0,
                'eligible_months' => 0,
                'credited_months' => 0,
                'opening_balance' => 0,
                'accrued_to_date' => 0,
                'credit_mode' => 'earned',
                'carry_forward' => 0,
                'note' => __('Credited when compensatory off is earned.'),
            ];
        }

        // Seasonal CL: no accrued balance bank
        if ($creditMode === 'seasonal' || $typeFrequency === 'seasonal') {
            return [
                'allowed' => 0,
                'display_total' => 0,
                'total_annual' => 0,
                'monthly_accrual' => 0,
                'eligible_months' => 0,
                'credited_months' => 0,
                'opening_balance' => 0,
                'accrued_to_date' => 0,
                'credit_mode' => 'seasonal',
                'carry_forward' => 0,
                'note' => $note,
            ];
        }

        // Event-based (bereavement): only what manager granted
        if ($typeFrequency === 'event') {
            $granted = 0.0;
            if ($isSpectal && $employee) {
                $granted = $this->leavePolicy()->grantedDays(
                    (int) $employee->id,
                    (int) $leaveType->id,
                    \App\Models\LeaveBalanceEntry::TYPE_GRANT
                );
            } elseif (!$isSpectal) {
                $granted = (float) ($leaveType->annual_credit ?? $leaveType->days ?? 7);
            }

            return [
                'allowed' => $granted,
                'display_total' => $granted,
                'total_annual' => $granted,
                'monthly_accrual' => 0,
                'eligible_months' => 0,
                'credited_months' => 0,
                'opening_balance' => 0,
                'accrued_to_date' => 0,
                'credit_mode' => 'event',
                'carry_forward' => 0,
                'note' => $granted > 0
                    ? __('Granted by manager: :days day(s).', ['days' => $granted])
                    : $note,
            ];
        }

        $annualCredit = (float) ($leaveType->annual_credit ?? 0);
        if ($annualCredit <= 0) {
            $annualCredit = (float) ($leaveType->days ?? 0);
        }

        $monthlyAccrual = (float) ($leaveType->monthly_credit ?? 0);
        if ($monthlyAccrual <= 0 && $annualCredit > 0) {
            $monthlyAccrual = round($annualCredit / 12, 2);
        }

        // WFH: monthly pool of monthly_limit (default 2), not annual/12 from days
        if ($typeFrequency === 'monthly_cap' && !empty($leaveType->monthly_limit)) {
            $monthlyAccrual = (float) $leaveType->monthly_limit;
        }

        $openingBalance = 0.0;
        $accruedToDate = 0.0;

        $normalizedCycle = $this->normalizeCycleDates($cycleDates);
        $cycleStart = Carbon::parse($normalizedCycle['start_date'])->startOfMonth();
        $cycleEnd = Carbon::parse($normalizedCycle['end_date'])->startOfMonth();

        // Spectal go-live: never accrue before 1 Aug 2026
        if ($isSpectal) {
            $goLive = Carbon::parse('2026-08-01')->startOfMonth();
            if ($cycleStart->lt($goLive)) {
                $cycleStart = $goLive->copy();
            }
        }

        $accrualStart = $cycleStart->copy();
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

        if ($eligibleMonths <= 0 || $asOfMonth->lessThan($accrualStart)) {
            $creditedMonths = 0;
        } else {
            $creditedMonths = min($eligibleMonths, $accrualStart->diffInMonths($asOfMonth) + 1);
        }

        if ($creditMode === 'lump_sum') {
            // Spectal Sick Leave transition: load Aug–Dec pro-rata upfront (5/12 of 7 = 2.92)
            if ($isSpectal && $policyCode === 'sick') {
                $proratedTotal = round($annualCredit * ($eligibleMonths / 12), 2);
            } else {
                $proratedTotal = round($monthlyAccrual > 0
                    ? $monthlyAccrual * $eligibleMonths
                    : $annualCredit * ($eligibleMonths / 12), 2);
            }
            $allowed = $proratedTotal;
            $accruedToDate = $allowed;
            $openingBalance = 0.0;
        } else {
            $accruedToDate = round($monthlyAccrual * $creditedMonths, 2);
            $allowed = min($accruedToDate, round($monthlyAccrual * $eligibleMonths, 2));
            $proratedTotal = round($monthlyAccrual * $eligibleMonths, 2);
            $openingBalance = 0.0;

            // Spectal PL: opening balance ledger + monthly accrual
            if ($isSpectal && $policyCode === 'pl' && $employee) {
                $periodKey = (string) ($cycleDates['year'] ?? date('Y'));
                $openingBalance = $this->leavePolicy()->openingBalanceDays(
                    (int) $employee->id,
                    (int) $leaveType->id,
                    $periodKey
                );
                $allowed = round($openingBalance + $accruedToDate, 2);
                $proratedTotal = round($openingBalance + ($monthlyAccrual * $eligibleMonths), 2);
                $note = __('Opening :open + Accrued :accrued (:months × :rate).', [
                    'open' => $openingBalance,
                    'accrued' => $accruedToDate,
                    'months' => $creditedMonths,
                    'rate' => $monthlyAccrual,
                ]);
            }
        }

        // monthly_cap (WFH): only current month allowance
        if ($typeFrequency === 'monthly_cap' && !empty($leaveType->monthly_limit)) {
            $allowed = (float) $leaveType->monthly_limit;
            $proratedTotal = $allowed;
            $monthlyAccrual = $allowed;
            $note = __('Monthly allowance only — does not accumulate unused days.');
        }

        if (!empty($employee) && $this->isEmployeeInProbation($employee)) {
            // Spectal: probation employees can SEE balances (accumulation continues)
            // Application is blocked separately in store/create.
            if (!$isSpectal && ($settings['probation_leave_accumulation'] ?? 'during') === 'after') {
                $allowed = 0;
            }
        }

        $carryForward = 0.0;
        $typeAllowsCf = isset($leaveType->is_carry_forward)
            ? ((int) $leaveType->is_carry_forward === 1)
            : (($settings['leave_carry_forward'] ?? 'off') === 'on');

        // Spectal 2026 transition: no prior-cycle CF into Aug go-live
        if ($isSpectal && ($cycleDates['year'] ?? '') === '2026') {
            $typeAllowsCf = false;
        }

        if ($includeCarryForward && $typeAllowsCf) {
            $previousCycle = $this->getPreviousCycleDates($cycleDates);
            if (!empty($previousCycle)) {
                $previousAllowance = $this->getLeaveAllowanceDetails($leaveType, $employee, $previousCycle, false);
                $previousUsage = $this->getLeaveUsageByCycle($employee ? (int) $employee->id : null, (int) $leaveType->id, $previousCycle);
                $previousAvailable = max(0, round(($previousAllowance['allowed'] ?? 0) - ($previousUsage['used'] ?? 0) - ($previousUsage['pending'] ?? 0), 2));

                $carryForwardMax = (float) ($leaveType->max_carry_forward ?? 0);
                if ($carryForwardMax <= 0) {
                    $carryForwardMax = (float) ($settings['leave_carry_forward_max'] ?? 0);
                }
                $carryForward = $carryForwardMax > 0 ? min($previousAvailable, $carryForwardMax) : $previousAvailable;
                $allowed = round($allowed + $carryForward, 2);
            }
        }

        // For monthly accrual, dashboard "Total" = credited-to-date (same base as Available).
        // For lump sum / event, Total = full loaded entitlement for the cycle.
        $displayTotal = in_array($creditMode, ['monthly'], true) && $typeFrequency !== 'monthly_cap'
            ? $allowed
            : $allowed;

        return [
            'allowed' => max(0, round($allowed, 2)),
            'display_total' => max(0, round($displayTotal, 2)),
            'total_annual' => max(0, round($proratedTotal, 2)),
            'monthly_accrual' => max(0, round($monthlyAccrual, 2)),
            'eligible_months' => $eligibleMonths,
            'credited_months' => $creditedMonths,
            'opening_balance' => max(0, round($openingBalance ?? 0, 2)),
            'accrued_to_date' => max(0, round($accruedToDate ?? $allowed, 2)),
            'credit_mode' => $creditMode,
            'carry_forward' => max(0, round($carryForward, 2)),
            'note' => $note,
        ];
    }

    protected function calculateLeaveBalanceSummary(int $employeeId, LeaveType $leaveType, array $cycleDates, bool $companyWide = false): array
    {
        $employee = $employeeId > 0 ? Employee::find($employeeId) : null;
        $allowance = $this->getLeaveAllowanceDetails($leaveType, $employee, $cycleDates);
        $usageEmployeeId = $companyWide ? null : ($employeeId > 0 ? $employeeId : null);

        $policyCode = LeavePolicyService::resolvePolicyCode($leaveType);
        $isMonthlyCap = ($leaveType->credit_frequency === 'monthly_cap') || $policyCode === 'wfh';

        if ($isMonthlyCap) {
            $usage = $this->getLeaveUsageForCurrentMonth($usageEmployeeId, (int) $leaveType->id, $cycleDates);
        } else {
            $usage = $this->getLeaveUsageByCycle($usageEmployeeId, (int) $leaveType->id, $cycleDates);
        }

        // Display total must be the same entitlement base used for Available
        // (fixes "Total 18 / Available 30" style mismatches).
        $total = (float) ($allowance['display_total'] ?? $allowance['allowed'] ?? 0);
        $used = (float) ($usage['used'] ?? 0);
        $pending = (float) ($usage['pending'] ?? 0);
        $available = max(0, round($total - $used - $pending, 2));

        return [
            'total' => max(0, round($total, 2)),
            'monthly_accrual' => $allowance['monthly_accrual'],
            'used' => $used,
            'pending' => $pending,
            'available' => $available,
            'credit_mode' => $allowance['credit_mode'] ?? 'lump_sum',
            'carry_forward' => $allowance['carry_forward'] ?? 0,
            'encashable_leave' => $this->calculateEncashableLeave($available, $leaveType),
            'opening_balance' => $allowance['opening_balance'] ?? 0,
            'accrued_to_date' => $allowance['accrued_to_date'] ?? 0,
            'note' => $allowance['note'] ?? null,
        ];
    }

    protected function getLeaveUsageForCurrentMonth(?int $employeeId, int $leaveTypeId, array $cycleDates, ?int $excludeLeaveId = null): array
    {
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd = Carbon::now()->endOfMonth()->toDateString();

        // Clamp to cycle bounds
        $normalized = $this->normalizeCycleDates($cycleDates);
        if ($monthStart < $normalized['start_date']) {
            $monthStart = $normalized['start_date'];
        }
        if ($monthEnd > $normalized['end_date']) {
            $monthEnd = $normalized['end_date'];
        }

        return $this->getLeaveUsageByCycle($employeeId, $leaveTypeId, [
            'start_date' => Carbon::parse($monthStart)->subDay()->toDateString(),
            'end_date' => Carbon::parse($monthEnd)->addDay()->toDateString(),
        ], $excludeLeaveId);
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
     * Spectal: set PL opening balance for an employee (then monthly accrual continues).
     */
    public function setOpeningBalanceView()
    {
        if (!\Auth::user()->can('Manage Leave') || \Auth::user()->type == 'employee') {
            return response()->json(['error' => __('Permission denied.')], 401);
        }

        $employees = Employee::where('created_by', \Auth::user()->creatorId())
            ->orderBy('name')
            ->get()
            ->pluck('name', 'id');

        $leaveTypes = LeaveType::where('created_by', \Auth::user()->creatorId())->get()
            ->filter(function ($lt) {
                return LeavePolicyService::resolvePolicyCode($lt) === 'pl';
            })
            ->pluck('title', 'id');

        if ($leaveTypes->isEmpty()) {
            return response()->json(['error' => __('No Privilege Leave type found. Create one under Leave Type first.')], 422);
        }

        $cycle = $this->leaveCycleDates();

        return view('leave.set_opening_balance', compact('employees', 'leaveTypes', 'cycle'));
    }

    public function storeOpeningBalance(Request $request)
    {
        if (!\Auth::user()->can('Manage Leave') || \Auth::user()->type == 'employee') {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if (!Schema::hasTable('leave_balance_entries')) {
            return redirect()->back()->with('error', __('Leave balance ledger is not installed. Run migrations first.'));
        }

        $validator = \Validator::make($request->all(), [
            'employee_id' => 'required|integer|exists:employees,id',
            'leave_type_id' => 'required|integer|exists:leave_types,id',
            'days' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        $employee = Employee::find($request->employee_id);
        $leaveType = LeaveType::find($request->leave_type_id);
        if (!$employee || !$leaveType
            || $employee->created_by != \Auth::user()->creatorId()
            || $leaveType->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if (LeavePolicyService::resolvePolicyCode($leaveType) !== 'pl') {
            return redirect()->back()->with('error', __('Opening balance is only supported for Privilege Leave.'));
        }

        $periodKey = (string) ($this->leaveCycleDates()['year'] ?? date('Y'));

        // Replace existing opening for this cycle (keep a single opening row)
        \App\Models\LeaveBalanceEntry::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('entry_type', \App\Models\LeaveBalanceEntry::TYPE_OPENING)
            ->where('period_key', $periodKey)
            ->delete();

        \App\Models\LeaveBalanceEntry::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'entry_type' => \App\Models\LeaveBalanceEntry::TYPE_OPENING,
            'days' => (float) $request->days,
            'period_key' => $periodKey,
            'notes' => $request->notes,
            'created_by' => \Auth::user()->creatorId(),
        ]);

        return redirect()->back()->with('success', __('Opening balance set for :name.', ['name' => $employee->name]));
    }

    /**
     * Spectal: grant bereavement entitlement (typically 7 days).
     */
    public function grantBereavementView()
    {
        if (!\Auth::user()->can('Manage Leave') || \Auth::user()->type == 'employee') {
            return response()->view('leave.grant_bereavement', [
                'employees' => collect(),
                'leaveTypes' => collect(),
                'formError' => __('Permission denied.'),
            ]);
        }

        $employees = Employee::where('created_by', \Auth::user()->creatorId())
            ->orderBy('name')
            ->get()
            ->pluck('name', 'id');

        $leaveTypes = LeaveType::where('created_by', \Auth::user()->creatorId())->get()
            ->filter(function ($lt) {
                $code = LeavePolicyService::resolvePolicyCode($lt);
                if ($code === 'bereavement') {
                    return true;
                }
                $title = strtolower((string) $lt->title);

                return str_contains($title, 'bereavement') || str_contains($title, 'compassionate');
            })
            ->pluck('title', 'id');

        $formError = null;
        if ($leaveTypes->isEmpty()) {
            $formError = __('No Bereavement leave type found. Create a leave type named “Bereavement” (or set policy_code = bereavement) under Leave Type first.');
        }
        if ($employees->isEmpty()) {
            $formError = __('No employees found to grant leave to.');
        }

        return view('leave.grant_bereavement', compact('employees', 'leaveTypes', 'formError'));
    }

    public function storeGrantBereavement(Request $request)
    {
        $respond = function (bool $ok, string $message) use ($request) {
            $key = $ok ? 'success' : 'error';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([$key => $message], $ok ? 200 : 422);
            }

            return redirect()->back()->with($key, $message);
        };

        if (!\Auth::user()->can('Manage Leave') || \Auth::user()->type == 'employee') {
            return $respond(false, __('Permission denied.'));
        }

        if (!Schema::hasTable('leave_balance_entries')) {
            return $respond(false, __('Leave balance ledger is not installed. Run migrations first.'));
        }

        $validator = \Validator::make($request->all(), [
            'employee_id' => 'required|integer|exists:employees,id',
            'leave_type_id' => 'required|integer|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'days' => 'nullable|numeric|min:0.5|max:7',
            'notes' => 'required|string|max:500',
            'create_leave' => 'nullable|in:0,1',
            'auto_approve' => 'nullable|in:0,1',
        ]);

        if ($validator->fails()) {
            return $respond(false, $validator->getMessageBag()->first());
        }

        $employee = Employee::find($request->employee_id);
        $leaveType = LeaveType::find($request->leave_type_id);
        if (!$employee || !$leaveType
            || $employee->created_by != \Auth::user()->creatorId()
            || $leaveType->created_by != \Auth::user()->creatorId()) {
            return $respond(false, __('Permission denied.'));
        }

        $code = LeavePolicyService::resolvePolicyCode($leaveType);
        $title = strtolower((string) $leaveType->title);
        $isBereavement = $code === 'bereavement'
            || str_contains($title, 'bereavement')
            || str_contains($title, 'compassionate');
        if (!$isBereavement) {
            return $respond(false, __('Please select a Bereavement leave type.'));
        }

        $startDate = Carbon::parse($request->start_date)->toDateString();
        $endDate = Carbon::parse($request->end_date)->toDateString();
        $totalDays = $this->calculateLeaveDays($startDate, $endDate, 'full_day', (int) \Auth::user()->creatorId());
        if ($totalDays <= 0) {
            return $respond(false, __('Selected dates result in 0 leave days. Please choose working days.'));
        }
        if ($totalDays > 7) {
            return $respond(false, __('Bereavement leave cannot exceed 7 days.'));
        }

        $grantDays = $request->filled('days') ? (float) $request->days : (float) $totalDays;
        $grantDays = min(7, max(0.5, $grantDays));

        \App\Models\LeaveBalanceEntry::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'entry_type' => \App\Models\LeaveBalanceEntry::TYPE_GRANT,
            'days' => $grantDays,
            'period_key' => date('Y-m'),
            'notes' => $request->notes,
            'created_by' => \Auth::user()->creatorId(),
        ]);

        $createLeave = $request->boolean('create_leave', true);
        $autoApprove = $request->boolean('auto_approve', true);

        if ($createLeave) {
            $leave = new LocalLeave();
            $leave->employee_id = $employee->id;
            $leave->leave_type_id = $leaveType->id;
            $leave->applied_on = Carbon::now()->toDateString();
            $leave->start_date = $startDate;
            $leave->end_date = $endDate;
            $leave->day_type = 'full_day';
            $leave->total_leave_days = $totalDays;
            $leave->leave_reason = trim(
                __('Bereavement leave applied by HR (:hr) on behalf of employee.', ['hr' => \Auth::user()->name])
                . ' ' . $request->notes
            );
            $leave->remark = __('HR on-behalf grant');
            $leave->status = $autoApprove ? 'Approved' : 'Pending';
            $leave->substitute_status = 'Accepted';
            $leave->created_by = \Auth::user()->creatorId();
            $leave->save();

            if ($employee->user_id) {
                \App\Services\InAppNotifier::notifyUser($employee->user_id, [
                    'module' => 'leave',
                    'action' => strtolower($leave->status),
                    'title' => __('Bereavement Leave') . ' — ' . __($leave->status),
                    'message' => $startDate . ' to ' . $endDate . ' (' . $totalDays . ' ' . __('days') . ')',
                    'link' => route('leave.index'),
                ]);
            }
        }

        return $respond(true, __('Bereavement leave (:days days, :start to :end) granted to :name on their behalf.', [
            'days' => $totalDays,
            'start' => $startDate,
            'end' => $endDate,
            'name' => $employee->name,
        ]));
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

