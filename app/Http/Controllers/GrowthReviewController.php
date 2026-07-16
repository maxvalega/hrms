<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PerformanceCycle;
use App\Models\GrMission;
use App\Models\GrShoutout;
use App\Models\GrSyncUp;
use App\Models\GrComebackPlan;
use App\Models\GrComebackPlanReview;
use App\Models\GrReview;
use App\Models\GrRating;
use App\Models\GrIncrement;
use App\Models\GrKpiAssignment;
use App\Models\GrCycleEmployee;
use App\Models\Utility;

class GrowthReviewController extends Controller
{
    private function creatorId() { return Auth::user()->creatorId(); }
    private function isAdmin()   { return in_array(Auth::user()->type, ['company', 'hr']); }
    private function isManager() { return in_array(Auth::user()->type, ['company', 'hr', 'employee']); }

    /**
     * Send a Growth Review email using the configured template.
     * Silently fails on any error — never breaks the controller flow.
     *
     * @param string                  $slug      Email template slug (e.g. 'growth_review_initiation')
     * @param string|array            $recipients Email address(es)
     * @param array                   $vars      Placeholder values for the template
     */
    private function notifyGrowthReview(string $slug, $recipients, array $vars = []): void
    {
        try {
            $emails = is_array($recipients) ? array_filter($recipients) : array_filter([$recipients]);
            if (empty($emails)) return;
            Utility::sendEmailTemplate($slug, array_values($emails), (object) $vars);
        } catch (\Throwable $e) {
            \Log::warning('GR email skipped (' . $slug . '): ' . $e->getMessage());
        }
    }

    /** Build review URL safely (catches missing routes in older deploys) */
    private function grReviewUrl(int $cycleId, ?int $employeeId = null, ?string $type = null): string
    {
        try {
            if ($type && $employeeId) {
                return route('growth-review.review.form', ['cycleId' => $cycleId, 'employeeId' => $employeeId, 'type' => $type]);
            }
            return route('growth-review.reviews', ['cycle_id' => $cycleId]);
        } catch (\Throwable $e) {
            return url('/growth-review');
        }
    }

    private function currentEmployee()
    {
        return Employee::where('user_id', Auth::id())->first();
    }

    private function managedEmployeeIds()
    {
        $emp = $this->currentEmployee();
        if (!$emp) return collect();
        return Employee::where('created_by', $this->creatorId())
            ->where('reporting_manager_id', $emp->id)
            ->pluck('id');
    }

    private function canAssignComebackPlanToEmployeeId(int $employeeId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->managedEmployeeIds()->contains($employeeId);
    }

    private function canManageComebackPlan(GrComebackPlan $plan): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $emp = $this->currentEmployee();
        if (!$emp) {
            return false;
        }

        return (int) $plan->assigned_by === (int) $emp->id;
    }

    // ════════════════════════════════════════════════════════════
    //  DASHBOARD
    // ════════════════════════════════════════════════════════════

    public function dashboard()
    {
        $cid = $this->creatorId();
        $emp = $this->currentEmployee();
        $isAdmin = $this->isAdmin();

        $activeCycle = PerformanceCycle::where('created_by', $cid)
            ->whereIn('status', ['active', 'review', 'calibration'])
            ->orderByDesc('start_date')->first();

        $cycles = PerformanceCycle::where('created_by', $cid)->orderByDesc('start_date')->get();

        // Stats
        $stats = [];
        if ($activeCycle) {
            $cycleId = $activeCycle->id;
            if ($isAdmin) {
                $stats['total_missions'] = GrMission::where('cycle_id', $cycleId)->count();
                $stats['pending_approvals'] = GrMission::where('cycle_id', $cycleId)->where('approval', 'pending')->count();
                $stats['shoutouts'] = GrShoutout::where('cycle_id', $cycleId)->count();
                $stats['reviews_submitted'] = GrReview::where('cycle_id', $cycleId)->where('status', 'submitted')->count();
                $stats['total_employees'] = Employee::where('created_by', $cid)->count();
                $stats['ratings_frozen'] = GrRating::where('cycle_id', $cycleId)->where('is_frozen', true)->count();
            } else {
                $empId = $emp ? $emp->id : 0;
                $stats['my_missions'] = GrMission::where('cycle_id', $cycleId)->where('employee_id', $empId)->count();
                $stats['my_completed'] = GrMission::where('cycle_id', $cycleId)->where('employee_id', $empId)->where('status', 'completed')->count();
                $stats['shoutouts_received'] = GrShoutout::where('to_employee_id', $empId)->count();
                $stats['pending_reviews'] = GrReview::where('cycle_id', $cycleId)->where('employee_id', $empId)->where('status', 'draft')->count();

                $managedIds = $this->managedEmployeeIds();
                $stats['team_pending_approval'] = GrMission::where('cycle_id', $cycleId)->whereIn('employee_id', $managedIds)->where('approval', 'pending')->count();
            }
        }

        $recentShoutouts = GrShoutout::with('fromEmployee', 'toEmployee')
            ->where('created_by', $cid)
            ->orderByDesc('created_at')->take(5)->get();

        return view('growth_review.dashboard', compact('activeCycle', 'cycles', 'stats', 'recentShoutouts', 'isAdmin'));
    }

    // ════════════════════════════════════════════════════════════
    //  PERFORMANCE CYCLES (HR/Admin)
    // ════════════════════════════════════════════════════════════

    public function cycles()
    {
        $cycles = PerformanceCycle::where('created_by', $this->creatorId())->orderByDesc('start_date')->get();

        // Eligibility rule: an employee can see / participate in a performance
        // cycle only after completing 6 months at the company (company_doj).
        // HR/company admins always see every cycle (they manage them).
        $notEligible = false;
        $tenureMonths = null;
        if (!$this->isAdmin()) {
            $emp = $this->currentEmployee();
            if ($emp && $emp->company_doj) {
                $doj = \Carbon\Carbon::parse($emp->company_doj);
                $tenureMonths = $doj->diffInMonths(\Carbon\Carbon::now());
                if ($tenureMonths < 6) {
                    $notEligible = true;
                    $cycles = collect();
                }
            } else {
                // No joining date on file — treat as not eligible.
                $notEligible = true;
                $cycles = collect();
            }
        }

        return view('growth_review.cycles.index', compact('cycles', 'notEligible', 'tenureMonths'));
    }

    public function cycleCreate()
    {
        return view('growth_review.cycles.create');
    }

    public function cycleStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'goal_deadline' => 'nullable|date',
            'self_review_start' => 'nullable|date',
            'self_review_end' => 'nullable|date',
            'manager_review_start' => 'nullable|date',
            'manager_review_end' => 'nullable|date',
            'head_review_start' => 'nullable|date',
            'head_review_end' => 'nullable|date',
            'calibration_start' => 'nullable|date',
            'calibration_end' => 'nullable|date',
            'rating_scale' => 'nullable|string|max:20',
        ]);
        $data['created_by'] = $this->creatorId();
        $data['status'] = 'draft';
        PerformanceCycle::create($data);
        return redirect()->route('growth-review.cycles')->with('success', __('Performance cycle created.'));
    }

    public function cycleEdit($id)
    {
        $cycle = PerformanceCycle::where('created_by', $this->creatorId())->findOrFail($id);
        return view('growth_review.cycles.edit', compact('cycle'));
    }

    public function cycleUpdate(Request $request, $id)
    {
        $cycle = PerformanceCycle::where('created_by', $this->creatorId())->findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'goal_deadline' => 'nullable|date',
            'self_review_start' => 'nullable|date',
            'self_review_end' => 'nullable|date',
            'manager_review_start' => 'nullable|date',
            'manager_review_end' => 'nullable|date',
            'head_review_start' => 'nullable|date',
            'head_review_end' => 'nullable|date',
            'calibration_start' => 'nullable|date',
            'calibration_end' => 'nullable|date',
            'status' => 'nullable|in:draft,active,review,calibration,completed',
            'rating_scale' => 'nullable|string|max:20',
        ]);
        $cycle->update($data);
        return redirect()->route('growth-review.cycles')->with('success', __('Cycle updated.'));
    }

    public function cycleDelete($id)
    {
        $cycle = PerformanceCycle::where('created_by', $this->creatorId())->findOrFail($id);
        $cycle->delete();
        return redirect()->route('growth-review.cycles')->with('success', __('Cycle deleted.'));
    }

    public function cycleShow($id)
    {
        $cid = $this->creatorId();
        $cycle = PerformanceCycle::where('created_by', $cid)->findOrFail($id);
        $assigned = GrCycleEmployee::with('employee')
            ->where('cycle_id', $id)->orderBy('created_at', 'desc')->get();
        $assignedIds = $assigned->pluck('employee_id')->all();
        $employees = Employee::where('created_by', $cid)->orderBy('name')->get(['id', 'name', 'employee_id', 'department_id']);
        $departments = DB::table('departments')->where('created_by', $cid)->orderBy('name')->get(['id', 'name']);

        // Mission counts per employee for this cycle
        $missionCounts = GrMission::where('cycle_id', $id)
            ->selectRaw('employee_id, count(*) as total, sum(approval = "approved") as approved')
            ->groupBy('employee_id')->get()->keyBy('employee_id');

        return view('growth_review.cycles.show', compact('cycle', 'assigned', 'assignedIds', 'employees', 'departments', 'missionCounts'));
    }

    public function cycleAssign(Request $request, $id)
    {
        $cycle = PerformanceCycle::where('created_by', $this->creatorId())->findOrFail($id);
        $data = $request->validate([
            'employee_ids'   => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:employees,id',
        ]);

        $cid = $this->creatorId();
        $count = 0;
        foreach ($data['employee_ids'] as $empId) {
            $exists = GrCycleEmployee::where('cycle_id', $id)->where('employee_id', $empId)->exists();
            if ($exists) continue;
            GrCycleEmployee::create([
                'cycle_id'      => $id,
                'employee_id'   => $empId,
                'status'        => 'assigned',
                'goal_deadline' => $cycle->goal_deadline,
                'created_by'    => $cid,
            ]);
            $count++;
        }
        return back()->with('success', __(':count employee(s) assigned to cycle.', ['count' => $count]));
    }

    public function cycleUnassign($id, $empId)
    {
        GrCycleEmployee::where('cycle_id', $id)->where('employee_id', $empId)->delete();
        return back()->with('success', __('Employee removed from cycle.'));
    }

    public function cycleActivate(Request $request, $id)
    {
        $cycle = PerformanceCycle::where('created_by', $this->creatorId())->findOrFail($id);
        $assigned = GrCycleEmployee::with('employee.user')->where('cycle_id', $id)->get();

        if ($assigned->isEmpty()) {
            return back()->with('error', __('No employees assigned. Please assign employees first.'));
        }

        // Update cycle status to active
        $cycle->update(['status' => 'active']);

        // Update all assigned employees status to goal_pending & set notified_at
        GrCycleEmployee::where('cycle_id', $id)->update([
            'status'      => 'goal_pending',
            'notified_at' => now(),
        ]);

        // Send templated email to each assigned employee — Growth Review Initiation
        $companyName = Utility::settings()['company_name'] ?? config('app.name');
        foreach ($assigned as $ce) {
            $emp = $ce->employee;
            if (!$emp || !$emp->user || empty($emp->user->email)) continue;

            $this->notifyGrowthReview('growth_review_initiation', $emp->user->email, [
                'employee_name'    => $emp->name,
                'cycle_name'       => $cycle->name,
                'cycle_start_date' => $cycle->start_date ? \Carbon\Carbon::parse($cycle->start_date)->format('d M Y') : '',
                'cycle_end_date'   => $cycle->end_date   ? \Carbon\Carbon::parse($cycle->end_date)->format('d M Y') : '',
                'due_date'         => $cycle->goal_deadline ? \Carbon\Carbon::parse($cycle->goal_deadline)->format('d M Y') : 'N/A',
                'review_url'       => $this->grReviewUrl($cycle->id),
                'company_name'     => $companyName,
            ]);
        }

        return back()->with('success', __('Cycle activated! :count employee(s) notified to add goals.', ['count' => $assigned->count()]));
    }

    // ════════════════════════════════════════════════════════════
    //  MISSIONS (GOALS)
    // ════════════════════════════════════════════════════════════

    public function missions(Request $request)
    {
        $cid = $this->creatorId();
        $emp = $this->currentEmployee();
        $isAdmin = $this->isAdmin();
        $cycleId = $request->get('cycle_id');

        $cycles = PerformanceCycle::where('created_by', $cid)->orderByDesc('start_date')->get();
        if (!$cycleId && $cycles->isNotEmpty()) $cycleId = $cycles->first()->id;

        $query = GrMission::with('employee', 'cycle')->where('cycle_id', $cycleId);

        if (!$isAdmin) {
            $empId = $emp ? $emp->id : 0;
            $managedIds = $this->managedEmployeeIds();
            $query->where(function ($q) use ($empId, $managedIds) {
                $q->where('employee_id', $empId)->orWhereIn('employee_id', $managedIds);
            });
        }

        $missions = $query->orderByDesc('created_at')->get();
        $employees = Employee::where('created_by', $cid)->get();

        // ── Assigned KRA / KPI records (from KPI Generator) ───────────
        // Shown alongside traditional missions. Scope to the same
        // visibility rules: admins see everything; others see their own
        // plus anything assigned to employees reporting to them.
        $assignQuery = \App\Models\GrKpiAssignment::with(['generation', 'employee'])
            ->where('created_by', $cid);
        if (!$isAdmin) {
            $empId = $emp ? $emp->id : 0;
            $managedIds = $this->managedEmployeeIds();
            $assignQuery->where(function ($q) use ($empId, $managedIds) {
                $q->where('employee_id', $empId)->orWhereIn('employee_id', $managedIds);
            });
        }
        $kpiAssignments = $assignQuery->orderByDesc('assigned_at')->get();

        return view('growth_review.missions.index', compact(
            'missions', 'cycles', 'cycleId', 'employees', 'isAdmin', 'kpiAssignments'
        ));
    }

    public function missionStore(Request $request)
    {
        $data = $request->validate([
            'cycle_id' => 'required|exists:performance_cycles,id',
            'employee_id' => 'nullable|exists:employees,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'kpi' => 'nullable|string|max:255',
            'weightage' => 'nullable|numeric|min:0|max:100',
            'deadline' => 'nullable|date',
        ]);

        $emp = $this->currentEmployee();
        if (empty($data['employee_id']) && $emp) {
            $data['employee_id'] = $emp->id;
        }
        $data['created_by'] = $this->creatorId();
        $data['approval'] = $this->isAdmin() ? 'approved' : 'pending';

        $mission = GrMission::create($data);

        // Update cycle-employee status to goal_submitted
        GrCycleEmployee::where('cycle_id', $data['cycle_id'])
            ->where('employee_id', $data['employee_id'])
            ->where('status', 'goal_pending')
            ->update(['status' => 'goal_submitted']);

        // Email: KRA added — notify the employee whose KRA was added (only if added by someone else)
        try {
            $targetEmp = Employee::with('user')->find($data['employee_id']);
            $addedByEmp = $emp; // current employee (or null if admin)
            $isSameUser = $targetEmp && $addedByEmp && (int) $targetEmp->id === (int) $addedByEmp->id;
            if ($targetEmp && $targetEmp->user && !empty($targetEmp->user->email) && !$isSameUser) {
                $cycle = PerformanceCycle::find($data['cycle_id']);
                $kraCount = GrMission::where('cycle_id', $data['cycle_id'])
                    ->where('employee_id', $data['employee_id'])
                    ->count();
                $addedByName = Auth::user()->name ?? __('HR');
                $this->notifyGrowthReview('growth_review_kra_added', $targetEmp->user->email, [
                    'employee_name' => $targetEmp->name,
                    'cycle_name'    => $cycle->name ?? '',
                    'added_by'      => $addedByName,
                    'kra_count'     => $kraCount,
                    'review_url'    => $this->grReviewUrl($data['cycle_id']),
                    'company_name'  => Utility::settings()['company_name'] ?? config('app.name'),
                ]);
            }
        } catch (\Throwable $e) { /* silent */ }

        return redirect()->route('growth-review.missions', ['cycle_id' => $data['cycle_id']])->with('success', __('Mission created.'));
    }

    public function missionUpdate(Request $request, $id)
    {
        $mission = GrMission::findOrFail($id);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'kpi' => 'nullable|string|max:255',
            'weightage' => 'nullable|numeric|min:0|max:100',
            'deadline' => 'nullable|date',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'progress' => 'nullable|integer|min:0|max:100',
        ]);
        $mission->update($data);
        return back()->with('success', __('Mission updated.'));
    }

    public function missionApprove(Request $request, $id)
    {
        $mission = GrMission::findOrFail($id);
        $action = $request->input('action', 'approved');
        $emp = $this->currentEmployee();
        $updateData = [
            'approval' => $action,
            'approved_by' => $emp ? $emp->id : null,
            'approved_at' => now(),
            'manager_remarks' => $request->input('manager_remarks'),
        ];
        // Auto-update task status on approval
        if ($action === 'approved' && $mission->status === 'pending') {
            $updateData['status'] = 'in_progress';
        }
        $mission->update($updateData);
        return back()->with('success', __('Mission :action.', ['action' => $action]));
    }

    public function missionRate(Request $request, $id)
    {
        $mission = GrMission::findOrFail($id);
        $data = $request->validate([
            'field' => 'required|in:self_rating,self_remarks,manager_rating,manager_rating_remarks,hod_rating,hod_rating_remarks',
            'value' => 'nullable|string|max:1000',
        ]);

        $field = $data['field'];
        $value = $data['value'];

        // Role check
        $user = Auth::user();
        $isAdmin = in_array($user->type, ['company', 'super admin'], true);
        $emp = $this->currentEmployee();
        $selfFields = ['self_rating', 'self_remarks'];
        $mgrFields  = ['manager_rating', 'manager_rating_remarks'];
        $hodFields  = ['hod_rating', 'hod_rating_remarks'];

        if (!$isAdmin) {
            $isOwner   = $emp && (int) $mission->employee_id === $emp->id;
            $isMgr     = $emp && (int) ($mission->employee->reporting_manager_id ?? 0) === $emp->id;
            $isHod     = $emp && ((int) ($mission->employee->hod_id ?? 0) === $emp->id || (int) ($mission->employee->management_id ?? 0) === $emp->id);

            if (in_array($field, $selfFields) && !$isOwner) {
                return response()->json(['ok' => false, 'error' => 'Only the employee can set self rating.'], 403);
            }
            if (in_array($field, $mgrFields) && !$isMgr) {
                return response()->json(['ok' => false, 'error' => 'Only the manager can set manager rating.'], 403);
            }
            if (in_array($field, $hodFields) && !$isHod) {
                return response()->json(['ok' => false, 'error' => 'Only the HOD can set HOD rating.'], 403);
            }
        }

        // Rating fields: cast to decimal
        if (str_contains($field, '_rating') && !str_contains($field, '_remarks')) {
            $value = $value !== null && $value !== '' ? round((float) $value, 1) : null;
            if ($value !== null && ($value < 0 || $value > 5)) {
                return response()->json(['ok' => false, 'error' => 'Rating must be 0-5.'], 422);
            }
        }

        $mission->update([$field => $value]);
        return response()->json(['ok' => true, 'field' => $field, 'value' => $mission->$field]);
    }

    public function missionUpload(Request $request, $id)
    {
        $mission = GrMission::findOrFail($id);
        $request->validate(['document' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg']);

        $file = $request->file('document');
        $name = $file->getClientOriginalName();
        $path = $file->store('gr_mission_docs/' . $id, 'public');

        $mission->update(['document' => $path, 'document_name' => $name]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'path' => $path, 'name' => $name]);
        }
        return back()->with('success', __('Document uploaded.'));
    }

    public function missionDocDelete(Request $request, $id)
    {
        $mission = GrMission::findOrFail($id);
        if ($mission->document) {
            Storage::disk('public')->delete($mission->document);
            $mission->update(['document' => null, 'document_name' => null]);
        }
        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('success', __('Document removed.'));
    }

    public function missionDelete($id)
    {
        GrMission::findOrFail($id)->delete();
        return back()->with('success', __('Mission deleted.'));
    }

    // ════════════════════════════════════════════════════════════
    //  SHOUTOUTS
    // ════════════════════════════════════════════════════════════

    public function shoutouts()
    {
        $cid = $this->creatorId();
        $emp = $this->currentEmployee();

        $shoutouts = GrShoutout::with('fromEmployee', 'toEmployee')
            ->where('created_by', $cid)
            ->orderByDesc('created_at')->paginate(20);

        $employees = Employee::where('created_by', $cid)->get();
        $activeCycle = PerformanceCycle::where('created_by', $cid)->whereIn('status', ['active', 'review'])->first();

        $badges = ['star' => '⭐ Star Performer', 'teamwork' => '🤝 Team Player', 'innovation' => '💡 Innovator', 'leadership' => '🎯 Leader', 'helpful' => '🙌 Helping Hand', 'quality' => '✅ Quality Champion'];

        return view('growth_review.shoutouts.index', compact('shoutouts', 'employees', 'activeCycle', 'badges'));
    }

    public function shoutoutStore(Request $request)
    {
        $data = $request->validate([
            'to_employee_id' => 'required|exists:employees,id',
            'message' => 'required|string|max:500',
            'badge' => 'nullable|string|max:50',
        ]);

        $emp = $this->currentEmployee();
        $activeCycle = PerformanceCycle::where('created_by', $this->creatorId())->whereIn('status', ['active', 'review'])->first();

        GrShoutout::create([
            'from_employee_id' => $emp ? $emp->id : 0,
            'to_employee_id' => $data['to_employee_id'],
            'message' => $data['message'],
            'badge' => $data['badge'] ?? null,
            'cycle_id' => $activeCycle ? $activeCycle->id : null,
            'created_by' => $this->creatorId(),
        ]);

        return back()->with('success', __('Shoutout sent!'));
    }

    public function shoutoutDelete($id)
    {
        GrShoutout::findOrFail($id)->delete();
        return back()->with('success', __('Shoutout removed.'));
    }

    // ════════════════════════════════════════════════════════════
    //  SYNC UPS
    // ════════════════════════════════════════════════════════════

    public function syncUps(Request $request)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('growth-review.dashboard')->with('error', __('Permission denied.'));
        }

        $cid = $this->creatorId();
        $isAdmin = true;

        $syncUps = GrSyncUp::with('employee', 'manager')
            ->where('created_by', $cid)
            ->orderByDesc('meeting_date')
            ->paginate(20);
        $employees = Employee::where('created_by', $cid)->get();
        $cycles = PerformanceCycle::where('created_by', $cid)->orderByDesc('start_date')->get();

        return view('growth_review.sync_ups.index', compact('syncUps', 'employees', 'cycles', 'isAdmin'));
    }

    public function syncUpStore(Request $request)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('growth-review.dashboard')->with('error', __('Permission denied.'));
        }

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'meeting_date' => 'required|date',
            'notes' => 'nullable|string',
            'discussion_points' => 'nullable|string',
            'action_items' => 'nullable|string',
            'cycle_id' => 'nullable|exists:performance_cycles,id',
        ]);

        $emp = $this->currentEmployee();
        GrSyncUp::create([
            'cycle_id' => $data['cycle_id'] ?? null,
            'employee_id' => $data['employee_id'],
            'manager_id' => $emp ? $emp->id : 0,
            'meeting_date' => $data['meeting_date'],
            'notes' => $data['notes'] ?? null,
            'discussion_points' => !empty($data['discussion_points']) ? array_filter(array_map('trim', explode("\n", $data['discussion_points']))) : null,
            'action_items' => !empty($data['action_items']) ? array_filter(array_map('trim', explode("\n", $data['action_items']))) : null,
            'status' => 'completed',
            'created_by' => $this->creatorId(),
        ]);

        return back()->with('success', __('Sync Up recorded.'));
    }

    public function syncUpUpdate(Request $request, $id)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('growth-review.dashboard')->with('error', __('Permission denied.'));
        }

        $syncUp = GrSyncUp::findOrFail($id);
        if ((int) $syncUp->created_by !== (int) $this->creatorId()) {
            return redirect()->route('growth-review.dashboard')->with('error', __('Permission denied.'));
        }

        $data = $request->validate([
            'notes' => 'nullable|string',
            'discussion_points' => 'nullable|string',
            'action_items' => 'nullable|string',
            'status' => 'nullable|in:scheduled,completed,cancelled',
        ]);

        $syncUp->update([
            'notes' => $data['notes'] ?? $syncUp->notes,
            'discussion_points' => !empty($data['discussion_points']) ? array_filter(array_map('trim', explode("\n", $data['discussion_points']))) : $syncUp->discussion_points,
            'action_items' => !empty($data['action_items']) ? array_filter(array_map('trim', explode("\n", $data['action_items']))) : $syncUp->action_items,
            'status' => $data['status'] ?? $syncUp->status,
        ]);

        return back()->with('success', __('Sync Up updated.'));
    }

    public function syncUpDelete($id)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('growth-review.dashboard')->with('error', __('Permission denied.'));
        }

        $syncUp = GrSyncUp::findOrFail($id);
        if ((int) $syncUp->created_by !== (int) $this->creatorId()) {
            return redirect()->route('growth-review.dashboard')->with('error', __('Permission denied.'));
        }

        $syncUp->delete();
        return back()->with('success', __('Sync Up deleted.'));
    }

    // ════════════════════════════════════════════════════════════
    //  COMEBACK PLANS (PIPs)
    // ════════════════════════════════════════════════════════════

    public function comebackPlans()
    {
        $cid = $this->creatorId();
        $emp = $this->currentEmployee();
        $isAdmin = $this->isAdmin();

        $query = GrComebackPlan::with('employee', 'assignedBy', 'reviews.reviewer')->where('created_by', $cid);

        if (!$isAdmin && $emp) {
            $managedIds = $this->managedEmployeeIds();
            $query->where(function ($q) use ($emp, $managedIds) {
                $q->where('employee_id', $emp->id)->orWhereIn('employee_id', $managedIds);
            });
        }

        $plans = $query->orderByDesc('created_at')->get();

        $managedIds = $this->managedEmployeeIds();
        $canAssign = $isAdmin || $managedIds->isNotEmpty();
        $assignableEmployees = $isAdmin
            ? Employee::where('created_by', $cid)->orderBy('name')->get()
            : Employee::where('created_by', $cid)->whereIn('id', $managedIds)->orderBy('name')->get();

        return view('growth_review.comeback.index', compact('plans', 'isAdmin', 'canAssign', 'assignableEmployees', 'emp'));
    }

    public function comebackStore(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'title' => 'required|string|max:255',
            'issues' => 'nullable|string',
            'action_steps' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'cycle_id' => 'nullable|exists:performance_cycles,id',
        ]);

        $cid = $this->creatorId();
        $emp = $this->currentEmployee();
        if (!$this->isAdmin() && !$emp) {
            return back()->with('error', __('Only managers can assign a plan.'));
        }

        if (!Employee::where('created_by', $cid)->where('id', $data['employee_id'])->exists()) {
            return back()->with('error', __('Invalid employee selection.'));
        }

        if (!$this->canAssignComebackPlanToEmployeeId((int) $data['employee_id'])) {
            return back()->with('error', __('You can assign plans only to your team members.'));
        }

        GrComebackPlan::create([
            'employee_id' => $data['employee_id'],
            'assigned_by' => $emp ? $emp->id : 0,
            'cycle_id' => $data['cycle_id'] ?? null,
            'title' => $data['title'],
            'issues' => $data['issues'] ?? null,
            'action_steps' => !empty($data['action_steps']) ? array_filter(array_map('trim', explode("\n", $data['action_steps']))) : null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => 'active',
            'final_outcome' => 'pending',
            'auto_initiated' => false,
            'created_by' => $cid,
        ]);

        return back()->with('success', __('Comeback Plan assigned.'));
    }

    public function comebackUpdate(Request $request, $id)
    {
        $plan = GrComebackPlan::where('created_by', $this->creatorId())->findOrFail($id);
        if (!$this->canManageComebackPlan($plan)) {
            return back()->with('error', __('You are not allowed to update this plan.'));
        }

        $data = $request->validate([
            'status' => 'nullable|in:active,on_track,at_risk,completed,failed',
            'final_remarks' => 'nullable|string',
            'final_outcome' => 'nullable|in:pending,success,failed,extended',
            'title' => 'nullable|string|max:255',
            'issues' => 'nullable|string',
            'action_steps' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $update = [];
        if (isset($data['status'])) $update['status'] = $data['status'];
        if (isset($data['final_remarks'])) $update['final_remarks'] = $data['final_remarks'];
        if (isset($data['final_outcome'])) {
            $update['final_outcome'] = $data['final_outcome'];
            if ($data['final_outcome'] !== 'pending') {
                $update['outcome_decided_at'] = now();
                if (!isset($data['status'])) {
                    $update['status'] = $data['final_outcome'] === 'success' ? 'completed' : ($data['final_outcome'] === 'failed' ? 'failed' : $plan->status);
                }
            } else {
                $update['outcome_decided_at'] = null;
            }
        }
        if (isset($data['title'])) $update['title'] = $data['title'];
        if (isset($data['issues'])) $update['issues'] = $data['issues'];
        if (!empty($data['action_steps'])) $update['action_steps'] = array_filter(array_map('trim', explode("\n", $data['action_steps'])));
        if (isset($data['start_date'])) $update['start_date'] = $data['start_date'];
        if (isset($data['end_date'])) $update['end_date'] = $data['end_date'];

        $plan->update($update);
        return back()->with('success', __('Comeback Plan updated.'));
    }

    public function comebackDelete($id)
    {
        $plan = GrComebackPlan::where('created_by', $this->creatorId())->findOrFail($id);
        if (!$this->canManageComebackPlan($plan)) {
            return back()->with('error', __('You are not allowed to delete this plan.'));
        }

        $plan->delete();
        return back()->with('success', __('Comeback Plan deleted.'));
    }

    public function comebackReviewStore(Request $request, $id)
    {
        $plan = GrComebackPlan::with('employee')->where('created_by', $this->creatorId())->findOrFail($id);
        if (!$this->canManageComebackPlan($plan)) {
            return back()->with('error', __('You are not allowed to add reviews for this plan.'));
        }

        $data = $request->validate([
            'review_date' => 'required|date',
            'progress' => 'required|in:on_track,at_risk,off_track',
            'rating' => 'nullable|integer|min:1|max:5',
            'strengths' => 'nullable|string',
            'improvements' => 'nullable|string',
            'comments' => 'nullable|string',
        ]);

        $emp = $this->currentEmployee();
        if (!$emp) {
            return back()->with('error', __('Reviewer employee record not found.'));
        }

        GrComebackPlanReview::create([
            'plan_id' => $plan->id,
            'reviewer_id' => $emp->id,
            'review_date' => $data['review_date'],
            'progress' => $data['progress'],
            'rating' => $data['rating'] ?? null,
            'strengths' => $data['strengths'] ?? null,
            'improvements' => $data['improvements'] ?? null,
            'comments' => $data['comments'] ?? null,
            'created_by' => $this->creatorId(),
        ]);

        return back()->with('success', __('Plan review added.'));
    }

    // ════════════════════════════════════════════════════════════
    //  REVIEWS (Multi-level workflow)
    // ════════════════════════════════════════════════════════════

    public function reviews(Request $request)
    {
        $cid = $this->creatorId();
        $emp = $this->currentEmployee();
        $isAdmin = $this->isAdmin();
        $cycleId = $request->get('cycle_id');

        $cycles = PerformanceCycle::where('created_by', $cid)->orderByDesc('start_date')->get();
        if (!$cycleId && $cycles->isNotEmpty()) $cycleId = $cycles->first()->id;

        $cycle = PerformanceCycle::find($cycleId);

        // Get all employees with their review status
        if ($isAdmin) {
            $employees = Employee::where('created_by', $cid)->get();
        } else {
            $empId = $emp ? $emp->id : 0;
            $managedIds = $this->managedEmployeeIds();
            $employees = Employee::where('created_by', $cid)
                ->where(function ($q) use ($empId, $managedIds) {
                    $q->where('id', $empId)->orWhereIn('id', $managedIds);
                })->get();
        }

        $reviews = GrReview::where('cycle_id', $cycleId)->get()->groupBy('employee_id');
        $ratings = GrRating::where('cycle_id', $cycleId)->get()->keyBy('employee_id');

        // KPI Generator weighted scores per employee — used as the value shown
        // in the Self / Manager / Head columns when no gr_reviews entry exists.
        // Most-recent assignment wins.
        $kpiScores = [];
        $assignments = GrKpiAssignment::with('generation')
            ->where('created_by', $cid)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->orderByDesc('assigned_at')
            ->get();
        foreach ($assignments as $a) {
            if (isset($kpiScores[$a->employee_id])) continue;
            $gen = $a->generation;
            if (!$gen) continue;
            $kras = json_decode($gen->content_json, true) ?? [];
            $self = $mgr = $hod = 0.0;
            foreach ($kras as $k) {
                $all = $k['kpis'] ?? [];
                $count = count($all);
                if ($count === 0) continue;
                $w = ((int) ($k['weightage'] ?? 0)) / 100;
                $self += (array_sum(array_map(fn($x) => (int)($x['rating'] ?? 0), $all)) / $count) * $w;
                $mgr  += (array_sum(array_map(fn($x) => (int)($x['manager_rating'] ?? 0), $all)) / $count) * $w;
                $hod  += (array_sum(array_map(fn($x) => (int)($x['head_rating'] ?? 0), $all)) / $count) * $w;
            }
            $kpiScores[$a->employee_id] = [
                'gen_id'  => $gen->id,
                'self'    => round($self, 2),
                'manager' => round($mgr, 2),
                'hod'     => round($hod, 2),
            ];
        }

        return view('growth_review.reviews.index', compact('cycle', 'cycles', 'cycleId', 'employees', 'reviews', 'ratings', 'isAdmin', 'kpiScores'));
    }

    public function reviewForm($cycleId, $employeeId, $type)
    {
        $cycle = PerformanceCycle::findOrFail($cycleId);
        $employee = Employee::findOrFail($employeeId);
        $missions = GrMission::where('cycle_id', $cycleId)->where('employee_id', $employeeId)->where('approval', 'approved')->get();

        $review = GrReview::where('cycle_id', $cycleId)->where('employee_id', $employeeId)->where('review_type', $type)->first();

        $allReviews = GrReview::where('cycle_id', $cycleId)->where('employee_id', $employeeId)->where('status', 'submitted')->get()->keyBy('review_type');

        // Determine if viewer can edit this review type
        $user = Auth::user();
        $viewerEmp = $this->currentEmployee();
        $isAdmin = in_array($user->type, ['company', 'super admin', 'hr'], true);
        $isOwner = $viewerEmp && (int) $employeeId === $viewerEmp->id;
        $isMgr = $viewerEmp && (int) ($employee->reporting_manager_id ?? 0) === $viewerEmp->id;
        $isHod = $viewerEmp && ((int) ($employee->hod_id ?? 0) === $viewerEmp->id || (int) ($employee->management_id ?? 0) === $viewerEmp->id);

        $canEdit = $isAdmin
            || ($type === 'self' && $isOwner)
            || ($type === 'manager' && $isMgr)
            || ($type === 'head' && $isHod);

        // If already submitted, read-only
        if ($review && $review->status === 'submitted') $canEdit = false;

        return view('growth_review.reviews.form', compact('cycle', 'employee', 'missions', 'review', 'type', 'allReviews', 'canEdit'));
    }

    public function reviewStore(Request $request)
    {
        $data = $request->validate([
            'cycle_id' => 'required|exists:performance_cycles,id',
            'employee_id' => 'required|exists:employees,id',
            'review_type' => 'required|in:self,manager,head,management',
            'rating' => 'nullable|numeric|min:0|max:10',
            'ratings_json' => 'nullable|string',
            'strengths' => 'nullable|string',
            'improvements' => 'nullable|string',
            'comments' => 'nullable|string',
            'action' => 'required|in:draft,submit',
        ]);

        $emp = $this->currentEmployee();

        $review = GrReview::updateOrCreate(
            ['cycle_id' => $data['cycle_id'], 'employee_id' => $data['employee_id'], 'review_type' => $data['review_type']],
            [
                'reviewer_id' => $emp ? $emp->id : Auth::id(),
                'rating' => $data['rating'] ?? null,
                'ratings_json' => !empty($data['ratings_json']) ? json_decode($data['ratings_json'], true) : null,
                'strengths' => $data['strengths'] ?? null,
                'improvements' => $data['improvements'] ?? null,
                'comments' => $data['comments'] ?? null,
                'status' => $data['action'] === 'submit' ? 'submitted' : 'draft',
                'submitted_at' => $data['action'] === 'submit' ? now() : null,
                'created_by' => $this->creatorId(),
            ]
        );

        // Auto-update gr_ratings
        if ($data['action'] === 'submit' && $data['rating']) {
            $ratingRow = GrRating::firstOrCreate(
                ['cycle_id' => $data['cycle_id'], 'employee_id' => $data['employee_id']],
                ['created_by' => $this->creatorId()]
            );
            $col = $data['review_type'] . '_rating';
            if (in_array($col, ['self_rating', 'manager_rating', 'head_rating'])) {
                $ratingRow->update([$col => $data['rating']]);
            }
        }

        // ── Email notifications on submit ─────────────────────────────────
        if ($data['action'] === 'submit') {
            try {
                $cycle    = PerformanceCycle::find($data['cycle_id']);
                $targetEmp = Employee::with(['user', 'designation', 'department', 'reportingManager.user', 'hod.user', 'management.user'])
                    ->find($data['employee_id']);
                $companyName = Utility::settings()['company_name'] ?? config('app.name');
                $reviewUrl   = $this->grReviewUrl($data['cycle_id'], $data['employee_id'], $data['review_type']);
                $rating      = $data['rating'] ?? '';

                if ($targetEmp) {
                    $baseVars = [
                        'employee_name'         => $targetEmp->name,
                        'employee_designation'  => optional($targetEmp->designation)->name ?? '',
                        'department_name'       => optional($targetEmp->department)->name ?? '',
                        'cycle_name'            => $cycle->name ?? '',
                        'review_url'            => $reviewUrl,
                        'company_name'          => $companyName,
                        'due_date'              => $cycle && $cycle->end_date ? \Carbon\Carbon::parse($cycle->end_date)->format('d M Y') : '',
                    ];

                    switch ($data['review_type']) {
                        case 'self':
                            // Notify reporting manager
                            $mgr = optional($targetEmp->reportingManager);
                            if ($mgr && $mgr->user && !empty($mgr->user->email)) {
                                $this->notifyGrowthReview('growth_review_manager_rating', $mgr->user->email, array_merge($baseVars, [
                                    'manager_name' => $mgr->name,
                                ]));
                            }
                            break;

                        case 'manager':
                            // Notify HOD
                            $hod = optional($targetEmp->hod);
                            if ($hod && $hod->user && !empty($hod->user->email)) {
                                $this->notifyGrowthReview('growth_review_hod_rating', $hod->user->email, array_merge($baseVars, [
                                    'hod_name'       => $hod->name,
                                    'manager_rating' => $rating,
                                ]));
                            }
                            break;

                        case 'head':
                            // Notify management
                            $mgmt = optional($targetEmp->management);
                            $managerRow = GrReview::where('cycle_id', $data['cycle_id'])
                                ->where('employee_id', $data['employee_id'])
                                ->where('review_type', 'manager')->first();
                            if ($mgmt && $mgmt->user && !empty($mgmt->user->email)) {
                                $this->notifyGrowthReview('growth_review_management_rating', $mgmt->user->email, array_merge($baseVars, [
                                    'management_name' => $mgmt->name,
                                    'hod_rating'      => $rating,
                                    'manager_rating'  => $managerRow->rating ?? '',
                                ]));
                            }
                            break;

                        case 'management':
                            // Final stage — review is now complete. Optionally notify employee.
                            // No email sent here; "Growth Review Closed" fires on freeze.
                            break;
                    }
                }

                // Always: if a SELF rating was just submitted, also notify employee of next step.
                // If a NON-SELF rating was submitted by reviewer who is NOT the employee, no employee email here.
                // (Employee gets "self rating pending" reminder via a separate cron / on KRA add.)
            } catch (\Throwable $e) {
                \Log::warning('GR reviewStore email skipped: ' . $e->getMessage());
            }
        }

        $msg = $data['action'] === 'submit' ? 'Review submitted.' : 'Review saved as draft.';
        return redirect()->route('growth-review.reviews', ['cycle_id' => $data['cycle_id']])->with('success', __($msg));
    }

    // ════════════════════════════════════════════════════════════
    //  CALIBRATION & RATINGS (HR)
    // ════════════════════════════════════════════════════════════

    public function calibration(Request $request)
    {
        $cid = $this->creatorId();
        $cycleId = $request->get('cycle_id');
        $deptId = $request->get('department_id');
        $cycles = PerformanceCycle::where('created_by', $cid)->orderByDesc('start_date')->get();
        if (!$cycleId && $cycles->isNotEmpty()) $cycleId = $cycles->first()->id;

        $query = GrRating::with('employee.department')->where('cycle_id', $cycleId);
        if ($deptId) {
            $query->whereHas('employee', function ($q) use ($deptId) {
                $q->where('department_id', $deptId);
            });
        }
        $ratings = $query->get();

        $employees = Employee::where('created_by', $cid)->get();
        $departments = \App\Models\Department::where('created_by', $cid)->orderBy('name')->get();
        $cycle = PerformanceCycle::find($cycleId);

        // Bell curve distribution counts (for summary banner)
        $distribution = [
            'Outstanding' => $ratings->where('calibration_category', 'Outstanding')->count(),
            'Exceeds'     => $ratings->where('calibration_category', 'Exceeds')->count(),
            'Meets'       => $ratings->where('calibration_category', 'Meets')->count(),
            'Low'         => $ratings->where('calibration_category', 'Low')->count(),
        ];

        return view('growth_review.calibration.index', compact(
            'ratings', 'employees', 'cycles', 'cycleId', 'cycle',
            'departments', 'deptId', 'distribution'
        ));
    }

    /**
     * Bell Curve auto-distribution — 10/20/50/20
     * Sorts employees by manager_rating (the "original") descending and
     * buckets them into Outstanding / Exceeds / Meets / Low. Frozen rows
     * are untouched. Scope is limited to a single cycle (and optionally a
     * single department).
     */
    public function applyBellCurve(Request $request)
    {
        $data = $request->validate([
            'cycle_id' => 'required|exists:performance_cycles,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        // Pull eligible ratings (not frozen, has a manager rating to sort by)
        $query = GrRating::with('employee')
            ->where('cycle_id', $data['cycle_id'])
            ->where('is_frozen', false)
            ->whereNotNull('manager_rating');

        if (!empty($data['department_id'])) {
            $deptId = $data['department_id'];
            $query->whereHas('employee', function ($q) use ($deptId) {
                $q->where('department_id', $deptId);
            });
        }

        $ratings = $query->get()->sortByDesc(function ($r) {
            return (float) $r->manager_rating;
        })->values();

        $total = $ratings->count();
        if ($total === 0) {
            return back()->with('error', __('No ratings to calibrate. Managers must submit ratings first.'));
        }

        // Bucket counts — guaranteed no-overlap, no-miss
        $outstanding = (int) round($total * 0.10);
        $exceeds     = (int) round($total * 0.20);
        $meets       = (int) round($total * 0.50);
        $low         = $total - ($outstanding + $exceeds + $meets);
        // Edge case: if rounding caused low to go negative, rebalance
        if ($low < 0) { $meets += $low; $low = 0; }

        // Bell curve only assigns category + grade. final_rating is kept as
        // the manager's original rating — HR can adjust it manually after.
        $bucketGrade = [
            'Outstanding' => 'A+',
            'Exceeds'     => 'A',
            'Meets'       => 'B',
            'Low'         => 'C',
        ];

        $emp = $this->currentEmployee();
        $calibratorId = $emp ? $emp->id : Auth::id();

        foreach ($ratings as $i => $r) {
            if ($i < $outstanding) {
                $cat = 'Outstanding';
            } elseif ($i < $outstanding + $exceeds) {
                $cat = 'Exceeds';
            } elseif ($i < $outstanding + $exceeds + $meets) {
                $cat = 'Meets';
            } else {
                $cat = 'Low';
            }

            GrRating::where('id', $r->id)->update([
                'calibration_category' => $cat,
                'final_rating' => $r->manager_rating,   // keep original manager rating
                'grade' => $bucketGrade[$cat],
                'is_calibrated' => true,
                'calibrated_by' => $calibratorId,
            ]);
        }

        return back()->with('success', __('Bell Curve applied to :n employees (Outstanding: :o, Exceeds: :e, Meets: :m, Low: :l).', [
            'n' => $total, 'o' => $outstanding, 'e' => $exceeds, 'm' => $meets, 'l' => $low,
        ]));
    }

    public function calibrationUpdate(Request $request)
    {
        $data = $request->validate([
            'ratings' => 'required|array',
            'ratings.*.id' => 'required|exists:gr_ratings,id',
            'ratings.*.final_rating' => 'required|numeric|min:0|max:5',
            'ratings.*.grade' => 'nullable|string|max:20',
            'ratings.*.calibration_notes' => 'nullable|string',
        ]);

        $emp = $this->currentEmployee();
        foreach ($data['ratings'] as $r) {
            GrRating::where('id', $r['id'])->update([
                'final_rating' => $r['final_rating'],
                'grade' => $r['grade'] ?? null,
                'calibration_notes' => $r['calibration_notes'] ?? null,
                'is_calibrated' => true,
                'calibrated_by' => $emp ? $emp->id : Auth::id(),
            ]);
        }

        return back()->with('success', __('Calibration saved.'));
    }

    public function freezeRatings(Request $request)
    {
        $cycleId = $request->input('cycle_id');
        GrRating::where('cycle_id', $cycleId)->where('is_calibrated', true)->update([
            'is_frozen' => true,
            'frozen_at' => now(),
        ]);

        PerformanceCycle::where('id', $cycleId)->update(['status' => 'completed']);
        return back()->with('success', __('All ratings frozen. Cycle completed.'));
    }

    // ════════════════════════════════════════════════════════════
    //  INCREMENTS
    // ════════════════════════════════════════════════════════════

    public function increments(Request $request)
    {
        $cid = $this->creatorId();
        $user = Auth::user();
        $isAdmin = in_array($user->type, ['company', 'super admin'], true);

        // Check if employee is a manager (has team members)
        if (!$isAdmin) {
            $emp = $this->currentEmployee();
            $isManager = $emp && Employee::where('created_by', $cid)
                ->where(function($q) use ($emp) {
                    $q->where('reporting_manager_id', $emp->id)
                      ->orWhere('hod_id', $emp->id)
                      ->orWhere('management_id', $emp->id);
                })->exists();

            if (!$isManager) {
                return redirect()->route('growth-review.dashboard')
                    ->with('error', __('You do not have permission to view increment records.'));
            }
        }

        $cycleId = $request->get('cycle_id');
        $deptId  = $request->get('department_id');
        $cycles = PerformanceCycle::where('created_by', $cid)->orderByDesc('start_date')->get();
        if (!$cycleId && $cycles->isNotEmpty()) $cycleId = $cycles->first()->id;

        $query = GrIncrement::with('employee', 'rating', 'proposer')->where('cycle_id', $cycleId);

        // Manager (employee type) sees only their team's increments
        $viewerRole = 'admin';
        if (!$isAdmin) {
            $emp = $this->currentEmployee();
            if ($emp) {
                $teamIds = Employee::where('created_by', $cid)
                    ->where(function($q) use ($emp) {
                        $q->where('reporting_manager_id', $emp->id)
                          ->orWhere('hod_id', $emp->id)
                          ->orWhere('management_id', $emp->id);
                    })->pluck('id');

                if ($teamIds->isNotEmpty()) {
                    // Check if viewer is management-level or manager-level
                    $isMgmt = Employee::where('created_by', $cid)->where('management_id', $emp->id)->exists();
                    $viewerRole = $isMgmt ? 'management' : 'manager';
                    $query->whereIn('employee_id', $teamIds);
                } else {
                    $viewerRole = 'employee';
                    $query->where('employee_id', $emp->id); // see only own
                }
            }
        }

        if ($deptId && $isAdmin) {
            $empIds = Employee::where('department_id', $deptId)->pluck('id');
            $query->whereIn('employee_id', $empIds);
        }
        $increments = $query->get();
        $cycle = PerformanceCycle::find($cycleId);
        $departments = DB::table('departments')->where('created_by', $cid)->orderBy('name')->get(['id', 'name']);

        // Team employees for manager propose modal (with CTC)
        $teamEmployees = collect();
        if (in_array($viewerRole, ['manager', 'management'])) {
            $emp = $this->currentEmployee();
            if ($emp) {
                $teamEmployees = Employee::where('created_by', $cid)
                    ->where(function($q) use ($emp) {
                        $q->where('reporting_manager_id', $emp->id)
                          ->orWhere('hod_id', $emp->id)
                          ->orWhere('management_id', $emp->id);
                    })->orderBy('name')->get(['id', 'name', 'employee_id']);

                // Attach CTC from employee_salaries
                $ctcMap = DB::table('employee_salaries')->whereIn('employee_id', $teamEmployees->pluck('id'))->pluck('ctc', 'employee_id');
                $teamEmployees->each(function($e) use ($ctcMap) {
                    $e->current_ctc = $ctcMap[$e->id] ?? 0;
                });
            }
        }

        // Salary data for net take-home calculation
        $empIds = $increments->pluck('employee_id')->unique();
        $salaryData = DB::table('employee_salaries')->whereIn('employee_id', $empIds)->get()->keyBy('employee_id');

        // ── Budget summary: Eligible vs Not-Eligible (across full scope) ──
        // "Eligible" = employees in scope who have an increment with amount > 0.
        // "Not Eligible" = everyone else in the same scope (no increment row, or 0 amount).
        $scopeEmpQuery = Employee::where('created_by', $cid);
        if ($deptId && $isAdmin) {
            $scopeEmpQuery->where('department_id', $deptId);
        } elseif (!$isAdmin && isset($teamIds) && $teamIds && $teamIds->isNotEmpty()) {
            $scopeEmpQuery->whereIn('id', $teamIds);
        } elseif (!$isAdmin && $viewerRole === 'employee' && ($emp = $this->currentEmployee())) {
            $scopeEmpQuery->where('id', $emp->id);
        }
        $scopeEmpIds = $scopeEmpQuery->pluck('id');

        $allCtcMap = DB::table('employee_salaries')
            ->whereIn('employee_id', $scopeEmpIds)
            ->pluck('ctc', 'employee_id');

        $eligibleIncrements = $increments->where('increment_amount', '>', 0);
        $eligibleEmpIds = $eligibleIncrements->pluck('employee_id')->unique();

        $budget = [
            'total_headcount'      => $scopeEmpIds->count(),
            'total_ctc'            => (float) $allCtcMap->sum(),
            'eligible_headcount'   => $eligibleEmpIds->count(),
            'eligible_ctc_current' => (float) $allCtcMap->only($eligibleEmpIds->all())->sum(),
            'eligible_ctc_revised' => (float) $eligibleIncrements->sum('new_ctc'),
            'eligible_increment'   => (float) $eligibleIncrements->sum('increment_amount'),
        ];
        $budget['ineligible_headcount'] = $budget['total_headcount'] - $budget['eligible_headcount'];
        $budget['ineligible_ctc']       = $budget['total_ctc'] - $budget['eligible_ctc_current'];
        $budget['eligible_pct']         = $budget['eligible_ctc_current'] > 0
            ? round(($budget['eligible_increment'] / $budget['eligible_ctc_current']) * 100, 2)
            : 0;

        return view('growth_review.increments.index', compact('increments', 'cycles', 'cycleId', 'cycle', 'departments', 'deptId', 'viewerRole', 'teamEmployees', 'salaryData', 'budget'));
    }

    /**
     * Goal Seek: distribute the target total across all increments in scope
     * in proportion to each employee's calibrated rating.
     *
     * Fixed amounts (synced-to-payroll rows) are treated as part of the
     * total — their value is subtracted from the target, and only the
     * remainder is distributed across editable rows.
     *
     * Weight per editable row: final_rating, fallback to manager_rating,
     * fallback to 1.0 (so every editable row gets at least an equal share
     * even if rating data is missing).
     */
    public function incrementsGoalSeek(Request $request)
    {
        $data = $request->validate([
            'cycle_id'      => 'required|exists:performance_cycles,id',
            'department_id' => 'nullable|exists:departments,id',
            'target_amount' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $isAdmin = in_array($user->type, ['company', 'super admin'], true);

        $query = GrIncrement::with('rating')->where('cycle_id', $data['cycle_id']);

        if (!empty($data['department_id']) && $isAdmin) {
            $empIds = Employee::where('department_id', $data['department_id'])->pluck('id');
            $query->whereIn('employee_id', $empIds);
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            return response()->json(['ok' => false, 'error' => __('No increments in scope.')], 422);
        }

        // Split: fixed (synced) vs editable
        $fixedRows    = $rows->where('synced_to_payroll', true);
        $editableRows = $rows->where('synced_to_payroll', false);

        $fixedTotal   = (float) $fixedRows->sum('increment_amount');
        $target       = (float) $data['target_amount'];
        $distributable = $target - $fixedTotal;

        if ($editableRows->isEmpty()) {
            return response()->json([
                'ok'    => false,
                'error' => __('All rows are locked (synced). Total fixed = :t', ['t' => number_format($fixedTotal, 2)]),
            ], 422);
        }

        if ($distributable < 0) {
            return response()->json([
                'ok'    => false,
                'error' => __('Target (:tg) is less than the sum of locked/synced increments (:fx). Increase target or unlock rows.', [
                    'tg' => number_format($target, 2),
                    'fx' => number_format($fixedTotal, 2),
                ]),
            ], 422);
        }

        // Weight per editable row (final_rating > manager_rating > 1.0 fallback)
        $weights = [];
        foreach ($editableRows as $r) {
            $rating = (float) ($r->rating->final_rating ?? 0);
            if ($rating <= 0) $rating = (float) ($r->rating->manager_rating ?? 0);
            if ($rating <= 0) $rating = 1.0;   // equal-share fallback
            $weights[$r->id] = $rating;
        }
        $totalWeight = array_sum($weights);

        $editableById = $editableRows->keyBy('id');
        $ids       = array_keys($weights);
        $lastId    = end($ids);
        $allocated = 0.0;
        $updated   = 0;

        foreach ($weights as $id => $w) {
            // Last row absorbs rounding drift so the total matches exactly
            if ($id === $lastId) {
                $incAmt = round($distributable - $allocated, 2);
                if ($incAmt < 0) $incAmt = 0;
            } else {
                $incAmt = round($distributable * ($w / $totalWeight), 2);
                $allocated += $incAmt;
            }

            $r       = $editableById[$id];
            $oldCtc  = (float) $r->old_ctc;
            $pct     = $oldCtc > 0 ? round($incAmt / $oldCtc * 100, 2) : 0;
            $newCtc  = $oldCtc + $incAmt;

            GrIncrement::where('id', $id)->update([
                'increment_amount' => $incAmt,
                'increment_pct'    => $pct,
                'new_ctc'          => $newCtc,
                // Goal Seek changed the amount → reset to 'proposed' so HR can
                // re-approve the new figure. Synced rows are excluded from this
                // loop entirely (they're filtered into $editableRows above).
                'status'           => 'proposed',
            ]);
            $updated++;
        }

        $finalTotal = (float) GrIncrement::whereIn('id', $rows->pluck('id'))->sum('increment_amount');

        return response()->json([
            'ok'               => true,
            'updated'          => $updated,
            'fixed_count'      => $fixedRows->count(),
            'fixed_total'      => $fixedTotal,
            'distributable'    => $distributable,
            'requested_target' => $target,
            'achieved_total'   => $finalTotal,
            'method'           => 'rating-proportional (locked rows excluded from distribution)',
        ]);
    }

    public function generateIncrements(Request $request)
    {
        $data = $request->validate([
            'cycle_id' => 'required|exists:performance_cycles,id',
            'slabs' => 'required|string', // JSON: [{"grade":"A+","pct":15},...]
            'effective_date' => 'required|date',
        ]);

        $slabs = json_decode($data['slabs'], true);
        if (!is_array($slabs)) return back()->with('error', __('Invalid slabs data.'));

        $slabMap = [];
        foreach ($slabs as $s) { $slabMap[$s['grade']] = (float)$s['pct']; }

        $ratings = GrRating::with('employee')->where('cycle_id', $data['cycle_id'])->where('is_frozen', true)->get();
        $cid = $this->creatorId();
        $count = 0;

        foreach ($ratings as $r) {
            if (!$r->grade || !isset($slabMap[$r->grade])) continue;
            $emp = $r->employee;
            if (!$emp) continue;

            $salary = DB::table('employee_salaries')->where('employee_id', $emp->id)->first();
            if (!$salary) continue;

            $oldCtc = (float)$salary->ctc;
            $pct = $slabMap[$r->grade];
            $incAmt = round($oldCtc * $pct / 100, 2);
            $newCtc = $oldCtc + $incAmt;

            GrIncrement::updateOrCreate(
                ['cycle_id' => $data['cycle_id'], 'employee_id' => $emp->id],
                [
                    'rating_id' => $r->id,
                    'old_ctc' => $oldCtc,
                    'new_ctc' => $newCtc,
                    'increment_pct' => $pct,
                    'increment_amount' => $incAmt,
                    'effective_date' => $data['effective_date'],
                    'status' => 'proposed',
                    'created_by' => $cid,
                ]
            );
            $count++;
        }

        return back()->with('success', __(':count increment proposals generated.', ['count' => $count]));
    }

    public function incrementApprove(Request $request, $id)
    {
        $inc = GrIncrement::with('employee')->findOrFail($id);
        $emp = $this->currentEmployee();
        $action = $request->input('action', 'approved');

        $inc->update([
            'status' => $action,
            'approved_by' => $emp ? $emp->id : Auth::id(),
        ]);

        // Auto-sync to payroll on approve
        if ($action === 'approved' && !$inc->synced_to_payroll) {
            DB::table('employee_salaries')->where('employee_id', $inc->employee_id)->update(['ctc' => $inc->new_ctc]);

            DB::table('salary_increment_history')->insert([
                'employee_id'          => $inc->employee_id,
                'old_ctc'              => $inc->old_ctc,
                'new_ctc'              => $inc->new_ctc,
                'increment_amount'     => $inc->increment_amount,
                'increment_percentage' => $inc->increment_pct,
                'effective_date'       => $inc->effective_date,
                'remarks'              => 'Performance Cycle Increment - ' . ($inc->cycle->name ?? ''),
                'created_by'           => $this->creatorId(),
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            $inc->update(['synced_to_payroll' => true, 'status' => 'applied']);

            return back()->with('success', __('Increment approved & synced to payroll for :name.', ['name' => $inc->employee->name ?? '']));
        }

        return back()->with('success', __('Increment :action.', ['action' => $inc->status]));
    }

    public function storeProposal(Request $request)
    {
        $data = $request->validate([
            'cycle_id'         => 'required|exists:performance_cycles,id',
            'employee_id'      => 'required|exists:employees,id',
            'increment_amount' => 'required|numeric|min:1',
            'effective_date'   => 'required|date',
            'remarks'          => 'nullable|string|max:500',
        ]);

        $emp = $this->currentEmployee();

        // Use original old_ctc from existing increment if present, otherwise from salary_increment_history joining record
        $existing = GrIncrement::where('cycle_id', $data['cycle_id'])->where('employee_id', $data['employee_id'])->first();
        if ($existing) {
            $oldCtc = (float) $existing->old_ctc;
        } else {
            // Get the base CTC before any growth-review increments (first record / joining CTC)
            $firstRecord = DB::table('salary_increment_history')
                ->where('employee_id', $data['employee_id'])
                ->orderBy('id', 'asc')
                ->first();
            if ($firstRecord && $firstRecord->new_ctc > 0) {
                $oldCtc = (float) $firstRecord->new_ctc;
            } else {
                $salary = DB::table('employee_salaries')->where('employee_id', $data['employee_id'])->first();
                $oldCtc = $salary ? (float) $salary->ctc : 0;
            }
        }

        $incAmt = round((float) $data['increment_amount'], 2);
        $pct = $oldCtc > 0 ? round($incAmt / $oldCtc * 100, 2) : 0;
        $newCtc = $oldCtc + $incAmt;

        GrIncrement::updateOrCreate(
            ['cycle_id' => $data['cycle_id'], 'employee_id' => $data['employee_id']],
            [
                'old_ctc'          => $oldCtc,
                'new_ctc'          => $newCtc,
                'increment_pct'    => $pct,
                'increment_amount' => $incAmt,
                'effective_date'   => $data['effective_date'],
                'status'           => 'manager_proposed',
                'proposed_by'      => $emp ? $emp->id : null,
                'proposed_at'      => now(),
                'remarks'          => $data['remarks'],
                'created_by'       => $this->creatorId(),
                'synced_to_payroll' => false,
            ]
        );

        return back()->with('success', __('Increment proposal created and sent to Management.'));
    }

    public function incrementPropose(Request $request, $id)
    {
        $inc = GrIncrement::with('employee')->findOrFail($id);
        $emp = $this->currentEmployee();

        $inc->update([
            'status'      => 'manager_proposed',
            'proposed_by' => $emp ? $emp->id : null,
            'proposed_at' => now(),
        ]);

        return back()->with('success', __('Increment proposal sent to Management for :name.', ['name' => $inc->employee->name ?? '']));
    }

    public function incrementUpdate(Request $request, $id)
    {
        $inc = GrIncrement::findOrFail($id);
        if ($inc->synced_to_payroll) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'error' => 'Already synced to payroll.'], 423);
            }
            return back()->with('error', __('Cannot edit — already synced to payroll.'));
        }

        $data = $request->validate([
            'increment_pct'    => 'nullable|numeric|min:0|max:100',
            'increment_amount' => 'nullable|numeric|min:0',
            'effective_date'   => 'required|date',
            'status'           => 'required|in:proposed,manager_proposed,approved,rejected',
            'remarks'          => 'nullable|string|max:500',
        ]);

        $oldCtc = (float) $inc->old_ctc;

        if ($request->has('increment_amount') && !$request->has('increment_pct')) {
            // Amount edited directly → recalculate pct
            $incAmt = round((float) $data['increment_amount'], 2);
            $pct    = $oldCtc > 0 ? round($incAmt / $oldCtc * 100, 2) : 0;
        } else {
            // Pct edited → recalculate amount
            $pct    = (float) ($data['increment_pct'] ?? $inc->increment_pct);
            $incAmt = round($oldCtc * $pct / 100, 2);
        }
        $newCtc = $oldCtc + $incAmt;

        $user = Auth::user();
        $isAdmin = in_array($user->type, ['company', 'super admin'], true);
        $updateData = [
            'increment_pct'    => $pct,
            'increment_amount' => $incAmt,
            'new_ctc'          => $newCtc,
            'effective_date'   => $data['effective_date'],
            'remarks'          => $data['remarks'],
        ];

        // If manager (non-admin) edits, set status to manager_proposed
        if (!$isAdmin) {
            $emp = $this->currentEmployee();
            $updateData['status'] = 'manager_proposed';
            $updateData['proposed_by'] = $emp ? $emp->id : null;
            $updateData['proposed_at'] = now();
        } else {
            $updateData['status'] = $data['status'];
        }

        $inc->update($updateData);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'pct' => $pct, 'amount' => $incAmt, 'new_ctc' => $newCtc, 'remarks' => $inc->remarks]);
        }
        return back()->with('success', __('Increment updated for :name.', ['name' => $inc->employee->name ?? '']));
    }

    public function incrementSyncPayroll(Request $request, $id)
    {
        $inc = GrIncrement::with('employee')->findOrFail($id);
        if ($inc->status !== 'approved') return back()->with('error', __('Increment must be approved first.'));

        // Apply to employee salary
        DB::table('employee_salaries')->where('employee_id', $inc->employee_id)->update(['ctc' => $inc->new_ctc]);

        // Record in salary_increment_history
        DB::table('salary_increment_history')->insert([
            'employee_id' => $inc->employee_id,
            'old_ctc' => $inc->old_ctc,
            'new_ctc' => $inc->new_ctc,
            'increment_pct' => $inc->increment_pct,
            'effective_date' => $inc->effective_date,
            'reason' => 'Performance Cycle Increment - ' . ($inc->cycle->name ?? ''),
            'created_by' => $this->creatorId(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $inc->update(['synced_to_payroll' => true, 'status' => 'applied']);
        return back()->with('success', __('Increment synced to payroll for :name.', ['name' => $inc->employee->name ?? '']));
    }

    public function incrementsExport(Request $request)
    {
        $cid = $this->creatorId();
        $cycleId = $request->get('cycle_id');
        $deptId = $request->get('department_id');

        $query = GrIncrement::with('employee', 'rating', 'cycle')->where('cycle_id', $cycleId);
        if ($deptId) {
            $empIds = Employee::where('department_id', $deptId)->pluck('id');
            $query->whereIn('employee_id', $empIds);
        }
        $increments = $query->get();
        $salaryData = DB::table('employee_salaries')->whereIn('employee_id', $increments->pluck('employee_id'))->get()->keyBy('employee_id');

        $cycleName = $increments->first()?->cycle?->name ?? 'increments';
        $filename = 'increments-' . \Str::slug($cycleName) . '-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        // Pre-load departments and reporting managers
        $deptMap = DB::table('departments')->pluck('name', 'id');
        $empMap = Employee::whereIn('id', $increments->pluck('employee_id'))->get()->keyBy('id');
        $mgrIds = $empMap->pluck('reporting_manager_id')->filter()->unique();
        $mgrNames = Employee::whereIn('id', $mgrIds)->pluck('name', 'id');

        $callback = function () use ($increments, $salaryData, $deptMap, $empMap, $mgrNames) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($f, ['Employee', 'Employee ID', 'DOJ', 'Department', 'Reporting Manager', 'Self Rating', 'Manager Rating', 'HOD Rating', 'Final Rating', 'Old CTC', 'Inc %', 'Inc Amount', 'New CTC', 'Old Net Monthly', 'New Net Monthly', 'Diff/Month', 'Effective Date', 'Status', 'Purpose']);

            foreach ($increments as $inc) {
                $empRecord = $empMap[$inc->employee_id] ?? null;
                $sal = $salaryData[$inc->employee_id] ?? null;
                $basicPct = $sal->basic_percentage ?? 50;
                $oldGross = round($inc->old_ctc / 12);
                $newGross = round($inc->new_ctc / 12);
                $oldBasic = round($oldGross * $basicPct / 100);
                $newBasic = round($newGross * $basicPct / 100);
                $oldPf = ($sal && $sal->is_pf_enabled) ? min(round($oldBasic * 0.12), 1800) : 0;
                $newPf = ($sal && $sal->is_pf_enabled) ? min(round($newBasic * 0.12), 1800) : 0;
                $oldEsic = ($sal && $sal->is_esic_enabled && $oldGross <= 21000) ? round($oldGross * 0.0075) : 0;
                $newEsic = ($sal && $sal->is_esic_enabled && $newGross <= 21000) ? round($newGross * 0.0075) : 0;
                $oldNet = $oldGross - $oldPf - $oldEsic;
                $newNet = $newGross - $newPf - $newEsic;

                fputcsv($f, [
                    $inc->employee->name ?? '—',
                    $inc->employee->employee_id ?? '',
                    $empRecord->company_doj ?? '—',
                    $deptMap[$empRecord->department_id ?? 0] ?? '—',
                    $mgrNames[$empRecord->reporting_manager_id ?? 0] ?? '—',
                    $inc->rating->self_rating ?? '—',
                    $inc->rating->manager_rating ?? '—',
                    $inc->rating->head_rating ?? '—',
                    $inc->rating->final_rating ?? '—',
                    $inc->old_ctc,
                    $inc->increment_pct . '%',
                    $inc->increment_amount,
                    $inc->new_ctc,
                    $oldNet,
                    $newNet,
                    $newNet - $oldNet,
                    $inc->effective_date->format('d-m-Y'),
                    ucfirst($inc->status),
                    $inc->remarks ?? '',
                ]);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function incrementLetter($id)
    {
        $inc = GrIncrement::with('employee', 'cycle')->findOrFail($id);
        $emp = $inc->employee;
        $salary = DB::table('employee_salaries')->where('employee_id', $emp->id)->first();
        $settings = \App\Models\Utility::settings();
        $companyName = $settings['company_name'] ?? 'Company';

        // Calculate net take-home (approx)
        $basicPct = $salary->basic_percentage ?? 50;
        $oldBasic = round($inc->old_ctc * $basicPct / 100 / 12, 2);
        $newBasic = round($inc->new_ctc * $basicPct / 100 / 12, 2);

        $data = [
            'inc' => $inc,
            'emp' => $emp,
            'companyName' => $companyName,
            'oldMonthly' => round($inc->old_ctc / 12),
            'newMonthly' => round($inc->new_ctc / 12),
            'diffMonthly' => round($inc->increment_amount / 12),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('growth_review.increments.letter_pdf', $data);
        $filename = 'increment-letter-' . \Str::slug($emp->name) . '-' . now()->format('Y') . '.pdf';
        return $pdf->download($filename);
    }
}
