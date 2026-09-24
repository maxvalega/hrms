@extends('layouts.admin')

@section('page-title')
    {{ __('Manage Monthly Attendance') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Manage Monthly Attendance Report') }}</li>
@endsection
@php
    $isVicRegister = $isVicRegister ?? false;
    $vicRegister = $vicRegister ?? ['rows' => [], 'day_headers' => []];
@endphp

@section('action-button')
    @if($isVicRegister)
        <a href="#" class="btn btn-sm btn-dark me-1" data-bs-toggle="modal" data-bs-target="#vicRegisterUploadModal"
            title="{{ __('Upload Consolidate Attendance Register') }}">
            <span class="btn-inner--icon"><i class="ti ti-upload"></i></span>
        </a>
    @endif
    <a href="#" class="btn btn-sm btn-primary me-1" onclick="saveAsPDF()" data-bs-toggle="tooltip" title="{{ __('Download') }}"
        data-original-title="{{ __('Download') }}">
        <span class="btn-inner--icon"><i class="ti ti-download"></i></span>
    </a>

    @php
        $emp = isset($_GET['employee_id']) && !empty($_GET['employee_id']) ? $_GET['employee_id'] : [];
        $employees = implode(', ', $emp);
    @endphp

    <a href="{{ route('report.attendance', [isset($_GET['month']) ? $_GET['month'] : date('Y-m'), isset($_GET['branch_id']) && !empty($_GET['branch_id']) ? $_GET['branch_id'] : 0, isset($_GET['department']) && !empty($_GET['department']) ? $_GET['department'] : 0, !empty($employees) ? $employees : 0]) }}"
        class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="" data-bs-original-title="Export">
        <span class="btn-inner--icon"><i class="ti ti-file-export "></i></span>
    </a>
@endsection


@push('css-page')
    <style>
        .vic-register-wrap { max-height: 72vh; }
        .vic-register-table { font-size: 11px; white-space: nowrap; }
        .vic-register-table th, .vic-register-table td { padding: 4px 6px; vertical-align: middle; }
        .vic-register-table thead th { position: sticky; top: 0; background: #f1f5f9; z-index: 2; }
        .vic-status-row { background: #fff; font-weight: 600; }
        .vic-detail-row { background: #fafafa; color: #475569; }
        .vic-code-p, .vic-code-p-lc { color: #0f766e; }
        .vic-code-a { color: #b91c1c; }
        .vic-code-wo, .vic-code-h { color: #0369a1; }
        .vic-code-hfd { color: #c2410c; }
        .vic-code-mp { color: #7c3aed; }
        .vic-code-pl, .vic-code-l-w-p- { color: #a16207; }
    </style>
@endpush

@push('script-page')
    <script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
    <script>
        var filename = $('#filename').val();

        function saveAsPDF() {
            var element = document.getElementById('printableArea');
            var opt = {
                margin: 0.3,
                filename: filename,
                image: {
                    type: 'jpeg',
                    quality: 1
                },
                html2canvas: {
                    scale: 4,
                    dpi: 72,
                    letterRendering: true
                },
                jsPDF: {
                    unit: 'in',
                    format: 'A2'
                }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
@endpush

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class=" mt-2 " id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        {{ Form::open(['route' => ['report.monthly.attendance'], 'method' => 'get', 'id' => 'report_monthly_attendance']) }}
                        <div class="row align-items-center justify-content-end">
                            <div class="col-xl-10">
                                <div class="row">
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            {{ Form::label('month', __(' Month'), ['class' => 'form-label']) }}
                                            {{ Form::month('month', isset($_GET['month']) ? $_GET['month'] : date('Y-m'), ['class' => 'month-btn form-control', 'autocomplete' => 'off', 'placeholder' => 'Select month']) }}
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            {{ Form::label('branch', __('Branch'), ['class' => 'form-label']) }}
                                            {{ Form::select('branch_id', $branch, isset($_GET['branch']) ? $_GET['branch'] : '', ['class' => 'form-control select branch_id', 'id' => 'branch-select branch_id']) }}
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box" id="department_div">
                                            {{ Form::label('department', __('Department'), ['class' => 'form-label']) }}
                                            <select class="form-control select department_id" name="department"
                                                id="department_id" placeholder="Select Department">
                                                <option value="">{{ __('All') }}</option>
                                                @if(is_iterable($department))
                                                @foreach($department as $dId => $dName)
                                                    @if($dId !== '' && $dId !== 0)
                                                    <option value="{{ $dId }}" {{ (isset($_GET['department']) && $_GET['department'] == $dId) ? 'selected' : '' }}>{{ $dName }}</option>
                                                    @endif
                                                @endforeach
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box" id="employee_div">
                                            {{ Form::label('employee', __('Employee'), ['class' => 'form-label']) }}
                                            <select class="form-control select" name="employee_id[]" id="employee_id"
                                                placeholder="Select Employee">
                                                <option value="">{{ __('All') }}</option>
                                                @foreach($allEmployees ?? $employees as $eId => $eName)
                                                    <option value="{{ $eId }}" {{ (isset($_GET['employee_id']) && in_array($eId, (array)$_GET['employee_id'])) ? 'selected' : '' }}>{{ $eName }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="row">
                                    <div class="col-auto mt-4">
                                        <a href="#" class="btn btn-sm btn-primary me-1"
                                            onclick="document.getElementById('report_monthly_attendance').submit(); return false;"
                                            data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                            data-original-title="{{ __('apply') }}">
                                            <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                        </a>
                                        <a href="{{ route('report.monthly.attendance') }}" class="btn btn-sm btn-danger "
                                            data-bs-toggle="tooltip" title="{{ __('Reset') }}"
                                            data-original-title="{{ __('Reset') }}">
                                            <span class="btn-inner--icon"><i
                                                    class="ti ti-refresh text-white-off "></i></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>

    <div id="printableArea">
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="badge theme-avtar bg-primary">
                                    <i class="ti ti-report"></i>
                                </div>
                                <div class="ms-3">
                                    <input type="hidden"
                                        value="{{ $data['branch'] . ' ' . __('Branch') . ' ' . $data['curMonth'] . ' ' . __('Attendance Report of') . ' ' . $data['department'] . ' ' . 'Department' }}"
                                        id="filename">
                                    <h5 class="mb-0">{{ __('Report') }}</h5>
                                    <div>
                                        <p class="text-muted text-sm mb-0">{{ __('Attendance Summary') }}</p>
                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @if ($data['branch'] != 'All')
                <div class="col">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="badge theme-avtar bg-secondary">
                                        <i class="ti ti-sitemap"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h5 class="mb-0">{{ __('Branch') }}</h5>
                                        <p class="text-muted text-sm mb-0">
                                            {{ $data['branch'] }} </p>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if ($data['department'] != 'All')
                <div class="col">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="badge theme-avtar bg-primary">
                                        <i class="ti ti-template"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h5 class="mb-0">{{ __('Department') }}</h5>
                                        <p class="text-muted text-sm mb-0">{{ $data['department'] }}</p>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <div class="col">
                <div class="card">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="badge theme-avtar bg-secondary">
                                    <i class="ti ti-sum"></i>
                                </div>
                                <div class="ms-3">
                                    <h5 class="mb-0">{{ __('Duration') }}</h5>
                                    <p class="text-muted text-sm mb-0">{{ $data['curMonth'] }}
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="card mon-card">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="badge theme-avtar bg-primary">
                                    <i class="ti ti-file-report"></i>
                                </div>
                                <div class="ms-3">
                                    <h5 class="mb-0">{{ __('Attendance') }}</h5>
                                    <div>
                                        <p class="text-muted text-sm mb-0">{{ __('Total present') }}:
                                            {{ $data['totalPresent'] }}</p>
                                        <p class="text-muted text-sm mb-0">{{ __('Total leave') }}:
                                            {{ $data['totalLeave'] }}</p>
                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card mon-card">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="badge theme-avtar bg-secondary">
                                    <i class="ti ti-clock"></i>
                                </div>
                                <div class="ms-3">
                                    <h5 class="mb-0">{{ __('Overtime') }}</h5>
                                    <p class="text-muted text-sm mb-0">
                                        {{ __('Total overtime in hours') }} :
                                        {{ number_format($data['totalOvertime'], 2) }}</p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card mon-card">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="badge theme-avtar bg-primary">
                                    <i class="ti ti-info-circle"></i>
                                </div>
                                <div class="ms-3">
                                    <h5 class="mb-0">{{ __('Early leave') }}</h5>
                                    <p class="text-muted text-sm mb-0">{{ __('Total early leave in hours') }}:
                                        {{ number_format($data['totalEarlyLeave'], 2) }}</p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card mon-card">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="badge theme-avtar bg-secondary">
                                    <i class="ti ti-alarm"></i>
                                </div>
                                <div class="ms-3">
                                    <h5 class="mb-0">{{ __('Employee late') }}</h5>
                                    <p class="text-muted text-sm mb-0">{{ __('Total late in hours') }} :
                                        {{ number_format($data['totalLate'], 2) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-body table-border-style">
                    @if($isVicRegister)
                        <div class="table-responsive py-3 vic-register-wrap">
                            <table class="table table-bordered table-sm vic-register-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Sr No.</th>
                                        <th>Employee Code</th>
                                        <th>Employee Name</th>
                                        <th>Employee Number</th>
                                        <th>Joining Date</th>
                                        <th>Branch</th>
                                        <th>Department</th>
                                        <th>Designation</th>
                                        <th>Division</th>
                                        <th>Working Area</th>
                                        <th>Project</th>
                                        <th>Present</th>
                                        <th>Absent</th>
                                        <th>Half Day</th>
                                        <th>Miss Punch</th>
                                        <th>Week Off</th>
                                        <th>Holiday</th>
                                        <th>Approved Leave</th>
                                        <th>Pending Leave</th>
                                        <th>Approved OutDuty</th>
                                        <th>Pending OutDuty</th>
                                        <th></th>
                                        @foreach(($vicRegister['day_headers'] ?? []) as $dayHeader)
                                            <th class="text-nowrap">{{ $dayHeader }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($vicRegister['rows'] ?? []) as $row)
                                        @php
                                            $detailRows = [
                                                ['label' => 'Shift', 'key' => 'shift'],
                                                ['label' => 'IN', 'key' => 'in'],
                                                ['label' => 'OUT', 'key' => 'out'],
                                                ['label' => 'Working Hours', 'key' => 'hours'],
                                                ['label' => 'Overtime Hours', 'key' => 'ot'],
                                            ];
                                        @endphp
                                        <tr class="vic-status-row">
                                            <td>{{ $row['sr'] }}</td>
                                            <td>{{ $row['code'] }}</td>
                                            <td class="text-nowrap">{{ $row['name'] }}</td>
                                            <td>{{ $row['number'] }}</td>
                                            <td class="text-nowrap">{{ $row['doj'] }}</td>
                                            <td>{{ $row['branch'] }}</td>
                                            <td>{{ $row['department'] }}</td>
                                            <td>{{ $row['designation'] }}</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td>{{ $row['counts']['present'] }}</td>
                                            <td>{{ $row['counts']['absent'] }}</td>
                                            <td>{{ $row['counts']['half_day'] }}</td>
                                            <td>{{ $row['counts']['miss_punch'] }}</td>
                                            <td>{{ $row['counts']['week_off'] }}</td>
                                            <td>{{ $row['counts']['holiday'] }}</td>
                                            <td>{{ $row['counts']['approved_leave'] }}</td>
                                            <td>{{ $row['counts']['pending_leave'] }}</td>
                                            <td>{{ $row['counts']['approved_outduty'] }}</td>
                                            <td>{{ $row['counts']['pending_outduty'] }}</td>
                                            <td class="fw-semibold">Status</td>
                                            @foreach($row['days'] as $day)
                                                <td class="text-center vic-code vic-code-{{ strtolower(preg_replace('/[^a-z0-9]+/i', '-', $day['code'])) }}">{{ $day['code'] }}</td>
                                            @endforeach
                                        </tr>
                                        @foreach($detailRows as $detail)
                                            <tr class="vic-detail-row">
                                                <td colspan="21"></td>
                                                <td class="fw-semibold">{{ $detail['label'] }}</td>
                                                @foreach($row['days'] as $day)
                                                    <td class="text-nowrap">{{ $day[$detail['key']] }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    @empty
                                        <tr>
                                            <td colspan="{{ 22 + count($vicRegister['day_headers'] ?? []) }}" class="text-center text-muted py-4">
                                                {{ __('No employees found. Upload the Consolidate Attendance Register to see this month.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @else
                    <div class="table-responsive py-4 attendance-table-responsive">
                        <table class="table ">
                            <thead>
                                <tr>
                                    <th class="active">{{ __('Name') }}</th>
                                    @foreach ($dates as $date)
                                        <th>{{ $date }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>

                                @foreach ($employeesAttendance as $attendance)
                                    <tr>
                                        <td>{{ $attendance['name'] }}</td>
                                        @foreach ($attendance['status'] as $status)
                                            <td>
                                                @if ($status == 'P')
                                                    <i class="badge bg-success p-2">{{ __('P') }}</i>
                                                @elseif($status == 'L')
                                                    <i class="badge bg-warning p-2">{{ __('L') }}</i>
                                                @elseif($status == 'A')
                                                    <i class="badge bg-danger p-2">{{ __('A') }}</i>
                                                @elseif($status == 'H')
                                                    <i class="badge bg-info p-2">{{ __('H') }}</i>
                                                @elseif($status == '')
                                                    <span class="text-muted" style="font-size:.7rem;">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($isVicRegister)
    <div class="modal fade" id="vicRegisterUploadModal" tabindex="-1" aria-labelledby="vicRegisterUploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('report.monthly.attendance.import') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="vicRegisterUploadModalLabel">{{ __('Upload Consolidate Attendance Register') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3" style="font-size:.9rem;">
                            {{ __('Upload the same Excel: one employee, then Status / Shift / IN / OUT / Working Hours / Overtime Hours, with day columns like 01-08-2026.') }}
                        </p>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Select Register File') }} <span class="text-danger">*</span></label>
                            <input type="file" name="file" accept=".xlsx,.xls" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-dark"><i class="ti ti-upload"></i> {{ __('Upload & Show') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('script-page')
    <script>
        $(document).ready(function() {
            var b_id = $('#branch_id').val();
            // getDepartment(b_id);
        });
        $(document).on('change', 'select[name=branch_id]', function() {
            var branch_id = $(this).val();

            getDepartment(branch_id);
        });

        function getDepartment(bid) {

            $.ajax({
                url: '{{ route('monthly.getdepartment') }}',
                type: 'POST',
                data: {
                    "branch_id": bid,
                    "_token": "{{ csrf_token() }}",
                },
                success: function(data) {

                    $('.department_id').empty();
                    var emp_selct = `<select class="department_id form-control multi-select" id="choices-multiple" multiple="" required="required" name="department_id[]">
                </select>`;
                    $('.department_div').html(emp_selct);

                    $('.department_id').append('<option value=""> {{ __('Select Department') }} </option>');
                    $.each(data, function(key, value) {
                        $('.department_id').append('<option value="' + key + '">' + value +
                            '</option>');
                    });
                    new Choices('#choices-multiple', {
                        removeItemButton: true,
                    });
                }
            });
        }

        $(document).on('change', '.department_id', function() {
            var department_id = $(this).val();
            getEmployee(department_id);
        });

        function getEmployee(did) {

            $.ajax({
                url: '{{ route('monthly.getemployee') }}',
                type: 'POST',
                data: {
                    "department_id": did,
                    "_token": "{{ csrf_token() }}",
                },
                success: function(data) {

                    $('#employee_id').empty();

                    $("#employee_div").html('');
                    // $('#employee_div').append('<select class="form-control" id="employee_id" name="employee_id[]"  multiple></select>');
                    $('#employee_div').append(
                        '<label for="employee" class="form-label">{{ __('Employee') }}</label><select class="form-control" id="employee_id" name="employee_id[]"  multiple></select>'
                    );

                    $('#employee_id').append('<option value="">{{ __('Select Employee') }}</option>');

                    $.each(data, function(key, value) {
                        $('#employee_id').append('<option value="' + key + '">' + value + '</option>');
                    });

                    var multipleCancelButton = new Choices('#employee_id', {
                        removeItemButton: true,
                    });
                }
            });
        }
    </script>

    <script>
        $(document).ready(function() {
            var now = new Date();
            var month = (now.getMonth() + 1);
            if (month < 10) month = "0" + month;
            var today = now.getFullYear() + '-' + month;
            $('.current_date').val(today);
        });
    </script>
@endpush
