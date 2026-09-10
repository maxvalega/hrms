@extends('layouts.admin')
@section('page-title') {{ __('Manage Assets') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Assets') }}</li>
@endsection

@push('css-page')
<style>
    .am-table{margin-bottom:0;width:100%;}
    .am-table thead th{font-size:.72rem;text-transform:uppercase;letter-spacing:.4px;color:#64748b;font-weight:600;background:#f8fafc;padding:12px 16px;border-bottom:1px solid #e2e8f0;white-space:nowrap;}
    .am-table tbody td{padding:14px 16px;vertical-align:middle;border-bottom:1px solid #f1f5f9;color:#0f172a;}
    .am-table tbody tr:last-child td{border-bottom:0;}
    .am-table td.desc{max-width:280px;white-space:normal;}
    .am-empty{text-align:center;padding:56px 20px;color:#94a3b8;}
    .am-actions{display:flex;align-items:center;justify-content:flex-end;gap:8px;flex-wrap:nowrap;}
    .card > .card-header{display:block;}
</style>
@endpush

@section('action-button')
    @can('Create Assets')
        <a href="#" data-url="{{ route('account-assets.create') }}" data-ajax-popup="true"
            data-title="{{ __('Create Assets') }}" data-size="lg" class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>
    @endcan
@endsection

@section('content')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card">
        <div class="card-header">
            <h5>{{ __('Assigned assets') }}</h5>
        </div>
        @if($assets->count())
            <div class="table-responsive">
                <table class="table am-table">
                    <thead>
                        <tr>
                            <th>{{ __('Asset') }}</th>
                            <th>{{ __('Employee') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Purchase date') }}</th>
                            <th>{{ __('Support until') }}</th>
                            <th>{{ __('Description') }}</th>
                            @if (Gate::check('Edit Assets') || Gate::check('Delete Assets'))
                                <th class="text-end">{{ __('Action') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($assets as $asset)
                            <tr>
                                <td class="fw-semibold">{{ $asset->name }}</td>
                                <td>{{ $asset->assignedEmployeeNames() }}</td>
                                <td>{{ \Auth::user()->priceFormat($asset->amount) }}</td>
                                <td>{{ $asset->purchase_date ? \Auth::user()->dateFormat($asset->purchase_date) : '—' }}</td>
                                <td>{{ $asset->supported_date ? \Auth::user()->dateFormat($asset->supported_date) : '—' }}</td>
                                <td class="desc">{{ $asset->description ?: '—' }}</td>
                                @if (Gate::check('Edit Assets') || Gate::check('Delete Assets'))
                                    <td>
                                        <div class="am-actions">
                                            @can('Edit Assets')
                                                <a href="#" class="btn btn-sm btn-info" data-size="lg"
                                                    data-url="{{ route('account-assets.edit', $asset->id) }}"
                                                    data-ajax-popup="true" data-title="{{ __('Edit Assets') }}">
                                                    <i class="ti ti-pencil"></i>
                                                </a>
                                            @endcan
                                            @can('Delete Assets')
                                                {!! Form::open([
                                                    'method' => 'DELETE',
                                                    'route' => ['account-assets.destroy', $asset->id],
                                                    'id' => 'delete-form-' . $asset->id,
                                                    'class' => 'd-inline',
                                                ]) !!}
                                                <a href="#" class="btn btn-sm btn-danger bs-pass-para">
                                                    <i class="ti ti-trash"></i>
                                                </a>
                                                {!! Form::close() !!}
                                            @endcan
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="am-empty">{{ __('No assets have been assigned yet.') }}</div>
        @endif
    </div>
@endsection
