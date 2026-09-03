@php
    $plan = App\Models\Utility::getChatGPTSettings();
@endphp

{{ Form::model($leave, ['route' => ['leave.update', $leave->id], 'method' => 'PUT', 'class' => 'needs-validation', 'novalidate', 'files' => true]) }}
<div class="modal-body">

    @if (!empty($plan) && ($plan->enable_chatgpt ?? '') == 'on')
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
                    {{ Form::select('employee_id', $employees, null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('Select Employee')]) }}
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
                    @foreach ($leavetypes as $leaveType)
                        <option value="{{ $leaveType->id }}" data-title="{{ $leaveType->title }}" data-approval="{{ $leaveType->approval_requirement ?? 'na' }}"
                            @if ($leaveType->id == $leave->leave_type_id) selected @endif>
                            {{ $leaveType->title }} (<p class="float-right pr-5">
                                {{ $leaveType->days }}</p>)</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('start_date', __('Start Date'), ['class' => 'col-form-label']) }}<x-required></x-required>
                {{ Form::text('start_date', null, ['class' => 'form-control d_week', 'required' => 'required', 'autocomplete' => 'off']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('end_date', __('End Date'), ['class' => 'col-form-label']) }}<x-required></x-required>
                {{ Form::text('end_date', null, ['class' => 'form-control d_week', 'required' => 'required', 'autocomplete' => 'off']) }}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('day_type', __('Day Type'), ['class' => 'col-form-label']) }}<x-required></x-required>
                <select name="day_type" id="day_type" class="form-control select" required>
                    <option value="full_day" @if ($leave->day_type === 'full_day') selected @endif>{{ __('Full Day') }}</option>
                    <option value="first_half" @if ($leave->day_type === 'first_half') selected @endif>{{ __('First Half') }}</option>
                    <option value="second_half" @if ($leave->day_type === 'second_half') selected @endif>{{ __('Second Half') }}</option>
                </select>
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

    <div class="row" id="medical-certificate-row" style="display: none;">
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('medical_certificate', __('Medical Certificate'), ['class' => 'col-form-label']) }}
                <small class="text-muted d-block mb-2">{{ __('Upload or replace medical certificate (PDF, JPG, PNG). Max size: 5MB') }}</small>
                
                @if(!empty($leave->medical_certificate))
                    <div class="mb-2 p-2 bg-light rounded">
                        <a href="{{ asset('storage/' . $leave->medical_certificate) }}" target="_blank" class="btn btn-sm btn-info">
                            <i class="ti ti-download"></i> {{ __('View Current Certificate') }}
                        </a>
                        @if($leave->certificate_verified)
                            <span class="badge badge-sm bg-success ms-2">{{ __('Verified') }}</span>
                        @else
                            <span class="badge badge-sm bg-warning ms-2">{{ __('Pending Verification') }}</span>
                        @endif
                    </div>
                @endif
                
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
                {{ Form::select('substitute_employee_id', $substitutes, $leave->substitute_employee_id, ['class' => 'form-control select', 'id' => 'substitute_employee_id', 'placeholder' => __('Select Substitute')]) }}
                <small class="text-muted" id="substitute-note">{{ __('Required for Vacation leave.') }}</small>
            </div>
        </div>
    </div>
    @role('Company')
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    {{ Form::label('status', __('Status'), ['class' => 'col-form-label']) }}
                    <select name="status" id="" class="form-control select2">
                        <option value="">{{ __('Select Status') }}</option>
                        <option value="Pending" @if ($leave->status == 'Pending') selected="" @endif>{{ __('Pending') }}
                        </option>
                        <option value="Approved" @if ($leave->status == 'Approved') selected="" @endif>{{ __('Approved') }}
                        </option>
                        <option value="Reject" @if ($leave->status == 'Reject') selected="" @endif>{{ __('Reject') }}
                        </option>
                    </select>
                </div>
            </div>
        </div>
    @endrole
</div>
<div class="modal-footer">
    <button type="button" class="btn  btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">

</div>
{{ Form::close() }}

<script>
    $(document).ready(function() {
        setTimeout(() => {
            var employee_id = $('#employee_id').val();
            if (employee_id) {
                $('#employee_id').trigger('change');
            }
        }, 100);

        function parseDate(dateText) {
            if (!dateText) return null;
            var parts = dateText.trim().split('-');
            if (parts.length !== 3) return null;
            return new Date(parts[0], parts[1] - 1, parts[2]);
        }

        function getRequestedDays() {
            var start = parseDate($('#start_date').val());
            var end = parseDate($('#end_date').val());

            if (!start || !end || end < start) {
                return 0;
            }

            var dayType = $('#day_type').val();
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
            var isVacation = titleLower.includes('vacation') || titleLower.includes('vaction');
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
                $('#medical_certificate').prop('required', false);
                $('#medical_certificate').val('');
                $('#medical-error-msg').text('');
            }
        }

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
        $('#leave_type_id, #day_type').on('change', togglePolicyFields);
        $('#start_date, #end_date').on('change keyup input blur changeDate dp.change', togglePolicyFields);
        $(document).on('change keyup input blur changeDate dp.change', 'input[name="start_date"], input[name="end_date"]', togglePolicyFields);
        
        // Initial check
        togglePolicyFields();
    });
</script>

<script>
    $(document).on('change', '#employee_id', function() {
        var employee_id = $(this).val();
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
                var current = '{{ $leave->substitute_employee_id }}';
                $('#substitute_employee_id').empty().append(
                    '<option value="">{{ __('Select Substitute') }}</option>'
                );
                $.each(data, function(_, item) {
                    var selected = current == item.id ? ' selected' : '';
                    $('#substitute_employee_id').append(
                        '<option value="' + item.id + '"' + selected + '>' + item.name + '</option>'
                    );
                });
            }
        });
    });
</script>
