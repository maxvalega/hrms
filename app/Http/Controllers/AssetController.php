<?php

namespace App\Http\Controllers;

use App\Exports\AssetsExport;
use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Employee;
use App\Models\ExitResignation;
use App\Support\TenantHost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class AssetController extends Controller
{
    protected function lifecycleEnabled(): bool
    {
        return TenantHost::assetLifecycleEnabled();
    }

    protected function assertLifecycle(): void
    {
        if (!$this->lifecycleEnabled()) {
            abort(404);
        }
    }

    protected function companyEmployees()
    {
        return Employee::where('created_by', Auth::user()->creatorId())
            ->orderBy('name')
            ->get()
            ->pluck('name', 'id');
    }

    public function mine()
    {
        if (!TenantHost::isJeminiMainPortal()) {
            abort(404);
        }

        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)
            ->where('created_by', $user->creatorId())
            ->first();

        $current = collect();
        $previous = collect();
        if ($employee) {
            if (Asset::hasLifecycleSchema()) {
                $current = Asset::assignedToEmployee($employee->id, $user->creatorId());
                $previousIds = AssetMovement::where('employee_id', $employee->id)
                    ->where('created_by', $user->creatorId())
                    ->pluck('asset_id')
                    ->unique()
                    ->filter(fn ($id) => !$current->contains('id', $id));
                $previous = $previousIds->isEmpty()
                    ? collect()
                    : Asset::where('created_by', $user->creatorId())
                        ->whereIn('id', $previousIds)
                        ->orderBy('name')
                        ->get();
            } else {
                $current = Asset::where('created_by', $user->creatorId())
                    ->where(function ($q) use ($employee) {
                        $id = (string) $employee->id;
                        $q->where('employee_id', $id)
                            ->orWhere('employee_id', 'like', $id . ',%')
                            ->orWhere('employee_id', 'like', '%,' . $id)
                            ->orWhere('employee_id', 'like', '%,' . $id . ',%');
                    })
                    ->orderBy('name')
                    ->get();
            }
        }

        return view('assets.lifecycle.mine', compact('current', 'previous', 'employee'));
    }

    public function index(Request $request)
    {
        if (!Auth::user()->can('Manage Assets')) {
            if (TenantHost::isJeminiMainPortal()) {
                return redirect()->route('account-assets.mine');
            }

            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if (TenantHost::isJeminiMainPortal()) {
            $assets = Asset::where('created_by', Auth::user()->creatorId())->orderByDesc('id')->get();

            return view('assets.lifecycle.company_index', compact('assets'));
        }

        if (!$this->lifecycleEnabled()) {
            $assets = Asset::where('created_by', '=', Auth::user()->creatorId())->get();

            return view('assets.index', compact('assets'));
        }

        $creatorId = Auth::user()->creatorId();
        $status = $request->input('status', 'all');
        $q = trim((string) $request->input('q', ''));

        $query = Asset::where('created_by', $creatorId)
            ->with('currentEmployee')
            ->orderByDesc('id');

        if ($status !== 'all' && in_array($status, [
            Asset::STATUS_INVENTORY,
            Asset::STATUS_ASSIGNED,
            Asset::STATUS_REPAIR,
            Asset::STATUS_RETIRED,
        ], true)) {
            $query->where('status', $status);
        }

        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', '%' . $q . '%')
                    ->orWhere('asset_code', 'like', '%' . $q . '%')
                    ->orWhere('serial_number', 'like', '%' . $q . '%')
                    ->orWhere('category', 'like', '%' . $q . '%')
                    ->orWhere('brand', 'like', '%' . $q . '%')
                    ->orWhereHas('currentEmployee', fn ($e) => $e->where('name', 'like', '%' . $q . '%'));
            });
        }

        $assets = $query->paginate(20)->withQueryString();

        $base = Asset::where('created_by', $creatorId);
        $totals = [
            'all' => (clone $base)->count(),
            'in_inventory' => (clone $base)->where('status', Asset::STATUS_INVENTORY)->count(),
            'assigned' => (clone $base)->where('status', Asset::STATUS_ASSIGNED)->count(),
            'under_repair' => (clone $base)->where('status', Asset::STATUS_REPAIR)->count(),
        ];

        return view('assets.lifecycle.index', compact('assets', 'totals', 'status', 'q'));
    }

    public function create()
    {
        if (!Auth::user()->can('Create Assets')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $employee = $this->companyEmployees();

        if (TenantHost::isJeminiMainPortal()) {
            return view('assets.lifecycle.create', compact('employee'));
        }

        if ($this->lifecycleEnabled()) {
            return view('assets.lifecycle.create', compact('employee'));
        }

        return view('assets.create', compact('employee'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('Create Assets')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if (TenantHost::isJeminiMainPortal()) {
            return $this->storeAssigned($request);
        }

        if ($this->lifecycleEnabled()) {
            return $this->storeLifecycle($request);
        }

        $validator = \Validator::make(
            $request->all(),
            [
                'employee_id' => 'required',
                'name' => 'required',
                'purchase_date' => 'required',
                'supported_date' => 'required',
                'amount' => 'required',
            ]
        );
        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }
        $employee_id = 0;
        if (!empty($request->employee_id)) {
            $employee_id = implode(',', $request->employee_id);
        }
        $assets = new Asset();
        $assets->employee_id = $employee_id;
        $assets->name = $request->name;
        $assets->purchase_date = $request->purchase_date;
        $assets->supported_date = $request->supported_date;
        $assets->amount = $request->amount;
        $assets->description = $request->description;
        $assets->created_by = Auth::user()->creatorId();
        $assets->save();

        return redirect()->route('account-assets.index')->with('success', __('Assets successfully created.'));
    }

    protected function storeAssigned(Request $request)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'employee_id' => 'required',
                'name' => 'required',
                'purchase_date' => 'required',
                'supported_date' => 'required',
                'amount' => 'required',
            ]
        );
        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        $ids = array_values(array_filter((array) $request->employee_id));
        $asset = new Asset();
        $asset->employee_id = implode(',', $ids);
        $asset->name = $request->name;
        $asset->purchase_date = $request->purchase_date;
        $asset->supported_date = $request->supported_date;
        $asset->amount = $request->amount;
        $asset->description = $request->description;
        $asset->created_by = Auth::user()->creatorId();
        $this->syncAssignmentColumns($asset, $ids);
        $asset->save();

        return redirect()->route('account-assets.index')->with('success', __('Assets successfully created.'));
    }

    protected function syncAssignmentColumns(Asset $asset, array $employeeIds): void
    {
        if (!Asset::hasLifecycleSchema()) {
            return;
        }

        $first = isset($employeeIds[0]) ? (int) $employeeIds[0] : 0;
        $asset->current_employee_id = $first ?: null;
        $asset->status = $first ? Asset::STATUS_ASSIGNED : Asset::STATUS_INVENTORY;
        if (empty($asset->condition)) {
            $asset->condition = 'good';
        }
        if (empty($asset->asset_code)) {
            $asset->asset_code = Asset::nextAssetCode($asset->created_by ?: Auth::user()->creatorId());
        }
    }

    protected function storeLifecycle(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:190',
            'purchase_date' => 'required|date',
            'supported_date' => 'nullable|date',
            'amount' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:80',
            'serial_number' => 'nullable|string|max:120',
            'brand' => 'nullable|string|max:80',
            'model' => 'nullable|string|max:80',
            'condition' => 'nullable|in:new,good,fair,poor,damaged',
            'location' => 'nullable|string|max:120',
            'description' => 'nullable|string',
            'employee_id' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withInput()->with('error', $validator->getMessageBag()->first());
        }

        $creatorId = Auth::user()->creatorId();
        $employee = null;
        if ($request->filled('employee_id')) {
            $employee = Employee::where('created_by', $creatorId)->find($request->employee_id);
            if (!$employee) {
                return redirect()->back()->withInput()->with('error', __('Employee not found.'));
            }
        }

        $asset = new Asset();
        $asset->name = $request->name;
        $asset->asset_code = $request->asset_code ?: Asset::nextAssetCode($creatorId);
        $asset->serial_number = $request->serial_number;
        $asset->category = $request->category;
        $asset->brand = $request->brand;
        $asset->model = $request->model;
        $asset->condition = $request->condition ?: 'good';
        $asset->location = $request->location;
        $asset->purchase_date = $request->purchase_date;
        $asset->supported_date = $request->supported_date ?: $request->purchase_date;
        $asset->amount = $request->amount ?: 0;
        $asset->description = $request->description;
        $asset->created_by = $creatorId;
        $asset->employee_id = $employee ? (string) $employee->id : '';
        $asset->current_employee_id = $employee?->id;
        $asset->status = $employee ? Asset::STATUS_ASSIGNED : Asset::STATUS_INVENTORY;
        $asset->save();

        $asset->recordMovement(AssetMovement::RECORDED, [
            'employee_id' => null,
            'notes' => $employee ? __('Documented and assigned immediately.') : __('Documented existing / new asset in inventory.'),
        ]);

        if ($employee) {
            $asset->recordMovement(AssetMovement::ASSIGNED, [
                'employee_id' => $employee->id,
                'notes' => __('Assigned at the time of recording.'),
            ]);
        }

        return redirect()->route('account-assets.show', $asset->id)
            ->with('success', $employee
                ? __('Asset recorded and assigned to :name.', ['name' => $employee->name])
                : __('Asset recorded in inventory.'));
    }

    public function show($id)
    {
        if (!TenantHost::isJeminiMainPortal()) {
            abort(404);
        }

        $asset = $this->findCompanyAsset($id);
        $user = Auth::user();
        $canManage = $user->can('Manage Assets');
        $myEmployee = Employee::where('user_id', $user->id)->where('created_by', $user->creatorId())->first();
        $isMine = false;
        if ($myEmployee) {
            if (Asset::hasLifecycleSchema()) {
                $isMine = (int) $asset->current_employee_id === (int) $myEmployee->id
                    || AssetMovement::where('asset_id', $asset->id)->where('employee_id', $myEmployee->id)->exists();
            } else {
                $ids = array_filter(array_map('trim', explode(',', (string) $asset->employee_id)));
                $isMine = in_array((string) $myEmployee->id, $ids, true);
            }
        }

        if (!$canManage && !$isMine) {
            return redirect()->route('account-assets.mine')->with('error', __('Permission denied.'));
        }

        if (Asset::hasLifecycleSchema()) {
            $asset->load(['currentEmployee', 'movements.employee', 'movements.performer', 'movements.replacement']);
        }

        $employee = $canManage ? $this->companyEmployees() : collect();
        $inventory = ($canManage && Asset::hasLifecycleSchema())
            ? Asset::availableInInventory($user->creatorId(), $asset->id)
            : collect();

        return view('assets.lifecycle.show', compact('asset', 'employee', 'inventory', 'canManage'));
    }

    public function edit($id)
    {
        if (!Auth::user()->can('Edit Assets')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $asset = Asset::find($id);
        if (!$asset || $asset->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $employee = $this->companyEmployees();

        if (TenantHost::isJeminiMainPortal()) {
            return view('assets.lifecycle.edit', compact('asset', 'employee'));
        }

        if ($this->lifecycleEnabled()) {
            return view('assets.lifecycle.edit', compact('asset', 'employee'));
        }

        return view('assets.edit', compact('asset', 'employee'));
    }

    public function update(Request $request, $id)
    {
        if (!Auth::user()->can('Edit Assets')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $asset = Asset::find($id);
        if (!$asset || $asset->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if (TenantHost::isJeminiMainPortal()) {
            return $this->updateAssigned($request, $asset);
        }

        if ($this->lifecycleEnabled()) {
            $validator = \Validator::make($request->all(), [
                'name' => 'required|string|max:190',
                'purchase_date' => 'required|date',
                'supported_date' => 'nullable|date',
                'amount' => 'nullable|numeric|min:0',
                'category' => 'nullable|string|max:80',
                'serial_number' => 'nullable|string|max:120',
                'brand' => 'nullable|string|max:80',
                'model' => 'nullable|string|max:80',
                'condition' => 'nullable|in:new,good,fair,poor,damaged',
                'location' => 'nullable|string|max:120',
                'description' => 'nullable|string',
                'asset_code' => 'nullable|string|max:50',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->with('error', $validator->getMessageBag()->first());
            }

            $asset->name = $request->name;
            $asset->asset_code = $request->asset_code ?: $asset->asset_code;
            $asset->serial_number = $request->serial_number;
            $asset->category = $request->category;
            $asset->brand = $request->brand;
            $asset->model = $request->model;
            $asset->condition = $request->condition ?: $asset->condition;
            $asset->location = $request->location;
            $asset->purchase_date = $request->purchase_date;
            $asset->supported_date = $request->supported_date ?: $asset->supported_date;
            $asset->amount = $request->amount ?: 0;
            $asset->description = $request->description;
            $asset->save();

            return redirect()->route('account-assets.show', $asset->id)
                ->with('success', __('Asset details updated.'));
        }

        $validator = \Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'purchase_date' => 'required',
                'supported_date' => 'required',
                'amount' => 'required',
            ]
        );
        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }
        $employee_id = 0;
        if (!empty($request->employee_id)) {
            $employee_id = implode(',', $request->employee_id);
        }

        $asset->name = $request->name;
        $asset->employee_id = $employee_id;
        $asset->purchase_date = $request->purchase_date;
        $asset->supported_date = $request->supported_date;
        $asset->amount = $request->amount;
        $asset->description = $request->description;
        $asset->save();

        return redirect()->route('account-assets.index')->with('success', __('Assets successfully updated.'));
    }

    protected function updateAssigned(Request $request, Asset $asset)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'employee_id' => 'required',
                'name' => 'required',
                'purchase_date' => 'required',
                'supported_date' => 'required',
                'amount' => 'required',
            ]
        );
        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        $ids = array_values(array_filter((array) $request->employee_id));
        $asset->name = $request->name;
        $asset->employee_id = implode(',', $ids);
        $asset->purchase_date = $request->purchase_date;
        $asset->supported_date = $request->supported_date;
        $asset->amount = $request->amount;
        $asset->description = $request->description;
        $this->syncAssignmentColumns($asset, $ids);
        $asset->save();

        return redirect()->route('account-assets.index')->with('success', __('Assets successfully updated.'));
    }

    public function destroy($id)
    {
        if (!Auth::user()->can('Delete Assets')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $asset = Asset::find($id);
        if (!$asset || $asset->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if ($this->lifecycleEnabled()) {
            AssetMovement::where('asset_id', $asset->id)->delete();
        }

        $asset->delete();

        return redirect()->route('account-assets.index')->with('success', __('Assets successfully deleted.'));
    }

    public function assignForm($id)
    {
        $this->assertLifecycle();
        if (!Auth::user()->can('Edit Assets')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $asset = $this->findCompanyAsset($id);
        if (!$asset->isAvailable()) {
            return redirect()->back()->with('error', __('Only inventory assets can be assigned.'));
        }

        $employee = $this->companyEmployees();
        $wasAssignedBefore = AssetMovement::where('asset_id', $asset->id)
            ->whereIn('action', [AssetMovement::ASSIGNED, AssetMovement::REASSIGNED])
            ->exists();

        return view('assets.lifecycle.assign', compact('asset', 'employee', 'wasAssignedBefore'));
    }

    public function assign(Request $request, $id)
    {
        $this->assertLifecycle();
        if (!Auth::user()->can('Edit Assets')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $asset = $this->findCompanyAsset($id);
        if (!$asset->isAvailable()) {
            return redirect()->back()->with('error', __('Only inventory assets can be assigned.'));
        }

        $data = $request->validate([
            'employee_id' => 'required|integer',
            'notes' => 'nullable|string|max:1000',
        ]);

        $employee = Employee::where('created_by', Auth::user()->creatorId())->find($data['employee_id']);
        if (!$employee) {
            return redirect()->back()->with('error', __('Employee not found.'));
        }

        $wasAssignedBefore = AssetMovement::where('asset_id', $asset->id)
            ->whereIn('action', [AssetMovement::ASSIGNED, AssetMovement::REASSIGNED])
            ->exists();

        $action = $wasAssignedBefore ? AssetMovement::REASSIGNED : AssetMovement::ASSIGNED;
        $asset->assignTo($employee, $action, $data['notes'] ?? null);

        return redirect()->route('account-assets.show', $asset->id)
            ->with('success', $wasAssignedBefore
                ? __('Asset reassigned to :name.', ['name' => $employee->name])
                : __('Asset assigned to :name.', ['name' => $employee->name]));
    }

    public function returnForm($id)
    {
        $this->assertLifecycle();
        if (!Auth::user()->can('Edit Assets')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $asset = $this->findCompanyAsset($id);
        if (!$asset->isAssigned()) {
            return redirect()->back()->with('error', __('Only assigned assets can be returned.'));
        }

        $inventory = Asset::availableInInventory(Auth::user()->creatorId(), $asset->id);
        $reason = request('reason', 'exit');

        return view('assets.lifecycle.return', compact('asset', 'inventory', 'reason'));
    }

    public function returnAsset(Request $request, $id)
    {
        $this->assertLifecycle();
        if (!Auth::user()->can('Edit Assets')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $asset = $this->findCompanyAsset($id);
        if (!$asset->isAssigned()) {
            return redirect()->back()->with('error', __('Only assigned assets can be returned.'));
        }

        $data = $request->validate([
            'reason' => 'required|in:exit,defective',
            'condition' => 'nullable|in:new,good,fair,poor,damaged',
            'notes' => 'nullable|string|max:1000',
            'replacement_asset_id' => 'nullable|integer',
        ]);

        $holder = $asset->currentEmployee;
        $replacement = null;

        if ($data['reason'] === 'defective' && !empty($data['replacement_asset_id'])) {
            $replacement = Asset::where('created_by', Auth::user()->creatorId())
                ->where('status', Asset::STATUS_INVENTORY)
                ->find($data['replacement_asset_id']);
            if (!$replacement) {
                return redirect()->back()->with('error', __('Replacement asset is not available in inventory.'));
            }
        }

        $condition = $data['condition'] ?? ($data['reason'] === 'defective' ? 'damaged' : $asset->condition);
        $asset->returnFromEmployee($data['reason'], $condition, $data['notes'] ?? null, $replacement);

        if ($replacement && $holder) {
            $replacement->assignTo(
                $holder,
                AssetMovement::ASSIGNED,
                __('Replacement issued because :asset was not functioning.', ['asset' => $asset->name])
            );
        }

        $message = $data['reason'] === 'defective'
            ? __('Asset taken back for repair and restored as not functioning.')
            : __('Asset taken back at exit and restored to inventory.');

        if ($replacement && $holder) {
            $message .= ' ' . __('Replacement :name assigned to :employee.', [
                'name' => $replacement->name,
                'employee' => $holder->name,
            ]);
        }

        if ($request->filled('resignation_id')) {
            return redirect()->route('exit-management.show', $request->resignation_id)->with('success', $message);
        }

        return redirect()->route('account-assets.show', $asset->id)->with('success', $message);
    }

    public function restoreForm($id)
    {
        $this->assertLifecycle();
        if (!Auth::user()->can('Edit Assets')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $asset = $this->findCompanyAsset($id);
        if (!$asset->isUnderRepair()) {
            return redirect()->back()->with('error', __('Only assets under repair can be restored to inventory.'));
        }

        return view('assets.lifecycle.restore', compact('asset'));
    }

    public function restore(Request $request, $id)
    {
        $this->assertLifecycle();
        if (!Auth::user()->can('Edit Assets')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $asset = $this->findCompanyAsset($id);
        if (!$asset->isUnderRepair()) {
            return redirect()->back()->with('error', __('Only assets under repair can be restored to inventory.'));
        }

        $data = $request->validate([
            'condition' => 'nullable|in:new,good,fair,poor,damaged',
            'notes' => 'nullable|string|max:1000',
        ]);

        $asset->restoreAfterRepair($data['condition'] ?? 'good', $data['notes'] ?? null);

        return redirect()->route('account-assets.show', $asset->id)
            ->with('success', __('Asset repaired and restored to inventory. It can now be reassigned.'));
    }

    public function returnAllFromExit(Request $request, int $resignationId)
    {
        $this->assertLifecycle();
        if (!Auth::user()->can('Edit Assets') && !Auth::user()->can('manage-exits')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $resignation = ExitResignation::where('created_by', Auth::user()->creatorId())->findOrFail($resignationId);
        $employee = Employee::where('user_id', $resignation->user_id)
            ->where('created_by', Auth::user()->creatorId())
            ->first();

        if (!$employee) {
            return redirect()->back()->with('error', __('Employee record not found for this resignation.'));
        }

        $assets = Asset::assignedToEmployee($employee->id, Auth::user()->creatorId());
        $count = 0;
        foreach ($assets as $asset) {
            $asset->returnFromEmployee('exit', $asset->condition, __('Returned during employee exit.'), null);
            $count++;
        }

        if ($count === 0) {
            return redirect()->route('exit-management.show', $resignation->id)
                ->with('info', __('No assigned assets to take back.'));
        }

        return redirect()->route('exit-management.show', $resignation->id)
            ->with('success', trans_choice(':count asset taken back and restored to inventory.|:count assets taken back and restored to inventory.', $count, ['count' => $count]));
    }

    protected function findCompanyAsset($id): Asset
    {
        $asset = Asset::find($id);
        if (!$asset || $asset->created_by != Auth::user()->creatorId()) {
            abort(404);
        }

        return $asset;
    }

    public function export()
    {
        $name = 'assets_' . date('Y-m-d i:h:s');
        $data = Excel::download(new AssetsExport(), $name . '.xlsx');

        return $data;
    }

    public function importFile(Request $request)
    {
        return view('assets.import');
    }

    public function assetsImportdata(Request $request)
    {
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

        foreach ($file_data as $key => $row) {
            $employeeData = Employee::where('email', 'like', $row[$request['employee_email']])->where('created_by', Auth::user()->creatorId())->first();

            if (!empty($employeeData)) {
                try {
                    $employeeId = $employeeData->id;

                    Asset::create([
                        'employee_id' => $employeeId,
                        'name' => $row[$request['name']],
                        'purchase_date' => $row[$request['purchase_date']],
                        'supported_date' => $row[$request['supported_date']],
                        'amount' => $row[$request['amount']],
                        'description' => $row[$request['description']],
                        'created_by' => Auth::user()->id,
                    ]);
                } catch (\Throwable $e) {
                    $flag = 1;
                    $html .= '<tr>';

                    $html .= '<td>' . (isset($row[$request['employee_email']]) ? $row[$request['employee_email']] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request['name']]) ? $row[$request['name']] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request['purchase_date']]) ? $row[$request['purchase_date']] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request['supported_date']]) ? $row[$request['supported_date']] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request['amount']]) ? $row[$request['amount']] : '-') . '</td>';
                    $html .= '<td>' . (isset($row[$request['description']]) ? $row[$request['description']] : '-') . '</td>';

                    $html .= '</tr>';
                }
            } else {
                $flag = 1;
                $html .= '<tr>';

                $html .= '<td>' . (isset($row[$request['employee_email']]) ? $row[$request['employee_email']] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request['name']]) ? $row[$request['name']] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request['purchase_date']]) ? $row[$request['purchase_date']] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request['supported_date']]) ? $row[$request['supported_date']] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request['amount']]) ? $row[$request['amount']] : '-') . '</td>';
                $html .= '<td>' . (isset($row[$request['description']]) ? $row[$request['description']] : '-') . '</td>';

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
}
