@extends('layout.app', ['page_name' => 'Invoice Queue'])
@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/buttons.css') }}">
@endpush
@section('content')
<div class="container-fluid py-3">
    <div class="page-card-design">
        @php
            $prefix = $prefix ?? (auth()->user()?->role_id == 6 ? 'manager' : 'admin');
        @endphp

        {{-- ── Header ─────────────────────────────────────────────────────── --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <div class="h5 text-uppercase mb-0">Invoice Queue</div>
                <small class="text-muted">Supplier-wise monthly invoice queue — {{ $monthLabel }}</small>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                @if (!$isConnected)
                    <a href="{{ route($prefix . '.quickbooks.auth') }}" class="btn-ds btn-primary-ds btn-sm text-uppercase">
                        Connect QuickBooks
                    </a>
                @else
                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>QuickBooks Connected</span>
                @endif
                <a href="{{ route($prefix . '.invoices') }}" class="btn-ds btn-secondary-outline btn-sm text-uppercase">
                    QB Invoices (synced)
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- ── QuickBooks Setup Incomplete Warning ────────────────────────────── --}}
        @if (!($qbSetupReady ?? true))
            <div class="alert alert-warning d-flex justify-content-between align-items-center mb-3" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-exclamation-triangle fs-5"></i>
                    <span>
                        <strong>QuickBooks setup is incomplete.</strong>
                        Complete the setup before creating invoices.
                        @if (!($isConnected ?? true))
                            &mdash; QuickBooks is not connected.
                        @elseif (!($accountsSynced ?? true))
                            &mdash; Chart of Accounts not yet synced.
                        @elseif (!($servicesSynced ?? true))
                            &mdash; Mandatory Services are not synced.
                        @endif
                    </span>
                </div>
                <a href="{{ route($prefix . '.setting') }}" class="btn-ds btn-warning-outline btn-sm text-nowrap ms-3">
                    <i class="fas fa-cog me-1"></i>Go to QuickBooks Settings
                </a>
            </div>
        @endif

        {{-- ── Global invoice tax setting ───────────────────────────────────── --}}
        <form method="POST" action="{{ route($prefix . '.settings.updateSetting') }}"
            class="d-flex flex-wrap justify-content-between align-items-center gap-2 border rounded p-3 mb-3 bg-light">
            @csrf
            <input type="hidden" name="setting" value="event_invoice_tax">
            <input type="hidden" name="taxable" value="0">
            <div>
                <div class="fw-semibold">Invoice Tax Code</div>
                <small class="text-muted">Applies to all supplier invoice batches. QuickBooks calculates tax when enabled.</small>
            </div>
            <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" role="switch" id="eventInvoiceTaxSwitch"
                    name="taxable" value="1" onchange="this.form.submit()"
                    {{ ($eventInvoiceTaxCode ?? 'NON') === 'TAX' ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="eventInvoiceTaxSwitch">
                    {{ ($eventInvoiceTaxCode ?? 'NON') === 'TAX' ? 'TAX — Taxable' : 'NON — Non-taxable' }}
                </label>
            </div>
        </form>

        {{-- ── Filters ─────────────────────────────────────────────────────── --}}
        <form method="GET" action="{{ route($prefix . '.event-invoicing') }}" class="mb-4">
            <div class="row g-2 align-items-end">

                {{-- Month --}}
                <div class="col-6 col-md-2">
                    <label class="form-label text-uppercase small mb-1">Month</label>
                    <input type="month" name="month" class="form-control form-control-sm"
                        value="{{ $filters['month'] ?? $month }}">
                </div>

                {{-- Event name --}}
                <div class="col-12 col-md-3">
                    <label class="form-label text-uppercase small mb-1">Event</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                        value="{{ $filters['search'] ?? '' }}" placeholder="Search event name…">
                </div>

                {{-- Client (Name / Email / Phone) --}}
                <div class="col-12 col-md-3">
                    <label class="form-label text-uppercase small mb-1">Client (Name / Email / Phone)</label>
                    <input type="text" name="client_search" class="form-control form-control-sm"
                        value="{{ $filters['client_search'] ?? '' }}" placeholder="Name, email or phone…">
                </div>

                {{-- Actions --}}
                <div class="col-6 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn-ds btn-primary-ds btn-sm">Filter</button>
                    <a href="{{ route($prefix . '.event-invoicing') }}" class="btn-ds btn-secondary-outline btn-sm">Reset</a>
                </div>
            </div>
        </form>

        {{-- ── Export row ───────────────────────────────────────────────────── --}}
        <div class="d-flex flex-wrap gap-2 mb-3">

            {{-- Month-wise export --}}
            <form method="GET" action="{{ route($prefix . '.export-event-invoicing-month') }}">
                <input type="hidden" name="month" value="{{ $month }}">
                @if (!empty($filters['client_search']))
                    <input type="hidden" name="client_search" value="{{ $filters['client_search'] }}">
                @endif
                <button type="submit" class="btn-ds btn-primary-outline btn-sm text-uppercase">
                    <i class="fas fa-file-export me-1"></i>Export (Month-wise)
                </button>
            </form>

            {{-- Event-wise export --}}
            <form method="GET" action="{{ route($prefix . '.export-event-invoicing-event') }}">
                <input type="hidden" name="month" value="{{ $month }}">
                @if (!empty($filters['search']))
                    <input type="hidden" name="search" value="{{ $filters['search'] }}">
                @endif
                @if (!empty($filters['client_search']))
                    <input type="hidden" name="client_search" value="{{ $filters['client_search'] }}">
                @endif
                <button type="submit" class="btn-ds btn-primary-outline btn-sm text-uppercase">
                    <i class="fas fa-file-export me-1"></i>Export (Event-wise)
                </button>
            </form>

            {{-- Client-wise export --}}
            <form method="GET" action="{{ route($prefix . '.export-event-invoicing-client') }}">
                <input type="hidden" name="month" value="{{ $month }}">
                @if (!empty($filters['client_search']))
                    <input type="hidden" name="client_search" value="{{ $filters['client_search'] }}">
                @endif
                <button type="submit" class="btn-ds btn-primary-outline btn-sm text-uppercase">
                    <i class="fas fa-file-export me-1"></i>Export (Client-wise)
                </button>
            </form>

        </div>

        {{-- ── Supplier Summary Table ─────────────────────────────────────── --}}
        @if ($supplierRows->count() > 0)
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle" style="font-size:13px;">
                <thead class="table-light">
                    <tr>
                        <th>Supplier</th>
                        <th class="text-center text-nowrap">Events</th>
                        <th class="text-center text-nowrap">Ready</th>
                        <th class="text-center text-nowrap">Invoiced</th>
                        <th class="text-center text-nowrap">Issues</th>
                        <th class="text-end text-nowrap">Billable Total</th>
                        <th class="text-nowrap">Invoice Status</th>
                        <th class="text-end text-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($supplierRows as $row)
                        @php
                            $client = $row['client'];
                            $collapseId = 'supplier-events-' . $client->id;
                        @endphp
                        <tr>
                            <td>
                                <button type="button"
                                    class="btn btn-link p-0 text-start fw-semibold text-decoration-none"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#{{ $collapseId }}"
                                    aria-expanded="false"
                                    aria-controls="{{ $collapseId }}">
                                    <i class="fas fa-chevron-right me-1 small"></i>{{ $client->name ?? '—' }}
                                </button>
                                <div class="small text-muted">
                                    {{ $client->email ?? 'No email' }}
                                    @if ($client->clientProfile?->quickbooks_customer_id)
                                        <span class="badge bg-light text-muted border ms-1">QB {{ $client->clientProfile->quickbooks_customer_id }}</span>
                                    @else
                                        <span class="badge bg-danger ms-1">Missing QB Customer</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">{{ $row['total_events'] }}</td>
                            <td class="text-center">
                                <span class="badge bg-success">{{ $row['publishable_count'] }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info text-dark">{{ $row['invoiced_count'] }}</span>
                            </td>
                            <td class="text-center">
                                @if ($row['issue_count'] > 0)
                                    <span class="badge bg-warning text-dark">{{ $row['issue_count'] }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">${{ number_format((float) $row['total_amount'], 2) }}</td>
                            <td class="text-nowrap">
                                <span class="badge {{ $row['summary']['class'] }}">{{ $row['summary']['label'] }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <button type="button"
                                    class="btn-ds btn-secondary-outline btn-sm text-uppercase"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#{{ $collapseId }}">
                                    Details
                                </button>
                                @if ($isConnected && $row['publishable_count'] > 0)
                                    <form method="POST" class="d-inline"
                                        action="{{ route($prefix . '.event-invoicing.create-month-invoice', ['client' => $client->id, 'month' => $month]) }}">
                                        @csrf
                                        <button type="submit" class="btn-ds btn-primary-ds btn-sm text-uppercase">
                                            Publish
                                        </button>
                                    </form>
                                @elseif (! $isConnected)
                                    <span class="text-muted small">Connect QB</span>
                                @else
                                    <span class="text-muted small">No publishable events</span>
                                @endif
                            </td>
                        </tr>
                        <tr class="collapse bg-light" id="{{ $collapseId }}">
                            <td colspan="8" class="p-0">
                                <div class="p-3">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered align-middle mb-0" style="font-size:12px;">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-nowrap">Event Date</th>
                                                    <th>Event</th>
                                                    <th class="text-nowrap">Brand</th>
                                                    <th class="text-nowrap">Recap</th>
                                                    <th class="text-nowrap">QB Status</th>
                                                    <th class="text-end text-nowrap">Amount</th>
                                                    <th class="text-nowrap">Status</th>
                                                    <th>Issue Details</th>
                                                    <th class="text-end text-nowrap">Review</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($row['events'] as $detail)
                                                    @php
                                                        $event = $detail['event'];
                                                        $qbLabel = match($detail['qb_status']) {
                                                            'success' => ['label' => 'Synced', 'cls' => 'bg-success'],
                                                            'failed' => ['label' => 'Failed', 'cls' => 'bg-danger'],
                                                            default => ['label' => '—', 'cls' => 'bg-light text-muted border'],
                                                        };
                                                    @endphp
                                                    <tr>
                                                        <td class="text-nowrap">{{ $event->FormattedDate ?: '—' }}</td>
                                                        <td>
                                                            <div class="fw-semibold">{{ $event->event_name ?? '—' }}</div>
                                                            <div class="small text-muted">#{{ $event->id }} · {{ $event->eventType?->name ?? '—' }}</div>
                                                        </td>
                                                        <td class="text-nowrap">{{ $event->brand->brand_name ?? '—' }}</td>
                                                        <td class="text-nowrap">
                                                            <span class="badge {{ $detail['recap_class'] }}">{{ $detail['recap_label'] }}</span>
                                                        </td>
                                                        <td class="text-nowrap">
                                                            <span class="badge {{ $qbLabel['cls'] }}">{{ $qbLabel['label'] }}</span>
                                                        </td>
                                                        <td class="text-end">${{ number_format((float) $detail['billable_total'], 2) }}</td>
                                                        <td class="text-nowrap">
                                                            <span class="badge {{ $detail['status_class'] }}">{{ $detail['status_label'] }}</span>
                                                        </td>
                                                        <td>
                                                            @if (!empty($detail['issues']))
                                                                @foreach ($detail['issues'] as $issue)
                                                                    <div class="text-danger small">{{ $issue }}</div>
                                                                @endforeach
                                                            @else
                                                                <span class="text-muted small">Ready for month invoice publishing.</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-end text-nowrap">
                                                            @if ($detail['publishable'])
                                                                <a href="{{ route($prefix . '.event.invoice-review', $event) }}"
                                                                    class="btn-ds btn-secondary-outline btn-sm">
                                                                    Review/Edit
                                                                </a>
                                                            @else
                                                                <span class="text-muted small">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted">
                Showing {{ $supplierRows->firstItem() }}–{{ $supplierRows->lastItem() }} of {{ $supplierRows->total() }} suppliers
            </small>
            {{ $supplierRows->links('vendor.pagination.custom') }}
        </div>

        @else
            <div class="content-box-grey mt-3">
                <div class="h6 text-center text-uppercase my-4 text-muted">
                    No suppliers found for the selected filters in {{ $monthLabel }}
                </div>
            </div>
        @endif

    </div>{{-- .page-card-design --}}
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });

    // QB setup guard — fires when setup is incomplete
    const qbSetupRoute = @json(route($prefix . '.setting'));

    document.querySelectorAll('.btn-create-invoice-blocked').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            Swal.fire({
                title: 'QuickBooks Setup Incomplete',
                text: 'QuickBooks setup must be completed before creating invoices. Please connect QuickBooks, sync your Chart of Accounts, and sync all mandatory Services.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Go to QuickBooks Settings',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = qbSetupRoute;
                }
            });
        });
    });
</script>
@endpush
