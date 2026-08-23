<form id="grant-bereavement-form" action="{{ route('leave.bereavement.grant.store') }}" method="post" class="needs-validation" novalidate>
@csrf
<div class="modal-body">
    <div id="grant-bereavement-alert" class="alert d-none mb-3" role="alert"></div>

    @if(!empty($formError))
        <div class="alert alert-danger mb-3">{{ $formError }}</div>
    @else
        <div class="alert alert-warning mb-3">
            {{ __('Bereavement leave stays hidden until you grant entitlement (usually 7 days) for a qualifying event.') }}
        </div>
    @endif

    <div class="row g-2">
        <div class="col-12">
            <div class="form-group mb-2">
                <label for="bereavement_employee_id" class="form-label fw-semibold">{{ __('Grant to Employee') }} <span class="text-danger">*</span></label>
                <select name="employee_id" id="bereavement_employee_id" class="form-control form-select" required
                    style="min-height: 48px; font-size: 16px; color: #212529; background: #fff;"
                    @if(!empty($formError)) disabled @endif>
                    <option value="">{{ __('Select employee…') }}</option>
                    @foreach($employees as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <div id="grant-to-preview" class="mt-2 p-2 border rounded bg-light d-none">
                    <strong>{{ __('Granting to') }}:</strong> <span id="grant-to-name"></span>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="form-group mb-2">
                <label for="bereavement_leave_type_id" class="form-label fw-semibold">{{ __('Bereavement Leave Type') }} <span class="text-danger">*</span></label>
                <select name="leave_type_id" id="bereavement_leave_type_id" class="form-control form-select" required
                    style="min-height: 48px; font-size: 16px; color: #212529; background: #fff;"
                    @if(!empty($formError)) disabled @endif>
                    <option value="">{{ __('Select leave type…') }}</option>
                    @foreach($leaveTypes as $id => $title)
                        <option value="{{ $id }}" @if($leaveTypes->count() === 1) selected @endif>{{ $title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-12 col-sm-6">
            <div class="form-group mb-2">
                <label for="bereavement_days" class="form-label fw-semibold">{{ __('Days to Grant') }} <span class="text-danger">*</span></label>
                <input type="number" name="days" id="bereavement_days" value="7" class="form-control" required
                    min="0.5" max="7" step="0.5" style="min-height:48px;font-size:16px;"
                    @if(!empty($formError)) disabled @endif>
            </div>
        </div>
        <div class="col-12">
            <div class="form-group mb-0">
                <label for="bereavement_notes" class="form-label fw-semibold">{{ __('Reason / Relation (remark)') }} <span class="text-danger">*</span></label>
                <textarea name="notes" id="bereavement_notes" class="form-control" required rows="4"
                    placeholder="{{ __('e.g. Immediate family — parent / spouse') }}"
                    style="min-height:110px;font-size:16px;color:#212529;background:#fff;"
                    @if(!empty($formError)) disabled @endif></textarea>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer flex-column flex-sm-row gap-2 align-items-stretch">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <button type="submit" class="btn btn-primary" id="grant-bereavement-submit" @if(!empty($formError)) disabled @endif>
        {{ __('Grant Bereavement') }}
    </button>
</div>
</form>
