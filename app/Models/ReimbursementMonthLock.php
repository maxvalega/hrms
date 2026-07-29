<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReimbursementMonthLock extends Model
{
    protected $table = 'reimbursement_month_locks';

    protected $fillable = [
        'lock_month',
        'locked_by',
        'created_by',
        'locked_at',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
    ];

    public static function isLocked(string $month, int $creatorId): bool
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('reimbursement_month_locks')) {
            return false;
        }

        return static::where('created_by', $creatorId)
            ->where('lock_month', $month)
            ->exists();
    }
}
