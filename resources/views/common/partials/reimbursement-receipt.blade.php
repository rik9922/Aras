@php
    $receiptPath = $reimbursement->receipt_path ?? null;
    $receiptUrl = $receiptPath ? asset('storage/' . $receiptPath) : null;
    $extension = $receiptPath ? strtolower(pathinfo($receiptPath, PATHINFO_EXTENSION)) : null;
    $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    $isPdf = $extension === 'pdf';
@endphp

<div class="reimbursement-receipt-section {{ ($showHeading ?? true) ? 'mt-3' : '' }}">
    @if ($showHeading ?? true)
        <h6 class="mb-2">Receipt</h6>
    @endif
    @if ($receiptUrl)
        <div class="d-flex flex-wrap align-items-start gap-3">
            @if ($isImage)
                <img src="{{ $receiptUrl }}" alt="Receipt"
                    data-type="image" data-url="{{ $receiptUrl }}" class="preview-media reimbursement-receipt-thumb"
                    onerror="this.style.display='none'">
            @elseif ($isPdf)
                <div class="reimbursement-receipt-pdf">
                    <iframe src="{{ $receiptUrl }}" title="Receipt PDF" class="reimbursement-receipt-pdf-frame"></iframe>
                </div>
            @endif
            <div>
                <a href="{{ $receiptUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                    <i class="fa-solid fa-up-right-from-square me-1"></i>Open receipt
                </a>
                @if ($isImage)
                    <p class="text-muted small mb-0 mt-2">Click the thumbnail to enlarge.</p>
                @endif
            </div>
        </div>
    @else
        <p class="text-muted mb-0">No receipt uploaded.</p>
    @endif
</div>

@once
    @push('styles')
        <style>
            .reimbursement-receipt-thumb {
                width: 300px;
                max-width: 100%;
                height: 150px;
                object-fit: cover;
                border-radius: 8px;
                cursor: pointer;
                border: 1px solid #e5e7eb;
            }

            .reimbursement-receipt-pdf-frame {
                width: 300px;
                max-width: 100%;
                height: 200px;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
            }
        </style>
    @endpush

    <div class="modal fade" id="reimbursementReceiptModal" tabindex="-1" aria-labelledby="reimbursementReceiptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reimbursementReceiptModalLabel">Receipt Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="reimbursementReceiptModalBody"></div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.reimbursement-receipt-section .preview-media').forEach(function (element) {
                    element.addEventListener('click', function () {
                        const mediaUrl = element.getAttribute('data-url');
                        const modalBody = document.getElementById('reimbursementReceiptModalBody');
                        const imgElement = document.createElement('img');
                        imgElement.src = mediaUrl;
                        imgElement.style.width = '100%';
                        imgElement.style.height = 'auto';
                        imgElement.style.borderRadius = '8px';
                        modalBody.innerHTML = '';
                        modalBody.appendChild(imgElement);
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('reimbursementReceiptModal')).show();
                    });
                });
            });
        </script>
    @endpush
@endonce
