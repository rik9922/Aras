@extends('layout.app', ['page_name' => 'Bank Account'])

@section('content')
    <div class="container py-3">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="h4 mb-0">Bank Accounts</div>
                <div class="fixed-border mt-1"></div>
            </div>
            <a href="{{ url()->previous() }}" class="btn-ds btn-secondary-ds">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div class="alert alert-success mt-3">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger mt-3">{{ session('error') }}</div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning mt-3">{{ session('warning') }}</div>
        @endif
        @if ($errors->has('bank_error'))
            <div class="alert alert-danger mt-3">{{ $errors->first('bank_error') }}</div>
        @endif

        {{-- Existing Bank Accounts --}}
        @if ($sources->count())
            <div class="page-card-design mt-3">
                <div class="h5 mb-1">My Bank Accounts</div>
                <div class="fixed-border mb-3"></div>

                <div class="row g-3">
                    @foreach ($sources as $source)
                        @php
                            $isVerifiedDefault = $source->is_default && $source->status === 'verified';
                        @endphp
                        <div class="col-12">
                            <div class="card {{ $isVerifiedDefault ? 'border-success' : 'border' }} shadow-sm rounded-3">
                                <div class="card-body p-3">

                                    {{-- Top row: bank name + default badge + status --}}
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-building-columns text-secondary fs-5"></i>
                                            <span class="fw-semibold fs-6">{{ $source->bank_name ?? 'Bank Account' }}</span>
                                            @if ($isVerifiedDefault)
                                                <span class="badge bg-success">Default</span>
                                            @endif
                                        </div>
                                        <div>
                                            @php
                                                $statusClass = match ($source->status) {
                                                    'verified' => 'bg-success',
                                                    'unverified', 'added' => 'bg-primary',
                                                    'awaiting_verification' => 'bg-info text-dark',
                                                    'removed' => 'bg-secondary',
                                                    default => 'bg-secondary',
                                                };
                                            @endphp
                                            <span
                                                class="badge {{ $statusClass }}">{{ formatStatus($source->status) }}</span>
                                        </div>
                                    </div>

                                    {{-- Detail row --}}
                                    <div class="row g-2 text-sm mb-3">
                                        <div class="col-6 col-md-3">
                                            <div class="text-muted small text-uppercase fw-bold">Account Number</div>
                                            <div class="fw-medium">{{ $source->account_number ?? '—' }}</div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="text-muted small text-uppercase fw-bold">Account Type</div>
                                            <div class="fw-medium">{{ ucfirst($source->account_type ?? '—') }}</div>
                                        </div>
                                    </div>

                                    {{-- Action buttons --}}
                                    <div class="d-flex flex-wrap gap-2">
                                        @if (in_array($source->status, ['unverified', 'awaiting_verification', 'added']))
                                            <a href="{{ route('ambassador.dwolla.bank.verify.form', $source->id) }}"
                                                class="btn-ds btn-warning-ds btn-sm">
                                                <i class="fa-solid fa-circle-check"></i> Verify Account
                                            </a>
                                        @endif

                                        @if (!$source->is_default && $source->status === 'verified')
                                            <form action="{{ route('ambassador.dwolla.bank.setDefault', $source->id) }}"
                                                method="POST" class="mb-0">
                                                @csrf
                                                <button type="submit" class="btn-ds btn-success-ds btn-sm">
                                                    <i class="fa-solid fa-star"></i> Set as Default
                                                </button>
                                            </form>
                                        @endif

                                        <form action="{{ route('ambassador.dwolla.bank.delete', $source->id) }}"
                                            method="POST" class="mb-0"
                                            onsubmit="return confirm('Are you sure you want to remove this bank account?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-ds btn-danger-ds btn-sm">
                                                <i class="fa-solid fa-trash"></i> Remove
                                            </button>
                                        </form>
                                    </div>

                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            {{-- Empty state --}}
            <div class="page-card-design mt-3 text-center py-5">
                <i class="fa-solid fa-building-columns fa-3x text-muted mb-3"></i>
                <div class="h6 text-muted">No bank accounts added yet.</div>
                <p class="text-muted small">Add a bank account below to receive payments.</p>
            </div>
        @endif

        {{-- Add Bank Account Form --}}
        <div class="page-card-design mt-3">
            <div class="h5 mb-1">Add Bank Account</div>
            <div class="fixed-border mb-3"></div>

            <form action="{{ route('ambassador.dwolla.bank') }}" method="POST">
                @csrf
                <div class="row g-3">

                    <div class="col-xl-6">
                        <label for="bank_name" class="form-label text-uppercase">Bank Name</label>
                        <input type="text" class="form-control shadow-none @error('bank_name') is-invalid @enderror"
                            name="bank_name" id="bank_name" value="{{ old('bank_name') }}" placeholder="e.g. Chase Bank"
                            minlength="2" maxlength="80" pattern="[A-Za-z]+( [A-Za-z]+)*"
                            title="Bank Name may contain letters and single spaces only.">
                        @error('bank_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-xl-6">
                        <label class="form-label text-uppercase">Account Holder Name</label>
                        <input type="text"
                            class="form-control shadow-none @error('account_holder_name') is-invalid @enderror"
                            name="account_holder_name" value="{{ old('account_holder_name') }}"
                            placeholder="Full name on account">
                        @error('account_holder_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-xl-6">
                        <label class="form-label text-uppercase">Routing Number</label>
                        <input type="text" class="form-control shadow-none @error('routing_number') is-invalid @enderror"
                            name="routing_number" maxlength="9" value="{{ old('routing_number') }}"
                            placeholder="9-digit routing number">
                        @error('routing_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-xl-6">
                        <label class="form-label text-uppercase">Account Number</label>
                        <input type="text" class="form-control shadow-none @error('account_number') is-invalid @enderror"
                            name="account_number" value="{{ old('account_number') }}" placeholder="Account number">
                        @error('account_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-xl-6">
                        <label class="form-label text-uppercase">Account Type</label>
                        <select name="account_type"
                            class="form-control shadow-none @error('account_type') is-invalid @enderror">
                            <option value="">Select type</option>
                            <option value="checking" {{ old('account_type') === 'checking' ? 'selected' : '' }}>Checking
                            </option>
                            <option value="savings" {{ old('account_type') === 'savings' ? 'selected' : '' }}>Savings
                            </option>
                        </select>
                        @error('account_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input type="hidden" name="set_as_default" value="0">
                            <input type="checkbox" class="form-check-input" id="setAsDefault" name="set_as_default"
                                value="1" {{ old('set_as_default') ? 'checked' : '' }}>
                            <label class="form-check-label" for="setAsDefault">Set as default account</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn-ds btn-primary-ds">
                                <i class="fa-solid fa-plus"></i> Add Bank Account
                            </button>
                            <button type="button" class="btn-ds btn-secondary-ds" onclick="window.history.back()">
                                Cancel
                            </button>
                        </div>
                    </div>

                </div>
            </form>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bankNameInput = document.getElementById('bank_name');

            if (!bankNameInput) {
                return;
            }

            const cleanBankName = value => value
                .replace(/[^A-Za-z ]+/g, '')
                .replace(/\s+/g, ' ')
                .replace(/^\s+/, '');
            const bankNamePattern = /^[A-Za-z]+(?: [A-Za-z]+)*$/;
            const bankNameMessage = 'Bank Name may contain letters and single spaces only.';

            const updateValidity = () => {
                const value = bankNameInput.value;

                bankNameInput.setCustomValidity(
                    value === '' || bankNamePattern.test(value) ? '' : bankNameMessage
                );
            };

            bankNameInput.addEventListener('beforeinput', function(event) {
                if (!event.data) {
                    return;
                }

                const start = bankNameInput.selectionStart ?? bankNameInput.value.length;
                const end = bankNameInput.selectionEnd ?? start;
                const nextValue = bankNameInput.value.slice(0, start) + event.data + bankNameInput.value
                    .slice(end);

                if (!/^[A-Za-z ]*$/.test(event.data) || nextValue.startsWith(' ') || nextValue.includes(
                        '  ')) {
                    event.preventDefault();
                }
            });

            bankNameInput.addEventListener('paste', function(event) {
                event.preventDefault();

                const pasted = (event.clipboardData || window.clipboardData).getData('text');
                const start = bankNameInput.selectionStart ?? bankNameInput.value.length;
                const end = bankNameInput.selectionEnd ?? start;
                const nextValue = bankNameInput.value.slice(0, start) + pasted + bankNameInput.value.slice(
                    end);

                bankNameInput.value = cleanBankName(nextValue).slice(0, 80);
                updateValidity();
                bankNameInput.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
            });

            bankNameInput.addEventListener('input', function() {
                const cleaned = cleanBankName(bankNameInput.value).slice(0, 80);

                if (bankNameInput.value !== cleaned) {
                    bankNameInput.value = cleaned;
                }

                updateValidity();
            });

            bankNameInput.addEventListener('blur', function() {
                bankNameInput.value = bankNameInput.value.replace(/\s+$/, '');
                updateValidity();
            });
        });
    </script>
@endpush
