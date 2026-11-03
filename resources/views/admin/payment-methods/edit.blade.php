@extends('layout.app', ['page_name' => 'Edit Payment Method'])

@section('content')
    @php
        $user = auth()->user();
        $prefix = $user && $user->role_id == 6 ? 'manager' : 'admin';
    @endphp
    <div class="container py-3">
        <h5>Edit Payment Method</h5>
        <div class="fixed-border"></div>

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="page-card-design">
            <form method="POST" action="{{ route($prefix.'.payment-methods.update', $paymentMethod) }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Account Name <span class="text-muted">(like QuickBooks)</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $paymentMethod->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account Type</label>
                        <select name="account_type" class="form-select @error('account_type') is-invalid @enderror">
                            <option value="">—</option>
                            <option value="Bank" {{ old('account_type', $paymentMethod->account_type) === 'Bank' ? 'selected' : '' }}>Bank</option>
                            <option value="Credit Card" {{ old('account_type', $paymentMethod->account_type) === 'Credit Card' ? 'selected' : '' }}>Credit Card</option>
                        </select>
                        @error('account_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Detail Type <span class="text-muted">(e.g. Checking, Savings)</span></label>
                        <input type="text" name="detail_type" class="form-control @error('detail_type') is-invalid @enderror" value="{{ old('detail_type', $paymentMethod->detail_type) }}" placeholder="Checking, Savings, CreditCard">
                        @error('detail_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Opening Balance (from QuickBooks)</label>
                        <input type="text" class="form-control" value="{{ $paymentMethod->opening_balance !== null ? number_format($paymentMethod->opening_balance, 2) : '—' }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">As of (from QuickBooks)</label>
                        <input type="text" class="form-control" value="{{ $paymentMethod->as_of_date?->format('Y-m-d') ?? '—' }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Code</label>
                        <input type="text" class="form-control" value="{{ $paymentMethod->code }}" disabled>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="active" value="1" class="form-check-input" id="active" {{ old('active', $paymentMethod->active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">Active</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn-ds btn-primary-ds">Update</button>
                        <a href="{{ route($prefix.'.payment-methods.index') }}" class="btn-ds btn-secondary-outline">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
