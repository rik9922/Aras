# Webhook URLs for ARAS Promotion Application

This document lists all webhook endpoints that should be configured in your application and with external services (like Dwolla).

## Base URL Configuration

- **Production:** `https://aras.betaplanets.com`
- **Local Development:** Use ngrok or similar tunnel service (e.g., `https://abc123.ngrok.io`)

**Note:** Dwolla does NOT accept `localhost` URLs. You must use a publicly accessible URL.

---

## 1. Dwolla Transfer Webhook (Primary)

**Purpose:** Receives transfer status updates from Dwolla (transfer_completed, transfer_failed, transfer_cancelled)

**Endpoint:** `POST /api/webhooks/dwolla`

**Full URLs:**
- **Production:** `https://aras.betaplanets.com/api/webhooks/dwolla`
- **Local Dev (ngrok):** `https://your-ngrok-url.ngrok.io/api/webhooks/dwolla`

**Controller:** `App\Http\Controllers\Webhooks\DwollaWebhookController@handle`

**Events Handled:**
- `transfer_completed` - Marks payout as completed
- `transfer_failed` - Marks payout as failed with reason
- `transfer_cancelled` - Marks payout as cancelled

**Security:** Validates `X-Dwolla-Signature` header using `DWOLLA_WEBHOOK_SECRET`

**Where to Configure:**
- **Dwolla Dashboard:** Create webhook subscription pointing to this URL
- **Admin Panel:** Go to **Pay → Webhooks** to manage subscriptions

---

## 2. Dwolla Bank Verification Webhook

**Purpose:** Receives bank account verification status updates from Dwolla

**Endpoint:** `POST /api/webhooks/bank-verification-status`

**Full URLs:**
- **Production:** `https://aras.betaplanets.com/api/webhooks/bank-verification-status`
- **Local Dev (ngrok):** `https://your-ngrok-url.ngrok.io/api/webhooks/bank-verification-status`

**Controller:** `App\Http\Controllers\Webhooks\DwollaAmbassadorBankVerificationWebhookController@handle`

**Events Handled:**
- `customer_funding_source_verified` - Marks bank account as verified, sets as default if none exists
- `customer_funding_source_verification_failed` - Marks bank account verification as failed

**Security:** Currently no signature validation (consider adding)

**Where to Configure:**
- **Dwolla Dashboard:** Create separate webhook subscription for bank verification events
- **Admin Panel:** Go to **Pay → Webhooks** to manage subscriptions

---

## Summary Table

| Webhook Name | Endpoint | Purpose | Required Secret |
|--------------|----------|---------|-----------------|
| Dwolla Transfer | `/api/webhooks/dwolla` | Transfer status updates | Yes (`DWOLLA_WEBHOOK_SECRET`) |
| Dwolla Bank Verification | `/api/webhooks/bank-verification-status` | Bank account verification | No (should add) |

---

## How to Set Up Webhooks

### Option 1: Via Admin Panel (Recommended)
1. Log in as Administrator
2. Navigate to **Pay → Webhooks**
3. Click **Add Webhook Subscription**
4. Enter the full URL (e.g., `https://aras.betaplanets.com/api/webhooks/dwolla`)
5. Secret is auto-filled from `DWOLLA_WEBHOOK_SECRET` in `.env`
6. Click **Create Subscription**

### Option 2: Via Dwolla Dashboard
1. Log in to Dwolla Dashboard
2. Go to **Webhooks** section
3. Create new subscription
4. Enter the webhook URL
5. Enter the secret (from `DWOLLA_WEBHOOK_SECRET`)
6. Save subscription

---

## Environment Variables Required

```env
# Base application URL
APP_URL=https://aras.betaplanets.com

# Dwolla Configuration
DWOLLA_ENVIRONMENT=sandbox  # or 'production'
DWOLLA_CLIENT_ID=your_client_id
DWOLLA_CLIENT_SECRET=your_client_secret
DWOLLA_WEBHOOK_SECRET=your_webhook_secret  # Used for signature validation
DWOLLA_PLATFORM_FUNDING_SOURCE=your_platform_funding_source_id
```

---

## Local Development Setup

Since Dwolla doesn't accept localhost URLs, use ngrok:

1. **Install ngrok:** https://ngrok.com/download
2. **Start your Laravel app:** `php artisan serve` (runs on port 8000)
3. **Start ngrok:** `ngrok http 8000`
4. **Copy the ngrok URL** (e.g., `https://abc123.ngrok.io`)
5. **Use in webhook subscription:** `https://abc123.ngrok.io/api/webhooks/dwolla`

**Note:** Free ngrok URLs change on restart. For development, consider:
- Using ngrok's paid plan for static URLs
- Or manually updating webhook URLs each time you restart ngrok

---

## Testing Webhooks

### Test Transfer Webhook:
```bash
curl -X POST https://aras.betaplanets.com/api/webhooks/dwolla \
  -H "Content-Type: application/json" \
  -H "X-Dwolla-Signature: your_signature" \
  -d '{
    "id": "test-event-id",
    "topic": "transfer_completed",
    "_links": {
      "resource": {
        "href": "https://api-sandbox.dwolla.com/transfers/abc123"
      }
    }
  }'
```

### Test Bank Verification Webhook:
```bash
curl -X POST https://aras.betaplanets.com/api/webhooks/bank-verification-status \
  -H "Content-Type: application/json" \
  -d '{
    "topic": "customer_funding_source_verified",
    "_links": {
      "resource": {
        "href": "https://api-sandbox.dwolla.com/funding-sources/abc123"
      }
    }
  }'
```

---

## Important Notes

1. **Both webhooks should be configured** in Dwolla for complete functionality
2. **Transfer webhook** is critical for payout status updates
3. **Bank verification webhook** ensures bank accounts are marked as verified automatically
4. **Signature validation** is implemented for transfer webhook but not for bank verification (consider adding)
5. **Idempotency** is handled - duplicate events are ignored based on event ID
6. **Webhook events are logged** in `dwolla_webhook_events` table for debugging

---

## Troubleshooting

- **403 Forbidden:** Check `X-Dwolla-Signature` header and `DWOLLA_WEBHOOK_SECRET` match
- **404 Not Found:** Verify the URL is correct and routes are registered
- **Localhost rejected:** Use ngrok or deploy to staging/production
- **Events not processing:** Check `dwolla_webhook_events` table for stored events and errors
