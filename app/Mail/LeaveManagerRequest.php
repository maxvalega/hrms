<?php

namespace App\Mail;

use App\Models\Leave;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeaveManagerRequest extends Mailable
{
    use Queueable, SerializesModels;

    public Leave $leave;
    public Employee $requester;
    public Employee $manager;

    public function __construct(Leave $leave, Employee $requester, Employee $manager)
    {
        $this->leave = $leave;
        $this->requester = $requester;
        $this->manager = $manager;
    }

    public function build()
    {
        return $this->view('email.leave_manager_request')
            ->with([
                'leave' => $this->leave,
                'requester' => $this->requester,
                'manager' => $this->manager,
            ])
            ->subject('New Leave Request — ' . ($this->requester->name ?? 'Employee'));
    }
}
