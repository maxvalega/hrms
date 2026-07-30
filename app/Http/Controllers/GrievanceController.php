<?php

namespace App\Http\Controllers;

use App\Models\Grievance;
use App\Models\GrievanceResponse;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GrievanceController extends Controller
{
    /**
     * Display a listing of grievances.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Grievance::with(['user', 'assignedTo', 'latestResponse']);

        // Filter based on user role
        if (in_array($user->type, ['super admin', 'company', 'hr'])) {
            // HR/Admin can see all grievances
            $query->forHR($user);
        } elseif ($user->type === 'employee') {
            // Employees can see their own grievances and anonymous ones with token
            $query->forUser($user, $request->get('anonymous_token'));
        } else {
            // Other roles can only see their own
            $query->where('user_id', $user->id);
        }

        // Apply filters
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Order by latest first
        $grievances = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get statistics for HR/Admin
        $stats = [];
        if (in_array($user->type, ['super admin', 'company', 'hr'])) {
            $stats = [
                'total' => Grievance::forHR($user)->count(),
                'open' => Grievance::forHR($user)->byStatus(Grievance::STATUS_OPEN)->count(),
                'in_progress' => Grievance::forHR($user)->byStatus(Grievance::STATUS_IN_PROGRESS)->count(),
                'resolved' => Grievance::forHR($user)->byStatus(Grievance::STATUS_RESOLVED)->count(),
                'anonymous' => Grievance::forHR($user)->where('is_anonymous', true)->count(),
            ];
        }

        return view('grievances.index', compact('grievances', 'stats'));
    }

    /**
     * Show the form for creating a new grievance.
     */
    public function create()
    {
        $categories = Grievance::getCategories();
        return view('grievances.create', compact('categories'));
    }

    /**
     * Store a newly created grievance in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => ['required', Rule::in(array_keys(Grievance::getCategories()))],
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:20',
            'is_anonymous' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $grievanceData = [
                'category' => $request->category,
                'title' => $request->title,
                'description' => $request->description,
                'status' => Grievance::STATUS_OPEN,
                'is_anonymous' => $request->boolean('is_anonymous', false),
            ];

            if (!$request->boolean('is_anonymous')) {
                $grievanceData['user_id'] = Auth::id();
            } else {
                $grievanceData['anonymous_token'] = Grievance::generateAnonymousToken();
            }

            $grievance = Grievance::create($grievanceData);

            // Create system note about the grievance creation
            GrievanceResponse::createSystemNote(
                $grievance->id,
                Auth::id(),
                'Grievance created successfully.'
            );

            DB::commit();

            \App\Services\InAppNotifier::notifyCompanyHr(Auth::user()->creatorId(), [
                'module' => 'grievance',
                'action' => 'created',
                'title' => __('New Grievance'),
                'message' => $grievance->title,
                'link' => route('grievances.show', $grievance->id),
            ]);

            $message = $grievance->is_anonymous 
                ? 'Grievance submitted successfully! Your anonymous token is: ' . $grievance->anonymous_token . '. Please save this token for future reference.'
                : 'Grievance submitted successfully!';

            return redirect()->route('grievances.show', $grievance->id)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to submit grievance. Please try again.')
                ->withInput();
        }
    }

    /**
     * Display the specified grievance.
     */
    public function show(Request $request, $id)
    {
        $user = Auth::user();
        $grievance = Grievance::with(['user', 'assignedTo', 'responses.responder'])
            ->findOrFail($id);

        // Check access permissions
        if (!$this->canAccessGrievance($grievance, $user, $request->get('anonymous_token'))) {
            abort(403, 'Unauthorized access to this grievance.');
        }

        // Get responses based on user role
        if (in_array($user->type, ['super admin', 'company', 'hr'])) {
            $responses = $grievance->responses;
        } else {
            $responses = $grievance->publicResponses;
        }

        // Get available HR staff for assignment
        $hrStaff = [];
        if (in_array($user->type, ['super admin', 'company', 'hr'])) {
            $hrStaff = User::whereIn('type', ['super admin', 'company', 'hr'])
                ->where('id', '!=', $user->id)
                ->get();
        }

        return view('grievances.show', compact('grievance', 'responses', 'hrStaff'));
    }

    /**
     * Update the grievance status.
     */
    public function updateStatus(Request $request, $id)
    {
        $grievance = Grievance::findOrFail($id);

        // Only HR/Admin can update status
        if (!in_array(Auth::user()->type, ['super admin', 'company', 'hr'])) {
            abort(403, 'Unauthorized to update grievance status.');
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(array_keys(Grievance::getStatuses()))],
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $oldStatus = $grievance->status;
            $newStatus = $request->status;

            // Update grievance
            $grievance->status = $newStatus;
            if ($request->filled('assigned_to')) {
                $grievance->assigned_to = $request->assigned_to;
            }

            if ($newStatus === Grievance::STATUS_RESOLVED) {
                $grievance->resolved_at = now();
            } elseif ($oldStatus === Grievance::STATUS_RESOLVED && $newStatus !== Grievance::STATUS_RESOLVED) {
                $grievance->resolved_at = null;
            }

            $grievance->save();

            // Create system note about status change
            $statusMessage = "Status changed from '{$oldStatus}' to '{$newStatus}'";
            if ($request->filled('assigned_to') && $grievance->assigned_to) {
                $assignedUser = User::find($grievance->assigned_to);
                $statusMessage .= ". Assigned to: {$assignedUser->name}";
            }

            GrievanceResponse::createSystemNote(
                $grievance->id,
                Auth::id(),
                $statusMessage
            );

            DB::commit();

            return redirect()->back()
                ->with('success', 'Grievance status updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to update grievance status.');
        }
    }

    /**
     * Add response to grievance.
     */
    public function addResponse(Request $request, $id)
    {
        $grievance = Grievance::findOrFail($id);

        // Check access permissions
        if (!$this->canAccessGrievance($grievance, Auth::user(), $request->get('anonymous_token'))) {
            abort(403, 'Unauthorized access to this grievance.');
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|min:10',
            'response_type' => ['required', Rule::in(['hr_response', 'employee_reply', 'internal_note'])],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $responseType = $request->response_type;
            $isInternal = $responseType === 'internal_note';

            // Validate response type based on user role
            if ($responseType === 'hr_response' && !in_array(Auth::user()->type, ['super admin', 'company', 'hr'])) {
                throw new \Exception('Unauthorized to create HR response.');
            }

            if ($responseType === 'employee_reply' && Auth::user()->type !== 'employee') {
                throw new \Exception('Unauthorized to create employee reply.');
            }

            // Create response
            $response = GrievanceResponse::create([
                'grievance_id' => $grievance->id,
                'responder_id' => Auth::id(),
                'message' => $request->message,
                'response_type' => $responseType === 'internal_note' ? GrievanceResponse::TYPE_HR_RESPONSE : $responseType,
                'is_internal_note' => $isInternal,
            ]);

            // Auto-update grievance status based on response
            if ($responseType === 'hr_response' && $grievance->isOpen()) {
                $grievance->markAsInProgress();
            }

            DB::commit();

            return redirect()->back()
                ->with('success', 'Response added successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to add response: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified grievance from storage.
     */
    public function destroy($id)
    {
        $grievance = Grievance::findOrFail($id);

        // Only Super Admin can delete grievances
        if (Auth::user()->type !== 'super admin') {
            abort(403, 'Unauthorized to delete grievances.');
        }

        try {
            DB::beginTransaction();
            $grievance->responses()->delete();
            $grievance->delete();
            DB::commit();

            return redirect()->route('grievances.index')
                ->with('success', 'Grievance deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to delete grievance.');
        }
    }

    /**
     * Check if user can access the grievance.
     */
    private function canAccessGrievance($grievance, $user, $anonymousToken = null)
    {
        // Super Admin, Company, HR can access all
        if (in_array($user->type, ['super admin', 'company', 'hr'])) {
            return true;
        }

        // User can access their own grievances
        if ($grievance->user_id === $user->id) {
            return true;
        }

        // Anonymous grievance access with token
        if ($grievance->is_anonymous && $anonymousToken === $grievance->anonymous_token) {
            return true;
        }

        // Managers can access grievances of their subordinates
        if ($user->type === 'employee') {
            $employee = Employee::where('user_id', $user->id)->first();
            if ($employee) {
                $subordinateIds = Employee::where('reporting_manager_id', $employee->id)
                    ->pluck('user_id')
                    ->toArray();
                return in_array($grievance->user_id, $subordinateIds);
            }
        }

        return false;
    }

    /**
     * Get grievance statistics for dashboard.
     */
    public function getStats()
    {
        $user = Auth::user();
        
        if (!in_array($user->type, ['super admin', 'company', 'hr'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = [
            'total' => Grievance::forHR($user)->count(),
            'open' => Grievance::forHR($user)->byStatus(Grievance::STATUS_OPEN)->count(),
            'in_progress' => Grievance::forHR($user)->byStatus(Grievance::STATUS_IN_PROGRESS)->count(),
            'resolved' => Grievance::forHR($user)->byStatus(Grievance::STATUS_RESOLVED)->count(),
            'this_month' => Grievance::forHR($user)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'last_month' => Grievance::forHR($user)
                ->whereMonth('created_at', now()->subMonth()->month)
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Run grievance module migrations.
     */
    public function runMigrations(Request $request)
    {
        $output = [];
        
        try {
            // Create grievances table
            if (!\Schema::hasTable('grievances')) {
                \Schema::create('grievances', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id')->nullable()->comment('Nullable for anonymous complaints');
                    $table->string('category');
                    $table->string('title');
                    $table->text('description');
                    $table->enum('status', ['open', 'in_progress', 'resolved'])->default('open');
                    $table->boolean('is_anonymous')->default(false);
                    $table->text('anonymous_token')->nullable()->unique()->comment('Token for tracking anonymous complaints');
                    $table->unsignedBigInteger('assigned_to')->nullable()->comment('HR/Admin assigned to handle');
                    $table->timestamp('resolved_at')->nullable();
                    $table->timestamps();
                    
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                    $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
                    
                    $table->index(['status', 'created_at']);
                    $table->index(['category', 'status']);
                });
                $output[] = "✅ grievances table created successfully!";
            } else {
                $output[] = "ℹ️ grievances table already exists";
            }

            // Create grievance_responses table
            if (!\Schema::hasTable('grievance_responses')) {
                \Schema::create('grievance_responses', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('grievance_id');
                    $table->unsignedBigInteger('responder_id');
                    $table->text('message');
                    $table->enum('response_type', ['hr_response', 'employee_reply', 'system_note'])->default('hr_response');
                    $table->boolean('is_internal_note')->default(false)->comment('Visible only to HR staff');
                    $table->timestamps();
                    
                    $table->foreign('grievance_id')->references('id')->on('grievances')->onDelete('cascade');
                    $table->foreign('responder_id')->references('id')->on('users')->onDelete('cascade');
                    
                    $table->index(['grievance_id', 'created_at']);
                    $table->index(['responder_id', 'created_at']);
                });
                $output[] = "✅ grievance_responses table created successfully!";
            } else {
                $output[] = "ℹ️ grievance_responses table already exists";
            }

            $output[] = "";
            $output[] = "🎉 All grievance module migrations completed successfully!";
            $output[] = "";
            $output[] = "📝 Next Steps:";
            $output[] = "1. Visit: /grievances";
            $output[] = "2. Test raising a grievance: /grievances/create";
            $output[] = "3. Check test page: /test-grievances";

            return response()->json([
                'success' => true,
                'output' => implode("\n", $output)
            ]);

        } catch (\Exception $e) {
            $output[] = "❌ Error during migration: " . $e->getMessage();
            $output[] = "";
            $output[] = "🔧 Troubleshooting:";
            $output[] = "1. Make sure database connection is working";
            $output[] = "2. Check if users table exists";
            $output[] = "3. Verify database permissions";

            return response()->json([
                'success' => false,
                'output' => implode("\n", $output)
            ]);
        }
    }

    /* ──────────────────────────────────────────────────────────────
     * Anonymous tracking — public, no auth.
     *
     * Anonymous complainants get a token (e.g. GRV_AB12CD34EF56) at submission.
     * This page lets them paste the token and see the current status + the
     * public response thread, without exposing any complainant info.
     * ──────────────────────────────────────────────────────────── */

    /** Show the "Track Anonymous Grievance" form. */
    public function trackForm()
    {
        return view('grievances.track');
    }

    /**
     * Lookup grievance by anonymous token. Always returns the same view; on
     * miss, shows an error message — never leaks whether the token format
     * was right vs the row simply doesn't exist.
     */
    public function trackLookup(Request $request)
    {
        $token = trim((string) $request->input('token'));

        if ($token === '') {
            return redirect()->route('grievances.track')
                ->with('error', __('Please enter your tracking token.'));
        }

        $grievance = Grievance::with(['publicResponses.responder'])
            ->where('is_anonymous', true)
            ->where('anonymous_token', $token)
            ->first();

        if (!$grievance) {
            return redirect()->route('grievances.track')
                ->with('error', __('No grievance found for that token. Please check and try again.'))
                ->withInput();
        }

        return view('grievances.track', [
            'grievance' => $grievance,
            'token'     => $token,
        ]);
    }
}
