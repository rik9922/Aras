# Ambassador Payout Invoice System

## Overview
This system automatically creates invoices in QuickBooks and sends them via email to ambassadors when their payouts are approved.

## Features
- Automatic invoice creation in QuickBooks after payout approval
- Email notification to ambassadors with invoice details
- Invoice tracking in local database
- Automatic QuickBooks customer creation for ambassadors

## Setup Instructions

### 1. Run Migration
```bash
php artisan migrate
```

This will add the `invoice_id` column to the `ambassador_payouts` table.

### 2. Configure Queue Workers
Make sure your queue workers are running to process invoice creation jobs:

```bash
php artisan queue:work --queue=payouts,invoices
```

Or add to your supervisor configuration:
```ini
[program:aras-queue-worker]
command=php /path/to/artisan queue:work --queue=payouts,invoices --sleep=3 --tries=3
```

### 3. QuickBooks Configuration
Ensure QuickBooks is properly connected:
- Access token and refresh token are set
- Realm ID is configured
- Service item exists in QuickBooks (default item ID: 1)

### 4. Email Configuration
Verify SMTP settings in `.env`:
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.postmarkapp.com
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS="no-reply@knoxweb.com"
```

## How It Works

### Flow:
1. Admin approves recap → `approveRecap()` in CommonController
2. `AmbassadorPayout` record created with status 'approved'
3. `RecapApprovedForPayout` event fired
4. `QueueAmbassadorPayout` listener queues the payout
5. `InitiateAmbassadorPayout` job processes Dwolla transfer
6. `CreateInvoiceForPayout` job is dispatched
7. Invoice created in QuickBooks
8. Invoice saved to local database
9. Email sent to ambassador with invoice details

### Database Schema:
- `ambassador_payouts.invoice_id` → Links to `invoices.id`
- `invoices` table stores QuickBooks invoice data locally

### Jobs:
- `InitiateAmbassadorPayout` - Handles Dwolla payout (queue: payouts)
- `CreateInvoiceForPayout` - Creates invoice and sends email (queue: invoices)

### Mail:
- `AmbassadorInvoiceMail` - Email template for invoice notification

## Testing

### Test Invoice Creation:
```php
// In tinker or test
$payout = AmbassadorPayout::find(1);
\App\Jobs\CreateInvoiceForPayout::dispatch($payout);
```

### Check Queue Status:
```bash
php artisan queue:failed
php artisan queue:retry all
```

## Troubleshooting

### Invoice Not Created:
- Check QuickBooks connection status
- Verify ambassador has email address
- Check queue worker is running
- Review logs: `storage/logs/laravel.log`

### Email Not Sent:
- Verify SMTP configuration
- Check ambassador email is valid
- Review mail logs

### QuickBooks Errors:
- Token expired: Will auto-refresh
- Customer not found: Will auto-create
- Item not found: Update item ID in `createInvoiceForPayout()`

## API Endpoints
No new endpoints required - system works automatically on recap approval.

## Notes
- Invoices are created asynchronously via queue
- Failed jobs are logged and can be retried
- QuickBooks token auto-refreshes when expired
- Invoice creation is idempotent (won't create duplicates)
