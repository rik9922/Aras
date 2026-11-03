<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Recap Denied</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f0ece1; margin: 0; padding: 0;">
    <div style="width: 100%; padding: 20px 0;">
        <div style="margin: 0 auto; max-width: 800px; background-color: #ffffff;">

            <!-- Header -->
            <div style="background: #B02A37; text-align: center; padding: 15px 0;">
                <img src="https://aras.betaplanets.com/assets/images/logo.png" alt="Aras Promotion Logo" height="70">
            </div>

            <!-- Body -->
            <div style="padding: 25px;">
                <p style="font-size:16px;">Hello {{ $recap->ambassador->name }},</p>

                <p>
                    Your event recap has been <strong style="color:red;">denied</strong> after review.
                </p>

                <hr style="margin:20px 0;">

                <h3 style="color:#B02A37;">Reason for Denial</h3>
                <p style="background:#f8d7da; padding:12px; border-radius:5px;">
                    {{ $recap->deny_reason }}
                </p>

                <br>

                <p>
                    Please review the feedback and update/resubmit the recap if required.
                    If you have any questions, contact the admin team.
                </p>

                <div style="text-align:center; margin:30px 0;">
                    <a href="{{ url('/ambassador/view-recap/' . $recap->id) }}"
                        style="background:#B02A37; color:#fff; padding:12px 25px; text-decoration:none; border-radius:5px;">
                        View Recap
                    </a>
                </div>

                <p style="margin-top:30px; font-size:14px; color:#777;">
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
