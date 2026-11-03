<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoiceNum }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
            color: #1a1a1a;
            background: #fff;
            line-height: 1.5;
        }

        @page {
            size: A4;
            margin: 1.5cm 1.8cm;
        }

        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }

        /* ── Page wrapper ─────────────────────────────────────────── */
        .page {
            width: 100%;
            max-width: 780px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* ── Header ───────────────────────────────────────────────── */
        .inv-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 1.5rem;
            border-bottom: 3px solid #0f2745;
            margin-bottom: 1.75rem;
        }
        .inv-header .company-block .company-name {
            font-size: 1.3rem;
            font-weight: 800;
            color: #0f2745;
            letter-spacing: -.01em;
        }
        .inv-header .company-block .company-sub {
            font-size: .78rem;
            color: #6b7280;
            margin-top: .15rem;
        }
        .inv-header .invoice-block { text-align: right; }
        .inv-header .invoice-block .inv-title {
            font-size: 2rem;
            font-weight: 900;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #0f2745;
        }
        .inv-header .invoice-block .inv-number {
            font-size: .95rem;
            font-weight: 700;
            color: #374151;
            margin-top: .2rem;
        }
        .inv-header .invoice-block .inv-dates {
            font-size: .78rem;
            color: #6b7280;
            margin-top: .35rem;
            line-height: 1.8;
        }

        /* ── Info grid ────────────────────────────────────────────── */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .info-block .label {
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #9ca3af;
            margin-bottom: .4rem;
        }
        .info-block .value {
            font-size: .85rem;
            color: #1f2937;
            line-height: 1.65;
        }
        .info-block .value strong {
            font-weight: 600;
            color: #0f2745;
        }

        /* ── Line items table ─────────────────────────────────────── */
        .line-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
            font-size: .85rem;
        }
        .line-table thead tr {
            background-color: #0f2745;
        }
        .line-table thead th {
            padding: .65rem 1rem;
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #fff;
            white-space: nowrap;
        }
        .line-table thead th:last-child,
        .line-table thead th:nth-child(2),
        .line-table thead th:nth-child(3) { text-align: right; }
        .line-table tbody tr { border-bottom: 1px solid #f0f0f0; }
        .line-table tbody tr:last-child { border-bottom: none; }
        .line-table tbody td {
            padding: .7rem 1rem;
            color: #374151;
            vertical-align: top;
        }
        .line-table tbody td:nth-child(2),
        .line-table tbody td:nth-child(3),
        .line-table tbody td:nth-child(4) { text-align: right; white-space: nowrap; }
        .line-desc { font-weight: 600; color: #1f2937; }
        .line-sub  { font-size: .75rem; color: #9ca3af; margin-top: .1rem; }

        /* Receipt percent row */
        .receipt-row td { background: #f9fafb; color: #4b5563; font-style: italic; }
        .receipt-row .line-desc { font-weight: 500; }

        /* Alternating rows */
        .line-table tbody tr:nth-child(even):not(.receipt-row) { background: #fafbfc; }

        /* ── Totals ───────────────────────────────────────────────── */
        .totals-wrap {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1.75rem;
        }
        .totals-table {
            width: 260px;
            font-size: .875rem;
        }
        .totals-table tr td {
            padding: .4rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .totals-table tr:last-child td { border-bottom: none; }
        .totals-table .t-label { color: #6b7280; }
        .totals-table .t-value { text-align: right; font-weight: 600; color: #1f2937; }
        .totals-table .t-tax { color: #9ca3af; font-style: italic; }
        .totals-table tr.grand td {
            border-top: 2px solid #0f2745;
            border-bottom: none;
            padding-top: .65rem;
            font-size: 1rem;
            font-weight: 800;
            color: #0f2745;
        }
        .totals-table tr.balance td { color: #dc2626; font-weight: 700; font-size: .9rem; }

        /* ── Notes ────────────────────────────────────────────────── */
        .notes-block {
            background: #f8fafc;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: .9rem 1.1rem;
            font-size: .82rem;
            color: #374151;
            margin-bottom: 1.5rem;
        }
        .notes-block .notes-label {
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #9ca3af;
            margin-bottom: .35rem;
        }

        /* ── Footer ───────────────────────────────────────────────── */
        .inv-footer {
            border-top: 1px solid #e5e7eb;
            margin-top: 2rem;
            padding-top: 1rem;
            font-size: .75rem;
            color: #9ca3af;
            text-align: center;
        }

        /* ── Print button (screen only) ───────────────────────────── */
        .print-bar {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: #0f2745;
            color: #fff;
            padding: .65rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: .85rem;
            z-index: 999;
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
        }
        .print-bar .bar-title { font-weight: 600; opacity: .85; }
        .print-bar .bar-actions { display: flex; gap: .65rem; }
        .btn-bar {
            padding: .4rem .9rem;
            border-radius: 6px;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
        }
        .btn-bar-primary { background: #fff; color: #0f2745; }
        .btn-bar-secondary { background: rgba(255,255,255,.15); color: #fff; }
        .btn-bar-secondary:hover { background: rgba(255,255,255,.25); color: #fff; }

        /* Offset page content below fixed bar */
        @media screen {
            body { padding-top: 50px; }
        }
    </style>
</head>
<body>

    {{-- Screen-only print bar --}}
    <div class="print-bar no-print">
        <span class="bar-title">Invoice #{{ $invoiceNum }} — Print Preview</span>
        <div class="bar-actions">
            <button class="btn-bar btn-bar-primary" onclick="window.print()">🖨 Print / Save as PDF</button>
            <button class="btn-bar btn-bar-secondary" onclick="window.close()">✕ Close</button>
        </div>
    </div>

    <div class="page">

        {{-- Header --}}
        <div class="inv-header">
            <div class="company-block">
                <div class="company-name">Aras Promotions</div>
                <div class="company-sub">Event Marketing</div>
            </div>
            <div class="invoice-block">
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

        {{-- Info grid: Bill To / Event / Payment Details --}}
        <div class="info-grid">
            {{-- Bill To --}}
            <div class="info-block">
                <div class="label">Bill To</div>
                <div class="value">
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
                </div>
            </div>

            {{-- Event --}}
            @if($event)
                <div class="info-block">
                    <div class="label">Event</div>
                    <div class="value">
                        <strong>{{ $event->event_name ?? ('Event #' . $event->id) }}</strong><br>
                        @if($event->start_time)
                            {{ \Carbon\Carbon::parse($event->start_time)->format('M d, Y g:i A') }}<br>
                        @endif
                        @if($event->city)
                            {{ $event->city }}
                        @endif
                    </div>
                </div>
            @endif

            {{-- Payment Details --}}
            <div class="info-block">
                <div class="label">Payment Details</div>
                <div class="value">
                    @if($invoice->terms)
                        <strong>Terms:</strong> {{ $invoice->terms }}<br>
                    @endif
                    @if($invoice->po_number)
                        <strong>PO #:</strong> {{ $invoice->po_number }}<br>
                    @endif
                    <strong>Currency:</strong> {{ $invoice->currency_code ?? 'USD' }}
                </div>
            </div>
        </div>

        {{-- Ambassadors --}}
        @if($event && $event->ambassador->isNotEmpty())
            <div style="margin-bottom:1.5rem;">
                <div class="info-block">
                    <div class="label">Ambassador(s)</div>
                    <div class="value">
                        @foreach($event->ambassador as $amb)
                            <strong>{{ $amb->name }}</strong>@if($amb->email) — {{ $amb->email }}@endif<br>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Line items --}}
        @if($lineItems->count() || $jsonLines->count())
            <table class="line-table">
                <thead>
                    <tr>
                        <th style="text-align:left;">Description</th>
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @if($lineItems->count())
                        @foreach($lineItems as $li)
                            @php
                                $isBuyback  = $li->service && mb_strtolower($li->service->name) === 'buyback';
                                $lineAmt    = (float) $li->amount;
                                $receiptAmt = ($isBuyback && $receiptPercent > 0 && $lineAmt > 0)
                                    ? round($lineAmt * ($receiptPercent / 100), 2)
                                    : 0;
                            @endphp
                            <tr>
                                <td>
                                    <div class="line-desc">{{ $li->service?->name ?? $li->description ?? '—' }}</div>
                                </td>
                                <td>{{ number_format((float)$li->quantity, 2) }}</td>
                                <td>${{ number_format((float)$li->rate, 2) }}</td>
                                <td>${{ number_format($lineAmt, 2) }}</td>
                            </tr>
                            @if($receiptAmt > 0)
                                <tr class="receipt-row">
                                    <td><div class="line-desc">Receipt Percent ({{ $receiptPercent }}%)</div></td>
                                    <td>1.00</td>
                                    <td>${{ number_format($receiptAmt, 2) }}</td>
                                    <td>${{ number_format($receiptAmt, 2) }}</td>
                                </tr>
                            @endif
                        @endforeach
                    @else
                        @foreach($jsonLines as $li)
                            <tr>
                                <td><div class="line-desc">{{ $li['description'] ?? '—' }}</div></td>
                                <td>{{ number_format((float)($li['quantity'] ?? 1), 2) }}</td>
                                <td>${{ number_format((float)($li['unit_price'] ?? 0), 2) }}</td>
                                <td>${{ number_format((float)($li['amount'] ?? 0), 2) }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        @endif

        {{-- Totals --}}
        <div class="totals-wrap">
            <table class="totals-table">
                @if($displaySubtotal != $totalAmount || $taxAmount > 0)
                    <tr>
                        <td class="t-label">Subtotal</td>
                        <td class="t-value">${{ number_format($displaySubtotal, 2) }}</td>
                    </tr>
                @endif
                @if($taxAmount > 0)
                    <tr>
                        <td class="t-label">Tax</td>
                        <td class="t-value">${{ number_format($taxAmount, 2) }}</td>
                    </tr>
                @elseif($lineItems->count())
                    <tr>
                        <td class="t-label">Tax</td>
                        <td class="t-value t-tax">Calculated separately</td>
                    </tr>
                @endif
                <tr class="grand">
                    <td>Total</td>
                    <td>${{ number_format($grandTotal, 2) }}</td>
                </tr>
                @if($balance > 0 && $balance != $totalAmount)
                    <tr class="balance">
                        <td>Balance Due</td>
                        <td>${{ number_format($balance, 2) }}</td>
                    </tr>
                @endif
            </table>
        </div>

        {{-- Notes --}}
        @if($invoice->notes_to_customer || $invoice->customer_memo)
            <div class="notes-block">
                <div class="notes-label">Notes</div>
                {{ $invoice->notes_to_customer ?? $invoice->customer_memo }}
            </div>
        @endif

        {{-- Footer --}}
        <div class="inv-footer">
            Aras Promotions &mdash; Thank you for your business.
        </div>

    </div>{{-- /.page --}}

    <script>
        // Auto-trigger print dialog when opened as popup
        if (window.opener) {
            window.addEventListener('load', function () {
                setTimeout(function () { window.print(); }, 400);
            });
        }
    </script>

</body>
</html>
