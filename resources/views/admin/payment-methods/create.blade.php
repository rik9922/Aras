@extends('layout.app', ['page_name' => 'Add Payment Method'])

@section('content')
    @php
        $user = auth()->user();
        $prefix = $user && $user->role_id == 6 ? 'manager' : 'admin';
    @endphp
    <div class="container py-3">
        <h5>Add Payment Method</h5>
        <div class="fixed-border"></div>

        <div class="content-box-grey d-flex align-items-center gap-2 p-2 mb-3">
            <i class="fa-solid fa-circle-info"></i>
            <div>
                To add a new payment account, first create the Bank / Credit Card account in <strong>QuickBooks</strong>,
                then click <strong>Sync from QuickBooks</strong> on the previous screen. You can create a label here,
                but balances always come from QuickBooks and are not editable.
            </div>
        </div>

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="page-card-design">
            <form method="POST" action="{{ route($prefix.'.payment-methods.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Account Name <span class="text-muted">(like QuickBooks)</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account Type</label>
                        <select name="account_type" class="form-select @error('account_type') is-invalid @enderror">
                            <option value="">—</option>
                            <option value="Bank" {{ old('account_type') === 'Bank' ? 'selected' : '' }}>Bank</option>
                            <option value="Credit Card" {{ old('account_type') === 'Credit Card' ? 'selected' : '' }}>Credit Card</option>
                        </select>
                        @error('account_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Type (code)</label>
                        <select name="code" class="form-select @error('code') is-invalid @enderror" required>
                            <option value="">Select</option>
                            @foreach ($codes as $code => $label)
                                <option value="{{ $code }}" {{ old('code') === $code ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Detail Type <span class="text-muted">(e.g. Checking, Savings)</span></label>
                        <input type="text" name="detail_type" class="form-control @error('detail_type') is-invalid @enderror" value="{{ old('detail_type') }}" placeholder="Checking, Savings, CreditCard">
                        @error('detail_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn-ds btn-primary-ds">Create Payment Method</button>
                        <a href="{{ route($prefix.'.payment-methods.index') }}" class="btn-ds btn-secondary-outline">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
