<script>
    function extractCityFromPlace(place) {
        const components = place.address_components || [];
        const find = (type) => components.find((c) => c.types.includes(type));
        const cityComponent = find('locality')
            || find('postal_town')
            || find('sublocality')
            || find('sublocality_level_1')
            || find('administrative_area_level_3')
            || find('administrative_area_level_2');

        return cityComponent ? cityComponent.long_name : '';
    }

    function applyResolvedTimezone(resolved) {
        if (!resolved) {
            return;
        }
        const ianaInput = document.getElementById('timezone');
        const labelInput = document.getElementById('timezone_label');
        const displayInput = document.getElementById('timezone_display');
        if (ianaInput) {
            ianaInput.value = resolved.timezone || '';
        }
        if (labelInput) {
            labelInput.value = resolved.timezone_label || '';
        }
        if (displayInput) {
            displayInput.value = resolved.display || '';
        }
        if (typeof window.onEventTimezoneResolved === 'function') {
            window.onEventTimezoneResolved(resolved.timezone);
        }
    }

    async function resolveEventTimezoneFromCoordinates(lat, lng) {
        const startInput = document.getElementById('startDateTime');
        const params = new URLSearchParams({
            latitude: String(lat),
            longitude: String(lng),
        });
        if (startInput && startInput.value) {
            params.set('at', startInput.value);
        }

        const response = await fetch(`{{ route($routePrefix . '.event.resolve-timezone') }}?${params.toString()}`, {
            headers: { 'Accept': 'application/json' },
        });

        if (!response.ok) {
            return null;
        }

        return response.json();
    }

    window.refreshEventTimezoneFromCoordinates = async function () {
        const latEl = document.getElementById('event_latitude');
        const lngEl = document.getElementById('event_longitude');
        if (!latEl || !lngEl || latEl.value === '' || lngEl.value === '') {
            return;
        }

        const lat = parseFloat(latEl.value);
        const lng = parseFloat(lngEl.value);
        if (Number.isNaN(lat) || Number.isNaN(lng)) {
            return;
        }

        try {
            const resolved = await resolveEventTimezoneFromCoordinates(lat, lng);
            applyResolvedTimezone(resolved);
        } catch (e) {
            console.warn('Timezone resolution failed', e);
        }
    };

    function initEventAddressAutocomplete() {
        const input = document.getElementById('event_address');
        if (!input || typeof google === 'undefined') {
            window.refreshEventTimezoneFromCoordinates();
            return;
        }

        const autocomplete = new google.maps.places.Autocomplete(input, {
            types: ['geocode'],
            componentRestrictions: { country: 'us' },
        });

        autocomplete.addListener('place_changed', async function () {
            const place = autocomplete.getPlace();
            if (!place.geometry) {
                return;
            }

            input.value = place.formatted_address || input.value;

            const cityEl = document.getElementById('city');
            if (cityEl) {
                cityEl.value = extractCityFromPlace(place);
            }

            const lat = place.geometry.location.lat();
            const lng = place.geometry.location.lng();
            document.getElementById('event_latitude').value = lat;
            document.getElementById('event_longitude').value = lng;

            await window.refreshEventTimezoneFromCoordinates();
        });

        window.refreshEventTimezoneFromCoordinates();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const startInput = document.getElementById('startDateTime');
        if (startInput) {
            startInput.addEventListener('change', function () {
                window.refreshEventTimezoneFromCoordinates();
            });
        }

        if (typeof google !== 'undefined' && google.maps && google.maps.event) {
            google.maps.event.addDomListener(window, 'load', initEventAddressAutocomplete);
        } else {
            initEventAddressAutocomplete();
        }
    });
</script>
