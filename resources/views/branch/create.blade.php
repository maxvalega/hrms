{{ Form::open(['url' => 'branch', 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="form-group">
                {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('name', null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('Enter Branch Name')]) }}
                </div>
                @error('name')
                    <span class="invalid-name" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="branch_country" class="form-label">{{ __('Country') }}</label>
                <select name="country" id="branch_country" class="form-control">
                    <option value="">{{ __('Select Country') }}</option>
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="branch_state" class="form-label">{{ __('State') }}</label>
                <select name="state" id="branch_state" class="form-control">
                    <option value="">{{ __('Select State') }}</option>
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="branch_city" class="form-label">{{ __('City') }}</label>
                <select name="city" id="branch_city" class="form-control">
                    <option value="">{{ __('Select City') }}</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('latitude', __('Latitude (for geo tagging)'), ['class' => 'form-label']) }}
                {{ Form::text('latitude', null, ['class' => 'form-control', 'placeholder' => '28.4595']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('longitude', __('Longitude (for geo tagging)'), ['class' => 'form-label']) }}
                {{ Form::text('longitude', null, ['class' => 'form-control', 'placeholder' => '77.0266']) }}
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
