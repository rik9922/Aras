@extends('layout.app', ['page_name' => 'Edit Ambassador'])
@php
    $ambRoutePrefix = request()->routeIs('manager.*') ? 'manager' : 'admin';
@endphp
<link rel="stylesheet" href="{{ asset('assets/select2/select2.css') }}">
<style>
    .ambassador-file-input {
        height: 1px;
        opacity: 0;
        overflow: hidden;
        position: absolute;
        width: 1px;
        z-index: -1;
    }
</style>
@section('content')
    <div class="container py-3">
        <div class="d-flex">
            <div class="h5">Edit Ambassador</div>
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
            <form action="{{ route($ambRoutePrefix . '.update.ambassador') }}" method="POST" enctype="multipart/form-data">@csrf
                <input type="hidden" name="id" value="{{ $ambassador->id }}">
                <div class="row p-3 pb-0">
                    <div class="col-md-6 col-xl-4">
                        <div class="h4 text-uppercase">Profile Picture</div>
                        <div class="profile-img">
                            <img id="preview-image"
                                src="{{ userProfileImageUrl($ambassador->getRawProfileImagePath()) }}"
                                height="150" width="150" style="object-fit: cover; border-radius: 50%;"
                                alt="Profile picture for {{ $ambassador->name }}">
                        </div>
                        <div class="content-box-grey p-3 mt-3">
                            Upload a new profile image and click Save. Center the face in the photo for best results.
                            <div class="pt-2 d-flex flex-wrap align-items-center gap-2">
                                <label for="file-upload" class="btn-ds btn-secondary-ds mb-0">Choose File</label>
                                <span id="profile-file-name" class="text-muted small">No file selected</span>
                                <input id="file-upload" class="ambassador-file-input" type="file" name="profile_image" accept="image/png,image/jpeg,image/jpg" />
                                @error('profile_image')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-8 d-flex align-items-center">
                        <div>
                            <div class="h5 mb-1">{{ $ambassador->name }}</div>
                            <div class="text-muted">{{ $ambassador->email }}</div>
                        </div>
                    </div>
                </div>
                <div class="row p-3">
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="firstName" class="form-label text-uppercase">First Name</label>
                            <input type="text" class="form-control shadow-none @error('first_name') is-invalid @enderror"
                                value="{{ old('first_name', $ambassador->first_name) }}" name="first_name" id="firstName">
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="lastName" class="form-label text-uppercase">Last Name</label>
                            <input type="text" class="form-control shadow-none @error('last_name') is-invalid @enderror"
                                value="{{ old('last_name', $ambassador->last_name) }}" name="last_name" id="lastName">
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="email" class="form-label text-uppercase">Email</label>
                            <input type="email" readonly disabled
                                class="form-control shadow-none @error('email') is-invalid @enderror"
                                value="{{ $ambassador->email }}" id="email">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="last_event" class="form-label text-uppercase">Last Event Worked</label>
                            <input type="text" readonly disabled class="form-control shadow-none"
                                   value="{{ $lastEvent ? $lastEvent->event_name . ' (' . \Carbon\Carbon::parse($lastEvent->end_time)->format('M d, Y') . ')' : 'No events yet' }}"
                                   id="last_event">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="nickname" class="form-label text-uppercase">Nickname</label>
                            <input type="text" class="form-control shadow-none @error('nickname') is-invalid @enderror"
                                value="{{ old('nickname', $ambassador->nickname) }}" name="nickname" id="nickname">
                            @error('nickname')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <!--<div class="col-6">-->
                    <!--    <div class="mb-3">-->
                    <!--        <label for="dob" class="form-label text-uppercase">DOB</label>-->
                    <!--        <input type="date" class="form-control shadow-none @error('dob') is-invalid @enderror"-->
                    <!--            value="{{ old('dob', $ambassador->dob) }}" name="dob" id="dob">-->
                    <!--        @error('dob')-->
                    <!--            <div class="invalid-feedback">{{ $message }}</div>-->
                    <!--        @enderror-->
                    <!--    </div>-->
                    <!--</div>-->
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="dob" class="form-label text-uppercase">DOB</label>
                            <input 
                                type="date" 
                                class="form-control shadow-none @error('dob') is-invalid @enderror"
                                value="{{ old('dob', $ambassador->dob ? \Carbon\Carbon::parse($ambassador->dob)->format('Y-m-d') : '') }}" 
                                name="dob" 
                                id="dob"
                            >
                            @error('dob')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="venmo" class="form-label text-uppercase">Venmo</label>
                            <input type="text" class="form-control shadow-none @error('venmo') is-invalid @enderror"
                                value="{{ old('venmo', $ambassador->venmo) }}" name="venmo" id="venmo">
                            @error('venmo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="region" class="form-label text-uppercase">Region</label>
                            <div class="d-flex gap-4">
                                <select class="form-select shadow-none @error('region') is-invalid @enderror" id="region"
                                    name="region">
                                    <option value="" selected>Select Region</option>
                                    <option value="Southeast" @if (old('region', $ambassador->region) == 'Southeast') selected @endif>Southeast
                                    </option>
                                    <option value="Midwest" @if (old('region', $ambassador->region) == 'Midwest') selected @endif>Midwest
                                    </option>
                                </select>
                            </div>
                            @error('region')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="phone" class="form-label text-uppercase">Phone</label>
                            <input type="text" class="form-control shadow-none @error('phone') is-invalid @enderror"
                                value="{{ old('phone', $ambassador->phone) }}" name="phone" id="phone" maxlength="10"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')">
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
                                        @if (old('titos_trained', $ambassador->titos_trained ?? false)) checked @endif>
                                    <label class="form-check-label" for="titosTrained">Tito's Trained</label>
                                    @error('titos_trained')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="hidden" name="thc_quiz_passed" value="0">
                                    <input type="checkbox" class="form-check-input" id="thcQuizPassed"
                                        name="thc_quiz_passed" value="1"
                                        @if (old('thc_quiz_passed', $ambassador->thc_quiz_passed ?? false)) checked @endif>
                                    <label class="form-check-label" for="thcQuizPassed">THC Quiz Passed</label>
                                    @error('thc_quiz_passed')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="hidden" name="male_ba_lgbtq_ok" value="0">
                                    <input type="checkbox" class="form-check-input" id="maleBaLgbtqOk"
                                        name="male_ba_lgbtq_ok" value="1"
                                        @if (old('male_ba_lgbtq_ok', $ambassador->male_ba_lgbtq_ok ?? false)) checked @endif>
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
                                        @if (old('attended_trainings', $ambassador->attended_trainings ?? false)) checked @endif>
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
                                        @if (old('tips_certified', $ambassador->tips_certified ?? false)) checked @endif>
                                    <label class="form-check-label" for="tipsCertified">TIPS Certified</label>
                                    @error('tips_certified')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="hidden" name="has_permit" value="0">
                                    <input type="checkbox" class="form-check-input" id="hasPermit" name="has_permit"
                                        value="1" @if (old('has_permit', $ambassador->has_permit ?? false)) checked @endif>
                                    <label class="form-check-label" for="hasPermit">Has Permit</label>
                                    @error('has_permit')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    @php
                        $liquor = $ambassador->ambassadorLiquorLicense;
                    @endphp
                    <div class="col-12">
                        <div class="h5 text-uppercase mt-4">Liquor license</div>
                        <div class="fixed-border mb-3"></div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3 form-check">
                            <input type="hidden" name="has_liquor_license" value="0">
                            <input type="checkbox" class="form-check-input" id="hasLiquorLicense" name="has_liquor_license"
                                value="1" @if (old('has_liquor_license', $liquor?->has_liquor_license ?? false)) checked @endif>
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
                                name="license_number" id="license_number"
                                value="{{ old('license_number', $liquor?->license_number ?? '') }}"
                                placeholder="e.g. state license ID">
                            @error('license_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mb-3">
                            <label for="issuing_state" class="form-label text-uppercase">Issuing state</label>
                            <input type="text" class="form-control shadow-none @error('issuing_state') is-invalid @enderror"
                                name="issuing_state" id="issuing_state"
                                value="{{ old('issuing_state', $liquor?->issuing_state ?? '') }}"
                                placeholder="e.g. FL" maxlength="50">
                            @error('issuing_state')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mb-3 form-check pt-4">
                            <input type="hidden" name="document_on_file" value="0">
                            <input type="checkbox" class="form-check-input" id="liquorDocumentOnFile" name="document_on_file"
                                value="1" @if (old('document_on_file', $liquor?->document_on_file ?? false)) checked @endif>
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
                                name="issued_at" id="issued_at"
                                value="{{ old('issued_at', optional($liquor?->issued_at)->format('Y-m-d')) }}">
                            @error('issued_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mb-3">
                            <label for="expires_at" class="form-label text-uppercase">Expiration date</label>
                            <input type="date" class="form-control shadow-none @error('expires_at') is-invalid @enderror"
                                name="expires_at" id="expires_at"
                                value="{{ old('expires_at', optional($liquor?->expires_at)->format('Y-m-d')) }}">
                            @error('expires_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="liquor_notes" class="form-label text-uppercase">Notes</label>
                            <textarea class="form-control shadow-none @error('notes') is-invalid @enderror" name="notes"
                                id="liquor_notes" rows="2" placeholder="Optional notes">{{ old('notes', $liquor?->notes ?? '') }}</textarea>
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
                                name="legal_address" id="legal_address"
                                value="{{ old('legal_address', $ambassador->legal_address) }}">
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
                                name="legal_city" id="legal_city"
                                value="{{ old('legal_city', $ambassador->legal_city) }}">
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
                                value="{{ old('legal_state', $ambassador->ambassadorProfile?->legal_state ?? $ambassador->legal_state ?? '') }}"
                                placeholder="Auto-filled from address (e.g. NY)"
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
                                value="{{ old('legal_zip_code', $ambassador->legal_zip_code) }}" name="legal_zip_code"
                                id="legal_zip_code" maxlength="6"
                                oninput="this.value = this.value.replace(/[^0-9\-]/g, '')">
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
                                value="1" @if (old('same_as_legal', $ambassador->same_as_legal)) checked @endif>
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
                                name="mailing_address" id="mailing_address"
                                value="{{ old('mailing_address', $ambassador->mailing_address) }}">
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
                                name="mailing_city" id="mailing_city"
                                value="{{ old('mailing_city', $ambassador->mailing_city) }}">
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
                                value="{{ old('mailing_state', $ambassador->ambassadorProfile?->mailing_state ?? $ambassador->mailing_state ?? '') }}"
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
                                value="{{ old('mailing_zip_code', $ambassador->mailing_zip_code) }}"
                                name="mailing_zip_code" id="mailing_zip_code" maxlength="6"
                                oninput="this.value = this.value.replace(/[^0-9\-]/g, '')">
                            @error('mailing_zip_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="h5 text-uppercase mt-4">About <em id="aboutAmbassadorName">{{ old('first_name', $ambassador->first_name) ?: 'Ambassador' }}</em></div>
                        <div class="fixed-border mb-3"></div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="favorite_coffee_shop" class="form-label text-uppercase">Favorite Coffee Shop</label>
                            <input type="text"
                                class="form-control shadow-none @error('favorite_coffee_shop') is-invalid @enderror"
                                value="{{ old('favorite_coffee_shop', $ambassador->favorite_coffee_shop) }}"
                                name="favorite_coffee_shop" id="favorite_coffee_shop"
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
                                value="{{ old('favorite_restaurant', $ambassador->favorite_restaurant) }}"
                                name="favorite_restaurant" id="favorite_restaurant"
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
                                value="{{ old('favorite_place_to_shop', $ambassador->favorite_place_to_shop) }}"
                                name="favorite_place_to_shop" id="favorite_place_to_shop"
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
                                value="{{ old('favorite_charity', $ambassador->favorite_charity) }}"
                                name="favorite_charity" id="favorite_charity" placeholder="Enter favorite charity">
                            @error('favorite_charity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="hired" class="form-label text-uppercase">Hired</label>
                            <input type="date" class="form-control shadow-none @error('hired') is-invalid @enderror"
                                value="{{ old('hired', $ambassador->hired ? \Carbon\Carbon::parse($ambassador->hired)->format('Y-m-d') : '') }}" name="hired" id="hired">
                            @error('hired')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="wage" class="form-label text-uppercase">Wage</label>
                            <input type="text" class="form-control shadow-none @error('wage') is-invalid @enderror"
                                value="{{ old('wage', $ambassador->wage) }}" name="wage" id="wage">
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
                                                @if (
                                                    (is_array(old('market_id')) && in_array($market->id, old('market_id'))) ||
                                                    (!old('market_id') && in_array($market->id, $selectedMarketIds ?? []))
                                                ) selected @endif>{{ $market->name }}
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
                                        <option value="{{ $size }}"
                                            {{ old('shirt_size', $ambassador->shirt_size ?? 'Medium') == $size ? 'selected' : '' }}>
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
                                    id="active" @if (old('status', $ambassador->status) == '1') checked @endif>
                                <label class="form-check-label text-uppercase" for="active">Active</label>
                            </div>
                            <div class="form-check m-2">
                                <input class="form-check-input shadow-none" name="status" type="radio" value="0"
                                    id="inactive" @if (old('status', $ambassador->status) == '0') checked @endif>
                                <label class="form-check-label text-uppercase" for="inactive">Inactive</label>
                            </div>
                            @error('status')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-row gap-3">
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
        document.getElementById('file-upload')?.addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('preview-image');
            const fileName = document.getElementById('profile-file-name');

            if (fileName) {
                fileName.textContent = file ? file.name : 'No file selected';
            }

            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
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

                // fillAddressFields — uses short_name for state (NY, CA, TX)
                function fillAddressFields(input, cityInput, stateInput, zipInput, place) {
                    if (!place.geometry) return;

                    let city = '', stateShort = '', zip = '';

                    for (const component of place.address_components) {
                        const types = component.types;
                        if (types.includes('locality'))                         city       = component.long_name;
                        else if (types.includes('administrative_area_level_1')) stateShort = component.short_name;
                        else if (types.includes('postal_code'))                 zip        = component.long_name;
                    }

                    // Use full formatted_address so nothing is truncated
                    input.value = place.formatted_address;
                    cityInput.value  = city;
                    stateInput.value = stateShort;
                    zipInput.value   = zip;

                    // Mirror to mailing if same_as_legal is checked
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
                    { source: 'legal_address', target: 'mailing_address' },
                    { source: 'legal_city',    target: 'mailing_city'    },
                    { source: 'legal_state',   target: 'mailing_state'   },
                    { source: 'legal_zip_code',target: 'mailing_zip_code'}
                ];
                mailingFields.forEach(field => {
                    const source = document.getElementById(field.source);
                    const target = document.getElementById(field.target);
                    if (isChecked) {
                        target.value    = source.value;
                        target.readOnly = true;
                        target.style.background = '#f8f9fa';
                    } else {
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
    document.addEventListener("DOMContentLoaded", function() {
        const dobInput = document.getElementById("dob");
        const today = new Date().toISOString().split("T")[0];
        dobInput.setAttribute("max", today);
    
        dobInput.addEventListener("change", function() {
            if (this.value > today) {
                alert("Date of Birth cannot be in the future.");
                this.value = ""; // clear invalid date
            }
        });
    });
    </script>
@endsection
