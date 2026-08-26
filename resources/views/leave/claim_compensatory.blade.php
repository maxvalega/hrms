{{ Form::open(['route' => 'leave.claim.compensatory.store', 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    @if(!empty($isSpectal))
        <div class="alert alert-info mb-3">
            {{ __('Select the Sunday/holiday you worked, then the date you want to take off. Earn and take within the same calendar quarter (:label: :start – :end).', [
                'label' => $quarter['label'] ?? '',
                'start' => $quarter['start'] ?? '',
                'end' => $quarter['end'] ?? '',
            ]) }}
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    {{ Form::label('worked_date', __('Date worked (Sunday / Holiday)'), ['class' => 'col-form-label']) }}<x-required></x-required>
                    <select name="worked_date" id="worked_date" class="form-control" required>
                        <option value="">{{ __('Select date') }}</option>
                        @forelse(($workedDateOptions ?? []) as $opt)
                            <option value="{{ $opt['date'] }}">{{ $opt['label'] }}</option>
                        @empty
                            <option value="" disabled>{{ __('No Sundays or holidays available in the current quarter yet.') }}</option>
                        @endforelse
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('worked_duration', __('Hours worked'), ['class' => 'col-form-label']) }}<x-required></x-required>
                    <select name="worked_duration" class="form-control" required>
                        <option value="full_day">{{ __('Full day (8 hrs)') }}</option>
                        <option value="half_day">{{ __('Half day (4 hrs)') }}</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('take_off_date', __('Date to take off'), ['class' => 'col-form-label']) }}<x-required></x-required>
                    {{ Form::text('take_off_date', null, ['class' => 'form-control d_week', 'required' => 'required', 'autocomplete' => 'off', 'placeholder' => __('Select date')]) }}
                    <small class="text-muted">{{ __('Must be in the same quarter as the worked date.') }}</small>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    {{ Form::label('leave_reason', __('Remark (optional)'), ['class' => 'col-form-label']) }}
                    {{ Form::textarea('leave_reason', null, ['class' => 'form-control', 'rows' => 2, 'placeholder' => __('Optional note')]) }}
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    {{ Form::label('available_comp_leaves', __('Available Compensatory Leaves'), ['class' => 'col-form-label']) }}
                    @if($compensatoryLeaves->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>{{ __('Earned Date') }}</th>
                                        <th>{{ __('Days') }}</th>
                                        <th>{{ __('Expiry Date') }}</th>
                                        <th>{{ __('Select') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($compensatoryLeaves as $compLeave)
                                        <tr>
                                            <td>{{ \Auth::user()->dateFormat($compLeave->earned_date) }}</td>
                                            <td>{{ $compLeave->days }}</td>
                                            <td>
                                                @if($compLeave->expiry_date)
                                                    {{ \Auth::user()->dateFormat($compLeave->expiry_date) }}
                                                    @if(\Carbon\Carbon::parse($compLeave->expiry_date)->diffInDays(\Carbon\Carbon::now()) <= 7)
                                                        <span class="badge badge-sm bg-warning ms-2">{{ __('Expiring Soon') }}</span>
                                                    @endif
                                                @else
                                                    {{ __('No Expiry') }}
                                                @endif
                                            </td>
                                            <td>
                                                <input type="checkbox" name="compensatory_leave_ids[]" value="{{ $compLeave->id }}" class="form-check-input">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            {{ __('No compensatory leaves available to claim.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if($compensatoryLeaves->count() > 0)
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {{ Form::label('start_date', __('Claim Date'), ['class' => 'col-form-label']) }}<x-required></x-required>
                        {{ Form::text('start_date', null, ['class' => 'form-control d_week', 'required' => 'required', 'autocomplete' => 'off', 'placeholder' => __('Select date')]) }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {{ Form::label('claim_days', __('Days to Claim'), ['class' => 'col-form-label']) }}<x-required></x-required>
                        {{ Form::number('claim_days', null, ['class' => 'form-control', 'required' => 'required', 'min' => '0.5', 'step' => '0.5', 'placeholder' => __('Enter number of days')]) }}
                        <small class="text-muted">{{ __('Total available: ') . $compensatoryLeaves->sum('days') . __(' days') }}</small>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

@if(!empty($isSpectal) || (!empty($compensatoryLeaves) && $compensatoryLeaves->count() > 0))
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <input type="submit" value="{{ __('Submit Claim') }}" class="btn btn-primary"
            @if(!empty($isSpectal) && empty($workedDateOptions)) disabled @endif>
    </div>
@endif

{{ Form::close() }}

@if(empty($isSpectal))
<script>
    $(document).ready(function() {
        var now = new Date();
        var month = (now.getMonth() + 1);
        var day = now.getDate();
        if (month < 10) month = "0" + month;
        if (day < 10) day = "0" + day;
        var today = now.getFullYear() + '-' + month + '-' + day;
        $('input[name="start_date"]').val(today);
    });
</script>
@endif
