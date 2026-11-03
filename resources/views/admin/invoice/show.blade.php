@extends('layout.app', ['page_name' => 'Invoice Details'])

@push('styles')
<style>
/* ── Print optimisation ─────────────────────────────────────────── */
@media print {
    .no-print, .sidebar, nav, header, footer,
    .page-header-actions, #topbar, .main-sidebar,
    .btn-print, .btn-send, .back-link,
    .inv-footer-bar, .inv-internal-panel { display: none !important; }
    body { background: #fff !important; }
    .print-wrapper { padding: 0 !important; max-width: 100% !important; }
    .inv-card { box-shadow: none !important; border: 1px solid #e5e7eb !important; border-radius: 0 !important; }
    .col-lg-4 { display: none !important; }
    .col-lg-8 { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; }
    @page { margin: 1.5cm; size: A4; }
}

/* ── Card shell ─────────────────────────────────────────────────── */
.inv-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 16px rgba(0,0,0,.07);
    overflow: hidden;
    margin-bottom: 1.25rem;
}

/* ── Header band ────────────────────────────────────────────────── */
.inv-header-band {
    background: linear-gradient(135deg, #0f2745 0%, #1e3a5f 100%);
    color: #fff;
    padding: 2rem 2.25rem 1.75rem;
    display: flex; align-items: flex-start;
    justify-content: space-between; gap: 1.5rem;
}
.inv-header-band .company-name {
    font-size: 1.35rem; font-weight: 800; letter-spacing: -.01em; margin: 0 0 .2rem;
}
.inv-header-band .company-sub {
    font-size: .8rem; opacity: .65; margin: 0;
}
.inv-header-band .inv-title {
    font-size: 2rem; font-weight: 900; letter-spacing: .08em;
    text-transform: uppercase; opacity: .95;
}
.inv-header-band .inv-num-block { text-align: right; }
.inv-header-band .inv-number {
    font-size: 1.05rem; font-weight: 700; opacity: .9; margin-bottom: .4rem;
}
.inv-header-band .inv-dates {
    font-size: .78rem; opacity: .65; line-height: 1.8;
}

/* ── Status bar ─────────────────────────────────────────────────── */
.inv-status-bar {
    padding: .6rem 2.25rem;
    display: flex; align-items: center; gap: .65rem; flex-wrap: wrap;
    border-bottom: 1px solid #f0f0f0;
    background: #fafbfc;
}
.inv-pill {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .22rem .7rem; border-radius: 999px;
    font-size: .73rem; font-weight: 600;
}
.inv-pill.green  { background: #dcfce7; color: #15803d; }
.inv-pill.amber  { background: #fef9c3; color: #b45309; }
.inv-pill.blue   { background: #dbeafe; color: #1d4ed8; }
.inv-pill.gray   { background: #f3f4f6; color: #374151; }
.inv-pill.red    { background: #fee2e2; color: #dc2626; }

/* ── Body ───────────────────────────────────────────────────────── */
.inv-body { padding: 2rem 2.25rem; }

/* ── Bill To / info grid ────────────────────────────────────────── */
.inv-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #f0f0f0;
}
.inv-info-block h6 {
    font-size: .68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .07em; color: #9ca3af; margin-bottom: .5rem;
}
.inv-info-block p { font-size: .875rem; color: #374151; margin: 0; line-height: 1.6; }
.inv-info-block strong { color: #0f2745; font-weight: 600; }

/* ── Line items table ───────────────────────────────────────────── */
.inv-table { width: 100%; border-collapse: collapse; font-size: .875rem; margin-top: .5rem; }
.inv-table thead tr { background: #f8fafc; }
.inv-table thead th {
    padding: .75rem 1rem; font-size: .7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .06em; color: #6b7280;
    border-bottom: 2px solid #e9ecef; white-space: nowrap;
}
.inv-table tbody td {
    padding: .8rem 1rem; color: #374151;
    border-bottom: 1px solid #f3f4f6; vertical-align: middle;
}
.inv-table tbody tr:last-child td { border-bottom: none; }
.inv-table tfoot td {
    padding: .55rem 1rem; font-size: .875rem;
}
.inv-table tfoot tr.subtotal-row td { border-top: 1px solid #e9ecef; }
.inv-table tfoot tr.tax-row td { color: #6b7280; }
.inv-table tfoot tr.total-row td {
    border-top: 2px solid #0f2745; font-weight: 800;
    font-size: 1rem; color: #0f2745; padding-top: .75rem;
}
.inv-table tfoot tr.balance-row td { color: #dc2626; font-weight: 700; }
.text-right { text-align: right; }
.text-center { text-align: center; }
.line-sub { font-size: .75rem; color: #9ca3af; }

/* ── Receipt percent row ────────────────────────────────────────── */
.receipt-row td { color: #4b5563; font-style: italic; background: #fafbff; }

/* ── Notes ──────────────────────────────────────────────────────── */
.inv-notes-card {
    background: #f8fafc; border: 1px solid #e9ecef;
    border-radius: 10px; padding: 1rem 1.25rem; font-size: .85rem;
    color: #374151; margin-top: 1.5rem;
}
.inv-notes-card h6 {
    font-size: .68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: #9ca3af; margin-bottom: .4rem;
}

/* ── Sidebar panels ─────────────────────────────────────────────── */
.inv-meta-panel {
    background: #fff; border: 1.5px solid #e9ecef; border-radius: 12px;
    padding: 1.2rem 1.4rem; margin-bottom: 1.25rem;
    box-shadow: 0 1px 6px rgba(0,0,0,.04);
}
.inv-meta-panel h6 {
    font-size: .73rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: #9ca3af; margin-bottom: .85rem;
}
.inv-meta-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: .35rem 0; font-size: .82rem; border-bottom: 1px solid #f3f4f6;
}
.inv-meta-row:last-child { border-bottom: none; }
.inv-meta-row .mk { color: #6b7280; }
.inv-meta-row .mv { font-weight: 600; color: #374151; font-family: monospace; font-size: .78rem; }
.inv-meta-row .mv.normal { font-family: inherit; font-size: .82rem; }

/* ── Footer action bar ──────────────────────────────────────────── */
.inv-footer-bar {
    position: sticky; bottom: 0; z-index: 100;
    background: #fff; border-top: 1px solid #e9ecef;
    padding: .85rem 2rem;
    display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
    box-shadow: 0 -2px 12px rgba(0,0,0,.06);
}
.btn-inv-primary {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .55rem 1.2rem; border-radius: 8px; font-size: .85rem; font-weight: 600;
    background: #1d4ed8; color: #fff; border: none; cursor: pointer; text-decoration: none;
}
.btn-inv-primary:hover { background: #1e40af; color: #fff; }
.btn-inv-outline {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .53rem 1.2rem; border-radius: 8px; font-size: .85rem; font-weight: 600;
    background: #fff; color: #374151; border: 1.5px solid #d1d5db;
    cursor: pointer; text-decoration: none;
}
.btn-inv-outline:hover { border-color: #1d4ed8; color: #1d4ed8; }
.btn-inv-green {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .53rem 1.2rem; border-radius: 8px; font-size: .85rem; font-weight: 600;
    background: #16a34a; color: #fff; border: none; cursor: pointer; text-decoration: none;
}
.btn-inv-green:hover { background: #15803d; color: #fff; }
</style>
@endpush

@section('content')
@php
    $user   = auth()->user();
    $prefix = $prefix ?? (($user && (int)$user->role_id === 6) ? 'manager' : 'admin');

    $billingClient = $invoice->billingParty?->client ?? null;
    $billAddr      = $invoice->billing_address ?? [];
    $event         = $invoice->event;
    $lineItems     = $invoice->lineItems->count() ? $invoice->lineItems : collect();

    // For QB-source invoices, line items stored as JSON on line_items column
    $jsonLines = collect();
    if ($lineItems->isEmpty() && !empty($invoice->line_items)) {
        $raw = is_array($invoice->line_items) ? $invoice->line_items : (json_decode($invoice->line_items, true) ?? []);
        $jsonLines = collect($raw);
    }

    // Receipt percent — from billing party client profile
    $receiptPercent = (int)($billingClient?->clientProfile?->default_receipt_percent ?? 0);

    // Status badge
    $statusColor = match(strtolower($invoice->status ?? '')) {
        'paid'      => 'green',
        'published' => 'blue',
        'draft'     => 'gray',
        'overdue'   => 'red',
        default     => 'amber',
    };
    $qbStatusColor = match($invoice->qb_publish_status ?? '') {
        'success' => 'green',
        'failed'  => 'red',
        'pending' => 'amber',
        default   => 'gray',
    };

    // Totals
    $subtotal = (float)($invoice->subtotal ?? $invoice->total_amount ?? 0);
    if ($lineItems->count()) {
        $subtotal = $lineItems->sum(fn($li) => (float)$li->amount);
    } elseif ($jsonLines->count()) {
        $subtotal = $jsonLines->sum(fn($li) => (float)($li['amount'] ?? 0));
    }
    $taxAmount   = (float)($invoice->tax_amount ?? 0);
    $totalAmount = (float)($invoice->total_amount ?? 0);
    $balance     = (float)($invoice->balance ?? 0);
    $invoiceNum  = $invoice->doc_number ?: ('INV-' . $invoice->id);
    $sendRoute   = route($prefix . '.invoices.send-email', $invoice->id);
    $csrfToken   = csrf_token();
@endphp

<div class="print-wrapper" style="max-width:1100px; margin:0 auto; padding: 1.5rem 1rem 6rem;">

    {{-- Back / action bar (hidden on print) --}}
    <div class="d-flex align-items-center justify-content-between mb-3 no-print">
        <a href="{{ route($prefix . '.invoices') }}" class="btn-inv-outline back-link">
            <i class="fas fa-arrow-left"></i> Back to Invoices
        </a>
        <div class="d-flex gap-2">
            {{--
            <a href="{{ route($prefix . '.invoices.print', $invoice->id) }}" target="_blank" class="btn-inv-outline btn-print">
                <i class="fas fa-print"></i> Print
            </a>
            --}}
            {{--
            <button id="sendInvoiceBtn" class="btn-inv-green btn-send"
                {{ !$billingClient?->email ? 'disabled title=No email on file' : '' }}>
                <i class="fas fa-paper-plane"></i> Send Invoice
            </button>
            --}}
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3">
            {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3">

        {{-- ── Main invoice card (full-width on print) ────────────── --}}
        <div class="col-lg-8">
            <div class="inv-card">

                {{-- Header band --}}
                <div class="inv-header-band">
                    <div>
                        <div class="company-name">Aras Promotions</div>
                        <div class="company-sub">Event Marketing</div>
                    </div>
                    <div class="inv-num-block">
                        <div class="inv-title">Invoice</div>
                        <div class="inv-number">#{{ $invoiceNum }}</div>
                        <div class="inv-dates">
                            <div>Date: {{ $invoice->invoice_date?->format('M d, Y') ?? '—' }}</div>
                            @if($invoice->due_date)
                                <div>Due: {{ $invoice->due_date->format('M d, Y') }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Status bar (internal — hidden on print) --}}
                <div class="inv-status-bar no-print">
                    <span class="inv-pill {{ $statusColor }}">
                        <i class="fas fa-circle" style="font-size:.45rem;"></i>
                        {{ ucfirst($invoice->status ?? 'Draft') }}
                    </span>
                    @if($balance > 0 && strtolower($invoice->status ?? '') !== 'paid')
                        <span class="inv-pill red ms-auto">Balance Due: ${{ number_format($balance, 2) }}</span>
                    @elseif($balance == 0 && $totalAmount > 0)
                        <span class="inv-pill green ms-auto"><i class="fas fa-check-circle"></i> Paid in Full</span>
                    @endif
                </div>

                <div class="inv-body">

                    {{-- Bill To / Event / Terms grid --}}
                    <div class="inv-info-grid">

                        {{-- Bill To --}}
                        <div class="inv-info-block">
                            <h6>Bill To</h6>
                            <p>
                                <strong>{{ $invoice->customer_name ?? ($billingClient?->name ?? '—') }}</strong><br>
                                @if($billingClient?->email)
                                    {{ $billingClient->email }}<br>
                                @endif
                                @if(!empty($billAddr['line1']))
                                    {{ $billAddr['line1'] }}<br>
                                @endif
                                @php
                                    $addrLine2 = collect([
                                        $billAddr['city'] ?? '',
                                        $billAddr['country_sub_division_code'] ?? '',
                                        $billAddr['postal_code'] ?? '',
                                    ])->filter()->implode(', ');
                                @endphp
                                @if($addrLine2) {{ $addrLine2 }}<br> @endif
                                @if(!empty($billAddr['country'])) {{ $billAddr['country'] }} @endif
                            </p>
                        </div>

                        {{-- Event --}}
                        @if($event)
                            <div class="inv-info-block">
                                <h6>Event</h6>
                                <p>
                                    <strong>{{ $event->event_name ?? ('Event #' . $event->id) }}</strong><br>
                                    @if($event->start_time)
                                        <span style="font-size:.82rem;color:#6b7280;">
                                            {{ \Carbon\Carbon::parse($event->start_time)->format('M d, Y g:i A') }}
                                        </span><br>
                                    @endif
                                    @if($event->city)
                                        <span style="font-size:.82rem;color:#6b7280;">
                                            <i class="fas fa-map-marker-alt me-1"></i>{{ $event->city }}
                                        </span>
                                    @endif
                                </p>
                            </div>
                        @endif

                        {{-- Terms / PO --}}
                        @if($invoice->terms || $invoice->po_number)
                            <div class="inv-info-block">
                                <h6>Payment Details</h6>
                                <p>
                                    @if($invoice->terms)
                                        <strong>Terms:</strong> {{ $invoice->terms }}<br>
                                    @endif
                                    @if($invoice->po_number)
                                        <strong>PO #:</strong> {{ $invoice->po_number }}
                                    @endif
                                </p>
                            </div>
                        @endif

                    </div>

                    {{-- Line items table --}}
                    @php
                        $hasLineItems = $lineItems->count() > 0;
                        $hasJsonLines = $jsonLines->count() > 0;
                    @endphp

                    @if($hasLineItems || $hasJsonLines)
                        <div class="table-responsive">
                            <table class="inv-table">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-right">Rate</th>
                                        <th class="text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($hasLineItems)
                                        @foreach($lineItems as $li)
                                            @php
                                                $isBuyback = $li->service && mb_strtolower($li->service->name) === 'buyback';
                                                $lineAmt   = (float)$li->amount;
                                                $receiptAmt = ($isBuyback && $receiptPercent > 0 && $lineAmt > 0)
                                                    ? round($lineAmt * ($receiptPercent / 100), 2)
                                                    : 0;
                                            @endphp
                                            <tr>
                                                <td>
                                                    {{ $li->description ?: ($li->service?->name ?? '—') }}
                                                    @if($li->service?->name && $li->description && $li->description !== $li->service->name)
                                                        <div class="line-sub">{{ $li->service->name }}</div>
                                                    @endif
                                                </td>
                                                <td class="text-center">{{ number_format((float)$li->quantity, 2) }}</td>
                                                <td class="text-right">${{ number_format((float)$li->rate, 2) }}</td>
                                                <td class="text-right">${{ number_format($lineAmt, 2) }}</td>
                                            </tr>
                                            {{-- Receipt Percent row immediately below Buyback --}}
                                            @if($receiptAmt > 0)
                                                <tr class="receipt-row">
                                                    <td>Receipt Percent ({{ $receiptPercent }}%)</td>
                                                    <td class="text-center">1.00</td>
                                                    <td class="text-right">${{ number_format($receiptAmt, 2) }}</td>
                                                    <td class="text-right">${{ number_format($receiptAmt, 2) }}</td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @else
                                        @foreach($jsonLines as $li)
                                            <tr>
                                                <td>{{ $li['description'] ?? '—' }}</td>
                                                <td class="text-center">{{ number_format((float)($li['quantity'] ?? 1), 2) }}</td>
                                                <td class="text-right">${{ number_format((float)($li['unit_price'] ?? 0), 2) }}</td>
                                                <td class="text-right">${{ number_format((float)($li['amount'] ?? 0), 2) }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                                <tfoot>
                                    @php
                                        // Receipt total across all buyback lines
                                        $totalReceiptAmt = 0;
                                        if ($hasLineItems && $receiptPercent > 0) {
                                            foreach ($lineItems as $li) {
                                                if ($li->service && mb_strtolower($li->service->name) === 'buyback') {
                                                    $totalReceiptAmt += round((float)$li->amount * ($receiptPercent / 100), 2);
                                                }
                                            }
                                        }
                                        $displaySubtotal = round($subtotal + $totalReceiptAmt, 2);
                                    @endphp
                                    @if($displaySubtotal != $totalAmount || $taxAmount > 0)
                                        <tr class="subtotal-row">
                                            <td colspan="3" class="text-right" style="color:#6b7280;">Subtotal</td>
                                            <td class="text-right" style="font-weight:600;">${{ number_format($displaySubtotal, 2) }}</td>
                                        </tr>
                                    @endif
                                    @if($taxAmount > 0)
                                        <tr class="tax-row">
                                            <td colspan="3" class="text-right">Tax</td>
                                            <td class="text-right">${{ number_format($taxAmount, 2) }}</td>
                                        </tr>
                                    @elseif($hasLineItems)
                                        <tr class="tax-row">
                                            <td colspan="3" class="text-right">Tax</td>
                                            <td class="text-right" style="font-style:italic;color:#9ca3af;">QB Calculated</td>
                                        </tr>
                                    @endif
                                    <tr class="total-row">
                                        <td colspan="3" class="text-right">Total</td>
                                        <td class="text-right">${{ number_format(round($totalAmount + $totalReceiptAmt, 2), 2) }}</td>
                                    </tr>
                                    @if($balance > 0 && $balance != $totalAmount)
                                        <tr class="balance-row">
                                            <td colspan="3" class="text-right">Balance Due</td>
                                            <td class="text-right">${{ number_format($balance, 2) }}</td>
                                        </tr>
                                    @endif
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <p class="text-muted small mt-2">No line items on this invoice.</p>
                    @endif

                    {{-- Customer-facing notes --}}
                    @if($invoice->notes_to_customer || $invoice->customer_memo)
                        <div class="inv-notes-card">
                            <h6>Notes</h6>
                            {{ $invoice->notes_to_customer ?? $invoice->customer_memo }}
                        </div>
                    @endif

                    {{-- Internal private note — hidden on print --}}
                    @if($invoice->private_note)
                        <div class="inv-notes-card no-print" style="background:#fffbeb;border-color:#fde68a;">
                            <h6 style="color:#b45309;">Internal Note</h6>
                            {{ $invoice->private_note }}
                        </div>
                    @endif

                </div>{{-- /.inv-body --}}
            </div>{{-- /.inv-card --}}
        </div>{{-- /.col-lg-8 --}}

        {{-- ── Sidebar (internal — hidden on print) ───────────────── --}}
        <div class="col-lg-4 no-print">

            {{-- Invoice Info --}}
            <div class="inv-meta-panel">
                <h6><i class="fas fa-file-invoice me-1"></i>Invoice Info</h6>
                <div class="inv-meta-row">
                    <span class="mk">Invoice #</span>
                    <span class="mv">{{ $invoiceNum }}</span>
                </div>
                <div class="inv-meta-row">
                    <span class="mk">Status</span>
                    <span class="inv-pill {{ $statusColor }}" style="font-size:.7rem;padding:.15rem .5rem;">
                        {{ ucfirst($invoice->status ?? 'Draft') }}
                    </span>
                </div>
                <div class="inv-meta-row">
                    <span class="mk">Invoice Date</span>
                    <span class="mv normal">{{ $invoice->invoice_date?->format('M d, Y') ?? '—' }}</span>
                </div>
                <div class="inv-meta-row">
                    <span class="mk">Due Date</span>
                    <span class="mv normal">{{ $invoice->due_date?->format('M d, Y') ?? '—' }}</span>
                </div>
                <div class="inv-meta-row">
                    <span class="mk">Currency</span>
                    <span class="mv normal">{{ $invoice->currency_code ?? 'USD' }}</span>
                </div>
                <div class="inv-meta-row">
                    <span class="mk">Total</span>
                    <span class="mv normal" style="font-weight:700;color:#0f2745;">
                        ${{ number_format($totalAmount, 2) }}
                    </span>
                </div>
                @if($balance >= 0)
                    <div class="inv-meta-row">
                        <span class="mk">Balance Due</span>
                        <span class="mv normal" style="color:{{ $balance > 0 ? '#dc2626' : '#16a34a' }};font-weight:700;">
                            ${{ number_format($balance, 2) }}
                        </span>
                    </div>
                @endif
                @if($invoice->terms)
                    <div class="inv-meta-row">
                        <span class="mk">Terms</span>
                        <span class="mv normal">{{ $invoice->terms }}</span>
                    </div>
                @endif
                @if($invoice->po_number)
                    <div class="inv-meta-row">
                        <span class="mk">PO Number</span>
                        <span class="mv">{{ $invoice->po_number }}</span>
                    </div>
                @endif
                <div class="inv-meta-row">
                    <span class="mk">Email Status</span>
                    <span class="mv normal">{{ $invoice->email_status ?? 'Not Sent' }}</span>
                </div>
            </div>

            {{-- Accounting / publishing panel (internal) --}}
            @if($invoice->qb_invoice_id || $invoice->qb_publish_status)
                <div class="inv-meta-panel inv-internal-panel">
                    <h6><i class="fas fa-cloud me-1"></i>Accounting Sync</h6>
                    <div class="inv-meta-row">
                        <span class="mk">Publish Status</span>
                        <span class="inv-pill {{ $qbStatusColor }}" style="font-size:.7rem;padding:.15rem .5rem;">
                            {{ ucfirst(str_replace('_',' ',$invoice->qb_publish_status ?? 'not published')) }}
                        </span>
                    </div>
                    @if($invoice->qb_published_at)
                        <div class="inv-meta-row">
                            <span class="mk">Published At</span>
                            <span class="mv normal">{{ $invoice->qb_published_at->format('M d, Y g:i A') }}</span>
                        </div>
                    @endif
                    @if($invoice->qb_invoice_id)
                        <div class="inv-meta-row">
                            <span class="mk">Sync ID</span>
                            <span class="mv">{{ $invoice->qb_invoice_id }}</span>
                        </div>
                    @endif
                    @if($invoice->qb_publish_error)
                        <div style="margin-top:.65rem;background:#fee2e2;border-radius:8px;padding:.6rem .8rem;font-size:.78rem;color:#dc2626;">
                            <i class="fas fa-exclamation-circle me-1"></i>{{ $invoice->qb_publish_error }}
                        </div>
                    @endif
                </div>
            @endif

            {{-- Activity --}}
            <div class="inv-meta-panel">
                <h6><i class="fas fa-history me-1"></i>Activity</h6>
                @if($invoice->draft_saved_at)
                    <div class="inv-meta-row">
                        <span class="mk">Draft Saved</span>
                        <span class="mv normal">{{ $invoice->draft_saved_at->format('M d, Y g:i A') }}</span>
                    </div>
                @endif
                @if($invoice->created_time)
                    <div class="inv-meta-row">
                        <span class="mk">Created</span>
                        <span class="mv normal">{{ $invoice->created_time->format('M d, Y') }}</span>
                    </div>
                @endif
                <div class="inv-meta-row">
                    <span class="mk">Source</span>
                    <span class="mv normal">
                        {{ $invoice->source === \App\Models\Invoice::SOURCE_APP ? 'Portal' : 'Synced' }}
                    </span>
                </div>
                @if($invoice->user)
                    <div class="inv-meta-row">
                        <span class="mk">Created by</span>
                        <span class="mv normal">{{ $invoice->user->name }}</span>
                    </div>
                @endif
            </div>

            {{-- Event quick link --}}
            @if($event)
                <div class="inv-meta-panel">
                    <h6><i class="fas fa-calendar-alt me-1"></i>Event</h6>
                    <div class="inv-meta-row">
                        <span class="mk">Name</span>
                        <span class="mv normal">{{ $event->event_name ?? ('Event #' . $event->id) }}</span>
                    </div>
                    @if($event->start_time)
                        <div class="inv-meta-row">
                            <span class="mk">Date</span>
                            <span class="mv normal">{{ \Carbon\Carbon::parse($event->start_time)->format('M d, Y') }}</span>
                        </div>
                    @endif
                    @if($event->admin_status)
                        <div class="inv-meta-row">
                            <span class="mk">Status</span>
                            <span class="mv normal">{{ ucwords(str_replace('_', ' ', $event->admin_status)) }}</span>
                        </div>
                    @endif
                    <div class="mt-2">
                        <a href="{{ route($prefix . '.show-event', $event->id) }}" class="btn-inv-outline" style="font-size:.78rem;padding:.3rem .75rem;">
                            <i class="fas fa-external-link-alt"></i> View Event
                        </a>
                    </div>
                </div>
            @endif

        </div>{{-- /.col-lg-4 --}}
    </div>{{-- /.row --}}
</div>

{{-- Sticky footer (hidden on print) --}}
<div class="inv-footer-bar no-print">
    <a href="{{ route($prefix . '.invoices') }}" class="btn-inv-outline">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    {{--
    <a href="{{ route($prefix . '.invoices.print', $invoice->id) }}" target="_blank" class="btn-inv-outline">
        <i class="fas fa-print"></i> Print Invoice
    </a>
    --}}
    {{--
    <button id="sendInvoiceBtnFooter" class="btn-inv-green {{ !$billingClient?->email ? 'disabled' : '' }}">
        <i class="fas fa-paper-plane"></i> Send Invoice
    </button>
    --}}
    @if($event)
        <a href="{{ route($prefix . '.event.invoice-review', $event->id) }}" class="btn-inv-outline ms-auto">
            <i class="fas fa-edit"></i> Edit Draft
        </a>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function () {
    const sendUrl    = @json($sendRoute);
    const csrf       = @json($csrfToken);
    const clientEmail = @json($billingClient?->email ?? null);

    function doSend() {
        if (!clientEmail) {
            Swal.fire({ icon: 'warning', title: 'No Email Address',
                text: 'No email address is on file for this billing party.',
                confirmButtonColor: '#1d4ed8' });
            return;
        }
        Swal.fire({
            title: 'Send Invoice?',
            html: 'Send this invoice to <strong>' + clientEmail + '</strong>?',
            icon: 'question', showCancelButton: true,
            confirmButtonText: 'Yes, Send', cancelButtonText: 'Cancel',
            confirmButtonColor: '#16a34a', cancelButtonColor: '#6c757d',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            const btns = [document.getElementById('sendInvoiceBtn'), document.getElementById('sendInvoiceBtnFooter')];
            btns.forEach(b => { if (b) { b.disabled = true; b.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…'; } });
            fetch(sendUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({}),
            })
            .then(r => r.json())
            .then(function (data) {
                btns.forEach(b => { if (b) { b.disabled = false; b.innerHTML = '<i class="fas fa-paper-plane"></i> Send Invoice'; } });
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Sent!', text: data.message, confirmButtonColor: '#1d4ed8' })
                        .then(() => window.location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Failed', text: data.message, confirmButtonColor: '#1d4ed8' });
                }
            })
            .catch(function (err) {
                btns.forEach(b => { if (b) { b.disabled = false; b.innerHTML = '<i class="fas fa-paper-plane"></i> Send Invoice'; } });
                Swal.fire({ icon: 'error', title: 'Network Error', text: err.message, confirmButtonColor: '#1d4ed8' });
            });
        });
    }

    const btn1 = document.getElementById('sendInvoiceBtn');
    const btn2 = document.getElementById('sendInvoiceBtnFooter');
    if (btn1) btn1.addEventListener('click', doSend);
    if (btn2) btn2.addEventListener('click', doSend);
})();
</script>
@endpush
@endsection
