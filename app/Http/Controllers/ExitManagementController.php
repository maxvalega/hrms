<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ExitChecklistItem;
use App\Models\ExitResignation;
use App\Models\FnfSettlement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Exit Management Module.
 *
 * Workflow:
 *   Employee → apply resignation
 *   Manager  → approve / reject
 *   HR       → final approve / reject (also auto-seeds default checklist)
 *   HR       → tick checklist items + record FNF
 *   HR       → mark complete (only when checklist done + FNF finalised)
 *
 * Role gates:
 *   - apply-resignation       : everyone
 *   - manager-approve-exit    : line managers (their direct reports only)
 *   - manage-exits            : HR / admin (full visibility within the tenant)
 */
class ExitManagementController extends Controller
{
    /* ──────────────────────────────────────────────────────────────
     * Listing — HR sees all; managers see direct reports; everyone
     * sees their own resignation in the same list.
     * ──────────────────────────────────────────────────────────── */
    public function index(Request $request)
    {
        $user      = $this->mustAuth();
        $creatorId = $user->creatorId();
        $isHr      = $user->can('manage-exits');
        $isMgr     = $user->can('manager-approve-exit');

        $q      = trim((string) $request->input('q', ''));
        $status = $request->input('status', 'all');

        $query = ExitResignation::with(['user', 'manager', 'hr', 'fnf'])
            ->where('created_by', $creatorId)
            ->orderByDesc('id');

        // Visibility: HR sees everything; manager sees own + their reports;
        // employees see own only.
        if (!$isHr) {
            $visibleUserIds = collect([$user->id]);
            if ($isMgr) {
                $myEmp = Employee::where('user_id', $user->id)->first();
                if ($myEmp) {
                    $teamUserIds = Employee::whereIn('id', $myEmp->teamMemberIds())
                        ->pluck('user_id')
                        ->filter();
                    $visibleUserIds = $visibleUserIds->merge($teamUserIds);
                }
            }
            $query->whereIn('user_id', $visibleUserIds->unique());
        }

        if ($q !== '') {
            $query->whereHas('user', fn ($u) => $u->where('name', 'like', '%' . $q . '%')
                                                  ->orWhere('email', 'like', '%' . $q . '%'));
        }
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $items = $query->paginate(15)->withQueryString();

        $base = ExitResignation::where('created_by', $creatorId);
        if (!$isHr) {
            $visibleUserIds = collect([$user->id]);
            if ($isMgr) {
                $myEmp = Employee::where('user_id', $user->id)->first();
                if ($myEmp) {
                    $visibleUserIds = $visibleUserIds->merge(
                        Employee::whereIn('id', $myEmp->teamMemberIds())->pluck('user_id')->filter()
                    );
                }
            }
            $base->whereIn('user_id', $visibleUserIds->unique());
        }

        $totals = [
            'all'      => (clone $base)->count(),
            'pending'  => (clone $base)->whereIn('status', ['pending', 'manager_approved'])->count(),
            'approved' => (clone $base)->where('status', 'hr_approved')->count(),
            'closed'   => (clone $base)->whereIn('status', ['completed', 'manager_rejected', 'hr_rejected'])->count(),
        ];

        // Does the current user already have an open resignation?
        $myActive = ExitResignation::where('user_id', $user->id)
            ->whereNotIn('status', ['completed', 'manager_rejected', 'hr_rejected'])
            ->first();

        return view('exit_management.index', [
            'items'    => $items,
            'totals'   => $totals,
            'filters'  => compact('q', 'status'),
            'isHr'     => $isHr,
            'isMgr'    => $isMgr,
            'myActive' => $myActive,
        ]);
    }

    /* ──────────────────────────────────────────────────────────────
     * Apply resignation (employee)
     * ──────────────────────────────────────────────────────────── */
    public function create()
    {
        $user = $this->mustAuth();
        if (!$user->can('apply-resignation')) {
            abort(403, __('You cannot apply for resignation.'));
        }

        // Cannot resign twice while one is open
        $open = ExitResignation::where('user_id', $user->id)
            ->whereNotIn('status', ['completed', 'manager_rejected', 'hr_rejected'])
            ->first();
        if ($open) {
            return redirect()->route('exit-management.show', $open->id)
                ->with('info', __('You already have a resignation in progress.'));
        }

        $employee = Employee::where('user_id', $user->id)->first();
        return view('exit_management.create', compact('employee'));
    }

    public function store(Request $request)
    {
        $user = $this->mustAuth();
        if (!$user->can('apply-resignation')) {
            abort(403);
        }

        // Block second open resignation
        $open = ExitResignation::where('user_id', $user->id)
            ->whereNotIn('status', ['completed', 'manager_rejected', 'hr_rejected'])
            ->first();
        if ($open) {
            return redirect()->route('exit-management.show', $open->id)
                ->with('error', __('You already have a resignation in progress.'));
        }

        $data = $request->validate([
            'reason'             => 'required|string|max:2000',
            'resignation_date'   => 'required|date',
            'last_working_day'   => 'required|date|after_or_equal:resignation_date',
        ]);

        $resDate = Carbon::parse($data['resignation_date']);
        $lwd     = Carbon::parse($data['last_working_day']);

        // Resolve the manager: try Employee.reporting_manager_id first.
        $employee  = Employee::where('user_id', $user->id)->first();
        $managerId = null;
        if ($employee && $employee->reporting_manager_id) {
            $managerEmp = Employee::find($employee->reporting_manager_id);
            $managerId  = $managerEmp?->user_id;
        }

        $r = ExitResignation::create([
            'user_id'             => $user->id,
            'created_by'          => $user->creatorId(),
            'reason'              => $data['reason'],
            'resignation_date'    => $resDate->toDateString(),
            'last_working_day'    => $lwd->toDateString(),
            'notice_period_days'  => $resDate->diffInDays($lwd),
            'status'              => 'pending',
            'manager_id'          => $managerId,
        ]);

        if ($managerId) {
            \App\Services\InAppNotifier::notifyUser($managerId, [
                'module' => 'exit',
                'action' => 'created',
                'title' => __('Resignation Submitted'),
                'message' => $user->name . ' — ' . $lwd->toDateString(),
                'link' => route('exit-management.show', $r->id),
            ]);
        }
        \App\Services\InAppNotifier::notifyCompanyHr($user->creatorId(), [
            'module' => 'exit',
            'action' => 'created',
            'title' => __('Resignation Submitted'),
            'message' => $user->name . ' — ' . $lwd->toDateString(),
            'link' => route('exit-management.show', $r->id),
        ]);

        return redirect()->route('exit-management.show', $r->id)
            ->with('success', __('Resignation submitted. Awaiting manager approval.'));
    }

    /* ──────────────────────────────────────────────────────────────
     * View / timeline
     * ──────────────────────────────────────────────────────────── */
    public function show(int $id)
    {
        $user = $this->mustAuth();
        $r    = $this->findOrFail($id);
        $this->ensureCanView($r);

        $r->load(['user', 'manager', 'hr', 'checklist', 'fnf']);

        return view('exit_management.show', [
            'r'          => $r,
            'isHr'       => $user->can('manage-exits'),
            'isMgr'      => $user->can('manager-approve-exit'),
            'isOwner'    => $r->user_id === $user->id,
            'canMgrAct'  => $this->canManagerAct($user, $r),
        ]);
    }

    /* ──────────────────────────────────────────────────────────────
     * Manager approve / reject
     * ──────────────────────────────────────────────────────────── */
    public function managerApprove(Request $request, int $id)
    {
        $user = $this->mustAuth();
        $r    = $this->findOrFail($id);

        if (!$this->canManagerAct($user, $r) || $r->status !== 'pending') {
            abort(403, __('You cannot act on this resignation.'));
        }

        $note = (string) $request->input('note', '');
        $r->update([
            'status'            => 'manager_approved',
            'manager_id'        => $user->id,
            'manager_action_at' => now(),
            'manager_note'      => $note ?: null,
        ]);

        return back()->with('success', __('Approved. Forwarded to HR.'));
    }

    public function managerReject(Request $request, int $id)
    {
        $user = $this->mustAuth();
        $r    = $this->findOrFail($id);

        if (!$this->canManagerAct($user, $r) || $r->status !== 'pending') {
            abort(403);
        }

        $data = $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $r->update([
            'status'            => 'manager_rejected',
            'manager_id'        => $user->id,
            'manager_action_at' => now(),
            'manager_note'      => $data['note'],
        ]);

        return back()->with('info', __('Resignation rejected.'));
    }

    /* ──────────────────────────────────────────────────────────────
     * HR approve / reject — also seeds the default checklist on approve.
     * ──────────────────────────────────────────────────────────── */
    public function hrApprove(Request $request, int $id)
    {
        $user = $this->ensureHr();
        $r    = $this->findOrFail($id);

        if ($r->status !== 'manager_approved') {
            return back()->with('error', __('Resignation must be manager-approved first.'));
        }

        DB::transaction(function () use ($r, $user, $request) {
            $r->update([
                'status'        => 'hr_approved',
                'hr_id'         => $user->id,
                'hr_action_at'  => now(),
                'hr_note'       => (string) $request->input('note') ?: null,
            ]);

            // Seed default checklist (only if empty)
            if ($r->checklist()->count() === 0) {
                foreach (ExitResignation::DEFAULT_CHECKLIST as $name) {
                    ExitChecklistItem::create([
                        'resignation_id' => $r->id,
                        'user_id'        => $r->user_id,
                        'item_name'      => $name,
                        'status'         => 'pending',
                    ]);
                }
            }

            // Seed an empty FNF draft
            FnfSettlement::firstOrCreate(
                ['resignation_id' => $r->id],
                ['user_id' => $r->user_id, 'status' => 'draft']
            );
        });

        return back()->with('success', __('HR approval recorded. Checklist & FNF draft initialised.'));
    }

    public function hrReject(Request $request, int $id)
    {
        $user = $this->ensureHr();
        $r    = $this->findOrFail($id);

        if (!in_array($r->status, ['pending', 'manager_approved'], true)) {
            return back()->with('error', __('Resignation cannot be rejected at this stage.'));
        }

        $data = $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $r->update([
            'status'        => 'hr_rejected',
            'hr_id'         => $user->id,
            'hr_action_at'  => now(),
            'hr_note'       => $data['note'],
        ]);

        return back()->with('info', __('Resignation rejected by HR.'));
    }

    /* ──────────────────────────────────────────────────────────────
     * Checklist actions (HR)
     * ──────────────────────────────────────────────────────────── */
    public function checklistToggle(Request $request, int $id, int $itemId)
    {
        $user = $this->ensureHr();
        $r    = $this->findOrFail($id);

        if ($r->status !== 'hr_approved') {
            return back()->with('error', __('Checklist can only be updated after HR approval.'));
        }

        $item = ExitChecklistItem::where('resignation_id', $r->id)->findOrFail($itemId);

        if ($item->status === 'pending') {
            $item->update([
                'status'       => 'completed',
                'completed_at' => now(),
                'completed_by' => $user->id,
            ]);
        } else {
            $item->update([
                'status'       => 'pending',
                'completed_at' => null,
                'completed_by' => null,
            ]);
        }

        return back()->with('success', __('Checklist updated.'));
    }

    public function checklistAdd(Request $request, int $id)
    {
        $this->ensureHr();
        $r = $this->findOrFail($id);

        if ($r->status !== 'hr_approved') {
            return back()->with('error', __('Checklist can only be edited after HR approval.'));
        }

        $data = $request->validate([
            'item_name' => 'required|string|max:200',
        ]);

        ExitChecklistItem::create([
            'resignation_id' => $r->id,
            'user_id'        => $r->user_id,
            'item_name'      => $data['item_name'],
            'status'         => 'pending',
        ]);

        return back()->with('success', __('Checklist item added.'));
    }

    public function checklistDelete(int $id, int $itemId)
    {
        $this->ensureHr();
        $r    = $this->findOrFail($id);
        $item = ExitChecklistItem::where('resignation_id', $r->id)->findOrFail($itemId);
        $item->delete();
        return back()->with('success', __('Checklist item removed.'));
    }

    /* ──────────────────────────────────────────────────────────────
     * FNF (HR)
     * ──────────────────────────────────────────────────────────── */
    public function fnfSave(Request $request, int $id)
    {
        $user = $this->ensureHr();
        $r    = $this->findOrFail($id);

        if ($r->status !== 'hr_approved') {
            return back()->with('error', __('FNF is only editable after HR approval.'));
        }

        $data = $request->validate([
            'pending_salary'   => 'nullable|numeric|min:0',
            'leave_encashment' => 'nullable|numeric|min:0',
            'gratuity'         => 'nullable|numeric|min:0',
            'bonus'            => 'nullable|numeric|min:0',
            'other_earnings'   => 'nullable|numeric|min:0',
            'notice_recovery'  => 'nullable|numeric|min:0',
            'asset_recovery'   => 'nullable|numeric|min:0',
            'tax_deduction'    => 'nullable|numeric|min:0',
            'other_deductions' => 'nullable|numeric|min:0',
            'remarks'          => 'nullable|string|max:1000',
            'action'           => 'required|in:save,finalise,paid',
        ]);

        $fnf = FnfSettlement::firstOrNew(['resignation_id' => $r->id]);
        $fnf->user_id          = $r->user_id;
        $fnf->pending_salary   = $data['pending_salary']   ?? 0;
        $fnf->leave_encashment = $data['leave_encashment'] ?? 0;
        $fnf->gratuity         = $data['gratuity']         ?? 0;
        $fnf->bonus            = $data['bonus']            ?? 0;
        $fnf->other_earnings   = $data['other_earnings']   ?? 0;
        $fnf->notice_recovery  = $data['notice_recovery']  ?? 0;
        $fnf->asset_recovery   = $data['asset_recovery']   ?? 0;
        $fnf->tax_deduction    = $data['tax_deduction']    ?? 0;
        $fnf->other_deductions = $data['other_deductions'] ?? 0;
        $fnf->remarks          = $data['remarks']          ?? null;
        $fnf->processed_by     = $user->id;
        $fnf->recompute();

        if ($data['action'] === 'finalise') {
            $fnf->status = 'finalised';
        } elseif ($data['action'] === 'paid') {
            $fnf->status  = 'paid';
            $fnf->paid_on = now()->toDateString();
        }

        $fnf->save();

        return back()->with('success', __('Settlement saved.'));
    }

    /* ──────────────────────────────────────────────────────────────
     * Mark complete — gated on checklist + finalised FNF.
     * ──────────────────────────────────────────────────────────── */
    public function complete(int $id)
    {
        $this->ensureHr();
        $r = $this->findOrFail($id);
        $r->load(['checklist', 'fnf']);

        if ($r->status !== 'hr_approved') {
            return back()->with('error', __('Only HR-approved exits can be completed.'));
        }
        if (!$r->checklistComplete()) {
            return back()->with('error', __('Complete every checklist item first.'));
        }
        if (!$r->fnf || !in_array($r->fnf->status, ['finalised', 'paid'], true)) {
            return back()->with('error', __('Finalise the FNF settlement first.'));
        }

        $r->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return back()->with('success', __('Exit marked complete.'));
    }

    /* ──────────────────────────────────────────────────────────────
     * Delete (HR only) — hard-delete; checklist + FNF cascade via FK.
     * ──────────────────────────────────────────────────────────── */
    public function destroy(int $id)
    {
        $this->ensureHr();
        $r = $this->findOrFail($id);
        $r->delete();
        return redirect()->route('exit-management.index')->with('success', __('Resignation removed.'));
    }

    /* ──────────────────────────────────────────────────────────────
     * Helpers
     * ──────────────────────────────────────────────────────────── */
    protected function findOrFail(int $id): ExitResignation
    {
        $user = Auth::user();
        return ExitResignation::where('created_by', $user->creatorId())->findOrFail($id);
    }

    protected function ensureCanView(ExitResignation $r): void
    {
        $user = Auth::user();
        if ($user->can('manage-exits')) return;
        if ($r->user_id === $user->id) return;
        if ($this->canManagerAct($user, $r, /*ignoreStatus*/ true)) return;
        abort(403, __('You do not have permission to view this resignation.'));
    }

    /**
     * True if $user is the line manager of the resignation's owner — and
     * (unless $ignoreStatus) the resignation is still pending manager review.
     */
    protected function canManagerAct(User $user, ExitResignation $r, bool $ignoreStatus = false): bool
    {
        if (!$user->can('manager-approve-exit')) return false;

        $myEmp = Employee::where('user_id', $user->id)->first();
        if (!$myEmp) return false;

        $targetEmp = Employee::where('user_id', $r->user_id)->first();
        if (!$targetEmp) return false;

        $isMgr = ($targetEmp->reporting_manager_id === $myEmp->id)
            || ($targetEmp->hod_id === $myEmp->id)
            || ($targetEmp->management_id === $myEmp->id);

        if (!$isMgr) return false;
        if ($ignoreStatus) return true;
        return $r->status === 'pending';
    }

    protected function ensureHr(): User
    {
        $user = $this->mustAuth();
        if (!$user->can('manage-exits')) {
            abort(403, __('Only HR can perform this action.'));
        }
        return $user;
    }

    protected function mustAuth(): User
    {
        $user = Auth::user();
        if (!$user) abort(401);
        return $user;
    }
}
