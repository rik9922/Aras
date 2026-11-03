<div class="col-md-6">
    <div class="mb-3">
        <label for="city" class="form-label text-uppercase">City</label>
        <input type="text"
            class="form-control shadow-none @error('city') is-invalid @enderror"
            name="city"
            value="{{ old('city', $event->city ?? '') }}"
            id="city"
            placeholder="Auto-filled from event address"
            readonly>
        @error('city')
            <div class="text-danger w-100">{{ $message }}</div>
        @enderror
    </div>
</div>
