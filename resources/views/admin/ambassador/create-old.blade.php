@extends('layout.app', ['page_name' => 'Create Ambassador'])
<link rel="stylesheet" href="{{asset('assets/select2/select2.css')}}">
@section('content')
    <div class="container py-3">
        <div class="d-flex">
            <div class="h5">Ambassador Details</div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <form action="{{route('admin.store.ambassador')}}" method="POST">@csrf
                <div class="row p-3">
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="firstName" class="form-label text-uppercase">First Name</label>
                            <input type="text" class="form-control shadow-none @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" name="first_name" id="firstName">
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="lastName" class="form-label text-uppercase">Last Name</label>
                            <input type="text" class="form-control shadow-none @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" name="last_name" id="lastName">
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="email" class="form-label text-uppercase">Email</label>
                            <input type="email" class="form-control shadow-none @error('email') is-invalid @enderror" value="{{ old('email') }}" name="email" id="email">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="password" class="form-label text-uppercase">Password(Leave Blank to remain
                                unchanged)</label>
                            <input type="password" class="form-control shadow-none  @error('password') is-invalid @enderror" name="password" id="password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="nickname" class="form-label text-uppercase">Nickname</label>
                            <input type="text" class="form-control shadow-none @error('nickname') is-invalid @enderror" value="{{ old('nickname') }}" name="nickname" id="nickname">
                            @error('nickname')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="dob" class="form-label text-uppercase">DOB</label>
                            <input type="date" class="form-control shadow-none @error('dob') is-invalid @enderror" value="{{ old('dob') }}" name="dob" id="dob">
                            @error('dob')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="venmo" class="form-label text-uppercase">Venmo</label>
                            <input type="text" class="form-control shadow-none @error('venmo') is-invalid @enderror" value="{{ old('venmo') }}" name="venmo" id="venmo">
                            @error('venmo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="region" class="form-label">Region</label>
                            <div class="d-flex gap-4">
                                <select class="form-select shadow-none @error('region') is-invalid @enderror" id="region" name="region" id="region">
                                    <option value="" selected>Select Region</option>
                                    <option value="Southeast">Southeast</option>
                                    <option value="Midwest">Midwest</option>
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
                            <input type="text" class="form-control shadow-none @error('phone') is-invalid @enderror" value="{{ old('phone') }}" name="phone" id="phone">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#mapModal">
                        <i class="fa fa-map-marker" aria-hidden="true"></i>
                        <label class="form-label text-uppercase ms-2">Choose the location on map</label>
                    </div>
                    
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="city" class="form-label text-uppercase">City</label>
                            <input type="text" class="form-control shadow-none @error('city') is-invalid @enderror" value="{{ old('city') }}" name="city" id="city">
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="state" class="form-label text-uppercase">State</label>
                            <input type="state" class="form-control shadow-none @error('state') is-invalid @enderror" value="{{ old('state') }}" name="state" id="state">
                            @error('state')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    
                    <input type="hidden" name="latitude" id="lat"/>
                    <input type="hidden" name="longitude" id="long"/>
                    
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="address" class="form-label text-uppercase">Address</label>
                            <input type="text" class="form-control shadow-none @error('address') is-invalid @enderror" value="{{ old('address') }}" name="address" id="address">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>


                    <div class="col-6">
                        <div class="mb-3">
                            <label for="hired" class="form-label text-uppercase">Hired</label>
                            <input type="date" class="form-control shadow-none @error('hired') is-invalid @enderror" value="{{ old('hired') }}" name="hired" id="hired">
                            @error('hired')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="wage" class="form-label text-uppercase">Wage</label>
                            <input type="text" class="form-control shadow-none @error('wage') is-invalid @enderror" value="{{ old('wage') }}" name="wage" id="wage">
                            @error('wage')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="market_id" class="form-label text-uppercase">Markets</label>
                            <div class="input-group mb-3">
                                <select class="form-select shadow-none multiple-select2 @error('market_id') is-invalid @enderror" id="market_id" name="market_id[]" multiple="multiple" data-placeholder="Select Markets">
                                    @if(!empty($markets))
                                        @foreach ($markets as $market)
                                            <option value="{{$market->id}}">{{$market->name}}</option>
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
                                <select class="form-select shadow-none @error('shirt_size') is-invalid @enderror" id="shirt_size" name="shirt_size">
                                    <option value="">Select Shirt Size</option>
                                    @foreach (getShirtSize() as $key => $size)
                                        <option value="{{ $key }}">
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
                                <input class="form-check-input shadow-none" name="status" type="radio" value="1" id="active" checked>
                                <label class="form-check-label text-uppercase" for="active">
                                    Active
                                </label>
                            </div>
                            
                            <div class="form-check m-2">
                                <input class="form-check-input shadow-none" name="status" type="radio" value="0" id="inactive">
                                <label class="form-check-label text-uppercase" for="inactive">
                                    Inactive
                                </label>
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
    
    <div class="modal fade" id="mapModal" tabindex="-1" aria-labelledby="mapModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="map" style="height: 500px; width: 100%;"></div>
                </div>
                <div class="row">
                    <div class="col text-end mb-3">
                        <button id="closeMap" class="btn-ds btn-primary-ds mx-5">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    
    <script src="{{asset('assets/js/jquery-3.6.0.min.js')}}"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ googleMapsPublicKey() }}&libraries=places"></script>
    <script>

        $(document).ready(function () {
            let map, marker, geocoder;
    
            // Function to initialize the map
            function initMap() {
                geocoder = new google.maps.Geocoder();
    
                // Default fallback location (New York if geolocation fails)
                const fallbackLocation = { lat: 40.7128, lng: -74.0060 };
                loadMap(fallbackLocation);
            }
    
            // Function to load the map with the given location
            function loadMap(location) {
                map = new google.maps.Map(document.getElementById("map"), {
                    zoom: 14,
                    center: location,
                });
    
                marker = new google.maps.Marker({
                    position: location,
                    map: map,
                    draggable: true,
                });
    
                marker.addListener("dragend", function () {
                    const pos = marker.getPosition();
                    getAddressFromLatLng(pos.lat(), pos.lng());
                });
            }
    
            // Function to reverse geocode lat/lng to address
            function getAddressFromLatLng(lat, lng) {
                const latlng = { lat: parseFloat(lat), lng: parseFloat(lng) };
    
                geocoder.geocode({ location: latlng }, function (results, status) {
                    if (status === "OK" && results[0]) {
                        const result = results[0];
                        const addressComponents = result.address_components;
    
                        // Auto-fill the address fields
                        $('#address').val(result.formatted_address);
                        $('#lat').val(lat);
                        $('#long').val(lng);
    
                        // Fill city, state, and zip code
                        addressComponents.forEach(comp => {
                            if (comp.types.includes("locality")) {
                                $('#city').val(comp.long_name);
                            }
                            if (comp.types.includes("administrative_area_level_1")) {
                                $('#state').val(comp.long_name);
                            }
                        });
                    }
                });
            }
    
            // Initialize map when modal is shown
            $('#mapModal').on('shown.bs.modal', function () {
                // Initialize or resize the map when the modal is opened
                google.maps.event.trigger(map, "resize");
                map.setCenter(marker.getPosition()); // Ensure map is centered around the marker
            });
    
            // Open the modal and trigger the map initialization
            $('#mapModal').on('show.bs.modal', function () {
                initMap(); // Initialize the map
            });
    
            // Close modal and update the form when save button is clicked
            $('#closeMap').click(function () {
                $('#mapModal').modal('hide');
            });
        });
    </script>
@endsection
