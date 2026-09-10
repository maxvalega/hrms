@extends('layouts.admin')
@section('page-title') {{ __('My Assets') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('My Assets') }}</li>
@endsection

@push('css-page')
<style>
    .am-badge{font-size:.65rem;font-weight:700;padding:3px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:.3px;display:inline-block;white-space:nowrap;}
    .am-badge-inventory{background:#dcfce7;color:#166534;}
    .am-badge-assigned{background:#dbeafe;color:#1d4ed8;}
    .am-badge-repair{background:#fef3c7;color:#b45309;}
    .am-badge-retired{background:#e2e8f0;color:#475569;}
    .am-table{margin-bottom:0;}
    .am-table th{font-size:.7rem;text-transform:uppercase;letter-spacing:.4px;color:#64748b;font-weight:600;background:#fafafa;white-space:nowrap;vertical-align:middle;}
    .am-table td{vertical-align:middle;}
    .am-empty{text-align:center;padding:48px 20px;color:#94a3b8;}
    .am-empty i{font-size:2.6rem;opacity:.35;display:block;margin-bottom:10px;}
    .am-empty p{margin:0;font-size:.9rem;}
</style>
@endpush

@section('content')
    <div class="card mb-3">
        <div class="card-header">
            <div>
                <h5 class="mb-1">{{ __('Assets currently with you') }}</h5>
                <small class="text-muted d-block">{{ __('Company assets assigned to you. HR takes these back at exit or if something is not working.') }}</small>
            </div>
        </div>
        @if($current->count())
            <div class="table-responsive">
                <table class="table am-table">
                    <thead>
                        <tr>
                            <th style="width:110px">{{ __('Code') }}</th>
                            <th>{{ __('Asset') }}</th>
                            <th style="width:140px">{{ __('Category') }}</th>
                            <th style="width:160px">{{ __('Serial') }}</th>
                            <th style="width:120px">{{ __('Condition') }}</th>
                            <th style="width:130px">{{ __('Status') }}</th>
                            <th style="width:100px" class="text-end">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($current as $asset)
                            <tr>
                                <td><strong>{{ $asset->asset_code ?: '—' }}</strong></td>
                                <td>
                                    {{ $asset->name }}
                                    @if($asset->brand || $asset->model)
                                        <div class="small text-muted">{{ trim($asset->brand . ' ' . $asset->model) }}</div>
                                    @endif
                                </td>
                                <td>{{ $asset->category ?: '—' }}</td>
                                <td>{{ $asset->serial_number ?: '—' }}</td>
                                <td>{{ $asset->conditionLabel() }}</td>
                                <td><span class="am-badge {{ $asset->statusBadgeClass() }}">{{ $asset->statusLabel() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('account-assets.show', $asset->id) }}" class="btn btn-sm btn-light border">{{ __('Details') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="am-empty">
                <i class="ti ti-package-off"></i>
                <p>{{ __('No company assets are assigned to you right now.') }}</p>
            </div>
        @endif
    </div>

    @if($previous->count())
        <div class="card">
            <div class="card-header">
                <div>
                    <h5 class="mb-1">{{ __('Previously with you') }}</h5>
                    <small class="text-muted d-block">{{ __('Returned at exit, sent for repair, or reassigned.') }}</small>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table am-table">
                    <thead>
                        <tr>
                            <th style="width:110px">{{ __('Code') }}</th>
                            <th>{{ __('Asset') }}</th>
                            <th style="width:140px">{{ __('Category') }}</th>
                            <th style="width:130px">{{ __('Status now') }}</th>
                            <th style="width:100px" class="text-end">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($previous as $asset)
                            <tr>
                                <td>{{ $asset->asset_code ?: '—' }}</td>
                                <td>{{ $asset->name }}</td>
                                <td>{{ $asset->category ?: '—' }}</td>
                                <td><span class="am-badge {{ $asset->statusBadgeClass() }}">{{ $asset->statusLabel() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('account-assets.show', $asset->id) }}" class="btn btn-sm btn-light border">{{ __('History') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
