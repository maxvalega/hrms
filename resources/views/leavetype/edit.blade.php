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
            <div class="form-group" data-checkbox-group="notice">
                {{ Form::label('notice_rule_presets', __('Notice Requirements'), ['class' => 'form-label']) }}
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input js-select-all" id="notice_select_all_edit" @checked(count($noticePresets) > 0 && count($selectedNoticePresets) === count($noticePresets))>
                    <label class="form-check-label fw-bold" for="notice_select_all_edit">{{ __('Select All') }}</label>
                </div>
                @foreach($noticePresets as $key => $preset)
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input js-group-item" name="notice_rule_presets[]" value="{{ $key }}" id="notice_{{ $key }}_edit" @checked(in_array($key, $selectedNoticePresets, true))>
                        <label class="form-check-label" for="notice_{{ $key }}_edit">{{ __($preset['label']) }}</label>
                    </div>
                @endforeach
                <small class="text-muted">{{ __('Optional') }}</small>
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
        @php
            $eligible = $leavetype->eligible_employee_types ?? [];
            $employmentTypes = [
                'full_time' => __('Full-time'),
                'intern' => __('Intern'),
                'part_time' => __('Part-time'),
                'consultant' => __('Consultant'),
                'mgmt_trainee' => __('Management Trainee'),
            ];
        @endphp
        <div class="col-md-12">
            <div class="form-group" data-checkbox-group="eligible">
                {{ Form::label('eligible_employee_types', __('Eligible Employment Types'), ['class' => 'form-label']) }}
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input js-select-all" id="eligible_select_all_edit" @checked(count($employmentTypes) > 0 && count(array_intersect(array_keys($employmentTypes), $eligible)) === count($employmentTypes))>
                    <label class="form-check-label fw-bold" for="eligible_select_all_edit">{{ __('Select All') }}</label>
                </div>
                @foreach($employmentTypes as $value => $label)
                    <div class="form-check form-check-inline">
                        <input type="checkbox" class="form-check-input js-group-item" name="eligible_employee_types[]" value="{{ $value }}" id="eligible_{{ $value }}_edit" @checked(in_array($value, $eligible))>
                        <label class="form-check-label" for="eligible_{{ $value }}_edit">{{ $label }}</label>
                    </div>
                @endforeach
                <div><small class="text-muted">{{ __('Leave empty for all employees') }}</small></div>
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

        $(document).off('change.ltCheckAll').on('change.ltCheckAll', '.js-select-all', function() {
            $(this).closest('[data-checkbox-group]').find('.js-group-item').prop('checked', this.checked);
        });
        $(document).off('change.ltCheckItem').on('change.ltCheckItem', '.js-group-item', function() {
            var $group = $(this).closest('[data-checkbox-group]');
            var $items = $group.find('.js-group-item');
            $group.find('.js-select-all').prop('checked', $items.length > 0 && $items.filter(':checked').length === $items.length);
        });
    })();
</script>




