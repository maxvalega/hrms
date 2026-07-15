@php
    $setting = App\Models\Utility::settings();
    $plan = App\Models\Utility::getChatGPTSettings();
@endphp
{{ Form::open(['url' => 'leave', 'method' => 'post', 'class' => 'needs-validation', 'novalidate', 'files' => true]) }}
<div class="modal-body">

    @if (!empty($isProbationRestricted) && $isProbationRestricted)
        <div class="alert alert-danger mb-3" role="alert" id="probation-warning-alert">
            <i class="ti ti-alert-circle me-1"></i>
            {{ $probationWarningMessage ?? __('You cannot apply for leave until your probation period is completed.') }}
        </div>
    @endif

    @if ($plan->enable_chatgpt == 'on')
        <div class="card-footer text-end">
            <a href="#" class="btn btn-sm btn-primary" data-size="medium" data-ajax-popup-over="true"
                data-url="{{ route('generate', ['leave']) }}" data-bs-toggle="tooltip" data-bs-placement="top"
                title="{{ __('Generate') }}" data-title="{{ __('Generate Content With AI') }}">
                <i class="fas fa-robot"></i>{{ __(' Generate With AI') }}
            </a>
        </div>
    @endif

    @if (\Auth::user()->type != 'employee')
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    {{ Form::label('employee_id', __('Employee'), ['class' => 'col-form-label']) }}<x-required></x-required>
                    {{ Form::select('employee_id', $employees, null, ['class' => 'form-control', 'id' => 'employee_id', 'required' => 'required', 'placeholder' => __('Select Employee')]) }}
                </div>
            </div>
        </div>
    @else
        {!! Form::hidden('employee_id', !empty($employees) ? $employees->id : 0, ['id' => 'employee_id']) !!}
    @endif
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('leave_type_id', __('Leave Type'), ['class' => 'col-form-label']) }}<x-required></x-required>
                <select name="leave_type_id" id="leave_type_id" class="form-control select" required>
                    <option value="">{{ __('Select Leave Type') }}</option>
                    @foreach ($leavetypes as $leave)
                        @if (str_starts_with($leave->title ?? '', '[OLD]'))
                            {{-- Keep old types visible but clearly marked; prefer policy_code types --}}
                        @endif
                        <option value="{{ $leave->id }}"
                            data-title="{{ $leave->title }}"
                            data-approval="{{ $leave->approval_requirement ?? 'na' }}"
                            data-policy="{{ $leave->policy_code ?? '' }}"
                            data-requires-family="{{ !empty($leave->requires_family_relation) ? '1' : '0' }}">
                            {{ $leave->title }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="row" id="leave-balance-row" style="display:none;">
        <div class="col-md-12">
            <div class="alert alert-info py-2 mb-3" id="leave-balance-info">
                <div><strong>{{ __('Credit Mode') }}:</strong> <span id="lb-mode">-</span></div>
                <div><strong>{{ __('Total') }}:</strong> <span id="lb-total">0</span> | <strong>{{ __('Used') }}:</strong> <span id="lb-used">0</span> | <strong>{{ __('Pending') }}:</strong> <span id="lb-pending">0</span></div>
                <div id="vacation-only-balance"><strong>{{ __('Monthly Accrual') }}:</strong> <span id="lb-monthly">0</span> | <strong>{{ __('Carry Forward') }}:</strong> <span id="lb-carry">0</span></div>
                <div><strong>{{ __('Available') }}:</strong> <span id="lb-available">0</span> <span id="encashable-inline">| <strong>{{ __('Encashable') }}:</strong> <span id="lb-encashable">0</span></span></div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('start_date', __('Start Date'), ['class' => 'col-form-label']) }}<x-required></x-required>
                {{ Form::text('start_date', null, ['class' => 'form-control d_week current_date', 'required' => 'required', 'autocomplete' => 'off']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('end_date', __('End Date'), ['class' => 'col-form-label']) }}<x-required></x-required>
                {{ Form::text('end_date', null, ['class' => 'form-control d_week current_date', 'required' => 'required', 'autocomplete' => 'off']) }}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('day_type', __('Day Type'), ['class' => 'col-form-label']) }}<x-required></x-required>
                <div class="form-check">
                    <input class="form-check-input day-type-radio" type="radio" name="day_type_choice" id="day_type_full" value="full_day" checked required>
                    <label class="form-check-label" for="day_type_full">
                        {{ __('Full Day') }}
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input day-type-radio" type="radio" name="day_type_choice" id="day_type_half" value="half_day" required>
                    <label class="form-check-label" for="day_type_half">
                        {{ __('Half Day') }}
                    </label>
                </div>
                <div id="half_day_options" style="margin-left: 30px; margin-top: 10px; display: none;">
                    <div class="form-check">
                        <input class="form-check-input half-day-option" type="radio" name="half_day_type" id="first_half" value="first_half">
                        <label class="form-check-label" for="first_half">
                            {{ __('First Half') }}
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input half-day-option" type="radio" name="half_day_type" id="second_half" value="second_half">
                        <label class="form-check-label" for="second_half">
                            {{ __('Second Half') }}
                        </label>
                    </div>
                </div>
                <input type="hidden" id="day_type_hidden" name="day_type" value="full_day">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('leave_reason', __('Leave Reason'), ['class' => 'col-form-label']) }}<x-required></x-required>
                {{ Form::textarea('leave_reason', null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('Leave Reason'), 'rows' => '3']) }}
            </div>
        </div>
    </div>

    <div class="row" id="family-relation-row" style="display:none;">
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('family_relation', __('Immediate Family Relation'), ['class' => 'col-form-label']) }}
                {{ Form::select('family_relation', [
                    '' => __('Select relation'),
                    'spouse' => __('Spouse'),
                    'parent' => __('Parent'),
                    'child' => __('Child'),
                    'sibling' => __('Sibling'),
                ], null, ['class' => 'form-control', 'id' => 'family_relation']) }}
                <small class="text-muted">{{ __('Required for Bereavement Leave — immediate family only.') }}</small>
            </div>
        </div>
    </div>

    <div class="row" id="medical-certificate-row" style="display: none;">
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('medical_certificate', __('Medical Certificate'), ['class' => 'col-form-label']) }}<x-required></x-required>
                <small class="text-muted d-block mb-2">{{ __('Upload medical certificate (PDF, JPG, PNG). Max size: 5MB') }}</small>
                <div class="custom-file-upload">
                    {{ Form::file('medical_certificate', ['class' => 'form-control form-control-file', 'id' => 'medical_certificate', 'accept' => '.pdf,.jpg,.jpeg,.png']) }}
                </div>
                <small class="text-danger d-block mt-1" id="medical-error-msg"></small>
            </div>
        </div>
    </div>

    <div class="row" id="substitute-row" style="display: none;">
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('substitute_employee_id', __('Substitute Employee'), ['class' => 'col-form-label']) }}
                {{ Form::select('substitute_employee_id', $substitutes, null, ['class' => 'form-control select', 'id' => 'substitute_employee_id', 'placeholder' => __('Select Substitute')]) }}
                <small class="text-muted" id="substitute-note">{{ __('Required for Vacation leave.') }}</small>
            </div>
        </div>
    </div>
    @if (isset($setting['is_enabled']) && $setting['is_enabled'] == 'on')
        <div class="form-group col-md-6">
            {{ Form::label('synchronize_type', __('Synchroniz in Google Calendar ?'), ['class' => 'form-label']) }}
            <div class=" form-switch">
                <input type="checkbox" class="form-check-input mt-2" name="synchronize_type" id="switch-shadow"
                    value="google_calender">
                <label class="form-check-label" for="switch-shadow"></label>
            </div>
        </div>
    @endif
</div>
<div class="modal-footer">
    <button type="button" class="btn  btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    @if (empty($isProbationRestricted) || !$isProbationRestricted)
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    @endif
</div>
{{ Form::close() }}

<script>
    $(document).ready(function() {
        var now = new Date();
        var month = (now.getMonth() + 1);
        var day = now.getDate();
        if (month < 10) month = "0" + month;
        if (day < 10) day = "0" + day;
        var today = now.getFullYear() + '-' + month + '-' + day;
        $('.current_date').val(today);

        // Day type checkbox/radio logic
        $(document).on('change', '.day-type-radio', function() {
            if ($('#day_type_half').is(':checked')) {
                $('#half_day_options').show();
                $('#first_half').prop('checked', true);
                updateDayTypeHidden();
            } else {
                $('#half_day_options').hide();
                updateDayTypeHidden();
            }
        });

        $(document).on('change', '.half-day-option', function() {
            updateDayTypeHidden();
        });

        function updateDayTypeHidden() {
            if ($('#day_type_full').is(':checked')) {
                $('#day_type_hidden').val('full_day');
            } else if ($('#day_type_half').is(':checked')) {
                var halfType = $('input[name="half_day_type"]:checked').val();
                $('#day_type_hidden').val(halfType || 'first_half');
            }
        }

        function parseDate(dateText) {
            if (!dateText) return null;
            var parts = dateText.trim().split('-');
            if (parts.length !== 3) return null;
            return new Date(parts[0], parts[1] - 1, parts[2]);
        }

        function isVacationTitle(title) {
            var value = (title || '').toString().toLowerCase();
            return /(vacation|vaction|vactine|vacatine|vacat)/.test(value);
        }

        function getRequestedDays() {
            var start = parseDate($('#start_date').val());
            var end = parseDate($('#end_date').val());

            if (!start || !end || end < start) {
                return 0;
            }

            var dayType = $('#day_type_hidden').val();
            if (dayType === 'first_half' || dayType === 'second_half') {
                return 0.5;
            }

            var msPerDay = 24 * 60 * 60 * 1000;
            return Math.floor((end - start) / msPerDay) + 1;
        }

        function togglePolicyFields() {
            var selectedOption = $('#leave_type_id').find('option:selected');
            var selectedTitle = selectedOption.data('title') || selectedOption.text();
            var titleLower = selectedTitle.toLowerCase();
            var isVacation = isVacationTitle(titleLower);
            var isSick = /sick|seek/.test(titleLower);
            var needsMedicalCertificate = isSick && getRequestedDays() >= 3;
            
            if (isVacation) {
                $('#substitute-row').show();
                $('#substitute_employee_id').prop('required', true);
            } else {
                $('#substitute-row').hide();
                $('#substitute_employee_id').prop('required', false).val('');
            }

            if (needsMedicalCertificate) {
                $('#medical-certificate-row').show();
                $('#medical_certificate').prop('required', true);
            } else {
                $('#medical-certificate-row').hide();
                $('#medical_certificate').prop('required', false).val('');
                $('#medical-error-msg').text('');
            }

            var requiresFamily = String(selectedOption.data('requires-family') || '0') === '1'
                || /bereavement/.test(titleLower);
            if (requiresFamily) {
                $('#family-relation-row').show();
                $('#family_relation').prop('required', true);
            } else {
                $('#family-relation-row').hide();
                $('#family_relation').prop('required', false).val('');
            }
        }

        function renderSelectedLeaveBalance() {
            var selected = $('#leave_type_id').find('option:selected');
            var leaveId = selected.val();

            if (!leaveId) {
                $('#leave-balance-row').hide();
                return;
            }

            var mode = selected.data('credit-mode') === 'monthly' ? '{{ __('Monthly') }}' : '{{ __('Lump Sum') }}';
            var total = parseFloat(selected.data('annual') || 0);
            var used = parseFloat(selected.data('used') || 0);
            var pending = parseFloat(selected.data('pending') || 0);
            var monthly = parseFloat(selected.data('monthly') || 0);
            var available = parseFloat(selected.data('available') || 0);
            var carryForward = parseFloat(selected.data('carry-forward') || 0);
            var encashable = parseFloat(selected.data('encashable') || 0);
            var leaveTitle = ((selected.data('title') || selected.text() || '') + '').toLowerCase();
            var isVacationLeave = isVacationTitle(leaveTitle);

            $('#lb-mode').text(mode);
            $('#lb-total').text(total);
            $('#lb-used').text(used);
            $('#lb-pending').text(pending);
            $('#lb-monthly').text(monthly);
            $('#lb-carry').text(carryForward);
            $('#lb-available').text(available);
            $('#lb-encashable').text(encashable);

            if (isVacationLeave) {
                $('#vacation-only-balance').show();
                $('#encashable-inline').show();
            } else {
                $('#vacation-only-balance').hide();
                $('#encashable-inline').hide();
            }

            $('#leave-balance-row').show();
        }

        function refreshLeaveTypeOptions(employeeId) {
            if (!employeeId) {
                $('#leave_type_id').empty().append('<option value="">{{ __('Select Leave Type') }}</option>');
                $('#leave-balance-row').hide();
                return;
            }

            var previous = $('#leave_type_id').val();
            $.ajax({
                url: '{{ route('leave.jsoncount') }}',
                type: 'POST',
                data: {
                    employee_id: employeeId,
                    _token: "{{ csrf_token() }}",
                },
                success: function(data) {
                    $('#leave_type_id').empty().append('<option value="">{{ __('Select Leave Type') }}</option>');

                    $.each(data, function(_, value) {
                        var used = parseFloat(value.total_leave || 0);
                        var pending = parseFloat(value.pending_leave || 0);
                        var available = parseFloat(value.available_leave || 0);
                        var annual = parseFloat(value.annual_leave || value.days || 0);
                        var monthly = parseFloat(value.monthly_accrual || 0);
                        var carryForward = parseFloat(value.carry_forward || 0);
                        var encashable = parseFloat(value.encashable_leave || 0);
                        var mode = value.credit_mode === 'monthly' ? '{{ __('Monthly') }}' : '{{ __('Lump Sum') }}';
                        var availableLabel = value.credit_mode === 'lump_sum'
                            ? '{{ __('Available (Total - Pending)') }}'
                            : '{{ __('Available') }}';
                        var titleLower = (value.title || '').toLowerCase();
                        var isVacationLeave = isVacationTitle(titleLower);

                        var optionText = value.title + ' (' +
                            '{{ __('Mode') }}: ' + mode + ', ' +
                            '{{ __('Total') }}: ' + annual + ', ' +
                            '{{ __('Used') }}: ' + used + ', ' +
                            '{{ __('Pending') }}: ' + pending + ', ' +
                            availableLabel + ': ' + available + ', ' +
                            '{{ __('Monthly') }}: ' + monthly;

                        if (isVacationLeave) {
                            optionText += ', ' +
                                '{{ __('Carry Forward') }}: ' + carryForward + ', ' +
                                '{{ __('Encashable') }}: ' + encashable;
                        }

                        optionText += ')';

                        var option = $('<option></option>')
                            .val(value.id)
                            .text(optionText)
                            .attr('data-title', value.title)
                            .attr('data-approval', value.approval_requirement || 'na')
                            .attr('data-used', used)
                            .attr('data-pending', pending)
                            .attr('data-available', available)
                            .attr('data-annual', annual)
                            .attr('data-monthly', monthly)
                            .attr('data-credit-mode', value.credit_mode || 'lump_sum')
                            .attr('data-carry-forward', carryForward)
                            .attr('data-encashable', encashable);

                        if (available <= 0) {
                            option.prop('disabled', true);
                        }

                        $('#leave_type_id').append(option);
                    });

                    if (previous && $('#leave_type_id option[value="' + previous + '"]:not(:disabled)').length) {
                        $('#leave_type_id').val(previous);
                    }

                    togglePolicyFields();
                    renderSelectedLeaveBalance();
                }
            });
        }

        window.refreshLeaveTypeOptions = refreshLeaveTypeOptions;

        // Validate medical certificate file
        $('#medical_certificate').on('change', function() {
            var file = this.files[0];
            if (file) {
                var validTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                var maxSize = 5 * 1024 * 1024; // 5MB

                if (!validTypes.includes(file.type)) {
                    $('#medical-error-msg').text('{{ __('Only PDF, JPG, and PNG files are allowed.') }}');
                    $(this).val('');
                    return;
                }

                if (file.size > maxSize) {
                    $('#medical-error-msg').text('{{ __('File size must not exceed 5MB.') }}');
                    $(this).val('');
                    return;
                }

                $('#medical-error-msg').text('');
            }
        });

        // Bind policy toggles
        $('#leave_type_id').on('change', function() {
            togglePolicyFields();
            renderSelectedLeaveBalance();
        });
        $('#start_date, #end_date').on('change keyup input blur changeDate dp.change', togglePolicyFields);
        $(document).on('change keyup input blur changeDate dp.change', 'input[name="start_date"], input[name="end_date"]', togglePolicyFields);
        $(document).on('change', '.day-type-radio, .half-day-option', togglePolicyFields);
        
        // Initial check
        setTimeout(function() {
            togglePolicyFields();
            renderSelectedLeaveBalance();

            var employeeId = $('#employee_id').val();
            if (employeeId) {
                refreshLeaveTypeOptions(employeeId);
            }
        }, 100);
    });
</script>

<script>
    $(document).ready(function() {
        setTimeout(() => {
            var employee_id = $('#employee_id').val();
            if (employee_id) {
                $('#employee_id').trigger('change');
            }
        }, 100);
    });
</script>

<script>
    $(document).on('change', '#employee_id', function() {
        var employee_id = $(this).val();
        if (typeof refreshLeaveTypeOptions === 'function') {
            refreshLeaveTypeOptions(employee_id);
        }

        if (!employee_id) {
            $('#substitute_employee_id').empty().append(
                '<option value="">{{ __('Select Substitute') }}</option>'
            );
            return;
        }

        $.ajax({
            url: '{{ route('leave.substitutes') }}',
            type: 'POST',
            data: {
                employee_id: employee_id,
                _token: "{{ csrf_token() }}",
            },
            success: function(data) {
                $('#substitute_employee_id').empty().append(
                    '<option value="">{{ __('Select Substitute') }}</option>'
                );
                $.each(data, function(_, item) {
                    $('#substitute_employee_id').append(
                        '<option value="' + item.id + '">' + item.name + '</option>'
                    );
                });
            }
        });
    });
</script>
