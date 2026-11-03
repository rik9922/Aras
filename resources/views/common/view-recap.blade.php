@extends('layout.app', ['page_name' => 'View Recap'])

@section('content')
    @php
        $recapType = \App\Services\RecapFormService::resolveForRecap($recap);
        $eventTypeName = strtolower(trim((string) optional(optional($recap->event)->eventType)->name));
        $isSpecialEventRecap = $recapType === \App\Services\RecapFormService::TYPE_SPECIAL_EVENT
            || $eventTypeName === 'special event';
        $isOnPremiseRecap = $recapType === \App\Services\RecapFormService::TYPE_ON_PREMISE
            || in_array($eventTypeName, ['on premise', 'on-premise'], true);
        if ($isSpecialEventRecap) {
            $recapType = \App\Services\RecapFormService::TYPE_SPECIAL_EVENT;
        } elseif ($isOnPremiseRecap) {
            $recapType = \App\Services\RecapFormService::TYPE_ON_PREMISE;
        }
    @endphp

    @if($recapType === \App\Services\RecapFormService::TYPE_OFF_PREMISE)
        @include('common.partials.off-premise-recap-view', ['recap' => $recap])
    @else
    <div class="container py-3">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        <div class="d-flex justify-content-end align-items-center flex-wrap gap-2 mb-3 view-recap-header-actions">
            @include('admin.recap.partials.export-email-button', [
                'recapId' => $recap->id,
                'buttonClass' => 'btn btn-outline-success view-recap-export-btn',
            ])
            <button type="button" class="btn btn-design grey-color view-recap-back-btn"
                onclick="window.history.go(-1)">
                Back
            </button>
        </div>
        <div class="section-container">
            <div class="section-title">Event Details</div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="recap-label">Store</div>
                    <div class="recap-value">{{ $recap->state->name ?? '--' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="recap-label">Brand</div>
                    <div class="recap-value">{{ $recap->event->brand->brand_name ?? 'N/A' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="recap-label">Event ID</div>
                    <div class="recap-value"><strong>{{ $recap->event_id }}</strong></div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="recap-label">Event Type</div>
                    <div class="recap-value">{{ $recap->event->eventType->name ?? 'Not set' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="recap-label">Recap Form</div>
                    <div class="recap-value">{{ \App\Services\RecapFormService::displayLabel(\App\Services\RecapFormService::resolveForRecap($recap)) }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="recap-label">Date</div>
                    <div class="recap-value">{{ $recap->event->FormattedDate ?? 'Not available' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="recap-label">Time</div>
                    <div class="recap-value">
                        {{ $recap->event->start_time ? \Carbon\Carbon::parse($recap->event->start_time)->format('h:i A') : 'Not available' }}
                        -
                        {{ $recap->event->end_time ? \Carbon\Carbon::parse($recap->event->end_time)->format('h:i A') : 'Not available' }}
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="recap-label">Ambassadors</div>
                    <div class="recap-value">
                        {{ !empty($ambassadors) ? $ambassadors : 'Not available' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="section-container">
            <div class="section-title">Recap Details</div>
            <div class="row mb-3">

            @if($recapType !== 'off_premise')
                <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">
                    <label for="describeLocation" class="form-label text-bold fs-6 font-bold">Describe the location's
                        traffic (Registers, Aisles, Other parts of location)</label>
                    <p class="text-bold">{{ $recap->traffic_location ?? '-' }}</p>
                </div>
                <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">

                    <label for="eventSuccesfull" class="form-label text-bold fs-6 font-bold">Was the timing of this event
                        successful?</label>
                    <p>{{ optional($recap)->close_time ? \Carbon\Carbon::parse($recap->close_time)->format('Y-m-d H:i') : 'Not available' }}
                    </p>

                </div>
                <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">

                    <label for="checkIn" class="form-label text-bold fs-6 font-bold">With whom did you check in?
                        Provide name, phone and/or email</label>
                    <p>{{ $recap->check_in_with_person ?? 'Not available' }}</p>

                </div>
                @if(! $isSpecialEventRecap)
                <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">

                    <label for="selectShirtSize" class="form-label text-bold fs-6 font-bold">Is there a promotion
                        running for this product?</label>
                    <p>{{ optional($recap)->promotion_product_status ? 'Yes' : 'No' }}</p>

                </div>
                <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">

                    <label for="everydayPriceTip" class="form-label text-bold fs-6 font-bold">Everyday Price Tip (line
                        break) What is the Feature/Special Pricing?</label>
                    <p>{{ optional($recap)->tip ?? 'Not available' }}</p>

                </div>

                <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">

                    <label for="competitorBrands" class="form-label text-bold fs-6 font-bold">If competitor brands were
                        having demos on-site, list their names, setup, location and price point.</label>
                    <p>{{ optional($recap)->brand_dmeo_name ?? 'Not available' }}</p>

                </div>
                @endif

                <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">
                    <label class="form-label text-bold fs-6 font-bold">Back up brand used</label>
                    <p>{{ optional($recap)->backup_brand_used ? 'Yes' : 'No' }}</p>
                </div>
                @if(optional($recap)->backup_brand_used && optional($recap)->backup_brand_name)
                <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">
                    <label class="form-label text-bold fs-6 font-bold">Backup brand name</label>
                    <p>{{ $recap->backup_brand_name }}</p>
                </div>
                @endif

                <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">

                    <label for="predominantGender" class="form-label text-bold fs-6 font-bold">What was the predominant
                        gender of consumers you interacted with?</label>
                    <p>{{ optional($recap)->interaction_gender ? ucfirst($recap->interaction_gender) : 'Not available' }}
                    </p>

                </div>
                <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">

                    <label for="predominantAge" class="form-label text-bold fs-6 font-bold">What was the predominant age
                        of consumers you interacted with?</label>
                    <p>{{ optional($recap)->interaction_age ?? 'Not available' }}</p>

                </div>
                <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">

                    <label for="manyConsumers" class="form-label text-bold fs-6 font-bold">Approximately how many
                        consumers did you interact with?</label>
                    <p>{{ optional($recap)->total_interaction ?? 'Not available' }}</p>

                </div>
                @if(! $isSpecialEventRecap)
                <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">

                    <label for="manySamples" class="form-label text-bold fs-6 font-bold">How many samples did you hand
                        out?</label>
                    <p>{{ $recap->samples ?? 'Not available' }}</p>

                </div>

                <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">

                    @if($recap->event && $recap->event->isThcEvent())
                        <label for="thcProducts" class="form-label text-bold fs-6 font-bold">THC Products Sold</label>
                        @if($recap->thc_products_sold)
                            @php
                                $thcProducts = is_string($recap->thc_products_sold) ? json_decode($recap->thc_products_sold, true) : $recap->thc_products_sold;
                            @endphp
                            @if($thcProducts && is_array($thcProducts))
                                <div class="mt-2">
                                    @foreach($thcProducts as $index => $product)
                                        <p><strong>{{ $product['brand'] }} {{ $product['flavor'] }} {{ $product['dosage'] }} {{ $product['type'] }}</strong></p>
                                    @endforeach
                                </div>
                            @else
                                <p>Not available</p>
                            @endif
                        @else
                            <p>Not available</p>
                        @endif
                    @else
                        <label for="productSelling" class="form-label text-bold fs-6 font-bold">What type of product were
                            you selling?</label>
                        <p>{{ $brand->brand_name ?? 'Not available' }}</p>
                    @endif

                </div>
                @if(!($recap->event && $recap->event->is_thc_event))
                <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">

                    <label for="productWereSold" class="form-label text-bold fs-6 font-bold">How many units of each
                        product were sold during your task?</label>
                    <p>{{ $recap->sold_unit ?? 'Not available' }}</p>

                </div>
                @endif
                @endif

                <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">
                    <label for="buyback_amount" class="form-label text-bold fs-6 font-bold">Buyback amount ($)</label>
                    <p>
                        @if($recap->buyback_amount !== null)
                            ${{ number_format((float) $recap->buyback_amount, 2) }}
                        @else
                            Not available
                        @endif
                    </p>
                </div>

                @endif {{-- end non-off_premise base fields --}}

                @include('common.partials.recap-type-display', ['recap' => $recap, 'recapType' => $recapType])

                <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">

                    <label for="stateId" class="form-label text-bold fs-6 font-bold">State : <b>
                            {{ $recap->state ? $recap->state->name : 'Not available' }} </b></label>

                </div>

                @if(! $isSpecialEventRecap)
                <div class="col-md-6" style="padding: 10px; border-bottom: 1px dashed #ddd;">

                    <label for="stateId" class="form-label text-bold fs-6 font-bold">How many people knew about the brand?
                        : <b> {{ $recap->brand_count ?? '-' }} </b></label>

                </div>
                @endif
            </div>
            @if($isSpecialEventRecap)
            @php
                $specialConsumerPhotos = [];
                if (!empty($recap->consumer_photo)) {
                    $decodedPhotos = json_decode($recap->consumer_photo, true);
                    $specialConsumerPhotos = (json_last_error() === JSON_ERROR_NONE && is_array($decodedPhotos))
                        ? $decodedPhotos
                        : array_filter(explode(',', $recap->consumer_photo));
                }

                $specialAttachmentGroups = [
                    'Product Display Photo' => array_filter([$recap->product_attachments1 ?? null]),
                    'Product Shelf Photo' => array_filter([$recap->product_attachments2 ?? null]),
                    'Setup Photo' => array_filter([$recap->brand_photo ?? null]),
                    'Consumers With Product' => $specialConsumerPhotos,
                    'Receipt' => array_filter([$recap->receipt ?? null]),
                ];

                $specialAdditionalAttachments = [];
                if (!empty($recap->recap_attachments)) {
                    $decodedAttachments = json_decode($recap->recap_attachments, true);
                    $specialAdditionalAttachments = (json_last_error() === JSON_ERROR_NONE && is_array($decodedAttachments))
                        ? $decodedAttachments
                        : [];
                }
                if (count($specialAdditionalAttachments) > 0) {
                    $specialAttachmentGroups['Additional Attachments'] = $specialAdditionalAttachments;
                }

                $hasSpecialAttachments = collect($specialAttachmentGroups)->flatten()->filter()->isNotEmpty();
            @endphp
            @if($hasSpecialAttachments)
                <div class="h6">Uploaded Files</div>
                @foreach($specialAttachmentGroups as $attachmentLabel => $attachments)
                    @if(count($attachments) > 0)
                        <div class="mb-3">
                            <div class="fw-semibold mb-2">{{ $attachmentLabel }}</div>
                            <div class="row d-flex flex-wrap gap-2">
                                @foreach($attachments as $attachment)
                                    @php
                                        $extension = strtolower(pathinfo($attachment, PATHINFO_EXTENSION));
                                        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    @endphp
                                    @if($isImage)
                                        <img src="{{ asset($attachment) }}" alt="{{ $attachmentLabel }}"
                                            data-type="image" data-url="{{ asset($attachment) }}"
                                            class="preview-media"
                                            style="width: 300px; height: 150px; object-fit: cover; border-radius: 8px; cursor:pointer;"
                                            onerror="this.style.display='none'">
                                    @else
                                        <a href="{{ asset($attachment) }}" target="_blank"
                                            class="btn btn-sm btn-outline-secondary">
                                            View {{ $attachmentLabel }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
            @elseif($isOnPremiseRecap)
            @php
                $onPremiseConsumerPhotos = [];
                if (!empty($recap->consumer_photo)) {
                    $decodedPhotos = json_decode($recap->consumer_photo, true);
                    $onPremiseConsumerPhotos = (json_last_error() === JSON_ERROR_NONE && is_array($decodedPhotos))
                        ? $decodedPhotos
                        : array_filter(explode(',', $recap->consumer_photo));
                }

                $onPremiseAttachmentGroups = [
                    'Product Display Photo' => array_filter([$recap->product_attachments1 ?? null]),
                    'Product Shelf Photo' => array_filter([$recap->product_attachments2 ?? null]),
                    'Setup Photo' => array_filter([$recap->brand_photo ?? null]),
                    'Consumers With Product' => $onPremiseConsumerPhotos,
                    'Receipt' => array_filter([$recap->receipt ?? null]),
                ];

                $onPremiseAdditionalAttachments = [];
                if (!empty($recap->recap_attachments)) {
                    $decodedAttachments = json_decode($recap->recap_attachments, true);
                    $onPremiseAdditionalAttachments = (json_last_error() === JSON_ERROR_NONE && is_array($decodedAttachments))
                        ? $decodedAttachments
                        : [];
                }
                if (count($onPremiseAdditionalAttachments) > 0) {
                    $onPremiseAttachmentGroups['Additional Attachments'] = $onPremiseAdditionalAttachments;
                }

                $hasOnPremiseAttachments = collect($onPremiseAttachmentGroups)->flatten()->filter()->isNotEmpty();
            @endphp
            @if($hasOnPremiseAttachments)
                <div class="h6">Uploaded Files</div>
                @foreach($onPremiseAttachmentGroups as $attachmentLabel => $attachments)
                    @if(count($attachments) > 0)
                        <div class="mb-3">
                            <div class="fw-semibold mb-2">{{ $attachmentLabel }}</div>
                            <div class="row d-flex flex-wrap gap-2">
                                @foreach($attachments as $attachment)
                                    @php
                                        $extension = strtolower(pathinfo($attachment, PATHINFO_EXTENSION));
                                        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    @endphp
                                    @if($isImage)
                                        <img src="{{ asset($attachment) }}" alt="{{ $attachmentLabel }}"
                                            data-type="image" data-url="{{ asset($attachment) }}"
                                            class="preview-media"
                                            style="width: 300px; height: 150px; object-fit: cover; border-radius: 8px; cursor:pointer;"
                                            onerror="this.style.display='none'">
                                    @else
                                        <a href="{{ asset($attachment) }}" target="_blank"
                                            class="btn btn-sm btn-outline-secondary">
                                            View {{ $attachmentLabel }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
            @elseif($recapType !== 'off_premise')
            <div class="h6">Events Photo</div>
            <div class="row d-flex flex-wrap gap-2">

                @if (optional($recap)->consumer_photo && !empty($recap->consumer_photo))
                    @php
                        $consumerPhotos = json_decode($recap->consumer_photo, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $consumerPhotos = explode(',', $recap->consumer_photo);
                        }
                    @endphp


                    @foreach ($consumerPhotos as $photo)
                        <img src="{{ asset($photo) }}" alt="" data-type="image" data-url="{{ asset($photo) }}"
                            class="preview-media"
                            style="width: 300px; height: 150px; object-fit: cover; border-radius: 8px; cursor:pointer;"
                            onerror="this.style.display='none'">
                    @endforeach
                @endif
                @if (optional($recap)->brand_photo && !empty($recap->brand_photo))
                    <img src="{{ asset($recap->brand_photo) }}" alt=""
                        style="width: 300px; height: 150px; object-fit: cover; border-radius: 8px;cursor:pointer;"
                        data-type="image" data-url="{{ asset($recap->brand_photo) }}" class="preview-media"
                        onerror="this.style.display='none'">
                @endif
                @if (optional($recap)->product_attachments1 && !empty($recap->product_attachments1))
                    <img src="{{ asset($recap->product_attachments1) }}" alt=""
                        style="width: 300px; height: 150px; object-fit: cover; border-radius: 8px;cursor:pointer;"
                        data-type="image" data-url="{{ asset($recap->product_attachments1) }}" class="preview-media"
                        onerror="this.style.display='none'">
                @endif
                @if (optional($recap)->product_attachments2 && !empty($recap->product_attachments2))
                    <img src="{{ asset($recap->product_attachments2) }}" alt=""
                        style="width: 300px; height: 150px; object-fit: cover; border-radius: 8px;cursor:pointer;"
                        data-type="image" data-url="{{ asset($recap->product_attachments2) }}" class="preview-media"
                        onerror="this.style.display='none'">
                @endif
            </div>
            @if (optional($recap)->receipt)
                <div class="h6 mt-2">Receipt</div>
                <div class="row d-flex flex-wrap gap-2">
                    <img src="{{ asset($recap->receipt) }}" alt="" data-type="image"
                        data-url="{{ asset($recap->receipt) }}" class="preview-media"
                        style="width: 300px; height: 150px; object-fit: cover; border-radius: 8px;cursor:pointer;">
                </div>
            @endif

            {{-- ADDITIONAL ATTACHMENTS --}}
            @php
                $recapAttachmentsView = [];
                if (!empty($recap->recap_attachments)) {
                    $decodedAtt = json_decode($recap->recap_attachments, true);
                    $recapAttachmentsView = (json_last_error() === JSON_ERROR_NONE && is_array($decodedAtt)) ? $decodedAtt : [];
                }
            @endphp
            @if(count($recapAttachmentsView) > 0)
                <div class="h6 mt-2">Additional Attachments</div>
                <div class="row d-flex flex-wrap gap-2 mb-3">
                    @foreach($recapAttachmentsView as $attachment)
                        @if(str_ends_with(strtolower($attachment), '.pdf'))
                            <a href="{{ asset($attachment) }}" target="_blank"
                               class="btn btn-outline-secondary btn-sm">
                                📄 {{ basename($attachment) }}
                            </a>
                        @else
                            <img src="{{ asset($attachment) }}" alt="Attachment"
                                 data-type="image" data-url="{{ asset($attachment) }}"
                                 class="preview-media"
                                 style="width:300px;height:150px;object-fit:cover;border-radius:8px;cursor:pointer;"
                                 onerror="this.style.display='none'">
                        @endif
                    @endforeach
                </div>
            @endif

            @endif {{-- end non-off_premise photos --}}
            <div class="row mt-4">
                @php
                    $user = auth()->user();
                    $rolePrefix = $user && $user->role_id == '6' ? 'manager' : 'admin';
                @endphp
                @if ($user && ($user->role_id == '1' || $user->role_id == '6'))
                <div class="col-12">
                    <form id="managerReviewForm" method="POST"
                        action="{{ route($rolePrefix . '.update-recap-manager-fields', $recap->id) }}"
                        class="mb-3">
                        @csrf
                        @method('patch')
                        <div class="d-flex gap-3 flex-wrap align-items-end">
                            <div>
                                <label class="form-label">Manager Payout Override ($)</label>
                                <input type="number" step="0.01" min="0" name="manager_override_amount"
                                    class="form-control"
                                    value="{{ old('manager_override_amount', $recap->manager_override_amount) }}">
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="manager_no_show"
                                    name="manager_no_show" value="1"
                                    {{ $recap->manager_no_show ? 'checked' : '' }}>
                                <label class="form-check-label" for="manager_no_show">
                                    Mark as No-Show (no payout / no QuickBooks)
                                </label>
                            </div>
                        </div>
                    </form>

                    <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                        <button type="submit" form="managerReviewForm" class="btn btn-outline-primary">
                            Save Manager Review
                        </button>

                        <form action="{{ route($rolePrefix . '.recap.approve', $recap->id) }}"
                            method="POST"
                            class="mb-0"
                            onsubmit="return confirm('Are you sure you want to approve this recap?');">
                            @csrf
                            @method('patch')

                            <button type="submit"
                                class="btn {{ $recap->is_approved ? 'btn-secondary' : 'btn-outline-success' }}"
                                {{ $recap->is_approved ? 'disabled' : '' }}>
                                {{ $recap->is_approved ? 'Approved' : 'Approve' }}
                            </button>
                        </form>

                        <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                            data-bs-target="#denyModal">
                            Deny
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Popup Modal for Image or Video -->
    <div class="modal fade" id="mediaModal" tabindex="-1" aria-labelledby="mediaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mediaModalLabel">Media Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="mediaModalBody">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Deny Modal -->
    <div class="modal fade" id="denyModal" tabindex="-1" aria-labelledby="denyModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">

                        <form method="POST" action="{{ route($rolePrefix . '.recap.deny', $recap->id) }}">
                    @csrf
                    @method('patch')
    
                    <div class="modal-header">
                        <h5 class="modal-title" id="denyModalLabel">Deny Recap</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Reason for Denial</label>
                            <textarea name="deny_reason" 
                                      class="form-control" 
                                      rows="4" 
                                      required 
                                      placeholder="Enter reason for denying this recap..."></textarea>
                        </div>
                    </div>
    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
    
                        <button type="submit" 
                                class="btn btn-danger"
                                onclick="return confirm('Are you sure you want to deny this recap?');">
                            Confirm Deny
                        </button>
                    </div>
    
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@push('styles')
    <style>
        .view-recap-header-actions .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            min-width: 86px;
            font-weight: 600;
            font-size: var(--font-size-button);
        }

        .view-recap-header-actions .view-recap-export-btn {
            border-radius: 0.375rem !important;
        }
    </style>
@endpush

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mediaElements = document.querySelectorAll('.preview-media');
        mediaElements.forEach(element => {
            element.addEventListener('click', function() {
                const mediaUrl = element.getAttribute('data-url');
                const mediaType = element.getAttribute('data-type');
                const mediaModalBody = document.getElementById('mediaModalBody');

                if (mediaType === 'image') {
                    const imgElement = document.createElement('img');
                    imgElement.src = mediaUrl;
                    imgElement.style.width = '100%';
                    imgElement.style.height = 'auto';
                    imgElement.style.borderRadius = '8px';
                    mediaModalBody.innerHTML = '';
                    mediaModalBody.appendChild(imgElement);
                } else if (mediaType === 'video') {
                    const videoElement = document.createElement('video');
                    videoElement.controls = true;
                    videoElement.style.width = '100%';
                    videoElement.style.height = 'auto';
                    const sourceElement = document.createElement('source');
                    sourceElement.src = mediaUrl;
                    videoElement.appendChild(sourceElement);
                    mediaModalBody.innerHTML = '';
                    mediaModalBody.appendChild(videoElement);
                }
                const modal = new bootstrap.Modal(document.getElementById('mediaModal'));
                modal.show();
            });
        });
    });
</script>
