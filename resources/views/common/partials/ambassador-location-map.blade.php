@php
    $mapLat = old('latitude', $existingLat ?? null);
    $mapLng = old('longitude', $existingLng ?? null);
    $addressInputId = $addressInputId ?? 'legal_address';
    $cityInputId = $cityInputId ?? 'legal_city';
    $stateInputId = $stateInputId ?? 'legal_state';
    $zipInputId = $zipInputId ?? 'legal_zip';
@endphp

<div class="col-12" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#ambassadorMapModal">
    <i class="fa fa-map-marker" aria-hidden="true"></i>
    <label class="form-label text-uppercase ms-2">Choose your location on map</label>
    @error('latitude')
        <div class="text-danger w-100">{{ $message }}</div>
    @enderror
    @error('longitude')
        <div class="text-danger w-100">{{ $message }}</div>
    @enderror
</div>
<input type="hidden" name="latitude" id="lat" value="{{ $mapLat }}" />
<input type="hidden" name="longitude" id="long" value="{{ $mapLng }}" />

<div class="modal fade" id="ambassadorMapModal" tabindex="-1" aria-labelledby="ambassadorMapModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Your Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="ambassador-location-map" style="height: 500px; width: 100%;"></div>
            </div>
            <div class="row">
                <div class="col text-end mb-3">
                    <button type="button" id="closeAmbassadorMap" class="btn btn-primary mx-5">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function() {
            const fallbackLocation = {
                lat: 40.7128,
                lng: -74.0060
            };
            const addressInputId = @json($addressInputId);
            const cityInputId = @json($cityInputId);
            const stateInputId = @json($stateInputId);
            const zipInputId = @json($zipInputId);

            let map, marker, geocoder;

            function getExistingCoordinates() {
                const lat = parseFloat(document.getElementById('lat').value);
                const lng = parseFloat(document.getElementById('long').value);
                if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                    return {
                        lat,
                        lng
                    };
                }
                return null;
            }

            function buildAddressQuery() {
                const parts = [];
                const address = document.getElementById(addressInputId)?.value?.trim();
                const city = document.getElementById(cityInputId)?.value?.trim();
                const stateSelect = document.getElementById(stateInputId);
                const stateName = stateSelect?.options[stateSelect.selectedIndex]?.getAttribute('data-fullName') || '';
                const zip = document.getElementById(zipInputId)?.value?.trim();

                if (address) parts.push(address);
                if (city) parts.push(city);
                if (stateName) parts.push(stateName);
                if (zip) parts.push(zip);

                if (parts.length === 0 && zip) {
                    return zip + ', USA';
                }

                return parts.length ? parts.join(', ') + ', USA' : '';
            }

            function setCoordinates(lat, lng) {
                document.getElementById('lat').value = lat;
                document.getElementById('long').value = lng;
            }

            function loadMap(location) {
                map = new google.maps.Map(document.getElementById('ambassador-location-map'), {
                    zoom: 14,
                    center: location,
                });

                marker = new google.maps.Marker({
                    position: location,
                    map: map,
                    draggable: true,
                });

                marker.addListener('dragend', function() {
                    const pos = marker.getPosition();
                    setCoordinates(pos.lat(), pos.lng());
                });
            }

            function centerMapOnLocation(location) {
                if (!map || !marker) {
                    loadMap(location);
                    return;
                }

                marker.setPosition(location);
                map.setCenter(location);
                setCoordinates(location.lat, location.lng);
            }

            function geocodeAddress(callback) {
                const address = buildAddressQuery();
                if (!address) {
                    callback(null);
                    return;
                }

                geocoder.geocode({
                    address
                }, function(results, status) {
                    if (status === 'OK' && results[0]) {
                        const loc = results[0].geometry.location;
                        callback({
                            lat: loc.lat(),
                            lng: loc.lng()
                        });
                    } else {
                        callback(null);
                    }
                });
            }

            function resolveInitialLocation(callback) {
                const existing = getExistingCoordinates();
                if (existing) {
                    callback(existing);
                    return;
                }

                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            callback({
                                lat: position.coords.latitude,
                                lng: position.coords.longitude
                            });
                        },
                        function() {
                            geocodeAddress(function(location) {
                                callback(location || fallbackLocation);
                            });
                        }, {
                            timeout: 8000,
                            maximumAge: 60000
                        }
                    );
                    return;
                }

                geocodeAddress(function(location) {
                    callback(location || fallbackLocation);
                });
            }

            function initMap() {
                geocoder = new google.maps.Geocoder();
                resolveInitialLocation(function(location) {
                    loadMap(location);
                    setCoordinates(location.lat, location.lng);
                });
            }

            function refreshMapFromAddress() {
                if (!geocoder) {
                    return;
                }

                geocodeAddress(function(location) {
                    if (location) {
                        centerMapOnLocation(location);
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                const modal = document.getElementById('ambassadorMapModal');
                const closeButton = document.getElementById('closeAmbassadorMap');

                if (!modal) {
                    return;
                }

                modal.addEventListener('show.bs.modal', initMap);

                modal.addEventListener('shown.bs.modal', function() {
                    if (map && marker) {
                        google.maps.event.trigger(map, 'resize');
                        map.setCenter(marker.getPosition());
                    }
                });

                closeButton?.addEventListener('click', function() {
                    bootstrap.Modal.getOrCreateInstance(modal).hide();
                });

                [addressInputId, cityInputId, stateInputId, zipInputId].forEach(function(fieldId) {
                    const field = document.getElementById(fieldId);
                    if (!field) {
                        return;
                    }

                    field.addEventListener('change', function() {
                        if (modal.classList.contains('show')) {
                            refreshMapFromAddress();
                        }
                    });
                });
            });

            window.setAmbassadorMapCoordinates = function(lat, lng) {
                setCoordinates(lat, lng);
            };
        })();
    </script>
@endpush
