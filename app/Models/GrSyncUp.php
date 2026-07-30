<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrSyncUp extends Model
{
    protected $fillable = [
        'cycle_id', 'employee_id', 'manager_id', 'meeting_date', 'notes',
        'discussion_points', 'action_items', 'status', 'employee_seen', 'created_by',
    ];

    protected $casts = [
        'discussion_points' => 'array',
        'action_items' => 'array',
        'meeting_date' => 'date',
        'employee_seen' => 'boolean',
    ];

    public function cycle()   { return $this->belongsTo(PerformanceCycle::class, 'cycle_id'); }
    public function employee(){ return $this->belongsTo(Employee::class, 'employee_id'); }
    public function manager() { return $this->belongsTo(Employee::class, 'manager_id'); }
}
