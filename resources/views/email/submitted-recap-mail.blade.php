<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Recap Submitted</title>
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

                <!-- Dynamic Greeting -->
                <p style="font-size: 16px; color: #333;">
                    Hello {{ $receiver->name }},
                </p>

                <p style="font-size: 15px; color: #333;">
                    A recap has been submitted and is awaiting your review.
                </p>

                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ddd;">

                <!-- Submitted By -->
                <h3 style="margin-bottom: 10px; color: #2E517C;">Submitted By</h3>
                <p><strong>Name:</strong> {{ $submittedUser->name }}</p>
                <p><strong>Email:</strong> {{ $submittedUser->email }}</p>

                <br>

                <!-- Ambassador Details -->
                <h3 style="margin-bottom: 10px; color: #2E517C;">Ambassador Details</h3>
                <p><strong>Name:</strong> {{ $recap->ambassador->name }}</p>
                <p><strong>Email:</strong> {{ $recap->ambassador->email }}</p>
                <p><strong>Phone:</strong> {{ $recap->ambassador->phone ?? 'N/A' }}</p>

                <br>

                <!-- Event Details -->
                <h3 style="margin-bottom: 10px; color: #2E517C;">Event Details</h3>
                <p><strong>Event ID:</strong> {{ $recap->event->id }}</p>
                <p><strong>Event Name:</strong> {{ $recap->event->event_name }}</p>
                <p><strong>Event Date:</strong> {{ \Carbon\Carbon::parse($recap->event->date)->format('M d, Y') }}</p>
                <p><strong>Submitted On:</strong> {{ $recap->created_at->format('M d, Y h:i A') }}</p>

                <br>

                <p style="font-size: 15px; color: #333;">
                    Please review the recap and either approve or deny it.
                </p>

                @php
                    $reviewUrl =
                        $receiver->role_id == 1
                            ? url('/admin/show-recap/' . $recap->id)
                            : url('/manager/show-recap/' . $recap->id);
                @endphp

                <!-- CTA Button -->
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{ $reviewUrl }}"
                        style="background-color: #2E517C; color: #ffffff; padding: 12px 25px;
                              text-decoration: none; border-radius: 5px; font-size: 15px;">
                        Review Recap
                    </a>
                </div>

                <p style="font-size: 14px; color: #555;">
                    Once approved, payment will be processed via Dwolla and recorded in QuickBooks automatically.
                </p>

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
