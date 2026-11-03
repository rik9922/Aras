<div class="col-md-6">
    <div class="mb-3">
        <label for="buyback_amount" class="form-label text-uppercase">Buyback amount ($)</label>
        <input type="number" step="0.01" min="0" name="buyback_amount" id="buyback_amount"
            class="form-control shadow-none @error('buyback_amount') is-invalid @enderror"
            placeholder="0"
            value="{{ old('buyback_amount', $value ?? '') }}">
        @error('buyback_amount')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>
