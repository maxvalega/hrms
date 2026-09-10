{{ Form::open(['route' => ['account-assets.assign', $asset->id], 'method' => 'post']) }}
<div class="modal-body">
    <p class="text-muted small">
        {{ $wasAssignedBefore
            ? __('This asset is back in inventory and can be reassigned.')
            : __('Give this inventory asset to an employee.') }}
    </p>
    <div class="mb-2">
        <strong>{{ $asset->asset_code }}</strong> · {{ $asset->name }}
        @if($asset->serial_number)<span class="text-muted"> · {{ $asset->serial_number }}</span>@endif
    </div>
    <div class="form-group">
        {{ Form::label('employee_id', __('Employee'), ['class' => 'form-label']) }}<x-required></x-required>
        {{ Form::select('employee_id', ['' => __('Select employee')] + $employee->toArray(), null, ['class' => 'form-control', 'required' => 'required']) }}
    </div>
    <div class="form-group">
        {{ Form::label('notes', __('Notes'), ['class' => 'form-label']) }}
        {{ Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 2, 'placeholder' => __('Optional handover notes')]) }}
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ $wasAssignedBefore ? __('Reassign') : __('Assign') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
