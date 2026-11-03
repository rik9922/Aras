@extends('layout.app', ['page_name' => 'View Event'])
@section('content')
@php
    $isAdminOrManager = Auth::check() && in_array((int) Auth::user()->role_id, [1, 6], true);
    $isAmbassador = Auth::check() && (int) Auth::user()->role_id === 5;
    $billingParties = $event->billingParties ?? collect();
    $fallbackBillingClient = $billingParties->isEmpty() ? ($event->client ?? null) : null;
    $legacyClientId = is_array($event->client_id) ? implode(', ', $event->client_id) : $event->client_id;
    $money = fn ($value) => '$' . number_format((float) ($value ?? 0), 2);
@endphp
@push('styles')
    <style>
        .section-title {
            font-size: var(--font-size-section-title);
            font-weight: 600;
            position: relative;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            color: #0b132b;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: #ab7017e0;
            border-radius: 3px;
        }

        .section-container {
            background: white;
            padding: 2rem;
            margin-bottom: 1.5rem;
        }

        .event-header {
            background: #ffff;
            color: #212529;
            padding-bottom: 2rem;
            padding-top: 0px;
        }

        .event-title {
            font-size: var(--font-size-page-title);
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .best-event-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.25rem 0.7rem;
            font-size: var(--font-size-caption);
            font-weight: 700;
            line-height: 1.2;
        }

        .best-event-badge.is-best {
            background: #0b132b;
            color: #fff;
        }

        .best-event-badge.not-best {
            background: #f2f2f2;
            color: #3a506b;
            border: 1px solid rgba(58, 80, 107, 0.2);
        }

        .event-status-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.25rem 0.7rem;
            font-size: var(--font-size-caption);
            font-weight: 700;
            line-height: 1.2;
        }

        .event-status-badge.completed {
            background: #198754;
            color: #fff;
        }

        .event-status-badge.cancelled {
            background: #dc3545;
            color: #fff;
        }

        .event-status-badge.pending {
            background: #fff3cd;
            color: #664d03;
            border: 1px solid rgba(102, 77, 3, 0.18);
        }

        .event-subtitle {
            font-size: var(--font-size-body);
            opacity: 0.8;
            color: black;
            display: flex;
            align-items: center;
            width: 100%;
            gap: 6px;
        }

        span.event-dot {
            display: inline-block;
            width: 5px;
            height: 5px;
            background: #000000a6;
            border-radius: 100%;
        }

        .event-incl {
            padding: 1rem;
            border-radius: 12px;
            background: #d79e8d17;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            min-height: 100px;
            border: 1px solid #d79e8d40
        }

        .event-incl .info_one {
            font-weight: 600;
            color: #0b132b;
            margin-bottom: 0.5rem;
            font-size: var(--font-size-subheading);
        }

        .billing-party-card {
            background: #fff;
            border: 1px solid #d79e8d40;
            border-radius: 8px;
            padding: 1rem;
            grid-column: 1 / -1;
            width: 100%;
        }

        .billing-party-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.85rem;
        }

        .billing-party-name {
            font-weight: 700;
            color: #0b132b;
        }

        .primary-billing-badge {
            background: #0b132b;
            color: #fff;
            border-radius: 999px;
            font-size: var(--font-size-caption);
            padding: 0.25rem 0.65rem;
            white-space: nowrap;
        }

        .billing-party-row {
            display: grid;
            grid-template-columns: minmax(120px, 0.4fr) 1fr;
            gap: 0.75rem;
            padding: 0.35rem 0;
            border-top: 1px solid rgba(11, 19, 43, 0.08);
            font-size: var(--font-size-body);
        }

        .billing-party-label {
            color: #5f6b7a;
            font-weight: 600;
        }

        .billing-party-value {
            color: #0b132b;
            word-break: break-word;
        }

        .billing-shared-note {
            grid-column: 1 / -1;
            background: #f8f9fa;
            border: 1px solid rgba(11, 19, 43, 0.08);
            border-radius: 8px;
            padding: 0.85rem 1rem;
            color: #0b132b;
        }

        .info_one i {
            margin-right: 4px;
            color: #D79E8D
        }

        .info_two {
            font-size: var(--font-size-body);
        }

        .info-grid.one {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            padding-top: 12px;
            padding-bottom: 35px;
        }

        .attach_ments {
            /*width: 100px;*/
            /*height: 100px;*/
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            /*box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);*/
            transition: all 0.3s ease;
        }

        .attach_ments img,
        .attach_ments video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .attachmentss {
            padding-top: 12px;
            padding-bottom: 35px;
            padding-inline: 15px;
        }

        .recap-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1rem;
            overflow: hidden;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .recap-table th {
            color: #3a506b;
            font-weight: 500;
            text-align: left;
            padding: 1rem;
            background: #f2f2f2;
        }

        .recap-table td {
            padding: 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .btn-view-recap-table {
            background: #3a506b !important;
            color: white !important;
            border: none;
            border-radius: 30px !important;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        @media (max-width: 767px) {
            .section-container {
                padding: 15px;
            }
        }
    </style>
@endpush
    <div class="container py-3">
        <div class="card border-0 shadow mt-2">
            <div class="section-container">
                <div class="event-header">
                    <div class="event-title">
                        <span>{{ $event->event_name ?? '-' }} {{ $event->eventType ? $event->eventType->name : '' }}</span>
                        <span class="best-event-badge {{ $event->integrate_with_best ? 'is-best' : 'not-best' }}">
                            {{ $event->integrate_with_best ? 'BEST Event' : 'Not BEST' }}
                        </span>
                        @if ($event->event_status == '1')
                            <span class="event-status-badge completed">Completed</span>
                        @elseif ($event->event_status == '2')
                            <span class="event-status-badge cancelled">Cancelled</span>
                        @else
                            <span class="event-status-badge pending">Pending</span>
                        @endif
                    </div>
                    <div class="event-subtitle">
                        <div>{{ $event->brand ? $event->brand->brand_name : '-' }}</div>
                        <span class="event-dot"></span>
                        <div>{{ $event->Dayname }}</div>
                        <div>{{ $event->FormattedDate }}</div>
                    </div>
                </div>

                @auth
                    @if (in_array((int) auth()->user()->role_id, [1, 6], true) && $event->budget_approval_status === 'pending')
                        @php
                            $budgetApproveUrl = (int) auth()->user()->role_id === 6
                                ? route('manager.events.budget.approve', $event)
                                : route('admin.events.budget.approve', $event);
                            $budgetDenyUrl = (int) auth()->user()->role_id === 6
                                ? route('manager.events.budget.deny', $event)
                                : route('admin.events.budget.deny', $event);
                        @endphp
                        <div class="alert alert-warning">
                            <strong>Budget / allocation approval pending.</strong>
                            Completing this event would exceed the Bill To program limits. Approve to allow staff to mark it Completed, or deny to keep it blocked.
                            <form method="POST" action="{{ $budgetApproveUrl }}" class="d-inline ms-2">@csrf
                                <button type="submit" class="btn btn-sm btn-success">Approve budget exception</button>
                            </form>
                            <form method="POST" action="{{ $budgetDenyUrl }}" class="d-inline ms-1">@csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Deny this budget exception?');">Deny</button>
                            </form>
                        </div>
                    @elseif (in_array((int) auth()->user()->role_id, [1, 6], true) && $event->budget_approval_status === 'approved')
                        <div class="alert alert-success small">
                            <strong>Budget exception approved.</strong> This event may be marked Completed even if it exceeds program limits.
                        </div>
                    @elseif (in_array((int) auth()->user()->role_id, [1, 6], true) && $event->budget_approval_status === 'denied')
                        <div class="alert alert-danger small">
                            <strong>Budget exception denied.</strong> Adjust billable amounts, client budget settings, or contact management.
                        </div>
                    @endif
                @endauth

                <h3 class="section-title">Event Information</h3>

                <div class="info-grid one">
                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-hashtag"></i> Event ID</div>
                        <div class="info_two">#{{ $event->id }}</div>
                    </div>

                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-calendar-check"></i> Event Type</div>
                        <div class="info_two">{{ $event->eventType ? $event->eventType->name : '-' }}</div>
                    </div>

                    @if($event->venueType)
                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-store"></i> Venue Type</div>
                        <div class="info_two">{{ $event->venueType->name ?? '-' }}</div>
                    </div>
                    @endif

                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-sun"></i> Day</div>
                        <div class="info_two">{{ $event->Dayname }}</div>
                    </div>

                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-calendar-day"></i> Date</div>
                        <div class="info_two">{{ $event->FormattedDate }}</div>
                    </div>

                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-clock"></i> Time/Total Duration</div>
                        <div class="info_two">{!! $event->FormattedTime !!}</div>
                    </div>

                    @unless($isAmbassador)
                        <div class="event-incl">
                            <div class="info_one"><i class="fas fa-business-time"></i> Billable Hours</div>
                            <div class="info_two">{{ $event->billable_hours_formatted }}</div>
                        </div>
                    @endunless

                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-map-marker-alt"></i> Location</div>
                        <div class="info_two">
                            {{ $event->location ? $event->location->address ?? $event->location->venue_name : '-' }}</div>
                    </div>

                    @if(!empty($event->city))
                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-city"></i> City</div>
                        <div class="info_two">{{ $event->city }}</div>
                    </div>
                    @endif

                    @if(!empty($event->event_address))
                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-road"></i> Event Address</div>
                        <div class="info_two">{{ $event->event_address }}</div>
                    </div>
                    @endif

                    @if(!empty($event->region))
                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-globe-americas"></i> Region</div>
                        <div class="info_two">{{ $event->region }}</div>
                    </div>
                    @endif

                    @if($event->manager)
                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-user-tie"></i> Manager</div>
                        <div class="info_two">{{ $event->manager->name }}</div>
                    </div>
                    @endif

                    @if($event->markets && $event->markets->count())
                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-map"></i> Markets</div>
                        <div class="info_two">{{ $event->markets->pluck('name')->join(', ') }}</div>
                    </div>
                    @endif

                    @if(!empty($event->position))
                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-users"></i> Positions</div>
                        <div class="info_two">{{ $event->position }}</div>
                    </div>
                    @endif

                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-id-badge"></i> Age Restricted</div>
                        <div class="info_two">{{ $event->age_restricted ? 'Yes' : 'No' }}</div>
                    </div>

                    @unless($isAmbassador)
                        <div class="event-incl">
                            <div class="info_one"><i class="fas fa-hand-paper"></i> Open for Applications</div>
                            <div class="info_two">{{ $event->apply_event ? 'Yes' : 'No' }}</div>
                        </div>
                    @endunless

                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-clipboard-check"></i> Recap Needed</div>
                        <div class="info_two">{{ $event->recap_needed ? 'Yes' : 'No' }}</div>
                    </div>

                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-bolt"></i> Pinata Pay</div>
                        <div class="info_two">{{ $event->pinata_pay ? 'Yes' : 'No' }}</div>
                    </div>

                    @unless($isAmbassador)
                        @if(!empty($event->booked_by))
                            <div class="event-incl">
                                <div class="info_one"><i class="fas fa-user-edit"></i> Booked By</div>
                                <div class="info_two">{{ $event->booked_by_display }}</div>
                            </div>
                        @endif
                    @endunless

                </div>

                <h3 class="section-title">Brand & Product Details</h3>

                <div class="info-grid">
                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-glass-whiskey"></i> Brand</div>
                        <div class="info_two">{{ $event->brand ? $event->brand->brand_name : '-' }}</div>
                    </div>

                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-wine-bottle"></i> Varietals</div>
                        <div class="info_two">{{ $event->varietals }}</div>
                    </div>

                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-box"></i> Kit</div>
                        <div class="info_two">{{ $event->kit == 1 ? 'Yes' : 'No' }}</div>
                    </div>

                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-building"></i> Department</div>
                        <div class="info_two">{{ $event->department ? $event->department->name : '-' }}</div>
                    </div>

                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-truck"></i> Distributor</div>
                        <div class="info_two">{{ $event->distributor ? $event->distributor->name : '-' }}</div>
                    </div>

                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-user"></i> Assigned</div>
                        <div class="info_two">{!! ambassadorName($event->id) !!}</div>
                    </div>

                    <div class="event-incl">
                        <div class="info_one"><i class="fa-solid fa-shirt"></i> Uniform</div>
                        <div class="info_two">{{ $event->uniform ?? '-' }}</div>
                    </div>

                    <div class="event-incl" style="grid-column: 1 / -1;">
                        <div class="info_one"><i class="fa-solid fa-note-sticky"></i> Notes</div>
                        <div class="info_two">{!! nl2br(e($event->notes ?? '-')) !!}</div>
                    </div>

                    @if($event->kit == 1 && !empty($event->kit_description))
                    <div class="event-incl" style="grid-column: 1 / -1;">
                        <div class="info_one"><i class="fas fa-box-open"></i> Kit Description</div>
                        <div class="info_two">{!! nl2br(e($event->kit_description)) !!}</div>
                    </div>
                    @endif

                    @if($isAdminOrManager)
                    <div class="event-incl">
                        <div class="info_one"><i class="fa-solid fa-note-sticky"></i> Internal Notes</div>
                        <div class="info_two">{!! $event->internal_notes !!}</div>
                    </div>
                    @endif
                </div>

                @if($isAdminOrManager && ($billingParties->isNotEmpty() || $fallbackBillingClient || !empty($legacyClientId)))
                <h3 class="section-title">Billing Parties</h3>
                <div class="info-grid">
                    @forelse($billingParties as $party)
                        @php
                            $client = $party->client;
                            $profile = $client?->clientProfile;
                            $cityState = trim(implode(', ', array_filter([$profile?->city ?? $client?->city, $profile?->state])));
                            $addressParts = array_filter([
                                $profile?->address ?? $client?->address,
                                $cityState,
                                $profile?->zip_code ?? $client?->zip_code,
                                $profile?->country ?? $client?->country,
                            ]);
                        @endphp
                        <div class="billing-party-card">
                            <div class="billing-party-header">
                                <div class="billing-party-name">{{ $client?->name ?? 'Bill To #' . $party->client_id }}</div>
                                @if($party->is_primary)
                                    <span class="primary-billing-badge">Primary</span>
                                @endif
                            </div>

                            @if($client?->email)
                            <div class="billing-party-row">
                                <div class="billing-party-label">Email</div>
                                <div class="billing-party-value">{{ $client->email }}</div>
                            </div>
                            @endif

                            @if($client?->phone)
                            <div class="billing-party-row">
                                <div class="billing-party-label">Phone</div>
                                <div class="billing-party-value">{{ $client->phone }}</div>
                            </div>
                            @endif

                            @if($profile?->company_name ?? $client?->company_name)
                            <div class="billing-party-row">
                                <div class="billing-party-label">Company</div>
                                <div class="billing-party-value">{{ $profile?->company_name ?? $client?->company_name }}</div>
                            </div>
                            @endif

                            @if(count($addressParts))
                            <div class="billing-party-row">
                                <div class="billing-party-label">Billing Address</div>
                                <div class="billing-party-value">{{ implode(' ', $addressParts) }}</div>
                            </div>
                            @endif

                            @if($profile?->primary_billing_contact ?? $client?->primary_billing_contact)
                            <div class="billing-party-row">
                                <div class="billing-party-label">Billing Contact</div>
                                <div class="billing-party-value">{{ $profile?->primary_billing_contact ?? $client?->primary_billing_contact }}</div>
                            </div>
                            @endif

                            <div class="billing-party-row">
                                <div class="billing-party-label">Labor Rate</div>
                                <div class="billing-party-value">
                                    {{ $party->labor_rate !== null ? $money($party->labor_rate) : ($profile?->default_labor_amount !== null ? $money($profile->default_labor_amount) . ' default' : '-') }}
                                </div>
                            </div>

                            @if($profile?->default_buyback_amount !== null)
                            <div class="billing-party-row">
                                <div class="billing-party-label">Default Buyback</div>
                                <div class="billing-party-value">{{ $money($profile->default_buyback_amount) }}</div>
                            </div>
                            @endif

                            @if($profile?->default_cups_amount !== null)
                            <div class="billing-party-row">
                                <div class="billing-party-label">Default Cups</div>
                                <div class="billing-party-value">{{ $money($profile->default_cups_amount) }}</div>
                            </div>
                            @endif

                            @if($party->is_primary && (float) ($event->cups_amount ?? 0) > 0)
                            <div class="billing-party-row">
                                <div class="billing-party-label">Cup Charge</div>
                                <div class="billing-party-value">{{ $money($event->cups_amount) }}</div>
                            </div>
                            @endif
                        </div>
                    @empty
                        <div class="billing-party-card">
                            <div class="billing-party-header">
                                <div class="billing-party-name">{{ $fallbackBillingClient?->name ?? 'Bill To #' . $legacyClientId }}</div>
                                <span class="primary-billing-badge">Primary</span>
                            </div>

                            @if($fallbackBillingClient?->email)
                            <div class="billing-party-row">
                                <div class="billing-party-label">Email</div>
                                <div class="billing-party-value">{{ $fallbackBillingClient->email }}</div>
                            </div>
                            @endif

                            @if($fallbackBillingClient?->phone)
                            <div class="billing-party-row">
                                <div class="billing-party-label">Phone</div>
                                <div class="billing-party-value">{{ $fallbackBillingClient->phone }}</div>
                            </div>
                            @endif

                            <div class="billing-party-row">
                                <div class="billing-party-label">Client ID</div>
                                <div class="billing-party-value">{{ $legacyClientId ?? '-' }}</div>
                            </div>
                        </div>
                    @endforelse

                </div>
                @endif

                @auth
                @if(in_array((int) auth()->user()->role_id, [1, 6]))
                <h3 class="section-title">Compensation & Fees</h3>
                <div class="info-grid">
                    @if(!empty($event->payment_type))
                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-credit-card"></i> Payment Type</div>
                        <div class="info_two">{{ $event->payment_type }}</div>
                    </div>
                    @endif

                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-dollar-sign"></i> Payment Status</div>
                        <div class="info_two">{{ $event->payment_status ? 'Paid' : 'Unpaid' }}</div>
                    </div>

                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-receipt"></i> Receipt Required</div>
                        <div class="info_two">{{ $event->receipt ? 'Yes' : 'No' }}</div>
                    </div>

                    @if(!empty($event->expedite_fee))
                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-shipping-fast"></i> Expedite Fee</div>
                        <div class="info_two">${{ $event->expedite_fee }}</div>
                    </div>
                    @endif

                    @if(!empty($event->shipping_fee))
                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-box"></i> Shipping/Postage Fee</div>
                        <div class="info_two">${{ $event->shipping_fee }}</div>
                    </div>
                    @endif

                    @if(!empty($event->parking_fee))
                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-parking"></i> Parking Fee</div>
                        <div class="info_two">${{ $event->parking_fee }}</div>
                    </div>
                    @endif

                    @if(!empty($event->drive_time))
                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-car"></i> Drive-Time Pay</div>
                        <div class="info_two">${{ $event->drive_time }}</div>
                    </div>
                    @endif

                    @if(!empty($event->labor_amount) || !empty($event->buyback_amount) || !empty($event->cups_amount))
                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-money-bill-wave"></i> Labor Amount</div>
                        <div class="info_two">${{ number_format($event->labor_amount ?? 0, 2) }}</div>
                    </div>
                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-wine-glass-alt"></i> Buyback Amount</div>
                        <div class="info_two">${{ number_format($event->buyback_amount ?? 0, 2) }}</div>
                    </div>
                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-coffee"></i> Cups Amount</div>
                        <div class="info_two">${{ number_format($event->cups_amount ?? 0, 2) }}</div>
                    </div>
                    @endif
                </div>

                @if(!empty($event->admin_status))
                <h3 class="section-title">Admin Status</h3>
                <div class="info-grid">
                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-info-circle"></i> Admin Status</div>
                        <div class="info_two">{{ ucwords(str_replace(['_', '-'], ' ', $event->admin_status)) }}</div>
                    </div>

                    @if(!empty($event->admin_status_set_at))
                    <div class="event-incl">
                        <div class="info_one"><i class="fas fa-calendar-check"></i> Status Set At</div>
                        <div class="info_two">{{ \Carbon\Carbon::parse($event->admin_status_set_at)->format('M d, Y h:i A') }}</div>
                    </div>
                    @endif

                    @if(!empty($event->cancellation_reason))
                    <div class="event-incl" style="grid-column: 1 / -1;">
                        <div class="info_one"><i class="fas fa-ban"></i> Cancellation Reason</div>
                        <div class="info_two">{!! nl2br(e($event->cancellation_reason)) !!}</div>
                    </div>
                    @endif
                </div>
                @endif
                @endif
                @endauth

                @if (!empty($event->eventAttachments) && count($event->eventAttachments) > 0)
                    <h3 class="section-title">Event Attachments</h3>
                
                    <div class="row g-3">
                        @foreach ($event->eventAttachments as $attachment)
                            @php
                                $fileUrl = asset($attachment->filename);
                                $fileType = $attachment->type;
                                $fileName = basename($attachment->filename);
                            @endphp
                
                            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                                <div class="attach_ments text-center" style="cursor: pointer;">
                                    @if (Str::startsWith($fileType, 'image/'))
                                        <img src="{{ $fileUrl }}"
                                             class="preview-media"
                                             data-url="{{ $fileUrl }}"
                                             data-type="image"
                                             style="height: 150px; object-fit: cover; border-radius: 8px;"
                                             onerror="this.style.display='none'">
                
                                    @elseif (Str::startsWith($fileType, 'video/'))
                                        <video class="preview-media"
                                               controls
                                               data-url="{{ $fileUrl }}"
                                               data-type="video"
                                               style="height: 150px; object-fit: cover; border-radius: 8px;"
                                               onerror="this.style.display='none'">
                                            <source src="{{ $fileUrl }}" type="{{ $fileType }}">
                                        </video>
                
                                    @else
                                        {{-- 📄 Other file types (PDF, DOC, etc.) --}}
                                        <div class="preview-media"
                                             data-url="{{ $fileUrl }}"
                                             data-type="file"
                                             style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 150px; border: 1px solid #ddd; border-radius: 8px; background: #f8f9fa;">
                                            <i class="fa-solid fa-file" style="font-size: 40px; color: #007bff; margin-bottom: 8px;"></i>
                                            <span style="font-size: var(--font-size-caption); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100px;">{{ $fileName }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif


                @if (Auth::check())
                    @if (!empty($recaps) && count($recaps) > 0)
                        <h3 class="section-title">Recap Details</h3>
                        <div class="container px-0">
	                            <table class="table align-middle recap-table">
	                                <thead>
	                                    <tr>
	                                        <th>Recap</th>
	                                        <th style="text-align: right">Action</th>
	                                    </tr>
	                                </thead>
	                                <tbody>
	                                    @foreach ($recaps as $recap)
	                                        <tr>
	                                            <td>You can view recap details by clicking this button.</td>
	                                            <td style="text-align: right">
                                                @if (Auth::user()->role_id == 5)
                                                    <a
                                                        href="{{ route('ambassador.view-recap', ['id' => $recap->event_id]) }}">
                                                        <button type="button" class="btn btn-design btn-view-recap-table">
                                                            <span><i class="fas fa-eye"></i> View</span>
                                                        </button>
                                                    </a>
                                                @elseif (Auth::user()->role_id == 4)
                                                    <a
                                                        href="{{ route('location.view-recap', ['id' => $recap->event_id]) }}">
                                                        <button type="button" class="btn btn-design btn-view-recap-table">
                                                            <span><i class="fas fa-eye"></i> View</span>
                                                        </button>
                                                    </a>
                                                @elseif (Auth::user()->role_id == 3)
                                                    <a href="{{ route('client.view-recap', ['id' => $recap->event_id]) }}">
                                                        <button type="button" class="btn btn-design btn-view-recap-table">
                                                            <span><i class="fas fa-eye"></i> View</span>
                                                        </button>
                                                    </a>
                                                @else
                                                    <a href="{{ route('admin.view-recap', ['id' => $recap->event_id]) }}">
                                                        <button type="button" class="btn btn-design btn-view-recap-table">
                                                            <span><i class="fas fa-eye"></i> View</span>
                                                        </button>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif

                <div class="d-flex gap-2 py-2 justify-content-end px-2">
                    <button type="button" class="btn btn-design grey-color" onclick="window.history.go(-1)">
                        <span class="text-nowrap">Back</span>
                    </button>
                </div>
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
@endsection
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mediaElements = document.querySelectorAll('.preview-media');
        mediaElements.forEach(element => {
            element.addEventListener('click', function() {
                const mediaUrl = element.getAttribute('data-url');
                window.open(mediaUrl, '_blank');
            });
        });
    });
</script>
@endpush
<!--@push('scripts')-->
<!--<script>-->
<!--    document.addEventListener('DOMContentLoaded', function() {-->
<!--        const mediaElements = document.querySelectorAll('.preview-media');-->
<!--        mediaElements.forEach(element => {-->
<!--            element.addEventListener('click', function() {-->
<!--                const mediaUrl = element.getAttribute('data-url');-->
<!--                const mediaType = element.getAttribute('data-type');-->
<!--                const mediaModalBody = document.getElementById('mediaModalBody');-->
<!--                mediaModalBody.innerHTML = '';-->
    
<!--                if (mediaType === 'image') {-->
<!--                    const imgElement = document.createElement('img');-->
<!--                    imgElement.src = mediaUrl;-->
<!--                    imgElement.style.width = '100%';-->
<!--                    imgElement.style.height = 'auto';-->
<!--                    imgElement.style.borderRadius = '8px';-->
<!--                    mediaModalBody.appendChild(imgElement);-->
<!--                } else if (mediaType === 'video') {-->
<!--                    const videoElement = document.createElement('video');-->
<!--                    videoElement.controls = true;-->
<!--                    videoElement.style.width = '100%';-->
<!--                    videoElement.style.height = 'auto';-->
<!--                    const sourceElement = document.createElement('source');-->
<!--                    sourceElement.src = mediaUrl;-->
<!--                    videoElement.appendChild(sourceElement);-->
<!--                    mediaModalBody.appendChild(videoElement);-->
<!--                } else if (mediaType === 'file') {-->
<!--                    const fileFrame = document.createElement('iframe');-->
<!--                    fileFrame.src = mediaUrl;-->
<!--                    fileFrame.style.width = '100%';-->
<!--                    fileFrame.style.height = '500px';-->
<!--                    fileFrame.style.border = 'none';-->
<!--                    mediaModalBody.appendChild(fileFrame);-->
<!--                }-->
    
<!--                const modal = new bootstrap.Modal(document.getElementById('mediaModal'));-->
<!--                modal.show();-->
<!--            });-->
<!--        });-->
<!--    });-->

<!--</script>-->
<!--@nedpush-->
