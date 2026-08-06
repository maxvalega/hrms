<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceEmployee;
use App\Models\AttendanceModificationRequest;
use App\Models\Employee;
use App\Models\IpRestrict;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\Utility;
use App\Services\FacialRecognitionService;
use App\Services\GeoFenceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MobileAppController extends Controller
{
    // ════════════════════════════════════════════════════════════
    // 1. AUTH — Login, Logout, Forgot Password
    // ════════════════════════════════════════════════════════════

    /**
     * POST /api/mobile/login
     * Body: email, password, device_name
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !password_verify($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials.'], 401);
        }

        if ($user->is_active != 1 || $user->is_disable != 1 || $user->is_login_enable != 1) {
            return response()->json(['success' => false, 'message' => 'Account is disabled. Contact HR.'], 403);
        }

        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee profile not found.'], 404);
        }

        // Revoke old tokens for this device
        $user->tokens()->where('name', $request->device_name)->delete();
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'type' => $user->type,
                    'avatar' => $user->avatar ? asset(Storage::url('uploads/avatar/' . $user->avatar)) : null,
                ],
                'employee' => [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_id,
                    'name' => $employee->name,
                    'phone' => $employee->phone,
                    'department' => $employee->department->name ?? null,
                    'designation' => $employee->designation->name ?? null,
                    'branch' => $employee->branch->name ?? null,
                    'shift' => $employee->shift ? [
                        'name' => $employee->shift->name,
                        'start' => $employee->shift->start_time,
                        'end' => $employee->shift->end_time,
                    ] : null,
                ],
            ],
        ]);
    }

    /**
     * POST /api/mobile/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'Logged out successfully.']);
    }

    /**
     * POST /api/mobile/forgot-password
     * Body: email
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Email not found.'], 404);
        }

        $token = Str::random(64);
        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        // In production, send email here
        return response()->json([
            'success' => true,
            'message' => 'Password reset link would be sent to your email.',
        ]);
    }

    /**
     * POST /api/mobile/change-password
     * Body: current_password, new_password, new_password_confirmation
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();
        if (!password_verify($request->current_password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Current password is incorrect.'], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['success' => true, 'message' => 'Password changed successfully.']);
    }

    // ════════════════════════════════════════════════════════════
    // 2. FACIAL RECOGNITION — Verify after login
    // ════════════════════════════════════════════════════════════

    /**
     * POST /api/mobile/verify-face
     * Body: photo (base64 image)
     */
    public function verifyFace(Request $request): JsonResponse
    {
        $request->validate(['photo' => 'required|string']);

        $employee = Employee::where('user_id', $request->user()->id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        try {
            // Save base64 photo to a temp file so the service can read it
            $base64 = $request->photo;
            // Strip data URI prefix if present (e.g. "data:image/jpeg;base64,...")
            if (str_contains($base64, ',')) {
                $base64 = substr($base64, strpos($base64, ',') + 1);
            }
            $decoded = base64_decode($base64, true);
            if (!$decoded) {
                return response()->json(['success' => false, 'message' => 'Invalid base64 photo data.'], 422);
            }
            $tmpPath = storage_path('app/temp_face_' . $employee->id . '_' . time() . '.jpg');
            file_put_contents($tmpPath, $decoded);

            $service = app(FacialRecognitionService::class);
            $result = $service->verifyByEmployeeId($employee->id, $tmpPath);

            // Clean up temp file
            @unlink($tmpPath);

            return response()->json([
                'success' => !empty($result['match']),
                'message' => $result['message'] ?? ($result['match'] ? 'Face verified.' : 'Face not matched.'),
                'data' => [
                    'match' => $result['match'] ?? false,
                    'confidence' => $result['confidence'] ?? 0,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Verification failed: ' . $e->getMessage()], 500);
        }
    }

    // ════════════════════════════════════════════════════════════
    // 3. DASHBOARD — Today's status, attendance logs
    // ════════════════════════════════════════════════════════════

    /**
     * GET /api/mobile/dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $employee = Employee::where('user_id', $request->user()->id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        $today = date('Y-m-d');
        $todayAttendance = AttendanceEmployee::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        $month = date('Y-m');
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t');
        $monthAttendance = AttendanceEmployee::where('employee_id', $employee->id)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->get();

        $settings = Utility::settings();

        return response()->json([
            'success' => true,
            'data' => [
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'employee_id' => $employee->employee_id,
                    'department' => $employee->department->name ?? null,
                    'designation' => $employee->designation->name ?? null,
                ],
                'today' => $todayAttendance ? [
                    'date' => $todayAttendance->date,
                    'status' => $todayAttendance->status,
                    'clock_in' => $todayAttendance->clock_in,
                    'clock_out' => $todayAttendance->clock_out,
                    'late' => $todayAttendance->late,
                    'early_leaving' => $todayAttendance->early_leaving,
                    'is_clocked_in' => $todayAttendance->clock_in !== '00:00:00',
                    'is_clocked_out' => $todayAttendance->clock_out !== '00:00:00',
                ] : null,
                'month_summary' => [
                    'working_days' => $monthAttendance->count(),
                    'present' => $monthAttendance->where('status', 'Present')->count(),
                    'half_day' => $monthAttendance->where('status', 'Half Day')->count(),
                    'absent' => $monthAttendance->where('status', 'Absent')->count(),
                    'leave' => $monthAttendance->where('status', 'Leave')->count(),
                    'late_marks' => (int)$monthAttendance->sum('late_mark'),
                    'early_marks' => (int)$monthAttendance->sum('early_mark'),
                ],
                'company' => [
                    'start_time' => $settings['company_start_time'] ?? '09:00',
                    'end_time' => $settings['company_end_time'] ?? '18:00',
                ],
            ],
        ]);
    }

    // ════════════════════════════════════════════════════════════
    // 4. CLOCK IN / CLOCK OUT
    // ════════════════════════════════════════════════════════════

    /**
     * POST /api/mobile/clock-in
     * Body: latitude, longitude, address (optional), photo (base64, optional)
     */
    public function clockIn(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'address' => 'nullable|string|max:500',
            'photo' => 'nullable|string',
        ]);

        $employee = Employee::where('user_id', $request->user()->id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        $settings = Utility::settings();

        // IP restriction check
        if (!empty($settings['ip_restrict']) && $settings['ip_restrict'] == 'on') {
            $ip = IpRestrict::where('created_by', $request->user()->creatorId())
                ->where('ip', $request->ip())->first();
            if (!$ip) {
                return response()->json(['success' => false, 'message' => 'This IP is not allowed.'], 403);
            }
        }

        $geoCheck = app(GeoFenceService::class)->validatePunch(
            $request->filled('latitude') ? (float) $request->latitude : null,
            $request->filled('longitude') ? (float) $request->longitude : null,
            $employee,
            (int) $request->user()->creatorId()
        );
        if (! $geoCheck['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $geoCheck['message'],
                'distance_metres' => $geoCheck['distance_metres'],
                'radius_metres' => $geoCheck['radius_metres'],
            ], 403);
        }

        $today = date('Y-m-d');
        $time = date('H:i:s');

        // Check if already clocked in today (open session)
        $existing = AttendanceEmployee::where('employee_id', $employee->id)
            ->where('date', $today)
            ->where('clock_out', '00:00:00')
            ->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'Already clocked in. Please clock out first.'], 422);
        }

        // Save photo
        $photoPath = null;
        if ($request->photo) {
            $photoPath = $this->saveBase64Photo($request->photo, 'attendance_photos', $employee->id . '_in_' . $today);
        }

        // Verify face if photo provided
        $photoVerified = false;
        if ($photoPath) {
            try {
                $service = app(FacialRecognitionService::class);
                $result = $service->verifyByEmployeeId($employee->id, $photoPath);
                $photoVerified = !empty($result['match']) && ($result['confidence'] ?? 0) >= 80;
            } catch (\Throwable $e) {
                // Continue without verification
            }
        }

        // Calculate late
        $startTime = Carbon::parse($today . ' ' . ($settings['company_start_time'] ?? '09:00'));
        $clockInTime = Carbon::parse($today . ' ' . $time);
        $lateSeconds = max(0, $clockInTime->diffInSeconds($startTime, false) * -1);
        $late = gmdate('H:i:s', $lateSeconds);

        // Determine shift
        $empShift = $employee->shift;
        $shiftStart = $empShift ? $empShift->start_time : ($settings['company_start_time'] ?? '09:00');

        // Grace period
        $graceMinutes = (int)($settings['attendance_grace_late_minutes'] ?? 30);
        $lateMark = $lateSeconds > ($graceMinutes * 60);

        // Half day threshold
        $halfDayMinutes = (int)($settings['attendance_half_day_deduction_minutes'] ?? 60);
        $lateMinutes = (int)round($lateSeconds / 60);
        $status = ($lateMinutes >= ($halfDayMinutes + $graceMinutes)) ? 'Half Day' : 'Present';

        $attendance = AttendanceEmployee::create([
            'employee_id' => $employee->id,
            'date' => $today,
            'status' => $status,
            'clock_in' => $time,
            'clock_out' => '00:00:00',
            'late' => $late,
            'early_leaving' => '00:00:00',
            'overtime' => '00:00:00',
            'total_rest' => '00:00:00',
            'late_mark' => $lateMark ? 1 : 0,
            'early_mark' => 0,
            'device_type' => 'Mobile',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $request->address,
            'photo' => $photoPath,
            'photo_verified' => $photoVerified ? 1 : 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Clocked in successfully.',
            'data' => [
                'id' => $attendance->id,
                'date' => $attendance->date,
                'clock_in' => $attendance->clock_in,
                'status' => $attendance->status,
                'late' => $attendance->late,
                'late_mark' => (bool)$attendance->late_mark,
                'photo_verified' => $photoVerified,
            ],
        ]);
    }

    /**
     * POST /api/mobile/clock-out
     * Body: latitude, longitude, address (optional), photo (base64, optional)
     */
    public function clockOut(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'address' => 'nullable|string|max:500',
            'photo' => 'nullable|string',
        ]);

        $employee = Employee::where('user_id', $request->user()->id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        $geoCheck = app(GeoFenceService::class)->validatePunch(
            $request->filled('latitude') ? (float) $request->latitude : null,
            $request->filled('longitude') ? (float) $request->longitude : null,
            $employee,
            (int) $request->user()->creatorId()
        );
        if (! $geoCheck['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $geoCheck['message'],
                'distance_metres' => $geoCheck['distance_metres'],
                'radius_metres' => $geoCheck['radius_metres'],
            ], 403);
        }

        $today = date('Y-m-d');
        $time = date('H:i:s');

        $attendance = AttendanceEmployee::where('employee_id', $employee->id)
            ->where('date', $today)
            ->where('clock_out', '00:00:00')
            ->first();

        if (!$attendance) {
            return response()->json(['success' => false, 'message' => 'No open clock-in found for today.'], 422);
        }

        $settings = Utility::settings();
        $endTime = Carbon::parse($today . ' ' . ($settings['company_end_time'] ?? '18:00'));
        $clockOutTime = Carbon::parse($today . ' ' . $time);

        // Early leaving
        $earlySeconds = max(0, $endTime->diffInSeconds($clockOutTime, false) * -1);
        $earlyLeaving = gmdate('H:i:s', $earlySeconds);

        // Overtime
        $overtimeSeconds = $clockOutTime->gt($endTime) ? $clockOutTime->diffInSeconds($endTime) : 0;
        $overtime = gmdate('H:i:s', $overtimeSeconds);

        // Early mark
        $graceEarlyMinutes = (int)($settings['attendance_grace_early_minutes'] ?? 0);
        $earlyMark = $earlySeconds > ($graceEarlyMinutes * 60);

        // Early half day check
        $halfDayMinutes = (int)($settings['attendance_half_day_deduction_minutes'] ?? 60);
        $earlyMinutes = (int)round($earlySeconds / 60);
        $deductionUnits = (float)$attendance->deduction_units;
        $earlyHalfDay = false;
        if ($attendance->status === 'Present' && $earlyMinutes >= $halfDayMinutes) {
            $deductionUnits += 0.5;
            $earlyHalfDay = true;
        }

        // Save photo
        $photoOutPath = null;
        $photoOutVerified = false;
        if ($request->photo) {
            $photoOutPath = $this->saveBase64Photo($request->photo, 'attendance_photos', $employee->id . '_out_' . $today);
            try {
                $service = app(FacialRecognitionService::class);
                $result = $service->verifyByEmployeeId($employee->id, $photoOutPath);
                $photoOutVerified = !empty($result['match']) && ($result['confidence'] ?? 0) >= 80;
            } catch (\Throwable $e) {
                // Continue
            }
        }

        $attendance->update([
            'clock_out' => $time,
            'early_leaving' => $earlyLeaving,
            'overtime' => $overtime,
            'early_mark' => $earlyMark ? 1 : 0,
            'deduction_units' => $deductionUnits,
            'device_type_out' => 'Mobile',
            'latitude_out' => $request->latitude,
            'longitude_out' => $request->longitude,
            'address_out' => $request->address,
            'photo_out' => $photoOutPath,
            'photo_out_verified' => $photoOutVerified ? 1 : 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Clocked out successfully.',
            'data' => [
                'id' => $attendance->id,
                'date' => $attendance->date,
                'clock_in' => $attendance->clock_in,
                'clock_out' => $time,
                'status' => $attendance->status,
                'early_leaving' => $earlyLeaving,
                'early_mark' => $earlyMark,
                'early_half_day' => $earlyHalfDay,
                'overtime' => $overtime,
                'photo_out_verified' => $photoOutVerified,
            ],
        ]);
    }

    // ════════════════════════════════════════════════════════════
    // 5. LEAVE — Apply, List, Types
    // ════════════════════════════════════════════════════════════

    /**
     * GET /api/mobile/leave-types
     */
    public function leaveTypes(Request $request): JsonResponse
    {
        $leaveTypes = LeaveType::where('created_by', $request->user()->creatorId())->get();

        return response()->json([
            'success' => true,
            'data' => $leaveTypes->map(fn($lt) => [
                'id' => $lt->id,
                'title' => $lt->title,
                'days' => $lt->days,
            ]),
        ]);
    }

    /**
     * GET /api/mobile/leaves?month=2026-03
     */
    public function leaves(Request $request): JsonResponse
    {
        $employee = Employee::where('user_id', $request->user()->id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        $query = Leave::where('employee_id', $employee->id)->orderByDesc('id');

        if ($request->month) {
            $start = $request->month . '-01';
            $end = date('Y-m-t', strtotime($start));
            $query->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end]);
            });
        }

        $leaves = $query->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $leaves->map(fn($l) => [
                'id' => $l->id,
                'leave_type' => $l->leaveType->title ?? null,
                'start_date' => $l->start_date,
                'end_date' => $l->end_date,
                'day_type' => $l->day_type,
                'total_days' => $l->total_leave_days,
                'reason' => $l->leave_reason,
                'status' => $l->status,
                'remark' => $l->remark,
                'applied_on' => $l->applied_on,
            ]),
        ]);
    }

    /**
     * POST /api/mobile/leave/apply
     * Body: leave_type_id, start_date, end_date, day_type, leave_reason
     */
    public function applyLeave(Request $request): JsonResponse
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'day_type' => 'required|in:full_day,first_half,second_half',
            'leave_reason' => 'required|string|max:500',
        ]);

        $employee = Employee::where('user_id', $request->user()->id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        $isHalfDay = in_array($request->day_type, ['first_half', 'second_half']);
        if ($isHalfDay && $request->start_date !== $request->end_date) {
            return response()->json(['success' => false, 'message' => 'Half day leave must be for a single date.'], 422);
        }

        $totalDays = $isHalfDay ? 0.5 : (Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date)) + 1);

        $leave = Leave::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $request->leave_type_id,
            'applied_on' => date('Y-m-d'),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'day_type' => $request->day_type,
            'total_leave_days' => $totalDays,
            'leave_reason' => $request->leave_reason,
            'status' => 'Pending',
            'created_by' => $request->user()->creatorId(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave applied successfully.',
            'data' => [
                'id' => $leave->id,
                'status' => 'Pending',
                'total_days' => $totalDays,
            ],
        ], 201);
    }

    // ════════════════════════════════════════════════════════════
    // 6. SWIPE REQUEST — Submit & History
    // ════════════════════════════════════════════════════════════

    /**
     * POST /api/mobile/swipe-request
     * Body: date, requested_status, requested_clock_in, requested_clock_out, reason
     */
    public function submitSwipeRequest(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'requested_status' => 'required|in:Present,Half Day,Leave,Absent',
            'requested_clock_in' => 'nullable|date_format:H:i',
            'requested_clock_out' => 'nullable|date_format:H:i',
            'reason' => 'required|string|max:500',
        ]);

        $employee = Employee::where('user_id', $request->user()->id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        if (empty($employee->reporting_manager_id)) {
            return response()->json(['success' => false, 'message' => 'No reporting manager assigned.'], 422);
        }

        // Find attendance record for the date
        $attendance = AttendanceEmployee::where('employee_id', $employee->id)
            ->where('date', $request->date)->first();

        // Check duplicate pending
        if ($attendance && Schema::hasTable('attendance_modification_requests')) {
            $pending = AttendanceModificationRequest::where('attendance_employee_id', $attendance->id)
                ->where('status', 'Pending')->exists();
            if ($pending) {
                return response()->json(['success' => false, 'message' => 'A pending request already exists for this date.'], 422);
            }
        }

        $swipeRequest = AttendanceModificationRequest::create([
            'attendance_employee_id' => $attendance->id ?? null,
            'employee_id' => $employee->id,
            'manager_employee_id' => $employee->reporting_manager_id,
            'requested_status' => $request->requested_status,
            'requested_clock_in' => $request->requested_clock_in,
            'requested_clock_out' => $request->requested_clock_out,
            'reason' => $request->reason,
            'status' => 'Pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Swipe request submitted.',
            'data' => ['id' => $swipeRequest->id, 'status' => 'Pending'],
        ], 201);
    }

    /**
     * GET /api/mobile/swipe-requests
     */
    public function swipeRequests(Request $request): JsonResponse
    {
        $employee = Employee::where('user_id', $request->user()->id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        $requests = AttendanceModificationRequest::where('employee_id', $employee->id)
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests->map(fn($r) => [
                'id' => $r->id,
                'date' => $r->attendance->date ?? null,
                'requested_status' => $r->requested_status,
                'requested_clock_in' => $r->requested_clock_in,
                'requested_clock_out' => $r->requested_clock_out,
                'reason' => $r->reason,
                'status' => $r->status,
                'manager_comment' => $r->manager_comment,
                'reviewed_at' => $r->reviewed_at,
            ]),
        ]);
    }

    // ════════════════════════════════════════════════════════════
    // 7. ATTENDANCE HISTORY
    // ════════════════════════════════════════════════════════════

    /**
     * GET /api/mobile/attendance-history?month=2026-03
     */
    public function attendanceHistory(Request $request): JsonResponse
    {
        $request->validate(['month' => 'nullable|string|size:7']);

        $employee = Employee::where('user_id', $request->user()->id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        $month = $request->month ?? date('Y-m');
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));

        $records = AttendanceEmployee::where('employee_id', $employee->id)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'records' => $records->map(fn($r) => [
                    'date' => $r->date,
                    'status' => $r->status,
                    'clock_in' => $r->clock_in,
                    'clock_out' => $r->clock_out,
                    'late' => $r->late,
                    'early_leaving' => $r->early_leaving,
                    'overtime' => $r->overtime,
                    'late_mark' => (bool)$r->late_mark,
                    'early_mark' => (bool)$r->early_mark,
                    'deduction_units' => (float)$r->deduction_units,
                ]),
                'summary' => [
                    'total' => $records->count(),
                    'present' => $records->where('status', 'Present')->count(),
                    'half_day' => $records->where('status', 'Half Day')->count(),
                    'absent' => $records->where('status', 'Absent')->count(),
                    'leave' => $records->where('status', 'Leave')->count(),
                    'late_marks' => (int)$records->sum('late_mark'),
                    'early_marks' => (int)$records->sum('early_mark'),
                ],
            ],
        ]);
    }

    // ════════════════════════════════════════════════════════════
    // 8. PROFILE
    // ════════════════════════════════════════════════════════════

    /**
     * GET /api/mobile/profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar ? asset(Storage::url('uploads/avatar/' . $user->avatar)) : null,
                ],
                'employee' => [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_id,
                    'name' => $employee->name,
                    'phone' => $employee->phone,
                    'dob' => $employee->dob,
                    'gender' => $employee->gender,
                    'department' => $employee->department->name ?? null,
                    'designation' => $employee->designation->name ?? null,
                    'branch' => $employee->branch->name ?? null,
                    'company_doj' => $employee->company_doj,
                    'shift' => $employee->shift ? [
                        'name' => $employee->shift->name,
                        'start' => $employee->shift->start_time,
                        'end' => $employee->shift->end_time,
                    ] : null,
                    'reporting_manager' => $employee->reportingManager->name ?? null,
                    'address' => $employee->present_address,
                    'city' => $employee->present_city,
                    'state' => $employee->present_state,
                ],
            ],
        ]);
    }

    // ════════════════════════════════════════════════════════════
    // 9. FINGERPRINT BIOMETRIC — Enroll, verify, clock-in, status, remove
    // ════════════════════════════════════════════════════════════
    //
    // The mobile app performs the actual fingerprint scan via the device's
    // native biometric SDK (Android BiometricPrompt / iOS Touch ID) and
    // derives a stable template string (e.g. SHA-256 of the SDK-returned
    // key-material or secure-hardware attestation). That template string is
    // what the app sends here — we never handle raw fingerprint images.

    /**
     * POST /api/mobile/fingerprint/enroll
     * Body: template (string, min 16 chars)
     */
    public function enrollFingerprint(Request $request): JsonResponse
    {
        $request->validate([
            'template' => 'required|string|min:16|max:4096',
        ]);

        $employee = Employee::where('user_id', $request->user()->id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        $employee->fingerprint_template = hash('sha256', $request->template);
        $employee->fingerprint_enrolled_at = now();
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Fingerprint enrolled successfully.',
            'data' => [
                'employee_id' => $employee->id,
                'enrolled_at' => $employee->fingerprint_enrolled_at->toDateTimeString(),
            ],
        ]);
    }

    /**
     * POST /api/mobile/fingerprint/verify
     * Body: template (string)
     */
    public function verifyFingerprint(Request $request): JsonResponse
    {
        $request->validate(['template' => 'required|string|min:16|max:4096']);

        $employee = Employee::where('user_id', $request->user()->id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        if (empty($employee->fingerprint_template)) {
            return response()->json([
                'success' => false,
                'message' => 'No fingerprint enrolled for this employee. Enroll first.',
            ], 422);
        }

        $match = hash_equals(
            $employee->fingerprint_template,
            hash('sha256', $request->template)
        );

        return response()->json([
            'success' => $match,
            'message' => $match ? 'Fingerprint verified.' : 'Fingerprint does not match.',
            'data' => ['match' => $match],
        ], $match ? 200 : 401);
    }

    /**
     * POST /api/mobile/fingerprint/clock-in
     * Body: template (required), latitude, longitude, address (optional)
     */
    public function clockInFingerprint(Request $request): JsonResponse
    {
        $request->validate([
            'template' => 'required|string|min:16|max:4096',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'address' => 'nullable|string|max:500',
        ]);

        $employee = Employee::where('user_id', $request->user()->id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        if (empty($employee->fingerprint_template)) {
            return response()->json([
                'success' => false,
                'message' => 'Fingerprint not enrolled. Please enroll before clocking in.',
            ], 422);
        }

        $match = hash_equals(
            $employee->fingerprint_template,
            hash('sha256', $request->template)
        );
        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Fingerprint verification failed.',
            ], 401);
        }

        $today = date('Y-m-d');
        $time = date('H:i:s');

        $existing = AttendanceEmployee::where('employee_id', $employee->id)
            ->where('date', $today)
            ->where('clock_out', '00:00:00')
            ->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Already clocked in. Please clock out first.',
            ], 422);
        }

        $settings = Utility::settings();
        $startTime = Carbon::parse($today . ' ' . ($settings['company_start_time'] ?? '09:00'));
        $clockInTime = Carbon::parse($today . ' ' . $time);
        $lateSeconds = max(0, $clockInTime->diffInSeconds($startTime, false) * -1);
        $late = gmdate('H:i:s', $lateSeconds);

        $graceMinutes = (int)($settings['attendance_grace_late_minutes'] ?? 30);
        $lateMark = $lateSeconds > ($graceMinutes * 60);
        $halfDayMinutes = (int)($settings['attendance_half_day_deduction_minutes'] ?? 60);
        $lateMinutes = (int)round($lateSeconds / 60);
        $status = ($lateMinutes >= ($halfDayMinutes + $graceMinutes)) ? 'Half Day' : 'Present';

        $attendance = AttendanceEmployee::create([
            'employee_id' => $employee->id,
            'date' => $today,
            'status' => $status,
            'clock_in' => $time,
            'clock_out' => '00:00:00',
            'late' => $late,
            'early_leaving' => '00:00:00',
            'overtime' => '00:00:00',
            'total_rest' => '00:00:00',
            'late_mark' => $lateMark ? 1 : 0,
            'early_mark' => 0,
            'clock_in_latitude' => $request->latitude,
            'clock_in_longitude' => $request->longitude,
            'clock_in_address' => $request->address,
            'clock_in_verified' => 1,
            'created_by' => $employee->created_by,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Clocked in with fingerprint.',
            'data' => [
                'attendance_id' => $attendance->id,
                'date' => $today,
                'clock_in' => $time,
                'status' => $status,
                'late' => $late,
            ],
        ]);
    }

    /**
     * GET /api/mobile/fingerprint/status
     */
    public function fingerprintStatus(Request $request): JsonResponse
    {
        $employee = Employee::where('user_id', $request->user()->id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        $enrolled = !empty($employee->fingerprint_template);

        return response()->json([
            'success' => true,
            'data' => [
                'enrolled' => $enrolled,
                'enrolled_at' => $enrolled && $employee->fingerprint_enrolled_at
                    ? Carbon::parse($employee->fingerprint_enrolled_at)->toDateTimeString()
                    : null,
            ],
        ]);
    }

    /**
     * DELETE /api/mobile/fingerprint/remove
     */
    public function removeFingerprint(Request $request): JsonResponse
    {
        $employee = Employee::where('user_id', $request->user()->id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        if (empty($employee->fingerprint_template)) {
            return response()->json([
                'success' => false,
                'message' => 'No fingerprint is currently enrolled.',
            ], 422);
        }

        $employee->fingerprint_template = null;
        $employee->fingerprint_enrolled_at = null;
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Fingerprint removed successfully.',
        ]);
    }

    // ════════════════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════════════════

    protected function saveBase64Photo(string $base64, string $folder, string $filename): ?string
    {
        try {
            $imageData = $base64;
            if (str_contains($base64, ',')) {
                $imageData = explode(',', $base64, 2)[1];
            }
            $decoded = base64_decode($imageData);
            if ($decoded === false || strlen($decoded) < 500) {
                return null;
            }

            $path = $folder . '/' . $filename . '_' . time() . '.jpg';
            Storage::disk('public')->put($path, $decoded);

            return 'storage/' . $path;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
