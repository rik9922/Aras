<p style="margin: 0 0 20px; color: #374151;">
    We would like to invite you to join the Aras Promotions team! To get started, please log in to the portal and complete the setup steps below.
</p>

{{-- Step 1: Portal login --}}
<table role="presentation" cellpadding="0" cellspacing="0" width="100%"
    style="margin: 0 0 20px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-left: 4px solid #c4a882; border-radius: 6px;">
    <tr>
        <td style="padding: 20px 24px; font-size: 14px; line-height: 1.5; color: #4b5563;">
            <p style="margin: 0 0 12px; font-weight: 600; color: #111827;">
                1. Log in to the Aras Promotions portal
            </p>
            <p style="margin: 0 0 10px;">
                Visit
                <a href="{{ $portalUrl }}" style="color: #2c3e50; font-weight: 600; text-decoration: underline;">{{ $portalUrl }}</a>
                and sign in with your credentials:
            </p>
            <p style="margin: 0 0 6px;">
                <strong style="color: #111827;">Username:</strong>
                <a href="mailto:{{ $username }}" style="color: #2c3e50; text-decoration: underline;">{{ $username }}</a>
            </p>
            <p style="margin: 0;">
                <strong style="color: #111827;">Password:</strong>
                <span style="font-family: 'Courier New', Courier, monospace; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 4px; padding: 2px 8px; color: #111827;">{{ $password }}</span>
            </p>
        </td>
    </tr>
</table>

{{-- Step 2: Account Settings --}}
<table role="presentation" cellpadding="0" cellspacing="0" width="100%"
    style="margin: 0 0 20px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-left: 4px solid #c4a882; border-radius: 6px;">
    <tr>
        <td style="padding: 20px 24px; font-size: 14px; line-height: 1.5; color: #4b5563;">
            <p style="margin: 0 0 12px; font-weight: 600; color: #111827;">
                2. Complete your profile
            </p>
            <p style="margin: 0 0 10px;">
                Go to <strong style="color: #111827;">My Account &rarr; Account Settings</strong>
                (<a href="{{ $profileUrl }}" style="color: #2c3e50; text-decoration: underline;">open profile</a>) and complete the following:
            </p>
            <p style="margin: 0 0 6px;">&bull; Contact information: name, phone, market, and date of birth</p>
            <p style="margin: 0 0 6px;">&bull; Legal address (required for tax and payment setup)</p>
            <p style="margin: 0 0 6px;">&bull; Upload a profile picture</p>
            <p style="margin: 0;">&bull; Click <strong style="color: #111827;">Save</strong></p>
        </td>
    </tr>
</table>

{{-- Step 3: Bank Account --}}
<table role="presentation" cellpadding="0" cellspacing="0" width="100%"
    style="margin: 0 0 20px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-left: 4px solid #c4a882; border-radius: 6px;">
    <tr>
        <td style="padding: 20px 24px; font-size: 14px; line-height: 1.5; color: #4b5563;">
            <p style="margin: 0 0 12px; font-weight: 600; color: #111827;">
                3. Set up and verify your bank account
            </p>
            <p style="margin: 0 0 10px;">
                Go to <strong style="color: #111827;">My Account &rarr; Bank Account</strong>
                (<a href="{{ $bankAccountUrl }}" style="color: #2c3e50; text-decoration: underline;">open bank account setup</a>).
            </p>
            <p style="margin: 0 0 6px;">&bull; If prompted, complete <strong style="color: #111827;">Payment Account Setup</strong> with any missing identity details.</p>
            <p style="margin: 0 0 6px;">&bull; Add your bank account using your routing and account numbers.</p>
            <p style="margin: 0 0 10px;">&bull; Verify your bank account by entering the two small micro-deposit amounts sent to your account.</p>
            <p style="margin: 0 0 10px;">
                <strong style="color: #111827;">Important:</strong> event payments and reimbursement requests cannot be processed until your bank account is verified.
            </p>
            <p style="margin: 0 0 6px;"><strong style="color: #111827;">How payments work:</strong></p>
            <p style="margin: 0 0 6px;">&bull; Event payments are initiated once your recap is <strong style="color: #111827;">approved by a manager or admin</strong> &mdash; there is no fixed monthly pay date.</p>
            <p style="margin: 0 0 6px;">&bull; Standard payouts typically deposit within <strong style="color: #111827;">1&ndash;3 business days</strong> after approval.</p>
            <p style="margin: 0 0 6px;">&bull; <strong style="color: #111827;">Next-day payments</strong> are available through our payment processor.</p>
            @if (config('services.dwolla.same_day_payout_fee'))
            <p style="margin: 0;">&bull; <strong style="color: #111827;">Same-day payments</strong> are also available for an additional <strong style="color: #111827;">${{ number_format((float) config('services.dwolla.same_day_payout_fee'), 2) }}</strong> fee, which is deducted from the Brand Ambassador&rsquo;s payout.</p>
            @else
            <p style="margin: 0;">&bull; <strong style="color: #111827;">Same-day payments</strong> are also available for an additional processing fee, which is deducted from the Brand Ambassador&rsquo;s payout.</p>
            @endif
        </td>
    </tr>
</table>

{{-- Step 4: Independent Contractor form --}}
<table role="presentation" cellpadding="0" cellspacing="0" width="100%"
    style="margin: 0 0 24px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-left: 4px solid #c4a882; border-radius: 6px;">
    <tr>
        <td style="padding: 20px 24px; font-size: 14px; line-height: 1.5; color: #4b5563;">
            <p style="margin: 0 0 12px; font-weight: 600; color: #111827;">
                4. Submit your W-9 Form</p>
            <p style="margin: 0 0 10px;">
                Complete and sign the Independent Contractor paperwork
                (<a href="{{ $w9FormUrl }}" style="color: #2c3e50; text-decoration: underline;">open W-9 Form</a>).
            </p>
            <p style="margin: 0;">
                Please ensure your name and contact details match your profile before submitting.
            </p>
        </td>
    </tr>
</table>

<p style="margin: 0 0 16px; color: #374151;">
    Once you complete these steps, you will be ready to apply for events and receive payments through the portal. Your market manager may reach out with any additional onboarding materials.
</p>

<p style="margin: 0; color: #6b7280;">
    Please reach out with any questions or concerns. We look forward to working with you!
</p>
