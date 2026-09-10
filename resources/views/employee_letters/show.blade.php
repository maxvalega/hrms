@extends('layouts.admin')
@section('page-title') {{ $letter->typeLabel() }} — {{ $letter->recipient_name }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employee-letters.index') }}">{{ __('Issue Letters') }}</a></li>
    <li class="breadcrumb-item">{{ $letter->recipient_name }}</li>
@endsection

@section('action-button')
    <a href="{{ route('employee-letters.pdf', $letter->id) }}" class="btn btn-sm btn-light border me-1">
        <i class="ti ti-download me-1"></i>{{ __('Download PDF') }}
    </a>
    @if(Auth::user()->can('Manage Employee') || in_array(Auth::user()->type, ['company','hr','super admin'], true))
        @if($letter->recipient_email)
            <form method="POST" action="{{ route('employee-letters.email', $letter->id) }}" class="d-inline">
                @csrf
                <button class="btn btn-sm btn-primary"><i class="ti ti-mail me-1"></i>{{ $letter->emailed_at ? __('Resend email') : __('Send email') }}</button>
            </form>
        @endif
    @endif
@endsection

@section('content')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><div class="small text-muted">{{ __('Type') }}</div><strong>{{ $letter->typeLabel() }}</strong></div>
                <div class="col-md-3"><div class="small text-muted">{{ __('To') }}</div><strong>{{ $letter->recipient_name }}</strong><div class="small">{{ $letter->recipient_email }}</div></div>
                <div class="col-md-3"><div class="small text-muted">{{ __('Issued') }}</div><strong>{{ $letter->issued_at?->format('d M Y H:i') }}</strong></div>
                <div class="col-md-3"><div class="small text-muted">{{ __('Email') }}</div><strong>{{ $letter->emailed_at ? $letter->emailed_at->format('d M Y H:i') : __('Not sent') }}</strong></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h6 class="mb-0">{{ __('Letter preview') }}</h6></div>
        <div class="card-body" style="max-width:800px;">
            {!! $letter->body_html !!}
        </div>
    </div>
@endsection
