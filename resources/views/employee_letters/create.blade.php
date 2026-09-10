@extends('layouts.admin')
@section('page-title') {{ __('Issue letter') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employee-letters.index') }}">{{ __('Issue Letters') }}</a></li>
    <li class="breadcrumb-item">{{ __('New') }}</li>
@endsection

@section('content')
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{ __('Issue a letter to an employee') }}</h5>
            <small class="text-muted">{{ __('Uses the format saved under Letter Formats. Offer letters can be emailed to a candidate who is not yet in the employee list.') }}</small>
        </div>
        <div class="card-body">
            {{ Form::open(['route' => 'employee-letters.store', 'method' => 'post']) }}
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Letter type') }}</label>
                    <select name="type" id="letter_type" class="form-control" onchange="elToggle()">
                        @foreach(\App\Models\LetterFormat::TYPES as $k => $lbl)
                            <option value="{{ $k }}" @selected($type === $k)>{{ __($lbl) }}</option>
                        @endforeach
                    </select>
                    <div class="small mt-1">
                        <a href="{{ route('employee-letters.formats.edit', $type) }}" id="format-link">{{ __('Review / upload this format') }}</a>
                    </div>
                </div>
                <div class="col-md-8">
                    <label class="form-label">{{ __('Employee') }}</label>
                    <select name="employee_id" class="form-control">
                        <option value="">{{ __('Select employee (or leave blank for a candidate offer)') }}</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }} {{ $emp->email ? '· '.$emp->email : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6" id="el-name">
                    <label class="form-label">{{ __('Recipient name (if not an employee)') }}</label>
                    <input type="text" name="recipient_name" class="form-control" value="{{ old('recipient_name') }}">
                </div>
                <div class="col-md-6" id="el-email">
                    <label class="form-label">{{ __('Recipient email') }}</label>
                    <input type="email" name="recipient_email" class="form-control" value="{{ old('recipient_email') }}" placeholder="{{ __('Required when sending email') }}">
                </div>

                <div class="col-md-6 el-f el-offer el-appointment">
                    <label class="form-label">{{ __('Job title / designation') }}</label>
                    <input type="text" name="job_title" class="form-control" value="{{ old('job_title') }}">
                </div>
                <div class="col-md-6 el-f el-offer el-appointment">
                    <label class="form-label">{{ __('Joining date') }}</label>
                    <input type="date" name="joining_date" class="form-control" value="{{ old('joining_date') }}">
                </div>
                <div class="col-md-6 el-f el-offer el-appointment el-increment">
                    <label class="form-label">{{ __('Salary / current CTC') }}</label>
                    <input type="text" name="salary" class="form-control" value="{{ old('salary') }}">
                </div>
                <div class="col-md-6 el-f el-offer">
                    <label class="form-label">{{ __('Offer expiry') }}</label>
                    <input type="date" name="offer_expiry" class="form-control" value="{{ old('offer_expiry') }}">
                </div>
                <div class="col-md-6 el-f el-confirmation">
                    <label class="form-label">{{ __('Confirmation date') }}</label>
                    <input type="date" name="confirmation_date" class="form-control" value="{{ old('confirmation_date') }}">
                </div>
                <div class="col-md-4 el-f el-increment">
                    <label class="form-label">{{ __('Revised CTC') }}</label>
                    <input type="text" name="new_salary" class="form-control" value="{{ old('new_salary') }}">
                </div>
                <div class="col-md-4 el-f el-increment">
                    <label class="form-label">{{ __('Increment amount') }}</label>
                    <input type="text" name="increment_amount" class="form-control" value="{{ old('increment_amount') }}">
                </div>
                <div class="col-md-4 el-f el-increment">
                    <label class="form-label">{{ __('Increment %') }}</label>
                    <input type="text" name="increment_percent" class="form-control" value="{{ old('increment_percent') }}">
                </div>
                <div class="col-md-6 el-f el-increment">
                    <label class="form-label">{{ __('Effective date') }}</label>
                    <input type="date" name="effective_date" class="form-control" value="{{ old('effective_date') }}">
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="send_email" value="1" id="send_email" checked>
                        <label class="form-check-label" for="send_email">{{ __('Email this letter now (PDF attached). It is also published on the employee portal.') }}</label>
                    </div>
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <a href="{{ route('employee-letters.index') }}" class="btn btn-light border">{{ __('Cancel') }}</a>
                <button class="btn btn-primary"><i class="ti ti-send me-1"></i>{{ __('Issue letter') }}</button>
            </div>
            {{ Form::close() }}
        </div>
    </div>
@endsection

@push('script-page')
<script>
function elToggle() {
    var t = document.getElementById('letter_type').value;
    document.querySelectorAll('.el-f').forEach(function (el) { el.style.display = 'none'; });
    document.querySelectorAll('.el-' + t).forEach(function (el) { el.style.display = ''; });
    document.getElementById('format-link').href = '{{ url('employee-letters/formats') }}/' + t;
}
elToggle();
</script>
@endpush
