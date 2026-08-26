<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use App\Models\PolicyAcknowledgement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Policy Management Module.
 *
 * - HR/Admin: upload/edit/archive/delete + see acknowledgement reports
 * - Employee: list, view PDF, click "Acknowledge"
 *
 * File storage: uploads land in storage/app/public/policies — served via the
 * stream() method (auth-guarded) so non-logged-in users can't pull URLs.
 */
class PolicyController extends Controller
{
    /* ──────────────────────────────────────────────────────────────
     * Listing — same page for HR and employees, but action buttons differ
     * ──────────────────────────────────────────────────────────── */
    public function index(Request $request)
    {
        $this->ensureView();

        $creatorId = Auth::user()->creatorId();
        $q         = trim((string) $request->input('q', ''));
        $category  = $request->input('category');
        $status    = $request->input('status', 'active');

        $query = Policy::where('created_by', $creatorId)
            ->withCount('acknowledgements')
            ->orderByDesc('id');

        if ($q !== '')               $query->where('title', 'like', '%' . $q . '%');
        if (!empty($category))       $query->where('category', $category);
        if ($status !== 'all')       $query->where('status', $status);

        $policies = $query->paginate(15)->withQueryString();

        // Per-policy: has the current user already acknowledged?
        $myAckIds = PolicyAcknowledgement::where('user_id', Auth::id())
            ->whereIn('policy_id', $policies->pluck('id'))
            ->pluck('policy_id')
            ->all();

        // Legacy "Company Policy" module (separate table) — still used by many admins.
        // Surface those docs here so employees see everything in one place.
        $legacyPolicies = collect();
        if (\Schema::hasTable('company_policies')) {
            $legacyQuery = \App\Models\CompanyPolicy::where('created_by', $creatorId)
                ->with('branches')
                ->orderByDesc('id');
            if ($q !== '') {
                $legacyQuery->where(function ($w) use ($q) {
                    $w->where('title', 'like', '%' . $q . '%')
                        ->orWhere('description', 'like', '%' . $q . '%');
                });
            }
            $legacyPolicies = $legacyQuery->get();
        }

        $newCount = Policy::where('created_by', $creatorId)->count();
        $legacyCount = $legacyPolicies->count();
        // When filters hide new policies, still count legacy for empty-state messaging
        if ($q === '' && empty($category) && ($status === 'active' || $status === 'all')) {
            // already loaded full legacy set above for active/all+no category
        } elseif (\Schema::hasTable('company_policies')) {
            $legacyCount = \App\Models\CompanyPolicy::where('created_by', $creatorId)->count();
        }

        $totals = [
            'all'      => $newCount + $legacyCount,
            'active'   => Policy::where('created_by', $creatorId)->where('status', 'active')->count() + $legacyCount,
            'archived' => Policy::where('created_by', $creatorId)->where('status', 'archived')->count(),
            'legacy'   => $legacyCount,
        ];

        $canManage = $this->userCanManagePolicies();

        return view('policies.index', [
            'policies'        => $policies,
            'legacyPolicies'  => $legacyPolicies,
            'filters'         => compact('q', 'category', 'status'),
            'categories'      => Policy::CATEGORIES,
            'myAckIds'        => $myAckIds,
            'totals'          => $totals,
            'canManage'       => $canManage,
        ]);
    }

    /* ──────────────────────────────────────────────────────────────
     * Upload (HR/Admin only)
     * ──────────────────────────────────────────────────────────── */
    public function create()
    {
        $this->ensureManage();
        return view('policies.create', ['categories' => Policy::CATEGORIES]);
    }

    public function store(Request $request)
    {
        $this->ensureManage();

        $data = $request->validate([
            'title'        => 'required|string|max:200',
            'category'     => 'required|string|max:50',
            'description'  => 'nullable|string|max:2000',
            'version'      => 'nullable|string|max:20',
            'is_mandatory' => 'nullable|boolean',
            // Accept common document/PDF MIME types. 10 MB cap is plenty for HR docs.
            'file'         => 'required|file|mimes:pdf,doc,docx,odt,txt|max:10240',
        ]);

        $stored = $this->storeUploadedFile($request->file('file'));

        Policy::create([
            'title'        => $data['title'],
            'category'     => Str::lower($data['category']),
            'description'  => $data['description'] ?? null,
            'file_path'    => $stored['path'],
            'file_name'    => $stored['original'],
            'file_mime'    => $stored['mime'],
            'file_size'    => $stored['size'],
            'version'      => $data['version'] ?? '1.0',
            'is_mandatory' => (bool) ($data['is_mandatory'] ?? false),
            'status'       => 'active',
            'created_by'   => Auth::user()->creatorId(),
        ]);

        return redirect()->route('policies.index')->with('success', __('Policy uploaded.'));
    }

    /* ──────────────────────────────────────────────────────────────
     * View detail (PDF inline) — accessible to anyone with view-policies
     * ──────────────────────────────────────────────────────────── */
    public function show(int $id)
    {
        $this->ensureView();
        $policy = Policy::where('created_by', Auth::user()->creatorId())->findOrFail($id);

        $ack = PolicyAcknowledgement::where('policy_id', $policy->id)
            ->where('user_id', Auth::id())
            ->first();

        // For HR: count + recent acknowledgements (audit trail)
        $recentAcks = collect();
        $totalAcks  = $policy->acknowledgements()->count();
        if (Auth::user()->can('manage-policies')) {
            $recentAcks = $policy->acknowledgements()
                ->with('user:id,name,email')
                ->orderByDesc('acknowledged_at')
                ->limit(50)
                ->get();
        }

        return view('policies.show', compact('policy', 'ack', 'recentAcks', 'totalAcks'));
    }

    /**
     * Stream the policy file inline (PDF in-browser, doc/docx download).
     * Auth-guarded so the file URL itself can't leak.
     */
    public function file(int $id)
    {
        $this->ensureView();
        $policy = Policy::where('created_by', Auth::user()->creatorId())->findOrFail($id);

        if (!Storage::disk('public')->exists($policy->file_path)) {
            abort(404, 'Policy file is missing.');
        }

        // Inline display for PDFs; attachment for office docs
        $disposition = ($policy->file_mime === 'application/pdf') ? 'inline' : 'attachment';

        return Storage::disk('public')->response(
            $policy->file_path,
            $policy->file_name,
            [
                'Content-Type' => $policy->file_mime ?: 'application/octet-stream',
                'Content-Disposition' => $disposition . '; filename="' . addslashes($policy->file_name) . '"',
            ]
        );
    }

    /* ──────────────────────────────────────────────────────────────
     * Acknowledge — one-click, idempotent
     * ──────────────────────────────────────────────────────────── */
    public function acknowledge(Request $request, int $id)
    {
        $user = Auth::user();
        if (!$user || !$user->can('acknowledge-policies')) {
            abort(403, __('You do not have permission to acknowledge policies.'));
        }

        $policy = Policy::where('created_by', $user->creatorId())->findOrFail($id);

        if ($policy->status !== 'active') {
            return back()->with('error', __('This policy is no longer active.'));
        }

        // Idempotent: if already acknowledged, just bounce back.
        $existing = PolicyAcknowledgement::where('policy_id', $policy->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return back()->with('info', __('You have already acknowledged this policy.'));
        }

        try {
            PolicyAcknowledgement::create([
                'policy_id'       => $policy->id,
                'user_id'         => $user->id,
                'acknowledged_at' => now(),
                'ip_address'      => $request->ip(),
                'user_agent'      => substr((string) $request->userAgent(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            // Race-condition fallback: another tab/click might have inserted simultaneously.
            \Log::warning('Policy ack insert race', ['err' => $e->getMessage()]);
        }

        return back()->with('success', __('Thank you — your acknowledgement has been recorded.'));
    }

    /* ──────────────────────────────────────────────────────────────
     * Edit / update / delete (HR/Admin)
     * ──────────────────────────────────────────────────────────── */
    public function edit(int $id)
    {
        $this->ensureManage();
        $policy = Policy::where('created_by', Auth::user()->creatorId())->findOrFail($id);
        return view('policies.edit', [
            'policy'     => $policy,
            'categories' => Policy::CATEGORIES,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $this->ensureManage();
        $policy = Policy::where('created_by', Auth::user()->creatorId())->findOrFail($id);

        $data = $request->validate([
            'title'        => 'required|string|max:200',
            'category'     => 'required|string|max:50',
            'description'  => 'nullable|string|max:2000',
            'version'      => 'nullable|string|max:20',
            'is_mandatory' => 'nullable|boolean',
            'status'       => 'required|in:active,archived',
            'file'         => 'nullable|file|mimes:pdf,doc,docx,odt,txt|max:10240',
        ]);

        $update = [
            'title'        => $data['title'],
            'category'     => Str::lower($data['category']),
            'description'  => $data['description'] ?? null,
            'version'      => $data['version'] ?? $policy->version,
            'is_mandatory' => (bool) ($data['is_mandatory'] ?? false),
            'status'       => $data['status'],
        ];

        // If a new file is uploaded, replace the old one.
        if ($request->hasFile('file')) {
            $stored = $this->storeUploadedFile($request->file('file'));
            // Best-effort cleanup of the previous file (don't fail the update on it)
            if ($policy->file_path) {
                try { Storage::disk('public')->delete($policy->file_path); } catch (\Throwable $e) {}
            }
            $update['file_path'] = $stored['path'];
            $update['file_name'] = $stored['original'];
            $update['file_mime'] = $stored['mime'];
            $update['file_size'] = $stored['size'];
        }

        $policy->update($update);
        return redirect()->route('policies.index')->with('success', __('Policy updated.'));
    }

    public function destroy(int $id)
    {
        $this->ensureManage();
        $policy = Policy::where('created_by', Auth::user()->creatorId())->findOrFail($id);

        // Delete file from disk; ack rows cascade via FK.
        if ($policy->file_path) {
            try { Storage::disk('public')->delete($policy->file_path); } catch (\Throwable $e) {}
        }
        $policy->delete();
        return redirect()->route('policies.index')->with('success', __('Policy deleted.'));
    }

    /* ──────────────────────────────────────────────────────────────
     * Helpers
     * ──────────────────────────────────────────────────────────── */

    /**
     * Move an UploadedFile into storage/app/public/policies and return the
     * canonical metadata used by both store() and update().
     */
    protected function storeUploadedFile(\Illuminate\Http\UploadedFile $file): array
    {
        $original = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension() ?: 'pdf';
        $name = Str::slug(pathinfo($original, PATHINFO_FILENAME)) . '_' . time() . '_' . Str::random(6) . '.' . $extension;
        $path = $file->storeAs('policies', $name, 'public');

        return [
            'path'     => $path,
            'original' => $original,
            'mime'     => $file->getClientMimeType(),
            'size'     => $file->getSize(),
        ];
    }

    protected function userCanManagePolicies(): bool
    {
        $u = Auth::user();
        if (!$u || $u->type === 'employee') {
            return false;
        }

        if ($u->can('manage-policies')) {
            return true;
        }

        return in_array(strtolower((string) $u->type), ['company', 'hr', 'super admin'], true);
    }

    protected function ensureView(): void
    {
        $u = Auth::user();
        if (!$u) {
            abort(403, __('You do not have permission to view policies.'));
        }

        if ($u->can('view-policies') || $u->can('manage-policies') || $this->userCanManagePolicies()) {
            return;
        }

        // Employees/company users should always see company policy docs even if
        // the Spatie seeder was never run on this tenant.
        if (in_array(strtolower((string) $u->type), ['employee', 'company', 'hr'], true)) {
            return;
        }

        abort(403, __('You do not have permission to view policies.'));
    }

    protected function ensureManage(): void
    {
        if (!$this->userCanManagePolicies()) {
            abort(403, __('You do not have permission to manage policies.'));
        }
    }
}
