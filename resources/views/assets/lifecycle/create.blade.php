{{ Form::open(['url' => 'account-assets', 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <p class="text-muted small mb-3">{{ __('Document an existing company asset. Leave employee empty to keep it in inventory, or assign it now.') }}</p>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('name', __('Asset name'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::text('name', null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('e.g. MacBook Pro 14')]) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('category', __('Category'), ['class' => 'form-label']) }}
                {{ Form::select('category', ['' => __('Select category')] + \App\Models\Asset::CATEGORIES, null, ['class' => 'form-control']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('asset_code', __('Asset code'), ['class' => 'form-label']) }}
                {{ Form::text('asset_code', null, ['class' => 'form-control', 'placeholder' => __('Auto if left blank')]) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('serial_number', __('Serial number'), ['class' => 'form-label']) }}
                {{ Form::text('serial_number', null, ['class' => 'form-control', 'placeholder' => __('Manufacturer serial')]) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('brand', __('Brand'), ['class' => 'form-label']) }}
                {{ Form::text('brand', null, ['class' => 'form-control', 'placeholder' => __('e.g. Apple')]) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('model', __('Model'), ['class' => 'form-label']) }}
                {{ Form::text('model', null, ['class' => 'form-control', 'placeholder' => __('e.g. A2442')]) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('amount', __('Purchase amount'), ['class' => 'form-label']) }}
                {{ Form::number('amount', null, ['class' => 'form-control', 'step' => '0.01', 'placeholder' => __('0.00')]) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('condition', __('Condition'), ['class' => 'form-label']) }}
                {{ Form::select('condition', \App\Models\Asset::CONDITIONS, 'good', ['class' => 'form-control']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('purchase_date', __('Purchase date'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::text('purchase_date', null, ['class' => 'form-control d_week current_date', 'required' => 'required', 'autocomplete' => 'off']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('supported_date', __('Support until'), ['class' => 'form-label']) }}
                {{ Form::text('supported_date', null, ['class' => 'form-control d_week current_date', 'autocomplete' => 'off']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('location', __('Location'), ['class' => 'form-label']) }}
                {{ Form::text('location', null, ['class' => 'form-control', 'placeholder' => __('e.g. IT store / Head office')]) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('employee_id', __('Assign now (optional)'), ['class' => 'form-label']) }}
                {{ Form::select('employee_id', ['' => __('Keep in inventory')] + $employee->toArray(), null, ['class' => 'form-control']) }}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('description', __('Notes'), ['class' => 'form-label']) }}
                {{ Form::textarea('description', null, ['class' => 'form-control', 'rows' => 2, 'placeholder' => __('Any identifying details')]) }}
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Record asset') }}" class="btn btn-primary">
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
