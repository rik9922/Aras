@extends('layout.app', ['page_name' => 'Edit Event'])

@section('content')
@php
    $routePrefix = ((int) (auth()->user()->role_id ?? 0) === 6) ? 'manager' : 'admin';
    $isManager = $routePrefix === 'manager';
    $isCompletedEvent = (string)($event->event_status ?? '0') === '1';
    $billingDefaultsUrlTemplate = url('/'.$routePrefix.'/clients/__CLIENT__/billing-defaults');
@endphp
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .bg-white {
            z-index: 12;
        }

        .btn-design {
            height: 38px;
            padding: 6px 12px;
            font-size: var(--font-size-button);
            line-height: 1.5;
        }
    </style>
@endpush
    
    @if ($errors->any())
    @php
        $evLabelMap = [
            'event_name'        => 'Event Name',
            'venue_name'        => 'Venue',
            'department_id'     => 'Department',
            'position'          => 'Positions',
            'distributor_id'    => 'Distributor',
            'brand_id'          => 'Brand',
            'market_ids'        => 'Markets',
            'manager_id'        => 'Manager',
            'start_time'        => 'Start Date/Time',
            'end_time'          => 'End Date/Time',
            'varietals'         => 'Varietals',
            'notes'             => 'Notes',
            'billing_party_ids' => 'Billing Parties',
            'client_id'         => 'Client',
            'payment_type'      => 'Payment Type',
            'booked_by'         => 'Booked By',
            'booked_date'       => 'Booked Date',
            'region'            => 'Region',
        ];
        $evShown = [];
    @endphp
    <div class="alert alert-danger mb-0" role="alert">
        <strong>Please correct the following errors:</strong>
        <ul class="mb-0 mt-1">
            @foreach ($errors->keys() as $evKey)
                @php
                    $evBase = explode('.', $evKey)[0];
                    if (in_array($evBase, $evShown)) continue;
                    $evShown[] = $evBase;
                    $evLabel   = $evLabelMap[$evBase] ?? ucwords(str_replace('_', ' ', $evBase));
                    $evMsg     = $errors->first($evKey);
                    $evDisplay = str_contains(strtolower($evMsg), 'required')
                        ? $evLabel . ' is required.'
                        : $evMsg;
                @endphp
                <li>{{ $evDisplay }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="container pb-3">
        <div class="page-card-design">
            <div class="d-flex">
                <div class="h5 text-uppercase">Event Details</div>
            </div>
            <div class="fixed-border"></div>
            <form id="eventSaveForm" action="{{ route($routePrefix . '.update.event') }}" method="POST" enctype="multipart/form-data">@csrf
                <input type="hidden" name="id" value="{{ $event->id }}">

                @if ($isCompletedEvent)
                    <div class="alert alert-warning mx-3 mt-3 mb-0 py-2 px-3 small">
                        <i class="bi bi-lock-fill me-1"></i>
                        This event is Completed. Only the <strong>Billing Parties (Bill To)</strong> section can be edited.
                    </div>
                @endif

                <div class="row p-3">
                    <fieldset class="row g-0 p-0 m-0 border-0" {{ $isCompletedEvent ? 'disabled' : '' }}>
                    {{-- Event name is generated automatically by the backend.
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="event_name" class="form-label text-uppercase">Event Name</label>
                            <input type="text" class="form-control shadow-none @error('event_name') is-invalid @enderror"
                                value="{{ old('event_name', $event->event_name) }}" name="event_name" id="event_name" placeholder="Event Name">
                            @error('event_name')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    --}}
                    @include('admin.event.partials.city-field', ['event' => $event])
                    @include('admin.event.partials.venue-event-fields', [
                        'event' => $event,
                        'states' => $states,
                        'venueTypes' => $venueTypes,
                    ])
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="selectDepartment" class="form-label text-uppercase field-required">Department</label>
                            <select class="form-select shadow-none @error('department_id') is-invalid @enderror"
                                id="selectDepartment" aria-label="Department" name="department_id">
                                <option value="">select</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}"
                                        {{ $event->department_id == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    @include('admin.event.partials.event-type-field', ['event' => $event])
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="selectPosition" class="form-label text-uppercase field-required">Positions(How many Ambassadors for
                                this event)</label>
                            <input type="number" class="form-control shadow-none @error('positions') is-invalid @enderror"
                                value="{{ $event->position }}" name="position" id="selectPosition" min="1"
                                step="1">
                            @error('position')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input shadow-none" type="checkbox" id="ageRestrictedCheckbox"
                                    name="age_restricted" value="1"
                                    {{ old('age_restricted', isset($event) ? $event->age_restricted : 1) ? 'checked' : '' }}
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
                                    {{ old('recap_needed', isset($event) ? $event->recap_needed : 1) ? 'checked' : '' }}
                                    @error('recap_needed') is-invalid @enderror>
                                <label class="form-check-label text-uppercase" for="recapNeeded">
                                    Check if needed for recaps
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
                                <input class="form-check-input shadow-none" type="checkbox" id="eventAvailableCheckbox"
                                    @if ($event->apply_event == 1) checked @endif name="apply_event"
                                    @error('apply_event') is-invalid @enderror>
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
                            <label for="selectDistributors" class="form-label text-uppercase field-required">Distributor</label>
                            <select class="form-select shadow-none @error('distributor_id') is-invalid @enderror"
                                id="selectDistributors" aria-label="Distributors" name="distributor_id">
                                <option value="">select</option>
                                @foreach ($distributors as $distributor)
                                    <option value="{{ $distributor->id }}"
                                        {{ $event->distributor_id == $distributor->id ? 'selected' : '' }}>
                                        {{ $distributor->name }}</option>
                                @endforeach
                            </select>
                            @error('distributor_id')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="brand" class="form-label text-uppercase field-required">Brand</label>
                            <select class="form-select shadow-none @error('brand_id') is-invalid @enderror"
                                id="brand" aria-label="Brand" name="brand_id">
                                <option value="">select</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}"
                                        {{ $event->brand_id == $brand->id ? 'selected' : '' }}>{{ $brand->brand_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('brand_id')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            @php
                                $selectedMarketIds = old('market_ids', $event->markets?->pluck('id')->all() ?? ($event->market_id ? [$event->market_id] : []));
                            @endphp
                            <label for="selectMarket" class="form-label text-uppercase field-required">Markets</label>
                            <select class="form-select shadow-none multiple-select2 @error('market_ids') is-invalid @enderror"
                                id="selectMarket" name="market_ids[]" multiple="multiple">
                                <option value="">select</option>
                                @foreach ($markets as $market)
                                    <option value="{{ $market->id }}"
                                        @if (is_array($selectedMarketIds) && in_array($market->id, $selectedMarketIds)) selected @endif>{{ $market->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('market_ids')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="selectManager" class="form-label text-uppercase field-required">Manager</label>
                            <select class="form-select shadow-none @error('manager_id') is-invalid @enderror"
                                id="selectManager" aria-label="Select Manager" name="manager_id">
                                <option value="">select</option>
                                @foreach ($managers as $manager)
                                    <option value="{{ $manager->id }}" 
                                    {{ old('manager_id', $event->manager_id) == $manager->id ? 'selected' : '' }}>
                                        {{ $manager->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('manager_id')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!--<input type="hidden" name="timezone" id="timezone">-->

                    <!--<div class="col-md-6">-->
                    <!--    <div class="mb-3">-->
                    <!--        <label for="startDate/Time" class="form-label text-uppercase">Start Date/Time</label>-->
                    <!--        <input type="datetime-local"-->
                    <!--            class="form-control shadow-none @error('start_time') is-invalid @enderror"-->
                    <!--            value="{{ $event->start_time }}" name="start_time" id="startDateTime">-->
                    <!--        @error('start_time')-->
                    <!--            <div class="text-danger w-100">{{ $message }}</div>-->
                    <!--        @enderror-->
                    <!--    </div>-->
                    <!--</div>-->
                    <!--<div class="col-md-6">-->
                    <!--    <div class="mb-3">-->
                    <!--        <label for="endDate/Time" class="form-label text-uppercase">End Date/Time</label>-->
                    <!--        <input type="datetime-local"-->
                    <!--            class="form-control shadow-none @error('end_time') is-invalid @enderror"-->
                    <!--            value="{{ $event->end_time }}" name="end_time" id="endDateTime">-->
                    <!--        @error('end_time')-->
                    <!--            <div class="text-danger w-100">{{ $message }}</div>-->
                    <!--        @enderror-->
                    <!--    </div>-->
                    <!--</div>-->
                    
                    <div class="row">
                        @include('admin.event.partials.timezone-field', ['event' => $event])

                      <div class="col-md-4 mb-3">
                        <label for="startDateTime" class="form-label text-uppercase field-required">Start Date/Time</label>
                        <input type="text" id="startDateTime" name="start_time" class="form-control shadow-none @error('start_time') is-invalid @enderror" value="{{ old('start_time', $event->start_time) }}">
                        @error('start_time')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                      </div>
                    
                      <div class="col-md-4 mb-3">
                        <label for="endDateTime" class="form-label text-uppercase field-required">End Date/Time</label>
                        <input type="text" id="endDateTime" name="end_time" class="form-control shadow-none @error('end_time') is-invalid @enderror" value="{{ old('end_time', $event->end_time) }}">
                        @error('end_time')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                      </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="varietals" class="form-label text-uppercase field-required">Varietals</label>
                            <textarea class="form-control shadow-none  @error('varietals') is-invalid @enderror" id="varietals"
                                placeholder="Event Varietals" name="varietals">{{ $event->varietals }}</textarea>
                            @error('varietals')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="notes" class="form-label text-uppercase field-required">Notes</label>
                            <textarea class="form-control shadow-none @error('notes') is-invalid @enderror" id="notes"
                                placeholder="Additional Event Notes" name="notes">{{ $event->notes }}</textarea>
                            @error('notes')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="internal_notes" class="form-label text-uppercase">Internal Notes (Visible only to MM)</label>
                            <textarea class="form-control shadow-none @error('internal_notes') is-invalid @enderror"
                                id="internal_notes" name="internal_notes"
                                placeholder="Notes visible only to Market Manager">{{ $event->internal_notes }}</textarea>
                            @error('internal_notes')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <!--<div class="col-md-12">-->
                    <!--    <div class="mb-3">-->
                    <!--        <div class="form-check">-->
                    <!--            <input class="form-check-input shadow-none" type="checkbox"-->
                    <!--                @if ($event->payment_status == 1) checked @endif id="unpaid"-->
                    <!--                name="payment_status">-->
                    <!--            <label class="form-check-label text-uppercase" for="unpaid">-->
                    <!--                Unpaid-->
                    <!--            </label>-->
                    <!--        </div>-->
                    <!--    </div>-->
                    <!--</div>-->
                    
                    <div class="col-md-12">
                        <div class="mb-3">
                            <div class="form-check">
                                <!--<input class="form-check-input shadow-none" type="checkbox" value="" id="unpaid" name="payment_status">-->
                                <input class="form-check-input shadow-none" type="checkbox" value="1"
                                    id="unpaid" name="payment_status" {{ old('payment_status', $event->payment_status) ? 'checked' : '' }}>
                                <label class="form-check-label text-uppercase" for="unpaid">
                                    Unpaid
                                </label>
                            </div>
                            @error('payment_status')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3" id="expediteFee"
                        @if ($event->payment_status == 1) style="display:none;" @endif>
                        <div class="mb-3">
                            <label for="expediteFee1" class="form-label text-uppercase">Expedite Fee</label>
                            <input type="text"
                                class="form-control shadow-none @error('expedite_fee') is-invalid @enderror"
                                value="{{ $event->expedite_fee }}" value="{{ old('expedite_fee') }}"
                                name="expedite_fee" id="expediteFee1">
                            @error('expedite_fee')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3" id="postageFee" @if ($event->payment_status == 1) style="display:none;" @endif>
                        <div class="mb-3">
                            <label for="shipping/postageFee" class="form-label text-uppercase">Shipping/Postage
                                Fee</label>
                            <input type="text" class="form-control shadow-none" name="shipping_fee"
                                value="{{ $event->shipping_fee }}" value="{{ old('shipping_fee') }}"
                                id="shipping/postageFee">
                        </div>
                    </div>
                    <div class="col-md-3" id="parkingFee" @if ($event->payment_status == 1) style="display:none;" @endif>
                        <div class="mb-3">
                            <label for="parkingFee" class="form-label text-uppercase">Parking Fee</label>
                            <input type="text" class="form-control shadow-none" name="parking_fee"
                                value="{{ $event->parking_fee }}" value="{{ old('parking_fee') }}">
                        </div>
                    </div>
                    <div class="col-md-3" id="drive-timePay"
                        @if ($event->payment_status == 1) style="display:none;" @endif>
                        <div class="mb-3">
                            <label for="drive-timePay" class="form-label text-uppercase">Drive-time pay / per
                                event</label>
                            <input type="text" class="form-control shadow-none" name="drive_time"
                                value="{{ $event->drive_time }}" value="{{ old('drive_time') }}">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="paymentType" class="form-label text-uppercase field-required">Payment Type</label>
                            <select class="form-select shadow-none @error('payment_type') is-invalid @enderror"
                                id="paymentType" name="payment_type">
                                <option value="">select</option>
                                @php
                                    $ptRaw = old('payment_type', $event->payment_type);
                                    $ptNorm = [
                                        '1' => 'No purchase',
                                        '2' => 'Product on site',
                                        '3' => 'Comdata',
                                        '4' => 'Check',
                                        '5' => 'Reimbursement',
                                        'ComData' => 'Comdata',
                                    ][$ptRaw] ?? $ptRaw;
                                @endphp
                                <option value="No purchase" {{ $ptNorm === 'No purchase' ? 'selected' : '' }}>No purchase</option>
                                <option value="Product on site" {{ $ptNorm === 'Product on site' ? 'selected' : '' }}>Product on site</option>
                                <option value="Comdata" {{ $ptNorm === 'Comdata' ? 'selected' : '' }}>Comdata</option>
                                <option value="Check" {{ $ptNorm === 'Check' ? 'selected' : '' }}>Check</option>
                                <option value="Reimbursement" {{ $ptNorm === 'Reimbursement' ? 'selected' : '' }}>Reimbursement</option>
                            </select>
                            @error('payment_type')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mt-4">
                            <div class="form-check">
                                <input class="form-check-input shadow-none  @error('receipt') is-invalid @enderror"
                                    type="checkbox" id="receiptRequired" value="1" name="receipt"
                                    {{ old('receipt', $event->receipt) ? 'checked' : '' }}>
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
                                    {{ old('kit', $event->kit) ? 'checked' : '' }}>
                                <label class="form-check-label text-uppercase" for="kitRequired">
                                    Kit Required
                                </label>

                                @error('kit')
                                    <div class="text-danger w-100">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="uniform" class="form-label text-uppercase">Uniform</label>
                            <div class="input-group mb-3">
                                <input type="text"
                                    class="form-control shadow-none @error('uniform') is-invalid @enderror"
                                    name="uniform" id="uniform" value="{{ $event->uniform }}">
                            </div>
                            @error('uniform')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    </fieldset>

                    <div class="col-12" id="billingPartiesSection">
                        @php
                            $billingLocked = (string)($event->event_status ?? '0') === '2'
                                || (is_string($event->admin_status ?? null) && str_starts_with($event->admin_status, 'cancelled_'));
                            $disabledAttr = $billingLocked ? 'disabled' : '';
                            $readonlyAttr = $billingLocked ? 'readonly' : '';
                            $managerBillingReadonlyAttr = ($isManager || $billingLocked) ? 'readonly' : '';
                            $managerBillingReadonlyClass = ($isManager || $billingLocked) ? 'bg-light' : '';
                            // Build existing labor rates keyed by client_id
                            // labor_rate is not stored per-party yet; fall back to client profile default
                            $existingLaborRates = [];
                            if ($event->billingParties->isNotEmpty()) {
                                foreach ($event->billingParties as $bp) {
                                    $existingLaborRates[$bp->client_id] = $bp->client->default_labor_amount ?? null;
                                }
                            } elseif ($event->client_id) {
                                $billToClient = $clients->firstWhere('id', $event->client_id);
                                if ($billToClient) {
                                    $existingLaborRates[$event->client_id] = $billToClient->default_labor_amount ?? null;
                                }
                            }
                        @endphp

                        @if ($billingLocked)
                            <div class="alert alert-warning py-2 px-3 small mb-3">
                                <i class="bi bi-lock-fill me-1"></i>
                                Billing fields are <strong>read-only</strong> — this event is
                                Cancelled.
                            </div>
                        @endif

                        {{-- ── Billing Parties ── --}}
                        <div class="mb-3">
                            <label for="selectBillingParties" class="form-label fw-semibold">Billing Parties (Bill To)</label>
                            <p class="small text-muted mb-1">Select one or more clients to invoice. One must be marked as Primary — the Cup Charge always belongs to the Primary party.</p>
                            <select class="form-select shadow-none @error('billing_party_ids') is-invalid @enderror"
                                id="selectBillingParties" name="billing_party_ids[]" multiple
                                {{ $disabledAttr }}>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}"
                                        data-labor-rate="{{ $client->default_labor_amount ?? '' }}"
                                        @if (old('billing_party_ids') && in_array($client->id, old('billing_party_ids'))) selected
                                        @elseif ($event->billingParties->pluck('client_id')->contains($client->id)) selected
                                        @elseif ($event->billingParties->isEmpty() && $event->client_id == $client->id) selected
                                        @endif>{{ $client->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="client_id" id="client_id" value="{{ old('client_id', $event->client_id) }}">
                            @error('billing_party_ids')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-2 mb-2">
                            {{-- Primary Billing Party selector --}}
                            <div class="col-md-6 mb-3" id="primaryBillingPartyWrap"
                            @php
                                $hasSelected = old('billing_party_ids')
                                    ? count(old('billing_party_ids')) > 0
                                    : ($event->billingParties->isNotEmpty() || $event->client_id);
                            @endphp
                            style="{{ $hasSelected ? '' : 'display:none;' }}">
                            <label for="primary_billing_party_id" class="form-label">Primary Billing Party</label>
                            <select class="form-select shadow-none" id="primary_billing_party_id"
                                name="primary_billing_party_id" {{ $disabledAttr }}>
                                <option value="">Select primary</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}"
                                        @if (old('primary_billing_party_id', $event->primaryBillingParty?->client_id ?? $event->client_id) == $client->id) selected @endif>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted small mt-1">Cup Charge always belongs to the Primary Billing Party.</small>
                            </div>

                            {{-- Cup Charge (Primary only) --}}
                            <div class="col-md-6" id="cupsWrap"
                                style="{{ $hasSelected ? '' : 'display:none;' }}">
                                <label for="cups_amount" class="form-label small">Cup Charge ($) <span class="text-muted fw-normal small">(Primary party)</span></label>
                                <input type="number" step="0.01" min="0" name="cups_amount" id="cups_amount"
                                    class="form-control {{ $managerBillingReadonlyClass }}" placeholder="0"
                                    value="{{ old('cups_amount', $event->cups_amount) }}"
                                    {{ $managerBillingReadonlyAttr }}>
                            </div>
                        </div>

                        {{-- Per-party Labor Rate rows --}}
                        <div id="billingPartyLaborRates" class="mb-3"
                            style="{{ $hasSelected ? '' : 'display:none;' }}">
                            <label class="form-label">Labor Rate per Billing Party</label>
                            <div id="laborRateRows">
                                {{-- Render server-side rows for existing parties (JS will also manage on change) --}}
                                @php
                                    $selectedPartyIds = old('billing_party_ids')
                                        ? old('billing_party_ids')
                                        : ($event->billingParties->isNotEmpty()
                                            ? $event->billingParties->pluck('client_id')->toArray()
                                            : ($event->client_id ? [$event->client_id] : []));
                                    $primaryPartyId = old('primary_billing_party_id',
                                        $event->primaryBillingParty?->client_id ?? $event->client_id);
                                    $clientsById = $clients->keyBy('id');
                                @endphp
                                @foreach ($selectedPartyIds as $partyClientId)
                                    @php $partyClient = $clientsById[$partyClientId] ?? null; @endphp
                                    @if ($partyClient)
                                        @php
                                            $savedRate = old('labor_rates.' . $partyClientId,
                                                $existingLaborRates[$partyClientId] ?? $partyClient->default_labor_amount ?? '');
                                            $isPrimary = (string)$partyClientId === (string)$primaryPartyId;
                                        @endphp
                                        <div class="d-flex align-items-center gap-2 mb-2 billing-party-labor-row p-2 border rounded bg-light"
                                            data-client-id="{{ $partyClientId }}">
                                            <div class="flex-grow-1 fw-semibold">
                                                @if ($isPrimary)
                                                    <span class="badge bg-primary me-1">Primary</span>
                                                @endif
                                                {{ $partyClient->name }}
                                            </div>
                                            <div class="d-flex align-items-center gap-1">
                                                <label class="form-label mb-0 small text-nowrap">Labor Rate ($)</label>
                                                <input type="number" step="0.01" min="0"
                                                    name="labor_rates[{{ $partyClientId }}]"
                                                    class="form-control {{ $managerBillingReadonlyClass }}" style="width:110px;"
                                                    placeholder="0" value="{{ $savedRate }}"
                                                    {{ $managerBillingReadonlyAttr }}>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        {{-- Buyback amount moved to recap forms; keep this field commented for future restore.
                        <div class="row g-2 mb-2">
                            <div class="col-md-6" id="buybackWrap"
                                style="{{ $hasSelected ? '' : 'display:none;' }}">
                                <label for="buyback_amount" class="form-label small">Buyback Amount ($)</label>
                                <input type="number" step="0.01" min="0" name="buyback_amount" id="buyback_amount"
                                    class="form-control placeholder="0"
                                    value="{{ old('buyback_amount', $event->buyback_amount) }}"
                                    {{ $readonlyAttr }}>
                            </div>
                        </div>
                        --}}

                        {{-- Buyback helper text moved with event buyback field; keep commented for future restore.
                        @php
                            $multiParty = count($selectedPartyIds) > 1;
                        @endphp
                        <div id="buybackHelperText" class="text-muted small mt-1"
                            style="{{ $multiParty ? '' : 'display:none;' }}">
                            When multiple Billing Parties are selected, this Buyback Amount will be split evenly across all Billing Parties during QuickBooks invoicing.
                        </div>
                        --}}

                        {{-- Always send split_cups=0 (cups never split) --}}
                        <input type="hidden" name="split_cups" value="0">
                    </div>

                    <fieldset class="row g-0 p-0 m-0 border-0" {{ $isCompletedEvent ? 'disabled' : '' }}>
                    @include('admin.event.partials.ambassador-staffing', [
                        'staffingRows' => $staffingRows ?? [],
                        'eventPinataPay' => old('pinata_pay', $eventPinataPay ?? false),
                        'staffingEventId' => $staffingEventId ?? $event->id,
                        'routePrefix' => $routePrefix ?? 'admin',
                    ])

                    <div class="col-md-5">
                        <div class="mb-3">
                            <label for="attachments" class="form-label text-uppercase">Attachments</label>
                            <br/>
                            <div style="gap: 20px;display: flex;">
                                <input type="radio" value="aras_document" name="att_option" onclick="document_view('show')"/>Aras Documents
                            </div>
                            <div style="gap: 20px;display: flex;">
                                <input type="radio" value="upload_document" name="att_option" onclick="upload_local()"/>Upload from Local
                            </div>
                            <input type="file" style="display:none;" class="form-control shadow-none" name="attachments[]" id="attachments"
                                placeholder="Select Documents..." multiple accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.zip,.ppt,.pptx">
                        </div>
                    </div>
                    <!-- 🖼️ Existing attachments preview -->
                    <div id="existing-preview" class="mb-3 d-flex flex-wrap gap-2">
                        @if (!empty($event->eventAttachments))
                            @foreach ($event->eventAttachments as $attachment)
                                <div class="preview-box position-relative" 
                                     data-id="{{ $attachment->id }}" 
                                     style="position: relative; width: 120px;">
                                     
                                    <span class="remove-existing" title="Remove"
                                        style="position: absolute;top: 0px; right: 5px;
                                               background: rgb(220,53,69);color: #fff;
                                               border-radius: 50%;width: 20px;height: 20px;
                                               text-align: center;cursor: pointer;z-index: 10;
                                               font-weight: bold;display: flex;justify-content: center;
                                               align-items: center;font-size: 10px;">
                                        &times;
                                    </span>
                    
                                    @if (Str::startsWith($attachment->type, 'image/'))
                                        <img src="{{ asset($attachment->filename) }}" class="preview-media"
                                            style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">
                                    @elseif (Str::startsWith($attachment->type, 'video/'))
                                        <video class="preview-media" controls
                                            style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">
                                            <source src="{{ asset($attachment->filename) }}" type="{{ $attachment->type }}">
                                        </video>
                                    @else
                                        <div style="display:flex;flex-direction:column;align-items:center;
                                                    justify-content:center;width:100px;height:100px;
                                                    border:1px solid #ddd;border-radius:8px;background:#f8f9fa;">
                                            <i class="fa-solid fa-file" style="font-size:36px;color:#007bff;margin-bottom:5px;"></i>
                                            <span style="font-size:12px;white-space:nowrap;overflow:hidden;
                                                         text-overflow:ellipsis;width:90px;">
                                                {{ basename($attachment->filename) }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </div>
                    
                    <input type="hidden" name="remove_id" id="remove_id" />
                    <div id="new-preview" class="mb-3 d-flex flex-wrap gap-2"></div>

                    <div class="col-md-5">
                        <label class="form-label text-uppercase d-block mb-2">BEST Integration</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input shadow-none" type="radio" name="integrate_with_best"
                                    id="normalEventRadio" value="0"
                                    {{ old('integrate_with_best', $event->integrate_with_best ?? 0) != '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="normalEventRadio">Normal Event</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input shadow-none" type="radio" name="integrate_with_best"
                                    id="bestEventRadio" value="1"
                                    {{ old('integrate_with_best', $event->integrate_with_best ?? 0) == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="bestEventRadio">BEST Event</label>
                            </div>
                        </div>
                        @error('integrate_with_best')
                            <div class="text-danger w-100">{{ $message }}</div>
                        @enderror
                        <input type="hidden" name="best_event_data" id="bestEventDataInput"
                               value="{{ old('best_event_data', $event->best_event_data ?? '') }}">
                        @if($event->integrate_with_best)
                            <small class="text-success mt-1 d-block">BEST configuration saved</small>
                        @endif
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="region" class="form-label field-required">Region</label>
                            <div class="d-flex gap-4">
                                <select class="form-select shadow-none  @error('region') is-invalid @enderror"
                                    id="region" name="region" id="region">
                                    <option value="" selected>Select Region</option>
                                    <option value="Southeast" @if (old('region', $event->region) == 'Southeast') selected @endif>Southeast
                                    </option>
                                    <option value="Midwest" @if (old('region', $event->region) == 'Midwest') selected @endif>Midwest
                                    </option>
                                </select>
                            </div>
                            @error('region')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="bookedDate" class="form-label text-uppercase field-required">Booked Date</label>
                            <input type="date"
                                class="form-control shadow-none @error('booked_date') is-invalid @enderror"
                                value="{{ old('booked_date', $event->booked_date ? \Carbon\Carbon::parse($event->booked_date)->format('Y-m-d') : '') }}" name="booked_date" id="bookedDate">
                        </div>
                        @error('booked_date')
                            <div class="text-danger w-100">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="bookedBy" class="form-label text-uppercase field-required">Booked By</label>
                            <!--<input type="text"-->
                            <!--    class="form-control shadow-none @error('booked_by') is-invalid @enderror"-->
                            <!--    name="booked_by" value="{{ Auth::user()->name }}" id="bookedBy" readonly>-->
                            @php
                                $bookedBySource = old('booked_by', $event->booked_by);
                                if (is_array($bookedBySource)) {
                                    $bookedByInput = $bookedBySource;
                                } else {
                                    $decodedBookedBy = json_decode((string) $bookedBySource, true);
                                    $bookedByInput = is_array($decodedBookedBy)
                                        ? $decodedBookedBy
                                        : preg_split('/[,;\r\n]+/', (string) $bookedBySource);
                                }
                                $bookedByInput = array_values(array_filter(array_map('trim', $bookedByInput ?? [])));
                            @endphp
                            <input type="text"
                                class="form-control shadow-none @error('booked_by') is-invalid @enderror"
                                name="booked_by" id="bookedBy" placeholder="Enter email(s), separated by commas"
                                value="{{ implode(', ', $bookedByInput) }}">
                        </div>
                        @error('booked_by')
                            <div class="text-danger w-100">{{ $message }}</div>
                        @enderror
                    </div>
                    </fieldset>

                </div>
                <div class="d-grid mx-3 mb-3">
                    @php
                        $routePrefix = ((int) (auth()->user()->role_id ?? 0) === 6) ? 'manager' : 'admin';
                    @endphp
                    <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                        <button type="submit" class="btn-ds btn-primary-ds">Save</button>
                        <button type="button" class="btn-ds btn-secondary-ds" onclick="window.history.back()">Back</button>

                        <a class="btn-ds btn-secondary-ds text-nowrap"
                           href="{{ route($routePrefix . '.duplicate.event', ['id' => $event->id]) }}">
                            Duplicate Event
                        </a>

                        <button type="button" class="btn-ds btn-success-ds text-nowrap" data-bs-toggle="modal" data-bs-target="#notifyAmbassadorsModal">
                            Notify Brand Ambassador
                        </button>

                        <div class="form-check ms-auto">
                            <input class="form-check-input shadow-none" type="checkbox" id="auto_notify_ambassadors"
                                   name="auto_notify_ambassadors" value="1" {{ old('auto_notify_ambassadors') ? 'checked' : '' }}
                                   {{ $isCompletedEvent ? 'disabled' : '' }}>
                            <label class="form-check-label text-uppercase" for="auto_notify_ambassadors">
                                Auto notify on time/location changes
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Notify Ambassadors Modal -->
    <div class="modal fade" id="notifyAmbassadorsModal" tabindex="-1" aria-labelledby="notifyAmbassadorsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route($routePrefix . '.events.notify-ambassadors', ['event' => $event->id]) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="notifyAmbassadorsModalLabel">Notify Brand Ambassadors</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="message_key" class="form-label">Message</label>
                            <select class="form-select shadow-none" id="message_key" name="message_key" required>
                                <option value="event_details_updated" selected>Event details updated</option>
                                <option value="event_time_changed">Event time has changed</option>
                                <option value="event_location_changed">Event location has changed</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        <div class="mb-3" id="custom_message_wrap" style="display:none;">
                            <label for="custom_message" class="form-label">Custom message</label>
                            <textarea class="form-control shadow-none" id="custom_message" name="custom_message" rows="3" maxlength="500"
                                      placeholder="Write a short message for assigned ambassadors..."></textarea>
                            <div class="form-text">Max 500 characters.</div>
                        </div>
                        <div class="small text-muted">
                            This will notify all ambassadors assigned to this event (in-app, and push if enabled in their notification settings).
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-ds btn-secondary-ds" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-ds btn-primary-ds">Send</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="modal fade" id="myModal" role="dialog">
        <div class="modal-dialog">
        
          <!-- Modal content-->
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">Documents</h4>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-footer">
              <button type="button" class="btn-ds btn-primary-ds" data-dismiss="modal" onclick="selected_files()">Submit</button>
              <button type="button" class="btn-ds btn-danger-ds" data-dismiss="modal" onclick="document_view('hide')">Close</button>
            </div>
          </div>
          
        </div>
      </div>

{{-- BEST Event Fields Modal (edit) --}}
<div class="modal fade" id="bestFieldsModal" tabindex="-1" aria-labelledby="bestFieldsModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bestFieldsModalLabel">BEST Event Configuration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="bestModalClose"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">All fields are required for BEST integration.</p>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-uppercase">Status</label>
                        <select class="form-select shadow-none" id="best_status"><option value="">---Select Status---</option><option value="Planned">Planned</option><option value="Completed">Completed</option><option value="Canceled">Canceled</option></select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-uppercase">Target Audience</label>
                        <select class="form-select shadow-none" id="best_target_audience"><option value="">---Select---</option><option value="Consumer">Consumer</option><option value="Trade">Trade</option></select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-uppercase">Account</label>
                        <div class="typeahead-container position-relative">
                            <input type="text" class="form-control shadow-none best-typeahead-input" id="best_account_name" data-target-id="best_account_id" data-api="/{{ ((int)(auth()->user()->role_id ?? 0) === 6) ? 'manager' : 'admin' }}/best-get-account" placeholder="Account name..." autocomplete="off">
                            <input type="hidden" id="best_account_id">
                            <div class="typeahead-dropdown best-typeahead-dropdown" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #ced4da;border-top:none;border-radius:0 0 .375rem .375rem;max-height:200px;overflow-y:auto;z-index:1050;"></div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-uppercase">Associated with Demand Creation Station</label>
                        <select class="form-select shadow-none" id="best_demand_creation"><option value="">---Select---</option><option value="Yes">Yes</option><option value="No">No</option></select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-uppercase">Wet or Dry</label>
                        <div class="d-flex gap-4 mt-2">
                            <div class="form-check"><input class="form-check-input" type="radio" name="best_wet_or_dry" id="best_wet" value="Wet"><label class="form-check-label" for="best_wet">Wet</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="best_wet_or_dry" id="best_dry" value="Dry"><label class="form-check-label" for="best_dry">Dry</label></div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-uppercase">Sampling Type</label>
                        <select class="form-select shadow-none" id="best_sampling_type"><option value="">---Sampling Type---</option><option value="Single Brand Sampling">Single Brand Sampling</option><option value="Multi Brand Sampling">Multi Brand Sampling</option></select>
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label text-uppercase">Objective</label><textarea class="form-control shadow-none" id="best_objective" rows="3"></textarea></div>
                    <div class="col-md-6 mb-3"><label class="form-label text-uppercase">Description</label><textarea class="form-control shadow-none" id="best_description" rows="3"></textarea></div>
                    <div class="col-md-4 mb-3"><label class="form-label text-uppercase">Attendees</label><input type="number" class="form-control shadow-none" id="best_attendees" min="0" value="0"></div>
                    <div class="col-md-4 mb-3"><label class="form-label text-uppercase">Interactions</label><input type="number" class="form-control shadow-none" id="best_interactions" min="0" value="0"></div>
                    <div class="col-md-4 mb-3"><label class="form-label text-uppercase">Budget</label><input type="number" class="form-control shadow-none" id="best_budget" min="0" value="0"></div>
                    <div class="col-md-6 mb-3"><label class="form-label text-uppercase">Event Results</label><textarea class="form-control shadow-none" id="best_event_results" rows="3"></textarea></div>
                    <div class="col-md-6 mb-3"><label class="form-label text-uppercase">Key Learnings</label><textarea class="form-control shadow-none" id="best_key_learnings" rows="3"></textarea></div>
                    <div class="col-md-6 mb-3"><label class="form-label text-uppercase">Consumer Feedback</label><textarea class="form-control shadow-none" id="best_consumer_feedback" rows="3"></textarea></div>
                    <div class="col-md-6 mb-3"><label class="form-label text-uppercase">Account Feedback</label><textarea class="form-control shadow-none" id="best_account_feedback" rows="3"></textarea></div>
                    <div class="col-12"><hr><h6 class="text-uppercase fw-bold">Consumer Event Samplings</h6></div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-uppercase">Account Premise Type</label>
                        <select class="form-select shadow-none" id="best_account_premise_type">
                            @foreach ($eventTypes as $type)<option value="{{ $type->name }}">{{ $type->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-uppercase">Program</label>
                        <div class="typeahead-container position-relative">
                            <input type="text" class="form-control shadow-none best-typeahead-input" id="best_program_name" data-target-id="best_program_id" data-api="/{{ ((int)(auth()->user()->role_id ?? 0) === 6) ? 'manager' : 'admin' }}/best-get-program" placeholder="Program name..." autocomplete="off">
                            <input type="hidden" id="best_program_id">
                            <div class="typeahead-dropdown best-typeahead-dropdown" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #ced4da;border-top:none;border-radius:0 0 .375rem .375rem;max-height:200px;overflow-y:auto;z-index:1050;"></div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-uppercase">Tag</label>
                        <div class="typeahead-container position-relative">
                            <input type="text" class="form-control shadow-none best-typeahead-input" id="best_tag_name" data-target-id="best_tag_id" data-api="/{{ ((int)(auth()->user()->role_id ?? 0) === 6) ? 'manager' : 'admin' }}/best-get-tags" placeholder="Tag name..." autocomplete="off">
                            <input type="hidden" id="best_tag_id">
                            <div class="typeahead-dropdown best-typeahead-dropdown" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #ced4da;border-top:none;border-radius:0 0 .375rem .375rem;max-height:200px;overflow-y:auto;z-index:1050;"></div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3"><label class="form-label text-uppercase">Bottles 1L</label><input type="number" class="form-control shadow-none" id="best_1l_bottles" min="0" value="0"></div>
                    <div class="col-md-3 mb-3"><label class="form-label text-uppercase">Bottles 1.75L</label><input type="number" class="form-control shadow-none" id="best_175l_bottles" min="0" value="0"></div>
                    <div class="col-md-3 mb-3"><label class="form-label text-uppercase">Bottles 750ML</label><input type="number" class="form-control shadow-none" id="best_750ml_bottles" min="0" value="0"></div>
                    <div class="col-md-3 mb-3"><label class="form-label text-uppercase">Bottles 375ML</label><input type="number" class="form-control shadow-none" id="best_375ml_bottles" min="0" value="0"></div>
                    <div class="col-md-4 mb-3"><label class="form-label text-uppercase">Total Samples</label><input type="number" class="form-control shadow-none" id="best_total_samples" min="0" value="0"></div>
                    <div class="col-md-4 mb-3"><label class="form-label text-uppercase">Samples Cocktail</label><input type="number" class="form-control shadow-none" id="best_total_samples_cocktail" min="0" value="0"></div>
                    <div class="col-md-4 mb-3"><label class="form-label text-uppercase">Samples Neat Shot</label><input type="number" class="form-control shadow-none" id="best_total_samples_neat_shot" min="0" value="0"></div>
                    <div class="col-12"><hr><h6 class="text-uppercase fw-bold">Consumer Event Lengths</h6></div>
                    <div class="col-md-4 mb-3"><label class="form-label text-uppercase">End Date</label><input type="date" class="form-control shadow-none" id="best_end_date"></div>
                    <div class="col-md-4 mb-3"><label class="form-label text-uppercase">Start Time</label><input type="time" class="form-control shadow-none" id="best_start_time"></div>
                    <div class="col-md-4 mb-3"><label class="form-label text-uppercase">End Time</label><input type="time" class="form-control shadow-none" id="best_end_time"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-ds btn-secondary-ds" data-bs-dismiss="modal" id="bestModalCancel">Cancel</button>
                <button type="button" class="btn-ds btn-primary-ds" id="bestModalSave">Save BEST Configuration</button>
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
        document.addEventListener('DOMContentLoaded', function () {
            const sel = document.getElementById('message_key');
            const wrap = document.getElementById('custom_message_wrap');
            const txt = document.getElementById('custom_message');
            if (!sel || !wrap) return;
            function sync() {
                const isCustom = sel.value === 'custom';
                wrap.style.display = isCustom ? 'block' : 'none';
                if (txt) txt.required = isCustom;
            }
            sel.addEventListener('change', sync);
            sync();
        });
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
      const { DateTime } = luxon;
      const timezoneInput = document.getElementById("timezone");
      const startInput = document.getElementById("startDateTime");
      const endInput = document.getElementById("endDateTime");
    
      let startPicker, endPicker;
    
      // Round to nearest 5 minutes
      function roundToNearest5(date) {
        const ms = 1000 * 60 * 5;
        return new Date(Math.ceil(date.getTime() / ms) * ms);
      }

      function parseDateInZone(value, zone) {
        if (!value) return null;
        // Parse as naive wall-clock string then assign the event timezone
        // WITHOUT shifting the time.
        let dt = DateTime.fromSQL(value).setZone(zone, { keepLocalTime: true });
        if (!dt.isValid) dt = DateTime.fromFormat(value, "yyyy-MM-dd HH:mm").setZone(zone, { keepLocalTime: true });
        if (!dt.isValid) dt = DateTime.fromFormat(value, "yyyy-MM-dd HH:mm:ss").setZone(zone, { keepLocalTime: true });
        if (!dt.isValid) dt = DateTime.fromISO(value).setZone(zone, { keepLocalTime: true });
        return dt.isValid ? dt : null;
      }

      function flatpickrWallTime(value, zone) {
        const dt = parseDateInZone(value, zone);
        return dt ? dt.toFormat("yyyy-MM-dd HH:mm") : null;
      }
    
      // Get current + adjusted times
      function getTimes(zone) {
        const parsedStart = flatpickrWallTime(startInput.value, zone);
        const parsedEnd = flatpickrWallTime(endInput.value, zone);

        if (parsedStart && parsedEnd) {
          return { start: parsedStart, end: parsedEnd };
        }

        const nowInZone = DateTime.local().setZone(zone);
        const rounded = roundToNearest5(nowInZone.toJSDate());
        const twoHoursLater = new Date(rounded.getTime() + 2 * 60 * 60 * 1000);
        return {
          start: DateTime.fromJSDate(rounded).toFormat("yyyy-MM-dd HH:mm"),
          end: DateTime.fromJSDate(twoHoursLater).toFormat("yyyy-MM-dd HH:mm"),
        };
      }
    
      // Initialize Pickers
      function initPickers(zone) {
        const { start, end } = getTimes(zone);
        if (startPicker) startPicker.destroy();
        if (endPicker) endPicker.destroy();
    
        startPicker = flatpickr(startInput, {
          enableTime: true,
          dateFormat: "Y-m-d H:i",
          altInput: true,
          altFormat: "m/d/Y h:i K",
          altInputClass: startInput.className,
          time_24hr: false,
          minuteIncrement: 5,
          defaultDate: start,
          // Allow editing historical events; prod often has past start times.
          onChange: function (selectedDates) {
            const startDate = selectedDates[0];
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
          onChange: function (selectedDates) {
            const endDate = selectedDates[0];
            const startDate = startPicker.selectedDates[0];
            if (startDate && endDate <= startDate) {
              alert("End time must be after start time.");
              const newEnd = new Date(startDate.getTime() + 2 * 60 * 60 * 1000);
              endPicker.setDate(newEnd, true);
            }
          },
        });
      }
    
      function currentEventZone() {
        return (timezoneInput && timezoneInput.value) ? timezoneInput.value : "UTC";
      }

      window.onEventTimezoneResolved = function (zone) {
        initPickers(zone || currentEventZone());
      };

      initPickers(currentEventZone());
    });
    function document_view(type){
        jQuery('#myModal').modal(type)
        if(type=='show'){
            jQuery('.modal-body').empty();
            $.ajax({
                type: 'GET', 
                url:window.location.origin+'/{{ $routePrefix }}/getDocuments',
                success: function (data) {
                    var html="<ul style='list-style-type: none;display: grid;gap: 10px;'> ";
                    if(data.length>0){
                        jQuery(data).each(function(key,element){
                            html+='<li style="display: flex;gap: 10px;">';
                                html+='<input name="selected_one[]" type="checkbox" value="'+element.url+'"><span>'+element.file_name+'</span>';
                            html+='</li>';
                        })
                    }
                    html+="</ul>";
                    jQuery('.modal-body').append(html);
                },
                error: function(res) { 
                     console.log(res);
                }
            })   
        }
    }
    async function selected_files(){
        var selectedValues = $('input[name="selected_one[]"]:checked').map(function() {
            return this.value; // Return the value of each checked checkbox
        }).get();
        if(selectedValues.length>0){
            const fileInput = document.getElementById('attachments');
            const dataTransfer = new DataTransfer();
            
            try {
                const filePromises = selectedValues.map(async (url, index) => {
                  const response = await fetch(url);
                  const blob = await response.blob();
                  const fileName = url.split('/').pop() || `file-${index}`;
                  return new File([blob], fileName, { type: blob.type });
                });
            
                const files = await Promise.all(filePromises);
            
                // 2. Add each file to the DataTransfer object
                files.forEach(file => dataTransfer.items.add(file));
            
                // 3. Update the input element
                fileInput.files = dataTransfer.files;
            
                // Trigger change event to notify other scripts
                fileInput.dispatchEvent(new Event('change', { bubbles: true }));
        
                // Add new files (prevent duplicates)
                console.log($('#attachments').prop('files'));
                jQuery('#myModal').modal('hide')
                
              } catch (error) {
                console.error('Failed to load one or more files:', error);
              }
        } else {
            console.log('Please check any file before submit');
            return false;
        }
    }
    function upload_local(){
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
            $("#selectBillingParties").select2({ placeholder: 'Select one or more billing parties', allowClear: true });
            $("#primary_billing_party_id").select2({ placeholder: 'Select primary', allowClear: true });

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
                        console.warn('Failed to load bill-to options for brand');
                    }
                });
            }

            const billingDefaultsUrlTemplate = @json($billingDefaultsUrlTemplate);
            const billingLocked = @json($billingLocked);

            // Existing labor rates from server (keyed by client_id string)
            const existingLaborRates = @json($existingLaborRates);

            // Cache: clientId -> billing defaults response
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

            // Build per-party labor rate rows dynamically
            function rebuildLaborRateRows() {
                if (billingLocked) return; // read-only: rows already rendered server-side

                var $select = $('#selectBillingParties');
                var selectedIds = $select.val() || [];
                var $container = $('#laborRateRows');
                var primaryId = String($('#primary_billing_party_id').val() || '');

                // Preserve existing entered values
                var entered = {};
                $container.find('.billing-party-labor-row').each(function() {
                    var cid = String($(this).data('client-id'));
                    entered[cid] = $(this).find('input[name]').val();
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

                    // Priority: user entered > existing saved > profile default (fetched async)
                    var savedVal = entered[clientId] !== undefined
                        ? entered[clientId]
                        : (existingLaborRates[clientId] !== undefined && existingLaborRates[clientId] !== null
                            ? existingLaborRates[clientId]
                            : '');

                    var badge = isPrimary ? '<span class="badge bg-primary me-1">Primary</span>' : '';

                    var $row = $('<div class="d-flex align-items-center gap-2 mb-2 billing-party-labor-row p-2 border rounded bg-light"></div>')
                        .attr('data-client-id', clientId);

                    $row.html(
                        '<div class="flex-grow-1 fw-semibold">' + badge + name + '</div>' +
                        '<div class="d-flex align-items-center gap-1">' +
                            '<label for="' + rowId + '" class="form-label mb-0 small text-nowrap">Labor Rate ($)</label>' +
                            '<input type="number" step="0.01" min="0" id="' + rowId + '"' +
                            '    name="labor_rates[' + clientId + ']"' +
                            '    class="form-control {{ $managerBillingReadonlyClass }}" style="width:110px;" placeholder="0"' +
                            '    value="' + savedVal + '" {{ $managerBillingReadonlyAttr }}>' +
                        '</div>'
                    );

                    $container.append($row);

                    // Async-fill from profile defaults only if no saved value
                    if (savedVal === '') {
                        fetchBillingDefaults(clientId, function(resp) {
                            var rate = (resp.labor_amount !== null && resp.labor_amount !== undefined)
                                ? resp.labor_amount : '';
                            $('#' + rowId).val(rate);
                        });
                    }
                });
            }

            // Auto-fill Cup Charge from Primary Billing Party's configured cups_amount
            function applyPrimaryCupCharge(primaryClientId) {
                if (!primaryClientId || billingLocked) return;
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
                if (billingLocked) return; // locked: no JS manipulation needed

                var ids = $('#selectBillingParties').val() || [];
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

            if (!billingLocked) {
                $('#selectBillingParties').on('change select2:select select2:unselect', onBillingSelectionChanged);
                $('#primary_billing_party_id').on('change', function() {
                    var val = $(this).val();
                    $('#client_id').val(val);
                    rebuildLaborRateRows();
                    // Update Cup Charge immediately when Primary changes
                    if (val) applyPrimaryCupCharge(val);
                });
            }

            // Brand → restrict bill-to options
            $('#brand').on('change', function() {
                loadBillToOptionsForBrand($(this).val());
            });
            // initial load
            loadBillToOptionsForBrand($('#brand').val());
            updateBillingPartyUi();


        });
    </script>

    <script>
        let selectedFiles = [];
        let removedAttachmentIds = [];
    
        // Handle new file selection
        $('#attachments').on('change', function () {
            const newFiles = Array.from(this.files);
    
            // Add new files (prevent duplicates)
            newFiles.forEach(file => {
                if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    selectedFiles.push(file);
                }
            });
    
            // Reset input to allow same file selection again
            this.value = '';
    
            renderNewPreview();
        });
    
        // Render preview for new uploads
        function renderNewPreview() {
            const preview = $('#new-preview');
            preview.html('');
    
            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const wrapper = $('<div>').addClass('preview-box').css({
                        position: 'relative',
                        width: '120px',
                        textAlign: 'center'
                    });
    
                    const removeBtn = $('<span>&times;</span>').css({
                        position: 'absolute',
                        top: '0px',
                        right: '5px',
                        background: '#dc3545',
                        color: '#fff',
                        borderRadius: '50%',
                        width: '20px',
                        height: '20px',
                        textAlign: 'center',
                        cursor: 'pointer',
                        zIndex: 10,
                        fontWeight: 'bold',
                        display: 'flex',
                        justifyContent: 'center',
                        alignItems: 'center',
                        fontSize: '10px'
                    }).attr('data-index', index);
    
                    removeBtn.on('click', function () {
                        const i = $(this).data('index');
                        selectedFiles.splice(i, 1);
                        updateInputFiles();
                        renderNewPreview();
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
                            width: '100px',
                            height: '100px',
                            borderRadius: '8px',
                            objectFit: 'cover'
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
    
                    wrapper.append(removeBtn).append(media);
                    preview.append(wrapper);
                };
    
                if (file.type.startsWith('image/') || file.type.startsWith('video/')) {
                    reader.readAsDataURL(file);
                } else {
                    reader.onload();
                }
            });
        }
    
        // Update the input field with current selected files
        function updateInputFiles() {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => dataTransfer.items.add(file));
            document.getElementById('attachments').files = dataTransfer.files;
        }
    
        // Handle existing file removal
        $(document).on('click', '.remove-existing', function () {
            const box = $(this).closest('.preview-box');
            const id = box.data('id');
            removedAttachmentIds.push(id);
            $('#remove_id').val(removedAttachmentIds.join(','));
            box.remove();
        });
    
        // ✅ Ensure files are synced before form submission
        $('form').on('submit', function () {
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
        const fieldMap = {
            'status': '#best_status', 'target_audience': '#best_target_audience',
            'account_id': '#best_account_id', 'account_name': '#best_account_name',
            'Associated_with_Demand_Creation_Station': '#best_demand_creation',
            'wet_or_dry': '[name="best_wet_or_dry"]:checked',
            'objective': '#best_objective', 'description': '#best_description',
            'sampling_type': '#best_sampling_type', 'attendees': '#best_attendees',
            'interactions': '#best_interactions', 'event_results': '#best_event_results',
            'key_learnings_future_considerations': '#best_key_learnings',
            'consumer_feedback': '#best_consumer_feedback', 'account_feedback': '#best_account_feedback',
            'budget': '#best_budget', 'Account_Premise_Type': '#best_account_premise_type',
            'program_id': '#best_program_id', 'program_name': '#best_program_name',
            'tag_id': '#best_tag_id', 'tag_name': '#best_tag_name',
            '1l_bottles': '#best_1l_bottles', '175l_bottles': '#best_175l_bottles',
            '750ml_bottles': '#best_750ml_bottles', '375ml_bottles': '#best_375ml_bottles',
            'total_samples_distributed': '#best_total_samples',
            'total_samples_distributed_cocktail': '#best_total_samples_cocktail',
            'total_samples_distributed_neat_shot': '#best_total_samples_neat_shot',
            'end_date': '#best_end_date', 'start_time': '#best_start_time', 'end_time': '#best_end_time'
        };
        function collectBestData() {
            const data = {};
            for (const [key, selector] of Object.entries(fieldMap)) {
                data[key] = key === 'wet_or_dry' ? ($('[name="best_wet_or_dry"]:checked').val() || '') : ($(selector).val() || '');
            }
            return data;
        }
        function populateBestModal(data) {
            if (!data) return;
            for (const [key, selector] of Object.entries(fieldMap)) {
                if (key === 'wet_or_dry') {
                    $('[name="best_wet_or_dry"]').prop('checked', false);
                    if (data[key]) $('[name="best_wet_or_dry"][value="' + data[key] + '"]').prop('checked', true);
                } else { $(selector).val(data[key] || ''); }
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
        function showBestValidationErrors(missingKeys) {
            clearBestValidationErrors();
            missingKeys.forEach(key => {
                const selector = fieldToElement[key];
                if (selector) {
                    const $el = $(selector);
                    $el.addClass('is-invalid');
                    if (!$el.next('.best-field-error').length) {
                        $el.after('<div class="best-field-error text-danger small mt-1">' + fieldLabels[key] + ' is required</div>');
                    }
                }
            });
        }
        $('input[name="integrate_with_best"]').on('change', function() {
            if ($(this).val() === '1' && $(this).is(':checked')) {
                const existing = $('#bestEventDataInput').val();
                if (existing) { try { populateBestModal(JSON.parse(existing)); } catch(e) {} }
                clearBestValidationErrors();
                new bootstrap.Modal(document.getElementById('bestFieldsModal')).show();
            }
        });
        $('#bestModalCancel, #bestModalClose').on('click', function() {
            clearBestValidationErrors();
            if (!$('#bestEventDataInput').val()) $('#normalEventRadio').prop('checked', true);
        });
        $('#bestModalSave').on('click', function() {
            clearBestValidationErrors();
            const required = Object.keys(fieldLabels);
            const data = collectBestData();
            const missing = required.filter(k => data[k] === undefined || data[k] === null || String(data[k]).trim() === '');
            if (missing.length) {
                showBestValidationErrors(missing);
                const missingLabels = missing.map(k => fieldLabels[k]);
                const $alert = $('<div class="alert alert-danger alert-dismissible fade show mt-2 best-field-error" role="alert">' +
                    '<strong>Please fill the following required fields:</strong><ul class="mb-0 mt-1">' +
                    missingLabels.map(l => '<li>' + l + '</li>').join('') +
                    '</ul><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
                $('#bestFieldsModal .modal-body').prepend($alert);
                $('#bestFieldsModal .modal-body').scrollTop(0);
                return;
            }
            $('#bestEventDataInput').val(JSON.stringify(data));
            bootstrap.Modal.getInstance(document.getElementById('bestFieldsModal')).hide();
        });
        const oldBestData = $('#bestEventDataInput').val();
        if (oldBestData) { try { populateBestModal(JSON.parse(oldBestData)); } catch(e) {} }

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
            init() { this.loadData(); this.bindEvents(); }
            async loadData() { try { this.data = await $.ajax({ url: this.apiUrl, method: 'GET' }) || []; } catch(e) {} }
            bindEvents() {
                this.$input.on('input', (e) => this.handleInput(e))
                    .on('focus', () => { if (this.$input.val().trim().length >= 1) this.filterData(this.$input.val().trim()); })
                    .on('blur', () => setTimeout(() => this.$dropdown.hide(), 200));
            }
            handleInput(e) {
                this.$hiddenInput.val('');
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {
                    const q = e.target.value.trim();
                    q.length >= 1 ? this.filterData(q) : this.$dropdown.hide();
                }, 300);
            }
            filterData(q) {
                const lower = q.toLowerCase();
                const items = this.data.filter(i => i.Name && i.Name.toLowerCase().includes(lower) && !i.IsDeleted).slice(0, 10);
                if (!items.length) { this.$dropdown.html('<div style="padding:.5rem .75rem;color:#6c757d;font-style:italic;">No results</div>').show(); return; }
                this.$dropdown.html(items.map(i => `<div class="best-typeahead-item" data-id="${i.Id}" data-name="${i.Name}" style="padding:.5rem .75rem;cursor:pointer;border-bottom:1px solid #f8f9fa;">${i.Name}</div>`).join('')).show();
                this.$dropdown.find('.best-typeahead-item').on('click', (e) => { const $i = $(e.currentTarget); this.$input.val($i.data('name')); this.$hiddenInput.val($i.data('id')); this.$dropdown.hide(); });
            }
        }
        $('.best-typeahead-input').each(function() { new BestModalTypeahead($(this)); });
    });
    </script>
    @include('admin.event.partials.event-venue-script')
    @include('admin.event.partials.best-form-validation-script')
    @include('admin.event.partials.event-form-validation-ui')
@endpush
