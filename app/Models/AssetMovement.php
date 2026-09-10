<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetMovement extends Model
{
    public const RECORDED = 'recorded';
    public const ASSIGNED = 'assigned';
    public const REASSIGNED = 'reassigned';
    public const RETURNED_EXIT = 'returned_exit';
    public const RETURNED_DEFECTIVE = 'returned_defective';
    public const RESTORED = 'restored';
    public const RETIRED = 'retired';

    protected $fillable = [
        'asset_id',
        'employee_id',
        'action',
        'replacement_asset_id',
        'condition',
        'notes',
        'performed_by',
        'created_by',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function replacement()
    {
        return $this->belongsTo(Asset::class, 'replacement_asset_id');
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            self::RECORDED => __('Recorded in inventory'),
            self::ASSIGNED => __('Assigned to employee'),
            self::REASSIGNED => __('Reassigned from inventory'),
            self::RETURNED_EXIT => __('Returned at exit'),
            self::RETURNED_DEFECTIVE => __('Returned — not functioning'),
            self::RESTORED => __('Restored to inventory after repair'),
            self::RETIRED => __('Retired'),
            default => ucfirst(str_replace('_', ' ', (string) $this->action)),
        };
    }

    public function actionIcon(): string
    {
        return match ($this->action) {
            self::RECORDED => 'ti-package',
            self::ASSIGNED, self::REASSIGNED => 'ti-user-plus',
            self::RETURNED_EXIT => 'ti-logout',
            self::RETURNED_DEFECTIVE => 'ti-tool',
            self::RESTORED => 'ti-refresh',
            self::RETIRED => 'ti-archive',
            default => 'ti-history',
        };
    }
}
