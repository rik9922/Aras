<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $emailTitle ?? 'Aras Promotions' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #1f2937; -webkit-font-smoothing: antialiased;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                    {{-- Header --}}
                    <tr>
                        <td align="center" style="padding: 40px 40px 28px; border-bottom: 1px solid #f0f0f0;">
                            <img src="{{ $logoUrl ?? asset('assets/images/aras-mail.png') }}"
                                alt="Aras Promotions"
                                width="200"
                                style="display: block; max-width: 200px; height: auto; border: 0; margin: 0 auto;">
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 36px 40px 32px;">
                            <p style="margin: 0 0 24px; font-size: 14px; line-height: 1.5; font-weight: 600; color: #111827;">
                                Hello {{ $recipientName }}!
                            </p>

                            <div style="font-size: 14px; line-height: 1.5; color: #4b5563;">
                                {!! $bodyHtml !!}
                            </div>

                            @if (!empty($actionText) && !empty($actionUrl))
                                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin: 32px 0 8px;">
                                    <tr>
                                        <td>
                                            <a href="{{ $actionUrl }}"
                                                style="display: inline-block; background-color: #2c3e50; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 6px; font-size: 14px; font-weight: 600; letter-spacing: 0.01em; line-height: 1.5;">
                                                {{ $actionText }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <p style="margin: 32px 0 0; font-size: 14px; line-height: 1.5; color: #6b7280;">
                                Best,<br>
                                <span style="color: #374151; font-weight: 600;">{{ $signature ?? 'Aras Promotions' }}</span>
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding: 20px 40px 28px; background-color: #f9fafb; border-top: 1px solid #f0f0f0;">
                            <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #9ca3af; text-align: center;">
                                &copy; {{ date('Y') }} Aras Promotions. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
