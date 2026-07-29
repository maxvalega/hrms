<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DucumentUpload extends Model
{
    protected $fillable = [
        'name',
        'role',
        'document',
        'description',
        'created_by',
        'uploaded_by',
        'assigned_user_id',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
