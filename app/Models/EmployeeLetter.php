<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLetter extends Model
{
    protected $fillable = [
        'letter_format_id',
        'type',
        'employee_id',
        'recipient_name',
        'recipient_email',
        'subject',
        'body_html',
        'pdf_path',
        'extra_fields',
        'status',
        'issued_at',
        'emailed_at',
        'issued_by',
        'created_by',
    ];

    protected $casts = [
        'extra_fields' => 'array',
        'issued_at' => 'datetime',
        'emailed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function format()
    {
        return $this->belongsTo(LetterFormat::class, 'letter_format_id');
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function typeLabel(): string
    {
        return __(LetterFormat::TYPES[$this->type] ?? ucfirst($this->type));
    }

    public static function replacePlaceholders(string $content, array $vars): string
    {
        foreach ($vars as $key => $val) {
            $content = str_replace('{' . $key . '}', (string) ($val ?? ''), $content);
        }

        return $content;
    }
}
