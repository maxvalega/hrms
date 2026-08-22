{{ Form::open(['route' => 'leave.opening.balance.store', 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="alert alert-info mb-3">
        {{ __('Set Privilege Leave opening balance for the current cycle. Monthly accrual (1.5/month) continues on top of this.') }}
        @if(!empty($cycle['label']))
            <br><small>{{ __('Cycle') }}: {{ $cycle['label'] }}</small>
        @endif
    </div>
    <div class="row g-2">
        <div class="col-12">
            <div class="form-group mb-2">
                {{ Form::label('employee_id', __('Employee'), ['class' => 'form-label fw-semibold']) }}<x-required></x-required>
                <select name="employee_id" id="opening_employee_id" class="form-control form-select" required style="min-height: 44px; font-size: 16px;">
                    <option value="">{{ __('Select employee…') }}</option>
                    @foreach($employees as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-12">
            <div class="form-group mb-2">
                {{ Form::label('leave_type_id', __('Privilege Leave Type'), ['class' => 'form-label fw-semibold']) }}<x-required></x-required>
                <select name="leave_type_id" id="opening_leave_type_id" class="form-control form-select" required style="min-height: 44px; font-size: 16px;">
                    <option value="">{{ __('Select leave type…') }}</option>
                    @foreach($leaveTypes as $id => $title)
                        <option value="{{ $id }}">{{ $title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-12 col-sm-6">
            <div class="form-group mb-2">
                {{ Form::label('days', __('Opening Balance (days)'), ['class' => 'form-label fw-semibold']) }}<x-required></x-required>
                {{ Form::number('days', 0, ['class' => 'form-control', 'required' => 'required', 'min' => '0', 'step' => '0.5', 'style' => 'min-height:44px;font-size:16px;']) }}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group mb-0">
                {{ Form::label('notes', __('Notes / Remark'), ['class' => 'form-label fw-semibold']) }}
                {{ Form::textarea('notes', null, [
                    'class' => 'form-control',
                    'rows' => '3',
                    'placeholder' => __('Optional notes'),
                    'style' => 'min-height:90px;font-size:16px;color:#212529;background:#fff;',
                ]) }}
            </div>
        </div>
    </div>
</div>
<div class="modal-footer flex-column flex-sm-row gap-2 align-items-stretch">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <input type="submit" value="{{ __('Save Opening Balance') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
