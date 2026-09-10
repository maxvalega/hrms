<?php

namespace App\Mail;

use App\Models\EmployeeLetter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmployeeLetterMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EmployeeLetter $letter,
        public array $settings,
        public ?string $pdfAbsolutePath = null,
        public ?string $attachName = null
    ) {
    }

    public function build()
    {
        $fromAddress = $this->settings['mail_from_address'] ?? config('mail.from.address');
        $fromName = $this->settings['mail_from_name'] ?? config('mail.from.name');

        $mail = $this->from($fromAddress, $fromName)
            ->subject($this->letter->subject)
            ->view('email.employee_letter');

        if ($this->pdfAbsolutePath && is_file($this->pdfAbsolutePath)) {
            $mail->attach($this->pdfAbsolutePath, [
                'as' => $this->attachName ?: ($this->letter->typeLabel() . '.pdf'),
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
