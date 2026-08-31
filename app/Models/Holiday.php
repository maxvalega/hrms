<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'occasion',
        'title',
        'description',
        'start_date',
        'end_date',
        'holiday_date',
        'recurring',
        'status',
        'location_id',
        'created_by',
    ];
    
    public function location()
    {
        return $this->belongsTo(Branch::class, 'location_id');
    }
    
    public function shiftMappings()
    {
        return $this->hasMany(HolidayShiftMapping::class, 'holiday_id');
    }
}
