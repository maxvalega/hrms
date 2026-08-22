{{ Form::open(['route' => ['attendance.regularisation.change.action', $row->id], 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="border rounded p-3 mb-3 bg-light">
        <div class="mb-1"><strong>{{ __('Employee') }}:</strong> {{ $row->employee->name ?? '-' }}</div>
        <div class="mb-1"><strong>{{ __('Date') }}:</strong> {{ \Auth::user()->dateFormat($row->date) }}</div>
        <div style="word-break:break-word;"><strong>{{ __('Reason') }}:</strong> {{ $row->reason }}</div>
    </div>
    <div class="form-group mb-3">
        {{ Form::label('status', __('Status'), ['class' => 'form-label fw-semibold']) }}<x-required></x-required>
        {{ Form::select('status', ['Approved' => __('Approve'), 'Reject' => __('Reject')], 'Approved', ['class' => 'form-control form-select', 'required' => 'required', 'style' => 'min-height:44px;font-size:16px;']) }}
    </div>
    <div class="form-group mb-0">
        {{ Form::label('manager_comment', __('Comment'), ['class' => 'form-label fw-semibold']) }}
        {{ Form::textarea('manager_comment', null, [
            'class' => 'form-control',
            'rows' => '3',
            'style' => 'min-height:90px;font-size:16px;color:#212529;background:#fff;',
        ]) }}
    </div>
</div>
<div class="modal-footer flex-column flex-sm-row gap-2 align-items-stretch">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <input type="submit" value="{{ __('Submit') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
