{{ Form::open(['route' => ['account-assets.restore', $asset->id], 'method' => 'post']) }}
<div class="modal-body">
    <p class="text-muted small">{{ __('Mark this asset as repaired and put it back in inventory so it can be reassigned.') }}</p>
    <div class="mb-3">
        <strong>{{ $asset->asset_code }}</strong> · {{ $asset->name }}
        @if($asset->serial_number)<span class="text-muted"> · {{ $asset->serial_number }}</span>@endif
    </div>
    <div class="form-group">
        {{ Form::label('condition', __('Condition after repair'), ['class' => 'form-label']) }}
        {{ Form::select('condition', \App\Models\Asset::CONDITIONS, 'good', ['class' => 'form-control']) }}
    </div>
    <div class="form-group">
        {{ Form::label('notes', __('Repair notes'), ['class' => 'form-label']) }}
        {{ Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 2, 'placeholder' => __('e.g. Battery replaced, tested OK')]) }}
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Restore to inventory') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
