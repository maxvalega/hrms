<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReimbursementClaim extends Model
{
    protected $table = 'reimbursement_claims';

    protected $fillable = [
        'employee_id',
        'component_id',
        'claim_month',
        'amount',
        'status',
        'remarks',
        'attachment',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'approved_at' => 'datetime',
    ];
}

