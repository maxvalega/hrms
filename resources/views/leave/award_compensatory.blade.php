{{ Form::open(['route' => 'leave.award.compensatory.store', 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('employee_id', __('Employee'), ['class' => 'col-form-label']) }}<x-required></x-required>
                {{ Form::select('employee_id', $employees, null, ['class' => 'form-control select', 'required' => 'required', 'placeholder' => __('Select Employee')]) }}
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {{-- NEW preferred: hours with matrix conversion 4h=0.5 / 8h=1 --}}
                {{ Form::label('hours', __('Hours Worked'), ['class' => 'col-form-label']) }}
                {{ Form::number('hours', null, ['class' => 'form-control', 'min' => '0', 'step' => '0.5', 'placeholder' => __('4 = half day, 8 = full day')]) }}
                <small class="text-muted">{{ __('Policy: 4 hrs = 1/2 day, 8 hrs = Full day') }}</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{-- OLD days field kept (optional fallback) --}}
                {{ Form::label('days', __('Days (optional fallback)'), ['class' => 'col-form-label']) }}
                {{ Form::number('days', null, ['class' => 'form-control', 'min' => '0.5', 'step' => '0.5', 'placeholder' => __('Or enter days directly')]) }}
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('earned_date', __('Earned Date'), ['class' => 'col-form-label']) }}<x-required></x-required>
                {{ Form::text('earned_date', null, ['class' => 'form-control current_date', 'required' => 'required', 'autocomplete' => 'off']) }}
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('reason', __('Reason'), ['class' => 'col-form-label']) }}<x-required></x-required>
                {{ Form::textarea('reason', null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('Reason for awarding compensatory leave'), 'rows' => '3']) }}
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('notes', __('Notes (Optional)'), ['class' => 'col-form-label']) }}
                {{ Form::textarea('notes', null, ['class' => 'form-control', 'placeholder' => __('Additional notes'), 'rows' => '2']) }}
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <input type="submit" value="{{ __('Award') }}" class="btn btn-primary">
</div>

{{ Form::close() }}

<script>
    $(document).ready(function() {
        var now = new Date();
        var month = (now.getMonth() + 1);
        var day = now.getDate();
        if (month < 10) month = "0" + month;
        if (day < 10) day = "0" + day;
        var today = now.getFullYear() + '-' + month + '-' + day;
        $('.current_date').val(today);
    });
</script>
