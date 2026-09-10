<!DOCTYPE html>
<html>
<body style="font-family:Segoe UI,Arial,sans-serif;color:#1f2937;line-height:1.6;">
<p>Dear {{ $letter->recipient_name }},</p>
<p>Please find attached your {{ strtolower($letter->typeLabel()) }} from HR. You can also view and download it from the employee portal.</p>
<p>Regards,<br>Human Resources</p>
</body>
</html>
