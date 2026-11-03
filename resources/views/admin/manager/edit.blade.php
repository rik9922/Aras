@extends('layout.app', ['page_name' => 'Edit Manager'])

@section('content')
    <div class="container py-3">
        <div class="d-flex">
            <div class="h5">Edit Manager</div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <form action="{{route('admin.update.manager')}}" method="POST">@csrf
                <input type="hidden" name="id" value="{{ $manager->id }}">
                <div class="row p-3">
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="firstName" class="form-label text-uppercase">First Name</label>
                            <input type="text" 
                                   class="form-control shadow-none @error('first_name') is-invalid @enderror" 
                                   name="first_name" 
                                   id="firstName" 
                                   value="{{ old('first_name', $manager->first_name) }}">
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
                                   value="{{ old('last_name', $manager->last_name) }}">
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="email" class="form-label text-uppercase">Email</label>
                            <input type="email" 
                                   readonly disabled 
                                   class="form-control shadow-none @error('email') is-invalid @enderror" 
                                   value="{{ $manager->email }}" 
                                   id="email">
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="phone" class="form-label text-uppercase">Phone</label>
                            <input type="text" 
                                   class="form-control shadow-none @error('phone') is-invalid @enderror" 
                                   name="phone" 
                                   id="phone" 
                                   value="{{ old('phone', $manager->phone) }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <input type="hidden" name="latitude" id="lat" value="{{ old('latitude', $manager->latitude) }}"/>
                    <input type="hidden" name="longitude" id="long" value="{{ old('longitude', $manager->longitude) }}"/>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="address" class="form-label text-uppercase">Address</label>
                            <input type="text" 
                                   class="form-control shadow-none @error('address') is-invalid @enderror" 
                                   name="address" 
                                   id="address" 
                                   value="{{ old('address', $manager->address) }}">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    @php
                        $selectedMarketIds = old('market_ids', $manager->managerMarkets->pluck('id')->all());
                    @endphp
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="market_ids" class="form-label text-uppercase">Markets they can view</label>
                            <select
                                class="form-select shadow-none multiple-select2 @error('market_ids') is-invalid @enderror"
                                id="market_ids" name="market_ids[]" multiple="multiple"
                                data-placeholder="Select one or more markets">
                                @foreach ($markets as $market)
                                    <option value="{{ $market->id }}"
                                        {{ in_array((int) $market->id, array_map('intval', (array) $selectedMarketIds), true) ? 'selected' : '' }}>
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
                                       {{ old('status', $manager->status) == '1' ? 'checked' : '' }}>
                                <label class="form-check-label text-uppercase" for="active">Active</label>
                            </div>

                            <div class="form-check m-2">
                                <input class="form-check-input shadow-none" 
                                       name="status" 
                                       type="radio" 
                                       value="0" 
                                       id="inactive" 
                                       {{ old('status', $manager->status) == '0' ? 'checked' : '' }}>
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
                // Extract latitude and longitude
                document.getElementById('lat').value = place.geometry.location.lat();
                document.getElementById('long').value = place.geometry.location.lng();
                input.value = place.formatted_address;
            } else {
                console.log('No details available for input:', input.value);
            }
        });
    }
    google.maps.event.addDomListener(window, 'load', initAutocomplete);
</script>
