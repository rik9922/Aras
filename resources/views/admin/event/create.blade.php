@extends('layout.app', ['page_name' => 'Create Event'])
@section('content')
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <style>
            .btn-design {
                height: 38px;
                padding: 6px 12px;
                font-size: var(--font-size-button);
                line-height: 1.5;
            }

            /* Suppress Bootstrap's green valid styling on this form.
                               was-validated colours every non-empty field green by default.
                               We only ever want red (invalid) feedback — valid fields
                               should simply show the default border. */
            #eventSaveForm.was-validated .form-control:valid,
            #eventSaveForm.was-validated .form-select:valid,
            #eventSaveForm.was-validated .form-check-input:valid {
                border-color: #dee2e6 !important;
                background-image: none !important;
                box-shadow: none !important;
            }

            #eventSaveForm.was-validated .form-control:valid:focus,
            #eventSaveForm.was-validated .form-select:valid:focus {
                border-color: #86b7fe !important;
                box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, .25) !important;
            }

            #eventSaveForm.was-validated .valid-feedback {
                display: none !important;
            }
        </style>
    @endpush
    @php
        $routePrefix = (int) (auth()->user()->role_id ?? 0) === 6 ? 'manager' : 'admin';
        $managerReadonlyAttr = $routePrefix === 'manager' ? 'readonly' : '';
        $managerReadonlyClass = $routePrefix === 'manager' ? 'bg-light' : '';
        $billingDefaultsUrlTemplate = url('/' . $routePrefix . '/clients/__CLIENT__/billing-defaults');
    @endphp

    <div class="container pb-3">
        @if ($errors->any())
            <div id="eventErrorSummary" class="alert alert-danger mx-3 mt-3" role="alert">
                <strong>Please correct the following errors before saving:</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="page-card-design">
            <div class="d-flex">
                <div class="h5 text-uppercase">Event Details</div>
            </div>
            <div class="fixed-border"></div>
            <form id="eventSaveForm" action="{{ route($routePrefix . '.store.event') }}" method="POST"
                enctype="multipart/form-data" novalidate
                @if ($errors->any()) class="was-validated" data-has-errors="1" @endif>@csrf
                <input type="hidden" name="auto_event_name" value="{{ old('auto_event_name', $nextEventName ?? '') }}">

                <div class="row p-3">
                    {{-- Event name is generated automatically by the backend.
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="event_name" class="form-label text-uppercase">Event Name</label>
                            <input type="text" class="form-control shadow-none @error('event_name') is-invalid @enderror"
                                name="event_name" value="{{ old('event_name', $nextEventName ?? '') }}" id="event_name" placeholder="Event Name"
                                autocomplete="off">
                            @error('event_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    --}}
                    @include('admin.event.partials.city-field', ['event' => new \App\Models\Event()])
                    @include('admin.event.partials.venue-event-fields', [
                        'event' => new \App\Models\Event(),
                        'states' => $states,
                        'venueTypes' => $venueTypes,
                    ])
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="selectDepartment"
                                class="form-label text-uppercase field-required">Department</label>
                            <select
                                class="form-control form-select shadow-none @error('department_id') is-invalid @enderror"
                                id="selectDepartment" aria-label="Department" name="department_id">
                                <option value="">Select</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @if (old('department_id') == $department->id) selected @endif>
                                        {{ $department->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    @include('admin.event.partials.event-type-field', ['event' => new \App\Models\Event()])
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="selectPosition" class="form-label text-uppercase field-required">Positions(How many
                                Ambassadors for
                                this event)</label>
                            <input type="number" class="form-control shadow-none @error('position') is-invalid @enderror"
                                value="{{ old('position') }}" name="position" id="selectPosition" min="1">
                            @error('position')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input shadow-none" type="checkbox" id="ageRestrictedCheckbox"
                                    name="age_restricted" value="1"
                                    {{ old('age_restricted', '1') == '1' ? 'checked' : '' }}
                                    @error('age_restricted') is-invalid @enderror>
                                <label class="form-check-label text-uppercase" for="ageRestrictedCheckbox">
                                    Age Restricted Event (Must be 21)
                                </label>
                                @error('age_restricted')
                                    <div class="text-danger w-100">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input shadow-none" type="checkbox" id="recapNeeded"
                                    name="recap_needed" value="1"
                                    {{ old('recap_needed', '1') == '1' ? 'checked' : '' }}
                                    @error('recap_needed') is-invalid @enderror>
                                <label class="form-check-label text-uppercase" for="recapNeeded">
                                    Require a recap for this event
                                </label>
                                @error('recap_needed')
                                    <div class="text-danger w-100">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- <div class="col-md-12">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input shadow-none" type="checkbox" id="eventAvailableCheckbox" checked
                                    name="apply_event" @error('apply_event') is-invalid @enderror>
                                <label class="form-check-label text-uppercase" for="eventAvailableCheckbox">
                                    Event available for online application (Ambassadors can apply online.<span
                                        style="color: var(--negative-color)">Automatically turned off once staffed</span>)
                                </label>
                                @error('apply_event')
                                    <div class="text-danger w-100">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div> --}}
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="selectDistributors"
                                class="form-label text-uppercase field-required">Distributor</label>
                            <select class="form-select shadow-none @error('distributor_id') is-invalid @enderror"
                                id="selectDistributors" aria-label="Distributors" name="distributor_id">
                                <option value="">Select</option>
                                @foreach ($distributors as $distributor)
                                    <option value="{{ $distributor->id }}"
                                        @if (old('distributor_id') == $distributor->id) selected @endif>
                                        {{ $distributor->name }}</option>
                                @endforeach
                            </select>
                            @error('distributor_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="brand" class="form-label text-uppercase field-required">Brand</label>
                            <select class="form-select shadow-none @error('brand_id') is-invalid @enderror" id="brand"
                                aria-label="Brand" name="brand_id">
                                <option value="">Select</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}" @if (old('brand_id') == $brand->id) selected @endif>
                                        {{ $brand->brand_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('brand_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="selectMarket" class="form-label text-uppercase field-required">Markets</label>
                            <select
                                class="form-select shadow-none multiple-select2 @error('market_ids') is-invalid @enderror"
                                id="selectMarket" name="market_ids[]" multiple="multiple">
                                <option value="">Select</option>
                                @foreach ($markets as $market)
                                    <option value="{{ $market->id }}"
                                        @if (is_array(old('market_ids')) && in_array($market->id, old('market_ids'))) selected @endif>{{ $market->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('market_ids')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="selectManager" class="form-label text-uppercase field-required">Manager</label>
                            <select class="form-select shadow-none @error('manager_id') is-invalid @enderror"
                                id="selectManager" aria-label="Select Manager" name="manager_id"
                                value="{{ old('manager_id') }}">
                                @php
                                    $defaultManagerId =
                                        old('manager_id') ?:
                                        ((int) (auth()->user()->role_id ?? 0) === 6
                                            ? auth()->id()
                                            : null);
                                @endphp
                                <option value="">Select</option>
                                @foreach ($managers as $manager)
                                    <option value="{{ $manager->id }}"
                                        @if ($defaultManagerId == $manager->id) selected @endif>{{ $manager->name }}</option>
                                @endforeach
                            </select>
                            @error('manager_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!--<div class="col-md-6">-->
                    <!--    <div class="mb-3">-->
                    <!--        <label for="startDate/Time" class="form-label text-uppercase">Start Date/Time</label>-->
                    <!--        <input type="datetime-local"-->
                    <!--            class="form-control shadow-none @error('start_time')
is-invalid
@enderror"-->
                    <!--            value="{{ old('start_time') }}" name="start_time" id="startDateTime" step="300">-->
                    <!--        @error('start_time')
        -->
                        <!--            <div class="text-danger w-100">{{ $message }}</div>-->
                        <!--
    @enderror-->
                    <!--    </div>-->
                    <!--</div>-->

                    <!--<div class="col-md-6">-->
                    <!--    <div class="mb-3">-->
                    <!--        <label for="endDate/Time" class="form-label text-uppercase">End Date/Time</label>-->
                    <!--        <input type="datetime-local"-->
                    <!--            class="form-control shadow-none @error('end_time')
is-invalid
@enderror"-->
                    <!--            value="{{ old('end_time') }}" name="end_time" id="endDateTime" step="300">-->
                    <!--        @error('end_time')
        -->
                        <!--            <div class="text-danger w-100">{{ $message }}</div>-->
                        <!--
    @enderror-->
                    <!--    </div>-->
                    <!--</div>-->

                    <div class="row">
                        @include('admin.event.partials.timezone-field', [
                            'event' => new \App\Models\Event(),
                        ])

                        <div class="col-md-4 mb-3">
                            <label for="startDateTime" class="form-label text-uppercase field-required">Start
                                Date/Time</label>
                            <input type="text" id="startDateTime" name="start_time"
                                class="form-control shadow-none @error('start_time') is-invalid @enderror"
                                value="{{ old('start_time') }}">
                            @error('start_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="endDateTime" class="form-label text-uppercase field-required">End
                                Date/Time</label>
                            <input type="text" id="endDateTime" name="end_time"
                                class="form-control shadow-none @error('end_time') is-invalid @enderror"
                                value="{{ old('end_time') }}">
                            @error('end_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="varietals" class="form-label text-uppercase field-required">Varietals</label>
                            <textarea class="form-control shadow-none  @error('varietals') is-invalid @enderror" id="varietals"
                                placeholder="Event Varietals" name="varietals">{{ old('varietals') }}</textarea>
                            @error('varietals')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="notes" class="form-label text-uppercase field-required">Notes</label>
                            <textarea class="form-control shadow-none @error('notes') is-invalid @enderror" id="notes"
                                placeholder="Additional Event Notes" name="notes">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="internal_notes" class="form-label text-uppercase">Internal Notes (Visible only to
                                MM)</label>
                            <textarea class="form-control shadow-none @error('internal_notes') is-invalid @enderror" id="internal_notes"
                                name="internal_notes" placeholder="Notes visible only to Market Manager">{{ old('internal_notes') }}</textarea>
                            @error('internal_notes')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="mb-3">
                            <div class="form-check">
                                <!--<input class="form-check-input shadow-none" type="checkbox" value="" id="unpaid" name="payment_status">-->
                                <input class="form-check-input shadow-none" type="checkbox" value="1"
                                    id="unpaid" name="payment_status" {{ old('payment_status') ? 'checked' : '' }}>
                                <label class="form-check-label text-uppercase" for="unpaid">
                                    Unpaid
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Start of Fee Fields Wrapper -->
                    <div class="col-md-3" id="expediteFee">
                        <div class="mb-3">
                            <label for="expediteFee1" class="form-label text-uppercase">Expedite Fee</label>
                            <input type="text"
                                class="form-control shadow-none @error('expedite_fee') is-invalid @enderror"
                                value="{{ old('expedite_fee') }}" name="expedite_fee" id="expediteFee1">
                            @error('expedite_fee')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3" id="postageFee">
                        <div class="mb-3">
                            <label for="shipping/postageFee" class="form-label text-uppercase">Shipping/Postage
                                Fee</label>
                            <input type="text" class="form-control shadow-none" name="shipping_fee"
                                value="{{ old('shipping_fee') }}" id="shipping/postageFee">
                        </div>
                    </div>
                    <div class="col-md-3" id="parkingFee">
                        <div class="mb-3">
                            <label for="parkingFee" class="form-label text-uppercase">Parking Fee</label>
                            <input type="text" class="form-control shadow-none" name="parking_fee"
                                value="{{ old('parking_fee') }}">
                        </div>
                    </div>
                    <div class="col-md-3" id="drive-timePay">
                        <div class="mb-3">
                            <label for="drive-timePay" class="form-label text-uppercase">Drive-time pay / per
                                event</label>
                            <input type="text" class="form-control shadow-none" name="drive_time"
                                value="{{ old('drive_time') }}">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="paymentType" class="form-label text-uppercase field-required">Payment Type</label>
                            <select class="form-select shadow-none @error('payment_type') is-invalid @enderror"
                                id="paymentType" name="payment_type" data-clearable>
                                <option value="">Select</option>
                                @php
                                    $pt = old('payment_type');
                                    $ptNorm =
                                        [
                                            '1' => 'No purchase',
                                            '2' => 'Product on site',
                                            '3' => 'Comdata',
                                            '4' => 'Check',
                                            '5' => 'Reimbursement',
                                            'ComData' => 'Comdata',
                                        ][$pt] ?? $pt;
                                @endphp
                                <option value="No purchase" {{ $ptNorm === 'No purchase' ? 'selected' : '' }}>No purchase
                                </option>
                                <option value="Product on site" {{ $ptNorm === 'Product on site' ? 'selected' : '' }}>
                                    Product on site</option>
                                <option value="Comdata" {{ $ptNorm === 'Comdata' ? 'selected' : '' }}>Comdata</option>
                                <option value="Check" {{ $ptNorm === 'Check' ? 'selected' : '' }}>Check</option>
                                <option value="Reimbursement" {{ $ptNorm === 'Reimbursement' ? 'selected' : '' }}>
                                    Reimbursement</option>
                            </select>
                            @error('payment_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mt-4">
                            <div class="form-check">
                                <input class="form-check-input shadow-none  @error('receipt') is-invalid @enderror"
                                    type="checkbox" id="receiptRequired" value="1" name="receipt"
                                    {{ old('receipt') ? 'checked' : '' }}>
                                <label class="form-check-label text-uppercase" for="receiptRequired">
                                    Receipt Required
                                </label>
                                @error('receipt')
                                    <div class="text-danger w-100">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mt-4">
                            <div class="form-check">
                                <input class="form-check-input shadow-none  @error('kit') is-invalid @enderror"
                                    type="checkbox" id="kitRequired" value="1" name="kit"
                                    {{ old('kit') ? 'checked' : '' }}>
                                <label class="form-check-label text-uppercase" for="kitRequired">
                                    Kit Required
                                </label>

                                @error('kit')
                                    <div class="text-danger w-100">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6" id="additionalFields" style="display:none;">
                        <div class="mb-3">
                            <label for="kit_description" class="form-label text-uppercase">Kit Description</label>
                            <div class="input-group mb-3">
                                <input type="text"
                                    class="form-control shadow-none @error('kit_description') is-invalid @enderror"
                                    name="kit_description" id="kit_description" value="{{ old('kit_description') }}">
                            </div>
                            @error('kit_description')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="uniform" class="form-label text-uppercase">Uniform</label>
                            <div class="input-group mb-3">
                                <input type="text"
                                    class="form-control shadow-none @error('uniform') is-invalid @enderror"
                                    name="uniform" id="uniform" value="{{ old('uniform') }}">
                            </div>
                            @error('uniform')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        {{-- ───── Billing Parties ───── --}}
                        <div class="mb-3">
                            <label for="selectBillingParties" class="form-label fw-semibold">Billing Parties (Bill
                                To)</label>
                            <p class="small text-muted mb-1">Select one or more clients to invoice. One must be marked as
                                Primary — the Cup Charge always belongs to the Primary party.</p>
                            <select class="form-select shadow-none @error('billing_party_ids') is-invalid @enderror"
                                id="selectBillingParties" name="billing_party_ids[]" multiple data-clearable>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}"
                                        data-labor-rate="{{ $client->default_labor_amount ?? '' }}"
                                        @if (old('billing_party_ids') && in_array($client->id, old('billing_party_ids'))) selected
                                        @elseif (old('client_id') == $client->id) selected @endif>
                                        {{ $client->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="client_id" id="client_id" value="{{ old('client_id') }}">
                            @error('billing_party_ids')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-2 mb-2">
                            {{-- Primary Billing Party selector (always visible once ≥1 selected) --}}
                            <div class="col-md-6 mb-3" id="primaryBillingPartyWrap" style="display: none;">
                                <label for="primary_billing_party_id" class="form-label">Primary Billing Party</label>
                                <select class="form-select shadow-none" id="primary_billing_party_id"
                                    name="primary_billing_party_id">
                                    <option value="">Select primary</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}"
                                            @if (old('primary_billing_party_id') == $client->id) selected @endif>{{ $client->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted small mt-1">Cup Charge always belongs to the Primary Billing
                                    Party.</small>
                            </div>

                            {{-- Cup Charge (always belongs to Primary) --}}
                            <div class="col-md-6" id="cupsWrap" style="display:none;">
                                <label for="cups_amount" class="form-label small">Cup Charge ($) <span
                                        class="text-muted fw-normal small">(Primary party)</span></label>
                                <input type="number" step="0.01" min="0" name="cups_amount" id="cups_amount"
                                    class="form-control {{ $managerReadonlyClass }}" placeholder="0" value="{{ old('cups_amount') }}"
                                    {{ $managerReadonlyAttr }}>
                            </div>
                        </div>

                        {{-- Per-party Labor Rate rows --}}
                        <div id="billingPartyLaborRates" class="mb-3" style="display:none;">
                            <label class="form-label">Labor Rate per Billing Party</label>
                            <div id="laborRateRows">
                                {{-- rows injected by JS --}}
                            </div>
                        </div>

                        {{-- Buyback amount moved to recap forms; keep this field commented for future restore.
                        <div class="row g-2 mb-2">
                            <div class="col-md-6" id="buybackWrap" style="display:none;">
                                <label for="buyback_amount" class="form-label small">Buyback Amount ($)</label>
                                <input type="number" step="0.01" min="0" name="buyback_amount"
                                    id="buyback_amount" class="form-control" placeholder="0"
                                    value="{{ old('buyback_amount') }}">
                            </div>
                        </div>
                        --}}
                        {{-- Buyback helper text moved with event buyback field; keep commented for future restore.
                        <div id="buybackHelperText" class="text-muted small mt-1" style="display:none;">
                            When multiple Billing Parties are selected, this Buyback Amount will be split evenly across all
                            Billing Parties during QuickBooks invoicing.
                        </div>
                        --}}

                        {{-- Hidden: preserve split_cups=0 always (cups never split) --}}
                        <input type="hidden" name="split_cups" value="0">
                    </div>
                    @include('admin.event.partials.ambassador-staffing', [
                        'staffingRows' => [],
                        'eventPinataPay' => (bool) old('pinata_pay', false),
                        'staffingEventId' => null,
                        'routePrefix' => $routePrefix,
                    ])


                    <div class="col-md-5">
                        <div class="mb-3">
                            <label for="attachments" class="form-label text-uppercase">Attachments (0)</label>
                            <br />
                            <div style="gap: 20px;display: flex;">
                                <input type="radio" value="aras_document" name="att_option"
                                    onclick="document_view('show')" />Aras Documents
                            </div>
                            <div style="gap: 20px;display: flex;">
                                <input type="radio" value="upload_document" name="att_option"
                                    onclick="upload_local()" />Upload from Local
                            </div>
                            <input type="file" style="display:none;" class="form-control shadow-none"
                                name="attachments[]" id="attachments" placeholder="Select Documents..." multiple
                                accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.zip,.ppt,.pptx">
                        </div>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label text-uppercase d-block mb-2">BEST Integration</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input shadow-none" type="radio" name="integrate_with_best"
                                    id="normalEventRadio" value="0"
                                    {{ old('integrate_with_best', '0') != '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="normalEventRadio">Normal Event</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input shadow-none" type="radio" name="integrate_with_best"
                                    id="bestEventRadio" value="1"
                                    {{ old('integrate_with_best') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="bestEventRadio">BEST Event</label>
                            </div>
                        </div>
                        @error('integrate_with_best')
                            <div class="text-danger w-100">{{ $message }}</div>
                        @enderror
                        <input type="hidden" name="best_event_data" id="bestEventDataInput"
                            value="{{ old('best_event_data') }}">
                    </div>

                    <div id="preview" class="mb-3 d-flex flex-wrap gap-2"></div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="region" class="form-label field-required">Region</label>
                            <div class="d-flex gap-4">
                                <select class="form-select shadow-none  @error('region') is-invalid @enderror"
                                    id="region" name="region">
                                    <option value="" selected>Select Region</option>
                                    <option value="Southeast" @if (old('region') == 'Southeast') selected @endif>Southeast
                                    </option>
                                    <option value="Midwest" @if (old('region') == 'Midwest') selected @endif>Midwest
                                    </option>
                                </select>
                            </div>
                            @error('region')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="bookedDate" class="form-label text-uppercase field-required">Booked Date</label>
                            <input type="date"
                                class="form-control shadow-none @error('booked_date') is-invalid @enderror"
                                value="{{ old('booked_date') }}" name="booked_date" id="bookedDate">
                            @error('booked_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="bookedBy" class="form-label text-uppercase field-required">Booked By</label>
                            @php
                                $oldBookedBy = old('booked_by');
                                if (is_array($oldBookedBy)) {
                                    $bookedByInput = $oldBookedBy;
                                } else {
                                    $decodedBookedBy = json_decode((string) $oldBookedBy, true);
                                    $bookedByInput = is_array($decodedBookedBy)
                                        ? $decodedBookedBy
                                        : preg_split('/[,;\r\n]+/', (string) $oldBookedBy);
                                }
                                $bookedByInput = array_values(array_filter(array_map('trim', $bookedByInput ?? [])));
                            @endphp
                            <input type="text"
                                class="form-control shadow-none @error('booked_by') is-invalid @enderror"
                                name="booked_by" id="bookedBy" placeholder="Enter email(s), separated by commas"
                                value="{{ implode(', ', $bookedByInput) }}">
                            <!--<input type="text"-->
                            <!--    class="form-control shadow-none @error('booked_by')
is-invalid
@enderror"-->
                            <!--    name="booked_by" value="{{ Auth::user()->name }}" id="bookedBy" readonly>-->
                        </div>
                        @error('booked_by')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="d-grid mx-3 mb-3">
                    <div class="col-md-3">
                        <button type="submit" class="btn-ds btn-primary-ds">Save</button>
                        <button type="button" class="btn-ds btn-back" onclick="window.history.back()">Back</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    {{-- BEST Event Fields Modal --}}
    <div class="modal fade" id="bestFieldsModal" tabindex="-1" aria-labelledby="bestFieldsModalLabel"
        aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bestFieldsModalLabel">BEST Event Configuration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        id="bestModalClose"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">All fields are required for BEST integration. These values will be
                        pre-filled in the ambassador recap form.</p>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-uppercase">Status</label>
                            <select class="form-select shadow-none" id="best_status" required>
                                <option value="">---Select Status---</option>
                                <option value="Planned">Planned</option>
                                <option value="Completed">Completed</option>
                                <option value="Canceled">Canceled</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-uppercase">Target Audience</label>
                            <select class="form-select shadow-none" id="best_target_audience" required>
                                <option value="">---Select Target Audience---</option>
                                <option value="Consumer">Consumer</option>
                                <option value="Trade">Trade</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-uppercase">Account</label>
                            <div class="typeahead-container position-relative">
                                <input type="text" class="form-control shadow-none best-typeahead-input"
                                    id="best_account_name" data-target-id="best_account_id"
                                    data-api="/{{ (int) (auth()->user()->role_id ?? 0) === 6 ? 'manager' : 'admin' }}/best-get-account"
                                    placeholder="Start typing account name..." autocomplete="off">
                                <input type="hidden" id="best_account_id">
                                <div class="typeahead-dropdown best-typeahead-dropdown"
                                    style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #ced4da;border-top:none;border-radius:0 0 .375rem .375rem;max-height:200px;overflow-y:auto;z-index:1050;">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-uppercase">Associated with Demand Creation Station</label>
                            <select class="form-select shadow-none" id="best_demand_creation" required>
                                <option value="">---Select---</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-uppercase">Wet or Dry Event</label>
                            <div class="d-flex gap-4 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="best_wet_or_dry" id="best_wet"
                                        value="Wet">
                                    <label class="form-check-label" for="best_wet">Wet</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="best_wet_or_dry" id="best_dry"
                                        value="Dry">
                                    <label class="form-check-label" for="best_dry">Dry</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-uppercase">Sampling Type</label>
                            <select class="form-select shadow-none" id="best_sampling_type" required>
                                <option value="">---Sampling Type---</option>
                                <option value="Single Brand Sampling">Single Brand Sampling</option>
                                <option value="Multi Brand Sampling">Multi Brand Sampling</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-uppercase">Objective</label>
                            <textarea class="form-control shadow-none" id="best_objective" rows="3" placeholder="Objective"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-uppercase">Description</label>
                            <textarea class="form-control shadow-none" id="best_description" rows="3" placeholder="Description"></textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-uppercase">Attendees</label>
                            <input type="number" class="form-control shadow-none" id="best_attendees" min="0"
                                value="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-uppercase">Interactions</label>
                            <input type="number" class="form-control shadow-none" id="best_interactions" min="0"
                                value="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-uppercase">Budget</label>
                            <input type="number" class="form-control shadow-none" id="best_budget" min="0"
                                value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-uppercase">Event Results</label>
                            <textarea class="form-control shadow-none" id="best_event_results" rows="3" placeholder="Event results"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-uppercase">Key Learnings &amp; Future Considerations</label>
                            <textarea class="form-control shadow-none" id="best_key_learnings" rows="3" placeholder="Key learnings"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-uppercase">Consumer Feedback</label>
                            <textarea class="form-control shadow-none" id="best_consumer_feedback" rows="3"
                                placeholder="Consumer feedback"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-uppercase">Account Feedback</label>
                            <textarea class="form-control shadow-none" id="best_account_feedback" rows="3" placeholder="Account feedback"></textarea>
                        </div>

                        <div class="col-12">
                            <hr>
                            <h6 class="text-uppercase fw-bold">Consumer Event Samplings</h6>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label text-uppercase">Account Premise Type</label>
                            <select class="form-select shadow-none" id="best_account_premise_type" required>
                                @foreach ($eventTypes as $type)
                                    <option value="{{ $type->name }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-uppercase">Program Name</label>
                            <div class="typeahead-container position-relative">
                                <input type="text" class="form-control shadow-none best-typeahead-input"
                                    id="best_program_name" data-target-id="best_program_id"
                                    data-api="/{{ (int) (auth()->user()->role_id ?? 0) === 6 ? 'manager' : 'admin' }}/best-get-program"
                                    placeholder="Start typing program name..." autocomplete="off">
                                <input type="hidden" id="best_program_id">
                                <div class="typeahead-dropdown best-typeahead-dropdown"
                                    style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #ced4da;border-top:none;border-radius:0 0 .375rem .375rem;max-height:200px;overflow-y:auto;z-index:1050;">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-uppercase">Tag</label>
                            <div class="typeahead-container position-relative">
                                <input type="text" class="form-control shadow-none best-typeahead-input"
                                    id="best_tag_name" data-target-id="best_tag_id"
                                    data-api="/{{ (int) (auth()->user()->role_id ?? 0) === 6 ? 'manager' : 'admin' }}/best-get-tags"
                                    placeholder="Start typing tag name..." autocomplete="off">
                                <input type="hidden" id="best_tag_id">
                                <div class="typeahead-dropdown best-typeahead-dropdown"
                                    style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #ced4da;border-top:none;border-radius:0 0 .375rem .375rem;max-height:200px;overflow-y:auto;z-index:1050;">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label text-uppercase">Total Bottles Sold 1L</label>
                            <input type="number" class="form-control shadow-none" id="best_1l_bottles" min="0"
                                value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label text-uppercase">Total Bottles Sold 1.75L</label>
                            <input type="number" class="form-control shadow-none" id="best_175l_bottles" min="0"
                                value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label text-uppercase">Total Bottles Sold 750ML</label>
                            <input type="number" class="form-control shadow-none" id="best_750ml_bottles"
                                min="0" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label text-uppercase">Total Bottles Sold 375ML</label>
                            <input type="number" class="form-control shadow-none" id="best_375ml_bottles"
                                min="0" value="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-uppercase">Total Samples Distributed</label>
                            <input type="number" class="form-control shadow-none" id="best_total_samples"
                                min="0" value="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-uppercase">Total Samples Distributed Cocktail</label>
                            <input type="number" class="form-control shadow-none" id="best_total_samples_cocktail"
                                min="0" value="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-uppercase">Total Samples Distributed Neat Shot</label>
                            <input type="number" class="form-control shadow-none" id="best_total_samples_neat_shot"
                                min="0" value="0">
                        </div>

                        <div class="col-12">
                            <hr>
                            <h6 class="text-uppercase fw-bold">Consumer Event Lengths</h6>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label text-uppercase">End Date</label>
                            <input type="date" class="form-control shadow-none" id="best_end_date">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-uppercase">Start Time</label>
                            <input type="time" class="form-control shadow-none" id="best_start_time">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-uppercase">End Time</label>
                            <input type="time" class="form-control shadow-none" id="best_end_time">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-ds btn-secondary-ds" data-bs-dismiss="modal"
                        id="bestModalCancel">Cancel</button>
                    <button type="button" class="btn-ds btn-primary-ds" id="bestModalSave">Save BEST
                        Configuration</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="myModal" role="dialog">
        <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Documents</h4>
                </div>
                <div class="modal-body" id="arasDocumentsModalBody">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-ds btn-primary-ds"
                        onclick="selected_files()">Submit</button>
                    <button type="button" class="btn-ds btn-danger-ds"
                        onclick="document_view('hide')">Close</button>
                </div>
            </div>

        </div>
    </div>
@endsection
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/luxon@3.4.4/build/global/luxon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const {
                DateTime
            } = luxon;
            const timezoneInput = document.getElementById("timezone");
            const startInput = document.getElementById("startDateTime");
            const endInput = document.getElementById("endDateTime");
            const startError = document.getElementById("startDateTimeError");
            const endError = document.getElementById("endDateTimeError");

            let startPicker, endPicker;

            function setError(el, msg) {
                if (!el) return;
                el.textContent = msg || "";
                el.style.display = msg ? "block" : "none";
            }

            // Round to nearest 5 minutes
            function roundToNearest5(date) {
                const ms = 1000 * 60 * 5;
                return new Date(Math.ceil(date.getTime() / ms) * ms);
            }

            function parseDateInZone(value, zone) {
                if (!value) return null;
                // Parse as naive wall-clock string then assign the event timezone
                // WITHOUT shifting the time. Passing { zone } to fromSQL/fromFormat
                // makes Luxon treat the string as UTC and convert it, causing a
                // 1-hour shift per UTC-offset difference (EST user + CST event).
                let dt = DateTime.fromSQL(value).setZone(zone, { keepLocalTime: true });
                if (!dt.isValid) dt = DateTime.fromFormat(value, "yyyy-MM-dd HH:mm").setZone(zone, { keepLocalTime: true });
                if (!dt.isValid) dt = DateTime.fromFormat(value, "yyyy-MM-dd HH:mm:ss").setZone(zone, { keepLocalTime: true });
                if (!dt.isValid) dt = DateTime.fromISO(value).setZone(zone, { keepLocalTime: true });
                return dt.isValid ? dt.toJSDate() : null;
            }

            // Get current + adjusted times
            function getTimes(zone) {
                const parsedStart = parseDateInZone(startInput.value, zone);
                const parsedEnd = parseDateInZone(endInput.value, zone);
                if (parsedStart && parsedEnd) {
                    return {
                        start: roundToNearest5(parsedStart),
                        end: roundToNearest5(parsedEnd)
                    };
                }

                const nowUTC = DateTime.utc();
                const nowInZone = nowUTC.setZone(zone);
                const rounded = roundToNearest5(nowInZone.toJSDate());
                const twoHoursLater = new Date(rounded.getTime() + 2 * 60 * 60 * 1000);
                return {
                    start: rounded,
                    end: twoHoursLater
                };
            }

            // Initialize Pickers
            function initPickers(zone) {
                const {
                    start,
                    end
                } = getTimes(zone);

                if (startPicker) startPicker.destroy();
                if (endPicker) endPicker.destroy();

                // If we're duplicating/editing via old input, allow past dates.
                const hasPrefill = !!(startInput.value || endInput.value);

                startPicker = flatpickr(startInput, {
                    enableTime: true,
                    dateFormat: "Y-m-d H:i",
                    altInput: true,
                    altFormat: "m/d/Y h:i K",
                    altInputClass: startInput.className,
                    time_24hr: false,
                    minuteIncrement: 5,
                    defaultDate: start,
                    minDate: hasPrefill ? null : "today",
                    onChange: function(selectedDates) {
                        const startDate = selectedDates[0];
                        if (!hasPrefill) {
                            const now = new Date();
                            if (startDate < now) {
                                setError(startError, "Start time cannot be in the past.");
                                startPicker.setDate(start, true);
                                return;
                            }
                            setError(startError, "");
                        }

                        if (typeof window.refreshEventTimezoneFromCoordinates === 'function') {
                            window.refreshEventTimezoneFromCoordinates();
                        }

                        const newEnd = new Date(startDate.getTime() + 2 * 60 * 60 * 1000);
                        endPicker.set("minDate", startDate);
                        endPicker.setDate(newEnd, true);
                    },
                });

                endPicker = flatpickr(endInput, {
                    enableTime: true,
                    dateFormat: "Y-m-d H:i",
                    altInput: true,
                    altFormat: "m/d/Y h:i K",
                    altInputClass: endInput.className,
                    time_24hr: false,
                    minuteIncrement: 5,
                    defaultDate: end,
                    minDate: start,
                    onChange: function(selectedDates) {
                        const endDate = selectedDates[0];
                        const startDate = startPicker.selectedDates[0];
                        if (startDate && endDate <= startDate) {
                            setError(endError, "End time must be after start time.");
                            const newEnd = new Date(startDate.getTime() + 2 * 60 * 60 * 1000);
                            endPicker.setDate(newEnd, true);
                            return;
                        }
                        setError(endError, "");
                    },
                });
            }

            function currentEventZone() {
                return (timezoneInput && timezoneInput.value) ? timezoneInput.value : "UTC";
            }

            window.onEventTimezoneResolved = function(zone) {
                initPickers(zone || currentEventZone());
            };

            initPickers(currentEventZone());
        });

        function toggleDocumentsModal(type) {
            const modalEl = document.getElementById('myModal');
            if (!modalEl) return;

            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalEl)[type]();
                return;
            }

            if (typeof jQuery !== 'undefined' && typeof jQuery.fn.modal === 'function') {
                jQuery(modalEl).modal(type);
            }
        }

        function document_view(type) {
            toggleDocumentsModal(type)
            if (type == 'show') {
                const $documentsBody = jQuery('#arasDocumentsModalBody');
                $documentsBody.html('<div class="text-muted">Loading documents...</div>');
                $.ajax({
                    type: 'GET',
                    url: '{{ url('/' . $routePrefix . '/getDocuments') }}',
                    success: function(data) {
                        var html = "<ul style='list-style-type: none;display: grid;gap: 10px;'> ";
                        if (data.length > 0) {
                            jQuery(data).each(function(key, element) {
                                html += '<li style="display: flex;gap: 10px;">';
                                html += '<input name="selected_one[]" type="checkbox" value="' + element
                                    .url + '"><span>' + element.file_name + '</span>';
                                html += '</li>';
                            })
                        } else {
                            html += '<li class="text-muted">No Aras documents available.</li>';
                        }
                        html += "</ul>";
                        $documentsBody.html(html);
                    },
                    error: function(res) {
                        console.log(res);
                        $documentsBody.html('<div class="text-danger">Unable to load documents.</div>');
                    }
                })
            }
        }
        async function selected_files() {
            var selectedValues = $('input[name="selected_one[]"]:checked').map(function() {
                return this.value; // Return the value of each checked checkbox
            }).get();
            if (selectedValues.length > 0) {
                const fileInput = document.getElementById('attachments');
                const dataTransfer = new DataTransfer();

                try {
                    const filePromises = selectedValues.map(async (url, index) => {
                        const response = await fetch(url);
                        const blob = await response.blob();
                        const fileName = url.split('/').pop() || `file-${index}`;
                        return new File([blob], fileName, {
                            type: blob.type
                        });
                    });

                    const files = await Promise.all(filePromises);

                    // 2. Add each file to the DataTransfer object
                    files.forEach(file => dataTransfer.items.add(file));

                    // 3. Update the input element
                    fileInput.files = dataTransfer.files;

                    // Trigger change event to notify other scripts
                    fileInput.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));

                    // Add new files (prevent duplicates)
                    console.log($('#attachments').prop('files'));
                    toggleDocumentsModal('hide')

                } catch (error) {
                    console.error('Failed to load one or more files:', error);
                }
            } else {
                console.log('Please check any file before submit');
                return false;
            }
        }

        function upload_local() {
            jQuery('input[name="attachments[]"]').trigger('click');
        }
    </script>


    <script>
        $(document).ready(function() {
            $("#event_type_id").select2();
            $("#venue_name").select2();
            {{-- $("#selectType").select2(); --}}
            $("#selectDepartment").select2();
            $("#selectDistributors").select2();
            $("#selectDepartment").select2();
            $("#selectDistributors").select2();
            {{-- $("#selectType").select2(); --}}
            $("#selectMarket").select2();
            $("#selectManager").select2();
            $("#brand").select2();
            $("#paymentType").select2();
            $("#selectBillingParties").select2({
                placeholder: 'Select one or more billing parties',
                allowClear: true
            });
            $("#primary_billing_party_id").select2({
                placeholder: 'Select primary',
                allowClear: true
            });

            function currentPrefix() {
                const parts = window.location.pathname.split('/').filter(Boolean);
                return parts[0] || 'admin';
            }

            function setBillingOptions(clients) {
                const $billing = $("#selectBillingParties");
                const $primary = $("#primary_billing_party_id");
                const selectedBilling = $billing.val() || [];
                const selectedPrimary = $primary.val();

                $billing.empty();
                $primary.empty();
                $primary.append(new Option('Select primary', '', false, false));

                clients.forEach(c => {
                    const id = String(c.id);
                    const name = c.name || '';
                    $billing.append(new Option(name, id, false, selectedBilling.includes(id)));
                    $primary.append(new Option(name, id, false, String(selectedPrimary) === id));
                });

                $billing.trigger('change.select2');
                $primary.trigger('change.select2');
                updateBillingPartyUi();
            }

            function loadBillToOptionsForBrand(brandId) {
                if (!brandId) {
                    // Keep current options when no brand is selected.
                    return;
                }
                const prefix = currentPrefix();
                $.ajax({
                    url: `/${prefix}/brands/${brandId}/bill-to-options`,
                    method: 'GET',
                    success: function(resp) {
                        if (resp && Array.isArray(resp.clients)) {
                            setBillingOptions(resp.clients);
                        }
                    },
                    error: function() {
                        // Fail open: keep existing options.
                        console.warn('Failed to load bill-to options for brand');
                    }
                });
            }

            const billingDefaultsUrlTemplate = @json($billingDefaultsUrlTemplate);

            // Cache: clientId -> { labor_amount, buyback_amount, cups_amount }
            const billingDefaultsCache = {};

            function fetchBillingDefaults(clientId, callback) {
                if (!clientId || !billingDefaultsUrlTemplate) return;
                if (billingDefaultsCache[clientId] !== undefined) {
                    callback(billingDefaultsCache[clientId]);
                    return;
                }
                const url = billingDefaultsUrlTemplate.replace('__CLIENT__', String(clientId));
                $.ajax({
                    url: url,
                    method: 'GET',
                    success: function(resp) {
                        if (!resp || !resp.success) return;
                        billingDefaultsCache[clientId] = resp;
                        callback(resp);
                    },
                    error: function() {
                        console.warn('Failed to load billing defaults for client', clientId);
                    }
                });
            }

            // Build per-party labor rate rows from currently selected billing parties
            function rebuildLaborRateRows() {
                var $select = $('#selectBillingParties');
                var selectedIds = $select.val() || [];
                var $container = $('#laborRateRows');
                var primaryId = String($('#primary_billing_party_id').val() || '');

                // Preserve existing entered values keyed by client id
                var existing = {};
                $container.find('.billing-party-labor-row').each(function() {
                    var cid = $(this).data('client-id');
                    var val = $(this).find('input[name]').val();
                    existing[cid] = val;
                });

                $container.empty();

                if (selectedIds.length === 0) {
                    $('#billingPartyLaborRates').hide();
                    $('#buybackWrap').hide();
                    $('#cupsWrap').hide();
                    return;
                }

                $('#billingPartyLaborRates').show();
                $('#buybackWrap').show();
                $('#cupsWrap').show();

                selectedIds.forEach(function(clientId) {
                    var $option = $select.find('option[value="' + clientId + '"]');
                    var name = $option.text().trim();
                    var isPrimary = String(clientId) === primaryId;
                    var rowId = 'labor_rate_' + clientId;
                    var savedVal = existing[clientId] !== undefined ? existing[clientId] : '';

                    var $row = $(
                            '<div class="d-flex align-items-center gap-2 mb-2 billing-party-labor-row p-2 border rounded bg-light"></div>'
                        )
                        .attr('data-client-id', clientId);

                    var badge = isPrimary ?
                        '<span class="badge bg-primary me-1">Primary</span>' :
                        '';

                    $row.html(
                        '<div class="flex-grow-1 fw-semibold">' + badge + name + '</div>' +
                        '<div class="d-flex align-items-center gap-1">' +
                        '<label for="' + rowId +
                        '" class="form-label mb-0 small text-nowrap">Labor Rate ($)</label>' +
                        '<input type="number" step="0.01" min="0" id="' + rowId + '"' +
                        '    name="labor_rates[' + clientId + ']"' +
                        '    class="form-control {{ $managerReadonlyClass }}" style="width:110px;" placeholder="0"' +
                        '    value="' + (savedVal !== '' ? savedVal : '') + '" {{ $managerReadonlyAttr }}>' +
                        '</div>'
                    );

                    $container.append($row);

                    // Auto-fill from billing defaults if no saved value
                    if (savedVal === '') {
                        fetchBillingDefaults(clientId, function(resp) {
                            var rate = resp.labor_amount !== null && resp.labor_amount !==
                                undefined ?
                                resp.labor_amount : '';
                            $('#' + rowId).val(rate);
                        });
                    }
                });
            }

            // Auto-fill Cup Charge from Primary Billing Party's configured cups_amount
            function applyPrimaryCupCharge(primaryClientId) {
                if (!primaryClientId) return;
                fetchBillingDefaults(primaryClientId, function(resp) {
                    if (resp.cups_amount !== null && resp.cups_amount !== undefined) {
                        $('#cups_amount').val(resp.cups_amount);
                    }
                });
            }

            function updateBuybackHelperText(ids) {
                $('#buybackHelperText').toggle(ids.length > 1);
            }

            function updateBillingPartyUi() {
                var ids = $('#selectBillingParties').val() || [];
                // Always show primary selector once >= 1 party selected
                $('#primaryBillingPartyWrap').toggle(ids.length > 0);
                var primary = $('#primary_billing_party_id').val();

                if (ids.length === 1) {
                    $('#client_id').val(ids[0]);
                    $('#primary_billing_party_id').val(ids[0]).trigger('change.select2');
                } else if (ids.length > 1) {
                    if (!ids.includes(primary)) {
                        $('#primary_billing_party_id').val(ids[0]).trigger('change.select2');
                    }
                    $('#client_id').val($('#primary_billing_party_id').val());
                } else {
                    $('#client_id').val('');
                    $('#primary_billing_party_id').val('').trigger('change.select2');
                }

                rebuildLaborRateRows();
                updateBuybackHelperText(ids);

                // Auto-fill Cup Charge from primary
                var primaryId = $('#client_id').val();
                if (primaryId) applyPrimaryCupCharge(primaryId);
            }

            function onBillingSelectionChanged() {
                updateBillingPartyUi();
            }

            $('#selectBillingParties').on('change select2:select select2:unselect', onBillingSelectionChanged);
            $('#primary_billing_party_id').on('change', function() {
                var val = $(this).val();
                $('#client_id').val(val);
                rebuildLaborRateRows();
                // Update Cup Charge when Primary changes
                if (val) applyPrimaryCupCharge(val);
            });

            // Brand → restrict bill-to options
            $('#brand').on('change', function() {
                loadBillToOptionsForBrand($(this).val());
            });
            // initial load
            loadBillToOptionsForBrand($('#brand').val());
            updateBillingPartyUi();
            const initialBillToId = $('#client_id').val();
            if (initialBillToId) {
                fetchBillingDefaults(initialBillToId, function(resp) {
                    if (resp.buyback_amount !== null && resp.buyback_amount !== undefined)
                        $('#buyback_amount').val(resp.buyback_amount);
                    if (resp.cups_amount !== null && resp.cups_amount !== undefined)
                        $('#cups_amount').val(resp.cups_amount);
                });
            }

            $('#venue_name').on('change', function() {
                var venueId = $(this).val();
                var venueLatitude = $('option:selected', this).data('latitude');
                var venueLongitude = $('option:selected', this).data('longitude');

                // Clear any previous nearby-ambassador suggestions.
                $('#searchResults').addClass('d-none').html('');

                // Skip the API call when the selected venue has no coordinates — the
                // controller would return an empty list anyway, and sending empty values
                // to PHP 8+ causes a deg2rad() TypeError.
                if (!venueId || !venueLatitude || !venueLongitude) {
                    return;
                }

                $.ajax({
                    url: '{{ route($routePrefix . '.fetch-nearby-ambassadors') }}',
                    method: 'GET',
                    data: {
                        venue_id: venueId,
                        venue_latitude: venueLatitude,
                        venue_longitude: venueLongitude
                    },
                    success: function(response) {
                        if (response.success && response.ambassadors && response.ambassadors
                            .length > 0) {
                            // Show nearby ambassadors in the staffing search-results panel so
                            // the user can quickly click to add them — same format as the
                            // manual search powered by ambassador-staffing.js.
                            var resultHtml =
                                '<ul class="list-group w-100 mb-0 position-absolute shadow-sm">';
                            $.each(response.ambassadors, function(i, ambassador) {
                                var displayName = ambassador.name || '';
                                resultHtml +=
                                    '<li style="cursor:pointer;" class="list-group-item ambassador-item"' +
                                    ' data-id="' + (ambassador.id || '') + '"' +
                                    ' data-name="' + displayName + '"' +
                                    ' data-email="' + (ambassador.email || '') + '"' +
                                    ' data-phone="' + (ambassador.phone || '') + '"' +
                                    ' data-wage="' + (ambassador.wage || '0.00') + '"' +
                                    ' data-profile="' + (ambassador.profile_image ||
                                        '') + '">' +
                                    displayName +
                                    (ambassador.email ? ' — ' + ambassador.email : '') +
                                    '</li>';
                            });
                            resultHtml += '</ul>';
                            $('#searchResults').html(resultHtml).removeClass('d-none');
                        }
                        // No ambassadors nearby — just stay silent; no blocking alert.
                    },
                    error: function(xhr) {
                        // Non-blocking: log to console instead of an alert so the
                        // user can continue creating the event without interruption.
                        console.warn('[fetch-nearby-ambassadors] Request failed:', xhr.status,
                            xhr.statusText);
                    }
                });
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            $('#kitRequired').on('change', function() {
                console.log(jQuery(this).prop('checked'));
                if (jQuery(this).prop('checked')) {
                    jQuery('#additionalFields').show();
                } else {
                    jQuery('#additionalFields').hide();
                }
            })
            // Keep ambassador selections consistent across scripts.
            window._eventFormState = window._eventFormState || {
                selectedAmbassadorIds: [],
                overridePayMap: {},
                additionalPayReasonMap: {},
                additionalPayNoteMap: {},
            };
            let selectedAmbassadorIds = window._eventFormState.selectedAmbassadorIds;
            let overridePayMap = window._eventFormState.overridePayMap;
            let additionalPayReasonMap = window._eventFormState.additionalPayReasonMap;
            let additionalPayNoteMap = window._eventFormState.additionalPayNoteMap;
        });
    </script>

    <script>
        let selectedFiles = [];

        $('#attachments').on('change', function() {
            const newFiles = Array.from(this.files);
            newFiles.forEach(file => {
                if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    selectedFiles.push(file);
                }
            });
            this.value = '';
            $('label[for="attachments"]').text(`Attachments (${selectedFiles.length})`);
            renderPreview();
        });

        function renderPreview() {
            const preview = $('#preview');
            preview.html('');

            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const wrapper = $('<div>').css({
                        position: 'relative',
                        width: '120px',
                        textAlign: 'center'
                    });

                    const closeBtn = $('<span>&times;</span>').css({
                        position: 'absolute',
                        top: '0',
                        right: '5px',
                        background: '#dc3545',
                        color: '#fff',
                        borderRadius: '50%',
                        width: '20px',
                        height: '20px',
                        textAlign: 'center',
                        cursor: 'pointer',
                        zIndex: 10,
                        fontWeight: 'bold'
                    }).attr('data-index', index);

                    closeBtn.on('click', function() {
                        const removeIndex = $(this).data('index');
                        selectedFiles.splice(removeIndex, 1);
                        updateInputFiles();
                        renderPreview();
                        $('label[for="attachments"]').text(`Attachments (${selectedFiles.length})`);
                    });

                    let media;
                    if (file.type.startsWith('image/')) {
                        media = $('<img>').attr('src', e.target.result).css({
                            width: '100px',
                            height: '100px',
                            objectFit: 'cover',
                            borderRadius: '8px'
                        });
                    } else if (file.type.startsWith('video/')) {
                        media = $('<video controls>').css({
                            width: '120px',
                            height: '100px'
                        }).append($('<source>').attr('src', e.target.result).attr('type', file.type));
                    } else {
                        media = $('<div>').css({
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            justifyContent: 'center',
                            width: '100px',
                            height: '100px',
                            border: '1px solid #ddd',
                            borderRadius: '8px',
                            background: '#f8f9fa'
                        }).append(
                            $('<i class="fa-solid fa-file"></i>').css({
                                fontSize: '36px',
                                color: '#007bff',
                                marginBottom: '5px'
                            }),
                            $('<span>').text(file.name).css({
                                fontSize: '12px',
                                whiteSpace: 'nowrap',
                                overflow: 'hidden',
                                textOverflow: 'ellipsis',
                                width: '90px'
                            })
                        );
                    }

                    wrapper.append(closeBtn).append(media);
                    preview.append(wrapper);
                };

                if (file.type.startsWith('image/') || file.type.startsWith('video/')) {
                    reader.readAsDataURL(file);
                } else {
                    reader.onload();
                }
            });
        }

        function updateInputFiles() {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => dataTransfer.items.add(file));
            document.getElementById('attachments').files = dataTransfer.files;
        }

        // ✅ Ensure latest files are attached before form submit
        $('form').on('submit', function() {
            updateInputFiles();
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const unpaidCheckbox = document.getElementById("unpaid");
            const expediteFee = document.getElementById("expediteFee");
            const postageFee = document.getElementById("postageFee");
            const parkingFee = document.getElementById("parkingFee");
            const drivePay = document.getElementById("drive-timePay");

            function toggleFeeFields() {
                if (unpaidCheckbox.checked) {
                    expediteFee.style.display = "none";
                    postageFee.style.display = "none";
                    parkingFee.style.display = "none";
                    drivePay.style.display = "none";
                } else {
                    expediteFee.style.display = "flex";
                    postageFee.style.display = "flex";
                    parkingFee.style.display = "flex";
                    drivePay.style.display = "flex";
                }
            }
            unpaidCheckbox.addEventListener("change", toggleFeeFields);
            toggleFeeFields();
        });
    </script>

    <script>
        $(document).ready(function() {
            const bestFieldKeys = [
                'status', 'target_audience', 'account_id', 'account_name',
                'Associated_with_Demand_Creation_Station', 'wet_or_dry',
                'objective', 'description', 'sampling_type',
                'attendees', 'interactions', 'event_results',
                'key_learnings_future_considerations', 'consumer_feedback',
                'account_feedback', 'budget', 'Account_Premise_Type',
                'program_id', 'program_name', 'tag_id', 'tag_name',
                '1l_bottles', '175l_bottles', '750ml_bottles', '375ml_bottles',
                'total_samples_distributed', 'total_samples_distributed_cocktail',
                'total_samples_distributed_neat_shot',
                'end_date', 'start_time', 'end_time'
            ];

            const fieldMap = {
                'status': '#best_status',
                'target_audience': '#best_target_audience',
                'account_id': '#best_account_id',
                'account_name': '#best_account_name',
                'Associated_with_Demand_Creation_Station': '#best_demand_creation',
                'wet_or_dry': '[name="best_wet_or_dry"]:checked',
                'objective': '#best_objective',
                'description': '#best_description',
                'sampling_type': '#best_sampling_type',
                'attendees': '#best_attendees',
                'interactions': '#best_interactions',
                'event_results': '#best_event_results',
                'key_learnings_future_considerations': '#best_key_learnings',
                'consumer_feedback': '#best_consumer_feedback',
                'account_feedback': '#best_account_feedback',
                'budget': '#best_budget',
                'Account_Premise_Type': '#best_account_premise_type',
                'program_id': '#best_program_id',
                'program_name': '#best_program_name',
                'tag_id': '#best_tag_id',
                'tag_name': '#best_tag_name',
                '1l_bottles': '#best_1l_bottles',
                '175l_bottles': '#best_175l_bottles',
                '750ml_bottles': '#best_750ml_bottles',
                '375ml_bottles': '#best_375ml_bottles',
                'total_samples_distributed': '#best_total_samples',
                'total_samples_distributed_cocktail': '#best_total_samples_cocktail',
                'total_samples_distributed_neat_shot': '#best_total_samples_neat_shot',
                'end_date': '#best_end_date',
                'start_time': '#best_start_time',
                'end_time': '#best_end_time'
            };

            function collectBestData() {
                const data = {};
                for (const [key, selector] of Object.entries(fieldMap)) {
                    if (key === 'wet_or_dry') {
                        data[key] = $('[name="best_wet_or_dry"]:checked').val() || '';
                    } else {
                        data[key] = $(selector).val() || '';
                    }
                }
                return data;
            }

            function populateBestModal(data) {
                if (!data) return;
                for (const [key, selector] of Object.entries(fieldMap)) {
                    if (key === 'wet_or_dry') {
                        $('[name="best_wet_or_dry"]').prop('checked', false);
                        if (data[key]) $('[name="best_wet_or_dry"][value="' + data[key] + '"]').prop('checked',
                            true);
                    } else {
                        $(selector).val(data[key] || '');
                    }
                }
            }

            const fieldLabels = {
                'status': 'Status',
                'target_audience': 'Target Audience',
                'account_id': 'Account',
                'Associated_with_Demand_Creation_Station': 'Associated with Demand Creation Station',
                'wet_or_dry': 'Wet or Dry Event',
                'objective': 'Objective',
                'description': 'Description',
                'sampling_type': 'Sampling Type',
                'attendees': 'Attendees',
                'interactions': 'Interactions',
                'event_results': 'Event Results',
                'key_learnings_future_considerations': 'Key Learnings & Future Considerations',
                'consumer_feedback': 'Consumer Feedback',
                'account_feedback': 'Account Feedback',
                'budget': 'Budget',
                'Account_Premise_Type': 'Account Premise Type',
                'program_id': 'Program Name',
                'tag_id': 'Tag',
                '1l_bottles': 'Total Bottles Sold 1L',
                '175l_bottles': 'Total Bottles Sold 1.75L',
                '750ml_bottles': 'Total Bottles Sold 750ML',
                '375ml_bottles': 'Total Bottles Sold 375ML',
                'total_samples_distributed': 'Total Samples Distributed',
                'total_samples_distributed_cocktail': 'Total Samples Distributed Cocktail',
                'total_samples_distributed_neat_shot': 'Total Samples Distributed Neat Shot',
                'end_date': 'End Date',
                'start_time': 'Start Time',
                'end_time': 'End Time'
            };

            const fieldToElement = {
                'status': '#best_status',
                'target_audience': '#best_target_audience',
                'account_id': '#best_account_name',
                'Associated_with_Demand_Creation_Station': '#best_demand_creation',
                'wet_or_dry': '#best_wet',
                'objective': '#best_objective',
                'description': '#best_description',
                'sampling_type': '#best_sampling_type',
                'attendees': '#best_attendees',
                'interactions': '#best_interactions',
                'event_results': '#best_event_results',
                'key_learnings_future_considerations': '#best_key_learnings',
                'consumer_feedback': '#best_consumer_feedback',
                'account_feedback': '#best_account_feedback',
                'budget': '#best_budget',
                'Account_Premise_Type': '#best_account_premise_type',
                'program_id': '#best_program_name',
                'tag_id': '#best_tag_name',
                '1l_bottles': '#best_1l_bottles',
                '175l_bottles': '#best_175l_bottles',
                '750ml_bottles': '#best_750ml_bottles',
                '375ml_bottles': '#best_375ml_bottles',
                'total_samples_distributed': '#best_total_samples',
                'total_samples_distributed_cocktail': '#best_total_samples_cocktail',
                'total_samples_distributed_neat_shot': '#best_total_samples_neat_shot',
                'end_date': '#best_end_date',
                'start_time': '#best_start_time',
                'end_time': '#best_end_time'
            };

            function clearBestValidationErrors() {
                $('#bestFieldsModal .is-invalid').removeClass('is-invalid');
                $('#bestFieldsModal .best-field-error').remove();
            }

            function validateBestFields() {
                const required = Object.keys(fieldLabels);
                const data = collectBestData();
                const missing = required.filter(k => data[k] === undefined || data[k] === null || String(data[k])
                    .trim() === '');
                return missing;
            }

            function showBestValidationErrors(missingKeys) {
                clearBestValidationErrors();
                missingKeys.forEach(key => {
                    const selector = fieldToElement[key];
                    if (selector) {
                        const $el = $(selector);
                        $el.addClass('is-invalid');
                        if (!$el.next('.best-field-error').length) {
                            $el.after('<div class="best-field-error text-danger small mt-1">' + fieldLabels[
                                key] + ' is required</div>');
                        }
                    }
                });
            }

            // Show modal when BEST Event is selected
            $('input[name="integrate_with_best"]').on('change', function() {
                if ($(this).val() === '1' && $(this).is(':checked')) {
                    const existing = $('#bestEventDataInput').val();
                    if (existing) {
                        try {
                            populateBestModal(JSON.parse(existing));
                        } catch (e) {}
                    }
                    clearBestValidationErrors();
                    const bestModal = new bootstrap.Modal(document.getElementById('bestFieldsModal'));
                    bestModal.show();
                }
            });

            // Cancel resets to normal event
            $('#bestModalCancel, #bestModalClose').on('click', function() {
                clearBestValidationErrors();
                const currentData = $('#bestEventDataInput').val();
                if (!currentData) {
                    $('#normalEventRadio').prop('checked', true);
                }
            });

            // Save BEST configuration
            $('#bestModalSave').on('click', function() {
                clearBestValidationErrors();
                const missing = validateBestFields();
                if (missing.length > 0) {
                    showBestValidationErrors(missing);
                    const missingLabels = missing.map(k => fieldLabels[k]);
                    const $alert = $(
                        '<div class="alert alert-danger alert-dismissible fade show mt-2 best-field-error" role="alert">' +
                        '<strong>Please fill the following required fields:</strong><ul class="mb-0 mt-1">' +
                        missingLabels.map(l => '<li>' + l + '</li>').join('') +
                        '</ul><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'
                    );
                    $('#bestFieldsModal .modal-body').prepend($alert);
                    $('#bestFieldsModal .modal-body').scrollTop(0);
                    return;
                }
                const data = collectBestData();
                $('#bestEventDataInput').val(JSON.stringify(data));
                bootstrap.Modal.getInstance(document.getElementById('bestFieldsModal')).hide();
            });

            // Restore modal data on page load if old() has it
            const oldBestData = $('#bestEventDataInput').val();
            if (oldBestData) {
                try {
                    populateBestModal(JSON.parse(oldBestData));
                } catch (e) {}
            }

            // BEST modal typeahead
            class BestModalTypeahead {
                constructor($input) {
                    this.$input = $input;
                    this.$hiddenInput = $(`#${$input.data('target-id')}`);
                    this.$dropdown = $input.siblings('.best-typeahead-dropdown');
                    this.apiUrl = $input.data('api');
                    this.data = [];
                    this.debounceTimer = null;
                    this.init();
                }
                init() {
                    this.loadData();
                    this.bindEvents();
                }
                async loadData() {
                    try {
                        const response = await $.ajax({
                            url: this.apiUrl,
                            method: 'GET'
                        });
                        this.data = response || [];
                    } catch (e) {
                        console.error('BEST typeahead load error:', e);
                    }
                }
                bindEvents() {
                    this.$input.on('input', (e) => this.handleInput(e))
                        .on('focus', () => {
                            if (this.$input.val().trim().length >= 1) this.filterData(this.$input.val()
                                .trim());
                        })
                        .on('blur', () => setTimeout(() => this.$dropdown.hide(), 200));
                }
                handleInput(e) {
                    const query = e.target.value.trim();
                    this.$hiddenInput.val('');
                    clearTimeout(this.debounceTimer);
                    this.debounceTimer = setTimeout(() => {
                        if (query.length >= 1) this.filterData(query);
                        else this.$dropdown.hide();
                    }, 300);
                }
                filterData(query) {
                    const lower = query.toLowerCase();
                    const filtered = this.data.filter(item => item.Name && item.Name.toLowerCase().includes(
                        lower) && !item.IsDeleted);
                    this.renderDropdown(filtered.slice(0, 10));
                }
                renderDropdown(items) {
                    if (!items.length) {
                        this.$dropdown.html(
                            '<div style="padding:.5rem .75rem;color:#6c757d;font-style:italic;">No results found</div>'
                        ).show();
                        return;
                    }
                    const html = items.map(item =>
                        `<div class="best-typeahead-item" data-id="${item.Id}" data-name="${item.Name}" style="padding:.5rem .75rem;cursor:pointer;border-bottom:1px solid #f8f9fa;">${item.Name}</div>`
                    ).join('');
                    this.$dropdown.html(html).show();
                    this.$dropdown.find('.best-typeahead-item').on('click', (e) => {
                        const $item = $(e.currentTarget);
                        this.$input.val($item.data('name'));
                        this.$hiddenInput.val($item.data('id'));
                        this.$dropdown.hide();
                    });
                }
            }
            $('.best-typeahead-input').each(function() {
                new BestModalTypeahead($(this));
            });
        });
    </script>
    @include('admin.event.partials.event-venue-script')
    @include('admin.event.partials.best-form-validation-script')
    @include('admin.event.partials.event-form-validation-ui')
@endpush
