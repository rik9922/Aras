@extends('layout.app', ['page_name' => 'Create Ambassador'])
@php $routePrefix = request()->routeIs('manager.*') ? 'manager' : 'admin'; @endphp
<link rel="stylesheet" href="{{ asset('assets/select2/select2.css') }}">
@section('content')
    <div class="container py-3">
        <div class="d-flex">
            <div class="h5">Create Ambassador</div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form action="{{ route($routePrefix.'.store.ambassador') }}" method="POST" id="ambassadorCreateForm">@csrf
                <div class="row p-3">
                    <div class="col-4">
                        <div class="mb-3">
                            <label for="firstName" class="form-label text-uppercase">First Name</label>
                            <input type="text" class="form-control shadow-none @error('first_name') is-invalid @enderror"
                                value="{{ old('first_name') }}" name="first_name" id="firstName" placeholder="Enter First Name">
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mb-3">
                            <label for="lastName" class="form-label text-uppercase">Last Name</label>
                            <input type="text" class="form-control shadow-none @error('last_name') is-invalid @enderror"
                                value="{{ old('last_name') }}" name="last_name" id="lastName" placeholder="Enter Last Name">
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mb-3">
                            <label for="email" class="form-label text-uppercase">Email</label>
                            <input type="email" class="form-control shadow-none @error('email') is-invalid @enderror"
                                value="{{ old('email') }}" name="email" id="email" placeholder="Enter Email Address">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-4">
                      <div class="mb-3 position-relative">
                        <label for="password" class="form-label text-uppercase">
                          Password (Leave blank to keep unchanged)
                        </label>
                    
                        <div class="input-group">
                          <input 
                            type="password" 
                            class="form-control shadow-none @error('password') is-invalid @enderror"
                            name="password" 
                            id="password" 
                            value="password"
                            placeholder="Enter Password"
                          >
                          <button 
                            type="button" 
                            class="btn btn-outline-secondary shadow-none" 
                            id="togglePassword"
                            tabindex="-1"
                          >
                            <i class="fa-solid fa-eye-slash" id="toggleIcon"></i>
                          </button>
                        </div>
                    
                        @error('password')
                          <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    
                        <small class="text-info">Default password is <strong>password</strong></small>
                      </div>
                    </div>

                    <div class="col-4">
                        <div class="mb-3">
                            <label for="nickname" class="form-label text-uppercase">Nickname</label>
                            <input type="text" class="form-control shadow-none @error('nickname') is-invalid @enderror"
                                value="{{ old('nickname') }}" name="nickname" id="nickname" placeholder="Enter Nickname">
                            @error('nickname')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mb-3">
                            <label for="dob" class="form-label text-uppercase">DOB</label>
                            <input 
                                type="date" 
                                class="form-control shadow-none @error('dob') is-invalid @enderror"
                                value="{{ old('dob') }}" 
                                name="dob" 
                                id="dob"
                                max="{{ now()->subDay()->toDateString() }}"
                                placeholder="Select Date of birth"
                            >
                            @error('dob')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mb-3">
                            <label for="venmo" class="form-label text-uppercase">Venmo</label>
                            <input type="text" class="form-control shadow-none @error('venmo') is-invalid @enderror"
                                value="{{ old('venmo') }}" name="venmo" id="venmo" placeholder="Enter Venmo Link">
                            @error('venmo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mb-3">
                            <label for="region" class="form-label text-uppercase">Region</label>
                            <div class="d-flex gap-4">
                                <select class="form-select shadow-none @error('region') is-invalid @enderror" id="region"
                                    name="region">
                                    <option value="" selected>Select Region</option>
                                    <option value="Southeast" @if (old('region') == 'Southeast') selected @endif>Southeast
                                    </option>
                                    <option value="Midwest" @if (old('region') == 'Midwest') selected @endif>Midwest
                                    </option>
                                </select>
                            </div>
                            @error('region')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mb-3">
                            <label for="phone" class="form-label text-uppercase">Phone</label>
                            <input type="text" class="form-control shadow-none @error('phone') is-invalid @enderror"
                                value="{{ old('phone') }}" name="phone" id="phone" maxlength="10"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="Enter Phone Number">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 mb-3">
                        <div class="h5 text-uppercase mt-4">Certifications and Preferences</div>
                        <div class="fixed-border mb-3"></div>
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3 form-check">
                                    <input type="hidden" name="titos_trained" value="0">
                                    <input type="checkbox" class="form-check-input" id="titosTrained"
                                        name="titos_trained" value="1"
                                        @if (old('titos_trained')) checked @endif>
                                    <label class="form-check-label" for="titosTrained">Tito's Trained</label>
                                    @error('titos_trained')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="hidden" name="thc_quiz_passed" value="0">
                                    <input type="checkbox" class="form-check-input" id="thcQuizPassed"
                                        name="thc_quiz_passed" value="1"
                                        @if (old('thc_quiz_passed')) checked @endif>
                                    <label class="form-check-label" for="thcQuizPassed">THC Quiz Passed</label>
                                    @error('thc_quiz_passed')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="hidden" name="male_ba_lgbtq_ok" value="0">
                                    <input type="checkbox" class="form-check-input" id="maleBaLgbtqOk"
                                        name="male_ba_lgbtq_ok" value="1"
                                        @if (old('male_ba_lgbtq_ok')) checked @endif>
                                    <label class="form-check-label" for="maleBaLgbtqOk">Male BA (Okay with
                                        LGBTQ+)</label>
                                    @error('male_ba_lgbtq_ok')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3 form-check">
                                    <input type="hidden" name="attended_trainings" value="0">
                                    <input type="checkbox" class="form-check-input" id="attendedTrainings"
                                        name="attended_trainings" value="1"
                                        @if (old('attended_trainings')) checked @endif>
                                    <label class="form-check-label" for="attendedTrainings">Attended
                                        Trainings</label>
                                    @error('attended_trainings')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="hidden" name="tips_certified" value="0">
                                    <input type="checkbox" class="form-check-input" id="tipsCertified"
                                        name="tips_certified" value="1"
                                        @if (old('tips_certified')) checked @endif>
                                    <label class="form-check-label" for="tipsCertified">TIPS Certified</label>
                                    @error('tips_certified')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="hidden" name="has_permit" value="0">
                                    <input type="checkbox" class="form-check-input" id="hasPermit" name="has_permit"
                                        value="1" @if (old('has_permit')) checked @endif>
                                    <label class="form-check-label" for="hasPermit">Has Permit</label>
                                    @error('has_permit')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="h5 text-uppercase mt-4">Liquor license</div>
                        <div class="fixed-border mb-3"></div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3 form-check">
                            <input type="hidden" name="has_liquor_license" value="0">
                            <input type="checkbox" class="form-check-input" id="hasLiquorLicense" name="has_liquor_license"
                                value="1" @if (old('has_liquor_license')) checked @endif>
                            <label class="form-check-label" for="hasLiquorLicense">Has liquor license</label>
                            @error('has_liquor_license')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mb-3">
                            <label for="license_number" class="form-label text-uppercase">License number</label>
                            <input type="text" class="form-control shadow-none @error('license_number') is-invalid @enderror"
                                name="license_number" id="license_number" value="{{ old('license_number') }}"
                                maxlength="50"
                                title="Use letters, numbers, spaces, hyphens, slashes, or dots. Include at least one letter or number."
                                placeholder="e.g. state license ID">
                            @error('license_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mb-3">
                            <label for="issuing_state" class="form-label text-uppercase">Issuing state</label>
                            <select class="form-select shadow-none @error('issuing_state') is-invalid @enderror"
                                name="issuing_state" id="issuing_state">
                                <option value="">Select state</option>
                                @foreach ($states as $state)
                                    <option value="{{ $state->short_name }}"
                                        @if (old('issuing_state') === $state->short_name || old('issuing_state') === $state->name) selected @endif>
                                        {{ $state->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('issuing_state')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mb-3 form-check pt-4">
                            <input type="hidden" name="document_on_file" value="0">
                            <input type="checkbox" class="form-check-input" id="liquorDocumentOnFile" name="document_on_file"
                                value="1" @if (old('document_on_file')) checked @endif>
                            <label class="form-check-label" for="liquorDocumentOnFile">Copy of license on file</label>
                            @error('document_on_file')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mb-3">
                            <label for="issued_at" class="form-label text-uppercase">Issued date</label>
                            <input type="date" class="form-control shadow-none @error('issued_at') is-invalid @enderror"
                                name="issued_at" id="issued_at" value="{{ old('issued_at') }}"
                                max="{{ now()->toDateString() }}">
                            @error('issued_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mb-3">
                            <label for="expires_at" class="form-label text-uppercase">Expiration date</label>
                            <input type="date" class="form-control shadow-none @error('expires_at') is-invalid @enderror"
                                name="expires_at" id="expires_at" value="{{ old('expires_at') }}"
                                min="{{ now()->toDateString() }}">
                            @error('expires_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="liquor_notes" class="form-label text-uppercase">Notes</label>
                            <textarea class="form-control shadow-none @error('notes') is-invalid @enderror" name="notes"
                                id="liquor_notes" rows="2" placeholder="Optional notes">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>


                    <div class="col-12">
                        <div class="h5 text-uppercase mt-4">Legal Address (for tax purposes)</div>
                        <div class="fixed-border mb-3"></div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="legal_address" class="form-label text-uppercase">Legal Address</label>
                            <input type="text"
                                class="form-control shadow-none @error('legal_address') is-invalid @enderror"
                                name="legal_address" id="legal_address" value="{{ old('legal_address') }}"
                                required
                                title="Use letters, numbers, spaces, and common address punctuation only. Do not use # or symbols.">
                            @error('legal_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="legal_city" class="form-label text-uppercase">City</label>
                            <input type="text"
                                class="form-control shadow-none @error('legal_city') is-invalid @enderror"
                                name="legal_city" id="legal_city" value="{{ old('legal_city') }}" placeholder="Enter City"
                                required
                                title="Use a valid city name with letters, numbers, spaces, and common punctuation.">
                            @error('legal_city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="legal_state" class="form-label text-uppercase">State</label>
                            <input type="text"
                                class="form-control shadow-none @error('legal_state') is-invalid @enderror"
                                name="legal_state" id="legal_state"
                                value="{{ old('legal_state') }}"
                                placeholder="Auto-filled from address (e.g. NY)"
                                required
                                readonly
                                style="background:#f8f9fa;">
                            <small class="text-muted">Populated automatically when you select an address above.</small>
                            @error('legal_state')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="legal_zip_code" class="form-label text-uppercase">Zip Code</label>
                            <input type="text"
                                class="form-control shadow-none @error('legal_zip_code') is-invalid @enderror"
                                value="{{ old('legal_zip_code') }}" name="legal_zip_code" id="legal_zip_code" maxlength="6"
                                oninput="this.value = this.value.replace(/[^0-9\-]/g, '')" placeholder="Enter ZipCode">
                            @error('legal_zip_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="h5 text-uppercase mt-4">Mailing Address</div>
                        <div class="fixed-border mb-3"></div>
                        <div class="mb-3 form-check">
                            <input type="hidden" name="same_as_legal" value="0">
                            <input type="checkbox" class="form-check-input" id="sameAsLegal" name="same_as_legal"
                                value="1" @if (old('same_as_legal')) checked @endif>
                            <label class="form-check-label" for="sameAsLegal">Same as Legal Address</label>
                            @error('same_as_legal')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="mailing_address" class="form-label text-uppercase">Mailing Address</label>
                            <input type="text"
                                class="form-control shadow-none @error('mailing_address') is-invalid @enderror"
                                name="mailing_address" id="mailing_address" value="{{ old('mailing_address') }}"
                                title="Use letters, numbers, spaces, and common address punctuation only. Do not use # or symbols.">
                            @error('mailing_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="mailing_city" class="form-label text-uppercase">City</label>
                            <input type="text"
                                class="form-control shadow-none @error('mailing_city') is-invalid @enderror"
                                name="mailing_city" id="mailing_city" value="{{ old('mailing_city') }}" placeholder="Enter Cityr"
                                title="Use a valid city name with letters, numbers, spaces, and common punctuation.">
                            @error('mailing_city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="mailing_state" class="form-label text-uppercase">State</label>
                            <input type="text"
                                class="form-control shadow-none @error('mailing_state') is-invalid @enderror"
                                name="mailing_state" id="mailing_state"
                                value="{{ old('mailing_state') }}"
                                placeholder="Auto-filled from address (e.g. NY)"
                                readonly
                                style="background:#f8f9fa;">
                            <small class="text-muted">Populated automatically when you select an address above.</small>
                            @error('mailing_state')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="mailing_zip_code" class="form-label text-uppercase">Zip Code</label>
                            <input type="text"
                                class="form-control shadow-none @error('mailing_zip_code') is-invalid @enderror"
                                value="{{ old('mailing_zip_code') }}" name="mailing_zip_code" id="mailing_zip_code" maxlength="6"
                                oninput="this.value = this.value.replace(/[^0-9\-]/g, '')" placeholder="Enter Zipcode">
                            @error('mailing_zip_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="h5 text-uppercase mt-4">About <em id="aboutAmbassadorName">{{ old('first_name') ?: 'Ambassador' }}</em></div>
                        <div class="fixed-border mb-3"></div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="favorite_coffee_shop" class="form-label text-uppercase">Favorite Coffee Shop</label>
                            <input type="text"
                                class="form-control shadow-none @error('favorite_coffee_shop') is-invalid @enderror"
                                value="{{ old('favorite_coffee_shop') }}" name="favorite_coffee_shop"
                                id="favorite_coffee_shop" maxlength="100"
                                placeholder="Enter favorite coffee shop">
                            @error('favorite_coffee_shop')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="favorite_restaurant" class="form-label text-uppercase">Favorite Restaurant</label>
                            <input type="text"
                                class="form-control shadow-none @error('favorite_restaurant') is-invalid @enderror"
                                value="{{ old('favorite_restaurant') }}" name="favorite_restaurant"
                                id="favorite_restaurant" maxlength="100"
                                placeholder="Enter favorite restaurant">
                            @error('favorite_restaurant')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="favorite_place_to_shop" class="form-label text-uppercase">Favorite Place to Shop</label>
                            <input type="text"
                                class="form-control shadow-none @error('favorite_place_to_shop') is-invalid @enderror"
                                value="{{ old('favorite_place_to_shop') }}" name="favorite_place_to_shop"
                                id="favorite_place_to_shop" maxlength="100"
                                placeholder="Enter favorite place to shop">
                            @error('favorite_place_to_shop')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="favorite_charity" class="form-label text-uppercase">Favorite Charity</label>
                            <input type="text"
                                class="form-control shadow-none @error('favorite_charity') is-invalid @enderror"
                                value="{{ old('favorite_charity') }}" name="favorite_charity" id="favorite_charity"
                                maxlength="100" placeholder="Enter favorite charity">
                            @error('favorite_charity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="hired" class="form-label text-uppercase">Hired</label>
                            <input type="date" class="form-control shadow-none @error('hired') is-invalid @enderror"
                                value="{{ old('hired') }}" name="hired" id="hired"
                                max="{{ now()->toDateString() }}">
                            @error('hired')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="wage" class="form-label text-uppercase">Wage</label>
                            <input type="text" class="form-control shadow-none @error('wage') is-invalid @enderror"
                                value="{{ old('wage') }}" name="wage" id="wage" maxlength="7"
                                inputmode="decimal" pattern="^\$?\d{1,3}(\.\d{1,2})?$"
                                title="Enter a dollar amount from 0.01 to 500.00 with up to 2 decimal places."
                                placeholder="Enter Wage">
                            @error('wage')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="market_id" class="form-label text-uppercase">Markets</label>
                            <div class="input-group mb-3">
                                <select
                                    class="form-select shadow-none multiple-select2 @error('market_id') is-invalid @enderror"
                                    id="market_id" name="market_id[]" multiple="multiple"
                                    data-placeholder="Select Markets">
                                    @if (!empty($markets))
                                        @foreach ($markets as $market)
                                            <option value="{{ $market->id }}"
                                                @if (is_array(old('market_id')) && in_array($market->id, old('market_id'))) selected @endif>{{ $market->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            @error('market_id')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="shirt_size" class="form-label text-uppercase">Shirt Size</label>
                            <div class="input-group mb-3">
                                <select class="form-select shadow-none @error('shirt_size') is-invalid @enderror"
                                    id="shirt_size" name="shirt_size">
                                    <option value="">Select Shirt Size</option>
                                    @foreach (getShirtSize() as $key => $size)
                                        <option value="{{ $key }}"
                                            {{ old('shirt_size', 'Medium') == $key ? 'selected' : '' }}>
                                            {{ $size }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('shirt_size')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex flex-wrap align-items-center">
                            <div class="form-check m-2">
                                <input class="form-check-input shadow-none" name="status" type="radio" value="1"
                                    id="active" @if (old('status', 1) == 1) checked @endif>
                                <label class="form-check-label text-uppercase" for="active">Active</label>
                            </div>
                            <div class="form-check m-2">
                                <input class="form-check-input shadow-none" name="status" type="radio" value="0"
                                    id="inactive" @if (old('status') == 0) checked @endif>
                                <label class="form-check-label text-uppercase" for="inactive">Inactive</label>
                            </div>
                            @error('status')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="col-2">
                    <button type="submit" class="btn-ds btn-save">Save</button>
                    <button type="button" class="btn-ds btn-back"
                        onclick="window.history.back()">Back</button>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ googleMapsPublicKey() }}&libraries=places"></script>
    <script>
        $(document).ready(function() {
            function updateAboutAmbassadorHeading() {
                const firstName = $('#firstName').val()?.trim();
                $('#aboutAmbassadorName').text(firstName || 'Ambassador');
            }

            $('#firstName').on('input', updateAboutAmbassadorHeading);
            updateAboutAmbassadorHeading();

            // Initialize Google Places Autocomplete for legal and mailing addresses
            function initAutocomplete() {
                const legalAddressInput  = document.getElementById('legal_address');
                const legalCityInput     = document.getElementById('legal_city');
                const legalZipInput      = document.getElementById('legal_zip_code');
                const mailingAddressInput= document.getElementById('mailing_address');
                const mailingCityInput   = document.getElementById('mailing_city');
                const mailingZipInput    = document.getElementById('mailing_zip_code');

                const options = {
                    types: ['address'],
                    componentRestrictions: {
                        country: 'us'
                    }
                };

                const legalAutocomplete = new google.maps.places.Autocomplete(legalAddressInput, options);
                const mailingAutocomplete = new google.maps.places.Autocomplete(mailingAddressInput, options);

                // fillAddressFields uses short_name for state — stores 2-letter code (NY, CA, TX)
                function fillAddressFields(input, cityInput, stateInput, zipInput, place) {
                    if (!place.geometry) return;

                    let city = '', stateShort = '', zip = '';

                    for (const component of place.address_components) {
                        const types = component.types;
                        if (types.includes('locality')) {
                            city = component.long_name;
                        } else if (types.includes('administrative_area_level_1')) {
                            stateShort = component.short_name; // e.g. "NY", "CA"
                        } else if (types.includes('postal_code')) {
                            zip = component.long_name;
                        }
                    }

                    // Use full formatted_address so nothing is truncated
                    input.value = place.formatted_address;
                    cityInput.value  = city;
                    stateInput.value = stateShort;
                    zipInput.value   = zip;

                    // If same as legal is checked, mirror to mailing fields
                    const legalStateInput   = document.getElementById('legal_state');
                    const mailingStateInput = document.getElementById('mailing_state');
                    if (document.getElementById('sameAsLegal').checked && input === legalAddressInput) {
                        mailingAddressInput.value = legalAddressInput.value;
                        mailingCityInput.value    = legalCityInput.value;
                        mailingStateInput.value   = legalStateInput.value;
                        mailingZipInput.value     = legalZipInput.value;
                    }
                }

                legalAutocomplete.addListener('place_changed', function() {
                    const place = legalAutocomplete.getPlace();
                    const legalStateInput = document.getElementById('legal_state');
                    fillAddressFields(legalAddressInput, legalCityInput, legalStateInput, legalZipInput, place);
                });

                mailingAutocomplete.addListener('place_changed', function() {
                    if (!document.getElementById('sameAsLegal').checked) {
                        const place = mailingAutocomplete.getPlace();
                        const mailingStateInput = document.getElementById('mailing_state');
                        fillAddressFields(mailingAddressInput, mailingCityInput, mailingStateInput, mailingZipInput, place);
                    }
                });
            }

            // Handle same as legal address checkbox
            $('#sameAsLegal').on('change', function() {
                const isChecked = this.checked;
                const mailingFields = [
                    { source: 'legal_address',  target: 'mailing_address'  },
                    { source: 'legal_city',      target: 'mailing_city'     },
                    { source: 'legal_state',     target: 'mailing_state'    },
                    { source: 'legal_zip_code',  target: 'mailing_zip_code' }
                ];

                mailingFields.forEach(field => {
                    const source = document.getElementById(field.source);
                    const target = document.getElementById(field.target);
                    if (isChecked) {
                        target.value    = source.value;
                        target.readOnly = true;
                        target.style.background = '#f8f9fa';
                    } else {
                        target.value    = '';
                        target.readOnly = false;
                        target.style.background = '';
                    }
                });
            });

            // Initialize mailing fields state based on checkbox
            $(document).ready(function() {
                initAutocomplete(); // Initialize autocomplete on page load
                if ($('#sameAsLegal').is(':checked')) {
                    [
                        { source: 'legal_address', target: 'mailing_address' },
                        { source: 'legal_city',    target: 'mailing_city'    },
                        { source: 'legal_state',   target: 'mailing_state'   },
                        { source: 'legal_zip_code',target: 'mailing_zip_code'}
                    ].forEach(field => {
                        const source = document.getElementById(field.source);
                        const target = document.getElementById(field.target);
                        target.value    = source.value;
                        target.readOnly = true;
                        target.style.background = '#f8f9fa';
                    });
                }
            });

            // Sync mailing fields when legal fields change and same_as_legal is checked
            $('#legal_address, #legal_city, #legal_state, #legal_zip_code').on('change input', function() {
                if ($('#sameAsLegal').is(':checked')) {
                    const mapping = {
                        'legal_address': 'mailing_address',
                        'legal_city':    'mailing_city',
                        'legal_state':   'mailing_state',
                        'legal_zip_code':'mailing_zip_code'
                    };
                    const targetId = mapping[this.id];
                    if (targetId) {
                        document.getElementById(targetId).value = this.value;
                    }
                }
            });

        });
    </script>
    <script>
      document.getElementById('togglePassword').addEventListener('click', function () {
        const passwordInput = document.getElementById('password');
        const icon = document.getElementById('toggleIcon');
    
        if (passwordInput.type === 'password') {
          passwordInput.type = 'text';
          icon.classList.remove('fa-eye-slash');
          icon.classList.add('fa-eye');
        } else {
          passwordInput.type = 'password';
          icon.classList.remove('fa-eye');
          icon.classList.add('fa-eye-slash');
        }
      });
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('ambassadorCreateForm');
            const dobInput = document.getElementById('dob');
            const hiredInput = document.getElementById('hired');
            const issuedAtInput = document.getElementById('issued_at');
            const expiresAtInput = document.getElementById('expires_at');
            const validatedTextFields = [
                {
                    input: document.getElementById('license_number'),
                    allowedCharacters: /^[A-Za-z0-9 .\/-]+$/,
                    pattern: /^(?=.*[A-Za-z0-9])(?!.*[ .\/-]{3})[A-Za-z0-9](?:[A-Za-z0-9 .\/-]*[A-Za-z0-9])?$/,
                    message: 'Use letters, numbers, spaces, hyphens, slashes, or dots. Include at least one letter or number.',
                },
                {
                    input: document.getElementById('legal_address'),
                    allowedCharacters: /^[A-Za-z0-9 .,\/'-]+$/,
                    pattern: /^(?=.*[A-Za-z0-9])(?!.*[ .,\/'-]{4})[A-Za-z0-9][A-Za-z0-9 .,\/'-]*[A-Za-z0-9.]$/,
                    message: 'Legal address must include letters or numbers and may only use common address punctuation. Do not use # or symbols.',
                },
                {
                    input: document.getElementById('legal_city'),
                    allowedCharacters: /^[A-Za-z0-9 .,'-]+$/,
                    pattern: /^(?=.*[A-Za-z])(?!.*[ .,'-]{4})[A-Za-z0-9][A-Za-z0-9 .,'-]*[A-Za-z0-9.]$/,
                    message: 'Legal city must be a valid city name using letters, numbers, spaces, and common punctuation.',
                },
                {
                    input: document.getElementById('mailing_address'),
                    allowedCharacters: /^[A-Za-z0-9 .,\/'-]+$/,
                    pattern: /^(?=.*[A-Za-z0-9])(?!.*[ .,\/'-]{4})[A-Za-z0-9][A-Za-z0-9 .,\/'-]*[A-Za-z0-9.]$/,
                    message: 'Mailing address must include letters or numbers and may only use common address punctuation. Do not use # or symbols.',
                },
                {
                    input: document.getElementById('mailing_city'),
                    allowedCharacters: /^[A-Za-z0-9 .,'-]+$/,
                    pattern: /^(?=.*[A-Za-z])(?!.*[ .,'-]{4})[A-Za-z0-9][A-Za-z0-9 .,'-]*[A-Za-z0-9.]$/,
                    message: 'Mailing city must be a valid city name using letters, numbers, spaces, and common punctuation.',
                },
                {
                    input: document.getElementById('favorite_coffee_shop'),
                    allowedCharacters: /^[A-Za-z0-9 .,&'\/-]+$/,
                    pattern: /^(?=.*[A-Za-z0-9])(?!.*[ .,&'\/-]{4})[A-Za-z0-9][A-Za-z0-9 .,&'\/-]*[A-Za-z0-9.]$/,
                    message: 'Favorite coffee shop must include letters or numbers and may only use common name punctuation.',
                },
                {
                    input: document.getElementById('favorite_restaurant'),
                    allowedCharacters: /^[A-Za-z0-9 .,&'\/-]+$/,
                    pattern: /^(?=.*[A-Za-z0-9])(?!.*[ .,&'\/-]{4})[A-Za-z0-9][A-Za-z0-9 .,&'\/-]*[A-Za-z0-9.]$/,
                    message: 'Favorite restaurant must include letters or numbers and may only use common name punctuation.',
                },
                {
                    input: document.getElementById('favorite_place_to_shop'),
                    allowedCharacters: /^[A-Za-z0-9 .,&'\/-]+$/,
                    pattern: /^(?=.*[A-Za-z0-9])(?!.*[ .,&'\/-]{4})[A-Za-z0-9][A-Za-z0-9 .,&'\/-]*[A-Za-z0-9.]$/,
                    message: 'Favorite place to shop must include letters or numbers and may only use common name punctuation.',
                },
                {
                    input: document.getElementById('favorite_charity'),
                    allowedCharacters: /^[A-Za-z0-9 .,&'\/-]+$/,
                    pattern: /^(?=.*[A-Za-z0-9])(?!.*[ .,&'\/-]{4})[A-Za-z0-9][A-Za-z0-9 .,&'\/-]*[A-Za-z0-9.]$/,
                    message: 'Favorite charity must include letters or numbers and may only use common name punctuation.',
                },
                {
                    input: document.getElementById('wage'),
                    allowedCharacters: /^[0-9.$]+$/,
                    pattern: /^\d{1,3}(\.\d{1,2})?$/,
                    minValue: 0.01,
                    maxValue: 500,
                    message: 'Enter a dollar amount from 0.01 to 500.00 with up to 2 decimal places.',
                },
            ];
            const stateInputs = [
                {
                    input: document.getElementById('issuing_state'),
                    allowedCharacters: /^[A-Za-z ]+$/,
                    message: 'Enter a valid US state name or abbreviation.',
                },
                {
                    input: document.getElementById('legal_state'),
                    allowedCharacters: /^[A-Za-z ]+$/,
                    required: true,
                    message: 'Legal state must be a valid US state abbreviation.',
                },
                {
                    input: document.getElementById('mailing_state'),
                    allowedCharacters: /^[A-Za-z ]+$/,
                    message: 'Mailing state must be a valid US state abbreviation.',
                },
            ];
            const validStates = [
                'AL', 'ALABAMA', 'AK', 'ALASKA', 'AZ', 'ARIZONA', 'AR', 'ARKANSAS',
                'CA', 'CALIFORNIA', 'CO', 'COLORADO', 'CT', 'CONNECTICUT', 'DE', 'DELAWARE',
                'DC', 'DISTRICT OF COLUMBIA', 'FL', 'FLORIDA', 'GA', 'GEORGIA', 'HI', 'HAWAII',
                'ID', 'IDAHO', 'IL', 'ILLINOIS', 'IN', 'INDIANA', 'IA', 'IOWA', 'KS', 'KANSAS',
                'KY', 'KENTUCKY', 'LA', 'LOUISIANA', 'ME', 'MAINE', 'MD', 'MARYLAND',
                'MA', 'MASSACHUSETTS', 'MI', 'MICHIGAN', 'MN', 'MINNESOTA', 'MS', 'MISSISSIPPI',
                'MO', 'MISSOURI', 'MT', 'MONTANA', 'NE', 'NEBRASKA', 'NV', 'NEVADA',
                'NH', 'NEW HAMPSHIRE', 'NJ', 'NEW JERSEY', 'NM', 'NEW MEXICO', 'NY', 'NEW YORK',
                'NC', 'NORTH CAROLINA', 'ND', 'NORTH DAKOTA', 'OH', 'OHIO', 'OK', 'OKLAHOMA',
                'OR', 'OREGON', 'PA', 'PENNSYLVANIA', 'RI', 'RHODE ISLAND',
                'SC', 'SOUTH CAROLINA', 'SD', 'SOUTH DAKOTA', 'TN', 'TENNESSEE', 'TX', 'TEXAS',
                'UT', 'UTAH', 'VT', 'VERMONT', 'VA', 'VIRGINIA', 'WA', 'WASHINGTON',
                'WV', 'WEST VIRGINIA', 'WI', 'WISCONSIN', 'WY', 'WYOMING',
            ];
            const today = new Date();
            const yesterday = new Date(today);

            function formatDateInputValue(date) {
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');

                return `${date.getFullYear()}-${month}-${day}`;
            }

            const todayValue = formatDateInputValue(today);
            yesterday.setDate(today.getDate() - 1);
            dobInput.max = formatDateInputValue(yesterday);
            hiredInput.max = todayValue;
            issuedAtInput.max = todayValue;

            function updateExpirationMin() {
                const minDate = issuedAtInput.value && issuedAtInput.value > todayValue
                    ? issuedAtInput.value
                    : todayValue;

                expiresAtInput.min = minDate;

                if (expiresAtInput.value && expiresAtInput.value < minDate) {
                    expiresAtInput.value = '';
                }
            }

            function validateDateRange(input, message, isInvalid) {
                input.setCustomValidity(isInvalid() ? message : '');
            }

            function validateDates() {
                updateExpirationMin();

                validateDateRange(
                    dobInput,
                    'Date of Birth must be before today.',
                    function() {
                        return dobInput.value && dobInput.value >= todayValue;
                    }
                );

                validateDateRange(
                    hiredInput,
                    'Hired date cannot be in the future.',
                    function() {
                        return hiredInput.value && hiredInput.value > todayValue;
                    }
                );

                validateDateRange(
                    issuedAtInput,
                    'Issued date cannot be in the future.',
                    function() {
                        return issuedAtInput.value && issuedAtInput.value > todayValue;
                    }
                );

                validateDateRange(
                    expiresAtInput,
                    'Expiration date must be today or later and on or after the issued date.',
                    function() {
                        return expiresAtInput.value
                            && (expiresAtInput.value < expiresAtInput.min
                                || (issuedAtInput.value && expiresAtInput.value < issuedAtInput.value));
                    }
                );
            }

            function normalizeTextFields() {
                validatedTextFields.forEach(function(field) {
                    let value = field.input.value.trim().replace(/\s+/g, ' ');

                    if (field.input.id === 'wage') {
                        value = value.replace(/^\$/, '');
                    }

                    field.input.value = value;
                });

                stateInputs.forEach(function(field) {
                    field.input.value = field.input.value.trim().replace(/\s+/g, ' ').toUpperCase();
                });
            }

            function validateTextFields() {
                validatedTextFields.forEach(function(field) {
                    const value = field.input.value.trim().replace(/\s+/g, ' ');

                    field.input.setCustomValidity(
                        value && (!field.pattern.test(value)
                            || (field.minValue && Number(value) < field.minValue)
                            || (field.maxValue && Number(value) > field.maxValue))
                            ? field.message
                            : ''
                    );
                });

                stateInputs.forEach(function(field) {
                    const value = field.input.value.trim().replace(/\s+/g, ' ').toUpperCase();

                    if (field.required && !value) {
                        field.input.setCustomValidity(field.message);
                    } else {
                        field.input.setCustomValidity(value && !validStates.includes(value) ? field.message : '');
                    }
                });
            }

            function sanitizeInputValue(value, allowedCharacters) {
                return Array.from(value).filter(function(character) {
                    return allowedCharacters.test(character);
                }).join('');
            }

            function bindCharacterFilter(field) {
                if (field.input.tagName === 'SELECT') {
                    return;
                }

                field.input.addEventListener('beforeinput', function(event) {
                    if (!event.data || event.inputType.indexOf('insert') !== 0) {
                        return;
                    }

                    if (!field.allowedCharacters.test(event.data)) {
                        event.preventDefault();
                    }
                });

                field.input.addEventListener('paste', function(event) {
                    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
                    const sanitizedText = sanitizeInputValue(pastedText, field.allowedCharacters);

                    if (sanitizedText !== pastedText) {
                        event.preventDefault();
                        field.input.setRangeText(
                            sanitizedText,
                            field.input.selectionStart,
                            field.input.selectionEnd,
                            'end'
                        );
                        field.input.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
            }

            issuedAtInput.addEventListener('change', validateDates);
            expiresAtInput.addEventListener('change', validateDates);
            dobInput.addEventListener('change', validateDates);
            hiredInput.addEventListener('change', validateDates);
            validatedTextFields.forEach(function(field) {
                bindCharacterFilter(field);
                field.input.addEventListener('focus', function() {
                    field.input.setCustomValidity('');
                });
                field.input.addEventListener('blur', function() {
                    normalizeTextFields();
                    validateTextFields();
                });
            });
            stateInputs.forEach(function(field) {
                bindCharacterFilter(field);
                field.input.addEventListener('focus', function() {
                    field.input.setCustomValidity('');
                });
                field.input.addEventListener('blur', function() {
                    normalizeTextFields();
                    validateTextFields();
                });
            });

            form.addEventListener('submit', function(event) {
                normalizeTextFields();
                validateTextFields();
                validateDates();

                if (!form.checkValidity()) {
                    event.preventDefault();
                    form.reportValidity();
                }
            });

            validateDates();
        });
    </script>

@endsection
