<script>
    (function () {
        const form = document.getElementById('eventSaveForm');
        if (!form) {
            return;
        }

        function isBestEventSelected() {
            const selected = form.querySelector('input[name="integrate_with_best"]:checked');
            return selected && selected.value === '1';
        }

        function hasBestConfiguration() {
            const input = document.getElementById('bestEventDataInput');
            if (!input || !input.value) {
                return false;
            }
            try {
                const data = JSON.parse(input.value);
                return data && typeof data === 'object' && Object.keys(data).length > 0;
            } catch (e) {
                return false;
            }
        }

        function openBestModal() {
            const modalEl = document.getElementById('bestFieldsModal');
            if (!modalEl || typeof bootstrap === 'undefined') {
                alert('BEST Event requires configuration. Please complete all required fields in the BEST form before saving.');
                return;
            }
            const bestRadio = form.querySelector('input[name="integrate_with_best"][value="1"]');
            if (bestRadio) {
                bestRadio.checked = true;
            }
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }

        form.addEventListener('submit', function (e) {
            if (!isBestEventSelected()) {
                return;
            }
            if (hasBestConfiguration()) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            openBestModal();
        });
    })();
</script>
