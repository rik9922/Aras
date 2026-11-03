<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Recap Export</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f0ece1; margin: 0; padding: 0;">
    <div style="width: 100%; padding: 20px 0;">
        <div style="margin: 0 auto; max-width: 800px; background-color: #ffffff;">

            <div style="background: #2E517C; text-align: center; padding: 15px 0;">
                <img src="https://aras.betaplanets.com/assets/images/logo.png" alt="Aras Promotion Logo" height="70">
            </div>

            <div style="padding: 25px;">
                <p style="font-size:16px;">Hello,</p>

                <p>
                    Your requested recap export is attached to this email.
                </p>

                @if ($event)
                    <hr style="margin:20px 0;">
                    <h3 style="color:#2E517C;">Event Summary</h3>
                    <p><strong>Event Name:</strong> {{ $event->event_name ?? '—' }}</p>
                    <p><strong>Event ID:</strong> {{ $event->id ?? '—' }}</p>
                    <p><strong>Date:</strong> {{ $event->FormattedDate ?? '—' }}</p>
                    <p><strong>Brand:</strong> {{ $event->brand->brand_name ?? '—' }}</p>
                @endif

                <p style="margin-top: 24px; color: #666; font-size: 14px;">
                    If you did not request this export, you can ignore this message.
                </p>
            </div>
        </div>
    </div>
</body>

</html>
