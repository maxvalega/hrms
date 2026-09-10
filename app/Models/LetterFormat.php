<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LetterFormat extends Model
{
    public const TYPE_OFFER = 'offer';
    public const TYPE_APPOINTMENT = 'appointment';
    public const TYPE_CONFIRMATION = 'confirmation';
    public const TYPE_INCREMENT = 'increment';

    public const TYPES = [
        self::TYPE_OFFER => 'Offer Letter',
        self::TYPE_APPOINTMENT => 'Appointment Letter',
        self::TYPE_CONFIRMATION => 'Confirmation Letter',
        self::TYPE_INCREMENT => 'Increment Letter',
    ];

    protected $fillable = [
        'type',
        'name',
        'content',
        'file_path',
        'file_name',
        'is_active',
        'created_by',
    ];

    public function typeLabel(): string
    {
        return __(self::TYPES[$this->type] ?? ucfirst($this->type));
    }

    public static function placeholders(): array
    {
        return [
            '{employee_name}' => __('Employee name'),
            '{employee_code}' => __('Employee ID'),
            '{designation}' => __('Designation'),
            '{department}' => __('Department'),
            '{branch}' => __('Branch / location'),
            '{company_name}' => __('Company name'),
            '{date}' => __('Letter date'),
            '{joining_date}' => __('Date of joining'),
            '{address}' => __('Address'),
            '{email}' => __('Email'),
            '{salary}' => __('Salary / CTC'),
            '{new_salary}' => __('Revised salary / CTC'),
            '{increment_amount}' => __('Increment amount'),
            '{increment_percent}' => __('Increment %'),
            '{effective_date}' => __('Effective date'),
            '{confirmation_date}' => __('Confirmation date'),
            '{job_title}' => __('Job title'),
            '{offer_expiry}' => __('Offer expiry date'),
        ];
    }

    public static function defaultContent(string $type): string
    {
        return match ($type) {
            self::TYPE_OFFER => <<<'HTML'
<p style="text-align:center;"><strong>OFFER LETTER</strong></p>
<p>Date: {date}</p>
<p>Dear {employee_name},</p>
<p>We are pleased to offer you the position of <strong>{job_title}</strong> with <strong>{company_name}</strong>.</p>
<p>Your date of joining will be <strong>{joining_date}</strong> at <strong>{branch}</strong>. Compensation for this role will be <strong>{salary}</strong> per annum.</p>
<p>Please review this offer and confirm your acceptance on or before <strong>{offer_expiry}</strong>. We look forward to welcoming you.</p>
<p>Sincerely,<br>{company_name}<br>Human Resources</p>
HTML,
            self::TYPE_APPOINTMENT => <<<'HTML'
<p style="text-align:center;"><strong>APPOINTMENT LETTER</strong></p>
<p>Date: {date}</p>
<p>To,<br>{employee_name}<br>{address}</p>
<p>Subject: Appointment as {designation}</p>
<p>Dear {employee_name},</p>
<p>We are pleased to appoint you as <strong>{designation}</strong> in the <strong>{department}</strong> department at <strong>{branch}</strong>, effective <strong>{joining_date}</strong>.</p>
<p>Your employee ID is <strong>{employee_code}</strong>. Compensation will be as discussed and recorded as <strong>{salary}</strong>.</p>
<p>Please report to your reporting manager on the date of joining. We wish you a successful career with {company_name}.</p>
<p>Sincerely,<br>{company_name}<br>Human Resources</p>
HTML,
            self::TYPE_CONFIRMATION => <<<'HTML'
<p style="text-align:center;"><strong>CONFIRMATION LETTER</strong></p>
<p>Date: {date}</p>
<p>Dear {employee_name},</p>
<p>This is to confirm that you have successfully completed your probation and are confirmed as a permanent employee of <strong>{company_name}</strong> in the position of <strong>{designation}</strong>, effective <strong>{confirmation_date}</strong>.</p>
<p>Employee ID: {employee_code}<br>Department: {department}<br>Location: {branch}</p>
<p>We appreciate your contribution and look forward to your continued association with us.</p>
<p>Sincerely,<br>{company_name}<br>Human Resources</p>
HTML,
            self::TYPE_INCREMENT => <<<'HTML'
<p style="text-align:center;"><strong>INCREMENT / REVISION LETTER</strong></p>
<p>Date: {date}</p>
<p>Dear {employee_name},</p>
<p>We are pleased to inform you that your compensation has been revised in recognition of your performance.</p>
<p>Designation: {designation}<br>Current CTC: {salary}<br>Revised CTC: {new_salary}<br>Increment: {increment_amount} ({increment_percent})<br>Effective date: {effective_date}</p>
<p>All other terms of your employment remain unchanged. We look forward to your continued contribution to {company_name}.</p>
<p>Sincerely,<br>{company_name}<br>Human Resources</p>
HTML,
            default => '<p>Dear {employee_name},</p><p></p><p>{company_name}</p>',
        };
    }

    public static function ensureDefaults(int $creatorId): void
    {
        foreach (self::TYPES as $type => $name) {
            self::firstOrCreate(
                ['type' => $type, 'created_by' => $creatorId],
                [
                    'name' => $name,
                    'content' => self::defaultContent($type),
                    'is_active' => true,
                ]
            );
        }
    }

    public static function forCompany(int $creatorId, string $type): self
    {
        self::ensureDefaults($creatorId);

        return self::where('created_by', $creatorId)->where('type', $type)->firstOrFail();
    }
}
