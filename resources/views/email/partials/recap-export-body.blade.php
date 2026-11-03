<p style="margin: 0 0 16px;">Your requested recap export is attached to this email.</p>

@if ($event)
    <p style="margin: 24px 0 12px; font-size: 14px; font-weight: 600; color: #111827; text-transform: uppercase; letter-spacing: 0.04em;">
        Event Summary
    </p>
    <p style="margin: 0 0 8px;"><strong>Event Name:</strong> {{ $event->event_name ?? '—' }}</p>
    <p style="margin: 0 0 8px;"><strong>Event ID:</strong> {{ $event->id ?? '—' }}</p>
    <p style="margin: 0 0 8px;"><strong>Date:</strong> {{ $event->FormattedDate ?? '—' }}</p>
    <p style="margin: 0;"><strong>Brand:</strong> {{ $event->brand->brand_name ?? '—' }}</p>
@endif

<p style="margin: 24px 0 0; color: #6b7280; font-size: 14px;">
    If you did not request this export, you can ignore this message.
</p>
