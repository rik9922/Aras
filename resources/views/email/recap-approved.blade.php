<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Recap Approved</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f0ece1; margin: 0; padding: 0;">
    <div style="width: 100%; padding: 20px 0;">
        <div style="margin: 0 auto; max-width: 800px; background-color: #ffffff;">

            <!-- Header -->
            <div style="background: #2E517C; text-align: center; padding: 15px 0;">
                <img src="https://aras.betaplanets.com/assets/images/logo.png" alt="Aras Promotion Logo" height="70">
            </div>

            <!-- Body -->
            <div style="padding: 25px;">
                <p style="font-size:16px;">Hello {{ $recap->ambassador->name }},</p>

                <p>
                    Great news! Your event recap has been <strong style="color:green;">approved</strong>.
                </p>

                <hr style="margin:20px 0;">

                <h3 style="color:#2E517C;">Event Details</h3>
                <p><strong>Event Name:</strong> {{ $recap->event->name }}</p>
                <p><strong>Event Date:</strong> {{ \Carbon\Carbon::parse($recap->event->date)->format('M d, Y') }}</p>
                <p><strong>Approved On:</strong> {{ now()->format('M d, Y h:i A') }}</p>

                <br>

                <p>
                    Good news! Your payment is now on its way. The funds will be sent to you shortly.
                </p>

                <div style="text-align:center; margin:30px 0;">
                    <a href="{{ url('ambassador/view-recap/' . $recap->id) }}"
                        style="background:#2E517C; color:#fff; padding:12px 25px; text-decoration:none; border-radius:5px;">
                        View Recap
                    </a>
                </div>

                <p style="margin-top:30px; font-size:14px; color:#777;">
                    Thank you for your great work! <br>
                    Aras Promotion Team
                </p>
            </div>

            <div style="text-align:center; padding:15px; background:#f8f9fa; font-size:12px; color:#777;">
                © {{ date('Y') }} Aras Promotion. All rights reserved.
            </div>

        </div>
    </div>
</body>

</html>
