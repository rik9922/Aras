@php
    $existingReceiptPath = $existingReceiptPath ?? null;
@endphp

<div class="row mb-3">
    <div class="col-md-12">
        <label for="receipt" class="form-label">Itemized Receipt</label>
        <input type="file" name="receipt" id="receipt"
            class="form-control @error('receipt') is-invalid @enderror"
            accept=".jpg,.jpeg,.png,.pdf">
        <small class="text-muted d-block mt-1">Upload a photo or PDF of your receipt (JPG, PNG, or PDF, max 5MB).</small>
        @error('receipt')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        @if ($existingReceiptPath)
            <div class="mt-3">
                <p class="text-muted small mb-2">Current receipt:</p>
                @include('common.partials.reimbursement-receipt', [
                    'reimbursement' => (object) ['receipt_path' => $existingReceiptPath],
                    'showHeading' => false,
                ])
            </div>
        @endif
    </div>
</div>
