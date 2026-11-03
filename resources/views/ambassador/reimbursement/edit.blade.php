@extends('layout.app', ['page_name' => 'Edit Reimbursement'])

@section('content')
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Edit Reimbursement #{{ $reimbursement->id }}</h5>
        <a href="{{ route('ambassador.reimbursements.show', $reimbursement) }}" class="btn btn-outline-secondary">&larr; Back to details</a>
    </div>
    <div class="fixed-border"></div>

    @if (! $hasVerifiedBank)
        <div class="alert alert-warning mb-3">
            <h6 class="alert-heading"><i class="fa-solid fa-university me-2"></i>Bank account required before resubmitting</h6>
            <p class="mb-2">Reimbursements are paid to your verified bank account. Before you can resubmit this reimbursement, you must:</p>
            <ol class="mb-3 ps-3">
                <li>Add your bank account under <strong>My Account → Bank Account</strong>.</li>
                <li>Complete the verification step (micro-deposits or instant verification).</li>
                <li>Wait until your bank account is approved/verified.</li>
            </ol>
            <p class="mb-0">Once your bank is verified, you can return here to update and resubmit this reimbursement.</p>
            <hr>
            <a href="{{ route('ambassador.dwolla.bank-account') }}" class="btn btn-primary">Add & verify bank account</a>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($reimbursement->rejection_reason)
        <div class="alert alert-danger mb-3">
            <strong>Rejection reason from admin/manager:</strong><br>
            {{ $reimbursement->rejection_reason }}
        </div>
    @endif

    <div class="page-card-design">
        <form action="{{ route('ambassador.reimbursements.update', $reimbursement) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Tag an Event (Optional)</label>
                    <select name="event_id" class="form-control" id="event_select">
                        <option value="">-- No event / Submit without tagging --</option>
                        @foreach($assignableEvents as $ev)
                            <option value="{{ $ev->id }}" data-brand="{{ $ev->brand->brand_name ?? '' }}"
                                {{ (old('event_id', $reimbursement->event_id) == $ev->id) ? 'selected' : '' }}>
                                {{ $ev->booked_date ? \Carbon\Carbon::parse($ev->booked_date)->format('M d, Y') : '-' }} - {{ $ev->brand->brand_name ?? 'Event' }} #{{ $ev->id }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">If this reimbursement is for an event you worked, select it to help us verify.</small>
                </div>
            </div>

            <div class="row mb-3" id="brand_section">
                <div class="col-md-6">
                    <label class="form-label">Brand (if not from event)</label>
                    <select name="brand_id" class="form-control" id="brand_id">
                        <option value="">-- Select or type below --</option>
                        @foreach($brands as $b)
                            <option value="{{ $b->id }}" {{ (old('brand_id', $reimbursement->brand_id) == $b->id) ? 'selected' : '' }}>
                                {{ $b->brand_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Or enter brand name</label>
                    <input type="text" name="brand_name" class="form-control" placeholder="e.g. Tito's Vodka"
                        value="{{ old('brand_name', $reimbursement->brand_name) }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Expense Date <span class="text-danger">*</span></label>
                    <input type="date" name="expense_date"
                        class="form-control @error('expense_date') is-invalid @enderror"
                        value="{{ old('expense_date', optional($reimbursement->expense_date)->format('Y-m-d')) }}" required>
                    @error('expense_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                    <input type="number" name="amount" step="0.01" min="0.01"
                        class="form-control @error('amount') is-invalid @enderror"
                        value="{{ old('amount', $reimbursement->amount) }}" required>
                    @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            @include('common.partials.reimbursement-receipt-upload', ['existingReceiptPath' => $reimbursement->receipt_path])

            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Reason for Reimbursement <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3" required>{{ old('reason', $reimbursement->reason) }}</textarea>
                    @error('reason')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <button type="submit" class="btn btn-primary" {{ $hasVerifiedBank ? '' : 'disabled' }}>Update & Resubmit</button>
                <a href="{{ route('ambassador.reimbursements.show', $reimbursement) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const eventSelect = document.getElementById('event_select');
    const brandSection = document.getElementById('brand_section');
    const brandId = document.getElementById('brand_id');

    function updateBrandSection() {
        if (!eventSelect) return;
        if (eventSelect.value) {
            brandSection.style.opacity = '0.6';
            brandId.disabled = true;
            brandId.value = '';
        } else {
            brandSection.style.opacity = '1';
            brandId.disabled = false;
        }
    }

    if (eventSelect) {
        eventSelect.addEventListener('change', updateBrandSection);
        updateBrandSection();
    }
});
</script>
@endsection

