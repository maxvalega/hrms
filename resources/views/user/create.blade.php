{!! Form::open(['route' => 'user.store', 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) !!}
<div class="modal-body">
    <div class="row">
        <div class="form-group">
            {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}<x-required></x-required>
            <div class="form-icon-user">
                {!! Form::text('name', null, [
                    'class' => 'form-control',
                    'required' => 'required',
                    'placeholder' => __('Enter Name'),
                ]) !!}
            </div>
        </div>
        <div class="form-group">
            {{ Form::label('email', __('Email'), ['class' => 'form-label']) }}<x-required></x-required>
            <div class="form-icon-user">
                {!! Form::email('email', null, [
                    'class' => 'form-control',
                    'required' => 'required',
                    'placeholder' => __('Enter Email'),
                ]) !!}
            </div>
        </div>
        @if (\Auth::user()->type == 'super admin' && \Illuminate\Support\Facades\Schema::hasColumn('users', 'subdomain'))
            <div class="form-group">
                {{ Form::label('subdomain', __('Company Subdomain'), ['class' => 'form-label']) }}
                <div class="input-group">
                    {!! Form::text('subdomain', null, [
                        'class' => 'form-control',
                        'placeholder' => 'spectal',
                        'pattern' => '[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?',
                        'autocomplete' => 'off',
                    ]) !!}
                    <span class="input-group-text">.{{ config('tenancy.base_domain', 'jemini.co.in') }}</span>
                </div>
                <small class="text-muted">
                    {{ __('Optional. Once set, company users must login only at this portal (not on the main domain).') }}
                </small>
            </div>
        @endif
        @if (\Auth::user()->type != 'super admin')
            <div class="form-group">
                {{ Form::label('role', __('User Role'), ['class' => 'form-label']) }}<x-required></x-required>
                <div class="form-icon-user">
                    {!! Form::select('role', $roles, null, ['class' => 'form-control', 'required' => 'required']) !!}
                </div>
                @error('role')
                    <span class="invalid-role" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        @endif
        <div class="col-md-5 mb-3">
            <label for="password_switch">{{ __('Login is enable') }}</label>
            <div class="form-check form-switch custom-switch-v1 float-end">
                <input type="checkbox" name="password_switch" class="form-check-input input-primary pointer"
                    value="on" id="password_switch">
                <label class="form-check-label" for="password_switch"></label>
            </div>
        </div>
        <div class="col-md-12 ps_div d-none">
            <div class="form-group">
                {{ Form::label('password', __('Password'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::password('password', ['class' => 'form-control', 'placeholder' => __('Enter Password'), 'minlength' => '6']) }}
                @error('password')
                    <small class="invalid-password" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </small>
                @enderror
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">

</div>
{!! Form::close() !!}