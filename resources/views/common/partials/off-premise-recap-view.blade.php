@php
    $event = $recap->event;
    $eventLocation = $event->location ?? null;
    $locationName = $eventLocation ? $eventLocation->name ?? '' : '';
    $locationAddress = $eventLocation ? $eventLocation->address ?? '' : '';
    $locationCity = $eventLocation ? $eventLocation->city ?? '' : '';
    $locationAddressDisplay = trim($locationAddress . ($locationCity ? ' - ' . $locationCity : ''));
    $locationDisplay = $locationName ?: $locationAddressDisplay;

    // Products sold (JSON)
    $productsSold = [];
    if ($recap->products_sold) {
        $decoded = is_string($recap->products_sold) ? json_decode($recap->products_sold, true) : $recap->products_sold;
        $productsSold = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
    }

    // OP receipts (JSON)
    $opReceipts = [];
    if ($recap->op_receipts) {
        $decoded = is_string($recap->op_receipts) ? json_decode($recap->op_receipts, true) : $recap->op_receipts;
        $opReceipts = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
    }

    // Event photos: consumer_photo (JSON array) + brand_photo + product_attachments1/2
    $eventPhotos = [];
    if ($recap->consumer_photo) {
        $decoded = json_decode($recap->consumer_photo, true);
        $photos =
            json_last_error() === JSON_ERROR_NONE && is_array($decoded)
                ? $decoded
                : array_filter(explode(',', $recap->consumer_photo));
        $eventPhotos = array_merge($eventPhotos, array_values($photos));
    }
    if (!empty($recap->brand_photo)) {
        $eventPhotos[] = $recap->brand_photo;
    }
    if (!empty($recap->product_attachments1)) {
        $eventPhotos[] = $recap->product_attachments1;
    }
    if (!empty($recap->product_attachments2)) {
        $eventPhotos[] = $recap->product_attachments2;
    }

    // Legacy single receipt field
    $legacyReceipt = $recap->receipt ?? null;

    // Ambassadors (loaded via event.ambassador)
    $ambassadors = $event->ambassador ?? collect([]);

    // Auth / role
    $authUser = auth()->user();
    $rolePrefix = $authUser && (string) $authUser->role_id === '6' ? 'manager' : 'admin';
    $canSeeExpeditedPayBadge = $authUser && in_array((string) $authUser->role_id, ['1', '5', '6'], true);
@endphp

<div class="container py-3">

    {{-- Top action bar --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div></div>
        <div class="d-flex mb-3 gap-2">
            @include('admin.recap.partials.export-email-button', [
                'recapId' => $recap->id,
                'buttonClass' => 'btn btn-outline-success',
            ])
            <button type="button" class="btn btn-secondary" onclick="window.history.go(-1)">Back</button>
        </div>
    </div>

    <div class="row g-4">

        {{-- ═══════════════════════════════════ LEFT ═══════════════════════════════════ --}}
        <div class="col-lg-8">

            {{-- Event heading --}}
            <h5 class="fw-bold text-uppercase mb-1">
                OFF-PREMISE EVENT: {{ strtoupper($locationDisplay ?: $event->event_name ?? 'N/A') }}
            </h5>
            <p class="mb-4 text-muted">
                Brand: {{ $event->brand->brand_name ?? 'N/A' }}
                @if ($canSeeExpeditedPayBadge && $recap->is_expedited_requested)
                    <span class="badge bg-warning text-dark ms-2">Expedited Pay Requested</span>
                @endif
            </p>

            {{-- DESCRIBE TRAFFIC FLOW --}}
            <div class="mb-4">
                <div class="op-section-label">DESCRIBE TRAFFIC FLOW</div>
                <div class="op-content-box">
                    {{ $recap->traffic_flow ?? 'N/A' }}
                </div>
            </div>

            {{-- DEMOGRAPHICS --}}
            <div class="mb-4">
                <div class="op-section-label">DEMOGRAPHICS</div>
                <div class="d-flex gap-4 mb-2">
                    <span class="fw-semibold">FEMALE
                        {{ $recap->demographics_female !== null ? $recap->demographics_female . '%' : '--' }}</span>
                    <span class="fw-semibold">MALE
                        {{ $recap->demographics_male !== null ? $recap->demographics_male . '%' : '--' }}</span>
                </div>
                <div class="row g-2">
                    <div class="col-sm-4">
                        <div class="op-field-label">AGE RANGE</div>
                        <div>{{ $recap->demographics_age_range ?? 'N/A' }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="op-field-label">SAMPLING NUMBER</div>
                        <div>{{ $recap->sampling_number ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            {{-- AMOUNT OF PRODUCT SOLD --}}
            <div class="mb-4">
                <div class="op-section-label">AMOUNT OF PRODUCT SOLD</div>
                @if ($productsSold && count($productsSold) > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Type</th>
                                    <th>Name</th>
                                    <th>Sold</th>
                                    <th>Regular Price</th>
                                    <th>Sale Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($productsSold as $product)
                                    <tr>
                                        <td>{{ $product['type'] ?? '--' }}</td>
                                        <td>{{ $product['name'] ?? '--' }}</td>
                                        <td>{{ $product['sold'] ?? '--' }}</td>
                                        <td>{{ isset($product['regular_price']) ? '$' . number_format((float) $product['regular_price'], 2) : '--' }}
                                        </td>
                                        <td>{{ isset($product['sale_price']) ? '$' . number_format((float) $product['sale_price'], 2) : '--' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0">No products recorded.</p>
                @endif
            </div>

            {{-- RECEIPT TOTAL & PARKING FEE --}}
            <div class="row mb-4">
                <div class="col-sm-4">
                    <div class="op-section-label">RECEIPT TOTAL</div>
                    <div>
                        @if ($recap->receipt_total !== null)
                            ${{ number_format((float) $recap->receipt_total, 2) }}
                        @else
                            N/A
                        @endif
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="op-section-label">PARKING FEE</div>
                    <div>
                        @if ($recap->op_parking_fee !== null)
                            ${{ number_format((float) $recap->op_parking_fee, 2) }}
                        @else
                            $0.00
                        @endif
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="op-section-label">BUYBACK AMOUNT</div>
                    <div>
                        @if ($recap->buyback_amount !== null)
                            ${{ number_format((float) $recap->buyback_amount, 2) }}
                        @else
                            $0.00
                        @endif
                    </div>
                </div>
            </div>

            {{-- YES/NO QUESTIONS --}}
            <div class="mb-4">
                <div class="op-content-box">
                    <ol class="mb-0 ps-3">
                        <li>Was the store aware of the demo?
                            <strong>{{ $recap->store_aware_of_demo ? 'Yes' : 'No' }}</strong>
                        </li>
                        <li>Was the store prepared for the demo?
                            <strong>{{ $recap->store_prepared_for_demo ? 'Yes' : 'No' }}</strong>
                        </li>
                        <li>Was the product on sale? <strong>{{ $recap->product_on_sale ? 'Yes' : 'No' }}</strong></li>
                        <li>Were there coupons or rebates available?
                            <strong>{{ $recap->coupons_rebates_available ? 'Yes' : 'No' }}</strong>
                        </li>
                        <li>Was there enough product in stock?
                            <strong>{{ $recap->enough_product_in_stock ? 'Yes' : 'No' }}</strong>
                        </li>
                        <li>Was there a case display? <strong>{{ $recap->case_display ? 'Yes' : 'No' }}</strong></li>
                        <li># of Cases in Display: <strong>{{ $recap->case_display_count ?? '--' }}</strong></li>
                    </ol>
                </div>
            </div>

            {{-- OBJECTIONS TO SALE --}}
            <div class="mb-4">
                <div class="op-section-label">OBJECTIONS TO SALE</div>
                <div class="op-content-box">
                    {{ $recap->objections_to_sale ?? 'N/A' }}
                </div>
            </div>

            {{-- CONSUMER COMMENTS --}}
            <div class="mb-4">
                <div class="op-section-label">CONSUMER COMMENTS</div>
                <div class="op-content-box">
                    {{ $recap->consumer_comments_suggestions ?? 'N/A' }}
                </div>
            </div>

            {{-- ACCOUNT FEEDBACK --}}
            <div class="mb-4">
                <div class="op-section-label">ACCOUNT FEEDBACK <span
                        class="text-muted fw-normal text-lowercase">(optional)</span></div>
                <div class="op-content-box">
                    {{ $recap->account_feedback ?? 'N/A' }}
                </div>
            </div>

            {{-- EVENT PHOTOS --}}
            @if (count($eventPhotos) > 0)
                <div class="mb-4">
                    <div class="op-section-label">EVENT PHOTOS</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($eventPhotos as $photo)
                            @if (strtolower(pathinfo($photo, PATHINFO_EXTENSION)) === 'pdf')
                                <a href="{{ asset($photo) }}" target="_blank" rel="noopener"
                                    class="btn btn-outline-secondary btn-sm align-self-start">
                                    📄 View {{ basename($photo) }}
                                </a>
                            @else
                                <img src="{{ asset($photo) }}" alt="Event Photo" class="preview-media"
                                    data-type="image" data-url="{{ asset($photo) }}"
                                    style="width: 200px; height: 150px; object-fit: cover; border-radius: 6px; cursor: pointer;"
                                    onerror="this.style.display='none'">
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- RECEIPTS (op_receipts JSON + legacy receipt field) --}}
            @php
                $allReceipts = $opReceipts;
                if ($legacyReceipt) {
                    $allReceipts[] = $legacyReceipt;
                }
            @endphp
            @if (count($allReceipts) > 0)
                <div class="mb-4">
                    <div class="op-section-label">RECEIPTS</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($allReceipts as $receipt)
                            @if (strtolower(pathinfo($receipt, PATHINFO_EXTENSION)) === 'pdf')
                                <a href="{{ asset($receipt) }}" target="_blank" rel="noopener"
                                    class="btn btn-outline-secondary btn-sm align-self-start">
                                    📄 View {{ basename($receipt) }}
                                </a>
                            @else
                                <img src="{{ asset($receipt) }}" alt="Receipt" class="preview-media" data-type="image"
                                    data-url="{{ asset($receipt) }}"
                                    style="width: 200px; height: 150px; object-fit: cover; border-radius: 6px; cursor: pointer;"
                                    onerror="this.style.display='none'">
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ADDITIONAL ATTACHMENTS --}}
            @php
                $recapAttachments = [];
                if (!empty($recap->recap_attachments)) {
                    $decoded = json_decode($recap->recap_attachments, true);
                    $recapAttachments = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
                }
            @endphp
            @if (count($recapAttachments) > 0)
                <div class="mb-4">
                    <div class="op-section-label">ADDITIONAL ATTACHMENTS</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($recapAttachments as $attachment)
                            @if (str_ends_with(strtolower($attachment), '.pdf'))
                                <a href="{{ asset($attachment) }}" target="_blank"
                                    class="btn btn-outline-secondary btn-sm">
                                    📄 {{ basename($attachment) }}
                                </a>
                            @else
                                <img src="{{ asset($attachment) }}" alt="Attachment" class="preview-media"
                                    data-type="image" data-url="{{ asset($attachment) }}"
                                    style="width:200px;height:150px;object-fit:cover;border-radius:6px;cursor:pointer;"
                                    onerror="this.style.display='none'">
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

        </div>{{-- /col-lg-8 --}}

        {{-- ═══════════════════════════════════ RIGHT ════════════════════════════════════ --}}
        <div class="col-lg-4">
            <div class="card border shadow-sm">
                <div class="card-body">
                    <h6 class="text-uppercase fw-bold border-bottom pb-2 mb-3">Other Details</h6>

                    <div class="mb-3">
                        <div class="op-field-label">LOCATION</div>
                        <div>{{ $locationDisplay ?: $event->event_name ?? 'N/A' }}</div>
                    </div>

                    <div class="mb-3">
                        <div class="op-field-label">START</div>
                        <div>
                            {{ $event->start_time ? \Carbon\Carbon::parse($event->start_time)->format('n/j/Y, g:i:s A') : 'N/A' }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="op-field-label">END</div>
                        <div>
                            {{ $event->end_time ? \Carbon\Carbon::parse($event->end_time)->format('n/j/Y, g:i:s A') : 'N/A' }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="op-field-label">EVENT STAFF</div>
                        @if ($ambassadors && $ambassadors->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0" style="font-size: 0.82rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>First Name</th>
                                            <th>Last Name</th>
                                            <th>Email</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($ambassadors as $amb)
                                            @php
                                                $nameParts = explode(' ', trim($amb->name ?? ''), 2);
                                            @endphp
                                            <tr>
                                                <td>{{ $nameParts[0] ?? '' }}</td>
                                                <td>{{ $nameParts[1] ?? '' }}</td>
                                                <td>{{ $amb->email ?? '' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>

                    <div class="mb-3">
                        <div class="op-field-label">DISTRIBUTOR</div>
                        <div>{{ $event->distributor->name ?? 'N/A' }}</div>
                    </div>

                    <div class="mb-3">
                        <div class="op-field-label">PAYMENT TYPE</div>
                        <div>{{ $event->payment_type ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            {{-- Admin / Manager approve actions --}}
            @if ($authUser && ((string) $authUser->role_id === '1' || (string) $authUser->role_id === '6'))
                <div class="card border shadow-sm mt-3">
                    <div class="card-body">
                        <form id="managerReviewFormOP" method="POST"
                            action="{{ route($rolePrefix . '.update-recap-manager-fields', $recap->id) }}"
                            class="mb-3">
                            @csrf
                            @method('patch')
                            <div class="d-flex flex-column gap-2">
                                <div>
                                    <label class="form-label form-label-sm">Manager Payout Override ($)</label>
                                    <input type="number" step="0.01" min="0"
                                        name="manager_override_amount" class="form-control form-control-sm"
                                        value="{{ old('manager_override_amount', $recap->manager_override_amount) }}">
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="manager_no_show_op"
                                        name="manager_no_show" value="1"
                                        {{ $recap->manager_no_show ? 'checked' : '' }}>
                                    <label class="form-check-label" for="manager_no_show_op">
                                        Mark as No-Show (no payout)
                                    </label>
                                </div>
                            </div>
                        </form>

                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" form="managerReviewFormOP" class="btn btn-sm btn-outline-primary">
                                Save Review
                            </button>

                            <form action="{{ route($rolePrefix . '.recap.approve', $recap->id) }}" method="POST"
                                class="mb-0" onsubmit="return confirm('Approve this recap?');">
                                @csrf
                                @method('patch')
                                <button type="submit"
                                    class="btn btn-sm {{ $recap->is_approved ? 'btn-secondary' : 'btn-outline-success' }}"
                                    {{ $recap->is_approved ? 'disabled' : '' }}>
                                    {{ $recap->is_approved ? 'Approved' : 'Approve' }}
                                </button>
                            </form>

                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                data-bs-target="#denyModal">
                                Deny
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>{{-- /col-lg-4 --}}

    </div>{{-- /row --}}
</div>

{{-- ───── Media lightbox modal ───── --}}
<div class="modal fade" id="mediaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="mediaModalBody"></div>
        </div>
    </div>
</div>

{{-- ───── Deny modal ───── --}}
@if ($authUser && ((string) $authUser->role_id === '1' || (string) $authUser->role_id === '6'))
    <div class="modal fade" id="denyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route($rolePrefix . '.recap.deny', $recap->id) }}">
                    @csrf
                    @method('patch')
                    <div class="modal-header">
                        <h5 class="modal-title">Deny Recap</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Reason for Denial</label>
                            <textarea name="deny_reason" class="form-control" rows="4" required
                                placeholder="Enter reason for denying this recap..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger"
                            onclick="return confirm('Are you sure you want to deny this recap?');">
                            Confirm Deny
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@push('styles')
    <style>
        .op-section-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #6c757d;
            letter-spacing: 0.05em;
            margin-bottom: 0.4rem;
        }

        .op-field-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #6c757d;
            letter-spacing: 0.04em;
            margin-bottom: 0.2rem;
        }

        .op-content-box {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.75rem 1rem;
            min-height: 2.5rem;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.preview-media').forEach(function(el) {
                el.addEventListener('click', function() {
                    const url = el.getAttribute('data-url');
                    const type = el.getAttribute('data-type');
                    const body = document.getElementById('mediaModalBody');
                    body.innerHTML = '';
                    if (type === 'image') {
                        const img = document.createElement('img');
                        img.src = url;
                        img.style.cssText = 'width:100%;height:auto;border-radius:6px;';
                        body.appendChild(img);
                    } else if (type === 'video') {
                        const vid = document.createElement('video');
                        vid.controls = true;
                        vid.style.cssText = 'width:100%;';
                        const src = document.createElement('source');
                        src.src = url;
                        vid.appendChild(src);
                        body.appendChild(vid);
                    }
                    new bootstrap.Modal(document.getElementById('mediaModal')).show();
                });
            });
        });
    </script>
@endpush
