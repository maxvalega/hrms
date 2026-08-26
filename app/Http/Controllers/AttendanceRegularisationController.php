<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRegularisation;
use App\Models\Employee;
use App\Services\LeavePolicyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AttendanceRegularisationController extends Controller
{
    protected function ensureTable()
    {
        return Schema::hasTable('attendance_regularisations');
    }

    public function index()
    {
        if (!Auth::check()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if (!$this->ensureTable()) {
            return redirect()->back()->with('error', __('Attendance regularisation is not installed. Run migrations first.'));
        }

        $creatorId = Auth::user()->creatorId();

        if (Auth::user()->type == 'employee') {
            $employee = Employee::where('user_id', Auth::user()->id)->first();
            $rows = AttendanceRegularisation::where('employee_id', $employee->id ?? 0)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->get();
        } else {
            $employeeIds = Employee::where('created_by', $creatorId)->pluck('id');
            $rows = AttendanceRegularisation::whereIn('employee_id', $employeeIds)
                ->with('employee')
                ->orderByRaw("CASE WHEN status = 'Pending' THEN 0 ELSE 1 END")
                ->orderByDesc('date')
                ->get();
        }

        $isSpectal = LeavePolicyService::isSpectalPortal();

        return view('attendance.regularisation_index', compact('rows', 'isSpectal'));
    }

    public function create()
    {
        if (!Auth::check()) {
            return response()->json(['error' => __('Permission denied.')], 401);
        }

        if (Auth::user()->type == 'employee') {
            $employees = Employee::where('user_id', Auth::user()->id)->get()->pluck('name', 'id');
        } else {
            $employees = Employee::where('created_by', Auth::user()->creatorId())->get()->pluck('name', 'id');
        }

        return view('attendance.regularisation_create', compact('employees'));
    }

    public function store(Request $request)
    {
        if (!$this->ensureTable()) {
            return redirect()->back()->with('error', __('Attendance regularisation is not installed. Run migrations first.'));
        }

        $validator = \Validator::make($request->all(), [
            'employee_id' => 'required|integer|exists:employees,id',
            'date' => 'required|date',
            'reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        $employee = Employee::find($request->employee_id);
        if (!$employee || $employee->created_by != Auth::user()->creatorId()) {
            if (!(Auth::user()->type == 'employee' && $employee && $employee->user_id == Auth::user()->id)) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }

        if (Auth::user()->type == 'employee') {
            $self = Employee::where('user_id', Auth::user()->id)->first();
            if (!$self || (int) $self->id !== (int) $request->employee_id) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }

        AttendanceRegularisation::create([
            'employee_id' => $employee->id,
            'date' => $request->date,
            'type' => 'on_ground',
            'reason' => $request->reason,
            'status' => 'Pending',
            'created_by' => Auth::user()->creatorId(),
        ]);

        return redirect()->route('attendance.regularisation.index')
            ->with('success', __('On Ground regularisation request submitted.'));
    }

    public function action($id)
    {
        if (!Auth::user()->can('Manage Attendance') && !Auth::user()->can('Manage Leave')) {
            return response()->json(['error' => __('Permission denied.')], 401);
        }

        $row = AttendanceRegularisation::with('employee')->findOrFail($id);
        if (!$row->employee || $row->employee->created_by != Auth::user()->creatorId()) {
            return response()->json(['error' => __('Permission denied.')], 401);
        }

        return view('attendance.regularisation_action', compact('row'));
    }

    public function changeAction(Request $request, $id)
    {
        if (!Auth::user()->can('Manage Attendance') && !Auth::user()->can('Manage Leave')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if (Auth::user()->type == 'employee') {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = \Validator::make($request->all(), [
            'status' => 'required|in:Approved,Reject',
            'manager_comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        $row = AttendanceRegularisation::with('employee')->findOrFail($id);
        if (!$row->employee || $row->employee->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $row->status = $request->status;
        $row->manager_comment = $request->manager_comment;
        $row->reviewed_by = Auth::user()->id;
        $row->reviewed_at = Carbon::now();
        $row->save();

        return redirect()->route('attendance.regularisation.index')
            ->with('success', __('Regularisation request updated.'));
    }

    /**
     * Delete a Pending On Ground request (own request for employees; HR can delete any Pending in company).
     */
    public function destroy($id)
    {
        if (!Auth::check()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if (!$this->ensureTable()) {
            return redirect()->back()->with('error', __('Attendance regularisation is not installed. Run migrations first.'));
        }

        $row = AttendanceRegularisation::with('employee')->findOrFail($id);

        if (!$row->employee || (int) $row->employee->created_by !== (int) Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if ($row->status !== 'Pending') {
            return redirect()->back()->with('error', __('Only pending requests can be deleted.'));
        }

        if (Auth::user()->type == 'employee') {
            $self = Employee::where('user_id', Auth::user()->id)->first();
            if (!$self || (int) $self->id !== (int) $row->employee_id) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } elseif (!Auth::user()->can('Manage Attendance') && !Auth::user()->can('Manage Leave')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $row->delete();

        return redirect()->route('attendance.regularisation.index')
            ->with('success', __('On Ground regularisation request deleted.'));
    }
}
