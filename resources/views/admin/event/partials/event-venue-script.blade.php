@php
    $routePrefix = $routePrefix ?? (request()->routeIs('manager.*') ? 'manager' : 'admin');
@endphp
<script>
(function () {
    const venueSelect = document.getElementById('venue_name');
    const addressInput = document.getElementById('event_address');
    const latInput = document.getElementById('event_latitude');
    const lngInput = document.getElementById('event_longitude');
    const ianaInput = document.getElementById('timezone');
    const labelInput = document.getElementById('timezone_label');
    const displayInput = document.getElementById('timezone_display');
    const cityInput = document.getElementById('city');
    const resolveUrl = @json(route($routePrefix . '.event.resolve-timezone'));

    if (!venueSelect) {
        return;
    }

    function applyResolved(resolved) {
        if (!resolved) {
            return;
        }
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

    async function resolveTimezoneFromCoords(lat, lng) {
        const params = new URLSearchParams({
            latitude: String(lat),
            longitude: String(lng),
        });
        const startInput = document.querySelector('[name="start_time"]');
        if (startInput && startInput.value) {
            params.set('at', startInput.value);
        }
        try {
            const response = await fetch(`${resolveUrl}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' },
            });
            if (!response.ok) {
                return;
            }
            const resolved = await response.json();
            applyResolved(resolved);
        } catch (e) {
            console.warn('Timezone resolve failed', e);
        }
    }

    function applyVenueOption(option) {
        if (!option || !option.value) {
            if (addressInput) addressInput.value = '';
            if (latInput) latInput.value = '';
            if (lngInput) lngInput.value = '';
            if (ianaInput) ianaInput.value = '';
            if (labelInput) labelInput.value = '';
            if (displayInput) displayInput.value = '';
            return;
        }

        if (addressInput) {
            addressInput.value = option.dataset.address || '';
        }
        if (latInput) {
            latInput.value = option.dataset.latitude || '';
        }
        if (lngInput) {
            lngInput.value = option.dataset.longitude || '';
        }
        if (cityInput && option.dataset.city) {
            cityInput.value = option.dataset.city;
        }

        const hasTz = option.dataset.timezone && option.dataset.timezone !== 'UTC';
        if (hasTz) {
            if (ianaInput) {
                ianaInput.value = option.dataset.timezone;
            }
            if (labelInput) {
                labelInput.value = option.dataset.timezoneLabel || '';
            }
            if (displayInput) {
                displayInput.value = option.dataset.timezoneDisplay || '';
            }
            if (typeof window.onEventTimezoneResolved === 'function') {
                window.onEventTimezoneResolved(option.dataset.timezone);
            }
        } else if (option.dataset.latitude && option.dataset.longitude) {
            resolveTimezoneFromCoords(option.dataset.latitude, option.dataset.longitude);
        }
    }

    venueSelect.addEventListener('change', function () {
        applyVenueOption(this.options[this.selectedIndex]);
    });

    if (typeof jQuery !== 'undefined') {
        jQuery(venueSelect).on('change', function () {
            const option = this.options[this.selectedIndex];
            applyVenueOption(option);
        });
    }

    if (venueSelect.value) {
        applyVenueOption(venueSelect.options[venueSelect.selectedIndex]);
    }
})();
</script>
