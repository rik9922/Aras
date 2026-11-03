<div style="font-family: Arial, Helvetica, sans-serif; color: #333; line-height: 1.5; max-width: 640px;">
    <p style="margin: 0 0 16px; font-size: 15px;">{{ $summary }}</p>

    <h3 style="margin: 24px 0 8px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.04em; color: #555;">Ambassador</h3>
    <table cellpadding="0" cellspacing="0" border="0" style="width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom: 8px;">
        <tr>
            <td style="padding: 8px 12px; background: #f5f5f5; width: 140px; vertical-align: top;"><strong>Name</strong></td>
            <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">{{ $ambassadorName }}</td>
        </tr>
        @if(!empty($ambassadorEmail))
        <tr>
            <td style="padding: 8px 12px; background: #f5f5f5; vertical-align: top;"><strong>Email</strong></td>
            <td style="padding: 8px 12px; border-bottom: 1px solid #eee;"><a href="mailto:{{ $ambassadorEmail }}" style="color: #2E517C;">{{ $ambassadorEmail }}</a></td>
        </tr>
        @endif
        @if(!empty($ambassadorPhone))
        <tr>
            <td style="padding: 8px 12px; background: #f5f5f5; vertical-align: top;"><strong>Phone</strong></td>
            <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">{{ $ambassadorPhone }}</td>
        </tr>
        @endif
        <tr>
            <td style="padding: 8px 12px; background: #f5f5f5; vertical-align: top;"><strong>Check-in status</strong></td>
            <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">
                <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; background: #fdecea; color: #c0392b;">{{ $checkInStatusLabel }}</span>
            </td>
        </tr>
        @if(!empty($scheduledStartTime))
        <tr>
            <td style="padding: 8px 12px; background: #f5f5f5; vertical-align: top;"><strong>Scheduled start</strong></td>
            <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">{{ $scheduledStartTime }}</td>
        </tr>
        @endif
        @if(!empty($checkInDetail))
        <tr>
            <td style="padding: 8px 12px; background: #f5f5f5; vertical-align: top;"><strong>Details</strong></td>
            <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">{{ $checkInDetail }}</td>
        </tr>
        @endif
    </table>

    <h3 style="margin: 24px 0 8px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.04em; color: #555;">Event details</h3>
    <table cellpadding="0" cellspacing="0" border="0" style="width: 100%; border-collapse: collapse; font-size: 14px;">
        <tr>
            <td style="padding: 8px 12px; background: #f5f5f5; width: 140px; vertical-align: top;"><strong>Event ID</strong></td>
            <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">#{{ $eventId }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 12px; background: #f5f5f5; vertical-align: top;"><strong>Event name</strong></td>
            <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">{{ $eventName }}</td>
        </tr>
        @if(!empty($eventType))
        <tr>
            <td style="padding: 8px 12px; background: #f5f5f5; vertical-align: top;"><strong>Event type</strong></td>
            <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">{{ $eventType }}</td>
        </tr>
        @endif
        @if(!empty($brandName))
        <tr>
            <td style="padding: 8px 12px; background: #f5f5f5; vertical-align: top;"><strong>Brand</strong></td>
            <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">{{ $brandName }}</td>
        </tr>
        @endif
        @if(!empty($eventDate))
        <tr>
            <td style="padding: 8px 12px; background: #f5f5f5; vertical-align: top;"><strong>Date</strong></td>
            <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">{{ $eventDate }}</td>
        </tr>
        @endif
        @if(!empty($eventTime))
        <tr>
            <td style="padding: 8px 12px; background: #f5f5f5; vertical-align: top;"><strong>Time</strong></td>
            <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">{{ $eventTime }}</td>
        </tr>
        @endif
        @if(!empty($eventLocation))
        <tr>
            <td style="padding: 8px 12px; background: #f5f5f5; vertical-align: top;"><strong>Location</strong></td>
            <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">{{ $eventLocation }}</td>
        </tr>
        @endif
        @if(!empty($department))
        <tr>
            <td style="padding: 8px 12px; background: #f5f5f5; vertical-align: top;"><strong>Department</strong></td>
            <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">{{ $department }}</td>
        </tr>
        @endif
        @if(!empty($distributor))
        <tr>
            <td style="padding: 8px 12px; background: #f5f5f5; vertical-align: top;"><strong>Distributor</strong></td>
            <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">{{ $distributor }}</td>
        </tr>
        @endif
        @if(!empty($paymentType))
        <tr>
            <td style="padding: 8px 12px; background: #f5f5f5; vertical-align: top;"><strong>Payment type</strong></td>
            <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">{{ $paymentType }}</td>
        </tr>
        @endif
        @if(!empty($eventStatus))
        <tr>
            <td style="padding: 8px 12px; background: #f5f5f5; vertical-align: top;"><strong>Status</strong></td>
            <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">{{ $eventStatus }}</td>
        </tr>
        @endif
    </table>

    @if(!empty($eventUrl))
    <p style="margin: 24px 0 0;">
        <a href="{{ $eventUrl }}" style="display: inline-block; padding: 10px 18px; background: #2E517C; color: #fff; text-decoration: none; border-radius: 4px; font-size: 14px; font-weight: 600;">View event in portal</a>
    </p>
    @endif

    <p style="margin: 24px 0 0; font-size: 12px; color: #777;">This is an automated late check-in alert from Aras Promotions. Please follow up with the ambassador if needed.</p>
</div>
