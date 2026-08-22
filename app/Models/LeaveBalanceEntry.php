<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalanceEntry extends Model
{
    public const TYPE_OPENING = 'opening';
    public const TYPE_GRANT = 'grant';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'entry_type',
        'days',
        'period_key',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'days' => 'float',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }
}
