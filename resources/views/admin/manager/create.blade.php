@extends('layout.app', ['page_name' => 'Create Manager'])

@section('content')
    <div class="container py-3">
        <div class="d-flex">
            <div class="h5">Manager Details</div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <form action="{{route('admin.store.manager')}}" method="POST" autocomplete="off">@csrf
                <div class="row p-3">
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="firstName" class="form-label text-uppercase">First Name</label>
                            <input type="text" 
                                   class="form-control shadow-none @error('first_name') is-invalid @enderror" 
                                   name="first_name" 
                                   id="firstName" 
                                   value="{{ old('first_name') }}">
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="lastName" class="form-label text-uppercase">Last Name</label>
                            <input type="text" 
                                   class="form-control shadow-none @error('last_name') is-invalid @enderror" 
                                   name="last_name" 
                                   id="lastName" 
                                   value="{{ old('last_name') }}">
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="email" class="form-label text-uppercase">Email</label>
                            <input type="email" 
                                   class="form-control shadow-none @error('email') is-invalid @enderror" 
                                   name="email" 
                                   id="email" 
                                   autocomplete="off"
                                   value="{{ old('email') }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="password" class="form-label text-uppercase">Password</label>
                            <input type="password" 
                                   class="form-control shadow-none  @error('password') is-invalid @enderror" 
                                   name="password" 
                                   autocomplete="new-password"
                                   id="password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="phone" class="form-label text-uppercase">Phone</label>
                            <input type="text" 
                                   class="form-control shadow-none @error('phone') is-invalid @enderror" 
                                   name="phone" 
                                   id="phone" 
                                   value="{{ old('phone') }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="address" class="form-label text-uppercase">Address</label>
                            <input type="text" 
                                   class="form-control shadow-none @error('address') is-invalid @enderror" 
                                   name="address" 
                                   id="address" 
                                   value="{{ old('address') }}">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <input type="hidden" name="latitude" id="lat" value="{{ old('latitude') }}"/>
                    <input type="hidden" name="longitude" id="long" value="{{ old('longitude') }}"/>

                    <div class="col-12">
                        <div class="mb-3">
                            <label for="market_ids" class="form-label text-uppercase">Markets they can view</label>
                            <select
                                class="form-select shadow-none multiple-select2 @error('market_ids') is-invalid @enderror"
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

                    <div class="col-6 mt-3">
                        <div class="d-flex flex-wrap align-items-center">
                            <div class="form-check m-2">
                                <input class="form-check-input shadow-none" 
                                       name="status" 
                                       type="radio" 
                                       value="1" 
                                       id="active" 
                                       {{ old('status', '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label text-uppercase" for="active">Active</label>
                            </div>

                            <div class="form-check m-2">
                                <input class="form-check-input shadow-none" 
                                       name="status" 
                                       type="radio" 
                                       value="0" 
                                       id="inactive" 
                                       {{ old('status') == '0' ? 'checked' : '' }}>
                                <label class="form-check-label text-uppercase" for="inactive">Inactive</label>
                            </div>
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
@endsection

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
                // Extract latitude and longitude
                document.getElementById('lat').value = place.geometry.location.lat();
                document.getElementById('long').value = place.geometry.location.lng();
            } else {
                console.log('No details available for input:', input.value);
            }
        });
    }

    google.maps.event.addDomListener(window, 'load', initAutocomplete);
</script>
