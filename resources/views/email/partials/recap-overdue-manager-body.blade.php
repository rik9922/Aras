@php
    $isMissingRecap = is_null($recap ?? null);
    $eventLabel = $event->event_name ?? ($event->brand->brand_name ?? 'N/A');
@endphp

@if ($isMissingRecap)
    <p style="margin: 0 0 16px;">
        The recap for event <strong>{{ $eventLabel }}</strong> (ID: {{ $event->id }}) has not been submitted within
        48 hours of the event end time and has been automatically marked as <strong>overdue</strong> with a payout of
        <strong>$0</strong>.
    </p>
    <p style="margin: 0 0 16px;">
        Please review this event and, if valid transactions occurred, enter the correct payout amount and submit the
        recap so that normal invoicing and QuickBooks sync can proceed. If no transactions occurred, mark this event as
        a <strong>No-Show</strong> so that no payout or QuickBooks record is created.
    </p>
@else
    <p style="margin: 0 0 16px;">
        The recap for event <strong>{{ $eventLabel }}</strong> (ID: {{ $event->id }}) was rejected more than 24 hours
        ago and has not been resubmitted by the Brand Ambassador. The payout has been automatically set to
        <strong>$0</strong>.
    </p>
    <p style="margin: 0 0 16px;">
        Please review the recap and event details. If there are valid transactions and you wish to override this, you
        may manually adjust the payout and proceed with normal processing. If there were no valid transactions, mark
        the recap as a <strong>No-Show</strong>.
    </p>
@endif

<p style="margin: 24px 0 12px; font-size: 14px; font-weight: 600; color: #111827; text-transform: uppercase; letter-spacing: 0.04em;">
    Event Summary
</p>

<p style="margin: 0 0 8px;"><strong>Event ID:</strong> {{ $event->id }}</p>
<p style="margin: 0 0 8px;"><strong>Event Name:</strong> {{ $eventLabel }}</p>
<p style="margin: 0 0 8px;">
    <strong>Date:</strong>
    @if ($event->booked_date)
        {{ \Carbon\Carbon::parse($event->booked_date)->format('M d, Y') }}
    @else
        N/A
    @endif
</p>
<p style="margin: 0;"><strong>Location:</strong> {{ $event->location?->city ?? 'N/A' }}</p>
