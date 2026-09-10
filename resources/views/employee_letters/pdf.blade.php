<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body{font-family:DejaVu Sans,Helvetica,Arial,sans-serif;font-size:13px;color:#222;line-height:1.55;margin:40px 48px;}
    .head{text-align:center;border-bottom:2px solid #4f46e5;padding-bottom:12px;margin-bottom:22px;}
    .head h2{margin:0;color:#4f46e5;font-size:18px;}
    .meta{font-size:11px;color:#64748b;margin-bottom:18px;}
    .body p{margin:0 0 10px;}
</style>
</head>
<body>
    <div class="head">
        <h2>{{ $companyName }}</h2>
        <div>{{ $letter->typeLabel() }}</div>
    </div>
    <div class="meta">{{ __('Date') }}: {{ $letter->issued_at?->format('d M Y') }} · {{ __('To') }}: {{ $letter->recipient_name }}</div>
    <div class="body">
        {!! $letter->body_html !!}
    </div>
</body>
</html>
