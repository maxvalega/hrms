@extends('layouts.admin')
@section('page-title')
    {{ __('Reimbursement Report') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Reimbursement Report') }}</li>
@endsection
@push('script-page')
    <script>
        $('input[name="type"]:radio').on('change', function() {
            var type = $(this).val();
            if (type == 'monthly') {
                $('.month').removeClass('d-none').addClass('d-block');
                $('.year').removeClass('d-block').addClass('d-none');
            } else {
                $('.year').removeClass('d-none').addClass('d-block');
                $('.month').removeClass('d-block').addClass('d-none');
            }
        });
        $('input[name="type"]:radio:checked').trigger('change');

        $(document).on('change', 'select[name=branch]', function() {
            getDepartment($(this).val());
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
                    var selected = '{{ request('department_id') }}';
                    var html = '<label class="form-label">{{ __('Department') }}</label>';
                    html += '<select class="form-control select department_id" name="department_id" id="department_id">';
                    html += '<option value="">{{ __('All') }}</option>';
                    $.each(data, function(key, value) {
                        html += '<option value="' + key + '"' + (String(selected) === String(key) ? ' selected' : '') + '>' + value + '</option>';
                    });
                    html += '</select>';
                    $('.department_div').html(html);
                }
            });
        }
    </script>
@endpush

@section('action-button')
    <div class="float-end">
        <a href="{{ route('reimbursement.report.export', [
            'type' => request('type', 'monthly'),
            'month' => request('month', date('Y-m')),
            'year' => request('year', date('Y')),
            'branch' => request('branch', 0),
            'department' => request('department_id', 0),
        ]) }}"
            class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{ __('Download Excel') }}">
            <i class="ti ti-file-spreadsheet me-1"></i>{{ __('Download Excel') }}
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2">
                <div class="card">
                    <div class="card-body">
                        {{ Form::open(['route' => ['report.reimbursement'], 'method' => 'get', 'id' => 'report_reimbursement']) }}
                        <div class="row align-items-center justify-content-end">
                            <div class="col-xl-10">
                                <div class="row">
                                    <div class="col-3">
                                        <label class="form-label">{{ __('Type') }}</label> <br>
                                        <div class="form-check form-check-inline form-group">
                                            <input type="radio" id="monthly" value="monthly" name="type"
                                                class="form-check-input"
                                                {{ (!isset($_GET['type']) || $_GET['type'] == 'monthly') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="monthly">{{ __('Monthly') }}</label>
                                        </div>
                                        <div class="form-check form-check-inline form-group">
                                            <input type="radio" id="yearly" value="yearly" name="type"
                                                class="form-check-input"
                                                {{ isset($_GET['type']) && $_GET['type'] == 'yearly' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="yearly">{{ __('Yearly') }}</label>
                                        </div>
                                    </div>

                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 month">
                                        <div class="btn-box">
                                            {{ Form::label('month', __('Month'), ['class' => 'form-label']) }}
                                            {{ Form::month('month', request('month', date('Y-m')), ['class' => 'month-btn form-control']) }}
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 year d-none">
                                        <div class="btn-box">
                                            {{ Form::label('year', __('Year'), ['class' => 'form-label']) }}
                                            <select class="form-control select" id="year" name="year">
                                                @for ($y = $filterYear['starting_year']; $y <= $filterYear['ending_year']; $y++)
                                                    <option value="{{ $y }}"
                                                        {{ (string) request('year', date('Y')) === (string) $y ? 'selected' : '' }}>
                                                        {{ $y }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            {{ Form::label('branch', __('Branch'), ['class' => 'form-label']) }}
                                            {{ Form::select('branch', $branch, request('branch'), ['class' => 'form-control select branch_id']) }}
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="form-icon-user department_div">
                                            {{ Form::label('department', __('Department'), ['class' => 'form-label']) }}
                                            {{ Form::select('department_id', $department, request('department_id'), ['class' => 'form-control select department_id', 'id' => 'department_id']) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto mt-4">
                                <a href="#" class="btn btn-sm btn-primary me-1"
                                    onclick="document.getElementById('report_reimbursement').submit(); return false;"
                                    data-bs-toggle="tooltip" title="{{ __('Apply') }}">
                                    <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                </a>
                                <a href="{{ route('report.reimbursement') }}" class="btn btn-sm btn-danger"
                                    data-bs-toggle="tooltip" title="{{ __('Reset') }}">
                                    <span class="btn-inner--icon"><i class="ti ti-refresh text-white-off"></i></span>
                                </a>
                            </div>
                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col">
                <div class="card p-4 mb-4">
                    <h6 class="report-text gray-text mb-0">{{ __('Report') }} :</h6>
                    <h7 class="report-text mb-0">{{ $filterYear['type'] . ' ' . __('Approved Reimbursements') }}</h7>
                </div>
            </div>
            @if ($filterYear['branch'] != 'All')
                <div class="col">
                    <div class="card p-4 mb-4">
                        <h6 class="report-text gray-text mb-0">{{ __('Branch') }} :</h6>
                        <h7 class="report-text mb-0">{{ $filterYear['branch'] }}</h7>
                    </div>
                </div>
            @endif
            @if ($filterYear['department'] != 'All')
                <div class="col">
                    <div class="card p-4 mb-4">
                        <h6 class="report-text gray-text mb-0">{{ __('Department') }} :</h6>
                        <h7 class="report-text mb-0">{{ $filterYear['department'] }}</h7>
                    </div>
                </div>
            @endif
            <div class="col">
                <div class="card p-4 mb-4">
                    <h6 class="report-text gray-text mb-0">{{ __('Duration') }} :</h6>
                    <h7 class="report-text mb-0">{{ $filterYear['dateYearRange'] }}</h7>
                </div>
            </div>
            <div class="col">
                <div class="card p-4 mb-4">
                    <h6 class="report-text gray-text mb-0">{{ __('Approved Claims') }} :</h6>
                    <h7 class="report-text mb-0">{{ $filterData['totalClaims'] }}</h7>
                </div>
            </div>
            <div class="col">
                <div class="card p-4 mb-4">
                    <h6 class="report-text gray-text mb-0">{{ __('Total Payable') }} :</h6>
                    <h7 class="report-text mb-0">{{ \Auth::user()->priceFormat($filterData['totalAmount']) }}</h7>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive py-4">
                        <table class="table mb-0" id="report-dataTable">
                            <thead>
                                <tr>
                                    <th>{{ __('S.No') }}</th>
                                    <th>{{ __('Employee ID') }}</th>
                                    <th>{{ __('Employee') }}</th>
                                    <th>{{ __('Component') }}</th>
                                    <th>{{ __('Month') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Approved On') }}</th>
                                    <th>{{ __('Account Holder') }}</th>
                                    <th>{{ __('Account No.') }}</th>
                                    <th>{{ __('Bank') }}</th>
                                    <th>{{ __('IFSC') }}</th>
                                    <th>{{ __('Remarks') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($claims as $index => $claim)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ !empty($claim->emp_code) ? \Auth::user()->employeeIdFormat($claim->emp_code) : '—' }}</td>
                                        <td>{{ $claim->name ?? '—' }}</td>
                                        <td>{{ $claim->component_name ?: ('#' . $claim->component_id) }}</td>
                                        <td>{{ $claim->claim_month }}</td>
                                        <td>{{ \Auth::user()->priceFormat($claim->amount) }}</td>
                                        <td><span class="badge bg-success">{{ __('Approved') }}</span></td>
                                        <td>{{ $claim->approved_at ? $claim->approved_at->format('d M Y') : '—' }}</td>
                                        <td>{{ $claim->account_holder_name ?: '—' }}</td>
                                        <td>{{ $claim->account_number ?: '—' }}</td>
                                        <td>{{ $claim->bank_name ?: '—' }}</td>
                                        <td>{{ $claim->bank_identifier_code ?: '—' }}</td>
                                        <td>{{ $claim->remarks ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="text-center text-muted">{{ __('No approved reimbursements found for the selected filters.') }}</td>
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
