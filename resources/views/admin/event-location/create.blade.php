@extends('layout.app', ['page_name' => 'Create Location'])

@section('content')
    <div class="container py-3">
        <div class="d-flex">
            <div class="h5">Event Location Details</div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <form action="{{route('admin.store.event.location')}}" method="POST">@csrf
                <div class="row p-3">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="city" class="form-label text-uppercase">Location Name</label>
                            <input type="text" class="form-control shadow-none @error('name') is-invalid @enderror" name="name" id="name" >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="city" class="form-label text-uppercase">Store Number</label>
                            {{-- <input type="text" class="form-control shadow-none @error('store_number') is-invalid @enderror" name="store_number" id="store_number" > --}}
                            <input type="text" class="form-control shadow-none @error('store_number') is-invalid @enderror" name="store_number" id="store_number" placeholder="(optional)">
                            @error('store_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="city" class="form-label text-uppercase">Phone Number</label>
                            <input type="tel" class="form-control shadow-none @error('phone') is-invalid @enderror" name="phone" id="phone" >
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="city" class="form-label text-uppercase">Location Payment Type</label>
                            {{-- <input type="text" class="form-control shadow-none @error('location_payment_type') is-invalid @enderror" name="location_payment_type" id="location_payment_type" > --}}
                            <select class="form-select shadow-none @error('location_payment_type') is-invalid @enderror"
                                name="location_payment_type" id="location_payment_type">
                                <option value="" selected>Select Payment Type</option>
                                <option value="Check" {{ old('location_payment_type') === 'Check' ? 'selected' : '' }}>Check</option>
                                <option value="ComData" {{ old('location_payment_type') === 'ComData' ? 'selected' : '' }}>ComData</option>
                                <option value="ComData - Gas" {{ old('location_payment_type') === 'ComData - Gas' ? 'selected' : '' }}>ComData - Gas</option>
                                <option value="Gift Card" {{ old('location_payment_type') === 'Gift Card' ? 'selected' : '' }}>Gift Card</option>
                                <option value="No Payment Needed" {{ old('location_payment_type') === 'No Payment Needed' ? 'selected' : '' }}>No Payment Needed</option>
                                <option value="Product Provided" {{ old('location_payment_type') === 'Product Provided' ? 'selected' : '' }}>Product Provided</option>
                                <option value="Reimbursement" {{ old('location_payment_type') === 'Reimbursement' ? 'selected' : '' }}>Reimbursement</option>
                            </select>
                            @error('location_payment_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="address" class="form-label text-uppercase">Address</label>
                            <input type="text" class="form-control shadow-none @error('address') is-invalid @enderror" name="address" id="address">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="city" class="form-label text-uppercase">City</label>
                            <input type="text" class="form-control shadow-none @error('city') is-invalid @enderror" name="city" id="city" readonly>
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <input type="hidden" name="latitude" id="lat"/>
                    <input type="hidden" name="longitude" id="long"/>
                    
                     <div class="col-12">
                        <div class="mb-3">
                            <label for="state" class="form-label text-uppercase">State</label>
                            <input type="text" class="form-control shadow-none @error('state') is-invalid @enderror" name="state" id="state" readonly>
                            @error('state')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label for="zip_code" class="form-label text-uppercase">Zip Code</label>
                            <input type="text" class="form-control shadow-none @error('zip_code') is-invalid @enderror" name="zip_code" id="zip_code" inputmode="numeric" autocomplete="postal-code" maxlength="6" pattern="[0-9]{5,6}" title="Enter 5 to 6 digits only" oninput="this.value = this.value.replace(/\D/g,'').slice(0,6)">
                            @error('zip_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    @include('admin.event-location.partials.timezone-field', ['location' => new \App\Models\EventLocation()])
                    
                </div>
                <div class="col-2">
                    <button type="submit" class="btn-ds btn-primary-ds">Save</button>
                    <button type="button" class="btn-ds btn-secondary-ds" onclick="window.history.back()">Back</button>
                </div>
            </form>
        </div>
    </div>
    
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ googleMapsPublicKey() }}&libraries=places"></script>
<script>
    function initAutocomplete() {
        const input = document.getElementById('address');
        const options = {
            types: ['geocode'],
            componentRestrictions: { country: 'us' }
        };

        const autocomplete = new google.maps.places.Autocomplete(input, options);

        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();

            if (place.geometry) {
                // Set the address in the input field
                input.value = place.formatted_address;

                // Extract latitude and longitude
                document.getElementById('lat').value = place.geometry.location.lat();
                document.getElementById('long').value = place.geometry.location.lng();

                // Initialize variables for city, state, and zip
                let city = '';
                let state = '';
                let postal = '';

                // Loop through the address components and extract city and state
                for (let i = 0; i < place.address_components.length; i++) {
                    const component = place.address_components[i];

                    // Check for city (locality, sublocality)
                    if (component.types.includes('locality')) {
                        city = component.long_name;
                    } else if (component.types.includes('sublocality_level_1')) {
                        city = component.long_name; // Some addresses may have the city here
                    }

                    // Check for state (administrative_area_level_1)
                    if (component.types.includes('administrative_area_level_1')) {
                        state = component.long_name;
                    }

                    if (component.types.includes('postal_code')) {
                        postal = component.long_name;
                    }
                }

                console.log("City: ", city);
                console.log("State: ", state);

                // Set city and state in respective fields
                document.getElementById('city').value = city;
                document.getElementById('state').value = state;

                let zipDigits = (postal || '').replace(/\D/g, '');
                if (zipDigits.length >= 9) {
                    zipDigits = zipDigits.substring(0, 5);
                } else if (zipDigits.length > 6) {
                    zipDigits = zipDigits.substring(0, 6);
                }
                if (zipDigits.length >= 5 && zipDigits.length <= 6) {
                    document.getElementById('zip_code').value = zipDigits;
                }

                document.getElementById('lat').dispatchEvent(new Event('change'));
            } else {
                console.log('No details available for input:', input.value);
            }
        });
    }

    google.maps.event.addDomListener(window, 'load', initAutocomplete);
</script>
@include('admin.event-location.partials.venue-timezone-script')
@endsection