<?php

namespace App\Http\Controllers;

use App\Imports\AttendanceImport;
use App\Models\AttendanceEmployee;
use App\Models\AttendanceModificationRequest;
use App\Models\AttendanceModificationRequestLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\IpRestrict;
use App\Models\Leave;
use App\Models\PayrollAttendanceSync;
use App\Models\User;
use App\Models\Utility;
use App\Services\FacialRecognitionService;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Exports\MonthlyAttendanceExport;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceEmployeeController extends Controller
{
    /** Minimum saved image size (bytes) to reject empty/corrupt camera captures */
    private const MIN_ATTENDANCE_PHOTO_BYTES = 500;

    private function denyUnlessAttendanceAdmin()
    {
        if (!in_array(Auth::user()->type, ['super admin', 'company'])) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        return null;
    }

    /**
     * Convert a local app URL (e.g. http://localhost/hrms/storage/...) to readable filesystem path.
     */
    private function localUrlToPath(string $url): ?string
    {
        $parts = parse_url($url);
        if (empty($parts['path'])) {
            return null;
        }

        $decodedPath = ltrim(urldecode((string) $parts['path']), '/');
        $scriptBase = trim((string) parse_url(url('/'), PHP_URL_PATH), '/');

        $pathCandidates = [$decodedPath];
        if (!empty($scriptBase) && str_starts_with($decodedPath, $scriptBase . '/')) {
            $pathCandidates[] = substr($decodedPath, strlen($scriptBase) + 1);
        }

        foreach ($pathCandidates as $relative) {
            $relative = ltrim((string) $relative, '/');
            if ($relative === '') {
                continue;
            }

            $withoutStorage = str_starts_with($relative, 'storage/')
                ? substr($relative, 8)
                : $relative;
            $withoutPublic = str_starts_with($relative, 'public/')
                ? substr($relative, 7)
                : $relative;

            $candidates = array_unique([
                public_path($relative),
                public_path($withoutStorage),
                public_path($withoutPublic),
                storage_path('app/public/' . $withoutStorage),
                storage_path('app/public/' . $withoutPublic),
            ]);

            foreach ($candidates as $path) {
                if (!empty($path) && is_readable($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    /**
     * Get filesystem path for current user's profile/avatar photo (tries multiple locations).
     */
    private function getProfilePhotoPath(): ?string
    {
        $avatar = \Auth::user()->avatar;
        if (empty($avatar)) {
            return null;
        }
        if (str_starts_with((string) $avatar, 'http://') || str_starts_with((string) $avatar, 'https://')) {
            $resolvedFromUrl = $this->localUrlToPath((string) $avatar);
            if (!empty($resolvedFromUrl)) {
                return $resolvedFromUrl;
            }

            return null;
        }
        $raw = ltrim(str_replace('\\', '/', urldecode((string) $avatar)), '/');
        $filename = basename($raw);
        $candidates = array_unique([
            // raw avatar value may already be a relative path
            public_path($raw),
            storage_path('app/public/' . $raw),
            // common avatar locations
            public_path('uploads/avatar/' . $raw),
            public_path('uploads/avatar/' . $filename),
            public_path('storage/uploads/avatar/' . $raw),
            public_path('storage/uploads/avatar/' . $filename),
            storage_path('app/public/uploads/avatar/' . $raw),
            storage_path('app/public/uploads/avatar/' . $filename),
        ]);
        if (Schema::hasColumn('users', 'avatar')) {
            try {
                $candidates[] = Storage::disk('public')->path($raw);
                $candidates[] = Storage::disk('public')->path('uploads/avatar/' . $raw);
                $candidates[] = Storage::disk('public')->path('uploads/avatar/' . $filename);
            } catch (\Throwable $e) {
                // ignore
            }
        }
        foreach ($candidates as $path) {
            if (!empty($path) && is_readable($path)) {
                return $path;
            }
        }
        return null;
    }

    /** Minimum confidence (0-100) to treat as verified. Same for clock-in and clock-out. */
    private const VERIFY_CONFIDENCE_MIN = 50;

    /** Cache TTL (seconds) for attendance page FacialRecognitionService preview */
    private const FACE_PREVIEW_CACHE_TTL = 600;

    /**
     * Absolute filesystem path for a stored attendance photo (relative to public/).
     */
    private function attendanceStoredPhotoPath(?string $stored): ?string
    {
        if (empty($stored)) {
            return null;
        }
        $storedValue = trim((string) $stored);
        if (str_starts_with($storedValue, 'http://') || str_starts_with($storedValue, 'https://')) {
            return $this->localUrlToPath($storedValue);
        }

        $normalized = str_replace('\\', '/', urldecode($storedValue));
        $normalized = ltrim($normalized, '/');

        // If path accidentally includes app base folder (e.g. hrms/uploads/attendance/x.jpg), strip it.
        $scriptBase = trim((string) parse_url(url('/'), PHP_URL_PATH), '/');
        if (!empty($scriptBase) && str_starts_with($normalized, $scriptBase . '/')) {
            $normalized = substr($normalized, strlen($scriptBase) + 1);
        }

        $relativeNoPublic = str_starts_with($normalized, 'public/')
            ? substr($normalized, 7)
            : $normalized;
        $candidates = array_unique([
            public_path($normalized),
            public_path($relativeNoPublic),
            public_path('storage/' . $relativeNoPublic),
            storage_path('app/public/' . $relativeNoPublic),
        ]);
        foreach ($candidates as $full) {
            if (!empty($full) && is_readable($full)) {
                return $full;
            }
        }

        return null;
    }

    /**
     * Run facial verification: compare captured photo with user's profile/avatar photo only.
     * Returns verified => true only when profile photo exists AND face matches with confidence >= VERIFY_CONFIDENCE_MIN.
     */
    private function verifyPhotoWithProfile(string $photoFullPath): array
    {
        $profilePath = $this->getProfilePhotoPath();
        if (empty($profilePath) || !file_exists($profilePath)) {
            return ['verified' => false, 'message' => __('Profile photo is missing. Please upload your profile photo first.')];
        }
        if (!file_exists($photoFullPath)) {
            return ['verified' => false, 'message' => __('Captured photo not found.')];
        }
        try {
            $facialService = app(FacialRecognitionService::class);
            $result = $facialService->verifyFace($photoFullPath, $profilePath);
            $match = $result['match'] ?? false;
            $confidence = (float) ($result['confidence'] ?? 0);
            $verified = $match && $confidence >= self::VERIFY_CONFIDENCE_MIN;
            $reason = $result['message'] ?? ($verified ? __('Face matches profile.') : __('Face does not match profile photo.'));
            if (!$verified && $confidence > 0) {
                $reason .= ' (' . round($confidence, 0) . '% ' . __('confidence') . ')';
            }
            return ['verified' => $verified, 'message' => $reason];
        } catch (\Throwable $e) {
            return ['verified' => false, 'message' => __('Verification failed: ') . $e->getMessage()];
        }
    }

    public function index(Request $request)
    {
        if (\Auth::user()->can('Manage Attendance')) {
            $branch = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $branch->prepend('All', '');

            $department = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $department->prepend('All', '');

            $employeeSelectionOptions = collect();
            $managerSelectionOptions = collect();
            $resolvedFilterType = $request->type === 'daily' ? 'daily' : 'monthly';
            $resolvedFilterMonth = $request->month;
            $resolvedFilterDate = $request->date;

            if (\Auth::user()->type == 'employee') {
                $emp = !empty(\Auth::user()->employee) ? \Auth::user()->employee->id : 0;
                $visibleEmployeeIds = collect([$emp])->filter(function ($id) {
                    return !empty($id);
                });

                if (!empty($emp) && Schema::hasColumn('employees', 'reporting_manager_id')) {
                    $assignedEmployeeIds = Employee::where('created_by', \Auth::user()->creatorId())
                        ->where('reporting_manager_id', $emp)
                        ->pluck('id');

                    $visibleEmployeeIds = $visibleEmployeeIds
                        ->merge($assignedEmployeeIds)
                        ->unique()
                        ->values();
                }

                $employeeSelectionOptions = Employee::whereIn('id', $visibleEmployeeIds)
                    ->orderBy('name')
                    ->pluck('name', 'id');

                $attendanceEmployee = AttendanceEmployee::query();
                $selectedEmployeeId = (int) $request->get('employee_id', 0);
                $employeeIdsForDateScope = $visibleEmployeeIds;
                if ($selectedEmployeeId > 0 && $visibleEmployeeIds->contains($selectedEmployeeId)) {
                    $attendanceEmployee->where('employee_id', $selectedEmployeeId);
                    $employeeIdsForDateScope = collect([$selectedEmployeeId]);
                } else {
                    $attendanceEmployee->whereIn('employee_id', $visibleEmployeeIds);
                }

                if ($resolvedFilterType == 'monthly') {
                    if (empty($resolvedFilterMonth)) {
                        // Default to current month so today's attendance is visible without changing filters
                        $resolvedFilterMonth = date('Y-m');
                    }

                    $month = date('m', strtotime($resolvedFilterMonth));
                    $year  = date('Y', strtotime($resolvedFilterMonth));
                    $start_date = date($year . '-' . $month . '-01');
                    $end_date = date('Y-m-t', strtotime('01-' . $month . '-' . $year));

                    $attendanceEmployee->whereBetween(
                        'date',
                        [
                            $start_date,
                            $end_date,
                        ]
                    );

                    $this->syncApprovedLeavesToAttendance($employeeIdsForDateScope->all(), $start_date, $end_date, \Auth::user()->creatorId());
                } elseif ($resolvedFilterType == 'daily' && !empty($resolvedFilterDate)) {
                    $attendanceEmployee->where('date', $resolvedFilterDate);
                } else {
                    $resolvedFilterType = 'monthly';
                    $resolvedFilterMonth = date('Y-m');

                    $month = date('m', strtotime($resolvedFilterMonth));
                    $year  = date('Y', strtotime($resolvedFilterMonth));
                    $start_date = date($year . '-' . $month . '-01');
                    $end_date = date('Y-m-t', strtotime('01-' . $month . '-' . $year));
                    $attendanceEmployee->whereBetween('date', [
                        $start_date,
                        $end_date,
                    ]);

                    $this->syncApprovedLeavesToAttendance($employeeIdsForDateScope->all(), $start_date, $end_date, \Auth::user()->creatorId());
                }

                $attendanceEmployee = $attendanceEmployee->orderByDesc('date')->orderByDesc('id')
                    ->with($this->attendanceIndexEagerLoads())
                    ->get();
            } else {
                $employee = Employee::select('id')->where('created_by', \Auth::user()->creatorId());

                $managerSelectionOptions = Employee::where('created_by', \Auth::user()->creatorId())
                    ->orderBy('name')
                    ->pluck('name', 'id');

                $selectedEmployeeFilterId = (int) $request->get('manager_employee_id', 0);
                if ($selectedEmployeeFilterId > 0) {
                    $employee->where('id', $selectedEmployeeFilterId);
                }

                if (!empty($request->branch)) {
                    $employee->where('branch_id', $request->branch);
                }

                if (!empty($request->department)) {
                    $employee->where('department_id', $request->department);
                }

                $employee = $employee->get()->pluck('id');
                $employeeIdsForDateScope = collect($employee)->filter()->values();

                $attendanceEmployee = AttendanceEmployee::whereIn('employee_id', $employee);
                if ($resolvedFilterType == 'monthly') {
                    if (empty($resolvedFilterMonth)) {
                        $resolvedFilterMonth = date('Y-m');
                    }

                    $month = date('m', strtotime($resolvedFilterMonth));
                    $year  = date('Y', strtotime($resolvedFilterMonth));
                    $start_date = date($year . '-' . $month . '-01');
                    $end_date = date('Y-m-t', strtotime('01-' . $month . '-' . $year));

                    $attendanceEmployee->whereBetween(
                        'date',
                        [
                            $start_date,
                            $end_date,
                        ]
                    );

                    $this->syncApprovedLeavesToAttendance($employeeIdsForDateScope->all(), $start_date, $end_date, \Auth::user()->creatorId());
                } elseif ($resolvedFilterType == 'daily' && !empty($resolvedFilterDate)) {
                    $attendanceEmployee->where('date', $resolvedFilterDate);
                } else {
                    $resolvedFilterType = 'monthly';
                    $resolvedFilterMonth = date('Y-m');

                    $month = date('m', strtotime($resolvedFilterMonth));
                    $year  = date('Y', strtotime($resolvedFilterMonth));
                    $start_date = date($year . '-' . $month . '-01');
                    $end_date = date('Y-m-t', strtotime('01-' . $month . '-' . $year));
                    $attendanceEmployee->whereBetween('date', [
                        $start_date,
                        $end_date,
                    ]);

                    $this->syncApprovedLeavesToAttendance($employeeIdsForDateScope->all(), $start_date, $end_date, \Auth::user()->creatorId());
                }

                $attendanceEmployee = $attendanceEmployee->orderByDesc('date')->orderByDesc('id')
                    ->with($this->attendanceIndexEagerLoads())
                    ->get();
            }

            $latestRequestByAttendance = collect();
            $attendanceIds = $attendanceEmployee->pluck('id')->filter()->values();
            if ($attendanceIds->count() > 0 && Schema::hasTable('attendance_modification_requests')) {
                $latestRequestByAttendance = AttendanceModificationRequest::whereIn('attendance_employee_id', $attendanceIds)
                    ->orderByDesc('id')
                    ->get()
                    ->groupBy('attendance_employee_id')
                    ->map(function ($items) {
                        return $items->first();
                    });
            }

            $focusRequest = null;
            $focusRequestId = (int) $request->get('swipe_request_id', 0);
            if ($focusRequestId > 0 && Schema::hasTable('attendance_modification_requests')) {
                $focusRequest = AttendanceModificationRequest::with(['attendance', 'employee'])->find($focusRequestId);
            }

            $pendingSwipeRequests = collect();
            if (Schema::hasTable('attendance_modification_requests')) {
                if (\Auth::user()->type == 'employee') {
                    $currentEmployee = \Auth::user()->employee;
                    if (!empty($currentEmployee)) {
                        $pendingSwipeRequests = AttendanceModificationRequest::with(['attendance.employee', 'employee'])
                            ->where('manager_employee_id', $currentEmployee->id)
                            ->where('status', 'Pending')
                            ->orderByDesc('id')
                            ->get();
                    }
                } else {
                    $pendingSwipeRequests = AttendanceModificationRequest::with(['attendance.employee', 'employee', 'manager'])
                        ->where('created_by', \Auth::user()->creatorId())
                        ->where('status', 'Pending')
                        ->orderByDesc('id')
                        ->get();
                }
            }

            $pendingSwipeRequestCount = (int) $pendingSwipeRequests->count();

            $currentEmployeeId = !empty(\Auth::user()->employee) ? \Auth::user()->employee->id : null;
            $showEmployeeColumn = \Auth::user()->type != 'employee' || (!empty($employeeSelectionOptions) && $employeeSelectionOptions->count() > 1);

            $policySettings = $this->getAttendanceSettings();
            $attendanceSummary = $this->buildAttendancePolicySummary($attendanceEmployee, $policySettings);
            $attendanceAnalytics = $this->buildAttendanceAnalytics($attendanceEmployee, $attendanceSummary);

            // Facial verification: compare most recent attendance clock-in and clock-out photos with profile avatar
            $attendanceSelfieUrl = null;
            $attendanceSelfieOutUrl = null;
            $profilePhotoUrl = null;
            $facialRecognitionClockIn = null;
            $facialRecognitionClockOut = null;
            if (\Auth::user()->type == 'employee') {
                // Get most recent attendance record with a clock-in photo
                $latestWithPhoto = $attendanceEmployee
                    ->filter(fn($a) => !empty($a->photo))
                    ->sortByDesc('date')
                    ->first();
                if ($latestWithPhoto) {
                    $attendanceSelfieUrl = $latestWithPhoto->photo_url;
                }
                // Get most recent attendance record with a clock-out photo
                $latestWithPhotoOut = $attendanceEmployee
                    ->filter(fn($a) => !empty($a->photo_out))
                    ->sortByDesc('date')
                    ->first();
                if ($latestWithPhotoOut) {
                    $attendanceSelfieOutUrl = $latestWithPhotoOut->photo_out_url ?? null;
                }
                $avatar = \Auth::user()->avatar;
                if (!empty($avatar)) {
                    if (str_starts_with($avatar, 'http')) {
                        $profilePhotoUrl = $avatar;
                    } else {
                        $profilePhotoUrl = asset(\Illuminate\Support\Facades\Storage::url('uploads/avatar/' . $avatar));
                    }
                }

                // FacialRecognitionService: same verify as clock-in/out, shown on this page (cached)
                $profileFs = $this->getProfilePhotoPath();
                if ($profileFs && is_readable($profileFs)) {
                    $profileMtime = (int) @filemtime($profileFs);
                    if ($latestWithPhoto) {
                        $absIn = $this->attendanceStoredPhotoPath($latestWithPhoto->photo);
                        if ($absIn) {
                            $inMtime = (int) @filemtime($absIn);
                            $cacheKeyIn = 'hrms_face_att_preview_in_'.\Auth::id().'_'.md5($absIn.'|'.$inMtime.'|'.$profileFs.'|'.$profileMtime);
                            $facialRecognitionClockIn = Cache::remember($cacheKeyIn, self::FACE_PREVIEW_CACHE_TTL, function () use ($absIn, $latestWithPhoto) {
                                $v = $this->verifyPhotoWithProfile($absIn);

                                return array_merge($v, [
                                    'attendance_date' => $latestWithPhoto->date,
                                    'stored_photo_verified' => \Schema::hasColumn('attendance_employees', 'photo_verified')
                                        ? (bool) $latestWithPhoto->photo_verified
                                        : null,
                                ]);
                            });
                        }
                    }
                    if ($latestWithPhotoOut) {
                        $absOut = $this->attendanceStoredPhotoPath($latestWithPhotoOut->photo_out ?? null);
                        if ($absOut) {
                            $outMtime = (int) @filemtime($absOut);
                            $cacheKeyOut = 'hrms_face_att_preview_out_'.\Auth::id().'_'.md5($absOut.'|'.$outMtime.'|'.$profileFs.'|'.$profileMtime);
                            $facialRecognitionClockOut = Cache::remember($cacheKeyOut, self::FACE_PREVIEW_CACHE_TTL, function () use ($absOut, $latestWithPhotoOut) {
                                $v = $this->verifyPhotoWithProfile($absOut);

                                return array_merge($v, [
                                    'attendance_date' => $latestWithPhotoOut->date,
                                    'stored_photo_verified' => \Schema::hasColumn('attendance_employees', 'photo_out_verified')
                                        ? (bool) $latestWithPhotoOut->photo_out_verified
                                        : null,
                                ]);
                            });
                        }
                    }
                }
            }

            // Load synced payroll attendance data for this month
            $syncedAttendance = [];
            if ($resolvedFilterType === 'monthly' && $resolvedFilterMonth && Schema::hasTable('payroll_attendance_sync')) {
                try {
                    $syncedAttendance = PayrollAttendanceSync::where('month', $resolvedFilterMonth)
                        ->where('created_by', \Auth::user()->creatorId())
                        ->get()
                        ->keyBy('employee_id')
                        ->toArray();
                } catch (\Throwable $e) {
                    \Log::warning('payroll_attendance_sync query failed: ' . $e->getMessage());
                    $syncedAttendance = [];
                }
            }

            return view('attendance.index', compact(
                'attendanceEmployee',
                'branch',
                'department',
                'attendanceSummary',
                'policySettings',
                'attendanceAnalytics',
                'employeeSelectionOptions',
                'managerSelectionOptions',
                'latestRequestByAttendance',
                'pendingSwipeRequests',
                'currentEmployeeId',
                'showEmployeeColumn',
                'resolvedFilterType',
                'resolvedFilterMonth',
                'resolvedFilterDate',
                'pendingSwipeRequestCount',
                'focusRequest',
                'attendanceSelfieUrl',
                'attendanceSelfieOutUrl',
                'profilePhotoUrl',
                'facialRecognitionClockIn',
                'facialRecognitionClockOut',
                'syncedAttendance'
            ));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Safe eager-loads for attendance index (skip relations when live DB is missing tables/columns).
     */
    protected function attendanceIndexEagerLoads(): array
    {
        $with = ['employee'];

        if (Schema::hasTable('shifts') && Schema::hasColumn('employees', 'shift_id')) {
            $with[] = 'employee.shift';
        }

        if (Schema::hasColumn('employees', 'reporting_manager_id')) {
            $with[] = 'employee.reportingManager';
        }

        return $with;
    }

    /**
     * Swipe request history for modal (same filters as index).
     */
    public function swipeHistory(Request $request)
    {
        if (!\Auth::user()->can('Manage Attendance')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }
        if (!Schema::hasTable('attendance_modification_requests')) {
            return response()->json(['requests' => [], 'message' => __('Swipe requests not available.')]);
        }

        $type = $request->get('type', 'monthly') === 'daily' ? 'daily' : 'monthly';
        $month = $request->get('month', date('Y-m'));
        $date = $request->get('date', date('Y-m-d'));

        $query = AttendanceModificationRequest::with(['attendance', 'employee', 'manager'])
            ->where('created_by', \Auth::user()->creatorId());

        if (\Auth::user()->type == 'employee') {
            $emp = !empty(\Auth::user()->employee) ? \Auth::user()->employee->id : null;
            if (empty($emp)) {
                return response()->json(['requests' => []]);
            }
            $query->where('employee_id', $emp);
        } else {
            $employee = Employee::select('id')->where('created_by', \Auth::user()->creatorId());
            if (Schema::hasColumn('employees', 'reporting_manager_id')) {
                $selectedManagerId = (int) $request->get('manager_employee_id', 0);
                if ($selectedManagerId > 0) {
                    $employee->where(function ($q) use ($selectedManagerId) {
                        $q->where('id', $selectedManagerId)->orWhere('reporting_manager_id', $selectedManagerId);
                    });
                }
            }
            if (!empty($request->branch)) {
                $employee->where('branch_id', $request->branch);
            }
            if (!empty($request->department)) {
                $employee->where('department_id', $request->department);
            }
            $selectedEmployeeId = (int) $request->get('employee_id', 0);
            if ($selectedEmployeeId > 0) {
                $employee->where('id', $selectedEmployeeId);
            }
            $employeeIds = $employee->get()->pluck('id')->filter()->values();
            if ($employeeIds->isEmpty()) {
                return response()->json(['requests' => []]);
            }
            $query->whereIn('employee_id', $employeeIds);
        }

        if ($type === 'daily' && !empty($date)) {
            $query->whereHas('attendance', function ($q) use ($date) {
                $q->where('date', $date);
            });
        } else {
            $month = $month ?: date('Y-m');
            $start = date('Y-m-01', strtotime($month));
            $end = date('Y-m-t', strtotime($month));
            $query->whereHas('attendance', function ($q) use ($start, $end) {
                $q->whereBetween('date', [$start, $end]);
            });
        }

        $requests = $query->orderByDesc('id')->get()->map(function ($req) {
            $att = $req->attendance;
            return [
                'id' => $req->id,
                'date' => $att ? $att->date : null,
                'employee_name' => $req->employee ? $req->employee->name : __('N/A'),
                'current_clock_in' => $att && $att->clock_in ? $att->clock_in : null,
                'current_clock_out' => $att && $att->clock_out ? $att->clock_out : null,
                'requested_status' => $req->requested_status,
                'requested_clock_in' => $req->requested_clock_in,
                'requested_clock_out' => $req->requested_clock_out,
                'reason' => $req->reason,
                'status' => $req->status,
                'manager_comment' => $req->manager_comment,
                'created_at' => $req->created_at ? $req->created_at->format('Y-m-d H:i') : null,
            ];
        });

        return response()->json(['requests' => $requests->values()->all()]);
    }

    public function submitSwipeRequest(Request $request)
    {
        if (\Auth::user()->type !== 'employee') {
            return redirect()->back()->with('error', __('Only employees can raise swipe requests.'));
        }

        if (!Schema::hasTable('attendance_modification_requests')) {
            return redirect()->back()->with('error', __('Swipe request module is not ready yet. Please run migrations.'));
        }

        $employee = \Auth::user()->employee;
        if (empty($employee)) {
            return redirect()->back()->with('error', __('Employee profile not found.'));
        }

        $validator = \Validator::make($request->all(), [
            'attendance_employee_id' => 'nullable|exists:attendance_employees,id',
            'request_date' => 'nullable|date',
            'requested_status' => 'nullable|in:Present,Half Day,Leave,Absent,Late Mark,Early Leaving,Early After Leave',
            'requested_clock_in' => 'nullable|date_format:H:i',
            'requested_clock_out' => 'nullable|date_format:H:i',
            'reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        if (empty($request->attendance_employee_id) && empty($request->request_date)) {
            return redirect()->back()->with('error', __('Please select attendance record or request date.'));
        }

        $attendance = null;
        if (!empty($request->attendance_employee_id)) {
            $attendance = AttendanceEmployee::find($request->attendance_employee_id);
            if (empty($attendance) || (int) $attendance->employee_id !== (int) $employee->id) {
                return redirect()->back()->with('error', __('You can request modification only for your own attendance.'));
            }
        } else {
            $requestDate = date('Y-m-d', strtotime((string) $request->request_date));
            $attendance = AttendanceEmployee::where('employee_id', $employee->id)
                ->whereDate('date', $requestDate)
                ->first();

            if (empty($attendance)) {
                $attendance = AttendanceEmployee::create([
                    'employee_id' => $employee->id,
                    'date' => $requestDate,
                    'status' => 'Leave',
                    'clock_in' => '00:00:00',
                    'clock_out' => '00:00:00',
                    'late' => '00:00:00',
                    'early_leaving' => '00:00:00',
                    'overtime' => '00:00:00',
                    'late_mark' => 0,
                    'early_mark' => 0,
                    'less_hours_mark' => 0,
                    'deduction_units' => 0,
                    'total_rest' => '00:00:00',
                    'created_by' => \Auth::user()->creatorId(),
                ]);
            }
        }

        if (empty($employee->reporting_manager_id)) {
            return redirect()->back()->with('error', __('Reporting manager is not assigned. Please contact HR.'));
        }

        $alreadyPending = AttendanceModificationRequest::where('attendance_employee_id', $attendance->id)
            ->where('employee_id', $employee->id)
            ->where('status', 'Pending')
            ->exists();

        if ($alreadyPending) {
            return redirect()->back()->with('error', __('A pending swipe request already exists for this attendance record.'));
        }

        AttendanceModificationRequest::create([
            'attendance_employee_id' => $attendance->id,
            'employee_id' => $employee->id,
            'manager_employee_id' => (int) $employee->reporting_manager_id,
            'requested_status' => !empty($request->requested_status) ? $request->requested_status : $this->detectSwipeIssueType($attendance),
            'requested_clock_in' => !empty($request->requested_clock_in) ? ($request->requested_clock_in . ':00') : null,
            'requested_clock_out' => !empty($request->requested_clock_out) ? ($request->requested_clock_out . ':00') : null,
            'reason' => $request->reason,
            'status' => 'Pending',
            'created_by' => \Auth::user()->creatorId(),
        ]);

        return redirect()->back()->with('success', __('Swipe modification request sent to your reporting manager.'));
    }

    public function updateSwipeRequest(Request $request, $id)
    {
        if (\Auth::user()->type !== 'employee') {
            return redirect()->back()->with('error', __('Only employees can update swipe requests.'));
        }

        if (!Schema::hasTable('attendance_modification_requests')) {
            return redirect()->back()->with('error', __('Swipe request module is not ready yet. Please run migrations.'));
        }

        $employee = \Auth::user()->employee;
        if (empty($employee)) {
            return redirect()->back()->with('error', __('Employee profile not found.'));
        }

        $validator = \Validator::make($request->all(), [
            'requested_status' => 'nullable|in:Present,Half Day,Leave,Absent,Late Mark,Early Leaving,Early After Leave',
            'requested_clock_in' => 'nullable|date_format:H:i',
            'requested_clock_out' => 'nullable|date_format:H:i',
            'reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        $swipeRequest = AttendanceModificationRequest::with('attendance')->find($id);
        if (empty($swipeRequest)) {
            return redirect()->back()->with('error', __('Swipe request not found.'));
        }

        if ($swipeRequest->status !== 'Pending') {
            return redirect()->back()->with('error', __('Only pending requests can be edited.'));
        }

        if ((int) $swipeRequest->employee_id !== (int) $employee->id) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $swipeRequest->requested_status = !empty($request->requested_status)
            ? $request->requested_status
            : $this->detectSwipeIssueType($swipeRequest->attendance);
        $swipeRequest->requested_clock_in = !empty($request->requested_clock_in) ? ($request->requested_clock_in . ':00') : null;
        $swipeRequest->requested_clock_out = !empty($request->requested_clock_out) ? ($request->requested_clock_out . ':00') : null;
        $swipeRequest->reason = $request->reason;
        $swipeRequest->save();

        return redirect()->back()->with('success', __('Swipe request updated successfully.'));
    }

    public function processSwipeRequest(Request $request, $id)
    {
        if (!Schema::hasTable('attendance_modification_requests')) {
            return redirect()->back()->with('error', __('Swipe request module is not ready yet. Please run migrations.'));
        }

        $validator = \Validator::make($request->all(), [
            'decision' => 'required|in:Approved,Rejected',
            'manager_comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        $swipeRequest = AttendanceModificationRequest::with('attendance')->find($id);
        if (empty($swipeRequest)) {
            return redirect()->back()->with('error', __('Swipe request not found.'));
        }

        if ($swipeRequest->status !== 'Pending') {
            return redirect()->back()->with('error', __('This request has already been processed.'));
        }

        $currentEmployee = \Auth::user()->employee;
        $isAllowed = false;
        if (\Auth::user()->type === 'employee' && !empty($currentEmployee)) {
            $isAllowed = (int) $swipeRequest->manager_employee_id === (int) $currentEmployee->id;
        } else {
            $isAllowed = (int) $swipeRequest->created_by === (int) \Auth::user()->creatorId();
        }

        if (!$isAllowed) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if ($request->decision === 'Approved') {
            $attendance = $swipeRequest->attendance;
            if (empty($attendance)) {
                return redirect()->back()->with('error', __('Linked attendance record not found.'));
            }

            $oldSnapshot = [
                'status' => $attendance->status,
                'clock_in' => $attendance->clock_in,
                'clock_out' => $attendance->clock_out,
                'late' => $attendance->late,
                'early_leaving' => $attendance->early_leaving,
                'overtime' => $attendance->overtime,
                'late_mark' => $attendance->late_mark,
                'early_mark' => $attendance->early_mark,
                'less_hours_mark' => $attendance->less_hours_mark,
                'deduction_units' => $attendance->deduction_units,
            ];

            $updateData = [
                'status' => 'Leave',
                'clock_in' => '00:00:00',
                'clock_out' => '00:00:00',
                'late' => '00:00:00',
                'early_leaving' => '00:00:00',
                'overtime' => '00:00:00',
                'late_mark' => 0,
                'early_mark' => 0,
                'less_hours_mark' => 0,
                'deduction_units' => 1,
            ];

            $attendance->update($updateData);

            $this->createSwipeRequestLog($swipeRequest, 'Approved', $oldSnapshot, $updateData, $request->manager_comment);
        } else {
            $this->createSwipeRequestLog($swipeRequest, 'Rejected', null, null, $request->manager_comment);
        }

        $swipeRequest->status = $request->decision;
        $swipeRequest->manager_comment = $request->manager_comment;
        $swipeRequest->reviewed_by = !empty($currentEmployee) ? $currentEmployee->id : null;
        $swipeRequest->reviewed_at = now();
        $swipeRequest->save();

        return redirect()->back()->with('success', __('Swipe request has been processed successfully.'));
    }

    protected function createSwipeRequestLog(AttendanceModificationRequest $swipeRequest, string $action, ?array $oldSnapshot = null, ?array $newSnapshot = null, ?string $remarks = null): void
    {
        if (!Schema::hasTable('attendance_modification_request_logs')) {
            return;
        }

        AttendanceModificationRequestLog::create([
            'attendance_modification_request_id' => $swipeRequest->id,
            'attendance_employee_id' => $swipeRequest->attendance_employee_id,
            'employee_id' => $swipeRequest->employee_id,
            'manager_employee_id' => $swipeRequest->manager_employee_id,
            'action' => $action,
            'old_snapshot' => $oldSnapshot,
            'new_snapshot' => $newSnapshot,
            'remarks' => $remarks,
            'created_by' => \Auth::user()->creatorId(),
        ]);
    }

    protected function detectSwipeIssueType(?AttendanceEmployee $attendance): string
    {
        if (empty($attendance)) {
            return 'Leave';
        }

        $status = strtolower((string) $attendance->status);
        if ($status === 'half day') {
            return 'Half Day';
        }
        if (in_array($status, ['leave', 'absent'])) {
            return 'Leave';
        }

        $lateMinutes = $this->durationToMinutes((string) ($attendance->late ?? '00:00:00'));
        $earlyMinutes = $this->durationToMinutes((string) ($attendance->early_leaving ?? '00:00:00'));

        if ($lateMinutes > 0) {
            return 'Late Mark';
        }
        if ($earlyMinutes > 0) {
            return 'Early After Leave';
        }

        return 'Present';
    }

    protected function buildAttendanceAnalytics($attendanceEmployee, array $attendanceSummary): array
    {
        $records = $attendanceEmployee->values();

        $summaryByEmployee = [];
        foreach ($attendanceSummary as $summaryItem) {
            $summaryByEmployee[$summaryItem['employee_id']] = $summaryItem;
        }

        $grouped = $records->groupBy('employee_id');
        $employeeBreakdown = [];

        foreach ($grouped as $employeeId => $employeeRecords) {
            $employeeRecords = $employeeRecords->values();
            $summaryItem = $summaryByEmployee[$employeeId] ?? null;

            $totalRecords = $employeeRecords->count();
            $presentDays = $employeeRecords->filter(function ($record) {
                return strtolower((string) $record->status) === 'present';
            })->count();
            $halfDayDays = $employeeRecords->filter(function ($record) {
                return strtolower((string) $record->status) === 'half day';
            })->count();
            $absentLeaveDays = $employeeRecords->filter(function ($record) {
                $status = strtolower((string) $record->status);
                return in_array($status, ['absent', 'leave']);
            })->count();

            $absentDays = $employeeRecords->filter(function ($record) {
                return strtolower((string) $record->status) === 'absent';
            })->count();
            $leaveDays = $employeeRecords->filter(function ($record) {
                return strtolower((string) $record->status) === 'leave';
            })->count();

            $employeeBreakdown[] = [
                'employee_id' => $employeeId,
                'employee_name' => !empty($employeeRecords[0]->employee) ? $employeeRecords[0]->employee->name : __('Employee') . ' #' . $employeeId,
                'total_days' => $totalRecords,
                'present_days' => $presentDays,
                'half_days' => $halfDayDays,
                'absent_days' => $absentDays,
                'leave_days' => $leaveDays,
                'absent_leave_days' => $absentLeaveDays,
                'late_marks' => (int) $employeeRecords->sum('late_mark'),
                'early_marks' => (int) $employeeRecords->sum('early_mark'),
                'less_hours_marks' => (int) $employeeRecords->sum('less_hours_mark'),
                'direct_late_half_day_dates' => $summaryItem ? count($summaryItem['direct_late_dates']) : 0,
                'direct_early_half_day_dates' => $summaryItem ? count($summaryItem['direct_early_dates']) : 0,
                'exempt_mark_dates' => $summaryItem ? count($summaryItem['exempt_dates']) : 0,
                'post_exemption_mark_dates' => $summaryItem ? count($summaryItem['post_exemption_dates']) : 0,
                'policy_deduction_sets' => $summaryItem ? count($summaryItem['deduction_groups']) : 0,
                'pending_mark_dates' => $summaryItem ? count($summaryItem['pending_dates']) : 0,
                'total_deduction_units' => (float) $employeeRecords->sum('deduction_units'),
            ];
        }

        $totalDirectLate = (int) array_sum(array_map(function ($item) {
            return count($item['direct_late_dates']);
        }, $attendanceSummary));
        $totalDirectEarly = (int) array_sum(array_map(function ($item) {
            return count($item['direct_early_dates']);
        }, $attendanceSummary));
        $totalExempt = (int) array_sum(array_map(function ($item) {
            return count($item['exempt_dates']);
        }, $attendanceSummary));
        $totalPostExemption = (int) array_sum(array_map(function ($item) {
            return count($item['post_exemption_dates']);
        }, $attendanceSummary));
        $totalPolicySets = (int) array_sum(array_map(function ($item) {
            return count($item['deduction_groups']);
        }, $attendanceSummary));
        $totalPending = (int) array_sum(array_map(function ($item) {
            return count($item['pending_dates']);
        }, $attendanceSummary));

        $uniqueEmployees = $records->pluck('employee_id')->unique()->count();

        return [
            'total_records' => $records->count(),
            'unique_employees' => $uniqueEmployees,
            'present_days' => $records->filter(function ($record) {
                return strtolower((string) $record->status) === 'present';
            })->count(),
            'half_day_days' => $records->filter(function ($record) {
                return strtolower((string) $record->status) === 'half day';
            })->count(),
            'absent_leave_days' => $records->filter(function ($record) {
                $status = strtolower((string) $record->status);
                return in_array($status, ['absent', 'leave']);
            })->count(),
            'late_marks' => (int) $records->sum('late_mark'),
            'early_marks' => (int) $records->sum('early_mark'),
            'less_hours_marks' => (int) $records->sum('less_hours_mark'),
            'direct_late_half_day_dates' => $totalDirectLate,
            'direct_early_half_day_dates' => $totalDirectEarly,
            'exempt_mark_dates' => $totalExempt,
            'post_exemption_mark_dates' => $totalPostExemption,
            'policy_deduction_sets' => $totalPolicySets,
            'pending_mark_dates' => $totalPending,
            'total_deduction_units' => (float) $records->sum('deduction_units'),
            'employee_breakdown' => $employeeBreakdown,
        ];
    }

    protected function buildAttendancePolicySummary($attendanceEmployee, array $settings): array
    {
        $summary = [];
        $exceptionLimit = max(0, (int) ($settings['exception_limit'] ?? 0));
        $deductionTriggerCount = $this->deductionTriggerCount((string) ($settings['deduction_policy'] ?? 'every1'));
        $directLateThreshold = (int) ($settings['half_day_deduction_minutes'] ?? 60);
        $directEarlyThreshold = (int) ($settings['half_day_deduction_minutes'] ?? 60);

        $groupedByEmployee = $attendanceEmployee->sortBy('date')->groupBy('employee_id');

        foreach ($groupedByEmployee as $employeeId => $records) {
            $employeeRecords = $records->values();
            $empRecord = $employeeRecords[0]->employee ?? null;
            $empShift = !empty($empRecord) ? $empRecord->shift : null;
            $employeeName = !empty($empRecord) ? $empRecord->name : __('Employee') . ' #' . $employeeId;
            $shiftStart = !empty($empShift) ? (string) $empShift->start_time : (string) Utility::getValByName('company_start_time');
            $shiftEnd = !empty($empShift) ? (string) $empShift->end_time : (string) Utility::getValByName('company_end_time');
            $shiftName = !empty($empShift) ? $empShift->name : __('Default');

            // Quick stats (raw counts from attendance status; case-insensitive)
            $totalDays = $employeeRecords->count();
            $rawPresentCount = $employeeRecords->filter(static function ($r) {
                return strtolower((string) ($r->status ?? '')) === 'present';
            })->count();
            $rawAbsentCount = $employeeRecords->filter(static function ($r) {
                return strtolower((string) ($r->status ?? '')) === 'absent';
            })->count();
            $rawLeaveCount = $employeeRecords->filter(static function ($r) {
                return strtolower((string) ($r->status ?? '')) === 'leave';
            })->count();
            $rawHalfDayCount = $employeeRecords->filter(static function ($r) {
                return strtolower((string) ($r->status ?? '')) === 'half day';
            })->count();
            $lateCount = (int) $employeeRecords->sum('late_mark');
            $earlyCount = (int) $employeeRecords->sum('early_mark');
            $totalDeductUnits = (float) $employeeRecords->sum('deduction_units');

            // Month total days & weekly offs (respect joining date)
            $firstDate = $employeeRecords->min('date');
            $monthStart = $firstDate ? Carbon::parse($firstDate)->startOfMonth()->startOfDay() : null;
            $monthEnd = $firstDate ? Carbon::parse($firstDate)->endOfMonth()->startOfDay() : null;
            $empDoj = !empty($empRecord) ? Carbon::parse($empRecord->company_doj) : null;

            // If employee joined mid-month, count days only from DOJ
            if ($monthStart && $empDoj && $empDoj->gt($monthStart) && $empDoj->lte($monthEnd)) {
                $effectiveStart = $empDoj->copy();
            } else {
                $effectiveStart = $monthStart ? $monthStart->copy() : null;
            }

            if ($effectiveStart && $monthEnd) {
                $monthTotalDays = (int) round($effectiveStart->diffInDays($monthEnd)) + 1;
                // Count weekly off days in the effective range using company setting
                $weeklyOffDaysSetting = array_map('intval', array_filter(
                    explode(',', (string) Utility::getValByName('weekly_off_days')),
                    fn($v) => $v !== ''
                ));
                if (empty($weeklyOffDaysSetting)) {
                    $weeklyOffDaysSetting = [Carbon::SUNDAY]; // fallback
                }
                $weeklyOffDates = [];
                $d = $effectiveStart->copy();
                while ($d->lte($monthEnd)) {
                    if (in_array($d->dayOfWeek, $weeklyOffDaysSetting)) {
                        $weeklyOffDates[$d->toDateString()] = true;
                    }
                    $d->addDay();
                }

                $weeklyOffs = count($weeklyOffDates);
                if ($weeklyOffs > 0) {
                    // If attendance has LEAVE on a weekly off (e.g. sandwich policy / manual adjustments),
                    // don't count the same date as W/OFF to keep totals consistent.
                    $leaveOnWeeklyOff = 0;
                    foreach ($employeeRecords as $rec) {
                        if (strtolower((string) ($rec->status ?? '')) !== 'leave') {
                            continue;
                        }
                        $dk = (string) ($rec->date ?? '');
                        if ($dk !== '' && isset($weeklyOffDates[$dk])) {
                            $leaveOnWeeklyOff++;
                        }
                    }
                    $weeklyOffs = max(0, $weeklyOffs - $leaveOnWeeklyOff);
                }
            } else {
                $monthTotalDays = 0;
                $weeklyOffs = 0;
            }

            // ── Categorize Half Days ──
            // Has approved leave → LEAVE (0.5 added to L stat)
            // No leave + direct late (late >= threshold) → HD deduction bucket (Late ½ Day)
            // No leave + direct early (early >= threshold) → HD deduction bucket (Early ½ Day)
            // No leave + regular → ABSENT (0.5 added to A stat)
            $hdLeaveCount = 0;
            $hdAbsentCount = 0;
            $hdPolicyForcedCount = 0; // Late ½ Day: HD without leave caused by late >= threshold
            $hdLeaveDates = [];
            $hdAbsentDates = [];
            $hdPolicyForcedDates = [];
            $earlyHDCount = 0; // Early ½ Day (direct early leaving) count across statuses
            $hdRecords = $employeeRecords->filter(static function ($r) {
                return strtolower((string) ($r->status ?? '')) === 'half day';
            });
            foreach ($hdRecords as $hdRec) {
                $dayNum = Carbon::parse($hdRec->date)->format('j');
                $hasLeave = \DB::table('leaves')
                    ->where('employee_id', $employeeId)
                    ->where('start_date', '<=', $hdRec->date)
                    ->where('end_date', '>=', $hdRec->date)
                    ->where('status', 'Approved')
                    ->exists();
                $lateMinutes = $this->durationToMinutes((string) ($hdRec->late ?? '00:00:00'));
                $earlyMinutes = $this->durationToMinutes((string) ($hdRec->early_leaving ?? '00:00:00'));
                $isPolicyForced = ($lateMinutes >= $directLateThreshold);
                $isDirectEarly = ($earlyMinutes >= $directEarlyThreshold);

                if ($hasLeave) {
                    $hdLeaveCount++;
                    $hdLeaveDates[] = $dayNum;
                } elseif ($isPolicyForced) {
                    // Late ½ Day — goes to HD deduction, not A
                    $hdPolicyForcedCount++;
                    $hdPolicyForcedDates[] = $dayNum;
                } elseif ($isDirectEarly) {
                    // Early ½ Day — goes to HD deduction, not A
                    $earlyHDCount++;
                } else {
                    // Regular HD without leave — goes to A
                    $hdAbsentDates[] = $dayNum;
                    $hdAbsentCount++;
                }
            }

            // ── Early ½ Day detection ──
            foreach ($employeeRecords as $rec) {
                if (strtolower($rec->status) === 'present') {
                    $earlyMinutes = $this->durationToMinutes((string) ($rec->early_leaving ?? '00:00:00'));
                    if ($earlyMinutes >= $directEarlyThreshold) {
                        $earlyHDCount++;
                    }
                }
            }

            // L = full leaves + half_day leaves (0.5 each)
            $leaveOnlyCount = $rawLeaveCount + ($hdLeaveCount * 0.5);
            // A = full absents + half_day absents (0.5 each)
            $absentOnlyCount = $rawAbsentCount + ($hdAbsentCount * 0.5);
            $halfDayCount = $rawHalfDayCount;
            $absentCount = $rawAbsentCount + $rawLeaveCount;

            // NOTE: hdDeduction and presentCount are calculated AFTER the marks loop below
            //       because we need deduction_groups count first.

            $markIndex = 0;
            $graceDates = [];
            $exemptDates = [];
            $postExemptionDates = [];
            $directLateDates = [];
            $directEarlyDates = [];
            $halfDayDates = [];
            $leaveDatesArr = [];
            $absentDatesArr = [];

            $graceLateMinutes = (int) ($settings['grace_late'] ?? 0);
            $graceEarlyMinutes = (int) ($settings['grace_early'] ?? 0);

            foreach ($employeeRecords as $record) {
                $lateMinutes = $this->durationToMinutes((string) ($record->late ?? '00:00:00'));
                $earlyMinutes = $this->durationToMinutes((string) ($record->early_leaving ?? '00:00:00'));
                $isDirectLate = $lateMinutes >= $directLateThreshold;
                $isDirectEarly = $earlyMinutes >= $directEarlyThreshold;
                $dayNum = Carbon::parse($record->date)->format('j');
                $status = strtolower((string) ($record->status ?? ''));

                // Track half day, leave, absent dates
                if ($status === 'half day') $halfDayDates[] = $dayNum;
                if ($status === 'present' && $isDirectEarly) $halfDayDates[] = $dayNum; // Early ½ Day → also half day
                if ($status === 'leave') $leaveDatesArr[] = $dayNum;
                if ($status === 'absent') $absentDatesArr[] = $dayNum;

                // Rule 1: Late/early but within grace period AND no mark was given
                $hasLateMark = ((int) ($record->late_mark ?? 0) === 1);
                $hasEarlyMark = ((int) ($record->early_mark ?? 0) === 1);
                if ($lateMinutes > 0 && $lateMinutes <= $graceLateMinutes && !$hasLateMark) {
                    $graceDates[] = $dayNum;
                }
                if ($earlyMinutes > 0 && $earlyMinutes <= $graceEarlyMinutes && !$hasEarlyMark) {
                    $graceDates[] = $dayNum;
                }

                if ($isDirectLate) {
                    $directLateDates[] = $dayNum;
                }

                if ($isDirectEarly) {
                    $directEarlyDates[] = $dayNum;
                }

                // Count each mark separately - a day can have both late + early = 2 marks
                $marksOnDay = 0;
                if (((int) ($record->late_mark ?? 0) === 1) && !$isDirectLate) $marksOnDay++;
                if (((int) ($record->early_mark ?? 0) === 1) && !$isDirectEarly) $marksOnDay++;

                for ($mi = 0; $mi < $marksOnDay; $mi++) {
                    $markIndex++;
                    if ($markIndex <= $exceptionLimit) {
                        if (!in_array($dayNum, $exemptDates)) $exemptDates[] = $dayNum;
                    } else {
                        if (!in_array($dayNum, $postExemptionDates)) $postExemptionDates[] = $dayNum;
                    }
                }
            }

            $graceDates = array_unique($graceDates);

            // Calculate total overtime hours for this employee (only if OT is enabled)
            $overtimeHours = 0.0;
            $overtimeDays = 0;
            $empSalary = \DB::table('employee_salaries')->where('employee_id', $employeeId)->first();
            $otEnabled = !empty($empSalary) && !empty($empSalary->overtime_enabled);
            if ($otEnabled) {
                foreach ($employeeRecords as $otRec) {
                    $otVal = (string)($otRec->overtime ?? '00:00:00');
                    if ($otVal !== '00:00:00' && $otVal !== '') {
                        $otParts = explode(':', $otVal);
                        $otH = (float)($otParts[0] ?? 0) + (float)($otParts[1] ?? 0) / 60 + (float)($otParts[2] ?? 0) / 3600;
                        if ($otH > 0) {
                            $overtimeHours += $otH;
                            $overtimeDays++;
                        }
                    }
                }
            }

            $deductionGroups = [];
            $pendingDates = [];
            if ($deductionTriggerCount > 0) {
                $chunks = array_chunk($postExemptionDates, $deductionTriggerCount);
                foreach ($chunks as $chunk) {
                    if (count($chunk) === $deductionTriggerCount) {
                        $deductionGroups[] = $chunk;
                    } else {
                        $pendingDates = $chunk;
                    }
                }
            }

            // ── HD = actual policy deductions ──
            // deduction groups (non-exempt marks) + early ½ day + late ½ day (policy-forced HD)
            $hdDeduction = (count($deductionGroups) * 0.5) + ($earlyHDCount * 0.5) + ($hdPolicyForcedCount * 0.5);

            // P = balancing figure so P + L + A + HD + W/OFF = month_total_days
            $presentCount = $monthTotalDays - $leaveOnlyCount - $absentOnlyCount - $hdDeduction - $weeklyOffs;

            $summary[] = [
                'employee_id' => $employeeId,
                'employee_name' => $employeeName,
                'shift_name' => $shiftName,
                'shift_start' => $shiftStart,
                'shift_end' => $shiftEnd,
                'grace_cutoff_time' => $this->graceCutoffTime($shiftStart, (int) ($settings['grace_late'] ?? 0)),
                'exception_limit' => $exceptionLimit,
                'deduction_trigger_count' => $deductionTriggerCount,
                'half_day_minutes' => (int) ($settings['half_day_deduction_minutes'] ?? 90),
                'total_days' => $totalDays,
                'month_total_days' => $monthTotalDays,
                'weekly_offs' => $weeklyOffs,
                'present_count' => $presentCount,
                'absent_only_count' => $absentOnlyCount,
                'leave_only_count' => $leaveOnlyCount,
                'absent_count' => $absentCount,
                'half_day_count' => $halfDayCount,
                'hd_deduction' => $hdDeduction,
                'hd_leave_count' => $hdLeaveCount,
                'hd_absent_count' => $hdAbsentCount,
                'hd_policy_forced_count' => $hdPolicyForcedCount,
                'hd_leave_dates' => $hdLeaveDates,
                'hd_absent_dates' => $hdAbsentDates,
                'hd_policy_forced_dates' => $hdPolicyForcedDates,
                'early_hd_count' => $earlyHDCount,
                'deduction_groups_count' => count($deductionGroups),
                'late_count' => $lateCount,
                'early_count' => $earlyCount,
                'total_deduct_units' => $totalDeductUnits,
                'half_day_dates' => $halfDayDates,
                'leave_dates' => $leaveDatesArr,
                'absent_dates' => $absentDatesArr,
                'grace_dates' => array_values($graceDates),
                'exempt_dates' => $exemptDates,
                'direct_late_dates' => $directLateDates,
                'direct_early_dates' => $directEarlyDates,
                'post_exemption_dates' => $postExemptionDates,
                'deduction_groups' => $deductionGroups,
                'pending_dates' => $pendingDates,
                'overtime_enabled' => $otEnabled,
                'overtime_hours' => round($overtimeHours, 2),
                'overtime_days' => $overtimeDays,
            ];
        }

        return $summary;
    }

    protected function syncApprovedLeavesToAttendance(array $employeeIds, string $startDate, string $endDate, int $creatorId): void
    {
        if (empty($employeeIds)) {
            return;
        }
        if (!Schema::hasTable('leaves')) {
            return;
        }

        try {
            $rangeStart = Carbon::parse($startDate)->startOfDay();
            $rangeEnd = Carbon::parse($endDate)->startOfDay();
        } catch (\Throwable $th) {
            return;
        }
        if ($rangeEnd->lt($rangeStart)) {
            return;
        }

        $settings = Utility::settings();
        $countRule = (string) ($settings['leave_count_rule'] ?? 'working_days');
        $sandwichPolicy = ((string) ($settings['leave_sandwich_policy'] ?? 'off')) === 'on';
        $holidayClubbing = ((string) ($settings['leave_holiday_clubbing'] ?? 'off')) === 'on';

        $weeklyOffDays = array_filter(
            array_map('trim', explode(',', (string) ($settings['weekly_off_days'] ?? Utility::getValByName('weekly_off_days') ?? '0'))),
            static fn($value) => $value !== ''
        );
        $weeklyOffDays = array_map('intval', $weeklyOffDays);
        if (empty($weeklyOffDays)) {
            $weeklyOffDays = [Carbon::SUNDAY];
        }

        $holidayDates = [];
        if (!$holidayClubbing && Schema::hasTable('holidays')) {
            $holidays = Holiday::where('created_by', $creatorId)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($query) use ($startDate, $endDate) {
                            $query->where('start_date', '<=', $startDate)->where('end_date', '>=', $endDate);
                        });
                })
                ->get(['start_date', 'end_date']);

            foreach ($holidays as $holiday) {
                try {
                    $hStart = Carbon::parse($holiday->start_date)->startOfDay();
                    $hEnd = Carbon::parse($holiday->end_date)->startOfDay();
                } catch (\Throwable $th) {
                    continue;
                }
                for ($d = $hStart->copy(); $d->lte($hEnd); $d->addDay()) {
                    $holidayDates[$d->toDateString()] = true;
                }
            }
        }

        $leaves = Leave::where('created_by', $creatorId)
            ->whereIn('employee_id', $employeeIds)
            ->where('status', 'Approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($query) use ($startDate, $endDate) {
                        $query->where('start_date', '<=', $startDate)->where('end_date', '>=', $endDate);
                    });
            })
            ->get(['employee_id', 'start_date', 'end_date', 'day_type']);

        if ($leaves->isEmpty()) {
            return;
        }

        $existing = AttendanceEmployee::where('created_by', $creatorId)
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->get(['id', 'employee_id', 'date', 'status']);

        $existingMap = []; // employee_id => date => row
        foreach ($existing as $row) {
            $existingMap[(int) $row->employee_id][(string) $row->date] = $row;
        }

        $now = now();
        $rowsToInsert = [];

        $baseLeaveUpdate = [
            'status' => 'Leave',
            'clock_in' => '00:00:00',
            'clock_out' => '00:00:00',
            'late' => '00:00:00',
            'early_leaving' => '00:00:00',
            'overtime' => '00:00:00',
            'total_rest' => '00:00:00',
            'updated_at' => $now,
        ];
        if (Schema::hasColumn('attendance_employees', 'late_mark')) $baseLeaveUpdate['late_mark'] = 0;
        if (Schema::hasColumn('attendance_employees', 'early_mark')) $baseLeaveUpdate['early_mark'] = 0;
        if (Schema::hasColumn('attendance_employees', 'less_hours_mark')) $baseLeaveUpdate['less_hours_mark'] = 0;
        if (Schema::hasColumn('attendance_employees', 'deduction_units')) $baseLeaveUpdate['deduction_units'] = 0;
        if (Schema::hasColumn('attendance_employees', 'device_type')) $baseLeaveUpdate['device_type'] = 'leave_sync';

        $baseHalfLeaveUpdate = $baseLeaveUpdate;
        $baseHalfLeaveUpdate['status'] = 'Half Day';

        foreach ($leaves as $leave) {
            $dayType = strtolower((string) ($leave->day_type ?? 'full_day'));

            try {
                $leaveStart = Carbon::parse($leave->start_date)->startOfDay();
                $leaveEnd = Carbon::parse($leave->end_date)->startOfDay();
            } catch (\Throwable $th) {
                continue;
            }
            if ($leaveEnd->lt($leaveStart)) {
                continue;
            }

            // Half day leave is treated as a single date. (Leave module returns 0.5 for non-full_day.)
            if ($dayType !== 'full_day') {
                $leaveEnd = $leaveStart->copy();
            }

            $cursor = $leaveStart->copy();
            if ($cursor->lt($rangeStart)) {
                $cursor = $rangeStart->copy();
            }
            $limit = $leaveEnd->copy();
            if ($limit->gt($rangeEnd)) {
                $limit = $rangeEnd->copy();
            }

            $empId = (int) $leave->employee_id;

            while ($cursor->lte($limit)) {
                $dateKey = $cursor->toDateString();

                $include = true;
                if (!$sandwichPolicy && $countRule !== 'calendar_days') {
                    if (in_array($cursor->dayOfWeek, $weeklyOffDays, true)) {
                        $include = false;
                    } elseif (!$holidayClubbing && isset($holidayDates[$dateKey])) {
                        $include = false;
                    }
                }

                if ($include) {
                    $existingRow = $existingMap[$empId][$dateKey] ?? null;
                    $update = $dayType === 'full_day' ? $baseLeaveUpdate : $baseHalfLeaveUpdate;

                    if ($existingRow) {
                        $currentStatus = strtolower((string) ($existingRow->status ?? ''));
                        $targetStatus = strtolower((string) ($update['status'] ?? ''));
                        if ($currentStatus !== $targetStatus) {
                            AttendanceEmployee::where('id', $existingRow->id)->update($update);
                            $existingRow->status = $update['status'];
                        }
                    } else {
                        $row = [
                            'employee_id' => $empId,
                            'date' => $dateKey,
                            'status' => $update['status'],
                            'clock_in' => $update['clock_in'],
                            'clock_out' => $update['clock_out'],
                            'late' => $update['late'],
                            'early_leaving' => $update['early_leaving'],
                            'overtime' => $update['overtime'],
                            'total_rest' => $update['total_rest'],
                            'created_by' => $creatorId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                        if (Schema::hasColumn('attendance_employees', 'late_mark')) $row['late_mark'] = 0;
                        if (Schema::hasColumn('attendance_employees', 'early_mark')) $row['early_mark'] = 0;
                        if (Schema::hasColumn('attendance_employees', 'less_hours_mark')) $row['less_hours_mark'] = 0;
                        if (Schema::hasColumn('attendance_employees', 'deduction_units')) $row['deduction_units'] = 0;
                        if (Schema::hasColumn('attendance_employees', 'device_type')) $row['device_type'] = 'leave_sync';

                        $rowsToInsert[] = $row;
                        $existingMap[$empId][$dateKey] = (object) ['id' => null, 'status' => $row['status']];
                    }
                }

                $cursor->addDay();
            }
        }

        if (!empty($rowsToInsert)) {
            AttendanceEmployee::insert($rowsToInsert);
        }
    }

    protected function deductionTriggerCount(string $policy): int
    {
        if ($policy === 'every2') {
            return 2;
        }
        if ($policy === 'every3') {
            return 3;
        }

        return 1;
    }

    protected function durationToMinutes(string $duration): int
    {
        $parts = explode(':', $duration);
        $hours = isset($parts[0]) ? (int) $parts[0] : 0;
        $minutes = isset($parts[1]) ? (int) $parts[1] : 0;

        return max(0, ($hours * 60) + $minutes);
    }

    protected function graceCutoffTime(?string $startTime, int $graceMinutes): string
    {
        try {
            $cleanTime = !empty($startTime) ? $startTime : '09:00:00';
            $format = strlen($cleanTime) === 5 ? 'H:i' : 'H:i:s';
            return Carbon::createFromFormat($format, $cleanTime)->addMinutes($graceMinutes)->format('h:i A');
        } catch (\Throwable $th) {
            return Carbon::now()->startOfDay()->addHours(9)->addMinutes($graceMinutes)->format('h:i A');
        }
    }

    public function create()
    {
        if (\Auth::user()->can('Create Attendance')) {
            $employees = User::where('created_by', '=', Auth::user()->creatorId())->where('type', '=', "employee")->get()->pluck('name', 'id');

            return view('attendance.create', compact('employees'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('Create Attendance')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'employee_id' => 'required',
                    'date' => 'required',
                    'clock_in' => 'required',
                    'clock_out' => 'required',
                    'photo' => 'required|image|max:10240',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $startTime  = Utility::getValByName('company_start_time');
            $endTime    = Utility::getValByName('company_end_time');
            $attendance = AttendanceEmployee::where('employee_id', '=', $request->employee_id)->where('date', '=', $request->date)->where('clock_out', '=', '00:00:00')->get()->toArray();
            if ($attendance) {
                return redirect()->route('attendanceemployee.index')->with('error', __('Employee Attendance Already Created.'));
            } else {
                $date = date("Y-m-d");

                $metrics = $this->calculateAttendanceMetrics(
                    (int) $request->employee_id,
                    $request->date,
                    $request->clock_in . ':00',
                    $request->clock_out . ':00'
                );

                $employeeAttendance                = new AttendanceEmployee();
                $employeeAttendance->employee_id   = $request->employee_id;
                $employeeAttendance->date          = $request->date;
                $employeeAttendance->status        = $metrics['attendance_status'];
                $employeeAttendance->clock_in      = $request->clock_in . ':00';
                $employeeAttendance->clock_out     = $request->clock_out . ':00';
                $employeeAttendance->late          = $metrics['late'];
                $employeeAttendance->early_leaving = $metrics['early_leaving'];
                $employeeAttendance->overtime      = $metrics['overtime'];
                $employeeAttendance->late_mark     = $metrics['late_mark'];
                $employeeAttendance->early_mark    = $metrics['early_mark'];
                $employeeAttendance->less_hours_mark = $metrics['less_hours_mark'];
                $employeeAttendance->deduction_units = $metrics['deduction_units'];
                $employeeAttendance->total_rest    = '00:00:00';

                $photoPathRel = null;
                if ($request->hasFile('photo')) {
                    $photoFile = $request->file('photo');
                    if ($photoFile->getSize() >= self::MIN_ATTENDANCE_PHOTO_BYTES) {
                        $photoName = time() . '_' . (int) $request->employee_id . '_manual.jpg';
                        if (! file_exists(public_path('uploads/attendance'))) {
                            mkdir(public_path('uploads/attendance'), 0777, true);
                        }
                        $photoFile->move(public_path('uploads/attendance'), $photoName);
                        $full = public_path('uploads/attendance/' . $photoName);
                        if (is_file($full) && filesize($full) >= self::MIN_ATTENDANCE_PHOTO_BYTES) {
                            $photoPathRel = 'uploads/attendance/' . $photoName;
                        }
                    }
                }
                if (empty($photoPathRel)) {
                    return redirect()->back()->with('error', __('A valid clock-in photo is required. Please upload a clear image.'));
                }
                $employeeAttendance->photo = $photoPathRel;
                if (\Schema::hasColumn('attendance_employees', 'photo_verified')) {
                    $employeeAttendance->photo_verified = 0;
                }
                
                // Calculate and store professional period at time of attendance (only if columns exist)
                if (\Schema::hasColumn('attendance_employees', 'professional_years_at_attendance')) {
                    $employee = Employee::find($request->employee_id);
                    $professionalPeriod = $this->calculateProfessionalPeriodAtDate($employee, $request->date);
                    $employeeAttendance->professional_years_at_attendance = $professionalPeriod['professional_years'];
                    $employeeAttendance->professional_months_at_attendance = $professionalPeriod['professional_months'];
                    $employeeAttendance->professional_days_at_attendance = $professionalPeriod['professional_days'];
                    $employeeAttendance->in_probation_at_attendance = $professionalPeriod['in_probation'];
                }
                
                $employeeAttendance->created_by    = \Auth::user()->creatorId();
                $employeeAttendance->save();

                return redirect()->route('attendanceemployee.index')->with('success', __('Employee attendance successfully created.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    public function show(Request $request)
    {
        // return redirect()->back();
        return redirect()->route('attendanceemployee.index');
    }
    public function edit($id)
    {
        if (\Auth::user()->can('Edit Attendance')) {
            $attendanceEmployee = AttendanceEmployee::where('id', $id)->first();
            $employees          = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            return view('attendance.edit', compact('attendanceEmployee', 'employees'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, $id)
    {
        if (\Auth::user()->type == 'company' || \Auth::user()->type == 'hr') {
            $employeeId      = AttendanceEmployee::where('employee_id', $request->employee_id)->first();
            $check = AttendanceEmployee::where('id', '=', $id)->where('employee_id', '=', $request->employee_id)->where('date', $request->date)->first();

            if (!empty($employeeId) || !empty($check)) {
                $startTime = Utility::getValByName('company_start_time');
                $endTime   = Utility::getValByName('company_end_time');

                $clockIn = $request->clock_in;
                $clockOut = $request->clock_out;

                if ($clockIn) {
                    $status = "present";
                } else {
                    $status = "leave";
                }

                $metrics = $this->calculateAttendanceMetrics(
                    (int) $request->employee_id,
                    $request->date,
                    $clockIn,
                    $clockOut,
                    (int) $check->id
                );
                
                if ($check->date == date('Y-m-d')) {
                    $updateData = [
                        'status' => $metrics['attendance_status'],
                        'late' => $metrics['late'],
                        'early_leaving' => $metrics['early_leaving'],
                        'overtime' => $metrics['overtime'],
                        'late_mark' => $metrics['late_mark'],
                        'early_mark' => $metrics['early_mark'],
                        'less_hours_mark' => $metrics['less_hours_mark'],
                        'deduction_units' => $metrics['deduction_units'],
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                    ];
                    
                    // Add professional period fields only if columns exist
                    if (\Schema::hasColumn('attendance_employees', 'professional_years_at_attendance')) {
                        $employee = Employee::find($request->employee_id);
                        $professionalPeriod = $this->calculateProfessionalPeriodAtDate($employee, $request->date);
                        $updateData['professional_years_at_attendance'] = $professionalPeriod['professional_years'];
                        $updateData['professional_months_at_attendance'] = $professionalPeriod['professional_months'];
                        $updateData['professional_days_at_attendance'] = $professionalPeriod['professional_days'];
                        $updateData['in_probation_at_attendance'] = $professionalPeriod['in_probation'];
                    }
                    
                    $check->update($updateData);

                    return redirect()->route('attendanceemployee.index')->with('success', __('Employee attendance successfully updated.'));
                } else {
                    return redirect()->route('attendanceemployee.index')->with('error', __('You can only update current day attendance.'));
                }
            } else {
                return redirect()->back()->with('error', __('Employee not avaliable'));
            }
        }

        $employeeId = ! empty(\Auth::user()->employee) ? (int) \Auth::user()->employee->id : 0;
        if ($employeeId <= 0) {
            $empByUser = Employee::where('user_id', \Auth::id())->first();
            $employeeId = $empByUser ? (int) $empByUser->id : 0;
        }
        $todayAttendance = AttendanceEmployee::where('employee_id', '=', $employeeId)->where('date', date('Y-m-d'))->first();

        $startTime = Utility::getValByName('company_start_time');
        $endTime   = Utility::getValByName('company_end_time');
        if (Auth::user()->type == 'employee') {

            $date = date("Y-m-d");
            $time = date("H:i:s");

            $metrics = $this->calculateAttendanceMetrics(
                (int) $employeeId,
                $date,
                $todayAttendance->clock_in ?? $time,
                $time,
                (int) $id
            );

            $attendanceEmployee['clock_out']      = $time;
            $attendanceEmployee['status']         = $metrics['attendance_status'];
            $attendanceEmployee['late']           = $metrics['late'];
            $attendanceEmployee['early_leaving']  = $metrics['early_leaving'];
            $attendanceEmployee['overtime']       = $metrics['overtime'];
            $attendanceEmployee['late_mark']      = $metrics['late_mark'];
            $attendanceEmployee['early_mark']     = $metrics['early_mark'];
            $attendanceEmployee['less_hours_mark'] = $metrics['less_hours_mark'];
            $attendanceEmployee['deduction_units'] = $metrics['deduction_units'];

            // Handle clock-out camera and location capture
            $deviceTypeOut = $request->input('device_type_out');
            $latitudeOut = $request->input('latitude_out');
            $longitudeOut = $request->input('longitude_out');
            $addressOut = $request->input('address_out');

            // Clock-out: require photo and verify against profile (same as clock-in)
            $photoOut = null;
            $photoBase64Out = $request->input('photo_base64_out');
            if (!empty($photoBase64Out)) {
                $photoDataOut = explode(',', $photoBase64Out);
                if (count($photoDataOut) > 1) {
                    $photoDecodedOut = base64_decode($photoDataOut[1], true);
                    if ($photoDecodedOut !== false && strlen($photoDecodedOut) >= self::MIN_ATTENDANCE_PHOTO_BYTES) {
                        $photoNameOut = time() . '_' . $employeeId . '_out.jpg';
                        $photoPathOut = public_path('uploads/attendance/' . $photoNameOut);
                        if (!file_exists(public_path('uploads/attendance'))) {
                            mkdir(public_path('uploads/attendance'), 0777, true);
                        }
                        file_put_contents($photoPathOut, $photoDecodedOut);
                        if (is_file($photoPathOut) && filesize($photoPathOut) >= self::MIN_ATTENDANCE_PHOTO_BYTES) {
                            $photoOut = 'uploads/attendance/' . $photoNameOut;
                        } else {
                            @unlink($photoPathOut);
                        }
                    }
                }
            }
            if (empty($photoOut)) {
                return redirect()->route('dashboard')->with('error', __('Please open the clock-out button, capture your photo, then confirm. Photo is required for clock-out.'));
            }
            $photoOutFullPath = public_path($photoOut);
            $verificationOut = $this->verifyPhotoWithProfile($photoOutFullPath);
            $photoOutVerified = ($verificationOut['verified'] === true);
            // Same as clock-in: if profile photo exists and verification fails, block clock-out
            if (!$photoOutVerified && !empty($this->getProfilePhotoPath())) {
                return redirect()->route('dashboard')->with('error', $verificationOut['message'] ?? __('Clock-out photo does not match profile. Please try again.'));
            }
            if (!$photoOutVerified && empty($this->getProfilePhotoPath())) {
                $photoOutVerified = false;
            }

            // Update attendance with clock-out data
            $attendanceEmployee['photo_out'] = $photoOut;
            if (\Schema::hasColumn('attendance_employees', 'photo_out_verified')) {
                $attendanceEmployee['photo_out_verified'] = $photoOutVerified;
            }
            if (!empty($deviceTypeOut)) {
                $attendanceEmployee['device_type_out'] = $deviceTypeOut;
            }
            if (!empty($latitudeOut)) {
                $attendanceEmployee['latitude_out'] = $latitudeOut;
            }
            if (!empty($longitudeOut)) {
                $attendanceEmployee['longitude_out'] = $longitudeOut;
            }
            if (!empty($addressOut)) {
                $attendanceEmployee['address_out'] = $addressOut;
            }

            if (!empty($request->date)) {
                $attendanceEmployee['date']       =  $request->date;
            }
            AttendanceEmployee::where('id', $id)->update($attendanceEmployee);

            $successMsg = __('Employee successfully clock Out.');
            if (!$photoOutVerified) {
                $successMsg .= ' ' . __('Photo could not be verified; marked as Not verified.');
            }
            return redirect()->route('dashboard')->with('success', $successMsg);
        }

        return redirect()->back()->with('error', __('Attendance could not be updated. Use the dashboard to clock out with a photo, or ask HR to edit today\'s attendance.'));
    }

    public function destroy($id)
    {
        if (\Auth::user()->can('Delete Attendance')) {
            $attendance = AttendanceEmployee::where('id', $id)->first();

            $attendance->delete();

            return redirect()->route('attendanceemployee.index')->with('success', __('Attendance successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function attendance(Request $request)
    {
        $settings = Utility::settings();
        $policySettings = $this->getAttendanceSettings();

        if (!empty($settings['ip_restrict']) && $settings['ip_restrict'] == 'on') {
            $userIp = request()->ip();
            $ip     = IpRestrict::where('created_by', Auth::user()->creatorId())->whereIn('ip', [$userIp])->first();
            if (empty($ip)) {
                return redirect()->back()->with('error', __('This IP is not allowed to clock in & clock out.'));
            }
        }

        // Resolve employee ID: use relation first, then fallback to query by user_id (relation may be null after login/session)
        $employeeId = 0;
        if (!empty(\Auth::user()->employee)) {
            $employeeId = (int) \Auth::user()->employee->id;
        }
        if ($employeeId <= 0) {
            $empByUser = Employee::where('user_id', \Auth::user()->id)->first();
            $employeeId = $empByUser ? (int) $empByUser->id : 0;
        }
        if ($employeeId <= 0) {
            return redirect()->back()->with('error', __('Employee profile not found. Cannot mark attendance. Please contact HR to link your account to an employee.'));
        }

        $startTime = Utility::getValByName('company_start_time');
        $endTime = Utility::getValByName('company_end_time');

        // Find the last clocked out entry for the employee
        $lastClockOutEntry = AttendanceEmployee::orderBy('id', 'desc')
            ->where('employee_id', '=', $employeeId)
            ->where('clock_out', '!=', '00:00:00')
            ->where('date', '=', date('Y-m-d'))
            ->first();

        $date = date("Y-m-d");
        $time = date("H:i:s");

        $lateMark = false;

        if ($lastClockOutEntry != null) {
            // Calculate late based on the difference between the last clock-out time and the current clock-in time
            $lastClockOutTime = $lastClockOutEntry->clock_out;
            $actualClockInTime = $date . ' ' . $time;

            $totalLateSeconds = strtotime($actualClockInTime) - strtotime($date . ' ' . $lastClockOutTime);

            // Ensure late time is non-negative
            $totalLateSeconds = max($totalLateSeconds, 0);

            $hours = abs(floor($totalLateSeconds / 3600));
            $mins = abs(floor($totalLateSeconds / 60 % 60));
            $secs = abs(floor($totalLateSeconds % 60));
            $late = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
        } else {
            // If there is no previous clock-out entry, assume no lateness
            $expectedStartTime = $date . ' ' . $startTime;
            $actualClockInTime = $date . ' ' . $time;

            $totalLateSeconds = strtotime($actualClockInTime) - strtotime($expectedStartTime);

            // Ensure late time is non-negative
            $totalLateSeconds = max($totalLateSeconds, 0);

            $hours = abs(floor($totalLateSeconds / 3600));
            $mins = abs(floor($totalLateSeconds / 60 % 60));
            $secs = abs(floor($totalLateSeconds % 60));
            $late = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
        }

        $lateSeconds = max(0, strtotime('1970-01-01 ' . $late) - strtotime('1970-01-01 00:00:00'));
        $lateMark = $lateSeconds > ($policySettings['grace_late'] * 60);
        $attendanceStatus = $this->resolveAttendanceStatusByLateSeconds(
            $lateSeconds,
            0, // no early leaving at clock-in time
            (int) $policySettings['grace_late'],
            (int) $policySettings['half_day_deduction_minutes']
        );

        // Prevent duplicate "open" clock-in rows for the same day (employee_id must be used, not user id)
        $openSessionToday = AttendanceEmployee::where('employee_id', $employeeId)
            ->where('date', $date)
            ->where('clock_out', '00:00:00')
            ->exists();
        if ($openSessionToday) {
            return redirect()->back()->with('error', __('You are already clocked in. Please clock out first.'));
        }

        // Get device type, location, and photo from request
        $deviceType = $request->input('device_type', 'unknown');
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $address = $request->input('address');
        $photo = null;

        // Handle photo upload (must be a real image file — reject empty/corrupt payloads)
        if ($request->hasFile('photo')) {
            $photoFile = $request->file('photo');
            if ($photoFile->isValid() && $photoFile->getSize() >= self::MIN_ATTENDANCE_PHOTO_BYTES) {
                $photoName = time() . '_' . $employeeId . '.' . $photoFile->getClientOriginalExtension();

                if (! file_exists(public_path('uploads/attendance'))) {
                    mkdir(public_path('uploads/attendance'), 0777, true);
                }

                $photoFile->move(public_path('uploads/attendance'), $photoName);
                $fullPath = public_path('uploads/attendance/' . $photoName);
                if (is_file($fullPath) && filesize($fullPath) >= self::MIN_ATTENDANCE_PHOTO_BYTES) {
                    $photo = 'uploads/attendance/' . $photoName;
                } else {
                    @unlink($fullPath);
                }
            }
        } elseif ($request->filled('photo_base64')) {
            $photoBase64 = $request->input('photo_base64');
            $photoData = explode(',', $photoBase64, 2);
            if (count($photoData) > 1) {
                $photoDecoded = base64_decode($photoData[1], true);
                if ($photoDecoded !== false && strlen($photoDecoded) >= self::MIN_ATTENDANCE_PHOTO_BYTES) {
                    $photoName = time() . '_' . $employeeId . '.jpg';
                    $photoPath = public_path('uploads/attendance/' . $photoName);

                    if (! file_exists(public_path('uploads/attendance'))) {
                        mkdir(public_path('uploads/attendance'), 0777, true);
                    }

                    if (file_put_contents($photoPath, $photoDecoded) !== false
                        && is_file($photoPath)
                        && filesize($photoPath) >= self::MIN_ATTENDANCE_PHOTO_BYTES) {
                        $photo = 'uploads/attendance/' . $photoName;
                    } else {
                        @unlink($photoPath);
                    }
                }
            }
        }

        // Photo is required for clock-in
        if (empty($photo)) {
            return redirect()->back()->with('error', __('Please capture your photo for clock-in (open camera, capture, then confirm).'));
        }
        $clockInFullPath = public_path($photo);
        $verification = $this->verifyPhotoWithProfile($clockInFullPath);
        $photoVerified = ($verification['verified'] === true);
        // If profile photo missing: allow clock-in but mark not verified. If profile exists but no match: block.
        if (!$photoVerified && !empty($this->getProfilePhotoPath())) {
            return redirect()->back()->with('error', $verification['message'] ?? __('Photo does not match your profile. Clock-in not allowed.'));
        }
        if (!$photoVerified && empty($this->getProfilePhotoPath())) {
            // No profile photo yet – allow clock-in, will show as Not verified
            $photoVerified = false;
        }

        $employeeAttendance                = new AttendanceEmployee();
        $employeeAttendance->employee_id   = $employeeId;
        $employeeAttendance->date          = $date;
        $employeeAttendance->status        = $attendanceStatus;
        $employeeAttendance->clock_in      = $time;
        $employeeAttendance->clock_out     = '00:00:00';
        $employeeAttendance->late          = $late;
        $employeeAttendance->early_leaving = '00:00:00';
        $employeeAttendance->overtime      = '00:00:00';
        $employeeAttendance->total_rest    = '00:00:00';
        $employeeAttendance->late_mark     = $lateMark ? 1 : 0;
        $employeeAttendance->early_mark    = 0;
        $employeeAttendance->less_hours_mark = 0;
        $employeeAttendance->deduction_units = 0;
        $employeeAttendance->created_by    = \Auth::user()->creatorId();
        $employeeAttendance->device_type   = $deviceType;
        $employeeAttendance->latitude      = $latitude;
        $employeeAttendance->longitude     = $longitude;
        $employeeAttendance->address       = $address;
        $employeeAttendance->photo         = $photo;
        if (\Schema::hasColumn('attendance_employees', 'photo_verified')) {
            $employeeAttendance->photo_verified = $photoVerified;
        }

        if (\Schema::hasColumn('attendance_employees', 'professional_years_at_attendance')) {
            $employee = Employee::find($employeeId);
            $professionalPeriod = $this->calculateProfessionalPeriodAtDate($employee, $date);
            $employeeAttendance->professional_years_at_attendance = $professionalPeriod['professional_years'];
            $employeeAttendance->professional_months_at_attendance = $professionalPeriod['professional_months'];
            $employeeAttendance->professional_days_at_attendance = $professionalPeriod['professional_days'];
            $employeeAttendance->in_probation_at_attendance = $professionalPeriod['in_probation'];
        }

        $employeeAttendance->save();

        $successMsg = __('Employee Successfully Clock In.');
        if (!$photoVerified) {
            $successMsg .= ' ' . __('Upload your profile photo for verification next time.');
        }
        return redirect()->back()->with('success', $successMsg);
    }

    public function bulkAttendance(Request $request)
    {
        if (\Auth::user()->can('Create Attendance')) {

            $branch = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $branch->prepend('Select Branch', '');

            $department = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $department->prepend('Select Department', '');

            $employees = [];
            if (!empty($request->branch) && !empty($request->department)) {
                $employees = Employee::where('created_by', \Auth::user()->creatorId())->where('branch_id', $request->branch)->where('department_id', $request->department)->get();
            }

            return view('attendance.bulk', compact('employees', 'branch', 'department'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function bulkAttendanceTemplate(Request $request)
    {
        try {
            if (!\Auth::user()->can('Create Attendance')) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
            if (empty($request->branch) || empty($request->department)) {
                return redirect()->back()->with('error', __('Branch & department field required.'));
            }

            $date = $request->date ?: date('Y-m-d');
            $employees = Employee::where('created_by', \Auth::user()->creatorId())
                ->where('branch_id', $request->branch)
                ->where('department_id', $request->department)
                ->get();

            $startTime = Utility::getValByName('company_start_time') ?: '09:00:00';
            $endTime   = Utility::getValByName('company_end_time')   ?: '18:00:00';

            $headings = ['Employee ID', 'Employee Name', 'Date', 'Status (Present/Absent/Leave)', 'Clock In (HH:MM)', 'Clock Out (HH:MM)'];

            $rows = [];
            foreach ($employees as $employee) {
                $att = AttendanceEmployee::where('employee_id', $employee->id)->where('date', $date)->first();
                $rows[] = [
                    \Auth::user()->employeeIdFormat($employee->employee_id),
                    $employee->name,
                    $date,
                    $att->status ?? 'Present',
                    ($att && $att->clock_in && $att->clock_in !== '00:00:00') ? substr($att->clock_in, 0, 5) : substr($startTime, 0, 5),
                    ($att && $att->clock_out && $att->clock_out !== '00:00:00') ? substr($att->clock_out, 0, 5) : substr($endTime, 0, 5),
                ];
            }

            $filename = 'bulk_attendance_template_' . $date . '.csv';
            $callback = function () use ($rows, $headings) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, $headings);
                foreach ($rows as $row) {
                    fputcsv($out, $row);
                }
                fclose($out);
            };

            return response()->stream($callback, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-store, no-cache',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Bulk attendance template failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Template download failed: ' . $e->getMessage());
        }
    }

    public function bulkAttendanceImport(Request $request)
    {
        try {
            if ($denied = $this->denyUnlessAttendanceAdmin()) {
                return $denied;
            }

            if (!\Auth::user()->can('Create Attendance')) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls']);

            $cid = \Auth::user()->creatorId();
            $path = $request->file('file')->getRealPath();
            $handle = fopen($path, 'r');
            if (!$handle) return redirect()->back()->with('error', 'Unable to read uploaded file.');

            $header = fgetcsv($handle);
            if (!$header) { fclose($handle); return redirect()->back()->with('error', 'Empty file.'); }

            $created = 0; $updated = 0; $skipped = 0; $errors = [];
            $rowNum = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue;

                $empIdRaw = trim($row[0] ?? '');
                $date     = trim($row[2] ?? '');
                $status   = trim($row[3] ?? 'Present');
                $clockIn  = trim($row[4] ?? '00:00');
                $clockOut = trim($row[5] ?? '00:00');

                if (!$empIdRaw || !$date) { $skipped++; continue; }

                $empIdNum = (int) preg_replace('/[^0-9]/', '', $empIdRaw);
                $employee = Employee::where('created_by', $cid)->where('employee_id', $empIdNum)->first();
                if (!$employee) { $errors[] = "Row $rowNum: Employee ID '$empIdRaw' not found"; $skipped++; continue; }

                try { $date = date('Y-m-d', strtotime($date)); } catch (\Throwable $e) { $skipped++; continue; }

                $in  = ($clockIn  && $clockIn  !== '00:00') ? date('H:i:s', strtotime($clockIn))  : '00:00:00';
                $out = ($clockOut && $clockOut !== '00:00') ? date('H:i:s', strtotime($clockOut)) : '00:00:00';

                $existing = AttendanceEmployee::where('employee_id', $employee->id)->where('date', $date)->first();
                $isUpdate = (bool) $existing;

                if (strcasecmp($status, 'Present') === 0) {
                    $metrics = $this->calculateAttendanceMetrics(
                        (int) $employee->id, $date, $in, $out, $existing->id ?? null
                    );
                    $att = $existing ?: new AttendanceEmployee();
                    $att->employee_id   = $employee->id;
                    $att->created_by    = $cid;
                    $att->date          = $date;
                    $att->status        = $metrics['attendance_status'];
                    $att->clock_in      = $in;
                    $att->clock_out     = $out;
                    $att->late          = $metrics['late'];
                    $att->early_leaving = $metrics['early_leaving'];
                    $att->overtime      = $metrics['overtime'];
                    $att->late_mark     = $metrics['late_mark'];
                    $att->early_mark    = $metrics['early_mark'];
                    $att->less_hours_mark  = $metrics['less_hours_mark'];
                    $att->deduction_units  = $metrics['deduction_units'];
                    $att->total_rest    = '00:00:00';
                    $att->save();
                } else {
                    $att = $existing ?: new AttendanceEmployee();
                    $att->employee_id   = $employee->id;
                    $att->created_by    = $cid;
                    $att->date          = $date;
                    $att->status        = ucfirst(strtolower($status)); // Absent, Leave
                    $att->clock_in      = '00:00:00';
                    $att->clock_out     = '00:00:00';
                    $att->late          = '00:00:00';
                    $att->early_leaving = '00:00:00';
                    $att->overtime      = '00:00:00';
                    $att->total_rest    = '00:00:00';
                    $att->save();
                }

                $isUpdate ? $updated++ : $created++;
            }
            fclose($handle);

            $msg = "Created: $created, Updated: $updated, Skipped: $skipped";
            if ($errors) $msg .= ' | Errors: ' . implode('; ', array_slice($errors, 0, 5));
            return redirect()->back()->with('success', $msg);
        } catch (\Throwable $e) {
            \Log::error('Bulk attendance import failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function uploadExcelAttendance(Request $request)
    {
        try {
            if ($denied = $this->denyUnlessAttendanceAdmin()) {
                return $denied;
            }

            $request->validate([
                'attendance_file' => 'required|file|mimes:xlsx,xls,csv,txt',
            ]);

            $creatorId = \Auth::user()->creatorId();
            $selectedEmployeeId = (int) ($request->employee_id ?: $request->manager_employee_id ?: 0);
            $sheets = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
                public function array(array $array)
                {
                    return $array;
                }
            }, $request->file('attendance_file'));

            $rows = $sheets[0] ?? [];
            if (empty($rows)) {
                return redirect()->back()->with('error', __('Uploaded file is empty.'));
            }

            $header = array_map(fn($value) => $this->normalizeAttendanceExcelKey($value), array_shift($rows));
            $hasHeader = in_array('date', $header, true) || in_array('clock_in', $header, true) || in_array('employee_id', $header, true);
            if (!$hasHeader) {
                array_unshift($rows, array_values($header));
                $header = ['employee_id', 'date', 'status', 'clock_in', 'clock_out'];
            }

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                if (count(array_filter($row, fn($value) => trim((string) $value) !== '')) === 0) {
                    continue;
                }

                $rowNumber = $index + 2;
                $data = [];
                foreach ($header as $columnIndex => $key) {
                    if ($key !== '') {
                        $data[$key] = $row[$columnIndex] ?? null;
                    }
                }

                $employee = $this->resolveAttendanceExcelEmployee($data, $selectedEmployeeId, $creatorId);
                $date = $this->normalizeAttendanceExcelDate($data['date'] ?? null);
                if (!$employee || !$date) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: employee/date missing";
                    continue;
                }

                $status = $this->normalizeAttendanceExcelStatus($data['status'] ?? 'Present');
                $clockIn = $this->normalizeAttendanceExcelTime($data['clock_in'] ?? null);
                $clockOut = $this->normalizeAttendanceExcelTime($data['clock_out'] ?? null);
                $existing = AttendanceEmployee::where('employee_id', $employee->id)->where('date', $date)->first();
                $isUpdate = (bool) $existing;
                $attendance = $existing ?: new AttendanceEmployee();

                $attendance->employee_id = $employee->id;
                $attendance->created_by = $creatorId;
                $attendance->date = $date;
                $attendance->total_rest = '00:00:00';

                if (in_array($status, ['Absent', 'Leave'], true)) {
                    $attendance->status = $status;
                    $attendance->clock_in = '00:00:00';
                    $attendance->clock_out = '00:00:00';
                    $attendance->late = '00:00:00';
                    $attendance->early_leaving = '00:00:00';
                    $attendance->overtime = '00:00:00';
                    $attendance->late_mark = 0;
                    $attendance->early_mark = 0;
                    $attendance->less_hours_mark = 0;
                    $attendance->deduction_units = $status === 'Absent' ? 1 : 0;
                } else {
                    $clockIn = $clockIn ?: '09:00:00';
                    $clockOut = $clockOut ?: '18:00:00';
                    $metrics = $this->calculateAttendanceMetrics((int) $employee->id, $date, $clockIn, $clockOut, $existing->id ?? null);

                    $attendance->status = $status === 'Half Day' ? 'Half Day' : $metrics['attendance_status'];
                    $attendance->clock_in = $clockIn;
                    $attendance->clock_out = $clockOut;
                    $attendance->late = $metrics['late'];
                    $attendance->early_leaving = $metrics['early_leaving'];
                    $attendance->overtime = $metrics['overtime'];
                    $attendance->late_mark = (int) $metrics['late_mark'];
                    $attendance->early_mark = (int) $metrics['early_mark'];
                    $attendance->less_hours_mark = (int) $metrics['less_hours_mark'];
                    $attendance->deduction_units = $status === 'Half Day' ? 0.5 : $metrics['deduction_units'];
                }

                $professionalPeriod = $this->calculateProfessionalPeriodAtDate($employee, $date);
                $attendance->professional_days_at_attendance = $professionalPeriod['professional_days'];
                $attendance->in_probation_at_attendance = $professionalPeriod['in_probation'];
                $attendance->save();

                $isUpdate ? $updated++ : $created++;
            }

            $message = "Excel upload done. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}";
            if (!empty($errors)) {
                $message .= ' | ' . implode('; ', array_slice($errors, 0, 3));
            }

            return redirect()->back()->with('success', $message);
        } catch (\Throwable $e) {
            \Log::error('Attendance Excel upload failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Excel upload failed: ' . $e->getMessage());
        }
    }

    protected function normalizeAttendanceExcelKey($value): string
    {
        $key = strtolower(trim((string) $value));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);
        $key = trim((string) $key, '_');

        return match ($key) {
            'employee', 'employee_db_id', 'emp_id', 'id' => 'employee_id',
            'employee_code', 'code', 'staff_code' => 'employee_code',
            'attendance_date' => 'date',
            'in', 'in_time', 'clockin', 'clock_in_time' => 'clock_in',
            'out', 'out_time', 'clockout', 'clock_out_time' => 'clock_out',
            default => $key,
        };
    }

    protected function resolveAttendanceExcelEmployee(array $data, int $selectedEmployeeId, int $creatorId): ?Employee
    {
        $employeeId = (int) ($data['employee_id'] ?? 0);
        if ($employeeId <= 0 && $selectedEmployeeId > 0) {
            $employeeId = $selectedEmployeeId;
        }

        if ($employeeId > 0) {
            $employee = Employee::where('created_by', $creatorId)->where('id', $employeeId)->first();
            if ($employee) {
                return $employee;
            }

            return Employee::where('created_by', $creatorId)->where('employee_id', $employeeId)->first();
        }

        $employeeCode = trim((string) ($data['employee_code'] ?? ''));
        if ($employeeCode !== '') {
            return Employee::where('created_by', $creatorId)->where('employee_id', preg_replace('/[^0-9]/', '', $employeeCode))->first();
        }

        $employeeName = trim((string) ($data['name'] ?? $data['employee_name'] ?? ''));
        if ($employeeName !== '') {
            return Employee::where('created_by', $creatorId)->where('name', 'like', $employeeName)->first();
        }

        return null;
    }

    protected function normalizeAttendanceExcelDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            }

            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function normalizeAttendanceExcelTime($value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('H:i:s');
            }

            return Carbon::parse((string) $value)->format('H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function normalizeAttendanceExcelStatus($value): string
    {
        $status = strtolower(trim((string) $value));

        return match ($status) {
            'a', 'absent' => 'Absent',
            'l', 'leave', 'on_leave' => 'Leave',
            'hd', 'half_day', 'half day' => 'Half Day',
            default => 'Present',
        };
    }

    public function bulkAttendanceExport(Request $request)
    {
        try {
            if ($denied = $this->denyUnlessAttendanceAdmin()) {
                return $denied;
            }

            if (!\Auth::user()->can('Create Attendance')) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            if (empty($request->branch) || empty($request->department)) {
                return redirect()->back()->with('error', __('Branch & department field required.'));
            }

            $date = $request->date ?: date('Y-m-d');
            $employees = Employee::where('created_by', \Auth::user()->creatorId())
                ->where('branch_id', $request->branch)
                ->where('department_id', $request->department)
                ->get();

            $branchName = Branch::where('id', $request->branch)->value('name') ?? '';
            $deptName = Department::where('id', $request->department)->value('name') ?? '';

            $headings = ['Employee ID', 'Name', 'Branch', 'Department', 'Date', 'Status', 'Clock In', 'Clock Out', 'Late', 'Early Leaving', 'Overtime'];

            $rows = [];
            foreach ($employees as $employee) {
                $att = AttendanceEmployee::where('employee_id', $employee->id)->where('date', $date)->first();
                $rows[] = [
                    \Auth::user()->employeeIdFormat($employee->employee_id),
                    $employee->name,
                    !empty($employee->branch) ? $employee->branch->name : $branchName,
                    !empty($employee->department) ? $employee->department->name : $deptName,
                    $date,
                    $att->status ?? 'Not Marked',
                    ($att && $att->clock_in && $att->clock_in !== '00:00:00') ? $att->clock_in : '-',
                    ($att && $att->clock_out && $att->clock_out !== '00:00:00') ? $att->clock_out : '-',
                    $att->late ?? '00:00:00',
                    $att->early_leaving ?? '00:00:00',
                    $att->overtime ?? '00:00:00',
                ];
            }

            $filename = 'bulk_attendance_' . $date . '_' . date('His') . '.csv';

            $callback = function () use ($rows, $headings) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, $headings);
                foreach ($rows as $row) {
                    fputcsv($out, $row);
                }
                fclose($out);
            };

            return response()->stream($callback, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-store, no-cache',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Bulk attendance export failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    public function bulkAttendanceData(Request $request)
    {
        if (\Auth::user()->can('Create Attendance')) {
            if (!empty($request->branch) && !empty($request->department)) {
                $startTime = Utility::getValByName('company_start_time');
                $endTime   = Utility::getValByName('company_end_time');
                $date      = $request->date;

                $employees = $request->employee_id;
                $atte      = [];
                foreach ($employees as $employee) {
                    $present = 'present-' . $employee;
                    $in      = 'in-' . $employee;
                    $out     = 'out-' . $employee;
                    $atte[]  = $present;
                    if ($request->$present == 'on') {

                        $in  = date("H:i:s", strtotime($request->$in));
                        $out = date("H:i:s", strtotime($request->$out));

                        $metrics = $this->calculateAttendanceMetrics(
                            (int) $employee,
                            $date,
                            $in,
                            $out,
                            !empty($attendance) ? (int) $attendance->id : null
                        );

                        $attendance = AttendanceEmployee::where('employee_id', '=', $employee)->where('date', '=', $request->date)->first();

                        if (!empty($attendance)) {
                            $employeeAttendance = $attendance;
                        } else {
                            $employeeAttendance              = new AttendanceEmployee();
                            $employeeAttendance->employee_id = $employee;
                            $employeeAttendance->created_by  = \Auth::user()->creatorId();
                        }

                        $employeeAttendance->date          = $request->date;
                        $employeeAttendance->status        = $metrics['attendance_status'];
                        $employeeAttendance->clock_in      = $in;
                        $employeeAttendance->clock_out     = $out;
                        $employeeAttendance->late          = $metrics['late'];
                        $employeeAttendance->early_leaving = $metrics['early_leaving'];
                        $employeeAttendance->overtime      = $metrics['overtime'];
                        $employeeAttendance->late_mark     = $metrics['late_mark'];
                        $employeeAttendance->early_mark    = $metrics['early_mark'];
                        $employeeAttendance->less_hours_mark = $metrics['less_hours_mark'];
                        $employeeAttendance->deduction_units = $metrics['deduction_units'];
                        $employeeAttendance->total_rest    = '00:00:00';
                        $employeeAttendance->save();
                    } else {
                        $attendance = AttendanceEmployee::where('employee_id', '=', $employee)->where('date', '=', $request->date)->first();

                        if (!empty($attendance)) {
                            $employeeAttendance = $attendance;
                        } else {
                            $employeeAttendance              = new AttendanceEmployee();
                            $employeeAttendance->employee_id = $employee;
                            $employeeAttendance->created_by  = \Auth::user()->creatorId();
                        }

                        $employeeAttendance->status        = 'Leave';
                        $employeeAttendance->date          = $request->date;
                        $employeeAttendance->clock_in      = '00:00:00';
                        $employeeAttendance->clock_out     = '00:00:00';
                        $employeeAttendance->late          = '00:00:00';
                        $employeeAttendance->early_leaving = '00:00:00';
                        $employeeAttendance->overtime      = '00:00:00';
                        $employeeAttendance->total_rest    = '00:00:00';
                        $employeeAttendance->save();
                    }
                }

                return redirect()->back()->with('success', __('Employee attendance successfully created.'));
            } else {
                return redirect()->back()->with('error', __('Branch & department field required.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    protected function getAttendanceSettings(): array
    {
        $settings = Utility::settings();
        $halfDayDeductionMinutes = isset($settings['attendance_half_day_deduction_minutes'])
            ? (int) $settings['attendance_half_day_deduction_minutes']
            : (int) round(((float) ($settings['attendance_half_day_deduction_hours'] ?? 1.5)) * 60);

        return [
            'grace_late' => (int) ($settings['attendance_grace_late_minutes'] ?? 0),
            'grace_early' => (int) ($settings['attendance_grace_early_minutes'] ?? 0),
            'exception_limit' => (int) ($settings['attendance_monthly_exception_limit'] ?? 0),
            'half_day_deduction_minutes' => $halfDayDeductionMinutes,
            'deduction_policy' => $settings['attendance_deduction_policy'] ?? 'every1',
            'flexi_required_hours' => (float) ($settings['flexi_required_hours'] ?? 9),
            'flexi_exception_limit' => (int) ($settings['flexi_exception_limit'] ?? 0),
            'flexi_less_hours_policy' => $settings['flexi_less_hours_policy'] ?? 'every3',
        ];
    }

    protected function getEmployeeShiftSchedule(int $employeeId): array
    {
        if (Schema::hasTable('shifts')) {
            $employee = Employee::with('shift')->find($employeeId);
        } else {
            $employee = Employee::find($employeeId);
        }

        if (!empty($employee) && !empty($employee->shift)) {
            return [
                'start' => (string) $employee->shift->start_time,
                'end' => (string) $employee->shift->end_time,
            ];
        }

        $shiftType = strtolower((string) ($employee->shift_type ?? 'morning'));

        if (!in_array($shiftType, ['morning', 'night'])) {
            $shiftType = 'morning';
        }

        $settings = Utility::settings();

        $morningStart = $settings['shift_morning_start_time'] ?? '09:00';
        $morningEnd = $settings['shift_morning_end_time'] ?? '18:00';
        $nightStart = $settings['shift_night_start_time'] ?? '21:00';
        $nightEnd = $settings['shift_night_end_time'] ?? '06:00';

        if ($shiftType === 'night') {
            return [
                'start' => !empty($nightStart) ? $nightStart : '21:00',
                'end' => !empty($nightEnd) ? $nightEnd : '06:00',
            ];
        }

        return [
            'start' => !empty($morningStart) ? $morningStart : '09:00',
            'end' => !empty($morningEnd) ? $morningEnd : '18:00',
        ];
    }

    protected function calculateAttendanceMetrics(int $employeeId, string $date, string $clockIn, string $clockOut, ?int $recordId = null): array
    {
        $settings = $this->getAttendanceSettings();
        $shiftSchedule = $this->getEmployeeShiftSchedule($employeeId);

        $companyStart = !empty($shiftSchedule['start']) ? $shiftSchedule['start'] : Utility::getValByName('company_start_time');
        $companyEnd = !empty($shiftSchedule['end']) ? $shiftSchedule['end'] : Utility::getValByName('company_end_time');

        $clockInTime = Carbon::parse($date . ' ' . $clockIn);
        $clockOutTime = Carbon::parse($date . ' ' . $clockOut);
        $startTime = Carbon::parse($date . ' ' . $companyStart);
        $endTime = Carbon::parse($date . ' ' . $companyEnd);

        if ($endTime->lessThanOrEqualTo($startTime)) {
            $endTime->addDay();
            if ($clockOutTime->lessThanOrEqualTo($clockInTime)) {
                $clockOutTime->addDay();
            }
        }

        $lateSeconds = max(0, $clockInTime->diffInSeconds($startTime, false) * -1);
        $earlySeconds = max(0, $endTime->diffInSeconds($clockOutTime, false) * -1);

        $late = gmdate('H:i:s', $lateSeconds);
        $earlyLeaving = gmdate('H:i:s', $earlySeconds);

        $overtimeSeconds = 0;
        if ($clockOutTime->gt($endTime)) {
            $overtimeSeconds = $clockOutTime->diffInSeconds($endTime);
        }
        $overtime = gmdate('H:i:s', $overtimeSeconds);

        $lateMark = $lateSeconds > ($settings['grace_late'] * 60);
        $earlyMark = $earlySeconds > ($settings['grace_early'] * 60);
        $attendanceStatus = $this->resolveAttendanceStatusByLateSeconds(
            $lateSeconds,
            $earlySeconds,
            (int) $settings['grace_late'],
            (int) $settings['half_day_deduction_minutes']
        );

        $workSeconds = max(0, $clockOutTime->diffInSeconds($clockInTime));
        $requiredSeconds = (int) round($settings['flexi_required_hours'] * 3600);
        $lessHoursMark = $requiredSeconds > 0 && $workSeconds < $requiredSeconds;

        $deductionUnits = 0;
        $marksToday = (int) $lateMark + (int) $earlyMark;
        if ($marksToday > 0) {
            $marksBefore = $this->countMarksBefore($employeeId, $date, $recordId);
            for ($i = 1; $i <= $marksToday; $i++) {
                $index = $marksBefore + $i;
                if ($index <= $settings['exception_limit']) {
                    continue;
                }
                $deductionUnits += $this->deductionUnitsForIndex($index, $settings['exception_limit'], $settings['deduction_policy']);
            }
        }

        if ($lessHoursMark) {
            $lessBefore = $this->countLessHoursBefore($employeeId, $date, $recordId);
            $lessIndex = $lessBefore + 1;
            if ($lessIndex > $settings['flexi_exception_limit']) {
                $deductionUnits += $this->flexiDeductionUnitsForIndex($lessIndex, $settings['flexi_exception_limit'], $settings['flexi_less_hours_policy']);
            }
        }

        // Early ½ Day: early leaving >= threshold → 0.5 deduction (status stays Present)
        $earlyHalfDay = false;
        $halfDayThresholdMinutes = (int) $settings['half_day_deduction_minutes'];
        $earlyMinutes = (int) round($earlySeconds / 60);
        if ($attendanceStatus === 'Present' && $earlyMinutes >= $halfDayThresholdMinutes) {
            $deductionUnits += 0.5;
            $earlyHalfDay = true;
        }

        return [
            'late' => $late,
            'early_leaving' => $earlyLeaving,
            'overtime' => $overtime,
            'attendance_status' => $attendanceStatus,
            'late_mark' => $lateMark,
            'early_mark' => $earlyMark,
            'less_hours_mark' => $lessHoursMark,
            'deduction_units' => $deductionUnits,
            'early_half_day' => $earlyHalfDay,
        ];
    }

    protected function resolveAttendanceStatusByLateSeconds(int $lateSeconds, int $earlySeconds, int $graceMinutes, int $halfDayMinutes = 60): string
    {
        $halfDayThresholdSeconds = max(0, ($halfDayMinutes + $graceMinutes) * 60);
        if ($lateSeconds > $halfDayThresholdSeconds) {
            return 'Half Day';
        }

        return 'Present';
    }

    protected function countMarksBefore(int $employeeId, string $date, ?int $recordId = null): int
    {
        $start = Carbon::parse($date)->startOfMonth()->toDateString();
        $end = Carbon::parse($date)->endOfMonth()->toDateString();

        $query = AttendanceEmployee::where('employee_id', $employeeId)
            ->whereBetween('date', [$start, $end])
            ->where(function ($sub) use ($date, $recordId) {
                $sub->where('date', '<', $date);
                if (!empty($recordId)) {
                    $sub->orWhere(function ($inner) use ($date, $recordId) {
                        $inner->where('date', $date)->where('id', '<', $recordId);
                    });
                }
            });

        return (int) $query->sum(DB::raw('COALESCE(late_mark,0) + COALESCE(early_mark,0)'));
    }

    protected function countLessHoursBefore(int $employeeId, string $date, ?int $recordId = null): int
    {
        $start = Carbon::parse($date)->startOfMonth()->toDateString();
        $end = Carbon::parse($date)->endOfMonth()->toDateString();

        $query = AttendanceEmployee::where('employee_id', $employeeId)
            ->whereBetween('date', [$start, $end])
            ->where(function ($sub) use ($date, $recordId) {
                $sub->where('date', '<', $date);
                if (!empty($recordId)) {
                    $sub->orWhere(function ($inner) use ($date, $recordId) {
                        $inner->where('date', $date)->where('id', '<', $recordId);
                    });
                }
            });

        return (int) $query->sum(DB::raw('COALESCE(less_hours_mark,0)'));
    }

    protected function deductionUnitsForIndex(int $index, int $limit, string $policy): float
    {
        $offset = $index - $limit;
        if ($offset <= 0) {
            return 0;
        }
        if ($policy === 'every2') {
            return $offset % 2 === 0 ? 0.5 : 0.0;
        }
        if ($policy === 'every3') {
            return $offset % 3 === 0 ? 0.5 : 0.0;
        }

        return 0.5;
    }

    protected function flexiDeductionUnitsForIndex(int $index, int $limit, string $policy): float
    {
        $offset = $index - $limit;
        if ($offset <= 0) {
            return 0;
        }
        $threshold = $policy === 'every2' ? 2 : 3;
        return $offset % $threshold === 0 ? 0.5 : 0.0;
    }

    public function importFile()
    {
        if ($denied = $this->denyUnlessAttendanceAdmin()) {
            return $denied;
        }

        return view('attendance.import');
    }

    // public function import(Request $request)
    // {
    //     $rules = [
    //         'file' => 'required|mimes:csv,txt,xlsx',
    //     ];
    //     $validator = \Validator::make($request->all(), $rules);

    //     if ($validator->fails()) {
    //         $messages = $validator->getMessageBag();

    //         return redirect()->back()->with('error', $messages->first());
    //     }

    //     $attendance = (new AttendanceImport())->toArray(request()->file('file'))[0];

    //     $email_data = [];
    //     foreach ($attendance as $key => $employee) {
    //         if ($key != 0) {
    //             echo "<pre>";
    //             if ($employee != null && Employee::where('email', $employee[0])->where('created_by', \Auth::user()->creatorId())->exists()) {
    //                 $email = $employee[0];
    //             } else {
    //                 $email_data[] = $employee[0];
    //             }
    //         }
    //     }
    //     $totalattendance = count($attendance) - 1;
    //     $errorArray    = [];

    //     $startTime = Utility::getValByName('company_start_time');
    //     $endTime   = Utility::getValByName('company_end_time');

    //     if (!empty($attendanceData)) {
    //         $errorArray[] = $attendanceData;
    //     } else {
    //         foreach ($attendance as $key => $value) {
    //             if ($key != 0) {
    //                 $employeeData = Employee::where('email', $value[0])->where('created_by', \Auth::user()->creatorId())->first();
    //                 // $employeeId = 0;
    //                 if (!empty($employeeData)) {
    //                     $employeeId = $employeeData->id;


    //                     $clockIn = $value[2];
    //                     $clockOut = $value[3];

    //                     if ($clockIn) {
    //                         $status = "present";
    //                     } else {
    //                         $status = "leave";
    //                     }

    //                     $totalLateSeconds = strtotime($clockIn) - strtotime($startTime);

    //                     $hours = floor($totalLateSeconds / 3600);
    //                     $mins  = floor($totalLateSeconds / 60 % 60);
    //                     $secs  = floor($totalLateSeconds % 60);
    //                     $late  = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

    //                     $totalEarlyLeavingSeconds = strtotime($endTime) - strtotime($clockOut);
    //                     $hours                    = floor($totalEarlyLeavingSeconds / 3600);
    //                     $mins                     = floor($totalEarlyLeavingSeconds / 60 % 60);
    //                     $secs                     = floor($totalEarlyLeavingSeconds % 60);
    //                     $earlyLeaving             = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

    //                     if (strtotime($clockOut) > strtotime($endTime)) {
    //                         //Overtime
    //                         $totalOvertimeSeconds = strtotime($clockOut) - strtotime($endTime);
    //                         $hours                = floor($totalOvertimeSeconds / 3600);
    //                         $mins                 = floor($totalOvertimeSeconds / 60 % 60);
    //                         $secs                 = floor($totalOvertimeSeconds % 60);
    //                         $overtime             = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
    //                     } else {
    //                         $overtime = '00:00:00';
    //                     }

    //                     $check = AttendanceEmployee::where('employee_id', $employeeId)->where('date', $value[1])->first();
    //                     if ($check) {
    //                         $check->update([
    //                             'late' => $late,
    //                             'early_leaving' => ($earlyLeaving > 0) ? $earlyLeaving : '00:00:00',
    //                             'overtime' => $overtime,
    //                             'clock_in' => $value[2],
    //                             'clock_out' => $value[3]
    //                         ]);
    //                     } else {
    //                         $time_sheet = AttendanceEmployee::create([
    //                             'employee_id' => $employeeId,
    //                             'date' => $value[1],
    //                             'status' => $status,
    //                             'late' => $late,
    //                             'early_leaving' => ($earlyLeaving > 0) ? $earlyLeaving : '00:00:00',
    //                             'overtime' => $overtime,
    //                             'clock_in' => $value[2],
    //                             'clock_out' => $value[3],
    //                             'created_by' => \Auth::user()->id,
    //                         ]);
    //                     }
    //                 }
    //             } else {
    //                 $email_data = implode(' And ', $email_data);
    //             }
    //         }
    //         if (!empty($email_data)) {
    //             return redirect()->back()->with('status', 'this record is not import. ' . '</br>' . $email_data);
    //         } else {
    //             if (empty($errorArray)) {
    //                 $data['status'] = 'success';
    //                 $data['msg']    = __('Record successfully imported');
    //             } else {

    //                 $data['status'] = 'error';
    //                 $data['msg']    = count($errorArray) . ' ' . __('Record imported fail out of' . ' ' . $totalattendance . ' ' . 'record');


    //                 foreach ($errorArray as $errorData) {
    //                     $errorRecord[] = implode(',', $errorData->toArray());
    //                 }

    //                 \Session::put('errorArray', $errorRecord);
    //             }

    //             return redirect()->back()->with($data['status'], $data['msg']);
    //         }
    //     }
    // }

    public function attendanceImportdata(Request $request)
    {
        if ($denied = $this->denyUnlessAttendanceAdmin()) {
            return $denied;
        }

        session_start();
        $html = '<h3 class="text-danger text-center">Below data is not inserted</h3></br>';
        $flag = 0;
        $html .= '<table class="table table-bordered"><tr>';
        try {
            $request = $request->data;
            $file_data = $_SESSION['file_data'];

            unset($_SESSION['file_data']);
        } catch (\Throwable $th) {
            $html = '<h3 class="text-danger text-center">Something went wrong, Please try again</h3></br>';
            return response()->json([
                'html' => true,
                'response' => $html,
            ]);
        }
        $user = Auth::user();

        $startTime = Utility::getValByName('company_start_time');
        $endTime = Utility::getValByName('company_end_time');

        foreach ($file_data as $key => $row) {
            $employeeData = Employee::Where('email', 'like', $row[$request['employee_email']])->where('created_by', \Auth::user()->creatorId())->first();
            if (!empty($employeeData)) {
                try {

                    $employeeId = $employeeData->id;

                    $clockIn = $row[$request['clock_in']];
                    $clockOut = $row[$request['clock_out']];
                    $attendanceDate = $row[$request['date']];

                    if ($clockIn) {
                        $status = "Present";
                    } else {
                        $status = "Leave";
                    }

                    $check = AttendanceEmployee::where('employee_id', $employeeId)->where('date', $attendanceDate)->first();
                    $metrics = $this->calculateAttendanceMetrics(
                        (int) $employeeId,
                        $attendanceDate,
                        (string) $clockIn,
                        (string) $clockOut,
                        !empty($check) ? (int) $check->id : null
                    );

                    if ($check) {
                        $check->update([
                            'status' => $metrics['attendance_status'],
                            'late' => $metrics['late'],
                            'early_leaving' => $metrics['early_leaving'],
                            'overtime' => $metrics['overtime'],
                            'late_mark' => $metrics['late_mark'],
                            'early_mark' => $metrics['early_mark'],
                            'less_hours_mark' => $metrics['less_hours_mark'],
                            'deduction_units' => $metrics['deduction_units'],
                            'clock_in' => $row[$request['clock_in']],
                            'clock_out' => $row[$request['clock_out']],
                        ]);
                    } else {
                        $time_sheet = AttendanceEmployee::create([
                            'employee_id' => $employeeId,
                            'date' => $attendanceDate,
                            'status' => $clockIn ? $metrics['attendance_status'] : $status,
                            'late' => $metrics['late'],
                            'early_leaving' => $metrics['early_leaving'],
                            'overtime' => $metrics['overtime'],
                            'late_mark' => $metrics['late_mark'],
                            'early_mark' => $metrics['early_mark'],
                            'less_hours_mark' => $metrics['less_hours_mark'],
                            'deduction_units' => $metrics['deduction_units'],
                            'clock_in' => $row[$request['clock_in']],
                            'clock_out' => $row[$request['clock_out']],
                            'created_by' => \Auth::user()->id,
                        ]);
                    }

                } catch (\Exception $e) {
                    $flag = 1;
                    $html .= '<tr>';

                    $html .= '<td>' . (isset($row[$request['employee_email']]) ? $row[$request['employee_email']] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request['date']]) ? $row[$request['date']] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request['clock_in']]) ? $row[$request['clock_in']] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request['clock_out']]) ? $row[$request['clock_out']] : '-') . '</td>';

                    $html .= '</tr>';
                }
            } else {
                $flag = 1;
                $html .= '<tr>';

                $html .= '<td>' . (isset($row[$request['employee_email']]) ? $row[$request['employee_email']] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request['date']]) ? $row[$request['date']] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request['clock_in']]) ? $row[$request['clock_in']] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request['clock_out']]) ? $row[$request['clock_out']] : '-') . '</td>';

                $html .= '</tr>';
            }
        }

        $html .= '
                        </table>
                        <br />
                        ';
        if ($flag == 1) {

            return response()->json([
                'html' => true,
                'response' => $html,
            ]);
        } else {
            return response()->json([
                'html' => false,
                'response' => 'Data Imported Successfully',
            ]);
        }

    }

    /**
     * Calculate professional period for an employee at a given date
     * Returns array with professional_years, professional_months, professional_days, in_probation
     */
    protected function calculateProfessionalPeriodAtDate(?Employee $employee, $date = null): array
    {
        if (empty($employee) || empty($employee->company_doj)) {
            return [
                'professional_years' => 0,
                'professional_months' => 0,
                'professional_days' => 0,
                'in_probation' => false,
            ];
        }

        $referenceDate = $date ? Carbon::parse($date)->startOfDay() : Carbon::now()->startOfDay();
        $doj = Carbon::parse($employee->company_doj)->startOfDay();

        // Calculate total days since joining
        $totalDays = $doj->diffInDays($referenceDate);

        // Calculate years, months, and remaining days
        $years = $referenceDate->copy()->subYears(intval($referenceDate->diffInYears($doj)))->diffInYears($doj);
        if ($years < 0) $years = 0;

        $tempDate = $doj->copy()->addYears($years);
        $months = $tempDate->diffInMonths($referenceDate);
        if ($months < 0) $months = 0;

        $tempDate->addMonths($months);
        $days = $tempDate->diffInDays($referenceDate);
        if ($days < 0) $days = 0;

        // Check if employee was in probation at the reference date
        $settings = Utility::settings();
        $probationMonths = (int) ($settings['probation_months'] ?? 0);
        $inProbation = false;
        if ($probationMonths > 0) {
            $probationEnd = $doj->copy()->addMonths($probationMonths);
            $inProbation = $referenceDate->lt($probationEnd);
        }

        return [
            'professional_years' => $years,
            'professional_months' => $months,
            'professional_days' => $totalDays,
            'in_probation' => $inProbation,
        ];
    }

    /**
     * Reapply deduction_units for all employees based on existing marks and policy settings.
     * Does NOT change late_mark, early_mark, or status — only recalculates deduction_units
     * using the current deduction policy (every1/every2/every3) and exception_limit.
     */
    public function reapplyAttendancePolicy(Request $request)
    {
        if ($denied = $this->denyUnlessAttendanceAdmin()) {
            return $denied;
        }

        $request->validate(['month' => 'required|string|size:7']);

        $user = \Auth::user();
        $creatorId = $user->creatorId();
        $month = $request->month;
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        $settings = $this->getAttendanceSettings();

        $exceptionLimit = (int) ($settings['exception_limit'] ?? 0);
        $deductionPolicy = (string) ($settings['deduction_policy'] ?? 'every1');
        $halfDayMinutes = (int) ($settings['half_day_deduction_minutes'] ?? 60);

        $employees = Employee::where('created_by', $creatorId)->get();
        $updated = 0;

        foreach ($employees as $emp) {
            $records = AttendanceEmployee::where('employee_id', $emp->id)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->orderBy('date')
                ->orderBy('id')
                ->get();

            if ($records->isEmpty()) continue;

            // First pass: count cumulative non-direct marks to calculate deduction_units
            $markIndex = 0;

            foreach ($records as $rec) {
                $status = strtolower((string) $rec->status);

                // Skip Leave/Absent (no clock data, no deduction)
                if (in_array($status, ['leave', 'absent'])) continue;

                $lateMinutes = $this->durationToMinutes((string) ($rec->late ?? '00:00:00'));
                $earlyMinutes = $this->durationToMinutes((string) ($rec->early_leaving ?? '00:00:00'));
                $isDirectLate = ($lateMinutes >= $halfDayMinutes);
                $isDirectEarly = ($earlyMinutes >= $halfDayMinutes);

                $newDU = 0.0;

                // Half Day status records always get 0.5
                if ($status === 'half day') {
                    $newDU = 0.5;
                }

                // Early ½ Day: Present with early >= threshold → 0.5 deduction
                if ($status === 'present' && $isDirectEarly) {
                    $newDU += 0.5;
                }

                // Count non-direct marks for policy deduction
                $marksOnDay = 0;
                if (((int) $rec->late_mark === 1) && !$isDirectLate) $marksOnDay++;
                if (((int) $rec->early_mark === 1) && !$isDirectEarly) $marksOnDay++;

                for ($mi = 0; $mi < $marksOnDay; $mi++) {
                    $markIndex++;
                    if ($markIndex <= $exceptionLimit) continue;
                    $newDU += $this->deductionUnitsForIndex($markIndex, $exceptionLimit, $deductionPolicy);
                }

                // Update if different
                if (round((float) $rec->deduction_units, 2) !== round($newDU, 2)) {
                    AttendanceEmployee::where('id', $rec->id)->update(['deduction_units' => $newDU]);
                    $updated++;
                }
            }
        }

        return redirect()->route('attendanceemployee.index', ['type' => 'monthly', 'month' => $month])
            ->with('success', __(':count attendance records updated for :month', ['count' => $updated, 'month' => Carbon::parse($monthStart)->format('F Y')]));
    }

    /**
     * Sync attendance data for payroll — snapshots all employees' attendance for the given month.
     */
    public function syncForPayroll(Request $request)
    {
        if ($denied = $this->denyUnlessAttendanceAdmin()) {
            return $denied;
        }

        $request->validate(['month' => 'required|string|size:7']);

        $user = \Auth::user();
        $creatorId = $user->creatorId();
        $month = $request->month;

        // Attendance date range — respects the configurable attendance
        // cut-off day on the pay schedule (e.g. 26th prev -> 25th current).
        // Defaults to the calendar month when start day = 1.
        [$monthStart, $monthEnd] = \App\Models\PaySchedule::attendanceRangeFor($month, $creatorId);

        $companyEndTime = (string)Utility::getValByName('company_end_time');
        $halfDayMinutes = (int)(Utility::getValByName('attendance_half_day_deduction_minutes') ?: 60);
        $policySettings = $this->getAttendanceSettings();

        // Fetch public holiday dates for this month
        $holidayDates = [];
        $holidays = DB::table('holidays')
            ->where('created_by', $creatorId)
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('start_date', [$monthStart, $monthEnd])
                  ->orWhereBetween('end_date', [$monthStart, $monthEnd]);
            })->get();
        foreach ($holidays as $h) {
            $hStart = max(strtotime($h->start_date), strtotime($monthStart));
            $hEnd = min(strtotime($h->end_date), strtotime($monthEnd));
            for ($ts = $hStart; $ts <= $hEnd; $ts += 86400) {
                $holidayDates[date('Y-m-d', $ts)] = $h->occasion ?? 'Holiday';
            }
        }

        // Fetch all attendance for the month (with employee+shift eager-loaded for policy summary)
        $allAttendance = AttendanceEmployee::with('employee', 'employee.shift')
            ->whereHas('employee', function ($q) use ($creatorId) {
                $q->where('created_by', $creatorId);
            })
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->orderBy('date')
            ->get();

        // Build policy summary for all employees at once (same logic as attendance page)
        $policySummaryAll = $this->buildAttendancePolicySummary($allAttendance, $policySettings);
        $policySummaryByEmp = [];
        foreach ($policySummaryAll as $ps) {
            $policySummaryByEmp[$ps['employee_id']] = $ps;
        }

        $employees = Employee::where('created_by', $creatorId)->get();
        $synced = 0;

        foreach ($employees as $emp) {
            $attendance = $allAttendance->where('employee_id', $emp->id)->values();

            if ($attendance->isEmpty()) continue;

            // Exclude holiday dates from present/working day counts
            $holidayCount = 0;
            $presentDays = 0;
            $halfDays = 0;
            $absentDays = 0;
            $leaveDays = 0;
            foreach ($attendance as $rec) {
                $dateStr = (string)$rec->date;
                $status = strtolower((string)$rec->status);
                if (isset($holidayDates[$dateStr]) && ($status === 'present' || $status === 'half day')) {
                    // This day is a public holiday — don't count as present/HD
                    $holidayCount++;
                    continue;
                }
                if ($status === 'present') $presentDays++;
                elseif ($status === 'half day') $halfDays++;
                elseif ($status === 'absent') $absentDays++;
                elseif ($status === 'leave') $leaveDays++;
            }
            $totalWorkingDays = $attendance->count() - $holidayCount;
            $lateMarks = (int)$attendance->sum('late_mark');
            $earlyMarks = (int)$attendance->sum('early_mark');
            $totalDeductionUnits = (float)$attendance->sum('deduction_units');

            // Early ½ Day detection
            $earlyHalfDayCount = 0;
            $details = [];
            foreach ($attendance as $rec) {
                $dateStr = (string)$rec->date;
                $isHoliday = isset($holidayDates[$dateStr]);

                $dayDetail = [
                    'date' => $rec->date,
                    'status' => $isHoliday ? 'Holiday' : $rec->status,
                    'clock_in' => $rec->clock_in,
                    'clock_out' => $rec->clock_out,
                    'late_mark' => $isHoliday ? 0 : (int)$rec->late_mark,
                    'early_mark' => $isHoliday ? 0 : (int)$rec->early_mark,
                    'deduction_units' => $isHoliday ? 0 : (float)$rec->deduction_units,
                    'early_half_day' => false,
                    'is_holiday' => $isHoliday,
                    'holiday_name' => $isHoliday ? ($holidayDates[$dateStr] ?? '') : '',
                ];

                if (!$isHoliday && strtolower($rec->status) === 'present' && $rec->clock_out !== '00:00:00') {
                    $endTime = Carbon::parse($rec->date . ' ' . $companyEndTime);
                    $clockOut = Carbon::parse($rec->date . ' ' . $rec->clock_out);
                    if ($clockOut->lt($endTime)) {
                        $earlyMin = abs($endTime->diffInMinutes($clockOut));
                        if ($earlyMin >= $halfDayMinutes) {
                            $earlyHalfDayCount++;
                            $dayDetail['early_half_day'] = true;
                            $dayDetail['early_minutes'] = $earlyMin;
                        }
                    }
                }

                $details[] = $dayDetail;
            }

            // Get policy summary for this employee
            $empPolicySummary = $policySummaryByEmp[$emp->id] ?? null;

            // Effective counts from policy summary (same as shown on attendance page)
            $presentEffective = $empPolicySummary ? (float)$empPolicySummary['present_count'] : (float)$presentDays;
            $leaveEffective = $empPolicySummary ? (float)$empPolicySummary['leave_only_count'] : (float)$leaveDays;
            $absentEffective = $empPolicySummary ? (float)$empPolicySummary['absent_only_count'] : (float)$absentDays;
            $hdDeduction = $empPolicySummary ? (float)$empPolicySummary['hd_deduction'] : (float)$halfDays;
            $weeklyOffs = $empPolicySummary ? (int)$empPolicySummary['weekly_offs'] : 0;
            $monthTotalDays = $empPolicySummary ? (int)$empPolicySummary['month_total_days'] : 0;

            // Subtract holiday dates that were counted as present in policy summary
            // Holiday attendance records with Present/HD should not count as present
            if ($holidayCount > 0) {
                $presentEffective = max(0, $presentEffective - $holidayCount);
            }

            PayrollAttendanceSync::updateOrCreate(
                ['employee_id' => $emp->id, 'month' => $month, 'created_by' => $creatorId],
                [
                    'working_days' => $totalWorkingDays,
                    'present' => $presentDays,
                    'half_day' => $halfDays,
                    'absent' => $absentDays,
                    'leave' => $leaveDays,
                    'late_marks' => $lateMarks,
                    'early_marks' => $earlyMarks,
                    'deduction_units' => $totalDeductionUnits,
                    'early_half_day' => $earlyHalfDayCount,
                    'policy_summary_json' => $empPolicySummary,
                    'present_effective' => $presentEffective,
                    'leave_effective' => $leaveEffective,
                    'absent_effective' => $absentEffective,
                    'hd_deduction' => $hdDeduction,
                    'weekly_offs' => $weeklyOffs,
                    'month_total_days' => $monthTotalDays,
                    'details_json' => $details,
                    'synced_by' => $user->id,
                    'synced_at' => now(),
                ]
            );
            $synced++;
        }

        return redirect()->route('attendanceemployee.index', ['type' => 'monthly', 'month' => $month])
            ->with('success', __(':count employees attendance synced for :month', ['count' => $synced, 'month' => Carbon::parse($month . '-01')->format('F Y')]));
    }

    public function exportMonthlyExcel(Request $request)
    {
        if ($denied = $this->denyUnlessAttendanceAdmin()) {
            return $denied;
        }

        if (!\Auth::user()->can('Manage Attendance')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $month = $request->get('month', date('Y-m'));
        $branch = $request->get('branch') ?: null;
        $department = $request->get('department') ?: null;
        $creatorId = \Auth::user()->creatorId();

        $label = Carbon::parse($month . '-01')->format('M_Y');
        $filename = "Attendance_{$label}.xlsx";

        return Excel::download(
            new MonthlyAttendanceExport($creatorId, $month, $branch ? (int) $branch : null, $department ? (int) $department : null),
            $filename
        );
    }
}
