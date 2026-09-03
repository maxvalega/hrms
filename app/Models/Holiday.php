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
        'is_optional',
        'day_type',
    ];
    
    public function location()
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'location_id')) {
            return $this->belongsTo(Branch::class, 'id')->whereRaw('1 = 0');
        }

        return $this->belongsTo(Branch::class, 'location_id');
    }
    
    public function shiftMappings()
    {
        return $this->hasMany(HolidayShiftMapping::class, 'holiday_id');
    }
}
