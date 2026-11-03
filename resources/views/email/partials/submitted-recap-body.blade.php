<p style="margin: 0 0 16px;">A recap has been submitted and is awaiting your review.</p>

<p style="margin: 24px 0 12px; font-size: 14px; font-weight: 600; color: #111827; text-transform: uppercase; letter-spacing: 0.04em;">
    Submitted By
</p>
<p style="margin: 0 0 8px;"><strong>Name:</strong> {{ $submittedUser->name }}</p>
<p style="margin: 0 0 16px;"><strong>Email:</strong> {{ $submittedUser->email }}</p>

<p style="margin: 0 0 12px; font-size: 14px; font-weight: 600; color: #111827; text-transform: uppercase; letter-spacing: 0.04em;">
    Ambassador Details
</p>
<p style="margin: 0 0 8px;"><strong>Name:</strong> {{ $recap->ambassador->name }}</p>
<p style="margin: 0 0 8px;"><strong>Email:</strong> {{ $recap->ambassador->email }}</p>
<p style="margin: 0 0 16px;"><strong>Phone:</strong> {{ $recap->ambassador->phone ?? 'N/A' }}</p>

<p style="margin: 0 0 12px; font-size: 14px; font-weight: 600; color: #111827; text-transform: uppercase; letter-spacing: 0.04em;">
    Event Details
</p>
<p style="margin: 0 0 8px;"><strong>Event ID:</strong> {{ $recap->event->id }}</p>
<p style="margin: 0 0 8px;"><strong>Event Name:</strong> {{ $recap->event->event_name }}</p>
<p style="margin: 0 0 8px;"><strong>Event Date:</strong> {{ \Carbon\Carbon::parse($recap->event->date)->format('M d, Y') }}</p>
<p style="margin: 0 0 16px;"><strong>Submitted On:</strong> {{ $recap->created_at->format('M d, Y h:i A') }}</p>

<p style="margin: 0 0 16px;">Please review the recap and either approve or deny it.</p>

<p style="margin: 0; color: #6b7280; font-size: 14px;">
    Once approved, payment will be processed via Dwolla and recorded in QuickBooks automatically.
</p>
