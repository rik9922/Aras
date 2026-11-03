@php
    $value = fn (string $key) => ($mode ?? 'create') === 'edit'
        ? old($key, $recap->{$key} ?? '')
        : old($key);
    $selectedState = ($mode ?? 'create') === 'edit'
        ? old('state_id', $recap->state_id ?? '')
        : old('state_id');
@endphp
<div class="col-md-6">
    <div class="mb-3">
        <label for="receipt" class="form-label text-uppercase">
            Itemized Receipt
        </label>
        <input type="file" class="form-control shadow-none @error('receipt') is-invalid @enderror"
            name="receipt" id="receipt">
        @error('receipt')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="col-md-6">
    <div class="mb-3">
        <label for="stateId" class="form-label text-uppercase"> State </label>
        <select name="state_id" id="stateId"
            class="form-control @error('state_id') is-invalid @enderror">
            <option value="">Select State</option>
            @foreach (getState() as $state)
                <option value="{{ $state->id }}" {{ (string) $selectedState === (string) $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
            @endforeach
        </select>
        @error('state_id')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="col-6">
    <div class="mb-3">
        <label for="brand_count" class="form-label text-uppercase">How many people knew about the
            brand?</label>
        <input type="number"
            class="form-control shadow-none @error('brand_count') is-invalid @enderror"
            name="brand_count" id="brand_count" value="{{ $value('brand_count') }}">
        @error('brand_count')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
