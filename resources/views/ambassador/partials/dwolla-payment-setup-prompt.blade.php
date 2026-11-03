@php
    $dwollaMissingFields = $dwollaMissingFields ?? [];
    $showDwollaModal = session('dwolla_payment_setup_required') && count($dwollaMissingFields) > 0;
@endphp

@if (count($dwollaMissingFields) > 0)
    <div class="alert alert-warning mb-0 text-center py-2"
        style="border-radius: 0; background-color: #fff3cd; border-color: #ffc107;">
        <span>
            Additional information is required to complete your payment account setup.
            <strong>Missing:</strong> {{ implode(', ', array_values($dwollaMissingFields)) }}.
        </span>
        <a href="{{ route('ambassador.dwolla.payment-setup') }}" class="btn btn-sm btn-dark ms-2"
            style="padding: 4px 12px;">Complete Payment Setup</a>
    </div>

    <div class="modal fade" id="dwollaPaymentSetupModal" tabindex="-1" aria-labelledby="dwollaPaymentSetupModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dwollaPaymentSetupModalLabel">Payment Account Setup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        To receive payments, we need a few more details for identity verification with Dwolla.
                        You can continue using the app and complete this when you are ready.
                    </p>
                    <p class="mb-2"><strong>Please provide:</strong></p>
                    <ul>
                        @foreach ($dwollaMissingFields as $label)
                            <li>{{ $label }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Continue Later</button>
                    <a href="{{ route('ambassador.dwolla.payment-setup') }}" class="btn btn-dark">Complete Now</a>
                </div>
            </div>
        </div>
    </div>

    @if ($showDwollaModal)
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var modalEl = document.getElementById('dwollaPaymentSetupModal');
                    if (modalEl && typeof bootstrap !== 'undefined') {
                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    }
                });
            </script>
        @endpush
    @endif
@elseif (session('warning'))
    <div class="alert alert-warning mb-0 text-center py-2"
        style="border-radius: 0; background-color: #fff3cd; border-color: #ffc107;">
        {{ session('warning') }}
    </div>
@endif
