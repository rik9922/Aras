@if ((string) (Auth::user()->role_id ?? '') === '5')
    <div class="modal fade" id="receiptOcrConfirmationModal" tabindex="-1"
        aria-labelledby="receiptOcrConfirmationTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptOcrConfirmationTitle">Receipt total confirmation</h5>
                </div>
                <div class="modal-body">
                    <p id="receiptOcrConfirmationMessage" class="mb-3"></p>
                    <div id="receiptOcrDetectedAmountWrap">
                        <label for="receiptOcrDetectedAmount" class="form-label">Detected amount</label>
                        <input type="text" id="receiptOcrDetectedAmount" class="form-control" readonly>
                    </div>
                    <div id="receiptOcrWarning" class="alert alert-warning mb-0 d-none" role="alert">
                        We couldn't identify the total amount. Please upload a clear picture of the receipt.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="receiptOcrNo" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">No</button>
                    <button type="button" id="receiptOcrYes" class="btn btn-dark">Yes</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                var receiptInput = document.getElementById('op_event_receipts');
                var totalInput = document.querySelector('input[name="receipt_total"]');
                var modalElement = document.getElementById('receiptOcrConfirmationModal');

                if (!receiptInput || !totalInput || !modalElement || typeof bootstrap === 'undefined') {
                    return;
                }

                var modal = new bootstrap.Modal(modalElement, {
                    backdrop: 'static',
                    keyboard: false
                });
                var message = document.getElementById('receiptOcrConfirmationMessage');
                var amountWrap = document.getElementById('receiptOcrDetectedAmountWrap');
                var amountInput = document.getElementById('receiptOcrDetectedAmount');
                var warning = document.getElementById('receiptOcrWarning');
                var yesButton = document.getElementById('receiptOcrYes');
                var noButton = document.getElementById('receiptOcrNo');
                var detectedTotal = null;
                var requestSequence = 0;
                var supportedTypes = ['image/jpeg', 'image/png', 'image/webp'];

                function showWarning() {
                    detectedTotal = null;
                    message.classList.add('d-none');
                    amountWrap.classList.add('d-none');
                    warning.classList.remove('d-none');
                    yesButton.classList.add('d-none');
                    noButton.textContent = 'No';
                    modal.show();
                }

                function showConfirmation(total) {
                    detectedTotal = String(total);
                    message.textContent = 'Your total is $' + detectedTotal + '. Is this correct?';
                    amountInput.value = '$' + detectedTotal;
                    message.classList.remove('d-none');
                    amountWrap.classList.remove('d-none');
                    warning.classList.add('d-none');
                    yesButton.classList.remove('d-none');
                    noButton.textContent = 'No';
                    modal.show();
                }

                receiptInput.addEventListener('change', function() {
                    var files = Array.from(receiptInput.files || []);
                    var image = files.slice().reverse().find(function(file) {
                        return supportedTypes.includes(file.type);
                    });

                    if (!image) {
                        return;
                    }

                    var currentRequest = ++requestSequence;
                    var formData = new FormData();
                    formData.append('receipt_image', image);

                    fetch(@json(route('ambassador.receipt-ocr')), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: formData
                    })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('Receipt OCR failed.');
                        }

                        return response.json();
                    })
                    .then(function(result) {
                        if (currentRequest !== requestSequence) {
                            return;
                        }

                        result.total ? showConfirmation(result.total) : showWarning();
                    })
                    .catch(function() {
                        if (currentRequest === requestSequence) {
                            showWarning();
                        }
                    });
                });

                yesButton.addEventListener('click', function() {
                    if (detectedTotal !== null) {
                        totalInput.value = detectedTotal.replace(/,/g, '');
                        totalInput.dispatchEvent(new Event('input', { bubbles: true }));
                        totalInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    modal.hide();
                });

                modalElement.addEventListener('hidden.bs.modal', function() {
                    if (detectedTotal === null) {
                        receiptInput.focus();
                    }
                });
            })();
        </script>
    @endpush
@endif
