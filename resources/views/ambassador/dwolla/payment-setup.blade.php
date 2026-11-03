@extends('layout.app', ['page_name' => 'Payment Account Setup'])
@section('content')
    <div class="container p-3">
        <div class="h4">Complete Payment Account Setup</div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <p class="mb-3">
                To receive payments through Dwolla, we need a few identity and address details.
                You can continue using the app while you complete this step.
            </p>

            @if (!empty($retrySetupOnly))
                <div class="alert alert-info mb-3">
                    Your profile already includes the required payment information. Select
                    <strong>Retry Payment Setup</strong> to finish creating your Dwolla account.
                </div>
            @elseif (!empty($missingFields))
                <div class="alert alert-info">
                    <strong>Still needed:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($missingFields as $label)
                            <li>{{ $label }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning">{{ session('warning') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('ambassador.dwolla.payment-setup.complete') }}" method="POST">
                @csrf
                <div class="row">
                    @if (isset($missingFields['first_name']))
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label text-uppercase">First Name</label>
                                <input type="text" name="first_name" id="first_name"
                                    class="form-control shadow-none @error('first_name') is-invalid @enderror"
                                    value="{{ old('first_name', $user->first_name) }}" required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endif

                    @if (isset($missingFields['last_name']))
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label text-uppercase">Last Name</label>
                                <input type="text" name="last_name" id="last_name"
                                    class="form-control shadow-none @error('last_name') is-invalid @enderror"
                                    value="{{ old('last_name', $user->last_name) }}" required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endif

                    @if (isset($missingFields['dob']))
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="dob" class="form-label text-uppercase">Date of Birth</label>
                                <input type="date" name="dob" id="dob"
                                    class="form-control shadow-none @error('dob') is-invalid @enderror"
                                    value="{{ old('dob', $user->dob) }}"
                                    max="{{ now()->subYears(\App\Services\Dwolla\CustomerService::MINIMUM_CUSTOMER_AGE)->format('Y-m-d') }}"
                                    required>
                                @error('dob')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endif

                    @if (isset($missingFields['legal_address']))
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="legal_address" class="form-label text-uppercase">Legal Street Address</label>
                                <input type="text" name="legal_address" id="legal_address"
                                    class="form-control shadow-none @error('legal_address') is-invalid @enderror"
                                    value="{{ old('legal_address', $user->legal_address ?? $user->address) }}" required>
                                @error('legal_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endif

                    @if (isset($missingFields['legal_city']))
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="legal_city" class="form-label text-uppercase">Legal City</label>
                                <input type="text" name="legal_city" id="legal_city"
                                    class="form-control shadow-none @error('legal_city') is-invalid @enderror"
                                    value="{{ old('legal_city', $user->legal_city ?? $user->city) }}" required>
                                @error('legal_city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endif

                    @if (isset($missingFields['legal_state']))
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="legal_state" class="form-label text-uppercase">Legal State</label>
                                <select name="legal_state" id="legal_state"
                                    class="form-control shadow-none @error('legal_state') is-invalid @enderror" required>
                                    <option value="">Select State</option>
                                    @foreach (getState() as $state)
                                        <option value="{{ $state->short_name }}"
                                            @selected(old('legal_state', $user->legal_state) == $state->short_name)>
                                            {{ $state->short_name }} — {{ $state->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('legal_state')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endif

                    @if (isset($missingFields['legal_zip_code']))
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="legal_zip_code" class="form-label text-uppercase">Legal ZIP Code</label>
                                <input type="text" name="legal_zip_code" id="legal_zip_code"
                                    class="form-control shadow-none @error('legal_zip_code') is-invalid @enderror"
                                    value="{{ old('legal_zip_code', $user->legal_zip_code ?? $user->zip_code) }}"
                                    maxlength="10" required>
                                @error('legal_zip_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endif
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="submit" class="btn btn-dark">
                        {{ !empty($retrySetupOnly) ? 'Retry Payment Setup' : 'Save and Complete Setup' }}
                    </button>
                    <a href="{{ route('ambassador.calendar') }}" class="btn btn-outline-secondary">Continue Later</a>
                </div>
            </form>
        </div>
    </div>
@endsection
