@extends('layouts.admin')
@section('page-title') {{ __('Issue Letters') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Employee Letters') }}</li>
@endsection

@push('css-page')
<style>
    .el-stat{border:1px solid var(--bs-border-color);border-radius:12px;background:#fff;padding:14px 16px;}
    .el-stat .n{font-size:1.4rem;font-weight:700;}
    .el-stat .l{font-size:.7rem;text-transform:uppercase;letter-spacing:.4px;color:#64748b;}
    .el-pill{font-size:.65rem;font-weight:700;padding:3px 10px;border-radius:20px;text-transform:uppercase;}
    .el-issued{background:#dbeafe;color:#1d4ed8;}
    .el-emailed{background:#dcfce7;color:#166534;}
</style>
@endpush

@section('action-button')
    <a href="{{ route('employee-letters.formats') }}" class="btn btn-sm btn-light border me-1">
        <i class="ti ti-template me-1"></i>{{ __('Letter Formats') }}
    </a>
    <a href="{{ route('employee-letters.create') }}" class="btn btn-sm btn-primary">
        <i class="ti ti-plus me-1"></i>{{ __('Issue letter') }}
    </a>
@endsection

@section('content')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="alert alert-info border-0 mb-3">
        <strong>{{ __('Where to issue the letter') }}</strong>
        <div class="small mt-1">{{ __('Use Issue letter. Pick the type (Offer, Appointment, Confirmation, Increment), choose the employee, generate it, publish it on the portal, and email it. Upload formats first under Letter Formats if you have a company template.') }}</div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="el-stat"><div class="n">{{ $totals['all'] }}</div><div class="l">{{ __('Letters issued') }}</div></div>
        </div>
        <div class="col-md-6">
            <div class="el-stat"><div class="n">{{ $totals['emailed'] }}</div><div class="l">{{ __('Sent by email') }}</div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between flex-wrap gap-2">
            <h5 class="mb-0">{{ __('Issued letters') }}</h5>
            <form class="d-flex gap-2" method="GET">
                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">{{ __('All types') }}</option>
                    @foreach(\App\Models\LetterFormat::TYPES as $k => $lbl)
                        <option value="{{ $k }}" @selected($type === $k)>{{ __($lbl) }}</option>
                    @endforeach
                </select>
                <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="{{ __('Name or email') }}">
                <button class="btn btn-sm btn-light border">{{ __('Search') }}</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('To') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($letters as $letter)
                        <tr>
                            <td>{{ $letter->issued_at?->format('d M Y') }}</td>
                            <td>{{ $letter->typeLabel() }}</td>
                            <td>
                                <strong>{{ $letter->recipient_name }}</strong>
                                <div class="small text-muted">{{ $letter->recipient_email }}</div>
                            </td>
                            <td>
                                @if($letter->emailed_at)
                                    <span class="el-pill el-emailed">{{ __('Emailed') }}</span>
                                @else
                                    <span class="el-pill el-issued">{{ __('On portal') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('employee-letters.show', $letter->id) }}" class="btn btn-sm btn-light border">{{ __('Open') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ __('No letters issued yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($letters->hasPages())<div class="card-body pt-0">{{ $letters->links() }}</div>@endif
    </div>
@endsection
