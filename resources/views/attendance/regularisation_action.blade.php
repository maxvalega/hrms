{{ Form::open(['route' => ['attendance.regularisation.change.action', $row->id], 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="mb-3">
        <strong>{{ __('Employee') }}:</strong> {{ $row->employee->name ?? '-' }}<br>
        <strong>{{ __('Date') }}:</strong> {{ \Auth::user()->dateFormat($row->date) }}<br>
        <strong>{{ __('Reason') }}:</strong> {{ $row->reason }}
    </div>
    <div class="form-group">
        {{ Form::label('status', __('Status'), ['class' => 'col-form-label']) }}<x-required></x-required>
        {{ Form::select('status', ['Approved' => __('Approve'), 'Reject' => __('Reject')], 'Approved', ['class' => 'form-control', 'required' => 'required']) }}
    </div>
    <div class="form-group">
        {{ Form::label('manager_comment', __('Comment'), ['class' => 'col-form-label']) }}
        {{ Form::textarea('manager_comment', null, ['class' => 'form-control', 'rows' => '2']) }}
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <input type="submit" value="{{ __('Submit') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
