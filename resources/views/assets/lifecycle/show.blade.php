@extends('layouts.admin')
@section('page-title') {{ __('Asset') }} — {{ $asset->name }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    @can('Manage Assets')
        <li class="breadcrumb-item"><a href="{{ route('account-assets.index') }}">{{ __('Assets') }}</a></li>
    @else
        <li class="breadcrumb-item"><a href="{{ route('account-assets.mine') }}">{{ __('My Assets') }}</a></li>
    @endcan
    <li class="breadcrumb-item">{{ $asset->name }}</li>
@endsection

@push('css-page')
<style>
    .meta-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;height:100%;}
    .meta-card .lbl{font-size:.66rem;text-transform:uppercase;color:#94a3b8;letter-spacing:.4px;font-weight:600;margin-bottom:4px;}
    .meta-card .val{font-weight:700;color:#0f172a;word-break:break-word;}
    .card > .card-header{display:block;}
</style>
@endpush

@section('action-button')
    @can('Manage Assets')
        <a href="{{ route('account-assets.index') }}" class="btn btn-sm btn-light border me-1"><i class="ti ti-arrow-left"></i></a>
        @can('Edit Assets')
            <a href="#" data-url="{{ route('account-assets.edit', $asset->id) }}" data-ajax-popup="true"
                data-title="{{ __('Edit asset') }}" data-size="lg" class="btn btn-sm btn-primary">
                <i class="ti ti-pencil me-1"></i>{{ __('Edit') }}
            </a>
        @endcan
    @else
        <a href="{{ route('account-assets.mine') }}" class="btn btn-sm btn-light border"><i class="ti ti-arrow-left me-1"></i>{{ __('Back') }}</a>
    @endcan
@endsection

@section('content')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card">
        <div class="card-header">
            <h5>{{ $asset->name }}</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="meta-card">
                        <div class="lbl">{{ __('Asset') }}</div>
                        <div class="val">{{ $asset->name }}</div>
                    </div>
                </div>
                @can('Manage Assets')
                    <div class="col-md-6">
                        <div class="meta-card">
                            <div class="lbl">{{ __('Assigned to') }}</div>
                            <div class="val">{{ $asset->assignedEmployeeNames() }}</div>
                        </div>
                    </div>
                @endcan
                <div class="col-md-6">
                    <div class="meta-card">
                        <div class="lbl">{{ __('Purchase date') }}</div>
                        <div class="val">{{ $asset->purchase_date ? \Auth::user()->dateFormat($asset->purchase_date) : '—' }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="meta-card">
                        <div class="lbl">{{ __('Support until') }}</div>
                        <div class="val">{{ $asset->supported_date ? \Auth::user()->dateFormat($asset->supported_date) : '—' }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="meta-card">
                        <div class="lbl">{{ __('Amount') }}</div>
                        <div class="val">{{ \Auth::user()->priceFormat($asset->amount) }}</div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="meta-card">
                        <div class="lbl">{{ __('Description') }}</div>
                        <div class="val" style="font-weight:500">{{ $asset->description ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
