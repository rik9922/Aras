<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recap Attention Required</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f0ece1; margin: 0; padding: 0;">
    <div style="width: 100%; padding: 20px 0;">
        <div style="margin: 0 auto; max-width: 800px; background-color: #ffffff;">

            <!-- Header -->
            <div style="background: #2E517C; text-align: center; padding: 15px 0;">
                <img src="https://aras.betaplanets.com/assets/images/logo.png" alt="Aras Promotion Logo" height="70"
                    style="object-fit: contain;">
            </div>

            <!-- Body -->
            <div style="padding: 25px;">
                @php
                    $isMissingRecap = is_null($recap ?? null);
                    $managerName = $event->manager?->name ?? 'Manager';
                @endphp

                <p style="font-size: 16px; color: #333;">
                    Hello {{ $managerName }},
                </p>

                @if ($isMissingRecap)
                    <p style="font-size: 15px; color: #333;">
                        The recap for event
                        <strong>{{ $event->event_name ?? ($event->brand->brand_name ?? 'N/A') }}</strong>
                        (ID: {{ $event->id }}) has not been submitted within 48 hours of the event end time and has been
                        automatically marked as <strong>overdue</strong> with a payout of <strong>$0</strong>.
                    </p>
                    <p style="font-size: 15px; color: #333;">
                        Please review this event and, if valid transactions occurred, enter the correct payout amount
                        and submit the recap so that normal invoicing and QuickBooks sync can proceed.
                        If no transactions occurred, mark this event as a <strong>No-Show</strong> so that no payout or
                        QuickBooks record is created.
                    </p>
                @else
                    <p style="font-size: 15px; color: #333;">
                        The recap for event
                        <strong>{{ $event->event_name ?? ($event->brand->brand_name ?? 'N/A') }}</strong>
                        (ID: {{ $event->id }}) was rejected more than 24 hours ago and has not been resubmitted by the
                        Brand Ambassador. The payout has been automatically set to <strong>$0</strong>.
                    </p>
                    <p style="font-size: 15px; color: #333;">
                        Please review the recap and event details. If there are valid transactions and you wish to
                        override this, you may manually adjust the payout and proceed with normal processing.
                        If there were no valid transactions, mark the recap as a <strong>No-Show</strong>.
                    </p>
                @endif

                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ddd;">

                <!-- Event summary -->
                <h3 style="margin-bottom: 10px; color: #2E517C;">Event Summary</h3>
                <p><strong>Event ID:</strong> {{ $event->id }}</p>
                <p><strong>Event Name:</strong> {{ $event->event_name ?? ($event->brand->brand_name ?? 'N/A') }}</p>
                <p><strong>Date:</strong>
                    @if($event->booked_date)
                        {{ \Carbon\Carbon::parse($event->booked_date)->format('M d, Y') }}
                    @else
                        N/A
                    @endif
                </p>
                <p><strong>Location:</strong> {{ $event->location?->city ?? 'N/A' }}</p>

                @php
                    $reviewUrl = route('manager.show-recap', $event->recap?->id ?? $event->id);
                @endphp

                <!-- CTA -->
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{ $reviewUrl }}"
                       style="background-color: #2E517C; color: #ffffff; padding: 12px 25px;
                              text-decoration: none; border-radius: 5px; font-size: 15px;">
                        Review Recap
                    </a>
                </div>

                <p style="margin-top: 30px; font-size: 14px; color: #999;">
                    Thank you,<br>
                    Aras Promotion Team
                </p>
            </div>

            <!-- Footer -->
            <div style="text-align: center; padding: 15px; background-color: #f8f9fa; font-size: 12px; color: #777;">
                © {{ date('Y') }} Aras Promotion. All rights reserved.
            </div>

        </div>
    </div>
</body>

</html>
@php
    $isMissingRecap = is_null($recap ?? null);
@endphp

<p>Dear {{ $event->manager?->name ?? 'Manager' }},</p>

@if ($isMissingRecap)
    <p>
        The recap for event <strong>{{ $event->event_name ?? ($event->brand->brand_name ?? 'N/A') }}</strong>
        (ID: {{ $event->id }}) has not been submitted within 48 hours of the event end time and has been
        automatically marked as <strong>overdue</strong> with a payout of <strong>$0</strong>.
    </p>
    <p>
        Please review this event and, if valid transactions occurred, enter the correct payout amount and submit the
        recap so that normal invoicing and QuickBooks sync can proceed. If no transactions occurred, mark this event
        as a <strong>No-Show</strong> so that no payout or QuickBooks record is created.
    </p>
@else
    <p>
        The recap for event <strong>{{ $event->event_name ?? ($event->brand->brand_name ?? 'N/A') }}</strong>
        (ID: {{ $event->id }}) was rejected more than 24 hours ago and has not been resubmitted by the Brand
        Ambassador. The payout has been automatically set to <strong>$0</strong>.
    </p>
    <p>
        Please review the recap and event details. If there are valid transactions and you wish to override this,
        you may manually adjust the payout and proceed with normal processing.
    </p>
@endif

<p>Thank you.</p>

