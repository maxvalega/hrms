@extends('layouts.admin')

@section('page-title')
    {{ __('Leave Action') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('leave.index') }}">{{ __('Leave') }}</a></li>
    <li class="breadcrumb-item">{{ __('Leave Action') }}</li>
@endsection

@section('action-button')
    <a href="{{ route('leave.index') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
        data-bs-original-title="{{ __('Back') }}">
        <i class="ti ti-arrow-left"></i>
    </a>
@endsection

@push('css-page')
<style>
    .leave-action-detail th,
    .leave-action-detail td {
        vertical-align: top;
        word-break: break-word;
    }
    @media (max-width: 575.98px) {
        .leave-action-detail,
        .leave-action-detail tbody,
        .leave-action-detail tr,
        .leave-action-detail th,
        .leave-action-detail td {
            display: block;
            width: 100% !important;
        }
        .leave-action-detail tr {
            border-bottom: 1px solid #e9ecef;
            padding: 0.65rem 0;
        }
        .leave-action-detail th {
            border: 0 !important;
            padding-bottom: 0.15rem !important;
            background: transparent !important;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            color: #6c757d;
        }
        .leave-action-detail td {
            border: 0 !important;
            padding-top: 0.15rem !important;
            font-size: 0.95rem;
        }
        .leave-action-footer .btn {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
    @php
        $startDate = \Carbon\Carbon::parse($leave->start_date);
        $endDate = \Carbon\Carbon::parse($leave->end_date);
        $totalDays = $leave->total_leave_days ?? $startDate->diffInDays($endDate) + 1;

        $dayTypeDisplay = __('Full Day');
        if (($leave->day_type ?? 'full_day') === 'first_half') {
            $dayTypeDisplay = __('First Half');
        } elseif (($leave->day_type ?? 'full_day') === 'second_half') {
            $dayTypeDisplay = __('Second Half');
        }

        $substituteEmployee = $leave->substitute_employee_id ? \App\Models\Employee::find($leave->substitute_employee_id) : null;
        $department = $employee && $employee->department_id ? optional(\App\Models\Department::find($employee->department_id))->name : null;
        $designation = $employee && $employee->designation_id ? optional(\App\Models\Designation::find($employee->designation_id))->name : null;
    @endphp

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Leave Request Details') }}</h5>
                </div>

                {{ Form::open(['url' => 'leave/changeaction', 'method' => 'post']) }}
                @csrf
                <div class="card-body px-3 px-sm-4">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 leave-action-detail">
                            <tbody>
                                <tr>
                                    <th class="bg-light" width="30%">{{ __('Employee') }}</th>
                                    <td>{{ !empty($employee->name) ? $employee->name : '-' }}</td>
                                </tr>
                                @if (!empty($employmentStatus))
                                    <tr>
                                        <th class="bg-light">{{ __('Employee Type / Status') }}</th>
                                        <td>
                                            <span class="badge bg-{{ $employmentStatus['badge'] }}">{{ $employmentStatus['label'] }}</span>
                                            @if(!empty($employmentStatus['type_code']))
                                                <small class="text-muted ms-1">({{ $employmentStatus['type_code'] }})</small>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                @if ($designation || $department)
                                    <tr>
                                        <th class="bg-light">{{ __('Position') }}</th>
                                        <td>{{ $designation ?? '' }}{{ $designation && $department ? ' - ' : '' }}{{ $department ?? '' }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <th class="bg-light">{{ __('Leave Type') }}</th>
                                    <td>{{ !empty($leavetype->title) ? $leavetype->title : '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">{{ __('Applied On') }}</th>
                                    <td>{{ \Auth::user()->dateFormat($leave->applied_on) }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">{{ __('Start Date') }}</th>
                                    <td>{{ \Auth::user()->dateFormat($leave->start_date) }} ({{ $startDate->format('l') }})</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">{{ __('End Date') }}</th>
                                    <td>{{ \Auth::user()->dateFormat($leave->end_date) }} ({{ $endDate->format('l') }})</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">{{ __('Duration') }}</th>
                                    <td>{{ $totalDays }} {{ $totalDays == 1 ? __('Day') : __('Days') }} ({{ $dayTypeDisplay }})</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">{{ __('Leave Reason') }}</th>
                                    <td>{{ !empty($leave->leave_reason) ? $leave->leave_reason : '-' }}</td>
                                </tr>
                                @if ($substituteEmployee)
                                    <tr>
                                        <th class="bg-light">{{ __('Substitute') }}</th>
                                        <td>
                                            {{ $substituteEmployee->name }}
                                            @if ($leave->substitute_status === 'Accepted')
                                                <span class="badge bg-success p-2 px-3">{{ __('Accepted') }}</span>
                                            @elseif($leave->substitute_status === 'Rejected')
                                                <span class="badge bg-danger p-2 px-3">{{ __('Rejected') }}</span>
                                            @else
                                                <span class="badge bg-warning p-2 px-3">{{ __('Pending') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                @if (!empty($leave->medical_certificate))
                                    <tr>
                                        <th class="bg-light">{{ __('Medical Certificate') }}</th>
                                        <td>
                                            @php
                                                $certPath = \Storage::url($leave->medical_certificate);
                                            @endphp
                                            <a class="btn btn-sm btn-warning" href="{{ $certPath }}" target="_blank">
                                                <i class="ti ti-eye"></i> {{ __('View') }}
                                            </a>
                                            @if ($leave->certificate_verified)
                                                <span class="badge bg-success p-2 px-3">{{ __('Verified') }}</span>
                                            @else
                                                <span class="badge bg-warning p-2 px-3">{{ __('Pending') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                @if (!empty($leave->remark))
                                    <tr>
                                        <th class="bg-light">{{ __('Remark') }}</th>
                                        <td>{{ $leave->remark }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <th class="bg-light">{{ __('Status') }}</th>
                                    <td>
                                        @if ($leave->status == 'Pending')
                                            <span class="badge bg-warning p-2 px-3">{{ $leave->status }}</span>
                                        @elseif($leave->status == 'Approved')
                                            <span class="badge bg-success p-2 px-3">{{ $leave->status }}</span>
                                        @else
                                            <span class="badge bg-danger p-2 px-3">{{ $leave->status }}</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <input type="hidden" value="{{ $leave->id }}" name="leave_id">
                </div>

                @php
                    $canRevokeApprovedFuture = ($canTakeAction ?? false)
                        && $leave->status === 'Approved'
                        && \Carbon\Carbon::parse($leave->start_date)->startOfDay()->gte(\Carbon\Carbon::today());
                @endphp
                @if (($canTakeAction ?? false) && $leave->status === 'Pending')
                    <div class="card-footer leave-action-footer d-flex flex-column flex-sm-row justify-content-sm-end gap-2">
                        <button type="submit" class="btn btn-success" name="status" value="Approved">
                            <i class="ti ti-check me-1"></i>{{ __('Accept Leave') }}
                        </button>
                        <button type="submit" class="btn btn-danger" name="status" value="Reject">
                            <i class="ti ti-x me-1"></i>{{ __('Reject Leave') }}
                        </button>
                    </div>
                @elseif ($canRevokeApprovedFuture)
                    <div class="card-footer leave-action-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                        <span class="text-muted small">
                            <i class="ti ti-info-circle me-1"></i>{{ __('This leave is already approved. You can reject/revoke it since the leave start date has not passed yet.') }}
                        </span>
                        <button type="submit" class="btn btn-danger" name="status" value="Reject" onclick="return confirm('{{ __('Are you sure you want to revoke/reject this approved leave?') }}')">
                            <i class="ti ti-x me-1"></i>{{ __('Revoke / Reject Leave') }}
                        </button>
                    </div>
                @else
                    <div class="card-footer">
                        <div class="alert alert-info mb-0">
                            {{ __('This leave request is already processed or you are not authorized to take action.') }}
                        </div>
                    </div>
                @endif
                {{ Form::close() }}
            </div>
        </div>
    </div>
@endsection
