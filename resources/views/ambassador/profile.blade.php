@extends('layout.app', ['page_name' => 'My Account'])
<link rel="stylesheet" href="{{ asset('assets/select2/select2.css') }}">
@section('content')
    <div class="container p-3">
        <div class="h4">Profile</div>
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
            <form action="{{ route('ambassador.update-profile') }}" method="POST" enctype="multipart/form-data">@csrf
                <div class="row">
                    <div class="col-md-6 col-xl-8 order-xl-1 order-2">
                        <div class="row pe-xl-4">

                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="firstName" class="form-label text-uppercase">First Name</label>
                                    <input type="text"
                                        class="form-control shadow-none @error('first_name') is-invalid @enderror"
                                        value="{{ old('first_name', $user->first_name) }}" name="first_name" id="firstName">
                                    @error('first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="lastName" class="form-label text-uppercase">Last Name</label>
                                    <input type="text"
                                        class="form-control shadow-none @error('last_name') is-invalid @enderror"
                                        value="{{ old('last_name', $user->last_name) }}" name="last_name" id="lastName">
                                    @error('last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="nickname" class="form-label text-uppercase">Nickname</label>
                                    <input type="text"
                                        class="form-control shadow-none @error('nickname') is-invalid @enderror"
                                        value="{{ old('nickname', $user->nickname) }}" name="nickname" id="nickname">
                                    @error('nickname')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="venmoName" class="form-label text-uppercase">Venmo Name</label>
                                    <input type="text"
                                        class="form-control shadow-none @error('venmo') is-invalid @enderror" name="venmo"
                                        value="{{ old('venmo', $user->venmo) }}" id="venmoName">
                                    @error('venmo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="phoneNumber" class="form-label text-uppercase">Cell Phone Number</label>
                                    <input type="text"
                                        class="form-control shadow-none @error('phone') is-invalid @enderror" name="phone"
                                        value="{{ old('phone', $user->phone) }}" id="phoneNumber">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="text-muted">
                                        Your cell phone may be used for text notifications. Standard message and data rates
                                        may apply
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="emailORusername" class="form-label text-uppercase">Email</label>
                                    <input type="email" class="form-control shadow-none" value="{{ $user->email }}"
                                        readonly disabled id="emailORusername">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="market_id" class="form-label text-uppercase">Market</label>
                                    <div class="input-group mb-3">
                                        @php
                                            // Prefer pivot markets (ambassadors table), fall back to legacy proxy accessor
                                            $selectedMarketIds = old('market_id',
                                                $user->ambassadorProfile?->markets->pluck('id')->toArray()
                                                ?? $user->market_id
                                                ?? []
                                            );
                                        @endphp
                                        <select
                                            class="form-select shadow-none multiple-select2 @error('market_id') is-invalid @enderror"
                                            id="market_id" name="market_id[]" multiple="multiple"
                                            data-placeholder="Select Markets">
                                            @if (!empty(getMarket()) && count(getMarket()) > 0)
                                                @foreach (getMarket() as $market)
                                                    <option value="{{ $market->id }}"
                                                        @if (in_array($market->id, (array) $selectedMarketIds)) selected @endif>
                                                        {{ $market->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    @error('market_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="market_id" class="form-label text-uppercase">DOB</label>
                                    <div class="input-group mb-3">
                                        <input type="date"
                                            class="form-control shadow-none @error('dob') is-invalid @enderror"
                                            value="{{ old('dob', $profile?->dob?->format('Y-m-d')) }}" name="dob" id="dob">
                                        @error('dob')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    @error('market_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="h5 text-uppercase mt-4">Legal Address (for tax purposes)</div>
                                <div class="fixed-border mb-3"></div>
                                <div class="row">
                                    <div class="col-xl-6">
                                        <div class="mb-3">
                                            <label for="legal_address" class="form-label text-uppercase">Legal
                                                Address</label>
                                            <input type="text"
                                                class="form-control shadow-none @error('legal_address') is-invalid @enderror"
                                                name="legal_address" id="legal_address"
                                                value="{{ old('legal_address', $user->legal_address ?? $user->address) }}">
                                            @error('legal_address')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-xl-6">
                                        <div class="mb-3">
                                            <label for="legal_city" class="form-label text-uppercase">City</label>
                                            <input type="text"
                                                class="form-control shadow-none @error('legal_city') is-invalid @enderror"
                                                name="legal_city" id="legal_city"
                                                value="{{ old('legal_city', $user->legal_city ?? $user->city) }}">
                                            @error('legal_city')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-xl-6">
                                        <div class="mb-3">
                                            <label for="legal_state" class="form-label text-uppercase">State</label>
                                            <select
                                                class="form-control shadow-none @error('legal_state_id') is-invalid @enderror"
                                                name="legal_state_id" id="legal_state">
                                                <option value=""> Select State</option>
                                                @foreach (getState() as $state)
                                                    <option data-shortName="{{ $state->short_name }}"
                                                        data-fullName="{{ $state->name }}" value="{{ $state->id }}"
                                                        @if (old('legal_state_id', $legalStateId) == $state->id) selected @endif>
                                                        {{ $state->short_name }}</option>
                                                @endforeach
                                            </select>
                                            @error('legal_state_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-xl-6">
                                        <div class="mb-3">
                                            <label for="legal_zip" class="form-label text-uppercase">Zip Code</label>
                                            <input type="text"
                                                class="form-control shadow-none @error('legal_zip_code') is-invalid @enderror"
                                                value="{{ old('legal_zip_code', $user->legal_zip_code ?? $user->zip_code) }}"
                                                name="legal_zip_code" id="legal_zip" maxlength="6">
                                            @error('legal_zip_code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="h5 text-uppercase mt-4">Mailing Address</div>
                                <div class="fixed-border mb-3"></div>
                                <div class="mb-3 form-check">
                                    <input type="hidden" name="same_as_legal" value="0">
                                    <input type="checkbox" class="form-check-input" id="sameAsLegal"
                                        name="same_as_legal" value="1"
                                        @if (old('same_as_legal', $user->same_as_legal ?? false)) checked @endif>
                                    <label class="form-check-label" for="sameAsLegal">Same as Legal Address</label>
                                    @error('same_as_legal')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-xl-6">
                                        <div class="mb-3">
                                            <label for="mailing_address" class="form-label text-uppercase">Mailing
                                                Address</label>
                                            <input type="text"
                                                class="form-control shadow-none @error('mailing_address') is-invalid @enderror"
                                                name="mailing_address" id="mailing_address"
                                                value="{{ old('mailing_address', $user->mailing_address ?? $user->address) }}">
                                            @error('mailing_address')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-xl-6">
                                        <div class="mb-3">
                                            <label for="mailing_city" class="form-label text-uppercase">City</label>
                                            <input type="text"
                                                class="form-control shadow-none @error('mailing_city') is-invalid @enderror"
                                                name="mailing_city" id="mailing_city"
                                                value="{{ old('mailing_city', $user->mailing_city ?? $user->city) }}">
                                            @error('mailing_city')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-xl-6">
                                        <div class="mb-3">
                                            <label for="mailing_state" class="form-label text-uppercase">State</label>
                                            <select
                                                class="form-control shadow-none @error('mailing_state_id') is-invalid @enderror"
                                                name="mailing_state_id" id="mailing_state">
                                                <option value=""> Select State</option>
                                                @foreach (getState() as $state)
                                                    <option data-shortName="{{ $state->short_name }}"
                                                        data-fullName="{{ $state->name }}" value="{{ $state->id }}"
                                                        @if (old('mailing_state_id', $mailingStateId) == $state->id) selected @endif>
                                                        {{ $state->short_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('mailing_state_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-xl-6">
                                        <div class="mb-3">
                                            <label for="mailing_zip" class="form-label text-uppercase">Zip Code</label>
                                            <input type="text"
                                                class="form-control shadow-none @error('mailing_zip_code') is-invalid @enderror"
                                                value="{{ old('mailing_zip_code', $user->mailing_zip_code ?? $user->zip_code) }}"
                                                name="mailing_zip_code" id="mailing_zip" maxlength="6">
                                            @error('mailing_zip_code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="h5 text-uppercase mt-4">About <em id="aboutAmbassadorName">{{ old('first_name', $user->first_name) ?: 'Ambassador' }}</em></div>
                                <div class="fixed-border mb-3"></div>
                                <div class="row">
                                    <div class="col-xl-6">
                                        <div class="mb-3">
                                            <label for="favorite_coffee_shop" class="form-label text-uppercase">Favorite Coffee Shop</label>
                                            <input type="text"
                                                class="form-control shadow-none @error('favorite_coffee_shop') is-invalid @enderror"
                                                value="{{ old('favorite_coffee_shop', $user->favorite_coffee_shop) }}"
                                                name="favorite_coffee_shop" id="favorite_coffee_shop"
                                                placeholder="Enter favorite coffee shop">
                                            @error('favorite_coffee_shop')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-xl-6">
                                        <div class="mb-3">
                                            <label for="favorite_restaurant" class="form-label text-uppercase">Favorite Restaurant</label>
                                            <input type="text"
                                                class="form-control shadow-none @error('favorite_restaurant') is-invalid @enderror"
                                                value="{{ old('favorite_restaurant', $user->favorite_restaurant) }}"
                                                name="favorite_restaurant" id="favorite_restaurant"
                                                placeholder="Enter favorite restaurant">
                                            @error('favorite_restaurant')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-xl-6">
                                        <div class="mb-3">
                                            <label for="favorite_place_to_shop" class="form-label text-uppercase">Favorite Place to Shop</label>
                                            <input type="text"
                                                class="form-control shadow-none @error('favorite_place_to_shop') is-invalid @enderror"
                                                value="{{ old('favorite_place_to_shop', $user->favorite_place_to_shop) }}"
                                                name="favorite_place_to_shop" id="favorite_place_to_shop"
                                                placeholder="Enter favorite place to shop">
                                            @error('favorite_place_to_shop')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-xl-6">
                                        <div class="mb-3">
                                            <label for="favorite_charity" class="form-label text-uppercase">Favorite Charity</label>
                                            <input type="text"
                                                class="form-control shadow-none @error('favorite_charity') is-invalid @enderror"
                                                value="{{ old('favorite_charity', $user->favorite_charity) }}"
                                                name="favorite_charity" id="favorite_charity"
                                                placeholder="Enter favorite charity">
                                            @error('favorite_charity')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="h5 text-uppercase mt-4">Your Location</div>
                                <div class="fixed-border mb-3"></div>
                                <p class="text-muted mb-3">
                                    Select your location on the map so we can match you with nearby events.
                                </p>
                                @include('common.partials.ambassador-location-map', [
                                    'existingLat' => $user->latitude,
                                    'existingLng' => $user->longitude,
                                ])
                            </div>

                            {{-- Certifications & Preferences are managed by Admins/Managers only
                                 (admin/manager Ambassador create + edit). They are intentionally
                                 NOT shown on the ambassador self-service profile so an ambassador
                                 cannot view or change their own certification values. --}}

                            <div class="col-12 mb-3">
                                <div class="h5 text-uppercase mt-4">Liquor license</div>
                                <div class="fixed-border mb-3"></div>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3 form-check">
                                            <input type="hidden" name="has_liquor_license" value="0">
                                            <input type="checkbox" class="form-check-input" id="hasLiquorLicense"
                                                name="has_liquor_license" value="1"
                                                @if (old('has_liquor_license', $liquor?->has_liquor_license ?? false)) checked @endif>
                                            <label class="form-check-label" for="hasLiquorLicense">Has liquor license</label>
                                            @error('has_liquor_license')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="mb-3 form-check">
                                            <input type="hidden" name="document_on_file" value="0">
                                            <input type="checkbox" class="form-check-input"
                                                id="liquorDocumentOnFile" name="document_on_file" value="1"
                                                @if (old('document_on_file', $liquor?->document_on_file ?? false)) checked @endif>
                                            <label class="form-check-label" for="liquorDocumentOnFile">Copy of license on file</label>
                                            @error('document_on_file')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="license_number" class="form-label text-uppercase">License number</label>
                                            <input type="text"
                                                class="form-control shadow-none @error('license_number') is-invalid @enderror"
                                                name="license_number" id="license_number"
                                                value="{{ old('license_number', $liquor?->license_number ?? '') }}"
                                                placeholder="e.g. state license ID">
                                            @error('license_number')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="issuing_state" class="form-label text-uppercase">Issuing state</label>
                                            <input type="text"
                                                class="form-control shadow-none @error('issuing_state') is-invalid @enderror"
                                                name="issuing_state" id="issuing_state"
                                                value="{{ old('issuing_state', $liquor?->issuing_state ?? '') }}"
                                                placeholder="e.g. FL" maxlength="50">
                                            @error('issuing_state')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-xl-6">
                                        <div class="mb-3">
                                            <label for="issued_at" class="form-label text-uppercase">Issued date</label>
                                            <input type="date"
                                                class="form-control shadow-none @error('issued_at') is-invalid @enderror"
                                                name="issued_at" id="issued_at"
                                                value="{{ old('issued_at', optional($liquor?->issued_at)->format('Y-m-d')) }}">
                                            @error('issued_at')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-xl-6">
                                        <div class="mb-3">
                                            <label for="expires_at" class="form-label text-uppercase">Expiration date</label>
                                            <input type="date"
                                                class="form-control shadow-none @error('expires_at') is-invalid @enderror"
                                                name="expires_at" id="expires_at"
                                                value="{{ old('expires_at', optional($liquor?->expires_at)->format('Y-m-d')) }}">
                                            @error('expires_at')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="liquor_notes" class="form-label text-uppercase">Notes</label>
                                            <textarea class="form-control shadow-none @error('notes') is-invalid @enderror"
                                                name="notes" id="liquor_notes" rows="2" placeholder="Optional notes">{{ old('notes', $liquor?->notes ?? '') }}</textarea>
                                            @error('notes')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="col-md-6 col-xl-4 order-xl-2 order-1">
                        <div class="row">
                            <div class="h4 text-uppercase">Profile Picture</div>
                            <div class="col-12">
                                <div class="profile-img">
                                    <img id="preview-image"
                                        src="{{ asset($user->profile_image ?? 'assets/images/profile.jpg') }}"
                                        height="150px" width="150px" style="object-fit: cover; border-radius: 50%;">
                                </div>
                                <div class="content-box-grey p-3 mt-3">
                                    You can change your profile image at any time, just select a file and click save.
                                    Be sure to make your face center of the image for the best results.
                                    <div class="pt-2">
                                        <label for="file-upload" class="profile-file-upload">
                                            Choose File
                                        </label>
                                        <input id="file-upload" type="file" name="profile_image" />
                                        @error('profile_image')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="h4 text-uppercase">Markets</div>
                            <div class="col-12">
                                @php
                                    // Source of truth: the ambassador_market pivot (ambassadors table),
                                    // NOT the legacy users.market_id column (stale / often NULL). This
                                    // mirrors the editable "Market" selector above and matches how event
                                    // matching, recaps, and messaging resolve an ambassador's markets.
                                    $assignedMarkets = $user->ambassadorProfile?->markets ?? collect();
                                @endphp
                                <ul>
                                    @forelse ($assignedMarkets as $market)
                                        <li>{{ $market->name }}</li>
                                    @empty
                                        <li class="text-muted">No markets assigned</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="d-grid mb-3">
                    <div class="col-sm-3">
                        <button type="submit" class="btn btn-design text-uppercase">Save</button>
                        <button type="button" class="btn btn-design grey-color"
                            onclick="window.history.back()">Back</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script>
        function updateAboutAmbassadorHeading() {
            const firstName = document.getElementById('firstName')?.value?.trim();
            const heading = document.getElementById('aboutAmbassadorName');
            if (heading) {
                heading.textContent = firstName || 'Ambassador';
            }
        }

        document.getElementById('firstName')?.addEventListener('input', updateAboutAmbassadorHeading);
        updateAboutAmbassadorHeading();

        document.getElementById('file-upload').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('preview-image');

            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Handle same as legal address checkbox
        document.getElementById('sameAsLegal').addEventListener('change', function() {
            const isChecked = this.checked;
            const mailingFields = [{
                    source: 'legal_address',
                    target: 'mailing_address'
                },
                {
                    source: 'legal_city',
                    target: 'mailing_city'
                },
                {
                    source: 'legal_state',
                    target: 'mailing_state'
                },
                {
                    source: 'legal_zip',
                    target: 'mailing_zip'
                }
            ];

            mailingFields.forEach(field => {
                const source = document.getElementById(field.source);
                const target = document.getElementById(field.target);
                if (isChecked) {
                    target.value = source.value;
                    target.disabled = true;
                } else {
                    target.disabled = false;
                }
            });
        });

        // Initialize mailing fields state based on checkbox
        document.addEventListener('DOMContentLoaded', function() {
            const sameAsLegal = document.getElementById('sameAsLegal');
            if (sameAsLegal.checked) {
                const mailingFields = [{
                        source: 'legal_address',
                        target: 'mailing_address'
                    },
                    {
                        source: 'legal_city',
                        target: 'mailing_city'
                    },
                    {
                        source: 'legal_state',
                        target: 'mailing_state'
                    },
                    {
                        source: 'legal_zip',
                        target: 'mailing_zip'
                    }
                ];

                mailingFields.forEach(field => {
                    const source = document.getElementById(field.source);
                    const target = document.getElementById(field.target);
                    target.value = source.value;
                    target.disabled = true;
                });
            }
        });

        // Sync mailing fields when legal fields change and checkbox is checked
        document.querySelectorAll('#legal_address, #legal_city, #legal_state, #legal_zip').forEach(field => {
            field.addEventListener('change', function() {
                if (document.getElementById('sameAsLegal').checked) {
                    const mapping = {
                        'legal_address': 'mailing_address',
                        'legal_city': 'mailing_city',
                        'legal_state': 'mailing_state',
                        'legal_zip': 'mailing_zip'
                    };
                    const targetId = mapping[this.id];
                    if (targetId) {
                        document.getElementById(targetId).value = this.value;
                    }
                }
            });
        });
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ googleMapsPublicKey() }}&libraries=places"></script>
    <script>
        function initAutocomplete() {
            const legalAddressInput = document.getElementById('legal_address');
            const legalCityInput = document.getElementById('legal_city');
            const legalStateSelect = document.getElementById('legal_state');
            const legalZipInput = document.getElementById('legal_zip');
            const mailingAddressInput = document.getElementById('mailing_address');
            const mailingCityInput = document.getElementById('mailing_city');
            const mailingStateSelect = document.getElementById('mailing_state');
            const mailingZipInput = document.getElementById('mailing_zip');

            const options = {
                types: ['address'],
                componentRestrictions: {
                    country: 'us'
                }
            };

            const legalAutocomplete = new google.maps.places.Autocomplete(legalAddressInput, options);
            const mailingAutocomplete = new google.maps.places.Autocomplete(mailingAddressInput, options);

            function fillAddressFields(input, cityInput, stateSelect, zipInput, place) {
                if (place.geometry) {
                    input.value = place.formatted_address;
                    let city = '';
                    let state = '';
                    let zip = '';

                    for (let i = 0; i < place.address_components.length; i++) {
                        const component = place.address_components[i];
                        const types = component.types;

                        if (types.includes('locality')) {
                            city = component.long_name;
                        } else if (types.includes('administrative_area_level_1')) {
                            state = component.long_name;
                        } else if (types.includes('postal_code')) {
                            zip = component.long_name;
                        }
                    }

                    cityInput.value = city;
                    zipInput.value = zip;

                    let matched = false;
                    for (let i = 0; i < stateSelect.options.length; i++) {
                        const option = stateSelect.options[i];
                        const shortName = option.getAttribute('data-shortName');
                        const fullName = option.getAttribute('data-fullName');

                        if (state.toLowerCase() === fullName.toLowerCase() || state.toLowerCase() === shortName
                            .toLowerCase()) {
                            stateSelect.value = option.value;
                            matched = true;
                            break;
                        }
                    }

                    if (!matched) {
                        stateSelect.value = '';
                    }

                    // If same as legal is checked, update mailing fields
                    if (document.getElementById('sameAsLegal').checked && input === legalAddressInput) {
                        mailingAddressInput.value = legalAddressInput.value;
                        mailingCityInput.value = legalCityInput.value;
                        mailingStateSelect.value = legalStateSelect.value;
                        mailingZipInput.value = legalZipInput.value;
                    }
                }
            }

            legalAutocomplete.addListener('place_changed', function() {
                const place = legalAutocomplete.getPlace();
                fillAddressFields(legalAddressInput, legalCityInput, legalStateSelect, legalZipInput, place);
                if (place.geometry && typeof window.setAmbassadorMapCoordinates === 'function') {
                    window.setAmbassadorMapCoordinates(
                        place.geometry.location.lat(),
                        place.geometry.location.lng()
                    );
                }
            });

            mailingAutocomplete.addListener('place_changed', function() {
                if (!document.getElementById('sameAsLegal').checked) {
                    const place = mailingAutocomplete.getPlace();
                    fillAddressFields(mailingAddressInput, mailingCityInput, mailingStateSelect, mailingZipInput,
                        place);
                }
            });
        }

        google.maps.event.addDomListener(window, 'load', initAutocomplete);
    </script>

@endsection
