@extends('layouts.admin')
@section('page-title')
    {{ __('Asset Management') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Asset Management') }}</li>
@endsection

@push('css-page')
<style>
    .am-stat{border:1px solid var(--bs-border-color);border-radius:12px;background:#fff;padding:14px 16px;display:flex;align-items:center;gap:12px;}
    .am-stat .am-icon{width:40px;height:40px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:18px;}
    .am-stat .am-num{font-size:1.45rem;font-weight:700;line-height:1;color:#0f172a;}
    .am-stat .am-lbl{font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-top:3px;}
    .am-stat.t-all .am-icon{background:linear-gradient(135deg,#6366f1,#8b5cf6);}
    .am-stat.t-inv .am-icon{background:linear-gradient(135deg,#10b981,#059669);}
    .am-stat.t-asg .am-icon{background:linear-gradient(135deg,#3b82f6,#2563eb);}
    .am-stat.t-rep .am-icon{background:linear-gradient(135deg,#f59e0b,#d97706);}
    .am-flow{display:flex;flex-wrap:wrap;gap:8px;align-items:center;font-size:.78rem;color:#475569;}
    .am-flow span.step{background:#f8fafc;border:1px solid #e2e8f0;border-radius:999px;padding:4px 10px;font-weight:600;}
    .am-flow .arr{color:#cbd5e1;}
    .am-badge{font-size:.65rem;font-weight:700;padding:3px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:.3px;}
    .am-badge-inventory{background:#dcfce7;color:#166534;}
    .am-badge-assigned{background:#dbeafe;color:#1d4ed8;}
    .am-badge-repair{background:#fef3c7;color:#b45309;}
    .am-badge-retired{background:#e2e8f0;color:#475569;}
    .am-table th{font-size:.7rem;text-transform:uppercase;letter-spacing:.4px;color:#64748b;font-weight:600;background:#fafafa;}
    .am-table td{vertical-align:middle;}
    .am-empty{text-align:center;padding:48px 16px;color:#94a3b8;}
    .am-empty i{font-size:3rem;opacity:.3;display:block;margin-bottom:10px;}
    .am-filter .nav-link{font-size:.8rem;font-weight:600;color:#64748b;}
    .am-filter .nav-link.active{color:#4f46e5;}
</style>
@endpush

@section('action-button')
    <a href="{{ route('assets.export') }}" data-bs-toggle="tooltip" title="{{ __('Export') }}" class="btn btn-sm btn-primary me-1">
        <i class="ti ti-file-export"></i>
    </a>
    @can('Create Assets')
        <a href="#" data-url="{{ route('account-assets.create') }}" data-ajax-popup="true"
            data-title="{{ __('Record Asset') }}" data-size="lg" data-bs-toggle="tooltip"
            class="btn btn-sm btn-primary" title="{{ __('Record existing asset') }}">
            <i class="ti ti-plus"></i>
        </a>
    @endcan
@endsection

@section('content')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('info'))<div class="alert alert-info alert-dismissible fade show">{{ session('info') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="am-flow">
                <span class="step">1. {{ __('Record inventory') }}</span>
                <span class="arr">→</span>
                <span class="step">2. {{ __('Assign to employee') }}</span>
                <span class="arr">→</span>
                <span class="step">3. {{ __('Take back at exit') }} → {{ __('inventory') }} → {{ __('reassign') }}</span>
                <span class="arr">or</span>
                <span class="step">4. {{ __('Not functioning') }} → {{ __('replacement') }} → {{ __('repair') }} → {{ __('restore') }} → {{ __('reassign') }}</span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
            <a href="{{ route('account-assets.index') }}" class="text-decoration-none">
                <div class="am-stat t-all">
                    <span class="am-icon"><i class="ti ti-packages"></i></span>
                    <div><div class="am-num">{{ $totals['all'] }}</div><div class="am-lbl">{{ __('All assets') }}</div></div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a href="{{ route('account-assets.index', ['status' => 'in_inventory']) }}" class="text-decoration-none">
                <div class="am-stat t-inv">
                    <span class="am-icon"><i class="ti ti-package"></i></span>
                    <div><div class="am-num">{{ $totals['in_inventory'] }}</div><div class="am-lbl">{{ __('In inventory') }}</div></div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a href="{{ route('account-assets.index', ['status' => 'assigned']) }}" class="text-decoration-none">
                <div class="am-stat t-asg">
                    <span class="am-icon"><i class="ti ti-user-check"></i></span>
                    <div><div class="am-num">{{ $totals['assigned'] }}</div><div class="am-lbl">{{ __('With employees') }}</div></div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a href="{{ route('account-assets.index', ['status' => 'under_repair']) }}" class="text-decoration-none">
                <div class="am-stat t-rep">
                    <span class="am-icon"><i class="ti ti-tool"></i></span>
                    <div><div class="am-num">{{ $totals['under_repair'] }}</div><div class="am-lbl">{{ __('Under repair') }}</div></div>
                </div>
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1"><i class="ti ti-device-laptop me-2"></i>{{ __('Asset register') }}</h5>
                <small class="text-muted">{{ __('Document existing assets, assign them, take them back at exit or when not functioning, then restore and reassign.') }}</small>
            </div>
            <form method="GET" class="d-flex gap-2">
                @if($status !== 'all')<input type="hidden" name="status" value="{{ $status }}">@endif
                <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="{{ __('Search name, code, serial, employee') }}" style="min-width:240px">
                <button class="btn btn-sm btn-light border">{{ __('Search') }}</button>
            </form>
        </div>
        <div class="card-body pt-2">
            <ul class="nav nav-tabs am-filter mb-3">
                @foreach(['all' => __('All'), 'in_inventory' => __('Inventory'), 'assigned' => __('Assigned'), 'under_repair' => __('Repair')] as $key => $lbl)
                    <li class="nav-item">
                        <a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ route('account-assets.index', array_filter(['status' => $key === 'all' ? null : $key, 'q' => $q ?: null])) }}">{{ $lbl }}</a>
                    </li>
                @endforeach
            </ul>

            <div class="table-responsive">
                <table class="table am-table">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Asset') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Serial') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Holder') }}</th>
                            <th>{{ __('Condition') }}</th>
                            <th width="180">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assets as $asset)
                            <tr>
                                <td><strong>{{ $asset->asset_code ?: '—' }}</strong></td>
                                <td>
                                    <a href="{{ route('account-assets.show', $asset->id) }}" class="fw-semibold">{{ $asset->name }}</a>
                                    @if($asset->brand || $asset->model)
                                        <div class="small text-muted">{{ trim($asset->brand . ' ' . $asset->model) }}</div>
                                    @endif
                                </td>
                                <td>{{ $asset->category ?: '—' }}</td>
                                <td>{{ $asset->serial_number ?: '—' }}</td>
                                <td><span class="am-badge {{ $asset->statusBadgeClass() }}">{{ $asset->statusLabel() }}</span></td>
                                <td>{{ $asset->holderName() }}</td>
                                <td>{{ $asset->conditionLabel() }}</td>
                                <td class="Action">
                                    <div class="d-flex gap-1 flex-wrap">
                                        <a href="{{ route('account-assets.show', $asset->id) }}" class="btn btn-sm bg-secondary text-white" data-bs-toggle="tooltip" title="{{ __('History') }}">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        @can('Edit Assets')
                                            @if($asset->isAvailable())
                                                <a href="#" class="btn btn-sm bg-info text-white" data-ajax-popup="true" data-size="md"
                                                    data-title="{{ __('Assign to employee') }}"
                                                    data-url="{{ route('account-assets.assign.form', $asset->id) }}"
                                                    data-bs-toggle="tooltip" title="{{ __('Assign') }}">
                                                    <i class="ti ti-user-plus"></i>
                                                </a>
                                            @endif
                                            @if($asset->isAssigned())
                                                <a href="#" class="btn btn-sm bg-warning text-white" data-ajax-popup="true" data-size="lg"
                                                    data-title="{{ __('Take back asset') }}"
                                                    data-url="{{ route('account-assets.return.form', $asset->id) }}"
                                                    data-bs-toggle="tooltip" title="{{ __('Take back') }}">
                                                    <i class="ti ti-transfer-in"></i>
                                                </a>
                                            @endif
                                            @if($asset->isUnderRepair())
                                                <a href="#" class="btn btn-sm bg-success text-white" data-ajax-popup="true" data-size="md"
                                                    data-title="{{ __('Restore after repair') }}"
                                                    data-url="{{ route('account-assets.restore.form', $asset->id) }}"
                                                    data-bs-toggle="tooltip" title="{{ __('Restore to inventory') }}">
                                                    <i class="ti ti-refresh"></i>
                                                </a>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="am-empty">
                                        <i class="ti ti-package-off"></i>
                                        {{ __('No assets yet. Record existing company assets to start the process.') }}
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($assets->hasPages())
                <div class="mt-2">{{ $assets->links() }}</div>
            @endif
        </div>
    </div>
@endsection
