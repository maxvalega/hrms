{{ Form::open(['route' => 'leave.bereavement.grant.store', 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="alert alert-warning mb-3">
        {{ __('Bereavement leave stays hidden until you grant entitlement (usually 7 days) for a qualifying event.') }}
    </div>
    <div class="row g-2">
        <div class="col-12">
            <div class="form-group mb-2">
                {{ Form::label('employee_id', __('Grant to Employee'), ['class' => 'form-label fw-semibold']) }}<x-required></x-required>
                <select name="employee_id" id="bereavement_employee_id" class="form-control form-select" required style="min-height: 44px; font-size: 16px;">
                    <option value="">{{ __('Select employee…') }}</option>
                    @foreach($employees as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <small class="text-muted">{{ __('Choose whom you are granting bereavement leave to.') }}</small>
            </div>
        </div>
        <div class="col-12">
            <div class="form-group mb-2">
                {{ Form::label('leave_type_id', __('Bereavement Leave Type'), ['class' => 'form-label fw-semibold']) }}<x-required></x-required>
                <select name="leave_type_id" id="bereavement_leave_type_id" class="form-control form-select" required style="min-height: 44px; font-size: 16px;">
                    <option value="">{{ __('Select leave type…') }}</option>
                    @foreach($leaveTypes as $id => $title)
                        <option value="{{ $id }}">{{ $title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-12 col-sm-6">
            <div class="form-group mb-2">
                {{ Form::label('days', __('Days to Grant'), ['class' => 'form-label fw-semibold']) }}<x-required></x-required>
                {{ Form::number('days', 7, ['class' => 'form-control', 'required' => 'required', 'min' => '0.5', 'max' => '7', 'step' => '0.5', 'style' => 'min-height:44px;font-size:16px;']) }}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group mb-0">
                {{ Form::label('notes', __('Reason / Relation (remark)'), ['class' => 'form-label fw-semibold']) }}<x-required></x-required>
                {{ Form::textarea('notes', null, [
                    'class' => 'form-control',
                    'required' => 'required',
                    'rows' => '4',
                    'placeholder' => __('e.g. Immediate family — parent / spouse'),
                    'style' => 'min-height:110px;font-size:16px;color:#212529;background:#fff;',
                ]) }}
            </div>
        </div>
    </div>
</div>
<div class="modal-footer flex-column flex-sm-row gap-2 align-items-stretch">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <input type="submit" value="{{ __('Grant Bereavement') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
