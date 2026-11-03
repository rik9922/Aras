@php
    $routePrefix = request()->routeIs('manager.*') ? 'manager' : 'admin';
@endphp
<script>
(function () {
    const latInput = document.getElementById('lat');
    const lngInput = document.getElementById('long');
    const ianaInput = document.getElementById('venue_timezone');
    const labelInput = document.getElementById('venue_timezone_label');
    const displayInput = document.getElementById('venue_timezone_display');
    const resolveUrl = @json(route($routePrefix . '.event.resolve-timezone'));

    if (!latInput || !lngInput) {
        return;
    }

    async function resolveFromCoords() {
        const lat = latInput.value;
        const lng = lngInput.value;
        if (!lat || !lng) {
            return;
        }
        const params = new URLSearchParams({ latitude: lat, longitude: lng });
        try {
            const response = await fetch(`${resolveUrl}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' },
            });
            if (!response.ok) {
                return;
            }
            const resolved = await response.json();
            if (ianaInput) {
                ianaInput.value = resolved.timezone || '';
            }
            if (labelInput) {
                labelInput.value = resolved.timezone_label || '';
            }
            if (displayInput) {
                displayInput.value = resolved.display || '';
            }
        } catch (e) {
            console.warn('Venue timezone resolve failed', e);
        }
    }

    const observer = new MutationObserver(resolveFromCoords);
    latInput.addEventListener('change', resolveFromCoords);
    lngInput.addEventListener('change', resolveFromCoords);

    if (latInput.value && lngInput.value) {
        if (!ianaInput || !ianaInput.value) {
            resolveFromCoords();
        }
    }
})();
</script>
