{{ Form::open(['route' => 'leave.bereavement.grant.store', 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="alert alert-warning mb-3">
        {{ __('Bereavement leave stays hidden until you grant entitlement (usually 7 days) for a qualifying event.') }}
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
                {{ Form::label('leave_type_id', __('Bereavement Leave Type'), ['class' => 'col-form-label']) }}<x-required></x-required>
                {{ Form::select('leave_type_id', $leaveTypes, null, ['class' => 'form-control select', 'required' => 'required', 'placeholder' => __('Select Leave Type')]) }}
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('days', __('Days to Grant'), ['class' => 'col-form-label']) }}<x-required></x-required>
                {{ Form::number('days', 7, ['class' => 'form-control', 'required' => 'required', 'min' => '0.5', 'max' => '7', 'step' => '0.5']) }}
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('notes', __('Reason / Relation'), ['class' => 'col-form-label']) }}<x-required></x-required>
                {{ Form::textarea('notes', null, ['class' => 'form-control', 'required' => 'required', 'rows' => '3', 'placeholder' => __('e.g. Immediate family — parent')]) }}
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <input type="submit" value="{{ __('Grant Bereavement') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
