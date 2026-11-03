## ARAS Promotion (Laravel)

Laravel 11 application for ARAS promotions, payouts, and integrations (Dwolla + QuickBooks), with webhooks and async job processing.

### Tech stack

- **Backend**: Laravel 11 (PHP **8.2+**)
- **Frontend tooling**: Vite + Tailwind + Alpine
- **Auth**: `tymon/jwt-auth` + Laravel Sanctum
- **Integrations**: Dwolla, QuickBooks, OneSignal
- **Queues**: Laravel queues (used for payouts/invoice creation)

### Prerequisites

- **PHP** 8.2+
- **Composer**
- **Node.js** + npm
- **Database** supported by Laravel (MySQL recommended)

### Local setup

Clone and install:

```bash
git clone <your-repo-url>
cd aras-promotion
composer install
npm install
```

Environment + app key:

```bash
cp .env.example .env
php artisan key:generate
```

Database:

```bash
php artisan migrate
```

JWT (if you’re using JWT auth flows):

```bash
php artisan jwt:secret
```

### Run locally

- **Backend**:

```bash
php artisan serve
```

- **Frontend assets**:

```bash
npm run build
```

### Queues (required for payouts + invoices)

Run a worker for the queues used by payouts/invoices:

```bash
php artisan queue:work --queue=payouts,invoices
```

### Webhooks

Dwolla requires a public URL (no `localhost`). Use ngrok (or similar) for local development and configure webhook URLs accordingly.

See:
- `WEBHOOK_URLS.md`
- `DWOLLA_WEBHOOK_UPDATES.md`

### QuickBooks invoice system

This project can automatically create invoices in QuickBooks and email ambassadors when payouts are approved.

See `INVOICE_SYSTEM_README.md` for:
- required queue configuration
- QuickBooks token/realm requirements
- troubleshooting tips

### Environment variables (high level)

Your `.env` will need values for:
- **Dwolla**: `DWOLLA_ENVIRONMENT`, `DWOLLA_CLIENT_ID`, `DWOLLA_CLIENT_SECRET`, `DWOLLA_WEBHOOK_SECRET`, etc. (see `WEBHOOK_URLS.md`)
- **Email**: `MAIL_*` (see `INVOICE_SYSTEM_README.md`)
- **OneSignal**: `ONE_SIGNAL_APP_ID`, `ANDROID_CHANNEL_ID`, `AUTHORIZATION_KEY_ID` (if notifications are enabled)

### Related docs in this repo

- `CLIENT_REQUIREMENTS_IMPLEMENTATION.md`
- `INVOICE_FIXES.md`
- `INVOICE_SYSTEM_README.md`
- `W9_QUICKBOOKS_VENDOR_README.md`
- `WEBHOOK_URLS.md`


