@extends('layouts.admin')
@section('page-title') {{ __('My Assets') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('My Assets') }}</li>
@endsection

@push('css-page')
<style>
    .am-badge{font-size:.65rem;font-weight:700;padding:3px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:.3px;}
    .am-badge-inventory{background:#dcfce7;color:#166534;}
    .am-badge-assigned{background:#dbeafe;color:#1d4ed8;}
    .am-badge-repair{background:#fef3c7;color:#b45309;}
    .am-badge-retired{background:#e2e8f0;color:#475569;}
    .am-empty{text-align:center;padding:40px 16px;color:#94a3b8;}
    .am-empty i{font-size:2.4rem;opacity:.35;display:block;margin-bottom:8px;}
</style>
@endpush

@section('content')
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">{{ __('Assets currently with you') }}</h5>
            <small class="text-muted">{{ __('Laptops, phones, ID cards and other company assets assigned to you. HR takes these back at exit or if something is not working.') }}</small>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Asset') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th>{{ __('Serial') }}</th>
                        <th>{{ __('Condition') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($current as $asset)
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
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="am-empty">
                                    <i class="ti ti-package-off"></i>
                                    {{ __('No company assets are assigned to you right now.') }}
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($previous->count())
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ __('Previously with you') }}</h5>
                <small class="text-muted">{{ __('Returned at exit, sent for repair, or reassigned.') }}</small>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Asset') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Status now') }}</th>
                            <th></th>
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
