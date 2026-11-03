@extends('layout.app', ['page_name' => 'Invoice Review'])
@push('styles')
<style>
/* ── Page ─────────────────────────────────────────── */
.ir-page { background:#f9fafb; min-height:100vh; padding:1.5rem 1.5rem 5rem; }

/* ── Breadcrumb / header ─────────────────────────── */
.ir-title     { font-size:2rem; font-weight:700; color:#111827; margin-bottom:.15rem; }
.ir-breadcrumb{ color:#9ca3af; }
.ir-breadcrumb a{ color:#9ca3af; text-decoration:none; }
.ir-breadcrumb a:hover{ color:#6b7280; }
.ir-back-btn  {
    display:inline-flex; align-items:center; gap:.4rem;
    font-size:.9rem; font-weight:500; color:#374151;
    background:#fff; border:1px solid #d1d5db; border-radius:.5rem;
    padding:.4rem .9rem; text-decoration:none; margin-top:.75rem;
    transition:background .15s;
}
.ir-back-btn:hover{ background:#f3f4f6; color:#111827; }

/* ── Status badges ────────────────────────────────── */
.badge-qb-connected { background:#16a34a; color:#fff; font-size:.9rem; font-weight:600; border-radius:.375rem; padding:.5rem .6rem; }
.badge-parties      { background:#e5e7eb; color:#374151; font-size:.9rem; font-weight:600; border-radius:.375rem; padding:.5rem .6rem; }

/* ── Event info card ─────────────────────────────── */
.ir-event-card {
    background:#fff; border:1px solid #e5e7eb; border-radius:.75rem;
    padding:1rem 1.25rem; box-shadow:0 1px 3px rgba(0,0,0,.06); margin-bottom:.75rem;
}
.ir-event-label { font-size:1rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#6b7280; margin-bottom:.75rem; }
.ir-stat-item   { display:flex; flex-direction:column; gap:.2rem; }
.ir-stat-lbl    { font-size:.95rem; color:#6b7280; display:flex; align-items:center; gap:.3rem; }
.ir-stat-lbl i  { font-size:.95rem; }
.ir-stat-val    { font-size:.85rem; font-weight:700; color:#111827; }

/* ── Info banner ──────────────────────────────────── */
.ir-info-banner {
    background:#eff6ff; border:1px solid #bfdbfe; border-radius:.5rem;
    padding:.65rem 1rem; display:flex; align-items:flex-start; gap:.5rem;
    font-size:.9rem; color:#374151; margin-bottom:1.5rem;
}
.ir-info-banner i { color:#3b82f6; margin-top:.05rem; flex-shrink:0; }

/* ── Two-column layout ────────────────────────────── */
.ir-grid { display:flex; gap:1.25rem; align-items:flex-start; }
.ir-cards { flex:1; min-width:0; }
.ir-sidebar { width:272px; flex-shrink:0; position:sticky; top:1.25rem; }

/* ── Invoice card ─────────────────────────────────── */
.ir-card {
    background:#fff; border:1px solid #e5e7eb; border-radius:.75rem;
    box-shadow:0 1px 3px rgba(0,0,0,.06); margin-bottom:1.25rem; overflow:hidden;
}
.ir-card-header {
    display:flex; align-items:center; gap:.6rem;
    padding:.85rem 1.25rem; border-bottom:1px solid #f3f4f6; cursor:pointer;
    user-select:none;
}
.ir-card-header i.icon-doc { color:#2563eb; font-size:.95rem; }
.ir-card-client-id { font-size:.85rem; font-weight:800; color:#111827; text-transform:uppercase; letter-spacing:.04em; }
.ir-card-client-name{ font-size:.9rem; font-weight:500; color:#374151; }
.badge-primary   { background:#2563eb; color:#fff; font-size:.65rem; font-weight:700; border-radius:.3rem; padding:.15rem .5rem; }
.badge-secondary { background:#e5e7eb; color:#4b5563; font-size:.65rem; font-weight:700; border-radius:.3rem; padding:.15rem .5rem; }
.ir-collapse-icon{ margin-left:auto; color:#9ca3af; font-size:.8rem; transition:transform .2s; }
.ir-card-body { padding:1.25rem; }

/* ── Section headings ─────────────────────────────── */
.ir-section-title {
    font-size:0.95rem; font-weight:700; text-transform:uppercase;
    letter-spacing:.07em; color:#6b7280; margin-bottom:.65rem;
    padding-bottom:.35rem; border-bottom:1px solid #f3f4f6;
}

/* ── Form labels / inputs ─────────────────────────── */
.ir-label {
    display:block; font-size:.95rem; font-weight:600; color:#424242; margin-bottom:.25rem;
}
.ir-label .req { color:#ef4444; }
.ir-input, .ir-select, .ir-textarea {
    width:100%; border:1px solid #d1d5db; border-radius:.5rem;
    padding:.4rem .65rem; font-size:.95rem; color:#111827; background:#fff;
    transition:border-color .15s, box-shadow .15s;
}
.ir-input:focus, .ir-select:focus, .ir-textarea:focus {
    outline:none; border-color:#3b82f6; box-shadow:0 0 0 2px rgba(59,130,246,.15);
}
.ir-input-readonly {
    background:#f3f4f6 !important; color:#6b7280; cursor:not-allowed;
    border-color:#d1d5db;
}
.ir-input-readonly:focus {
    border-color:#d1d5db; box-shadow:none;
}
.ir-input-wrap { position:relative; }
.ir-input-wrap .ir-input { padding-right:2rem; }
.ir-input-icon { position:absolute; right:.55rem; top:50%; transform:translateY(-50%); color:#9ca3af; font-size:.75rem; pointer-events:none; }
.ir-checkbox-label { font-size:.78rem; color:#374151; display:flex; align-items:center; gap:.4rem; cursor:pointer; }

/* ── Line items table ─────────────────────────────── */
.ir-table { width:100%; border-collapse:collapse; font-size:.85rem; }
.ir-table thead th {
    background:#f9fafb; border:1px solid #e5e7eb;
    padding:.4rem .6rem; text-align:left;
    font-size:.65rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.04em;
}
.ir-table thead th.text-right { text-align:right; }
.ir-table tbody td { border:1px solid #e5e7eb; padding:.45rem .6rem; color:#111827; vertical-align:middle; }
.ir-table tbody td.text-right { text-align:right; }
.ir-table tbody td.text-muted-val { color:#6b7280; font-style:italic; }
.ir-item-name { font-weight:600; color:#111827; }
.ir-item-desc { font-size:.85rem; color:#6b7280; margin-top:.1rem; }
.ir-line-remove { background:none; border:none; color:#9ca3af; cursor:pointer; padding:.1rem .3rem; font-size:.85rem; }
.ir-line-remove:hover { color:#ef4444; }

/* ── Add service row ──────────────────────────────── */
.ir-add-service-row { display:flex; align-items:center; gap:.5rem; margin-top:.65rem; }
.ir-add-service-btn {
    display:inline-flex; align-items:center; gap:.35rem;
    font-size:.8rem; font-weight:600; color:#2563eb;
    background:none; border:1px dashed #93c5fd; border-radius:.4rem;
    padding:.3rem .75rem; cursor:pointer; transition:background .15s;
}
.ir-add-service-btn:hover { background:#eff6ff; }
.ir-add-service-select { display:none; }

/* ── Tax / Notes row ──────────────────────────────── */
.ir-tax-notes { display:grid; grid-template-columns:1fr 2fr; gap:.75rem; margin-top:.85rem; align-items:start; }
.ir-char-count { font-size:.65rem; color:#9ca3af; text-align:right; margin-top:.15rem; }

/* ── Sidebar cards ────────────────────────────────── */
.ir-sidebar-card {
    background:#fff; border:1px solid #e5e7eb; border-radius:.75rem;
    padding:1rem 1.1rem; box-shadow:0 1px 3px rgba(0,0,0,.06); margin-bottom:1rem;
}
.ir-sidebar-title { font-size:.85rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#6b7280; margin-bottom:.75rem; }
.ir-summary-row { display:flex; justify-content:space-between; font-size:.88rem; color:#374151; margin-bottom:.35rem; }
.ir-summary-row.total-row { font-weight:700; font-size:.95rem; color:#111827; margin-top:.5rem; padding-top:.5rem; border-top:1px solid #e5e7eb; }
.ir-summary-row.grand-row { font-weight:800; font-size:1.05rem; color:#2563eb; }
.ir-how-box { background:#eff6ff; border-radius:.5rem; padding:.65rem .75rem; margin-top:.75rem; font-size:.82rem; color:#374151; }
.ir-how-box .how-title { font-weight:700; color:#1e40af; display:flex; align-items:center; gap:.3rem; margin-bottom:.4rem; }
.ir-how-box ul { margin:0; padding-left:1.1rem; }
.ir-how-box ul li { margin-bottom:.2rem; }
.ir-how-box ul li strong { color:#1e40af; }

.ir-breakdown-card { background:#fff; border:1px solid #e5e7eb; border-radius:.75rem; padding:1rem 1.1rem; box-shadow:0 1px 3px rgba(0,0,0,.06); margin-bottom:1rem; }
.ir-breakdown-title { font-size:.85rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#6b7280; margin-bottom:.65rem; }
.ir-bd-row { display:flex; justify-content:space-between; font-size:.88rem; color:#374151; padding:.25rem 0; }
.ir-bd-row.subtotal { border-top:1px solid #e5e7eb; margin-top:.25rem; padding-top:.4rem; }
.ir-bd-row.total-final { font-weight:800; font-size:.95rem; color:#2563eb; padding-top:.35rem; border-top:1px solid #e5e7eb; margin-top:.1rem; }
.ir-auto-note { background:#eff6ff; border-radius:.4rem; padding:.45rem .6rem; font-size:.8rem; color:#374151; display:flex; align-items:flex-start; gap:.35rem; margin-top:.6rem; }
.ir-auto-note i { color:#3b82f6; flex-shrink:0; margin-top:.05rem; font-size:1rem; }

/* ── Sticky footer ────────────────────────────────── */
.ir-footer {
    position:fixed; bottom:0; left:0; right:0; z-index:100;
    background:#fff; border-top:1px solid #e5e7eb;
    display:flex; justify-content:space-between; align-items:center;
    padding:.75rem 1.5rem; gap:1rem;
}
.ir-footer-note { font-size:.85rem; color:#6b7280; }

/* ── Divider ──────────────────────────────────────── */
.ir-divider { border:0; border-top:1px solid #f3f4f6; margin:1rem 0; }

/* ── Collapsed state ──────────────────────────────── */
.ir-card-body.collapsed { display:none; }
.ir-collapse-icon.open  { transform:rotate(180deg); }
</style>
@endpush

@section('content')
@php
    $prefix = $prefix ?? (auth()->user()?->role_id == 6 ? 'manager' : 'admin');
    $N      = $billingParties->count();

    // Event hours
    $eventHours = 0;
    if ($event->start_time && $event->end_time) {
        $tz    = app(\App\Services\EventTimezoneService::class)->normalizeIana($event->timezone);
        $start = \Carbon\Carbon::parse($event->start_time, $tz);
        $end   = \Carbon\Carbon::parse($event->end_time, $tz);
        $eventHours = round($start->diffInMinutes($end) / 60, 2);
    }

    $laborTotal     = (float)($event->labor_amount   ?? 0);
    $buybackTotal   = (float)($event->buyback_amount ?? 0);
    $cupsTotal      = (float)($event->cups_amount    ?? 0);
    $parkingTotal   = (float)($event->parking_fee    ?? 0);
    $expediteTotal  = (float)($event->expedite_fee   ?? 0);
    $shippingTotal  = (float)($event->shipping_fee   ?? 0);
    $driveTimeTotal = (float)($event->drive_time     ?? 0);

    // Helper: even split with last-party rounding correction
    $splitFn = function(float $total, int $n, int $idx): float {
        if ($n <= 0) return 0.0;
        $per = round($total / $n, 2);
        return ($n > 1 && $idx === $n - 1)
            ? round($total - $per * ($n - 1), 2)
            : $per;
    };

    // Primary party index
    $primaryIndex = 0;
    $primaryName  = '—';
    foreach ($billingParties as $i => $bp) {
        if ((bool)($bp->is_primary ?? ($i === 0))) {
            $primaryIndex = $i;
            $primaryName  = $bp->client?->name ?? '—';
            break;
        }
    }

    // Pre-compute per-party amounts
    $partyAmounts = [];
    foreach ($billingParties as $i => $bp) {
        $isPrimary = (bool)($bp->is_primary ?? ($i === 0));
        $laborRate = (float)($bp->labor_rate ?? 0);
        $laborAmt  = $N > 0 ? round(($eventHours * $laborRate) / $N, 2) : 0;

        $buybackAmt   = $splitFn($buybackTotal,   $N, $i);
        $cupsAmt      = $isPrimary ? $cupsTotal : 0;
        $parkingAmt   = $splitFn($parkingTotal,   $N, $i);
        $expediteAmt  = $splitFn($expediteTotal,  $N, $i);
        $shippingAmt  = $splitFn($shippingTotal,  $N, $i);
        $driveTimeAmt = $splitFn($driveTimeTotal, $N, $i);

        // Receipt Percent — per-client, applied to this party's buyback share
        $receiptPercent = (int)(($bp->client?->clientProfile->default_receipt_percent) ?? 0);
        $receiptAmt     = ($receiptPercent > 0 && $buybackAmt > 0)
            ? round($buybackAmt * ($receiptPercent / 100), 2)
            : 0.0;

        $subtotal = $laborAmt + $buybackAmt + $receiptAmt + $cupsAmt
                  + $parkingAmt + $expediteAmt + $shippingAmt + $driveTimeAmt;

        $partyAmounts[$i] = compact(
            'laborAmt','buybackAmt','cupsAmt',
            'parkingAmt','expediteAmt','shippingAmt','driveTimeAmt',
            'receiptAmt','receiptPercent',
            'subtotal','laborRate'
        );
    }
    $grandTotal = array_sum(array_column($partyAmounts, 'subtotal'));

    // Services map: name (lowercase) => Service model (for QB item IDs)
    $servicesMap = $availableServices->keyBy(fn($s) => mb_strtolower($s->name));
@endphp

<div class="ir-page">

    {{-- ── Page Header ──────────────────────────────────────────────────── --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <div class="ir-title">Invoice Review</div>
            <div class="ir-breadcrumb">
                <a href="{{ route($prefix.'.event-invoicing') }}">‹ Invoice Queue</a>
                &nbsp;›&nbsp; Invoice Review
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($isConnected)
                <button class="btn-ds btn-success-ds btn-sm">
                    <i class="fas fa-check-circle me-1"></i>QuickBooks Connected
                </button>
            @else
                <button class="btn-ds btn-warning-ds btn-sm">
                    <i class="fas fa-exclamation-triangle me-1"></i>QuickBooks Not Connected
                </button>
            @endif
            <button class="btn-ds btn-light-ds btn-sm">
                <i class="fas fa-users me-1"></i>{{ $N }} Billing {{ Str::plural('Party',$N) }}
            </button>
        </div>
    </div>

    <a href="{{ route($prefix.'.event-invoicing') }}" class="ir-back-btn mb-3">
        <i class="fas fa-arrow-left"></i> Back to Invoice Queue
    </a>

    {{-- ── Event Information ──────────────────────────────────────────────── --}}
    <div class="ir-event-card mt-3">
        <div class="ir-event-label">Event Information (Read Only)</div>
        <div class="d-flex flex-wrap justify-content-between gap-4">
            <div class="ir-stat-item">
                <span class="ir-stat-lbl"><i class="fas fa-calendar-alt"></i>Event Name</span>
                <span class="ir-stat-val">{{ $event->event_name ?? '—' }}</span>
            </div>
            <div class="ir-stat-item">
                <span class="ir-stat-lbl"><i class="fas fa-calendar-day"></i>Event Date</span>
                <span class="ir-stat-val">{{ $event->start_time ? \Carbon\Carbon::parse($event->start_time)->format('M j, Y') : '—' }}</span>
            </div>
            <div class="ir-stat-item">
                <span class="ir-stat-lbl"><i class="fas fa-clock"></i>Hours</span>
                <span class="ir-stat-val">{{ number_format($eventHours, 2) }} hrs</span>
            </div>
            <div class="ir-stat-item">
                <span class="ir-stat-lbl"><i class="fas fa-dollar-sign"></i>Labor Total</span>
                <span class="ir-stat-val">${{ number_format($laborTotal, 2) }}</span>
            </div>
            <div class="ir-stat-item">
                <span class="ir-stat-lbl"><i class="fas fa-dollar-sign"></i>Buyback</span>
                <span class="ir-stat-val">${{ number_format($buybackTotal, 2) }}</span>
            </div>
            <div class="ir-stat-item">
                <span class="ir-stat-lbl"><i class="fas fa-coffee"></i>Cup Charge</span>
                <span class="ir-stat-val">${{ number_format($cupsTotal, 2) }}</span>
            </div>
            @if($parkingTotal > 0)
            <div class="ir-stat-item">
                <span class="ir-stat-lbl"><i class="fas fa-parking"></i>Parking Fee</span>
                <span class="ir-stat-val">${{ number_format($parkingTotal, 2) }}</span>
            </div>
            @endif
            @if($expediteTotal > 0)
            <div class="ir-stat-item">
                <span class="ir-stat-lbl"><i class="fas fa-bolt"></i>Expedite Fee</span>
                <span class="ir-stat-val">${{ number_format($expediteTotal, 2) }}</span>
            </div>
            @endif
            @if($shippingTotal > 0)
            <div class="ir-stat-item">
                <span class="ir-stat-lbl"><i class="fas fa-shipping-fast"></i>Shipping Fee</span>
                <span class="ir-stat-val">${{ number_format($shippingTotal, 2) }}</span>
            </div>
            @endif
            @if($driveTimeTotal > 0)
            <div class="ir-stat-item">
                <span class="ir-stat-lbl"><i class="fas fa-car"></i>Drive-time Pay</span>
                <span class="ir-stat-val">${{ number_format($driveTimeTotal, 2) }}</span>
            </div>
            @endif
            <div class="ir-stat-item">
                <span class="ir-stat-lbl"><i class="fas fa-user-tie"></i>Primary Party</span>
                <span class="ir-stat-val">{{ $primaryName }}</span>
            </div>
        </div>
    </div>

    {{-- ── Info banner ──────────────────────────────────────────────────── --}}
    <div class="ir-info-banner">
        <i class="fas fa-info-circle"></i>
        <span>
            <strong>Labour:</strong> per-party rate × hours ÷ N.&nbsp;
            <strong>Cup Charge:</strong> primary party only.&nbsp;
            <strong>Buyback, Parking, Expedite, Shipping, Drive-time:</strong> split evenly across all billing parties.
        </span>
    </div>

    {{-- ── Main form wraps all invoice cards ─────────────────────────────── --}}
    <form method="POST" action="{{ route($prefix.'.event.invoice-review.save-draft', $event) }}" id="saveDraftForm">
    @csrf
    <input type="hidden" name="event_id" value="{{ $event->id }}">

    {{-- ── Two-column grid ──────────────────────────────────────────────── --}}
    <div class="ir-grid">

        {{-- ───────────────── LEFT: Invoice Cards ─────────────────────── --}}
        <div class="ir-cards">

            @foreach ($billingParties as $i => $party)
            @php
                $client    = $party->client;
                $profile   = $client?->clientProfile;
                $isPrimary = (bool)($party->is_primary ?? ($i === 0));
                $partyNum  = $i + 1;
                $clientLbl = 'CLIENT '.chr(65 + $i);

                // ── Draft hydration: use saved Draft if available ─────────────────
                $draft = ($draftInvoices ?? collect())->get($party->id);
                $hasDraft = $draft !== null;

                // ── Invoice Number editability ────────────────────────────────────
                // Editable ONLY on a brand-new invoice (no draft record exists yet).
                // Once a Draft has been saved (or the invoice is Published), the
                // Invoice Number is part of the accounting record and must be readonly.
                $isPublished       = $hasDraft && ($draft->qb_publish_status === 'success') && $draft->qb_invoice_id !== null;
                $invoiceNumReadonly = $hasDraft; // true for Draft OR Published

                // ── Invoice metadata ─────────────────────────────────────────
                // Pre-fill with next unique number for new invoices; show saved number for existing drafts.
                $docNumber   = $hasDraft ? ($draft->doc_number ?? '') : \App\Models\Invoice::nextDocNumber();
                $draftTerms  = $hasDraft ? ($draft->terms        ?? 'Net 30') : 'Net 30';
                $draftStatus = $hasDraft ? ($draft->status       ?? 'Draft')  : 'Draft';
                $draftCurrency = $hasDraft ? ($draft->currency_code ?? 'USD') : 'USD';
                $draftInvDate  = $hasDraft ? ($draft->invoice_date?->format('Y-m-d') ?? now()->format('Y-m-d')) : now()->format('Y-m-d');
                $draftDueDate  = $hasDraft ? ($draft->due_date?->format('Y-m-d')     ?? now()->addDays(30)->format('Y-m-d')) : now()->addDays(30)->format('Y-m-d');
                $draftPoNumber = $hasDraft ? ($draft->po_number  ?? '') : '';
                $draftTaxCode  = in_array(($eventInvoiceTaxCode ?? 'NON'), ['TAX','NON'], true) ? $eventInvoiceTaxCode : 'NON';
                $draftNotes    = $hasDraft ? ($draft->notes_to_customer ?? '') : '';
                $draftSubtotal = $hasDraft ? (float)($draft->subtotal ?? 0) : 0;

                // ── Amounts (from Draft or event calculation) ─────────────────
                $amounts      = $partyAmounts[$i];
                $laborRate    = $amounts['laborRate'];
                $subtotal     = $hasDraft ? $draftSubtotal : $amounts['subtotal'];

                $laborDesc = number_format($eventHours, 2).' hrs × $'.number_format($laborRate, 2)
                           .' / '.$N.' '.($N===1?'party':'parties');

                // ── Address: draft billing address first, then client profile ──
                $billAddr = $hasDraft ? ($draft->billing_address ?? []) : [];
                $shipAddr = $hasDraft ? ($draft->shipping_address ?? []) : [];

                $addr1    = $billAddr['line1']                      ?? $profile?->address   ?? '';
                $addrCity = $billAddr['city']                       ?? $profile?->city      ?? '';
                $addrSt   = $billAddr['country_sub_division_code']  ?? $profile?->state     ?? '';
                $addrZip  = $billAddr['postal_code']                ?? $profile?->zip_code  ?? '';
                $addrCo   = $billAddr['country']                    ?? $profile?->country   ?? 'USA';

                $shipAddr1    = $shipAddr['line1']                     ?? $addr1;
                $shipAddrCity = $shipAddr['city']                      ?? $addrCity;
                $shipAddrSt   = $shipAddr['country_sub_division_code'] ?? $addrSt;
                $shipAddrZip  = $shipAddr['postal_code']               ?? $addrZip;
                $shipAddrCo   = $shipAddr['country']                   ?? $addrCo;

                // ── Line items: from Draft or event calculation ─────────────
                if ($hasDraft && $draft->lineItems->isNotEmpty()) {
                    // Load exactly as saved
                    $autoLines = $draft->lineItems->map(fn($li) => [
                        'key'  => $li->service ? \Illuminate\Support\Str::slug($li->service->name, '_') : 'custom_'.$li->id,
                        'name' => $li->service?->name ?? $li->description,
                        'desc' => $li->description,
                        'qty'  => (int)$li->quantity,
                        'rate' => (float)$li->rate,
                        'amt'  => (float)$li->amount,
                        'service_id' => $li->service_id,
                    ])->values()->toArray();
                } else {
                    // Fall back to event calculation
                    $laborAmt     = $amounts['laborAmt'];
                    $buybackAmt   = $amounts['buybackAmt'];
                    $cupsAmt      = $amounts['cupsAmt'];
                    $parkingAmt   = $amounts['parkingAmt'];
                    $expediteAmt  = $amounts['expediteAmt'];
                    $shippingAmt  = $amounts['shippingAmt'];
                    $driveTimeAmt = $amounts['driveTimeAmt'];
                    $receiptAmt   = $amounts['receiptAmt'];
                    $receiptPercent = $amounts['receiptPercent'];

                    $autoLines = [];
                    if ($laborAmt > 0)     $autoLines[] = ['key'=>'labour_charge',   'name'=>'Labour Charge',        'desc'=>$laborDesc,                      'qty'=>number_format($eventHours,2), 'rate'=>$N>0?round($laborRate/$N,2):0, 'amt'=>$laborAmt,     'service_id'=>null];
                    if ($buybackAmt > 0)   $autoLines[] = ['key'=>'buyback',         'name'=>'Buyback',              'desc'=>'Product buyback (split equally)', 'qty'=>1, 'rate'=>$buybackAmt,   'amt'=>$buybackAmt,   'service_id'=>null];
                    if ($cupsAmt > 0)      $autoLines[] = ['key'=>'cup_charge',      'name'=>'Cup Charge',           'desc'=>$isPrimary?'Cup charge (primary party only)':'—', 'qty'=>1, 'rate'=>$cupsAmt, 'amt'=>$cupsAmt, 'service_id'=>null];
                    if ($parkingAmt > 0)   $autoLines[] = ['key'=>'parking_fee',     'name'=>'Parking Fee',          'desc'=>'Parking expenses (split equally)', 'qty'=>1, 'rate'=>$parkingAmt,   'amt'=>$parkingAmt,   'service_id'=>null];
                    if ($expediteAmt > 0)  $autoLines[] = ['key'=>'expedite_fee',    'name'=>'Expedite Fee',         'desc'=>'Expedited scheduling fee (split equally)', 'qty'=>1, 'rate'=>$expediteAmt,  'amt'=>$expediteAmt,  'service_id'=>null];
                    if ($shippingAmt > 0)  $autoLines[] = ['key'=>'shipping_fee',    'name'=>'Shipping/Postage Fee', 'desc'=>'Shipping & postage (split equally)', 'qty'=>1, 'rate'=>$shippingAmt,  'amt'=>$shippingAmt,  'service_id'=>null];
                    if ($driveTimeAmt > 0) $autoLines[] = ['key'=>'drive_time',      'name'=>'Drive-time Pay',       'desc'=>'Drive-time compensation (split equally)', 'qty'=>1, 'rate'=>$driveTimeAmt, 'amt'=>$driveTimeAmt, 'service_id'=>null];
                }

                $autoLineKeys = array_column($autoLines, 'key');
            @endphp

            {{-- Hidden: billing party identity for this invoice card --}}
            <input type="hidden" name="parties[{{ $i }}][billing_party_id]" value="{{ $party->id ?? '' }}">
            <input type="hidden" name="parties[{{ $i }}][client_id]" value="{{ $client?->id ?? '' }}">
            <input type="hidden" name="parties[{{ $i }}][is_primary]" value="{{ $isPrimary ? '1' : '0' }}">

            {{-- Hidden: line items (from Draft or event calculation) --}}
            @foreach($autoLines as $li => $line)
                @if(!empty($line['service_id']))
                <input type="hidden" name="parties[{{ $i }}][line_items][{{ $li }}][service_id]"   value="{{ $line['service_id'] }}">
                @endif
                <input type="hidden" name="parties[{{ $i }}][line_items][{{ $li }}][service_key]"  value="{{ $line['key'] }}">
                <input type="hidden" name="parties[{{ $i }}][line_items][{{ $li }}][description]"  value="{{ $line['desc'] }}">
                <input type="hidden" name="parties[{{ $i }}][line_items][{{ $li }}][quantity]"     value="{{ $line['qty'] }}">
                <input type="hidden" name="parties[{{ $i }}][line_items][{{ $li }}][rate]"         value="{{ $line['rate'] }}">
                <input type="hidden" name="parties[{{ $i }}][line_items][{{ $li }}][amount]"       value="{{ $line['amt'] }}">
                <input type="hidden" name="parties[{{ $i }}][line_items][{{ $li }}][sort_order]"   value="{{ $li }}">
            @endforeach

            <div class="ir-card" id="ir-card-{{ $i }}">

                {{-- Card Header --}}
                <div class="ir-card-header" onclick="irToggle({{ $i }})">
                    <i class="fas fa-file-invoice icon-doc"></i>
                    <span class="ir-card-client-id">{{ $clientLbl }}</span>
                    <span class="ir-card-client-name">{{ $client?->name ?? 'Billing Party '.$partyNum }}</span>
                    @if($isPrimary)
                        <span class="badge-primary">Primary</span>
                    @else
                        <span class="badge-secondary">Secondary</span>
                    @endif
                    @if($hasDraft)
                        <span style="font-size:.65rem;background:#d1fae5;color:#065f46;border-radius:.3rem;padding:.15rem .5rem;font-weight:700;"><i class="fas fa-save me-1"></i>Draft Saved</span>
                    @endif
                    @if(!$client?->clientProfile?->quickbooks_customer_id)
                        <span style="font-size:.65rem;background:#fee2e2;color:#991b1b;border-radius:.3rem;padding:.15rem .5rem;font-weight:700;">No QB Customer</span>
                    @endif
                    <i class="fas fa-chevron-up ir-collapse-icon open" id="ir-icon-{{ $i }}"></i>
                </div>

                <div class="ir-card-body" id="ir-body-{{ $i }}">

                    {{-- 1. Invoice Information --}}
                    <div class="ir-section-title">1. Invoice Information</div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-3">
                            <label class="ir-label">Invoice Number <span class="req">*</span>
                                @if($invoiceNumReadonly)
                                    <span style="font-size:.7rem;font-weight:400;color:#6b7280;margin-left:.35rem;">
                                        @if($isPublished)
                                            <i class="fas fa-lock" title="Locked — invoice published"></i> Published
                                        @else
                                            <i class="fas fa-lock" title="Locked — draft saved"></i> Draft saved
                                        @endif
                                    </span>
                                @endif
                            </label>
                            <div class="ir-input-wrap">
                                <input type="text"
                                    class="ir-input{{ $invoiceNumReadonly ? ' ir-input-readonly' : '' }}"
                                    name="parties[{{ $i }}][doc_number]"
                                    value="{{ $docNumber }}"
                                    @if($invoiceNumReadonly) readonly @endif
                                    title="{{ $invoiceNumReadonly ? 'Invoice Number is locked once a draft has been saved.' : '' }}">
                                @if($invoiceNumReadonly)
                                    <i class="fas fa-lock ir-input-icon" style="color:#9ca3af;" title="Locked"></i>
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <label class="ir-label">Terms <span class="req">*</span></label>
                            <select class="ir-select" name="parties[{{ $i }}][terms]" data-card="{{ $i }}">
                                @foreach(['Due on Receipt','Net 15','Net 30','Net 60'] as $term)
                                    <option value="{{ $term }}" {{ $draftTerms === $term ? 'selected' : '' }}>{{ $term }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <label class="ir-label">Status <span class="req">*</span></label>
                            <select class="ir-select" name="parties[{{ $i }}][status]">
                                @foreach(['Draft','Sent','Paid'] as $st)
                                    <option value="{{ $st }}" {{ $draftStatus === $st ? 'selected' : '' }}>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <label class="ir-label">Currency <span class="req">*</span></label>
                            <select class="ir-select" name="parties[{{ $i }}][currency_code]">
                                @foreach(['USD','EUR','GBP'] as $cur)
                                    <option value="{{ $cur }}" {{ $draftCurrency === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="ir-label">Invoice Date <span class="req">*</span></label>
                            <div class="ir-input-wrap">
                                <input type="date" class="ir-input ir-inv-date" data-card="{{ $i }}"
                                    name="parties[{{ $i }}][invoice_date]" value="{{ $draftInvDate }}">
                                <i class="fas fa-calendar ir-input-icon"></i>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <label class="ir-label">Due Date <span class="req">*</span></label>
                            <div class="ir-input-wrap">
                                <input type="date" class="ir-input ir-due-date" id="due-date-{{ $i }}"
                                    name="parties[{{ $i }}][due_date]" value="{{ $draftDueDate }}">
                                <i class="fas fa-calendar ir-input-icon"></i>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <label class="ir-label">P.O. Number</label>
                            <input type="text" class="ir-input" name="parties[{{ $i }}][po_number]" value="{{ $draftPoNumber }}" placeholder="PO-12345">
                        </div>
                    </div>
                    <hr class="ir-divider">

                    {{-- 2. Client Information --}}
                    <div class="ir-section-title">2. Client Information</div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-3">
                            <label class="ir-label">Name</label>
                            <input type="text" class="ir-input" name="parties[{{ $i }}][client_name]" value="{{ $client?->name ?? '' }}">
                        </div>
                        <div class="col-sm-3">
                            <label class="ir-label">Email</label>
                            <input type="email" class="ir-input" name="parties[{{ $i }}][client_email]" value="{{ $client?->email ?? '' }}">
                        </div>
                        <div class="col-sm-3">
                            <label class="ir-label">Phone</label>
                            <input type="text" class="ir-input" name="parties[{{ $i }}][client_phone]" value="{{ $client?->phone ?? '' }}">
                        </div>
                        <div class="col-sm-3">
                            <label class="ir-label">Company</label>
                            <input type="text" class="ir-input" name="parties[{{ $i }}][company]" value="{{ $profile?->company_name ?? '' }}">
                        </div>
                    </div>
                    <hr class="ir-divider">

                    {{-- 3 & 4. Billing / Shipping Address --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="ir-section-title">3. Billing Address</div>
                            <div class="row g-2">
                                <div class="col-12"><label class="ir-label">Address Line 1 <span class="req">*</span></label>
                                    <input type="text" class="ir-input ir-bill-field" data-card="{{ $i }}" data-field="addr1"
                                        name="parties[{{ $i }}][billing][addr1]" value="{{ $addr1 }}"></div>
                                <div class="col-12"><label class="ir-label">Address Line 2</label>
                                    <input type="text" class="ir-input ir-bill-field" data-card="{{ $i }}" data-field="addr2"
                                        name="parties[{{ $i }}][billing][addr2]" value=""></div>
                                <div class="col-5"><label class="ir-label">City <span class="req">*</span></label>
                                    <input type="text" class="ir-input ir-bill-field" data-card="{{ $i }}" data-field="city"
                                        name="parties[{{ $i }}][billing][city]" value="{{ $addrCity }}"></div>
                                <div class="col-3"><label class="ir-label">State <span class="req">*</span></label>
                                    <input type="text" class="ir-input ir-bill-field" data-card="{{ $i }}" data-field="state"
                                        name="parties[{{ $i }}][billing][state]" value="{{ $addrSt }}" maxlength="5"></div>
                                <div class="col-4"><label class="ir-label">ZIP <span class="req">*</span></label>
                                    <input type="text" class="ir-input ir-bill-field" data-card="{{ $i }}" data-field="zip"
                                        name="parties[{{ $i }}][billing][zip]" value="{{ $addrZip }}"></div>
                                <div class="col-12"><label class="ir-label">Country <span class="req">*</span></label>
                                    <input type="text" class="ir-input ir-bill-field" data-card="{{ $i }}" data-field="country"
                                        name="parties[{{ $i }}][billing][country]" value="{{ $addrCo }}"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="ir-section-title mb-0">4. Shipping Address</div>
                                <label class="ir-checkbox-label">
                                    <input type="checkbox" class="ir-same-billing" data-card="{{ $i }}" checked>
                                    <span style="font-size:.75rem;">Same as billing</span>
                                </label>
                            </div>
                            <div class="row g-2" id="shipping-fields-{{ $i }}">
                                <div class="col-12"><label class="ir-label">Address Line 1 <span class="req">*</span></label>
                                    <input type="text" class="ir-input ir-ship-field" data-card="{{ $i }}" data-field="addr1"
                                        name="parties[{{ $i }}][shipping][addr1]" value="{{ $shipAddr1 }}"></div>
                                <div class="col-12"><label class="ir-label">Address Line 2</label>
                                    <input type="text" class="ir-input ir-ship-field" data-card="{{ $i }}" data-field="addr2"
                                        name="parties[{{ $i }}][shipping][addr2]" value="{{ $shipAddr['line2'] ?? '' }}"></div>
                                <div class="col-5"><label class="ir-label">City <span class="req">*</span></label>
                                    <input type="text" class="ir-input ir-ship-field" data-card="{{ $i }}" data-field="city"
                                        name="parties[{{ $i }}][shipping][city]" value="{{ $shipAddrCity }}"></div>
                                <div class="col-3"><label class="ir-label">State <span class="req">*</span></label>
                                    <input type="text" class="ir-input ir-ship-field" data-card="{{ $i }}" data-field="state"
                                        name="parties[{{ $i }}][shipping][state]" value="{{ $shipAddrSt }}" maxlength="5"></div>
                                <div class="col-4"><label class="ir-label">ZIP <span class="req">*</span></label>
                                    <input type="text" class="ir-input ir-ship-field" data-card="{{ $i }}" data-field="zip"
                                        name="parties[{{ $i }}][shipping][zip]" value="{{ $shipAddrZip }}"></div>
                                <div class="col-12"><label class="ir-label">Country <span class="req">*</span></label>
                                    <input type="text" class="ir-input ir-ship-field" data-card="{{ $i }}" data-field="country"
                                        name="parties[{{ $i }}][shipping][country]" value="{{ $shipAddrCo }}"></div>
                            </div>
                        </div>
                    </div>
                    <hr class="ir-divider">

                    {{-- 5. Line Items --}}
                    <div class="ir-section-title">5. Line Items</div>
                    <div class="table-responsive">
                        <table class="ir-table" id="line-table-{{ $i }}">
                            <thead>
                                <tr>
                                    <th style="width:16%;">Service</th>
                                    <th>Description</th>
                                    <th style="width:10%;">Qty</th>
                                    <th class="text-right" style="width:11%;">Rate</th>
                                    <th class="text-right" style="width:11%;">Amount</th>
                                    <th style="width:32px;"></th>
                                </tr>
                            </thead>
                            <tbody id="line-tbody-{{ $i }}">
                                @foreach($autoLines as $li => $line)
                                <tr class="ir-line-row" data-card="{{ $i }}" data-key="{{ $line['key'] }}">
                                    <td><span class="ir-item-name">{{ $line['name'] }}</span></td>
                                    <td><span class="ir-item-desc">{{ $line['desc'] }}</span></td>
                                    <td>{{ $line['qty'] }}</td>
                                    <td class="text-right">${{ number_format($line['rate'], 2) }}</td>
                                    <td class="text-right"><strong>${{ number_format($line['amt'], 2) }}</strong></td>
                                    <td></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- + Add Service --}}
                    <div class="ir-add-service-row" id="add-svc-row-{{ $i }}">
                        <button type="button" class="ir-add-service-btn" onclick="irShowAddService({{ $i }})">
                            <i class="fas fa-plus"></i> Add Service
                        </button>
                        <div class="ir-add-service-select" id="add-svc-select-{{ $i }}" style="display:flex;gap:.4rem;align-items:center;">
                            <select class="ir-select" id="svc-dropdown-{{ $i }}" style="width:220px;font-size:.85rem;">
                                <option value="">— Select service —</option>
                                @foreach($availableServices as $svc)
                                    <option value="{{ $svc->id }}"
                                        data-name="{{ $svc->name }}"
                                        data-desc="{{ $svc->description ?? '' }}"
                                        data-key="{{ Str::slug($svc->name, '_') }}"
                                        {{ in_array(Str::slug($svc->name,'_'), $autoLineKeys) ? 'disabled' : '' }}>
                                        {{ $svc->name }}{{ in_array(Str::slug($svc->name,'_'), $autoLineKeys) ? ' (already added)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" step="0.01" min="0"
                                class="ir-input" id="svc-amount-{{ $i }}"
                                placeholder="Amount" style="width:110px;font-size:.85rem;">
                            <button type="button" class="btn-ds btn-primary-ds btn-sm"
                                onclick="irAddServiceLine({{ $i }})">Add</button>
                            <button type="button" class="btn-ds btn-secondary-outline btn-sm"
                                onclick="irHideAddService({{ $i }})">Cancel</button>
                        </div>
                    </div>

                    {{-- Tax + Notes --}}
                    <div class="ir-tax-notes mt-3">
                        <div>
                            <label class="ir-label">Tax Code
                                <span style="font-size:.7rem;font-weight:400;color:#6b7280;margin-left:.3rem;">Controlled by Invoice Queue global setting</span>
                            </label>
                            <input type="hidden" name="parties[{{ $i }}][tax_code]" value="{{ $draftTaxCode }}">
                            <select class="ir-select ir-tax-code ir-input-readonly" data-card="{{ $i }}" disabled>
                                @foreach(['NON','TAX'] as $tc)
                                    <option value="{{ $tc }}" {{ $draftTaxCode === $tc ? 'selected' : '' }}>
                                        {{ $tc === 'NON' ? 'NON — Non-taxable' : 'TAX — Taxable (QB calculates)' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="ir-label">Notes to Customer</label>
                            <textarea class="ir-textarea ir-notes"
                                data-card="{{ $i }}"
                                name="parties[{{ $i }}][notes]"
                                rows="3" maxlength="400"
                                placeholder="Enter notes to appear on the invoice…"
                                style="resize:vertical;">{{ $draftNotes }}</textarea>
                            <div class="ir-char-count"><span class="ir-note-count" data-card="{{ $i }}">0</span> / 400</div>
                        </div>
                    </div>

                </div>{{-- /ir-card-body --}}
            </div>{{-- /ir-card --}}
            @endforeach

        </div>{{-- /ir-cards --}}

        {{-- ───────────────── RIGHT: Sidebar ───────────────────────────── --}}
        <div class="ir-sidebar">

            {{-- Event Total Summary --}}
            <div class="ir-sidebar-card">
                <div class="ir-sidebar-title">Event Total Summary</div>
                @foreach($billingParties as $i => $bp)
                    <div class="ir-summary-row">
                        <span>Client {{ chr(65+$i) }} Total</span>
                        <span id="sidebar-total-{{ $i }}">${{ number_format($partyAmounts[$i]['subtotal'], 2) }}</span>
                    </div>
                @endforeach
                <div class="ir-summary-row grand-row">
                    <span>Grand Total</span>
                    <span id="sidebar-grand">${{ number_format($grandTotal, 2) }}</span>
                </div>
                <div class="ir-how-box">
                    <div class="how-title"><i class="fas fa-info-circle"></i> Calculation Rules</div>
                    <ul>
                        <li><strong>Labour</strong> = Hours × Rate ÷ N parties</li>
                        <li><strong>Cup Charge</strong> = Primary party only</li>
                        <li><strong>Buyback, Parking, Expedite, Shipping, Drive-time</strong> = Split evenly ÷ N</li>
                    </ul>
                </div>
            </div>

            {{-- Per-party breakdown --}}
            @foreach($billingParties as $i => $bp)
            @php $amt = $partyAmounts[$i]; @endphp
            <div class="ir-breakdown-card" id="breakdown-card-{{ $i }}">
                <div class="ir-breakdown-title">{{ $billingParties[$i]->client?->name ?? 'Client '.chr(65+$i) }} Breakdown</div>
                <div class="ir-bd-row"><span>Labour Charge</span><span id="bd-labor-{{ $i }}">${{ number_format($amt['laborAmt'], 2) }}</span></div>
                <div class="ir-bd-row"><span>Buyback</span><span id="bd-buyback-{{ $i }}">${{ number_format($amt['buybackAmt'], 2) }}</span></div>
                @if($amt['receiptAmt'] > 0)
                <div class="ir-bd-row"><span>Receipt Percent ({{ $amt['receiptPercent'] }}%)</span><span id="bd-receipt-{{ $i }}">${{ number_format($amt['receiptAmt'], 2) }}</span></div>
                @endif
                <div class="ir-bd-row"><span>Cup Charge</span><span id="bd-cups-{{ $i }}">${{ number_format($amt['cupsAmt'], 2) }}</span></div>
                @if($parkingTotal > 0)
                <div class="ir-bd-row"><span>Parking Fee</span><span id="bd-parking-{{ $i }}">${{ number_format($amt['parkingAmt'], 2) }}</span></div>
                @endif
                @if($expediteTotal > 0)
                <div class="ir-bd-row"><span>Expedite Fee</span><span id="bd-expedite-{{ $i }}">${{ number_format($amt['expediteAmt'], 2) }}</span></div>
                @endif
                @if($shippingTotal > 0)
                <div class="ir-bd-row"><span>Shipping Fee</span><span id="bd-shipping-{{ $i }}">${{ number_format($amt['shippingAmt'], 2) }}</span></div>
                @endif
                @if($driveTimeTotal > 0)
                <div class="ir-bd-row"><span>Drive-time Pay</span><span id="bd-drivetime-{{ $i }}">${{ number_format($amt['driveTimeAmt'], 2) }}</span></div>
                @endif
                <div class="ir-bd-row"><span>Additional (manual)</span><span id="bd-extra-{{ $i }}">$0.00</span></div>
                <div class="ir-bd-row subtotal"><span>Subtotal</span><span id="bd-subtotal-{{ $i }}">${{ number_format($amt['subtotal'], 2) }}</span></div>
                <div class="ir-bd-row"><span id="bd-tax-label-{{ $i }}">Tax</span><span id="bd-tax-{{ $i }}" style="font-style:italic;color:#6b7280;">QB calculated</span></div>
                <div class="ir-bd-row total-final"><span>Total</span><span id="bd-total-{{ $i }}">${{ number_format($amt['subtotal'], 2) }}</span></div>
                <div class="ir-auto-note">
                    <i class="fas fa-info-circle"></i>
                    <span>Auto-calculated from event data and billing rules.</span>
                </div>
            </div>
            @endforeach

        </div>{{-- /ir-sidebar --}}

    </div>{{-- /ir-grid --}}

</div>{{-- /ir-page --}}
    </form>{{-- /saveDraftForm --}}

{{-- Sticky Footer --}}
<div class="ir-footer">
    <a href="{{ route($prefix.'.event-invoicing') }}" class="ir-back-btn">
        <i class="fas fa-arrow-left"></i> Back to Invoice Queue
    </a>
    <span class="ir-footer-note">Save a draft first, then publish to QuickBooks.</span>
    <div class="d-flex gap-2">
        <button type="submit" form="saveDraftForm" class="btn-ds btn-secondary-outline btn-sm">
            <i class="fas fa-save me-1"></i> Save Draft
        </button>
        <button type="button" class="btn-ds btn-success-ds btn-sm" id="publishAllBtn"
            @if(!$isConnected || ($draftInvoices ?? collect())->isEmpty()) disabled title="Save a draft first, then publish." @endif
            onclick="irPublishAll()">
            <i class="fas fa-cloud-upload-alt me-1"></i> Publish All to QuickBooks
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
/* ── Subtotals per card (auto lines) ────────────────── */
var irSubtotals = @json(collect($partyAmounts)->map(fn($a) => $a['subtotal'])->values());
var irExtraAmts = {};

/* ── Collapse / Expand ──────────────────────────────── */
function irToggle(i) {
    document.getElementById('ir-body-'+i).classList.toggle('collapsed');
    document.getElementById('ir-icon-'+i).classList.toggle('open');
}

/* ── Terms → Due Date ───────────────────────────────── */
function addDaysToDate(dateStr, days) {
    const d = new Date(dateStr);
    d.setDate(d.getDate() + days);
    return d.toISOString().split('T')[0];
}
document.querySelectorAll('select[name$="[terms]"]').forEach(function(sel) {
    sel.addEventListener('change', function() {
        const card = this.dataset.card;
        const invDate = document.querySelector('[name="parties['+card+'][invoice_date]"]')?.value;
        const dueEl   = document.getElementById('due-date-'+card);
        if (!invDate || !dueEl) return;
        const map = {'Due on Receipt':0,'Net 10':10,'Net 15':15,'Net 30':30,'Net 60':60};
        dueEl.value = addDaysToDate(invDate, map[this.value] ?? 30);
    });
});
document.querySelectorAll('.ir-inv-date').forEach(function(el) {
    el.addEventListener('change', function() {
        const card  = this.dataset.card;
        const terms = document.querySelector('[name="parties['+card+'][terms]"]')?.value ?? 'Net 30';
        const dueEl = document.getElementById('due-date-'+card);
        if (!dueEl) return;
        const map = {'Due on Receipt':0,'Net 10':10,'Net 15':15,'Net 30':30,'Net 60':60};
        dueEl.value = addDaysToDate(this.value, map[terms] ?? 30);
    });
});

/* ── Same-as-billing checkbox ───────────────────────── */
function syncShipping(card) {
    document.querySelectorAll('.ir-bill-field[data-card="'+card+'"]').forEach(function(bf) {
        const sf = document.querySelector('.ir-ship-field[data-card="'+card+'"][data-field="'+bf.dataset.field+'"]');
        if (sf) sf.value = bf.value;
    });
}
document.querySelectorAll('.ir-same-billing').forEach(function(cb) {
    const card = cb.dataset.card;
    const shipFields = document.querySelectorAll('.ir-ship-field[data-card="'+card+'"]');
    shipFields.forEach(sf => { sf.readOnly=true; sf.style.background='#f9fafb'; });
    syncShipping(card);
    cb.addEventListener('change', function() {
        shipFields.forEach(sf => { sf.readOnly=cb.checked; sf.style.background=cb.checked?'#f9fafb':'#fff'; });
        if (cb.checked) syncShipping(card);
    });
});
document.querySelectorAll('.ir-bill-field').forEach(function(bf) {
    bf.addEventListener('input', function() {
        const card = this.dataset.card;
        const cb = document.querySelector('.ir-same-billing[data-card="'+card+'"]');
        if (cb && cb.checked) syncShipping(card);
    });
});

/* ── Subtotal update (no tax math — QB calculates tax) ── */
function irRecalcTotals(card) {
    const subtotal = (irSubtotals[card] || 0) + (irExtraAmts[card] || 0);

    const setTxt = (id, val) => { const el=document.getElementById(id); if(el) el.textContent=val; };
    setTxt('bd-subtotal-'+card, '$'+subtotal.toFixed(2));
    setTxt('bd-tax-label-'+card, 'Tax');
    setTxt('bd-tax-'+card, 'QB calculated');
    setTxt('bd-total-'+card, '$'+subtotal.toFixed(2));
    setTxt('bd-extra-'+card, '$'+(irExtraAmts[card]||0).toFixed(2));
    setTxt('sidebar-total-'+card, '$'+subtotal.toFixed(2));

    let grand = 0;
    document.querySelectorAll('[id^="bd-total-"]').forEach(t => {
        grand += parseFloat(t.textContent.replace('$','')) || 0;
    });
    setTxt('sidebar-grand', '$'+grand.toFixed(2));
}
// Run once on page load to initialize sidebar values
document.addEventListener('DOMContentLoaded', function() {
    var cards = document.querySelectorAll('[id^="ir-body-"]');
    cards.forEach(function(el) {
        var card = el.id.replace('ir-body-', '');
        irRecalcTotals(card);
    });
});

/* ── Notes character counter ────────────────────────── */
document.querySelectorAll('.ir-notes').forEach(function(ta) {
    ta.addEventListener('input', function() {
        const c = document.querySelector('.ir-note-count[data-card="'+this.dataset.card+'"]');
        if (c) c.textContent = this.value.length;
    });
});

/* ── + Add Service ───────────────────────────────────── */
function irShowAddService(card) {
    document.getElementById('add-svc-select-'+card).style.display='flex';
    document.querySelector('#add-svc-row-'+card+' .ir-add-service-btn').style.display='none';
}
function irHideAddService(card) {
    document.getElementById('add-svc-select-'+card).style.display='none';
    document.querySelector('#add-svc-row-'+card+' .ir-add-service-btn').style.display='inline-flex';
    document.getElementById('svc-dropdown-'+card).value='';
    document.getElementById('svc-amount-'+card).value='';
}
function irAddServiceLine(card) {
    const sel    = document.getElementById('svc-dropdown-'+card);
    const amtEl  = document.getElementById('svc-amount-'+card);
    const opt    = sel.options[sel.selectedIndex];
    const amt    = parseFloat(amtEl.value || 0);

    if (!sel.value || !opt) {
        Swal.fire({ icon: 'warning', title: 'Select a Service', text: 'Please select a service before adding.', confirmButtonColor: '#0d6efd' });
        return;
    }
    if (amt <= 0) {
        Swal.fire({ icon: 'warning', title: 'Invalid Amount', text: 'Please enter a valid amount greater than 0.', confirmButtonColor: '#0d6efd' });
        return;
    }

    const name      = opt.dataset.name;
    const desc      = opt.dataset.desc || '';
    const key       = opt.dataset.key;
    const serviceId = sel.value;

    // Determine next line item index for this card
    const existingHiddens = document.querySelectorAll(
        `input[name^="parties[${card}][line_items]["]`
    );
    // Get max index used so far
    const indices = Array.from(existingHiddens).map(el => {
        const m = el.name.match(/\[(\d+)\]\[/);
        return m ? parseInt(m[1]) : -1;
    });
    const nextIdx = indices.length ? Math.max(...indices) + 1 : 0;

    // Append hidden inputs for this manual line item
    const form = document.getElementById('saveDraftForm');
    const fields = {
        service_id:  serviceId,
        service_key: key,
        description: desc,
        quantity:    '1',
        rate:        amt.toFixed(2),
        amount:      amt.toFixed(2),
        sort_order:  nextIdx.toString(),
    };
    const hiddenIds = [];
    Object.entries(fields).forEach(([field, val]) => {
        const inp = document.createElement('input');
        inp.type  = 'hidden';
        inp.name  = `parties[${card}][line_items][${nextIdx}][${field}]`;
        inp.value = val;
        inp.dataset.manualKey = key;
        inp.dataset.card = card;
        inp.className = 'ir-manual-hidden';
        form.appendChild(inp);
        hiddenIds.push(inp);
    });

    // Add visible row
    const tbody = document.getElementById('line-tbody-'+card);
    const tr = document.createElement('tr');
    tr.className = 'ir-line-row ir-line-manual';
    tr.dataset.card = card;
    tr.dataset.key  = key;
    tr.dataset.lineIdx = nextIdx;
    tr.innerHTML = `
        <td><span class="ir-item-name">${name}</span></td>
        <td><span class="ir-item-desc">${desc}</span></td>
        <td>1</td>
        <td class="text-right">$${amt.toFixed(2)}</td>
        <td class="text-right"><strong>$${amt.toFixed(2)}</strong></td>
        <td><button type="button" class="ir-line-remove" onclick="irRemoveLine(this,${card},${amt},${nextIdx})" title="Remove"><i class="fas fa-times"></i></button></td>`;
    tbody.appendChild(tr);

    // Disable option in dropdown
    opt.disabled = true;
    opt.text = opt.dataset.name + ' (already added)';

    // Accumulate extra
    irExtraAmts[card] = (irExtraAmts[card] || 0) + amt;
    irRecalcTotals(card);
    irHideAddService(card);
}
function irRemoveLine(btn, card, amt, lineIdx) {
    const row = btn.closest('tr');
    const key = row.dataset.key;

    // Re-enable dropdown option
    const opt = document.querySelector('#svc-dropdown-'+card+' option[data-key="'+key+'"]');
    if (opt) { opt.disabled=false; opt.text=opt.dataset.name; }

    // Remove associated hidden inputs
    document.querySelectorAll('.ir-manual-hidden[data-manual-key="'+key+'"][data-card="'+card+'"]')
        .forEach(el => el.remove());

    irExtraAmts[card] = Math.max(0, (irExtraAmts[card]||0) - amt);
    row.remove();
    irRecalcTotals(card);
}

/* ── Phase 3: Publish All Drafts to QuickBooks ──────── */
var irPublishUrl = '{{ route($prefix.".event.invoice-review.publish-drafts", $event) }}';
var irCsrfToken  = document.querySelector('meta[name="csrf-token"]')?.content
                 || '{{ csrf_token() }}';

function irPublishAll() {
    var btn = document.getElementById('publishAllBtn');
    if (!btn || btn.disabled) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Publishing…';

    Swal.fire({
        title: 'Publish to QuickBooks?',
        text: 'All Draft invoices will be published. Already-published invoices will be skipped.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Publish',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
    }).then(function(result) {
        if (!result.isConfirmed) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-cloud-upload-alt me-1"></i> Publish All to QuickBooks';
            return;
        }
        irDoPublish(btn);
    });
}

function irDoPublish(btn) {
    fetch(irPublishUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': irCsrfToken,
            'Accept':       'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({}),
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        btn.innerHTML = '<i class="fas fa-cloud-upload-alt me-1"></i> Publish All to QuickBooks';

        var errorHtml = '';
        if (data.errors && data.errors.length) {
            errorHtml = '<ul style="text-align:left;margin-top:8px;padding-left:18px;">';
            data.errors.forEach(function(e) {
                errorHtml += '<li><strong>' + e.party + ':</strong> ' + e.error + '</li>';
            });
            errorHtml += '</ul>';
        }

        if (data.success && !data.partial) {
            Swal.fire({
                icon: 'success',
                title: 'Published!',
                html: '<strong>' + data.summary + '</strong>',
                confirmButtonColor: '#0d6efd',
            }).then(function() { window.location.reload(); });
        } else if (data.partial) {
            Swal.fire({
                icon: 'warning',
                title: 'Partial Success',
                html: '<strong>' + data.summary + '</strong>' + errorHtml,
                confirmButtonColor: '#0d6efd',
            }).then(function() { window.location.reload(); });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Publishing Failed',
                html: '<strong>' + data.summary + '</strong>' + errorHtml,
                confirmButtonColor: '#0d6efd',
            });
            btn.disabled = false;
        }
    })
    .catch(function(err) {
        btn.innerHTML = '<i class="fas fa-cloud-upload-alt me-1"></i> Publish All to QuickBooks';
        btn.disabled = false;
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: err.message,
            confirmButtonColor: '#0d6efd',
        });
    });
}
</script>
@endpush
