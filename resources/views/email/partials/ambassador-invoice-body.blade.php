@php
    $isReimbursement = isset($reimbursement) && $reimbursement;
    $paymentReference = $isReimbursement ? 'REIMB-'.$reimbursement->id : ($payout->reference ?? 'N/A');
    $paymentAmount = $isReimbursement ? $reimbursement->amount : ($payout->amount ?? 0);
    $paymentCurrency = $isReimbursement ? 'USD' : ($payout->currency ?? 'USD');
    $paymentStatus = $isReimbursement ? $reimbursement->status : ($payout->status ?? 'completed');
    $transactionId = $isReimbursement ? $reimbursement->dwolla_transfer_id : ($payout->dwolla_transfer_id ?? null);
    $bankName = $fundingSource->bank_name ?? null;
    $accountLast4 = $fundingSource?->account_number ? substr((string) $fundingSource->account_number, -4) : null;
    $paymentDate = $isReimbursement
        ? ($reimbursement->paid_at ?? $reimbursement->updated_at)
        : ($payout->completed_at ?? $payout->approved_at ?? $payout->updated_at);
    $eventDate = $event?->formatted_date ?? ($event?->start_time ? \Carbon\Carbon::parse($event->start_time)->format('M d, Y') : null);
    $eventTime = isset($event) && $event && $event->start_time && $event->end_time
        ? strip_tags($event->formatted_time_with_date ?: $event->formatted_time)
        : null;
@endphp

<p style="margin: 0 0 16px;">
    {{ $isReimbursement ? 'Your reimbursement payment has been successfully completed.' : 'Your ambassador payment has been successfully completed.' }}
</p>

<p style="margin: 0 0 12px; font-size: 14px; font-weight: 600; color: #111827; text-transform: uppercase; letter-spacing: 0.04em;">
    Payment Details
</p>

<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; font-size: 14px; margin-bottom: 16px;">
    @unless($isReimbursement)
    <tr>
        <td style="padding: 8px 12px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; width: 40%;"><strong>Invoice Number</strong></td>
        <td style="padding: 8px 12px; border-bottom: 1px solid #e5e7eb;">{{ $invoice->doc_number ?? 'Pending' }}</td>
    </tr>
    @endunless
    <tr>
        <td style="padding: 8px 12px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;"><strong>Reference</strong></td>
        <td style="padding: 8px 12px; border-bottom: 1px solid #e5e7eb;">{{ $paymentReference }}</td>
    </tr>
    @if($isReimbursement)
    <tr>
        <td style="padding: 8px 12px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;"><strong>Expense Date</strong></td>
        <td style="padding: 8px 12px; border-bottom: 1px solid #e5e7eb;">{{ $reimbursement->expense_date?->format('M d, Y') ?? 'N/A' }}</td>
    </tr>
    @endif
    <tr>
        <td style="padding: 8px 12px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;"><strong>Amount</strong></td>
        <td style="padding: 8px 12px; border-bottom: 1px solid #e5e7eb;">${{ number_format((float) $paymentAmount, 2) }} {{ $paymentCurrency }}</td>
    </tr>
    <tr>
        <td style="padding: 8px 12px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;"><strong>Status</strong></td>
        <td style="padding: 8px 12px; border-bottom: 1px solid #e5e7eb;">{{ ucfirst($paymentStatus) }}</td>
    </tr>
    @if($transactionId)
    <tr>
        <td style="padding: 8px 12px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;"><strong>Transaction ID</strong></td>
        <td style="padding: 8px 12px; border-bottom: 1px solid #e5e7eb;">{{ $transactionId }}</td>
    </tr>
    @endif
    @if($bankName)
    <tr>
        <td style="padding: 8px 12px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;"><strong>Bank</strong></td>
        <td style="padding: 8px 12px; border-bottom: 1px solid #e5e7eb;">{{ $bankName }}</td>
    </tr>
    @endif
    @if($accountLast4)
    <tr>
        <td style="padding: 8px 12px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;"><strong>Account</strong></td>
        <td style="padding: 8px 12px; border-bottom: 1px solid #e5e7eb;">****{{ $accountLast4 }}</td>
    </tr>
    @endif
    <tr>
        <td style="padding: 8px 12px; background: #f9fafb;"><strong>Date</strong></td>
        <td style="padding: 8px 12px;">{{ $paymentDate ? $paymentDate->format('M d, Y') : 'N/A' }}</td>
    </tr>
</table>

<p style="margin: 0 0 12px; font-size: 14px; font-weight: 600; color: #111827; text-transform: uppercase; letter-spacing: 0.04em;">
    {{ $isReimbursement ? 'Reimbursement Details' : 'Event Details' }}
</p>

<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; font-size: 14px; margin-bottom: 16px;">
    @if($event)
    <tr>
        <td style="padding: 8px 12px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; width: 40%;"><strong>Event</strong></td>
        <td style="padding: 8px 12px; border-bottom: 1px solid #e5e7eb;">{{ $event->event_name ?? 'N/A' }}</td>
    </tr>
    <tr>
        <td style="padding: 8px 12px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;"><strong>Event ID</strong></td>
        <td style="padding: 8px 12px; border-bottom: 1px solid #e5e7eb;">{{ $event->id ?? 'N/A' }}</td>
    </tr>
    @if($eventDate)
    <tr>
        <td style="padding: 8px 12px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;"><strong>Event Date</strong></td>
        <td style="padding: 8px 12px; border-bottom: 1px solid #e5e7eb;">{{ $eventDate }}</td>
    </tr>
    @endif
    @if($eventTime)
    <tr>
        <td style="padding: 8px 12px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;"><strong>Event Time</strong></td>
        <td style="padding: 8px 12px; border-bottom: 1px solid #e5e7eb;">{!! $eventTime !!}</td>
    </tr>
    @endif
    @else
    <tr>
        <td style="padding: 8px 12px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; width: 40%;"><strong>Event</strong></td>
        <td style="padding: 8px 12px; border-bottom: 1px solid #e5e7eb;">N/A</td>
    </tr>
    @endif
    @if($brand || ($isReimbursement && $reimbursement->brand_name))
    <tr>
        <td style="padding: 8px 12px; background: #f9fafb; border-bottom: {{ $isReimbursement ? '1px solid #e5e7eb' : '0' }};"><strong>Brand</strong></td>
        <td style="padding: 8px 12px; border-bottom: {{ $isReimbursement ? '1px solid #e5e7eb' : '0' }};">{{ $brand->brand_name ?? $reimbursement->brand_name ?? 'N/A' }}</td>
    </tr>
    @endif
    @if($isReimbursement)
    <tr>
        <td style="padding: 8px 12px; background: #f9fafb;"><strong>Reason</strong></td>
        <td style="padding: 8px 12px;">{{ $reimbursement->reason }}</td>
    </tr>
    @endif
</table>

<p style="margin: 0; color: #6b7280; font-size: 14px;">If you have any questions, please contact our support team.</p>
