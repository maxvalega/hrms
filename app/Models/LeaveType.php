<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = [
        'title',
        'policy_code',
        'days',
        'monthly_credit',
        'annual_credit',
        'approval_requirement',
        'credit_frequency',
        'is_prorata',
        'eligible_employee_types',
        'min_notice_days',
        'notice_rules',
        'max_consecutive_days',
        'monthly_limit',
        'max_encash_on_exit',
        'requires_family_relation',
        'is_as_earned',
        'policy_notes',
        'is_carry_forward',
        'max_carry_forward',
        'is_encashable',
        'encash_rate_per_day',
        'encash_basis',
        'country',
        'state',
        'city',
        'created_by',
    ];

    protected $casts = [
        'is_prorata' => 'boolean',
        'requires_family_relation' => 'boolean',
        'is_as_earned' => 'boolean',
        'eligible_employee_types' => 'array',
        'notice_rules' => 'array',
        'monthly_credit' => 'float',
        'annual_credit' => 'float',
        'max_carry_forward' => 'float',
        'max_consecutive_days' => 'float',
        'monthly_limit' => 'float',
        'max_encash_on_exit' => 'float',
        'encash_rate_per_day' => 'float',
    ];
}
