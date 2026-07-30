<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Leave Request</title>
</head>
<body style="font-family: Arial, sans-serif; color: #222; line-height: 1.5;">
    <p>Hello {{ $manager->name }},</p>
    <p>
        <strong>{{ $requester->name }}</strong> has applied for leave. Please review and take action.
    </p>
    <p>
        <strong>Leave Type:</strong> {{ optional($leave->leaveType)->title ?? 'Leave' }}<br>
        <strong>Dates:</strong> {{ $leave->start_date }} to {{ $leave->end_date }}<br>
        <strong>Day Type:</strong> {{ ucwords(str_replace('_', ' ', $leave->day_type ?? 'full_day')) }}<br>
        <strong>Reason:</strong> {{ $leave->leave_reason ?: '—' }}
    </p>
    <p>
        <a href="{{ route('leave.index') }}" style="display:inline-block;padding:10px 16px;background:#51459d;color:#fff;text-decoration:none;border-radius:6px;">
            Review Leave Request
        </a>
    </p>
    <p>Thank you.</p>
</body>
</html>
