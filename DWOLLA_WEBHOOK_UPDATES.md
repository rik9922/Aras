# Dwolla Webhook Updates

## Changes Made

### 1. DwollaWebhookController.php
- Updated webhook routing to properly distinguish between customer and funding source events
- Customer events (customer_*) now route to ProcessDwollaCustomerWebhook
- Funding source events (funding_source_*) route to ProcessDwollaFundingSourceWebhook

### 2. ProcessDwollaCustomerWebhook.php
**Complete rewrite** - Previously handled transfer events, now handles customer events:

**Supported Events:**
- `customer_created` - Updates customer status to 'created'
- `customer_verified` - Updates customer status to 'verified'
- `customer_suspended` - Updates customer status to 'suspended'
- `customer_activated` - Updates customer status to 'active'
- `customer_deactivated` - Updates customer status to 'deactivated'

**Key Features:**
- Fetches latest customer data from Dwolla API using GET /customers/{id}
- Updates `dwolla_customers` table with:
  - status
  - verification_status
  - raw_response (full customer data)
- Proper error handling and logging

### 3. ProcessDwollaFundingSourceWebhook.php
**Enhanced** to fetch real-time data from Dwolla API:

**Supported Events:**
- `funding_source_added` - Creates/updates funding source with latest data
- `funding_source_verified` - Marks funding source as verified
- `funding_source_removed` - Marks funding source as removed
- `funding_source_unverified` - Marks funding source as unverified/removed

**Key Features:**
- Fetches latest funding source data from Dwolla API using GET /funding-sources/{id}
- Updates `dwolla_funding_sources` table with:
  - bank_name
  - account_type
  - status
  - verified_at (for verified events)
  - raw_response (full funding source data)
- Proper error handling and logging

## Database Updates

### dwolla_customers table
Fields updated by webhooks:
- `status` - Customer status from Dwolla
- `verification_status` - Verification status from Dwolla
- `raw_response` - Complete customer data from Dwolla API

### dwolla_funding_sources table
Fields updated by webhooks:
- `bank_name` - Bank name from Dwolla
- `account_type` - Account type (checking/savings)
- `status` - Funding source status (verified/unverified/removed)
- `verified_at` - Timestamp when verified
- `is_default` - Set to false when removed
- `raw_response` - Complete funding source data from Dwolla API

## Webhook Events Flow

### Customer Events
1. Dwolla sends webhook → DwollaWebhookController
2. Event stored in dwolla_webhook_events table
3. ProcessDwollaCustomerWebhook job dispatched
4. Job fetches latest customer data from Dwolla API
5. Updates dwolla_customers table
6. Marks webhook event as processed

### Funding Source Events
1. Dwolla sends webhook → DwollaWebhookController
2. Event stored in dwolla_webhook_events table
3. ProcessDwollaFundingSourceWebhook job dispatched
4. Job fetches latest funding source data from Dwolla API
5. Updates dwolla_funding_sources table
6. Marks webhook event as processed

## Testing

To test the webhooks:

1. **Customer Events:**
   ```bash
   # Create a customer via your app
   # Dwolla will send customer_created webhook
   # Check dwolla_customers table for updated status
   ```

2. **Funding Source Events:**
   ```bash
   # Add a bank account via your app
   # Dwolla will send funding_source_added webhook
   # Verify micro-deposits
   # Dwolla will send funding_source_verified webhook
   # Check dwolla_funding_sources table for updated status
   ```

## Webhook URL
Ensure your Dwolla webhook subscription is set to:
```
POST https://your-domain.com/api/webhooks/dwolla
```

## Environment Variables Required
```env
DWOLLA_ENVIRONMENT=sandbox
DWOLLA_ID=your_dwolla_key
DWOLLA_SECRET=your_dwolla_secret
DWOLLA_WEBHOOK_SECRET=your_webhook_secret
```
