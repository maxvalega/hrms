{{ Form::model($user, ['route' => ['user.update', $user->id], 'method' => 'PUT', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group">
            {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}<x-required></x-required>
            <div class="form-icon-user">
                {!! Form::text('name', null, ['class' => 'form-control', 'required' => 'required', 'placeholder'=>'Enter Name']) !!}
            </div>
        </div>
        <div class="form-group">
            {{ Form::label('email', __('Email'), ['class' => 'form-label']) }}<x-required></x-required>
            <div class="form-icon-user">
                {!! Form::email('email', null, ['class' => 'form-control', 'required' => 'required','placeholder'=>'Enter Email']) !!}
            </div>
        </div>

        @if (\Auth::user()->type == 'super admin' && ($user->type ?? '') === 'company' && \Illuminate\Support\Facades\Schema::hasColumn('users', 'subdomain'))
            <div class="form-group">
                {{ Form::label('subdomain', __('Company Subdomain'), ['class' => 'form-label']) }}
                <div class="input-group">
                    {!! Form::text('subdomain', $user->subdomain ?? null, [
                        'class' => 'form-control',
                        'placeholder' => 'spectal',
                        'pattern' => '[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?',
                        'autocomplete' => 'off',
                    ]) !!}
                    <span class="input-group-text">.{{ config('tenancy.base_domain', 'jemini.co.in') }}</span>
                </div>
                <small class="text-muted">
                    {{ __('Portal URL') }}:
                    <strong>https://{{ ($user->subdomain ?: 'subdomain') }}.{{ config('tenancy.base_domain', 'jemini.co.in') }}</strong>
                </small>
            </div>
        @endif

        @if (\Auth::user()->type != 'super admin')
            <div class="form-group ">
                {{ Form::label('role', __('User Role'), ['class' => 'form-label']) }}<x-required></x-required>
                <div class="form-icon-user">
                    {!! Form::select('role', $roles, $user->roles, ['class' => 'form-control', 'required' => 'required']) !!}
                </div>
                @error('role')
                    <span class="invalid-role" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        @endif
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">

</div>
{!! Form::close() !!}


