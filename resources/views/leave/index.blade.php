@extends('layouts.admin')

@section('page-title')
    {{ __('Manage Leave') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Leave ') }}</li>
@endsection

@section('action-button')
    <div class="leave-action-btns d-flex flex-wrap gap-1 justify-content-end">
        <a href="{{ route('leave.export') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
            data-bs-original-title="{{ __('Export') }}">
            <i class="ti ti-file-export"></i>
        </a>

        <a href="{{ route('leave.calender') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
            data-bs-original-title="{{ __('Calendar View') }}">
            <i class="ti ti-calendar"></i>
        </a>

        <a href="#" data-url="{{ route('leave.claim.compensatory') }}" data-ajax-popup="true"
            data-title="{{ __('Claim Compensatory Leave') }}" data-size="lg" data-bs-toggle="tooltip"
            class="btn btn-sm btn-info" data-bs-original-title="{{ __('Claim Comp Leave') }}">
            <i class="ti ti-gift"></i>
        </a>

        @if(!empty($isSpectal) && \Auth::user()->type != 'employee')
            <a href="#" data-url="{{ route('leave.opening.balance') }}" data-ajax-popup="true"
                data-title="{{ __('Set PL Opening Balance') }}" data-size="lg" data-bs-toggle="tooltip"
                class="btn btn-sm btn-secondary" data-bs-original-title="{{ __('PL Opening Balance') }}">
                <i class="ti ti-adjustments"></i>
            </a>
            <a href="#" data-url="{{ route('leave.bereavement.grant') }}" data-ajax-popup="true"
                data-title="{{ __('Grant Bereavement Leave') }}" data-size="lg" data-bs-toggle="tooltip"
                class="btn btn-sm btn-warning" data-bs-original-title="{{ __('Grant Bereavement') }}">
                <i class="ti ti-heart"></i>
            </a>
        @endif

        @can('Create Leave')
            <a href="#" data-url="{{ route('leave.create') }}" data-ajax-popup="true"
                data-title="{{ __('Create New Leave') }}" data-size="lg" data-bs-toggle="tooltip"
                class="btn btn-sm btn-primary" data-bs-original-title="{{ __('Create') }}">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection

@push('css-page')
<style>
    .leave-balance-card h6 { word-break: break-word; }
    .leave-balance-card .stat-label { font-size: 0.72rem; line-height: 1.2; }
    .leave-mobile-card .leave-reason {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
    }
    .leave-table-wrap { -webkit-overflow-scrolling: touch; }
    .leave-table-wrap table { min-width: 720px; }
    .leave-action-cell { white-space: nowrap; }
    .leave-action-cell .action-btn { display: inline-flex; margin: 0 2px 2px 0 !important; }
    .leave-action-cell .action-btn a.mx-3 { margin-left: 0 !important; margin-right: 0 !important; }
    @media (max-width: 767.98px) {
        .leave-alert-stack {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 0.75rem;
            text-align: left;
        }
        .leave-alert-stack .btn { width: 100%; }
        .leave-balance-card { height: 100%; }
        .leave-desktop-table { display: none !important; }
        .leave-mobile-list { display: block !important; }
        .leave-action-btns .btn { min-width: 36px; }
    }
    @media (min-width: 768px) {
        .leave-mobile-list { display: none !important; }
        .leave-desktop-table { display: block !important; }
    }
</style>
@endpush

@section('content')
    <div class="row">
        @if(!empty($isSpectal) && !empty($employmentStatus))
            <div class="col-12 mb-3">
                <div class="alert alert-{{ $employmentStatus['badge'] === 'success' ? 'success' : ($employmentStatus['badge'] === 'warning' ? 'warning' : 'info') }} d-flex leave-alert-stack align-items-center justify-content-between mb-0">
                    <div class="me-md-3">
                        <strong>{{ __('Employment Status') }}:</strong>
                        <span class="badge bg-{{ $employmentStatus['badge'] }} ms-1">{{ $employmentStatus['label'] }}</span>
                        @if(!empty($employmentStatus['type_code']))
                            <small class="text-muted ms-2">({{ $employmentStatus['type_code'] }})</small>
                        @endif
                        @if(!empty($employmentStatus['note']))
                            <div class="small mt-1">{{ $employmentStatus['note'] }}</div>
                        @endif
                    </div>
                    <small class="text-muted text-md-end">{{ $date['label'] ?? '' }}</small>
                </div>
            </div>
        @endif

        @if(!empty($isSpectal))
            <div class="col-12 mb-3">
                <div class="alert alert-secondary mb-0 d-flex leave-alert-stack align-items-center justify-content-between">
                    <span class="me-md-3">{{ __('On Ground is not leave — request attendance regularisation instead.') }}</span>
                    <a href="{{ route('attendance.regularisation.index') }}" class="btn btn-sm btn-outline-dark flex-shrink-0">
                        {{ __('On Ground Regularisation') }}
                    </a>
                </div>
            </div>
            <div class="col-12 mb-3">
                <div class="alert alert-info mb-0">
                    <strong>{{ __('Casual Leave (CL)') }}:</strong>
                    {{ __('Not shown as an annual bank. CL is only available during May–July. Balance cards for CL appear automatically in that window.') }}
                </div>
            </div>
        @endif

        @if(!empty($isSpectal) && \Auth::user()->type != 'employee' && isset($previewEmployees))
            <div class="col-12 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-3">
                        <form method="get" action="{{ route('leave.index') }}" class="row g-2 align-items-end">
                            <div class="col-12 col-md-8">
                                <label class="form-label fw-semibold mb-1">{{ __('Leave balance dashboard') }}</label>
                                <select name="balance_employee_id" class="form-control form-select" style="min-height:44px;font-size:16px;" onchange="this.form.submit()">
                                    <option value="">{{ __('Select employee…') }}</option>
                                    @foreach($previewEmployees as $id => $name)
                                        <option value="{{ $id }}" @selected((int)($previewEmployeeId ?? 0) === (int)$id)>
                                            {{ $name }}@if(!empty($selfEmployeeId) && (int)$selfEmployeeId === (int)$id) ({{ __('You') }})@endif
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">
                                    @if(!empty($selfEmployeeId))
                                        {{ __('Your own balances load by default. Switch employee to preview someone else.') }}
                                    @else
                                        {{ __('No employee profile linked to this HR login — pick an employee to view balances. Link Rohan’s user to an employee record if his personal dashboard should appear automatically.') }}
                                    @endif
                                </small>
                            </div>
                            <div class="col-12 col-md-4">
                                <button type="submit" class="btn btn-primary w-100">{{ __('Show Balances') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if(!empty($leaveBalance) && count($leaveBalance) > 0)
            <div class="col-12 mb-3">
                <div class="card">
                    <div class="card-header d-flex flex-column flex-sm-row align-items-sm-center justify-content-sm-between gap-1 bg-light">
                        <h5 class="card-title mb-0">{{ __('Leave Balance Summary') }}</h5>
                        <small class="text-muted">{{ $date['label'] ?? __('Current Year') }} | {{ $date['year'] ?? date('Y') }}</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($leaveBalance as $balance)
                                @php
                                    $leaveTypeName = strtolower((string) ($balance['leave_type'] ?? ''));
                                    $isVacationLeave = preg_match('/(vacation|vaction|vactine|vacatine|vacat)/', $leaveTypeName) === 1;
                                    $policyCode = $balance['policy_code'] ?? '';
                                    $isProbationView = !empty($employmentStatus['on_probation']);
                                    $isSpectalPl = !empty($isSpectal) && $policyCode === 'pl';
                                @endphp
                                <div class="col-12 col-sm-6 col-xl-4">
                                    <div class="border rounded p-3 leave-balance-card h-100 {{ $isProbationView ? 'border-info' : '' }}" style="{{ $isProbationView ? 'background:#f0f9ff;' : '' }}">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <div class="min-w-0 pe-2">
                                                <h6 class="mb-0">{{ $balance['leave_type'] }}</h6>
                                                @if ($isSpectalPl)
                                                    <small class="text-muted d-block">
                                                        {{ __('Opening') }} {{ $balance['opening_balance'] ?? 0 }}
                                                        + {{ __('Accrued') }} {{ $balance['accrued_to_date'] ?? 0 }}
                                                    </small>
                                                @elseif(!empty($balance['note']))
                                                    <small class="text-muted d-block">{{ $balance['note'] }}</small>
                                                @endif
                                            </div>
                                            <span class="badge bg-primary flex-shrink-0">{{ $balance['total'] }} {{ __('days') }}</span>
                                        </div>
                                        @if(empty($isSpectal))
                                            <small class="text-muted d-block mb-2">
                                                {{ __('Mode') }}:
                                                @php
                                                    $mode = $balance['credit_mode'] ?? 'lump_sum';
                                                    $modeLabel = match ($mode) {
                                                        'monthly' => __('Monthly accrual'),
                                                        'event' => __('Event-based'),
                                                        'seasonal' => __('Seasonal window'),
                                                        'earned' => __('As earned'),
                                                        default => __('Loaded for period'),
                                                    };
                                                @endphp
                                                {{ $modeLabel }}
                                            </small>
                                        @endif
                                        <div class="row text-center g-2 mt-1">
                                            <div class="col-4">
                                                <small class="text-muted d-block stat-label">{{ __('Used') }}</small>
                                                <h6 class="text-danger mb-0">{{ $balance['used'] }}</h6>
                                            </div>
                                            <div class="col-4">
                                                <small class="text-muted d-block stat-label">{{ __('Pending') }}</small>
                                                <h6 class="text-warning mb-0">{{ $balance['pending'] ?? 0 }}</h6>
                                            </div>
                                            <div class="col-4">
                                                <small class="text-muted d-block stat-label">{{ __('Available') }}</small>
                                                <h6 class="text-success mb-0">{{ $balance['available'] }}</h6>
                                            </div>
                                        </div>
                                        @if (empty($isSpectal) && ($isVacationLeave || $policyCode === 'pl'))
                                            <div class="row text-center g-2 mt-2">
                                                <div class="col-6">
                                                    <small class="text-muted d-block stat-label">{{ __('Carry Forward') }}</small>
                                                    <h6 class="text-secondary mb-0">{{ $balance['carry_forward'] ?? 0 }}</h6>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted d-block stat-label">{{ __('Encashable') }}</small>
                                                    <h6 class="text-dark mb-0">{{ $balance['encashable_leave'] ?? 0 }}</h6>
                                                </div>
                                            </div>
                                        @endif
                                        @php
                                            $percentage = $balance['total'] > 0 ? round(($balance['used'] / $balance['total']) * 100) : 0;
                                        @endphp
                                        <div class="progress mt-3" style="height: 8px;">
                                            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ min($percentage, 100) }}%"
                                                 aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    {{-- Mobile cards --}}
                    <div class="leave-mobile-list">
                        @forelse ($leaves as $leave)
                            @php $empStatus = $leave->employment_status_meta ?? null; @endphp
                            <div class="border rounded p-3 mb-3 leave-mobile-card">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="min-w-0">
                                        @if (($showEmployeeColumn ?? false) || \Auth::user()->type != 'employee')
                                            <div class="fw-semibold">{{ !empty($leave->employees) ? $leave->employees->name : '-' }}</div>
                                        @endif
                                        <div class="text-primary">{{ !empty($leave->leaveType) ? $leave->leaveType->title : '' }}</div>
                                    </div>
                                    @if ($leave->status == 'Pending')
                                        <span class="badge bg-warning flex-shrink-0">{{ $leave->status }}</span>
                                    @elseif($leave->status == 'Approved')
                                        <span class="badge bg-success flex-shrink-0">{{ $leave->status }}</span>
                                    @else
                                        <span class="badge bg-danger flex-shrink-0">{{ $leave->status }}</span>
                                    @endif
                                </div>
                                @if (!empty($isSpectal) && !empty($empStatus) && ((\Auth::user()->type != 'employee') || ($showEmployeeColumn ?? false)))
                                    <div class="mb-2">
                                        <span class="badge bg-{{ $empStatus['badge'] }}">{{ $empStatus['label'] }}</span>
                                    </div>
                                @endif
                                <div class="small text-muted mb-2">
                                    {{ \Auth::user()->dateFormat($leave->start_date) }}
                                    → {{ \Auth::user()->dateFormat($leave->end_date) }}
                                    · {{ $leave->total_leave_days }} {{ __('day(s)') }}
                                    · {{ ucwords(str_replace('_', ' ', $leave->day_type ?? '')) }}
                                </div>
                                <div class="small leave-reason mb-3">{{ $leave->leave_reason }}</div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ URL::to('leave/' . $leave->id . '/action') }}" class="btn btn-sm btn-success">
                                        <i class="ti ti-caret-right"></i> {{ __('Action') }}
                                    </a>
                                    @if (\Auth::user()->type != 'employee')
                                        @can('Edit Leave')
                                            <a href="#" class="btn btn-sm btn-info"
                                                data-url="{{ URL::to('leave/' . $leave->id . '/edit') }}"
                                                data-ajax-popup="true" data-size="lg" data-title="{{ __('Edit Leave') }}">
                                                <i class="ti ti-pencil"></i> {{ __('Edit') }}
                                            </a>
                                        @endcan
                                        @can('Delete Leave')
                                            {!! Form::open([
                                                'method' => 'DELETE',
                                                'route' => ['leave.destroy', $leave->id],
                                                'id' => 'delete-form-m-' . $leave->id,
                                                'class' => 'd-inline',
                                            ]) !!}
                                            <a href="#" class="btn btn-sm btn-danger bs-pass-para">
                                                <i class="ti ti-trash"></i> {{ __('Delete') }}
                                            </a>
                                            {!! Form::close() !!}
                                        @endcan
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-muted text-center mb-0">{{ __('No leave requests.') }}</p>
                        @endforelse
                    </div>

                    {{-- Desktop / tablet table --}}
                    <div class="leave-desktop-table table-responsive leave-table-wrap">
                        <table class="table mb-0" id="pc-dt-simple">
                            <thead>
                                <tr>
                                    @if (($showEmployeeColumn ?? false) || \Auth::user()->type != 'employee')
                                        <th>{{ __('Employee') }}</th>
                                    @endif
                                    @if (!empty($isSpectal) && ((\Auth::user()->type != 'employee') || ($showEmployeeColumn ?? false)))
                                        <th>{{ __('Employee Type') }}</th>
                                    @endif
                                    <th>{{ __('Leave Type') }}</th>
                                    <th>{{ __('Applied On') }}</th>
                                    <th>{{ __('Start Date') }}</th>
                                    <th>{{ __('End Date') }}</th>
                                    <th>{{ __('Day Type') }}</th>
                                    <th>{{ __('Total Days') }}</th>
                                    <th>{{ __('Leave Reason') }}</th>
                                    <th>{{ __('status') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($leaves as $leave)
                                    <tr>
                                        @if (($showEmployeeColumn ?? false) || \Auth::user()->type != 'employee')
                                            <td>{{ !empty($leave->employee_id) && !empty($leave->employees) ? $leave->employees->name : '-' }}</td>
                                        @endif
                                        @if (!empty($isSpectal) && ((\Auth::user()->type != 'employee') || ($showEmployeeColumn ?? false)))
                                            @php $empStatus = $leave->employment_status_meta ?? null; @endphp
                                            <td>
                                                @if (!empty($empStatus))
                                                    <span class="badge bg-{{ $empStatus['badge'] }}">{{ $empStatus['label'] }}</span>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        @endif
                                        <td>{{ !empty($leave->leave_type_id) ? $leave->leaveType->title : '' }}</td>
                                        <td>{{ \Auth::user()->dateFormat($leave->applied_on) }}</td>
                                        <td>{{ \Auth::user()->dateFormat($leave->start_date) }}</td>
                                        <td>{{ \Auth::user()->dateFormat($leave->end_date) }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $leave->day_type ?? '')) }}</td>
                                        <td>{{ $leave->total_leave_days }}</td>
                                        <td style="max-width: 220px; white-space: normal;">{{ $leave->leave_reason }}</td>
                                        <td>
                                            @if ($leave->status == 'Pending')
                                                <div class="badge bg-warning p-2 px-3">{{ $leave->status }}</div>
                                            @elseif($leave->status == 'Approved')
                                                <div class="badge bg-success p-2 px-3">{{ $leave->status }}</div>
                                            @elseif($leave->status == 'Reject')
                                                <div class="badge bg-danger p-2 px-3">{{ $leave->status }}</div>
                                            @endif
                                        </td>
                                        <td class="Action leave-action-cell">
                                            <div class="action-btn me-1">
                                                <a href="{{ URL::to('leave/' . $leave->id . '/action') }}" class="btn btn-sm bg-success align-items-center"
                                                    data-bs-toggle="tooltip" data-bs-original-title="{{ __('Manage Leave') }}">
                                                    <span class="text-white"><i class="ti ti-caret-right"></i></span>
                                                </a>
                                            </div>
                                            @if (\Auth::user()->type != 'employee')
                                                @can('Edit Leave')
                                                    <div class="action-btn me-1">
                                                        <a href="#" class="btn btn-sm bg-info align-items-center"
                                                            data-url="{{ URL::to('leave/' . $leave->id . '/edit') }}"
                                                            data-ajax-popup="true" data-size="lg" data-bs-toggle="tooltip"
                                                            data-title="{{ __('Edit Leave') }}"
                                                            data-bs-original-title="{{ __('Edit') }}">
                                                            <span class="text-white"><i class="ti ti-pencil"></i></span>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('Delete Leave')
                                                    <div class="action-btn">
                                                        {!! Form::open([
                                                            'method' => 'DELETE',
                                                            'route' => ['leave.destroy', $leave->id],
                                                            'id' => 'delete-form-' . $leave->id,
                                                        ]) !!}
                                                        <a href="#" class="btn btn-sm bg-danger align-items-center bs-pass-para"
                                                            data-bs-toggle="tooltip" data-bs-original-title="Delete" aria-label="Delete">
                                                            <span class="text-white"><i class="ti ti-trash"></i></span>
                                                        </a>
                                                        {!! Form::close() !!}
                                                    </div>
                                                @endcan
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script>
        $(document).on('change', '#employee_id', function() {
            var employee_id = $(this).val();

            $.ajax({
                url: '{{ route('leave.jsoncount') }}',
                type: 'POST',
                data: {
                    "employee_id": employee_id,
                    "_token": "{{ csrf_token() }}",
                },
                success: function(data) {
                    var oldval = $('#leave_type_id').val();
                    $('#leave_type_id').empty();
                    $('#leave_type_id').append(
                        '<option value="">{{ __('Select Leave Type') }}</option>');

                    $.each(data, function(key, value) {
                        var used = parseFloat(value.total_leave || 0);
                        var pending = parseFloat(value.pending_leave || 0);
                        var available = parseFloat(value.available_leave || 0);
                        var annual = parseFloat(value.annual_leave || value.days || 0);
                        var monthly = parseFloat(value.monthly_accrual || 0);
                        var creditMode = value.credit_mode === 'monthly' ? '{{ __('Monthly') }}' : '{{ __('Lump Sum') }}';
                        var availableLabel = value.credit_mode === 'lump_sum'
                            ? '{{ __('Available (Total - Pending)') }}'
                            : '{{ __('Available') }}';

                        var optionText = value.title + ' (' +
                            '{{ __('Mode') }}: ' + creditMode + ', ' +
                            '{{ __('Total') }}: ' + annual + ', ' +
                            '{{ __('Used') }}: ' + used + ', ' +
                            '{{ __('Pending') }}: ' + pending + ', ' +
                            availableLabel + ': ' + available + ', ' +
                            '{{ __('Monthly') }}: ' + monthly +
                            ')';

                        if (available <= 0) {
                            $('#leave_type_id').append('<option value="' + value.id + '" disabled>' + optionText + '</option>');
                        } else {
                            $('#leave_type_id').append('<option value="' + value.id + '">' + optionText + '</option>');
                        }

                        if (oldval) {
                            if (oldval == value.id) {
                                $("#leave_type_id option[value=" + oldval + "]").attr(
                                    "selected", "selected");
                            }
                        }
                    });
                }
            });
        });

        // Bereavement grant modal (works even when inline scripts in ajax HTML are stripped)
        function syncBereavementGrantPreview() {
            var $emp = $('#bereavement_employee_id');
            if (!$emp.length) return;
            var val = $emp.val();
            var text = $emp.find('option:selected').text();
            if (val) {
                $('#grant-to-name').text(text);
                $('#grant-to-preview').removeClass('d-none');
            } else {
                $('#grant-to-preview').addClass('d-none');
            }
        }

        function syncBereavementEndDefault() {
            var start = $('#bereavement_start_date').val();
            if (!start) return;
            var $end = $('#bereavement_end_date');
            if ($end.val()) return;
            var d = new Date(start + 'T00:00:00');
            d.setDate(d.getDate() + 6); // 7 calendar days inclusive
            var yyyy = d.getFullYear();
            var mm = String(d.getMonth() + 1).padStart(2, '0');
            var dd = String(d.getDate()).padStart(2, '0');
            $end.val(yyyy + '-' + mm + '-' + dd);
        }

        $(document).on('change', '#bereavement_employee_id', syncBereavementGrantPreview);
        $(document).on('change', '#bereavement_start_date', syncBereavementEndDefault);
        $(document).on('shown.bs.modal', '#commonModal', function () {
            setTimeout(function () {
                syncBereavementGrantPreview();
                if (!$('#bereavement_start_date').val()) {
                    var now = new Date();
                    var yyyy = now.getFullYear();
                    var mm = String(now.getMonth() + 1).padStart(2, '0');
                    var dd = String(now.getDate()).padStart(2, '0');
                    $('#bereavement_start_date').val(yyyy + '-' + mm + '-' + dd);
                    syncBereavementEndDefault();
                }
            }, 50);
        });

        $(document).on('submit', '#grant-bereavement-form', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $('#grant-bereavement-submit');
            var $alert = $('#grant-bereavement-alert');
            $alert.addClass('d-none').removeClass('alert-danger alert-success').text('');
            $btn.prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (res) {
                    var msg = (res && res.success) ? res.success : '{{ __('Bereavement leave granted.') }}';
                    $alert.removeClass('d-none alert-danger').addClass('alert-success').text(msg);
                    if (typeof show_toastr === 'function') show_toastr('Success', msg, 'success');
                    setTimeout(function () {
                        $('#commonModal').modal('hide');
                        window.location.reload();
                    }, 900);
                },
                error: function (xhr) {
                    var msg = '{{ __('Unable to grant bereavement leave.') }}';
                    if (xhr.responseJSON) {
                        msg = xhr.responseJSON.error || xhr.responseJSON.message || msg;
                    }
                    $alert.removeClass('d-none alert-success').addClass('alert-danger').text(msg);
                    if (typeof show_toastr === 'function') show_toastr('Error', msg, 'error');
                    $btn.prop('disabled', false);
                }
            });
        });
    </script>
@endpush
