@extends('layout.app', ['page_name' => 'Edit Market'])

@php
    $routePrefix = (int) (auth()->user()->role_id ?? 0) === 6 ? 'manager' : 'admin';
    $selectedEventTypeIds = old('event_type_ids', $market->eventTypes->pluck('id')->toArray());
@endphp

@section('content')
    <div class="container py-3">
        <div class="d-flex">
            <div class="h5">Edit Market</div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <form action="{{ route($routePrefix.'.update.market') }}" method="POST">@csrf
                <input type="hidden" name="id" value="{{ $market->id }}">
                <input type="hidden" name="latitude" value="{{ $market->latitude }}" id="latitude">
                <input type="hidden" name="longitude" value="{{ $market->longitude }}" id="longitude">
                <div class="row p-3">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="firstName" class="form-label text-uppercase">Market Name</label>
                            <input type="text" class="form-control shadow-none @error('name') is-invalid @enderror" value="{{ old('name', $market->name) }}" name="name" id="firstName">
                            <small class="text-muted">Changing the name does not dissociate events already tied to this market.</small>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label for="Description" class="form-label text-uppercase">Description</label>
                            <textarea type="text" class="form-control shadow-none @error('description') is-invalid @enderror" name="description"
                                id="Description" placeholder="Description">{{ old('description', $market->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label for="location" class="form-label text-uppercase">Location</label>
                            <input type="text" class="form-control shadow-none @error('location') is-invalid @enderror" value="{{ old('location', $market->location) }}" name="location" id="location">
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="state" class="form-label text-uppercase">State</label>
                            <input type="text" class="form-control shadow-none @error('state') is-invalid @enderror" name="state" id="state" value="{{ old('state', $market->state) }}" placeholder="Auto-filled from location">
                            @error('state')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="region" class="form-label text-uppercase">Region</label>
                            <select class="form-select shadow-none @error('region') is-invalid @enderror" name="region" id="region">
                                <option value="">Select Region</option>
                                <option value="Southeast" @selected(old('region', $market->region) === 'Southeast')>Southeast</option>
                                <option value="Midwest" @selected(old('region', $market->region) === 'Midwest')>Midwest</option>
                            </select>
                            @error('region')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label text-uppercase d-block">Event Type</label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach ($eventTypes as $type)
                                    <div class="form-check">
                                        <input class="form-check-input shadow-none" type="radio"
                                            name="event_type_ids[]" value="{{ $type->id }}"
                                            id="event_type_{{ $type->id }}"
                                            @checked(in_array($type->id, $selectedEventTypeIds))>
                                        <label class="form-check-label" for="event_type_{{ $type->id }}">{{ $type->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                            @error('event_type_ids')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label text-uppercase d-block">Status</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input shadow-none" name="status" type="radio" value="active"
                                    id="market-status-active" @checked(old('status', $market->status) === 'active')>
                                <label class="form-check-label" for="market-status-active">Active</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input shadow-none" name="status" type="radio" value="inactive"
                                    id="market-status-inactive" @checked(old('status', $market->status) === 'inactive')>
                                <label class="form-check-label" for="market-status-inactive">Inactive</label>
                            </div>
                            @error('status')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>
                <div class="col-2 gap-2">
                    <button type="submit" class="btn-ds btn-primary-ds">Save</button>
                    <button type="button" class="btn-ds btn-secondary-ds" onclick="window.history.back()">Back</button>
                </div>
            </form>
        </div>
    </div>
@endsection
<script src="https://maps.googleapis.com/maps/api/js?key={{ googleMapsPublicKey() }}&libraries=places"></script>
<script>
    const stateRegionMap = {
        'Alabama': 'Southeast', 'Arkansas': 'Southeast', 'Florida': 'Southeast',
        'Georgia': 'Southeast', 'Kentucky': 'Southeast', 'Louisiana': 'Southeast',
        'Mississippi': 'Southeast', 'North Carolina': 'Southeast', 'South Carolina': 'Southeast',
        'Tennessee': 'Southeast', 'Virginia': 'Southeast', 'West Virginia': 'Southeast',
        'Illinois': 'Midwest', 'Indiana': 'Midwest', 'Iowa': 'Midwest',
        'Kansas': 'Midwest', 'Michigan': 'Midwest', 'Minnesota': 'Midwest',
        'Missouri': 'Midwest', 'Nebraska': 'Midwest', 'North Dakota': 'Midwest',
        'Ohio': 'Midwest', 'South Dakota': 'Midwest', 'Wisconsin': 'Midwest',
    };

    function applyStateRegion(stateName) {
        document.getElementById('state').value = stateName;
        const region = stateRegionMap[stateName] || '';
        if (region) {
            document.getElementById('region').value = region;
        }
    }

    // Auto-populate region from existing state on page load
    document.addEventListener('DOMContentLoaded', function () {
        const stateVal = document.getElementById('state').value.trim();
        const regionVal = document.getElementById('region').value;
        if (stateVal && !regionVal && stateRegionMap[stateVal]) {
            document.getElementById('region').value = stateRegionMap[stateVal];
        }
    });

    function initAutocomplete() {
        const input = document.getElementById('location');
        const autocomplete = new google.maps.places.Autocomplete(input, {
            types: ['geocode'],
            componentRestrictions: { country: 'us' }
        });

        autocomplete.addListener('place_changed', function () {
            const place = autocomplete.getPlace();
            if (!place.geometry) return;

            input.value = place.formatted_address;
            document.getElementById('latitude').value = place.geometry.location.lat();
            document.getElementById('longitude').value = place.geometry.location.lng();

            const stateComponent = place.address_components.find(c => c.types.includes('administrative_area_level_1'));
            if (stateComponent) {
                applyStateRegion(stateComponent.long_name);
            }
        });
    }

    google.maps.event.addDomListener(window, 'load', initAutocomplete);
</script>
