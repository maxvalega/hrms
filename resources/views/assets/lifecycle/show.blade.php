@extends('layouts.admin')
@section('page-title') {{ __('Asset') }} — {{ $asset->name }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    @can('Manage Assets')
        <li class="breadcrumb-item"><a href="{{ route('account-assets.index') }}">{{ __('Asset Management') }}</a></li>
    @else
        <li class="breadcrumb-item"><a href="{{ route('account-assets.mine') }}">{{ __('My Assets') }}</a></li>
    @endcan
    <li class="breadcrumb-item">{{ $asset->asset_code ?: $asset->name }}</li>
@endsection

@push('css-page')
<style>
    .am-badge{font-size:.65rem;font-weight:700;padding:3px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:.3px;}
    .am-badge-inventory{background:#dcfce7;color:#166534;}
    .am-badge-assigned{background:#dbeafe;color:#1d4ed8;}
    .am-badge-repair{background:#fef3c7;color:#b45309;}
    .am-badge-retired{background:#e2e8f0;color:#475569;}
    .meta-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;}
    .meta-card .lbl{font-size:.66rem;text-transform:uppercase;color:#94a3b8;letter-spacing:.4px;font-weight:600;}
    .meta-card .val{font-weight:700;color:#0f172a;}
    .am-tl{position:relative;padding-left:28px;}
    .am-tl::before{content:'';position:absolute;left:10px;top:8px;bottom:8px;width:2px;background:#e2e8f0;}
    .am-tl-item{position:relative;margin-bottom:18px;}
    .am-tl-item .dot{position:absolute;left:-22px;top:2px;width:16px;height:16px;border-radius:50%;background:#6366f1;color:#fff;display:flex;align-items:center;justify-content:center;font-size:10px;}
    .am-tl-item .when{font-size:.72rem;color:#94a3b8;}
</style>
@endpush

@section('action-button')
    @can('Manage Assets')
        <a href="{{ route('account-assets.index') }}" class="btn btn-sm btn-light border me-1"><i class="ti ti-arrow-left"></i></a>
    @else
        <a href="{{ route('account-assets.mine') }}" class="btn btn-sm btn-light border me-1"><i class="ti ti-arrow-left"></i></a>
    @endcan
    @can('Edit Assets')
        <a href="#" data-url="{{ route('account-assets.edit', $asset->id) }}" data-ajax-popup="true"
            data-title="{{ __('Edit asset') }}" data-size="lg" class="btn btn-sm btn-primary me-1">
            <i class="ti ti-pencil"></i>
        </a>
        @if($asset->isAvailable())
            <a href="#" data-url="{{ route('account-assets.assign.form', $asset->id) }}" data-ajax-popup="true"
                data-title="{{ __('Assign to employee') }}" data-size="md" class="btn btn-sm btn-info">
                <i class="ti ti-user-plus me-1"></i>{{ __('Assign') }}
            </a>
        @endif
        @if($asset->isAssigned())
            <a href="#" data-url="{{ route('account-assets.return.form', $asset->id) }}" data-ajax-popup="true"
                data-title="{{ __('Take back asset') }}" data-size="lg" class="btn btn-sm btn-warning">
                <i class="ti ti-transfer-in me-1"></i>{{ __('Take back') }}
            </a>
        @endif
        @if($asset->isUnderRepair())
            <a href="#" data-url="{{ route('account-assets.restore.form', $asset->id) }}" data-ajax-popup="true"
                data-title="{{ __('Restore after repair') }}" data-size="md" class="btn btn-sm btn-success">
                <i class="ti ti-refresh me-1"></i>{{ __('Restore to inventory') }}
            </a>
        @endif
    @endcan
    @can('Delete Assets')
        {!! Form::open(['method' => 'DELETE', 'route' => ['account-assets.destroy', $asset->id], 'class' => 'd-inline', 'onsubmit' => 'return confirm("'.__('Delete this asset?').'")']) !!}
            <button class="btn btn-sm btn-danger"><i class="ti ti-trash"></i></button>
        {!! Form::close() !!}
    @endcan
@endsection

@section('content')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <div class="text-muted small">{{ $asset->asset_code }}</div>
                    <h4 class="mb-1">{{ $asset->name }}</h4>
                    <div class="text-muted">{{ trim(($asset->brand ?? '') . ' ' . ($asset->model ?? '')) }} {{ $asset->serial_number ? '· ' . $asset->serial_number : '' }}</div>
                    <div class="mt-2"><span class="am-badge {{ $asset->statusBadgeClass() }}">{{ $asset->statusLabel() }}</span></div>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-3">
                    <div class="meta-card">
                        <div class="lbl">{{ __('Holder') }}</div>
                        <div class="val">{{ $asset->holderName() }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="meta-card">
                        <div class="lbl">{{ __('Category') }}</div>
                        <div class="val">{{ $asset->category ?: '—' }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="meta-card">
                        <div class="lbl">{{ __('Condition') }}</div>
                        <div class="val">{{ $asset->conditionLabel() }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="meta-card">
                        <div class="lbl">{{ __('Location') }}</div>
                        <div class="val">{{ $asset->location ?: '—' }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="meta-card">
                        <div class="lbl">{{ __('Purchase date') }}</div>
                        <div class="val">{{ $asset->purchase_date ? \Auth::user()->dateFormat($asset->purchase_date) : '—' }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="meta-card">
                        <div class="lbl">{{ __('Support until') }}</div>
                        <div class="val">{{ $asset->supported_date ? \Auth::user()->dateFormat($asset->supported_date) : '—' }}</div>
                    </div>
                </div>
                @can('Manage Assets')
                <div class="col-md-3">
                    <div class="meta-card">
                        <div class="lbl">{{ __('Amount') }}</div>
                        <div class="val">{{ \Auth::user()->priceFormat($asset->amount) }}</div>
                    </div>
                </div>
                @endcan
                <div class="col-md-3">
                    <div class="meta-card">
                        <div class="lbl">{{ __('Notes') }}</div>
                        <div class="val" style="font-weight:500">{{ $asset->description ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h6 class="mb-0"><i class="ti ti-history me-1"></i>{{ __('Movement history') }}</h6>
        </div>
        <div class="card-body">
            @if($asset->movements->isEmpty())
                <p class="text-muted mb-0">{{ __('No movements recorded yet.') }}</p>
            @else
                <div class="am-tl">
                    @foreach($asset->movements as $m)
                        <div class="am-tl-item">
                            <span class="dot"><i class="ti {{ $m->actionIcon() }}"></i></span>
                            <div class="fw-semibold">{{ $m->actionLabel() }}</div>
                            <div class="when">
                                {{ $m->created_at->format('d M Y · h:i A') }}
                                @if($m->performer) · {{ $m->performer->name }} @endif
                            </div>
                            @if($m->employee)
                                <div class="small mt-1">{{ __('Employee') }}: {{ $m->employee->name }}</div>
                            @endif
                            @if($m->replacement)
                                <div class="small">{{ __('Replacement issued') }}:
                                    <a href="{{ route('account-assets.show', $m->replacement->id) }}">{{ $m->replacement->asset_code }} {{ $m->replacement->name }}</a>
                                </div>
                            @endif
                            @if($m->condition)
                                <div class="small text-muted">{{ __('Condition') }}: {{ __(ucfirst($m->condition)) }}</div>
                            @endif
                            @if($m->notes)
                                <div class="small mt-1">{{ $m->notes }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
