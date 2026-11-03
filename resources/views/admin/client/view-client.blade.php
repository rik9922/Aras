@extends('layout.app', ['page_name' => 'Bill To'])
@php $routePrefix = request()->routeIs('manager.*') ? 'manager' : 'admin'; @endphp
@section('content')
    <div class="container py-3">
        <div class="page-card-design">

            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1">{{ $client->name }} Details</h5>
                    <div class="fixed-border"></div>
                </div>
                <div>
                    <a href="{{ url()->previous() }}" class="btn-ds btn-dark-ds grey-color btn-sm">
                        Back
                    </a>
                    <a href="{{ route($routePrefix.'.client.transactions', ['client' => $client->id]) }}">
                        <button type="button" class="btn-ds btn-primary-ds btn-sm">
                            Show Transactions
                        </button>
                    </a>
                </div>

            </div>

            {{-- All client-specific fields come from the clientProfile (clients table) --}}
            @php $cp = $client->clientProfile; @endphp

            {{-- Bill To Info --}}
            <div class="row g-3">

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header fw-semibold">
                            Basic Information
                        </div>
                        <div class="card-body">
                            <p><strong>Name:</strong> {{ $client->name }}</p>
                            <p><strong>Company Name:</strong> {{ $cp?->company_name ?? '—' }}</p>
                            <p><strong>Primary Billing Contact:</strong> {{ $cp?->primary_billing_contact ?? '—' }}</p>
                            <p><strong>Email:</strong> {{ $client->email }}</p>
                            <p><strong>Phone:</strong> {{ $client->phone ?? '—' }}</p>
                            <p><strong>Status:</strong>
                                <span class="badge {{ $client->status ? 'bg-success' : 'bg-danger' }}">
                                    {{ $client->status ? 'Active' : 'Inactive' }}
                                </span>
                            </p>
                            <p><strong>Created At:</strong> {{ $client->created_at->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header fw-semibold">
                            Address Information
                        </div>
                        <div class="card-body">
                            <p><strong>Address:</strong> {{ $cp?->address ?? '—' }}</p>
                            <p><strong>City:</strong> {{ $cp?->city ?? '—' }}</p>
                            <p><strong>State:</strong> {{ $cp?->state ?? '—' }}</p>
                            <p><strong>ZIP Code:</strong> {{ $cp?->zip_code ?? '—' }}</p>
                            <p><strong>Country:</strong> {{ $cp?->country ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header fw-semibold">
                            Business / Platform Details
                        </div>
                        <div class="card-body">
                            <p><strong>QuickBooks Customer ID:</strong> {{ $cp?->quickbooks_customer_id ?? '—' }}</p>
                            <p><strong>QB Sync Status:</strong>
                                @php
                                    $qbSynced = !empty($cp?->quickbooks_customer_id);
                                    $qbStatus = $cp?->qb_customer_sync_status;
                                    $qbLabel  = $qbSynced ? 'Synced' : ($qbStatus ? ucfirst($qbStatus) : 'Not synced');
                                    $qbClass  = $qbSynced ? 'bg-success' : (($qbStatus === 'failed') ? 'bg-danger' : 'bg-warning text-dark');
                                @endphp
                                <span class="badge {{ $qbClass }}">{{ $qbLabel }}</span>
                                @if ($cp?->qb_customer_last_sync_attempt_at)
                                    <small class="text-muted d-block">Last attempt: {{ $cp->qb_customer_last_sync_attempt_at->format('m/d/Y H:i') }}</small>
                                @endif
                                @if ($qbStatus === 'failed' && $cp?->qb_customer_last_sync_error)
                                    <small class="text-danger d-block">{{ $cp->qb_customer_last_sync_error }}</small>
                                @endif
                            </p>
                            <p><strong>Labor Rate Per Hour:</strong>
                                {{ $cp?->default_labor_amount !== null ? '$' . number_format((float) $cp->default_labor_amount, 2) : '—' }}
                            </p>
                            <p><strong>Cup Charge:</strong>
                                {{ $cp?->default_cups_amount !== null ? '$' . number_format((float) $cp->default_cups_amount, 2) : '—' }}
                            </p>
                            <p><strong>Default Buyback:</strong>
                                {{ $cp?->default_buyback_amount !== null ? '$' . number_format((float) $cp->default_buyback_amount, 2) : '—' }}
                            </p>
                            @if($cp?->notes)
                            <p class="mb-0"><strong>Notes:</strong></p>
                            <p class="text-muted" style="white-space: pre-wrap">{{ $cp->notes }}</p>
                            @else
                            <p><strong>Notes:</strong> —</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header fw-semibold">
                            Program / Budget Tracking
                        </div>
                        <div class="card-body">
                            @php
                                $hasAmountCap = $cp?->budget_max_amount !== null && (float) $cp->budget_max_amount > 0;
                                $hasEventCap  = $cp?->budget_max_events !== null && (int) $cp->budget_max_events > 0;
                            @endphp

                            <p><strong>Program / allocation name:</strong>
                                {{ $cp?->budget_program_name ?: '— (not configured)' }}
                            </p>

                            <p><strong>Max budget ($):</strong>
                                {{ $hasAmountCap ? number_format((float) $cp->budget_max_amount, 2) : 'No dollar cap' }}
                            </p>

                            <p><strong>Max completed events:</strong>
                                {{ $hasEventCap ? $cp->budget_max_events : 'No allocation cap' }}
                            </p>

                            <p><strong>Warning threshold:</strong>
                                {{ $cp?->budget_warn_percent ?? 80 }}%
                            </p>

                            <p><strong>Enforce limits:</strong>
                                <span class="badge {{ $cp?->budget_enforce ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $cp?->budget_enforce ? 'Yes' : 'No' }}
                                </span>
                            </p>

                            <p><strong>Require approval when over:</strong>
                                <span class="badge {{ $cp?->budget_require_approval ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $cp?->budget_require_approval ? 'Yes' : 'No' }}
                                </span>
                            </p>

                            <p><strong>Budget alert email:</strong>
                                {{ $cp?->budget_alert_email ?: '— (uses global + brand defaults)' }}
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
