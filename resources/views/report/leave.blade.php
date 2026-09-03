@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Leave Report') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Leave Report') }}</li>
@endsection
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
                    format: 'A4'
                }
            };
            html2pdf().set(opt).from(element).save();

        }
    </script>
    <script>
        $('input[name="type"]:radio').on('change', function(e) {
            var type = $(this).val();
            if (type == 'monthly') {
                $('.month').addClass('d-block');
                $('.month').removeClass('d-none');
                $('.year').addClass('d-none');
                $('.year').removeClass('d-block');
            } else {
                $('.year').addClass('d-block');
                $('.year').removeClass('d-none');
                $('.month').addClass('d-none');
                $('.month').removeClass('d-block');
            }
        });

        $('input[name="type"]:radio:checked').trigger('change');
    </script>

    <script>
        $(document).ready(function() {
            var b_id = $('.branch_id').val();
            // getDepartment(b_id);
        });
        $(document).on('change', 'select[name=branch]', function() {
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
    </script>
@endpush
@section('action-button')
    <a href="#" class="btn btn-sm btn-primary me-2" onclick="saveAsPDF()" data-bs-toggle="tooltip" title="{{ __('Download') }}"
        data-original-title="{{ __('Download') }}" style="margin-right: 5px;">
        <span class="btn-inner--icon"><i class="ti ti-download"></i></span>
    </a>
    <a href="{{ route('report.leave.export', request()->query()) }}" class="btn btn-sm btn-success float-end" data-bs-toggle="tooltip"
        data-bs-original-title="{{ __('Export Excel') }}">
        <i class="ti ti-file-spreadsheet"></i>
    </a>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2" id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        {{ Form::open(['route' => ['report.leave'], 'method' => 'get', 'id' => 'report_leave']) }}
                        <div class="row align-items-center justify-content-end">
                            <div class="col-xl-10">
                                <div class="row">
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            <div class="   mx-2">
                                                {{ Form::label('type', __('Type'), ['class' => 'form-label']) }}<br>
                                                <div class="form-check form-check-inline form-group">
                                                    <input type="radio" id="monthly" value="monthly" name="type"
                                                        class="form-check-input"
                                                        {{ isset($_GET['type']) && $_GET['type'] == 'monthly' ? 'checked' : 'checked' }}>
                                                    {{ Form::label('monthly', __('Monthly'), ['class' => 'form-label']) }}
                                                </div>
                                                <div class="form-check form-check-inline form-group">
                                                    <input type="radio" id="yearly" value="yearly" name="type"
                                                        class="form-check-input yearly"
                                                        {{ isset($_GET['type']) && $_GET['type'] == 'yearly' ? 'checked' : '' }}>
                                                    {{ Form::label('yearly', __('Yearly'), ['class' => 'form-label']) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 month">
                                        <div class="btn-box">
                                            {{ Form::label('month', __('Month'), ['class' => 'form-label']) }}
                                            {{ Form::month('month', isset($_GET['month']) ? $_GET['month'] : date('Y-m'), ['class' => 'month-btn form-control']) }}
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 year d-none">
                                        <div class="btn-box">
                                            {{ Form::label('year', __('Year'), ['class' => 'form-label']) }}
                                            <select class="form-control select" id="year" name="year"
                                                tabindex="-1" aria-hidden="true">
                                                @for ($filterYear['starting_year']; $filterYear['starting_year'] <= $filterYear['ending_year']; $filterYear['starting_year']++)
                                                    <option
                                                        {{ isset($_GET['year']) && $_GET['year'] == $filterYear['starting_year'] ? 'selected' : '' }}
                                                        {{ !isset($_GET['year']) && date('Y') == $filterYear['starting_year'] ? 'selected' : '' }}
                                                        value="{{ $filterYear['starting_year'] }}">
                                                        {{ $filterYear['starting_year'] }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            {{ Form::label('branch', __('Branch'), ['class' => 'form-label']) }}
                                            {{ Form::select('branch', $branch, isset($_GET['branch']) ? $_GET['branch'] : '', ['class' => 'form-control select branch_id', 'id' => 'branch']) }}
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box" id="department_id">
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
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="row">
                                    <div class="col-auto mt-4">
                                        <a href="#" class="btn btn-sm btn-primary me-1"
                                            onclick="document.getElementById('report_leave').submit(); return false;"
                                            data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                            data-original-title="{{ __('apply') }}">
                                            <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                        </a>

                                        <a href="{{ route('report.leave') }}" class="btn btn-sm btn-danger me-1"
                                            data-bs-toggle="tooltip" title="{{ __('Reset') }}"
                                            data-original-title="{{ __('Reset') }}">
                                            <span class="btn-inner--icon"><i class="ti ti-refresh text-white-off"></i></span>
                                        </a>
                                        <a href="{{ route('report.leave.export', request()->query()) }}" class="btn btn-sm btn-success" style="white-space:nowrap;">
                                            <i class="ti ti-file-spreadsheet me-1"></i>{{ __('Excel') }}
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

    <div id="printableArea" class="">
        <input type="hidden"
            value="{{ $filterYear['branch'] . ' ' . __('Branch') . ' ' . $filterYear['dateYearRange'] . ' ' . $filterYear['type'] . ' ' . __('Leave Report of') . ' ' . $filterYear['department'] . ' ' . 'Department' }}"
            id="filename">


    </div>

    <div class="col-xl-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('Leave Report') }}</h5>
                <a href="{{ route('report.leave.export', request()->query()) }}" class="btn btn-sm btn-success">
                    <i class="ti ti-file-spreadsheet me-1"></i>{{ __('Export Excel') }}
                </a>
            </div>
            <div class="card-body table-border-style">
                <div class="table-responsive">
                    <table class="table" id="pc-dt-simple">
                        <thead>
                            <tr>
                                <th>{{ __('Employee ID') }}</th>
                                <th>{{ __('Employee') }}</th>
                                <th class="text-center">{{ __('Opening') }}</th>
                                <th class="text-center">{{ __('Availed') }}</th>
                                <th class="text-center">{{ __('Remaining') }}</th>
                                <th class="text-center">{{ __('Carry Fwd') }}</th>
                                <th class="text-center">{{ __('Lapsed') }}</th>
                                <th class="text-center">{{ __('Encashment') }}</th>
                                <th class="text-center">{{ __('Pending') }}</th>
                                <th>{{ __('Detail') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($leaves as $leave)
                                <tr>
                                    <td><a href="{{ route('employee.show', \Illuminate\Support\Facades\Crypt::encrypt($leave['id'])) }}"
                                            class="btn btn-sm btn-outline-primary">{{ \Auth::user()->employeeIdFormat($leave['employee_id']) }}</a>
                                    </td>
                                    <td><strong>{{ $leave['employee'] }}</strong></td>
                                    <td class="text-center"><span class="badge bg-secondary p-2">{{ $leave['opening'] ?? 0 }}</span></td>
                                    <td class="text-center"><span class="badge bg-warning text-dark p-2">{{ $leave['availed'] ?? 0 }}</span></td>
                                    <td class="text-center"><strong>{{ $leave['remaining'] ?? 0 }}</strong></td>
                                    <td class="text-center">
                                        @if(($leave['carry_forward'] ?? 0) > 0)
                                            <span class="badge bg-primary p-2">{{ $leave['carry_forward'] }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if(($leave['lapsed'] ?? 0) > 0)
                                            <span class="text-danger fw-bold">{{ $leave['lapsed'] }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if(($leave['encash_days'] ?? 0) > 0)
                                            <span class="badge bg-success p-2">{{ $leave['encash_days'] }}d</span>
                                            <br><small class="text-success">&#8377;{{ number_format($leave['encash_amount'] ?? 0) }}</small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if(($leave['pending'] ?? 0) > 0)
                                            <span class="badge bg-warning text-dark p-2">{{ $leave['pending'] }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-outline-info"
                                            data-url="{{ route('report.employee.leave', [$leave['id'], 'Approved', isset($_GET['type']) ? $_GET['type'] : 'no', isset($_GET['month']) ? $_GET['month'] : date('Y-m'), isset($_GET['year']) ? $_GET['year'] : date('Y')]) }}"
                                            data-ajax-popup="true" data-size="xl"
                                            data-title="{{ __('Leave Detail') }} — {{ $leave['employee'] }}"
                                            data-bs-toggle="tooltip" title="{{ __('View Detail') }}">
                                            <i class="ti ti-eye"></i>
                                        </a>
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
