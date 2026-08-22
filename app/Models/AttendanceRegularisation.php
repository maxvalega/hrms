<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRegularisation extends Model
{
    protected $fillable = [
        'employee_id',
        'date',
        'type',
        'reason',
        'status',
        'manager_comment',
        'reviewed_by',
        'reviewed_at',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
