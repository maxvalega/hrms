<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class Asset extends Model
{
    public const STATUS_INVENTORY = 'in_inventory';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_REPAIR = 'under_repair';
    public const STATUS_RETIRED = 'retired';

    public const CATEGORIES = [
        'Laptop' => 'Laptop',
        'Desktop' => 'Desktop',
        'Monitor' => 'Monitor',
        'Mobile' => 'Mobile Phone',
        'Tablet' => 'Tablet',
        'Headset' => 'Headset',
        'Keyboard' => 'Keyboard / Mouse',
        'ID Card' => 'ID Card',
        'Access Card' => 'Access Card / Keys',
        'SIM' => 'SIM Card',
        'Charger' => 'Charger / Adapter',
        'Other' => 'Other',
    ];

    public const CONDITIONS = [
        'new' => 'New',
        'good' => 'Good',
        'fair' => 'Fair',
        'poor' => 'Poor',
        'damaged' => 'Damaged / Not functioning',
    ];

    protected $fillable = [
        'employee_id',
        'current_employee_id',
        'name',
        'asset_code',
        'serial_number',
        'category',
        'brand',
        'model',
        'condition',
        'status',
        'purchase_date',
        'supported_date',
        'amount',
        'description',
        'location',
        'created_by',
    ];

    public function users($users)
    {
        $userArr = explode(',', (string) $users);
        $emp = Employee::whereIn('id', $userArr);
        $employees = $emp->get()->pluck('id');
        $users = [];
        foreach ($employees as $user) {
            $emp = Employee::find($user);
            $users[] = User::where('id', $emp->user_id)->first();
        }

        return $users;
    }

    public function currentEmployee()
    {
        return $this->belongsTo(Employee::class, 'current_employee_id');
    }

    public function movements()
    {
        return $this->hasMany(AssetMovement::class)->orderByDesc('id');
    }

    public static function hasLifecycleSchema(): bool
    {
        try {
            return Schema::hasColumn('assets', 'status');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_INVENTORY => __('In inventory'),
            self::STATUS_ASSIGNED => __('Assigned'),
            self::STATUS_REPAIR => __('Under repair'),
            self::STATUS_RETIRED => __('Retired'),
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_INVENTORY => 'am-badge-inventory',
            self::STATUS_ASSIGNED => 'am-badge-assigned',
            self::STATUS_REPAIR => 'am-badge-repair',
            self::STATUS_RETIRED => 'am-badge-retired',
            default => 'am-badge-inventory',
        };
    }

    public function conditionLabel(): string
    {
        return __(self::CONDITIONS[$this->condition] ?? ucfirst((string) $this->condition));
    }

    public function holderName(): string
    {
        if ($this->relationLoaded('currentEmployee') && $this->currentEmployee) {
            return $this->currentEmployee->name;
        }
        if ($this->current_employee_id) {
            $emp = Employee::find($this->current_employee_id);
            return $emp?->name ?: '—';
        }

        return '—';
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_INVENTORY;
    }

    public function isAssigned(): bool
    {
        return $this->status === self::STATUS_ASSIGNED;
    }

    public function isUnderRepair(): bool
    {
        return $this->status === self::STATUS_REPAIR;
    }

    public function recordMovement(string $action, array $extra = []): AssetMovement
    {
        return AssetMovement::create(array_merge([
            'asset_id' => $this->id,
            'employee_id' => $this->current_employee_id,
            'action' => $action,
            'condition' => $this->condition,
            'performed_by' => Auth::id(),
            'created_by' => $this->created_by,
        ], $extra));
    }

    public function assignTo(Employee $employee, string $action = AssetMovement::ASSIGNED, ?string $notes = null): void
    {
        $this->current_employee_id = $employee->id;
        $this->employee_id = (string) $employee->id;
        $this->status = self::STATUS_ASSIGNED;
        $this->save();

        $this->recordMovement($action, [
            'employee_id' => $employee->id,
            'notes' => $notes,
        ]);
    }

    public function returnFromEmployee(string $reason, ?string $condition = null, ?string $notes = null, ?self $replacement = null): void
    {
        $employeeId = $this->current_employee_id;
        $action = $reason === 'defective' ? AssetMovement::RETURNED_DEFECTIVE : AssetMovement::RETURNED_EXIT;

        if ($condition) {
            $this->condition = $condition;
        }

        $this->current_employee_id = null;
        $this->employee_id = '';
        $this->status = $reason === 'defective' ? self::STATUS_REPAIR : self::STATUS_INVENTORY;
        if ($reason === 'defective') {
            $this->location = $this->location ?: __('Repair / IT');
        }
        $this->save();

        $this->recordMovement($action, [
            'employee_id' => $employeeId,
            'replacement_asset_id' => $replacement?->id,
            'notes' => $notes,
            'condition' => $this->condition,
        ]);
    }

    public function restoreAfterRepair(?string $condition = null, ?string $notes = null): void
    {
        if ($condition) {
            $this->condition = $condition;
        } elseif ($this->condition === 'damaged') {
            $this->condition = 'good';
        }

        $this->current_employee_id = null;
        $this->employee_id = '';
        $this->status = self::STATUS_INVENTORY;
        $this->save();

        $this->recordMovement(AssetMovement::RESTORED, [
            'employee_id' => null,
            'notes' => $notes,
            'condition' => $this->condition,
        ]);
    }

    public static function nextAssetCode(int $creatorId): string
    {
        $count = static::where('created_by', $creatorId)->count() + 1;

        return 'AST-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    public static function assignedToEmployee(int $employeeId, int $creatorId)
    {
        return static::where('created_by', $creatorId)
            ->where('status', self::STATUS_ASSIGNED)
            ->where(function ($q) use ($employeeId) {
                $q->where('current_employee_id', $employeeId)
                    ->orWhere('employee_id', (string) $employeeId);
            })
            ->orderBy('name')
            ->get();
    }

    public static function availableInInventory(int $creatorId, ?int $exceptId = null)
    {
        $q = static::where('created_by', $creatorId)
            ->where('status', self::STATUS_INVENTORY)
            ->orderBy('name');

        if ($exceptId) {
            $q->where('id', '!=', $exceptId);
        }

        return $q->get();
    }
}
