@extends('layout.app', ['page_name' => 'Create POS'])

@section('content')
    <div class="container py-3">
        <div class="d-flex">
            <div class="h5">POS User Details</div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <form action="{{ route('admin.store.pos.user') }}" method="POST">@csrf
                <div class="row p-3">
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="firstName" class="form-label text-uppercase">First Name</label>
                            <input type="text" class="form-control shadow-none @error('first_name') is-invalid @enderror"
                                   name="first_name" id="firstName" value="{{ old('first_name') }}">
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="lastName" class="form-label text-uppercase">Last Name</label>
                            <input type="text" class="form-control shadow-none @error('last_name') is-invalid @enderror"
                                   name="last_name" id="lastName" value="{{ old('last_name') }}">
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="email" class="form-label text-uppercase">Email</label>
                            <input type="email" class="form-control shadow-none @error('email') is-invalid @enderror"
                                   name="email" id="email" value="{{ old('email') }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="password" class="form-label text-uppercase">Password</label>
                            <input type="password" class="form-control shadow-none @error('password') is-invalid @enderror"
                                   name="password" id="password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="phone" class="form-label text-uppercase">Phone</label>
                            <input type="text" class="form-control shadow-none @error('phone') is-invalid @enderror"
                                   name="phone" id="phone" value="{{ old('phone') }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="address" class="form-label text-uppercase">Address</label>
                            <input type="text" class="form-control shadow-none @error('address') is-invalid @enderror"
                                   name="address" id="address" value="{{ old('address') }}">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="city" class="form-label text-uppercase">City</label>
                            <input type="text" class="form-control shadow-none @error('city') is-invalid @enderror"
                                   name="city" id="city" value="{{ old('city') }}" readonly>
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <input type="hidden" name="latitude" id="lat" value="{{ old('latitude') }}"/>
                    <input type="hidden" name="longitude" id="long" value="{{ old('longitude') }}"/>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="state" class="form-label text-uppercase">State</label>
                            <input type="text" class="form-control shadow-none @error('state') is-invalid @enderror"
                                   name="state" id="state" value="{{ old('state') }}" readonly>
                            @error('state')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label for="market_ids" class="form-label text-uppercase">Markets they can view</label>
                            <select class="form-select shadow-none multiple-select2 @error('market_ids') is-invalid @enderror"
                                    id="market_ids" name="market_ids[]" multiple="multiple"
                                    data-placeholder="Select one or more markets">
                                @foreach ($markets as $market)
                                    <option value="{{ $market->id }}"
                                        {{ is_array(old('market_ids')) && in_array((string) $market->id, old('market_ids', []), true) ? 'selected' : '' }}>
                                        {{ $market->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('market_ids')
                                <div class="text-danger w-100 small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

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
                input.value = place.formatted_address;

                document.getElementById('lat').value = place.geometry.location.lat();
                document.getElementById('long').value = place.geometry.location.lng();

                let city = '';
                let state = '';

                place.address_components.forEach(component => {
                    if (component.types.includes('locality')) {
                        city = component.long_name;
                    } else if (component.types.includes('sublocality_level_1')) {
                        city = component.long_name;
                    }
                    if (component.types.includes('administrative_area_level_1')) {
                        state = component.long_name;
                    }
                });

                document.getElementById('city').value = city;
                document.getElementById('state').value = state;
            }
        });
    }

    google.maps.event.addDomListener(window, 'load', initAutocomplete);
</script>
@endsection
