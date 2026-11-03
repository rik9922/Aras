@extends('layout.app', ['page_name' => 'Edit Location'])

@section('content')
    <div class="container py-3">
        <div class="d-flex">
            <div class="h5">Edit Location</div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <form action="{{route('admin.update.location')}}" method="POST">@csrf
                <input type="hidden" name="id" value="{{$location->id}}">
                <div class="row p-3">
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="firstName" class="form-label text-uppercase">First Name</label>
                            <input type="text" class="form-control shadow-none @error('first_name') is-invalid @enderror" value="{{$location->first_name}}" name="first_name" id="firstName">
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="lastName" class="form-label text-uppercase">Last Name</label>
                            <input type="text" class="form-control shadow-none @error('last_name') is-invalid @enderror" value="{{$location->last_name}}" name="last_name" id="lastName">
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="email" class="form-label text-uppercase">Email</label>
                            <input type="email" readonly disabled class="form-control shadow-none @error('email') is-invalid @enderror" value="{{$location->email}}" id="email">

                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="phone" class="form-label text-uppercase">Phone</label>
                            <input type="text" class="form-control shadow-none @error('phone') is-invalid @enderror" value="{{$location->phone}}" name="phone" id="phone">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="venue_name" class="form-label text-uppercase">Venue/Store Name</label>
                            <input type="text" class="form-control shadow-none @error('venue_name') is-invalid @enderror" value="{{$location->venue_name}}" name="venue_name" id="venue_name">
                            @error('venue_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="venue_type" class="form-label text-uppercase">Venue/Store Type</label>
                            <select class="form-select shadow-none  @error('state_id') is-invalid @enderror" id="venue_type" name="venue_type">
                                @if(!empty($venues))
                                    @foreach ($venues as $venue)
                                        <option @if($location->venue_type == $venue->id) selected @endif value="{{$venue->id}}">{{$venue->name}}</option>
                                    @endforeach
                                @endif
                            </select>
                            @error('venue_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="event_location_id" class="form-label text-uppercase">Event Location</label>
                            <select class="form-select shadow-none @error('event_location_id') is-invalid @enderror" id="event_location_id" name="event_location_id">
                                @if(!empty($eventLocation))
                                    @foreach ($eventLocation as $locations)
                                        <option @if($location->event_location_id == $locations->id) selected @endif value="{{ $locations->id }}">
                                            {{ $locations->address . ' ' . $locations->city . ' ' . $locations->state }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('event_location_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    
                    <input type="hidden" name="latitude" id="lat" value="{{ $location->latitude }}"/>
                    <input type="hidden" name="longitude" id="long" value="{{ $location->longitude }}"/>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="address" class="form-label text-uppercase">User Address</label>
                            <input type="text" class="form-control shadow-none @error('address') is-invalid @enderror" value="{{$location->address}}" name="address" id="address">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6 mb-3">
                        <div class="d-flex flex-wrap align-items-center">
                            <div class="form-check m-2">
                                <input class="form-check-input shadow-none" name="status" type="radio" value="1" @if($location->status=='1') checked @endif id="active" >
                                <label class="form-check-label text-uppercase" for="active">
                                    Active
                                </label>
                            </div>
                            
                            <div class="form-check m-2">
                                <input class="form-check-input shadow-none" name="status" type="radio" value="0" @if($location->status=='0') checked @endif id="inactive">
                                <label class="form-check-label text-uppercase" for="inactive">
                                    Inactive
                                </label>
                            </div>
                        
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="city" class="form-label text-uppercase">City</label>
                            <input type="text" class="form-control shadow-none @error('city') is-invalid @enderror" value="{{$location->city}}" name="city" id="city">
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="state" class="form-label text-uppercase">State</label>
                            <input type="text" class="form-control shadow-none @error('state') is-invalid @enderror" value="{{$location->state}}" name="state" id="state">
                            @error('state')
                                <div class="invalid-feedback">{{ $message }}</div>
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
                // Set the address in the input field
                input.value = place.formatted_address;

                // Extract latitude and longitude
                document.getElementById('lat').value = place.geometry.location.lat();
                document.getElementById('long').value = place.geometry.location.lng();

                // Initialize variables for city and state
                let city = '';
                let state = '';

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
                }

                console.log("City: ", city);
                console.log("State: ", state);

                // Set city and state in respective fields
                document.getElementById('city').value = city;
                document.getElementById('state').value = state;
            } else {
                console.log('No details available for input:', input.value);
            }
        });
    }

    google.maps.event.addDomListener(window, 'load', initAutocomplete);
</script>
@endsection
