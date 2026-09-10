<style>
    #choices-multiple + .select2-container { width: 100% !important; }
    .modal-body .form-label { margin-bottom: 6px; }
    .modal-body .form-control, .modal-body .select2-container .select2-selection { min-height: 42px; }
</style>
{{ Form::open(['url' => 'account-assets', 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row g-3">
        <div class="col-md-6">
            <div class="form-group mb-0">
                {{ Form::label('employee_id', __('Employee Name'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::select('employee_id[]', $employee, null, ['class' => 'form-control select2', 'id' => 'choices-multiple', 'multiple' => '', 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group mb-0">
                {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::text('name', null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('Enter Asset Name')]) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group mb-0">
                {{ Form::label('amount', __('Amount'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::number('amount', '', ['class' => 'form-control', 'required' => 'required', 'step' => '0.01', 'placeholder' => __('Enter Amount')]) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group mb-0">
                {{ Form::label('purchase_date', __('Purchase Date'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::text('purchase_date', null, ['class' => 'form-control d_week current_date', 'required' => 'required', 'autocomplete' => 'off']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group mb-0">
                {{ Form::label('supported_date', __('Support Until'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::text('supported_date', null, ['class' => 'form-control d_week current_date', 'required' => 'required', 'autocomplete' => 'off']) }}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group mb-0">
                {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
                {{ Form::textarea('description', null, ['class' => 'form-control', 'rows' => '3', 'placeholder' => __('Enter Description')]) }}
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
</div>
{{ Form::close() }}

<script>
    $(document).ready(function() {
        var now = new Date();
        var month = ('0' + (now.getMonth() + 1)).slice(-2);
        var day = ('0' + now.getDate()).slice(-2);
        var today = now.getFullYear() + '-' + month + '-' + day;
        $('.current_date').each(function() {
            if (!$(this).val()) $(this).val(today);
        });
    });
</script>
