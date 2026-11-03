@php
    $statusColor = ($locationStatus ?? '') === 'at_event' ? '#2d6a1e' : '#9a6700';
    $statusBg = ($locationStatus ?? '') === 'at_event' ? '#eaffed' : '#fff8f1';
@endphp

<p style="font-size: 16px; line-height: 1.5; color: #2d3035; margin: 0 0 16px;">
    {{ $summary }}
</p>

<table role="presentation" cellpadding="0" cellspacing="0" width="100%"
    style="border-collapse: collapse; margin-bottom: 20px; font-family: Arial, sans-serif; font-size: 14px;">
    <tr>
        <td colspan="2"
            style="background: #2e517c; color: #ffffff; padding: 10px 14px; font-weight: bold; font-size: 15px;">
            Ambassador
        </td>
    </tr>
    <tr>
        <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #4c5763; width: 38%;">Name</td>
        <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #2d3035; font-weight: 600;">
            {{ $ambassadorName }}
        </td>
    </tr>
    @if (!empty($ambassadorEmail))
        <tr>
            <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #4c5763;">Email</td>
            <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #2d3035;">
                <a href="mailto:{{ $ambassadorEmail }}" style="color: #1c4975;">{{ $ambassadorEmail }}</a>
            </td>
        </tr>
    @endif
    @if (!empty($ambassadorPhone))
        <tr>
            <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #4c5763;">Phone</td>
            <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #2d3035;">{{ $ambassadorPhone }}</td>
        </tr>
    @endif
    <tr>
        <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #4c5763;">Checked in at</td>
        <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #2d3035;">{{ $checkInTime }}</td>
    </tr>
    <tr>
        <td style="padding: 10px 14px; color: #4c5763;">Location status</td>
        <td style="padding: 10px 14px;">
            <span
                style="display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; color: {{ $statusColor }}; background: {{ $statusBg }};">
                {{ $locationStatusLabel }}
            </span>
            @if (!empty($distanceNote))
                <span style="display: block; margin-top: 6px; font-size: 13px; color: #4c5763;">{{ $distanceNote }}</span>
            @endif
        </td>
    </tr>
</table>

<table role="presentation" cellpadding="0" cellspacing="0" width="100%"
    style="border-collapse: collapse; margin-bottom: 20px; font-family: Arial, sans-serif; font-size: 14px;">
    <tr>
        <td colspan="2"
            style="background: #2e517c; color: #ffffff; padding: 10px 14px; font-weight: bold; font-size: 15px;">
            Event details
        </td>
    </tr>
    <tr>
        <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #4c5763; width: 38%;">Event ID</td>
        <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #2d3035; font-weight: 700;">
            #{{ $eventId }}
        </td>
    </tr>
    <tr>
        <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #4c5763;">Event name</td>
        <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #2d3035; font-weight: 600;">
            {{ $eventName }}
        </td>
    </tr>
    @if (!empty($eventType))
        <tr>
            <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #4c5763;">Event type</td>
            <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #2d3035;">{{ $eventType }}</td>
        </tr>
    @endif
    @if (!empty($brandName))
        <tr>
            <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #4c5763;">Brand</td>
            <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #2d3035;">{{ $brandName }}</td>
        </tr>
    @endif
    @if (!empty($eventDate))
        <tr>
            <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #4c5763;">Date</td>
            <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #2d3035;">{{ $eventDate }}</td>
        </tr>
    @endif
    @if (!empty($eventTime))
        <tr>
            <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #4c5763;">Time / duration</td>
            <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #2d3035;">{!! $eventTime !!}</td>
        </tr>
    @endif
    @if (!empty($eventLocation))
        <tr>
            <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #4c5763;">Location</td>
            <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #2d3035;">{{ $eventLocation }}</td>
        </tr>
    @endif
    @if (!empty($department))
        <tr>
            <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #4c5763;">Department</td>
            <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #2d3035;">{{ $department }}</td>
        </tr>
    @endif
    @if (!empty($distributor))
        <tr>
            <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #4c5763;">Distributor</td>
            <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #2d3035;">{{ $distributor }}</td>
        </tr>
    @endif
    @if (!empty($paymentType))
        <tr>
            <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #4c5763;">Payment type</td>
            <td style="padding: 10px 14px; border-bottom: 1px solid #eceef1; color: #2d3035;">{{ $paymentType }}</td>
        </tr>
    @endif
    @if (!empty($eventStatus))
        <tr>
            <td style="padding: 10px 14px; color: #4c5763;">Event status</td>
            <td style="padding: 10px 14px; color: #2d3035;">{{ $eventStatus }}</td>
        </tr>
    @endif
</table>

@if (!empty($eventUrl))
    <p style="margin: 0 0 8px; font-family: Arial, sans-serif; font-size: 14px;">
        <a href="{{ $eventUrl }}"
            style="display: inline-block; background: #1c4975; color: #ffffff; text-decoration: none; padding: 12px 20px; border-radius: 8px; font-weight: 600;">
            View event in portal
        </a>
    </p>
@endif

<p style="font-size: 13px; color: #4c5763; margin: 16px 0 0; line-height: 1.5;">
    This is an automated check-in alert from Aras Promotions. Please follow up with the ambassador if they checked in away
    from the event location.
</p>
