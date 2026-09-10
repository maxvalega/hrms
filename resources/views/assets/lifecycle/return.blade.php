{{ Form::open(['route' => ['account-assets.return', $asset->id], 'method' => 'post']) }}
@if(request('resignation_id'))
    <input type="hidden" name="resignation_id" value="{{ request('resignation_id') }}">
@endif
<div class="modal-body">
    <div class="mb-3">
        <strong>{{ $asset->asset_code }}</strong> · {{ $asset->name }}
        <div class="small text-muted">{{ __('Currently with') }} {{ $asset->holderName() }}</div>
    </div>

    <div class="form-group">
        <label class="form-label">{{ __('Why is it coming back?') }}</label>
        <div class="form-check mb-1">
            <input class="form-check-input" type="radio" name="reason" id="reason_exit" value="exit" {{ $reason !== 'defective' ? 'checked' : '' }} onchange="amToggleReplacement()">
            <label class="form-check-label" for="reason_exit">
                {{ __('Employee exit — take back and restore to inventory') }}
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="reason" id="reason_defective" value="defective" {{ $reason === 'defective' ? 'checked' : '' }} onchange="amToggleReplacement()">
            <label class="form-check-label" for="reason_defective">
                {{ __('Not functioning well — send for repair and optionally issue a replacement') }}
            </label>
        </div>
    </div>

    <div class="form-group">
        {{ Form::label('condition', __('Condition on return'), ['class' => 'form-label']) }}
        {{ Form::select('condition', \App\Models\Asset::CONDITIONS, $reason === 'defective' ? 'damaged' : $asset->condition, ['class' => 'form-control', 'id' => 'return_condition']) }}
    </div>

    <div class="form-group" id="am-replacement" style="{{ $reason === 'defective' ? '' : 'display:none' }}">
        {{ Form::label('replacement_asset_id', __('Assign replacement from inventory'), ['class' => 'form-label']) }}
        @if($inventory->count())
            <select name="replacement_asset_id" class="form-control">
                <option value="">{{ __('No replacement now') }}</option>
                @foreach($inventory as $item)
                    <option value="{{ $item->id }}">{{ $item->asset_code }} — {{ $item->name }}{{ $item->serial_number ? ' (' . $item->serial_number . ')' : '' }}</option>
                @endforeach
            </select>
            <small class="text-muted">{{ __('The replacement is assigned to the same employee. After this asset is fixed it goes back to inventory and can be reassigned.') }}</small>
        @else
            <div class="alert alert-warning py-2 mb-0">{{ __('No spare assets in inventory. Record or restore one first, then issue a replacement.') }}</div>
        @endif
    </div>

    <div class="form-group">
        {{ Form::label('notes', __('Notes'), ['class' => 'form-label']) }}
        {{ Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 2, 'placeholder' => __('e.g. Screen flickering / last working day handover')]) }}
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Take back') }}" class="btn btn-primary">
</div>
{{ Form::close() }}

<script>
function amToggleReplacement() {
    var defective = document.getElementById('reason_defective').checked;
    document.getElementById('am-replacement').style.display = defective ? '' : 'none';
    if (defective) {
        document.getElementById('return_condition').value = 'damaged';
    }
}
</script>
