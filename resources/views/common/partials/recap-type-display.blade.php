@php
    $displayType = $recapType ?? \App\Services\RecapFormService::resolveForRecap($recap);
@endphp

@if($displayType === 'off_premise')

    {{-- ── OFF-PREMISE SPECIFIC FIELDS ─────────────────────────────── --}}

    <div class="col-md-12" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Describe Traffic Flow</label>
        <p>{{ $recap->traffic_flow ?? 'Not available' }}</p>
    </div>

    <div class="col-md-4" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Demographics — Female</label>
        <p>{{ $recap->demographics_female !== null ? $recap->demographics_female . '%' : 'Not available' }}</p>
    </div>
    <div class="col-md-4" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Demographics — Male</label>
        <p>{{ $recap->demographics_male !== null ? $recap->demographics_male . '%' : 'Not available' }}</p>
    </div>
    <div class="col-md-4" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Demographics — Age Range</label>
        <p>{{ $recap->demographics_age_range ?? 'Not available' }}</p>
    </div>

    <div class="col-md-4" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Sampling Number</label>
        <p>{{ $recap->sampling_number ?? 'Not available' }}</p>
    </div>

    {{-- Products Sold table --}}
    <div class="col-md-12" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Products Sold</label>
        @php
            $productsSold = $recap->products_sold ?? null;
            if (is_string($productsSold)) {
                $productsSold = json_decode($productsSold, true);
            }
        @endphp
        @if(!empty($productsSold) && is_array($productsSold))
            <div class="table-responsive mt-2">
                <table class="table table-sm table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Sold</th>
                            <th>Regular Price</th>
                            <th>Sale Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productsSold as $product)
                        <tr>
                            <td>{{ $product['type'] ?? '-' }}</td>
                            <td>{{ $product['name'] ?? '-' }}</td>
                            <td>{{ $product['sold'] ?? '-' }}</td>
                            <td>{{ isset($product['regular_price']) && $product['regular_price'] !== null && $product['regular_price'] !== '' ? '$' . number_format((float)$product['regular_price'], 2) : '-' }}</td>
                            <td>{{ isset($product['sale_price']) && $product['sale_price'] !== null && $product['sale_price'] !== '' ? '$' . number_format((float)$product['sale_price'], 2) : '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p>Not available</p>
        @endif
    </div>

    <div class="col-md-4" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Receipt Total</label>
        <p>{{ $recap->receipt_total !== null ? '$' . number_format((float)$recap->receipt_total, 2) : 'Not available' }}</p>
    </div>
    <div class="col-md-4" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Parking Fee</label>
        <p>{{ $recap->op_parking_fee !== null ? '$' . number_format((float)$recap->op_parking_fee, 2) : 'Not available' }}</p>
    </div>

    {{-- Demo Assessment --}}
    <div class="col-md-4" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Store Aware of Demo?</label>
        <p>{{ $recap->store_aware_of_demo ? 'Yes' : 'No' }}</p>
    </div>
    <div class="col-md-4" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Store Prepared for Demo?</label>
        <p>{{ $recap->store_prepared_for_demo ? 'Yes' : 'No' }}</p>
    </div>
    <div class="col-md-4" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Product on Sale?</label>
        <p>{{ $recap->product_on_sale ? 'Yes' : 'No' }}</p>
    </div>
    <div class="col-md-4" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Coupons / Rebates Available?</label>
        <p>{{ $recap->coupons_rebates_available ? 'Yes' : 'No' }}</p>
    </div>
    <div class="col-md-4" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Enough Product in Stock?</label>
        <p>{{ $recap->enough_product_in_stock ? 'Yes' : 'No' }}</p>
    </div>
    <div class="col-md-4" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Case Display?</label>
        <p>
            {{ $recap->case_display ? 'Yes' : 'No' }}
            @if($recap->case_display && $recap->case_display_count)
                — {{ $recap->case_display_count }}
            @endif
        </p>
    </div>

    <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Objections to the Sale</label>
        <p>{{ $recap->objections_to_sale ?? 'Not available' }}</p>
    </div>
    <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Consumer Comments / Suggestions</label>
        <p>{{ $recap->consumer_comments_suggestions ?? 'Not available' }}</p>
    </div>
    <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Account Feedback</label>
        <p>{{ $recap->account_feedback ?? 'Not available' }}</p>
    </div>

    {{-- Off-premise photos & receipts --}}
    @php
        $opPhotos = $recap->op_event_photos ?? null;
        if (is_string($opPhotos)) { $opPhotos = json_decode($opPhotos, true); }
        $opReceipts = $recap->op_event_receipts ?? null;
        if (is_string($opReceipts)) { $opReceipts = json_decode($opReceipts, true); }
    @endphp

    @if(!empty($opPhotos) && is_array($opPhotos))
        <div class="col-md-12 mt-2">
            <div class="h6">Event Photos</div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($opPhotos as $photo)
                    <img src="{{ asset($photo) }}" alt="" data-type="image" data-url="{{ asset($photo) }}"
                        class="preview-media"
                        style="width:300px;height:150px;object-fit:cover;border-radius:8px;cursor:pointer;"
                        onerror="this.style.display='none'">
                @endforeach
            </div>
        </div>
    @endif

    @if(!empty($opReceipts) && is_array($opReceipts))
        <div class="col-md-12 mt-2">
            <div class="h6">Receipts</div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($opReceipts as $receipt)
                    @php $ext = strtolower(pathinfo($receipt, PATHINFO_EXTENSION)); @endphp
                    @if(in_array($ext, ['jpg','jpeg','png','gif']))
                        <img src="{{ asset($receipt) }}" alt="" data-type="image" data-url="{{ asset($receipt) }}"
                            class="preview-media"
                            style="width:300px;height:150px;object-fit:cover;border-radius:8px;cursor:pointer;"
                            onerror="this.style.display='none'">
                    @else
                        <a href="{{ asset($receipt) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                            View Receipt
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

@elseif($displayType === 'special_event')

    <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Consumer Interactions</label>
        <p>{{ $recap->consumer_interactions ?? 'Not available' }}</p>
    </div>
    <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Consumer Sampled</label>
        <p>{{ $recap->consumer_sampled ?? 'Not available' }}</p>
    </div>
    <div class="col-md-12" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Event Summary</label>
        <p>{{ $recap->event_summary ?? 'Not available' }}</p>
    </div>
    <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Please list all relevant consumer feedback.</label>
        <p>{{ $recap->consumer_feedback ?? 'Not available' }}</p>
    </div>
    <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Ambassador Feedback</label>
        <p>{{ $recap->ambassador_feedback ?? 'Not available' }}</p>
    </div>

@elseif($displayType === 'on_premise')

    <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Drinks Purchased After Sampling (Consumer)</label>
        <p>{{ $recap->drinks_purchased_after_sampling ?? 'Not available' }}</p>
    </div>
    <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Consumer Comments</label>
        <p>{{ $recap->consumer_feedback ?? 'Not available' }}</p>
    </div>
    <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Manager Comments</label>
        <p>{{ $recap->manager_comments ?? 'Not available' }}</p>
    </div>
    <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Consumer Comments / Suggestions</label>
        <p>{{ $recap->consumer_comments_suggestions ?? 'Not available' }}</p>
    </div>

@else

    {{-- Default / BEST / off_premise fallback --}}
    <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Please list all relevant consumer feedback.</label>
        <p>{{ $recap->consumer_feedback ?? 'Not available' }}</p>
    </div>
    <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">
        <label class="form-label text-bold fs-6 font-bold">Ambassador Feedback</label>
        <p>{{ $recap->ambassador_feedback ?? 'Not available' }}</p>
    </div>

@endif
