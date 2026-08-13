@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Bulk Attendance') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Bulk Attendance') }}</li>
@endsection

@php
    $isVimalBulkMode = $isVimalBulkMode ?? false;
    $viewDate = $viewDate ?? (isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'));
    $importPreview = $importPreview ?? null;
    $canUpload = in_array(\Auth::user()->type, ['super admin', 'company']);
@endphp

@push('script-page')
    <script>
        $('#present_all').click(function() {
            if (this.checked) {
                $('.present').each(function() { this.checked = true; });
                $('.present_check_in').removeClass('d-none').addClass('d-block');
            } else {
                $('.present').each(function() { this.checked = false; });
                $('.present_check_in').removeClass('d-block').addClass('d-none');
            }
        });

        $('.present').click(function() {
            var div = $(this).parent().parent().parent().parent().find('.present_check_in');
            if (this.checked) {
                div.removeClass('d-none').addClass('d-block');
            } else {
                div.removeClass('d-block').addClass('d-none');
            }
        });

        $(document).on('change', 'select[name=branch]', function() {
            var branch_id = $(this).val();
            $.ajax({
                url: '{{ route('monthly.getdepartment') }}',
                type: 'POST',
                data: {
                    "branch_id": branch_id,
                    "_token": "{{ csrf_token() }}",
                },
                success: function(data) {
                    $('.department_id').empty();
                    $('.department_id').append('<option value=""> {{ __('Select Department') }} </option>');
                    $.each(data, function(key, value) {
                        $('.department_id').append('<option value="' + key + '">' + value + '</option>');
                    });
                }
            });
        });
    </script>
@endpush

@section('action-button')
@endsection

@section('content')
    {{-- Simple upload-first flow --}}
    @if($canUpload)
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                        <div>
                            <h5 class="mb-1">{{ __('Upload Attendance') }}</h5>
                            <p class="text-muted mb-0" style="font-size:.9rem;">
                                @if($isVimalBulkMode)
                                    {{ __('No need to select branch or department first. Upload your file — data appears immediately.') }}
                                @else
                                    {{ __('Upload a filled template. Dates and employees are taken from the file.') }}
                                @endif
                            </p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('attendanceemployee.bulkattendance.template', ['date' => $viewDate, 'branch' => request('branch'), 'department' => request('department')]) }}"
                               class="btn btn-info text-white">
                                <i class="ti ti-download"></i> {{ __('1. Download Template') }}
                            </a>
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#bulkUploadModal">
                                <i class="ti ti-upload"></i> {{ __('2. Upload Excel') }}
                            </button>
                            @if($isVimalBulkMode)
                            <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#importRegisterModal">
                                <i class="ti ti-file-import"></i> {{ __('Import Monthly Register') }}
                            </button>
                            @endif
                            <a href="{{ route('attendanceemployee.bulkattendance.export', ['date' => $viewDate, 'branch' => request('branch'), 'department' => request('department')]) }}"
                               class="btn btn-success">
                                <i class="ti ti-file-export"></i> {{ __('Export') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Just-imported preview --}}
    @if(!empty($importPreview) && is_array($importPreview))
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-success">
                <div class="card-header bg-success text-white py-2">
                    <strong>{{ __('Just imported') }}</strong>
                    <span class="ms-2" style="opacity:.9;">{{ __('Showing saved attendance from your upload') }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Employee ID') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('In') }}</th>
                                    <th>{{ __('Out') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($importPreview as $row)
                                    <tr>
                                        <td>{{ $row['employee_id'] ?? '' }}</td>
                                        <td>{{ $row['name'] ?? '' }}</td>
                                        <td>{{ $row['date'] ?? '' }}</td>
                                        <td>
                                            <span class="badge bg-{{ ($row['status'] ?? '') === 'Present' ? 'success' : (($row['status'] ?? '') === 'Leave' ? 'info' : 'danger') }}">
                                                {{ $row['status'] ?? '' }}
                                            </span>
                                        </td>
                                        <td>{{ !empty($row['clock_in']) && $row['clock_in'] !== '00:00:00' ? substr($row['clock_in'], 0, 5) : '—' }}</td>
                                        <td>{{ !empty($row['clock_out']) && $row['clock_out'] !== '00:00:00' ? substr($row['clock_out'], 0, 5) : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Optional view filters --}}
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    {{ Form::open(['route' => ['attendanceemployee.bulkattendance'], 'method' => 'get', 'id' => 'bulkattendance_filter']) }}
                    <div class="row align-items-end g-3">
                        <div class="col-md-3">
                            {{ Form::label('date', __('View date'), ['class' => 'form-label']) }}
                            <input type="date" name="date" id="bulk_view_date" class="form-control" value="{{ $viewDate }}">
                            <small class="text-muted">{{ __('Only to view / edit marked attendance') }}</small>
                        </div>
                        <div class="col-md-3">
                            {{ Form::label('branch', __('Branch (optional)'), ['class' => 'form-label']) }}
                            {{ Form::select('branch', $branch, request('branch'), ['class' => 'form-control select branch_id', 'id' => 'branch_id']) }}
                        </div>
                        <div class="col-md-3">
                            {{ Form::label('department', __('Department (optional)'), ['class' => 'form-label']) }}
                            {{ Form::select('department', $department, request('department'), ['class' => 'form-control select department_id', 'id' => 'department_id']) }}
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-search"></i> {{ __('Show') }}
                            </button>
                            <a href="{{ route('attendanceemployee.bulkattendance') }}" class="btn btn-light">{{ __('Reset') }}</a>
                        </div>
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    {{ __('Employees') }}
                    <small class="text-muted">— {{ __('attendance for') }} {{ $viewDate }}</small>
                </span>
                <span class="badge bg-primary">{{ $employees->count() }} {{ __('employees') }}</span>
            </div>
            <div class="card-body table-border-style">
                @if($employees->isEmpty())
                    <div class="alert alert-warning mb-0">
                        @if($isVimalBulkMode)
                            {{ __('No employees found for this company. Add employees first, then upload attendance.') }}
                        @else
                            {{ __('Select branch and department, then click Show.') }}
                        @endif
                    </div>
                @else
                {{ Form::open(['route' => ['attendanceemployee.bulkattendance'], 'method' => 'post']) }}
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="10%">{{ __('Employee Id') }}</th>
                                <th>{{ __('Employee') }}</th>
                                <th>{{ __('Branch') }}</th>
                                <th>{{ __('Department') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>
                                    <div class="form-group my-auto">
                                        <div class="custom-control">
                                            <input class="form-check-input" type="checkbox" name="present_all" id="present_all">
                                            <label class="custom-control-label" for="present_all">{{ __('Mark Present') }}</label>
                                        </div>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($employees as $employee)
                                @php
                                    $attendance = $employee->present_status($employee->id, $viewDate);
                                    $statusLabel = $attendance->status ?? null;
                                @endphp
                                <tr class="{{ $statusLabel ? 'table-success' : '' }}">
                                    <td class="Id">
                                        <input type="hidden" value="{{ $employee->id }}" name="employee_id[]">
                                        <a href="{{ route('employee.show', \Illuminate\Support\Facades\Crypt::encrypt($employee->id)) }}"
                                            class="btn btn-outline-primary btn-sm">{{ \Auth::user()->employeeIdFormat($employee->employee_id) }}</a>
                                    </td>
                                    <td>{{ $employee->name }}</td>
                                    <td>{{ !empty($employee->branch) ? $employee->branch->name : '' }}</td>
                                    <td>{{ !empty($employee->department) ? $employee->department->name : '' }}</td>
                                    <td>
                                        @if($statusLabel)
                                            <span class="badge bg-{{ $statusLabel === 'Present' ? 'success' : ($statusLabel === 'Leave' ? 'info' : 'danger') }}">
                                                {{ $statusLabel }}
                                            </span>
                                            @if(!empty($attendance->clock_in) && $attendance->clock_in !== '00:00:00')
                                                <small class="text-muted d-block">{{ substr($attendance->clock_in, 0, 5) }} – {{ substr($attendance->clock_out ?? '', 0, 5) }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">{{ __('Not marked') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="row">
                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <div class="custom-control custom-checkbox">
                                                        <input class="form-check-input present" type="checkbox"
                                                            name="present-{{ $employee->id }}"
                                                            id="present{{ $employee->id }}"
                                                            {{ !empty($attendance) && $attendance->status == 'Present' ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="present{{ $employee->id }}"></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-10 present_check_in {{ empty($attendance) || $attendance->status != 'Present' ? 'd-none' : '' }}">
                                                <div class="row">
                                                    <label class="col-md-2 control-label">{{ __('In') }}</label>
                                                    <div class="col-md-4">
                                                        <input type="time" class="form-control timepicker"
                                                            name="in-{{ $employee->id }}"
                                                            value="{{ !empty($attendance) && $attendance->clock_in != '00:00:00' ? $attendance->clock_in : App\Models\Utility::getValByName('company_start_time') }}">
                                                    </div>
                                                    <label class="col-md-2 control-label">{{ __('Out') }}</label>
                                                    <div class="col-md-4">
                                                        <input type="time" class="form-control timepicker"
                                                            name="out-{{ $employee->id }}"
                                                            value="{{ !empty($attendance) && $attendance->clock_out != '00:00:00' ? $attendance->clock_out : App\Models\Utility::getValByName('company_end_time') }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="attendance-btn float-end pt-4">
                    <input type="hidden" value="{{ $viewDate }}" name="date">
                    <input type="hidden" value="{{ request('branch') }}" name="branch">
                    <input type="hidden" value="{{ request('department') }}" name="department">
                    {{ Form::submit(__('Update'), ['class' => 'btn btn-primary']) }}
                </div>
                {{ Form::close() }}
                @endif
            </div>
        </div>
    </div>

    {{-- Bulk Upload Modal --}}
    <div class="modal fade" id="bulkUploadModal" tabindex="-1" aria-labelledby="bulkUploadModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form action="{{ route('attendanceemployee.bulkattendance.import') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
              <h5 class="modal-title" id="bulkUploadModalLabel">{{ __('Upload Attendance Excel') }}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="alert alert-info" style="font-size:.85rem;">
                <strong>{{ __('Simple flow') }}:</strong>
                <ol class="mb-0 ps-3">
                  <li>{{ __('Download Template (optional)') }}</li>
                  <li>{{ __('Fill Employee ID, Date, Status, Clock In / Out') }}</li>
                  <li>{{ __('Upload here — results show immediately. No branch/department needed.') }}</li>
                </ol>
              </div>
              <div class="mb-3">
                <label class="form-label">{{ __('Select File') }} <span class="text-danger">*</span></label>
                <input type="file" name="file" accept=".csv,.xlsx,.xls" class="form-control" required>
                <small class="text-muted">{{ __('Supported: .csv, .xlsx, .xls') }}</small>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
              <button type="submit" class="btn btn-primary"><i class="ti ti-upload"></i> {{ __('Upload & Show Data') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    @if($isVimalBulkMode)
    <div class="modal fade" id="importRegisterModal" tabindex="-1" aria-labelledby="importRegisterModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form action="{{ route('attendanceemployee.bulkattendance.import-register') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
              <h5 class="modal-title" id="importRegisterModalLabel">{{ __('Import Monthly Register') }}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="alert alert-info" style="font-size:.85rem;">
                <ul class="mb-0 ps-3">
                  <li>{{ __('One row per employee') }}</li>
                  <li>{{ __('Day columns: 01-08-2026 or day numbers 1–31') }}</li>
                  <li>{{ __('Values: P / A / L / HD') }}</li>
                  <li>{{ __('No branch or department required') }}</li>
                </ul>
              </div>
              <div class="mb-3">
                <label class="form-label">{{ __('Month') }} <small class="text-muted">({{ __('if headers are day numbers 1–31') }})</small></label>
                <input type="month" name="month" class="form-control" value="{{ substr($viewDate, 0, 7) }}">
              </div>
              <div class="mb-3">
                <label class="form-label">{{ __('Select Register File') }} <span class="text-danger">*</span></label>
                <input type="file" name="file" accept=".csv,.xlsx,.xls" class="form-control" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
              <button type="submit" class="btn btn-dark"><i class="ti ti-file-import"></i> {{ __('Import & Show Data') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    @endif
@endsection
