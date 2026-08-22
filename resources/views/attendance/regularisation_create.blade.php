{{ Form::open(['route' => 'attendance.regularisation.store', 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row g-2">
        @if (\Auth::user()->type != 'employee')
            <div class="col-12">
                <div class="form-group mb-2">
                    {{ Form::label('employee_id', __('Employee'), ['class' => 'form-label fw-semibold']) }}<x-required></x-required>
                    <select name="employee_id" class="form-control form-select" required style="min-height:44px;font-size:16px;">
                        <option value="">{{ __('Select Employee') }}</option>
                        @foreach($employees as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @else
            {{ Form::hidden('employee_id', $employees->keys()->first()) }}
        @endif
        <div class="col-12">
            <div class="form-group mb-2">
                {{ Form::label('date', __('Date'), ['class' => 'form-label fw-semibold']) }}<x-required></x-required>
                {{ Form::text('date', null, ['class' => 'form-control d_week', 'required' => 'required', 'autocomplete' => 'off', 'style' => 'min-height:44px;font-size:16px;']) }}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group mb-0">
                {{ Form::label('reason', __('Reason'), ['class' => 'form-label fw-semibold']) }}<x-required></x-required>
                {{ Form::textarea('reason', null, [
                    'class' => 'form-control',
                    'required' => 'required',
                    'rows' => '4',
                    'placeholder' => __('Describe on-ground / field duty'),
                    'style' => 'min-height:110px;font-size:16px;color:#212529;background:#fff;',
                ]) }}
            </div>
        </div>
    </div>
</div>
<div class="modal-footer flex-column flex-sm-row gap-2 align-items-stretch">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <input type="submit" value="{{ __('Submit') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
