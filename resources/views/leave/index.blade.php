@extends('layouts.admin')

@section('page-title')
    {{ __('Manage Leave') }}
@endsection


@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Leave ') }}</li>
@endsection

@section('action-button')
    <a href="{{ route('leave.export') }}" class="btn btn-sm btn-primary me-1" data-bs-toggle="tooltip"
        data-bs-original-title="{{ __('Export') }}">
        <i class="ti ti-file-export"></i>
    </a>

    <a href="{{ route('leave.calender') }}" class="btn btn-sm btn-primary me-1" data-bs-toggle="tooltip"
        data-bs-original-title="{{ __('Calendar View') }}">
        <i class="ti ti-calendar"></i>
    </a>

    <a href="#" data-url="{{ route('leave.claim.compensatory') }}" data-ajax-popup="true"
        data-title="{{ __('Claim Compensatory Leave') }}" data-size="lg" data-bs-toggle="tooltip" title=""
        class="btn btn-sm btn-info me-1" data-bs-original-title="{{ __('Claim Comp Leave') }}">
        <i class="ti ti-gift"></i>
    </a>

    @can('Create Leave')
        <a href="#" data-url="{{ route('leave.create') }}" data-ajax-popup="true"
            data-title="{{ __('Create New Leave') }}" data-size="lg" data-bs-toggle="tooltip" title=""
            class="btn btn-sm btn-primary" data-bs-original-title="{{ __('Create') }}">
            <i class="ti ti-plus"></i>
        </a>
    @endcan
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12 mb-3">
            <div class="card">
                <div class="card-body py-2">
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <span class="badge {{ !empty($leavePolicy['carry_forward']) ? 'bg-success' : 'bg-secondary' }}">
                            {{ __('Carry Forward') }}: {{ !empty($leavePolicy['carry_forward']) ? __('Enabled') : __('Disabled') }}
                        </span>
                        <span class="badge {{ !empty($leavePolicy['encashment']) ? 'bg-success' : 'bg-secondary' }}">
                            {{ __('Encashment') }}: {{ !empty($leavePolicy['encashment']) ? __('Enabled') : __('Disabled') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($leaveBalance) && count($leaveBalance) > 0)
            <div class="col-xl-12 mb-3">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between bg-light">
                        <h5 class="card-title mb-0">{{ __('Leave Balance Summary') }}</h5>
                        <small class="text-muted">{{ __('Current Year') }} | {{ \Carbon\Carbon::parse($date['start_date'] ?? date('Y-m-d'))->format('Y') }}</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($leaveBalance as $balance)
                                @php
                                    $leaveTypeName = strtolower((string) ($balance['leave_type'] ?? ''));
                                    $isVacationLeave = preg_match('/(vacation|vaction|vactine|vacatine|vacat)/', $leaveTypeName) === 1;
                                @endphp
                                <div class="col-lg-4 col-md-6 mb-3">
                                    <div class="border rounded p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-0">{{ $balance['leave_type'] }}</h6>
                                            </div>
                                            <span class="badge bg-primary">{{ $balance['total'] }} {{ __('days') }}</span>
                                        </div>
                                        <small class="text-muted d-block">{{ __('Mode') }}: {{ ($balance['credit_mode'] ?? 'lump_sum') === 'monthly' ? __('Monthly') : __('Lump Sum') }}</small>
                                        <div class="mt-3">
                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <div class="mb-2">
                                                        <small class="text-muted d-block">{{ __('Monthly Accrual') }}</small>
                                                        <h6 class="text-primary">{{ $balance['monthly_accrual'] ?? 0 }}</h6>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="mb-2">
                                                        <small class="text-muted d-block">{{ __('Used') }}</small>
                                                        <h6 class="text-danger">{{ $balance['used'] }}</h6>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="mb-2">
                                                        <small class="text-muted d-block">{{ __('Pending') }}</small>
                                                        <h6 class="text-warning">{{ $balance['pending'] ?? 0 }}</h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row text-center mt-1">
                                                <div class="col-6">
                                                    <small class="text-muted d-block">{{ __('Total Annual Leave') }}</small>
                                                    <h6 class="text-info">{{ $balance['total'] }}</h6>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted d-block">
                                                        {{ __('Available Leave (Total - Used - Pending)') }}
                                                    </small>
                                                    <h6 class="text-success">{{ $balance['available'] }}</h6>
                                                </div>
                                            </div>
                                            @if ($isVacationLeave)
                                                <div class="row text-center mt-1">
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">{{ __('Carry Forward') }}</small>
                                                        <h6 class="text-secondary">{{ $balance['carry_forward'] ?? 0 }}</h6>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">{{ __('Encashable') }}</small>
                                                        <h6 class="text-dark">{{ $balance['encashable_leave'] ?? 0 }}</h6>
                                                    </div>
                                                </div>
                                                @if (!empty($leavePolicy['carry_forward']) && (float)($balance['carry_forward'] ?? 0) <= 0)
                                                    <small class="text-muted d-block mt-1">
                                                        {{ __('Carry forward is 0 because previous leave cycle had no remaining eligible balance (or employee joined in current cycle).') }}
                                                    </small>
                                                @endif
                                            @endif
                                            <!-- Progress Bar -->
                                            @php
                                                $percentage = $balance['total'] > 0 ? round(($balance['used'] / $balance['total']) * 100) : 0;
                                            @endphp
                                            <div class="progress mt-2" style="height: 20px;">
                                                <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $percentage }}%" 
                                                     aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                                                    {{ $percentage }}%
                                                </div>
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

        <div class="col-xl-12">
            <div class="card">
                <div class="card-header card-body table-border-style">
                    {{-- <h5> </h5> --}}
                    <div class="table-responsive">
                        <table class="table" id="pc-dt-simple">
                            <thead>
                                <tr>
                                    @if (($showEmployeeColumn ?? false) || \Auth::user()->type != 'employee')
                                        <th>{{ __('Employee') }}</th>
                                    @endif
                                    <th>{{ __('Leave Type') }}</th>
                                    <th>{{ __('Applied On') }}</th>
                                    <th>{{ __('Start Date') }}</th>
                                    <th>{{ __('End Date') }}</th>
                                    <th>{{ __('Day Type') }}</th>
                                    <th>{{ __('Total Days') }}</th>
                                    <th>{{ __('Leave Reason') }}</th>
                                    <th>{{ __('status') }}</th>
                                    <th width="200px">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($leaves as $leave)
                                    <tr>
                                        @if (($showEmployeeColumn ?? false) || \Auth::user()->type != 'employee')
                                            <td>{{ !empty($leave->employee_id) && !empty($leave->employees) ? $leave->employees->name : '-' }}
                                            </td>
                                        @endif
                                        <td>{{ !empty($leave->leave_type_id) ? $leave->leaveType->title : '' }}
                                        </td>
                                        <td>{{ \Auth::user()->dateFormat($leave->applied_on) }}</td>
                                        <td>{{ \Auth::user()->dateFormat($leave->start_date) }}</td>
                                        <td>{{ \Auth::user()->dateFormat($leave->end_date) }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $leave->day_type ?? '')) }}</td>

                                        <td>{{ $leave->total_leave_days }}</td>
                                        <td>{{ $leave->leave_reason }}</td>
                                        <td>
                                            @if ($leave->status == 'Pending')
                                                <div class="badge bg-warning p-2 px-3 status-badge5">
                                                    {{ $leave->status }}</div>
                                            @elseif($leave->status == 'Approved')
                                                <div class="badge bg-success p-2 px-3 status-badge5">
                                                    {{ $leave->status }}</div>
                                            @elseif($leave->status == 'Reject')
                                                <div class="badge bg-danger p-2 px-3 status-badge5">
                                                    {{ $leave->status }}</div>
                                            @endif
                                        </td>

                                        <td class="Action">
                                                    @if (\Auth::user()->type != 'employee')
                                                        <div class="action-btn me-2">
                                                            <a href="{{ URL::to('leave/' . $leave->id . '/action') }}" class="mx-3 btn btn-sm bg-success align-items-center"
                                                                data-bs-toggle="tooltip"
                                                                title=""
                                                                data-bs-original-title="{{ __('Manage Leave') }}">
                                                                <span class="text-white"><i class="ti ti-caret-right"></i></span>
                                                            </a>
                                                        </div>
                                                        @can('Edit Leave')
                                                            <div class="action-btn me-2">
                                                                <a href="#" class="mx-3 btn btn-sm bg-info align-items-center"
                                                                    data-size="lg"
                                                                    data-url="{{ URL::to('leave/' . $leave->id . '/edit') }}"
                                                                    data-ajax-popup="true" data-size="md" data-bs-toggle="tooltip"
                                                                    title="" data-title="{{ __('Edit Leave') }}"
                                                                    data-bs-original-title="{{ __('Edit') }}">
                                                                    <span class="text-white"><i class="ti ti-pencil"></i></span>
                                                                </a>
                                                            </div>
                                                        @endcan
                                                        @can('Delete Leave')
                                                            @if (\Auth::user()->type != 'employee')
                                                                <div class="action-btn">
                                                                    {!! Form::open([
                                                                        'method' => 'DELETE',
                                                                        'route' => ['leave.destroy', $leave->id],
                                                                        'id' => 'delete-form-' . $leave->id,
                                                                    ]) !!}
                                                                    <a href="#"
                                                                        class="mx-3 btn btn-sm bg-danger align-items-center bs-pass-para"
                                                                        data-bs-toggle="tooltip" title=""
                                                                        data-bs-original-title="Delete" aria-label="Delete"><span class="text-white"><i
                                                                            class="ti ti-trash"></i></span></a>
                                                                    </form>
                                                                </div>
                                                            @endif
                                                        @endcan
                                                    @else
                                                        <div class="action-btn me-2">
                                                            <a href="{{ URL::to('leave/' . $leave->id . '/action') }}" class="mx-3 btn btn-sm bg-success align-items-center"
                                                                data-bs-toggle="tooltip"
                                                                title=""
                                                                data-bs-original-title="{{ __('Manage Leave') }}">
                                                                <span class="text-white"><i class="ti ti-caret-right"></i></span>
                                                            </a>
                                                        </div>
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
    </script>
@endpush
