{{ Form::open(['route' => 'attendance.regularisation.store', 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        @if (\Auth::user()->type != 'employee')
            <div class="col-md-12">
                <div class="form-group">
                    {{ Form::label('employee_id', __('Employee'), ['class' => 'col-form-label']) }}<x-required></x-required>
                    {{ Form::select('employee_id', $employees, null, ['class' => 'form-control select', 'required' => 'required', 'placeholder' => __('Select Employee')]) }}
                </div>
            </div>
        @else
            {{ Form::hidden('employee_id', $employees->keys()->first()) }}
        @endif
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('date', __('Date'), ['class' => 'col-form-label']) }}<x-required></x-required>
                {{ Form::text('date', null, ['class' => 'form-control d_week', 'required' => 'required', 'autocomplete' => 'off']) }}
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('reason', __('Reason'), ['class' => 'col-form-label']) }}<x-required></x-required>
                {{ Form::textarea('reason', null, ['class' => 'form-control', 'required' => 'required', 'rows' => '3', 'placeholder' => __('Describe on-ground / field duty')]) }}
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <input type="submit" value="{{ __('Submit') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
