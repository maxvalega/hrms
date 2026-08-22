{{ Form::open(['route' => 'leave.opening.balance.store', 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="alert alert-info mb-3">
        {{ __('Set Privilege Leave opening balance for the current cycle. Monthly accrual (1.5/month) continues on top of this.') }}
        @if(!empty($cycle['label']))
            <br><small>{{ __('Cycle') }}: {{ $cycle['label'] }}</small>
        @endif
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('employee_id', __('Employee'), ['class' => 'col-form-label']) }}<x-required></x-required>
                {{ Form::select('employee_id', $employees, null, ['class' => 'form-control select', 'required' => 'required', 'placeholder' => __('Select Employee')]) }}
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('leave_type_id', __('Privilege Leave Type'), ['class' => 'col-form-label']) }}<x-required></x-required>
                {{ Form::select('leave_type_id', $leaveTypes, null, ['class' => 'form-control select', 'required' => 'required', 'placeholder' => __('Select Leave Type')]) }}
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('days', __('Opening Balance (days)'), ['class' => 'col-form-label']) }}<x-required></x-required>
                {{ Form::number('days', 0, ['class' => 'form-control', 'required' => 'required', 'min' => '0', 'step' => '0.5']) }}
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('notes', __('Notes'), ['class' => 'col-form-label']) }}
                {{ Form::textarea('notes', null, ['class' => 'form-control', 'rows' => '2', 'placeholder' => __('Optional notes')]) }}
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <input type="submit" value="{{ __('Save Opening Balance') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
