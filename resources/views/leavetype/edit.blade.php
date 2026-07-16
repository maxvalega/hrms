{{ Form::model($leavetype, ['route' => ['leavetype.update', $leavetype->id], 'method' => 'PUT', 'class' => 'needs-validation', 'novalidate', 'data-addr-country' => $leavetype->country ?? '', 'data-addr-state' => $leavetype->state ?? '', 'data-addr-city' => $leavetype->city ?? '']) }}
<div class="modal-body">

    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="form-group">
                {{ Form::label('title', __('Name'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('title', null, ['class' => 'form-control', 'required'=>'required', 'placeholder' => __('Enter Leave Type Name')]) }}
                </div>
                @error('title')
                    <span class="invalid-name" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>


        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="form-group">
                {{ Form::label('days', __('Days Per Year'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::number('days', null, ['class' => 'form-control', 'id' => 'days', 'placeholder' => __('Enter Days / Year'), 'min' => '0', 'step' => '0.01']) }}
                </div>
               
            </div>
        </div>

        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="form-group">
                {{ Form::label('monthly_credit', __('Monthly Credit'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::number('monthly_credit', null, ['class' => 'form-control', 'id' => 'monthly_credit', 'placeholder' => __('Enter Monthly Credit'), 'min' => '0', 'step' => '0.01']) }}
                </div>
            </div>
        </div>

        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="form-group">
                {{ Form::label('annual_credit', __('Annual Credit'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::number('annual_credit', null, ['class' => 'form-control', 'id' => 'annual_credit', 'placeholder' => __('Enter Annual Credit'), 'min' => '0', 'step' => '0.01']) }}
                </div>
            </div>
        </div>

        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="form-group">
                {{ Form::label('approval_requirement', __('Approval Requirement'), ['class' => 'form-label']) }}
                {{ Form::select('approval_requirement', ['subordinate' => __('Subordinate Approval'), 'na' => __('NA')], $leavetype->approval_requirement ?? 'na', ['class' => 'form-control']) }}
            </div>
        </div>

        {{-- Policy matrix fields (new) --}}
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('policy_code', __('Policy Code'), ['class' => 'form-label']) }}
                {{ Form::text('policy_code', $leavetype->policy_code ?? null, ['class' => 'form-control']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('credit_frequency', __('Credit Frequency'), ['class' => 'form-label']) }}
                {{ Form::select('credit_frequency', [
                    'monthly' => __('Monthly'),
                    'annual' => __('Annual'),
                    'earned' => __('As earned'),
                    'monthly_cap' => __('Monthly cap'),
                ], $leavetype->credit_frequency ?? 'monthly', ['class' => 'form-control']) }}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group form-check mt-4">
                {{ Form::checkbox('is_prorata', 1, $leavetype->is_prorata ?? true, ['class' => 'form-check-input', 'id' => 'is_prorata']) }}
                <label class="form-check-label" for="is_prorata">{{ __('Prorata on joining') }}</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group form-check mt-4">
                {{ Form::checkbox('is_carry_forward', 1, !empty($leavetype->is_carry_forward), ['class' => 'form-check-input', 'id' => 'is_carry_forward']) }}
                <label class="form-check-label" for="is_carry_forward">{{ __('Carry Forward') }}</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group form-check mt-4">
                {{ Form::checkbox('is_encashable', 1, !empty($leavetype->is_encashable), ['class' => 'form-check-input', 'id' => 'is_encashable']) }}
                <label class="form-check-label" for="is_encashable">{{ __('Encashable') }}</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('max_carry_forward', __('Max Carry Forward'), ['class' => 'form-label']) }}
                {{ Form::number('max_carry_forward', $leavetype->max_carry_forward ?? null, ['class' => 'form-control', 'min' => '0', 'step' => '0.01']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('max_encash_on_exit', __('Max Encash on Exit'), ['class' => 'form-label']) }}
                {{ Form::number('max_encash_on_exit', $leavetype->max_encash_on_exit ?? null, ['class' => 'form-control', 'min' => '0', 'step' => '0.01']) }}
            </div>
        </div>
        @php
            $noticePresets = \App\Services\LeavePolicyService::noticeRulePresets();
            $selectedNoticePresets = \App\Services\LeavePolicyService::selectedNoticeRulePresetKeys($leavetype->notice_rules ?? null);
        @endphp
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('notice_rule_presets', __('Notice Requirements'), ['class' => 'form-label']) }}
                <select name="notice_rule_presets[]" class="form-control" multiple size="5">
                    @foreach($noticePresets as $key => $preset)
                        <option value="{{ $key }}" @selected(in_array($key, $selectedNoticePresets, true))>{{ __($preset['label']) }}</option>
                    @endforeach
                </select>
                <small class="text-muted">{{ __('Optional. Hold Ctrl/Cmd to select multiple bands.') }}</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('min_notice_days', __('Min Notice (calendar days)'), ['class' => 'form-label']) }}
                {{ Form::number('min_notice_days', $leavetype->min_notice_days ?? null, ['class' => 'form-control', 'min' => '0']) }}
                <small class="text-muted">{{ __('Used only when no notice bands are selected above.') }}</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('monthly_limit', __('Monthly Limit'), ['class' => 'form-label']) }}
                {{ Form::number('monthly_limit', $leavetype->monthly_limit ?? null, ['class' => 'form-control', 'min' => '0', 'step' => '0.5']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('max_consecutive_days', __('Max Consecutive Days'), ['class' => 'form-label']) }}
                {{ Form::number('max_consecutive_days', $leavetype->max_consecutive_days ?? null, ['class' => 'form-control', 'min' => '0', 'step' => '0.5']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group form-check mt-4">
                {{ Form::checkbox('requires_family_relation', 1, !empty($leavetype->requires_family_relation), ['class' => 'form-check-input', 'id' => 'requires_family_relation']) }}
                <label class="form-check-label" for="requires_family_relation">{{ __('Requires immediate family') }}</label>
            </div>
        </div>
        @php $eligible = $leavetype->eligible_employee_types ?? []; @endphp
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('eligible_employee_types', __('Eligible Employment Types'), ['class' => 'form-label']) }}
                <select name="eligible_employee_types[]" class="form-control" multiple>
                    <option value="full_time" @selected(in_array('full_time', $eligible))>{{ __('Full-time') }}</option>
                    <option value="intern" @selected(in_array('intern', $eligible))>{{ __('Intern') }}</option>
                    <option value="part_time" @selected(in_array('part_time', $eligible))>{{ __('Part-time') }}</option>
                    <option value="consultant" @selected(in_array('consultant', $eligible))>{{ __('Consultant') }}</option>
                    <option value="mgmt_trainee" @selected(in_array('mgmt_trainee', $eligible))>{{ __('Management Trainee') }}</option>
                </select>
                <small class="text-muted">{{ __('Leave empty for all employees') }}</small>
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('policy_notes', __('Policy Notes'), ['class' => 'form-label']) }}
                {{ Form::textarea('policy_notes', $leavetype->policy_notes ?? null, ['class' => 'form-control', 'rows' => 2]) }}
            </div>
        </div>

        <div class="col-md-4"><div class="form-group"><label class="form-label">{{ __('Country') }}</label><select name="country" class="form-control addr-country"><option value="">{{ __('Select Country') }}</option></select></div></div>
        <div class="col-md-4"><div class="form-group"><label class="form-label">{{ __('State') }}</label><select name="state" class="form-control addr-state"><option value="">{{ __('Select State') }}</option></select></div></div>
        <div class="col-md-4"><div class="form-group"><label class="form-label">{{ __('City') }}</label><select name="city" class="form-control addr-city"><option value="">{{ __('Select City') }}</option></select></div></div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
</div>
{{ Form::close() }}

<script>
    (function() {
        var isSyncing = false;

        function toNumber(value) {
            var parsed = parseFloat(value);
            return Number.isFinite(parsed) ? parsed : null;
        }

        function toFixedTwo(value) {
            return (Math.round(value * 100) / 100).toFixed(2);
        }

        function setIfChanged(input, value) {
            if (!input) return;
            if (String(input.value) !== String(value)) {
                input.value = value;
            }
        }

        function syncFromDays(daysInput, monthlyInput, annualInput) {
            var days = toNumber(daysInput.value);
            if (days === null) return;

            setIfChanged(annualInput, toFixedTwo(days));
            setIfChanged(monthlyInput, toFixedTwo(days / 12));
        }

        function syncFromAnnual(daysInput, monthlyInput, annualInput) {
            var annual = toNumber(annualInput.value);
            if (annual === null) return;

            setIfChanged(daysInput, toFixedTwo(annual));
            setIfChanged(monthlyInput, toFixedTwo(annual / 12));
        }

        function syncFromMonthly(daysInput, monthlyInput, annualInput) {
            var monthly = toNumber(monthlyInput.value);
            if (monthly === null) return;

            var annual = monthly * 12;
            setIfChanged(annualInput, toFixedTwo(annual));
            setIfChanged(daysInput, toFixedTwo(annual));
        }

        $(document).on('input change', '#days, #annual_credit, #monthly_credit', function() {
            if (isSyncing) return;

            var daysInput = document.getElementById('days');
            var monthlyInput = document.getElementById('monthly_credit');
            var annualInput = document.getElementById('annual_credit');

            if (!daysInput || !monthlyInput || !annualInput) return;

            isSyncing = true;
            if (this.id === 'days') {
                syncFromDays(daysInput, monthlyInput, annualInput);
            } else if (this.id === 'annual_credit') {
                syncFromAnnual(daysInput, monthlyInput, annualInput);
            } else if (this.id === 'monthly_credit') {
                syncFromMonthly(daysInput, monthlyInput, annualInput);
            }
            isSyncing = false;
        });
    })();
</script>




