@extends('layouts.admin')
@section('page-title') {{ __('My Assets') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('My Assets') }}</li>
@endsection

@push('css-page')
<style>
    .am-table{margin-bottom:0;width:100%;}
    .am-table thead th{font-size:.72rem;text-transform:uppercase;letter-spacing:.4px;color:#64748b;font-weight:600;background:#f8fafc;padding:12px 16px;border-bottom:1px solid #e2e8f0;white-space:nowrap;}
    .am-table tbody td{padding:14px 16px;vertical-align:middle;border-bottom:1px solid #f1f5f9;color:#0f172a;}
    .am-table tbody tr:last-child td{border-bottom:0;}
    .am-empty{text-align:center;padding:56px 20px;color:#94a3b8;}
    .card > .card-header{display:block;}
</style>
@endpush

@section('content')
    <div class="card">
        <div class="card-header">
            <h5>{{ __('My Assets') }}</h5>
        </div>
        @if($current->count())
            <div class="table-responsive">
                <table class="table am-table">
                    <thead>
                        <tr>
                            <th>{{ __('Asset') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Purchase date') }}</th>
                            <th>{{ __('Support until') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th class="text-end">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($current as $asset)
                            <tr>
                                <td class="fw-semibold">{{ $asset->name }}</td>
                                <td>{{ \Auth::user()->priceFormat($asset->amount) }}</td>
                                <td>{{ $asset->purchase_date ? \Auth::user()->dateFormat($asset->purchase_date) : '—' }}</td>
                                <td>{{ $asset->supported_date ? \Auth::user()->dateFormat($asset->supported_date) : '—' }}</td>
                                <td>{{ $asset->description ?: '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('account-assets.show', $asset->id) }}" class="btn btn-sm btn-primary">{{ __('View') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="am-empty">{{ __('No company assets are assigned to you right now.') }}</div>
        @endif
    </div>
@endsection
