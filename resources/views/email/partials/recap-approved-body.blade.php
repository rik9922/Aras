<p style="margin: 0 0 16px;">
    Great news! Your event recap has been <strong style="color: #15803d;">approved</strong>.
</p>

<p style="margin: 0 0 12px; font-size: 14px; font-weight: 600; color: #111827; text-transform: uppercase; letter-spacing: 0.04em;">
    Event Details
</p>
<p style="margin: 0 0 8px;"><strong>Event Name:</strong> {{ $recap->event->name }}</p>
<p style="margin: 0 0 8px;"><strong>Event Date:</strong> {{ \Carbon\Carbon::parse($recap->event->date)->format('M d, Y') }}</p>
<p style="margin: 0 0 16px;"><strong>Approved On:</strong> {{ now()->format('M d, Y h:i A') }}</p>

<p style="margin: 0;">Good news! Your payment is now on its way. The funds will be sent to you shortly.</p>
