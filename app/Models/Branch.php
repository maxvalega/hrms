<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Branch extends Model
{
    protected $fillable = [
        'name',
        'country',
        'state',
        'city',
        'latitude',
        'longitude',
        'created_by',
    ];

    
}
