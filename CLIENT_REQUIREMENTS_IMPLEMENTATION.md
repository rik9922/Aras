# Client Requirements Implementation Summary

## Client Sync Clarification + Control (QuickBooks)

### What exists today
- **Auto-sync on client creation**: when a new client (role_id = 3) is created in the portal, we dispatch `CreateQuickBooksCustomer` to create the QuickBooks Customer and store `users.quickbooks_customer_id`.
- **Auto-sync on client activity**: middleware `EnsureQuickBooksCustomer` dispatches `CreateQuickBooksCustomer` when a client logs in and they do not yet have a QuickBooks Customer ID.
- **Bulk/manual sync**: admin (and manager) can trigger a bulk sync which dispatches `SyncAllClientsWithQuickBooks` to queue `CreateQuickBooksCustomer` for each unsynced client.

### When clients sync / don’t sync
- **Will sync** when:
  - QuickBooks is connected (valid tokens/realm id)
  - Client exists in portal (role_id=3) and does not have `quickbooks_customer_id`
  - Required basic fields are present for QuickBooks Customer creation (name; optional: email/phone/address)
- **Won’t sync / may fail** when:
  - QuickBooks is not connected or token refresh fails
  - QuickBooks API returns an error (duplicates, validation errors, permissions, etc.)
  - Job queue is not running (jobs will remain queued and never execute)

### Visibility added (portal)
- Each client now tracks:
  - `qb_customer_sync_status` (queued/syncing/synced/failed)
  - `qb_customer_last_sync_attempt_at`
  - `qb_customer_synced_at`
  - `qb_customer_last_sync_error`
- The Clients list shows this status and provides a one-click retry for unsynced clients.

## 1. Dwolla Event Pay – QuickBooks Description

**Requirement:** When Dwolla transactions (event pay) sync to QuickBooks, include event ID, month and year in description/memo. Format: "January 2025 Event Pay - Event ID 12345"

**Implementation:** Updated `QuickBooksService::createExpenseForPayout()` to use `booked_date` (with fallback to `start_time`) and format as requested. The description and PrivateNote both use this format.

---

## 2. Reimbursement Module

**Requirement:** Add a "Reimbursement" tab; Brand Ambassadors and Market Managers can submit; Managers approve/reject (with rejection reason); sync to QuickBooks as "January 2025 Reimbursement - Event ID 12345" (excluded from 1099).

**Implementation:**
- **Reimbursement tab** added to header for Admin, Manager, and Ambassador
- **Submission:** BAs and Managers can submit via `/ambassador/reimbursements/create` or `/manager/reimbursements/create`
- **Form fields:** Optional event tag, date & brand, amount, reason, receipt
- **Approval flow:** Admin/Manager approve or reject (with required reason) on the reimbursement detail page
- **Dwolla payout:** Approved reimbursements are paid via Dwolla to the user’s default bank account
- **QuickBooks:** **Not synced** (per client requirement). Reimbursements remain portal-only and are paid via Dwolla; Dwolla webhooks mark them `paid` when the transfer completes.

---

## 3. Invoice vs Expense Clarification

**Requirement:** Clarify that event pay creates an **expense** in QuickBooks, not an invoice. Invoices are only for client billing when all recaps are in.

**Implementation:** The system creates QuickBooks **Expenses** (not Invoices) for ambassador event pay. Client invoicing is separate and controlled manually when all recaps are complete. Documentation updated accordingly.

---

## Invoice Timing & Notifications

**Requirement:**
- Clarify invoice creation timing (same-day or delayed)
- Add notifications:
  - Notify when all recaps are complete
  - Notify when no upcoming events remain for the month

**Implementation:**
- **Invoice timing today**: invoices are **not auto-created**. The portal provides an invoicing workflow (by customer + event month) and invoices are published/synced via QuickBooks when initiated by an Admin/Manager.
- **Notifications added**: a scheduled command evaluates each client/month and sends notifications to Admins/Managers when:
  - **All required recaps are complete (so far)** for completed events in that month (including `auto_zeroed` cases)
  - **No upcoming (non-cancelled) events remain** for that client in that month
- **Dedupe**: notifications are recorded per `client_id + month + type` to avoid re-sending every day.

**Command:**

```bash
# Run for current month
php artisan invoices:notify-month-milestones

# Dry run (no DB writes, no notifications)
php artisan invoices:notify-month-milestones --dry-run --json

# Specific month
php artisan invoices:notify-month-milestones --month=2026-04
```

---

## Cup Charges Splitting Behavior

**Requirement:**
- When multiple billing parties exist: **do not auto-split cups silently**
- **Ask user** before splitting cups
- Default behavior should be configurable

**Implementation:**
- Added **Settings → Invoicing** default: cups billed to **primary** billing party or **split evenly**.
- Updated the QuickBooks transfer flow for multi-party events so it **requires an explicit cups allocation choice** whenever cups total \(>\) 0.
- The transfer will not proceed until the user chooses **Primary** vs **Split**, preventing silent cups splitting.

---

## Cancellation Logic Improvements

**Requirement:**
- For **$15 cancellation**: ensure **hours = 0** in portal, show **flat fee only**
- Ensure **cups are removed** from cancelled events
- Maintain existing:
  - **Short notice** = full labor
  - **No-show** = no invoice

**Implementation:**
- When an ops cancellation status is set (`admin_status` starting with `cancelled_`), the system now **normalizes billables**:
  - **Always**: `cups_amount = 0` and `split_cups = false`
  - **$15 fee** (`cancelled_2_3_business_days_fee_15`): `labor_amount = 15`, `buyback_amount = 0`, `cups_amount = 0`
  - **No-fee** (`cancelled_no_fee`): `labor_amount = 0`, `buyback_amount = 0`, `cups_amount = 0`
- Portal “hours/billable hours” display shows **0** for the **$15 fee cancellation** case.
- QuickBooks transfer already prevents invoices for `no_show` and `cancelled_no_fee`, and cups are excluded for the cancellation variants.

---

## 4. W9 / Onboarding Enhancements

**Requirement:** W9 verification, business name for EIN, vendor name format "Last Name, First Name", accuracy checks.

**Implementation:**
- **Vendor name format:** QuickBooks vendor uses "Last Name, First Name" for individuals
- **Business name for EIN:** Added `business_name_ein` field when EIN is selected
- **Tax ID validation:** Enforces exactly 9 digits with clear error messages
- **Strict SSN/EIN validation (server-side):**
  - **SSN**: blocks known-invalid patterns (e.g. `000`, `666`, `9xx` prefixes, `..00..`, `....0000`)
  - **EIN**: blocks invalid `00` prefix and all-zeros
- **Verification metadata (optional external hook):** stores `verification_status`, `verification_provider`, `verified_at`, `verification_error` on `w9_forms`. External verification is disabled by default (can be enabled later via `W9_VERIFICATION_ENABLED=true`).
- **Placeholder:** Name field shows expected format to reduce typos

---

## Service / Line Item Management

**Requirement:**
- Clarify and improve creation of QuickBooks service items from portal
- Prevent duplicates
- Add visibility/logging

**Implementation:**
- **Normalization**: service names are normalized (trim + collapse whitespace) before create/update.
- **Duplicate prevention**:
  - When creating services (Admin → Services) or via invoice custom line items, the system reuses an existing `services` row if the name matches case/whitespace-insensitively.
  - QuickBooks service creation first checks for an existing QB Item by name before creating a new one.
- **Visibility**: Services list now shows the **QB Item ID** column.
- **Logging**: QuickBooks service create/reuse events are logged with service name + QB item id.

## 5. 1099 Tracking Report

**Requirement:** Report to track which vendors have been marked for 1099 in QuickBooks.

**Implementation:** Added **1099 Vendor Report** under Pay dropdown (Admin). Lists all ambassadors with QB Vendor ID and W9 status. Checkbox to mark each as "1099 tracked in QB" as you update QuickBooks.

**Route:** `/admin/vendor-1099-report`

---

## 6. QuickBooks Sync on Launch

**Requirement:** Sync existing QuickBooks vendors to the portal to avoid duplicate vendors when BAs are added.

**Implementation:** Added "Sync Existing QB Vendors to Portal" on the 1099 Vendor Report page. Matches QB vendors to portal users by DisplayName ("Lastname, Firstname") or email and sets `quickbooks_vendor_id` so new vendors are not created for existing BAs.

---

## Files Created/Modified

### New Files
- `database/migrations/2026_03_09_000001_create_reimbursements_table.php`
- `database/migrations/2026_03_09_000002_add_business_name_ein_to_w9_forms.php`
- `database/migrations/2026_03_09_000003_add_1099_tracked_to_users.php`
- `app/Models/Reimbursement.php`
- `app/Http/Controllers/Ambassador/ReimbursementController.php`
- `app/Http/Controllers/Admin/ReimbursementController.php`
- `app/Http/Controllers/Admin/Vendor1099ReportController.php`
- `app/Jobs/InitiateReimbursementPayout.php`
- `resources/views/ambassador/reimbursement/index.blade.php`
- `resources/views/ambassador/reimbursement/create.blade.php`
- `resources/views/ambassador/reimbursement/show.blade.php`
- `resources/views/admin/reimbursement/index.blade.php`
- `resources/views/admin/reimbursement/show.blade.php`
- `resources/views/admin/vendor-1099-report/index.blade.php`

### Modified Files
- `app/Services/QuickBooksService.php` – Event pay description, reimbursement expense, vendor sync, vendor name format
- `app/Services/Dwolla/DwollaTransferService.php` – `createForReimbursement()`
- `app/Http/Controllers/Ambassador/W9FormController.php` – Business name for EIN, vendor format
- `resources/views/ambassador/w9-form.blade.php` – Business name EIN field, placeholder
- `resources/views/layout/partials/header.blade.php` – Reimbursement tab, 1099 report link
- `routes/web.php` – Reimbursement and 1099 routes
- `app/Http/Controllers/Admin/QuickBooksController.php` – `syncVendors()`

---

## Run Migrations

```bash
php artisan migrate
```

## Storage Link (for reimbursement receipts)

```bash
php artisan storage:link
```
