@extends('layouts.admin')

@section('page-title')
    {{ __('Attendance Regularisation') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Attendance Regularisation') }}</li>
@endsection

@section('action-button')
    <a href="#" data-url="{{ route('attendance.regularisation.create') }}" data-ajax-popup="true"
        data-title="{{ __('Request On Ground Regularisation') }}" data-size="lg" data-bs-toggle="tooltip"
        class="btn btn-sm btn-primary" data-bs-original-title="{{ __('Create') }}">
        <i class="ti ti-plus"></i>
    </a>
@endsection

@push('css-page')
<style>
    .ar-reason {
        word-break: break-word;
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    @media (max-width: 767.98px) {
        .ar-desktop-table { display: none !important; }
        .ar-mobile-list { display: block !important; }
    }
    @media (min-width: 768px) {
        .ar-mobile-list { display: none !important; }
        .ar-desktop-table { display: block !important; }
    }
</style>
@endpush

@section('content')
    <div class="row">
        <div class="col-12 mb-3">
            <div class="alert alert-info mb-0">
                {{ __('On Ground is not a leave type. Use this form to regularise attendance when you were on ground / field duty.') }}
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="ar-mobile-list">
                        @forelse ($rows as $row)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="min-w-0">
                                        @if (\Auth::user()->type != 'employee')
                                            <div class="fw-semibold">{{ $row->employee->name ?? '-' }}</div>
                                        @endif
                                        <div class="text-muted small">{{ \Auth::user()->dateFormat($row->date) }} · {{ __('On Ground') }}</div>
                                    </div>
                                    @if ($row->status == 'Approved')
                                        <span class="badge bg-success">{{ __('Approved') }}</span>
                                    @elseif ($row->status == 'Reject')
                                        <span class="badge bg-danger">{{ __('Rejected') }}</span>
                                    @else
                                        <span class="badge bg-warning">{{ __('Pending') }}</span>
                                    @endif
                                </div>
                                <div class="small ar-reason mb-3">{{ $row->reason }}</div>
                                @if (\Auth::user()->type != 'employee' && $row->status == 'Pending')
                                    <a href="#" class="btn btn-sm btn-success"
                                        data-url="{{ route('attendance.regularisation.action', $row->id) }}"
                                        data-ajax-popup="true"
                                        data-title="{{ __('Review Regularisation') }}">
                                        <i class="ti ti-check"></i> {{ __('Review') }}
                                    </a>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted text-center mb-0">{{ __('No requests yet.') }}</p>
                        @endforelse
                    </div>

                    <div class="ar-desktop-table table-responsive">
                        <table class="table mb-0" id="pc-dt-simple">
                            <thead>
                                <tr>
                                    @if (\Auth::user()->type != 'employee')
                                        <th>{{ __('Employee') }}</th>
                                    @endif
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Reason') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    <tr>
                                        @if (\Auth::user()->type != 'employee')
                                            <td>{{ $row->employee->name ?? '-' }}</td>
                                        @endif
                                        <td>{{ \Auth::user()->dateFormat($row->date) }}</td>
                                        <td>{{ __('On Ground') }}</td>
                                        <td style="max-width: 280px; white-space: normal;">{{ $row->reason }}</td>
                                        <td>
                                            @if ($row->status == 'Approved')
                                                <span class="badge bg-success">{{ __('Approved') }}</span>
                                            @elseif ($row->status == 'Reject')
                                                <span class="badge bg-danger">{{ __('Rejected') }}</span>
                                            @else
                                                <span class="badge bg-warning">{{ __('Pending') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if (\Auth::user()->type != 'employee' && $row->status == 'Pending')
                                                <a href="#" class="btn btn-sm btn-success"
                                                    data-url="{{ route('attendance.regularisation.action', $row->id) }}"
                                                    data-ajax-popup="true"
                                                    data-title="{{ __('Review Regularisation') }}"
                                                    data-bs-toggle="tooltip" title="{{ __('Action') }}">
                                                    <i class="ti ti-check"></i>
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">{{ __('No requests yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
