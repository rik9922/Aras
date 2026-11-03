# Dwolla Operating Balance Forecast Alert Spec

## Objective

Automatically forecast required payout cash for the next 14 days and alert admins/managers when the Dwolla operating funding source balance is below the required amount.

This replaces the current manual bi-weekly balance check with daily (or more frequent) monitoring plus push/email notifications.

## Current Architecture (Relevant)

- Dwolla transfers are created in `app/Services/Dwolla/DwollaTransferService.php` using `services.dwolla.platform_funding_source` as source.
- Payout records are persisted in `ambassador_payouts` and move through statuses (`approved`, `queued`, `processing`, `completed`, etc.).
- Payout creation starts from recap approval in `app/Http/Controllers/CommonController.php`.
- Push/email notification plumbing already exists in `app/Services/NotificationDispatchService.php`.
- Admin/Manager users exist and are already targetable for notifications via role filters.

## Proposed Design

### 1) Add Operating Balance Read API (Dwolla)

Create `app/Services/Dwolla/OperatingBalanceService.php`:

- Resolve platform funding source ID from `config('services.dwolla.platform_funding_source')`.
- Call Dwolla endpoint:
  - `GET /funding-sources/{platform_funding_source}`
  - follow `_links.balance.href` and fetch current balance.
- Return normalized structure:
  - `funding_source_id`
  - `available_balance` (decimal)
  - `currency`
  - `as_of` (timestamp)

Notes:
- Handle API failures explicitly (log + throw domain exception).
- Cache very briefly (1-5 minutes) to avoid repeated calls during one run.

### 2) Add 14-Day Forecast Service

Create `app/Services/Forecast/PayrollForecastService.php`:

- Method: `forecastForNextDays(int $days = 14): array`
- Base data source:
  - `ambassador_payouts` with statuses that represent upcoming cash need:
    - Include: `approved`, `queued`, `processing`
    - Exclude: `completed`, `failed`, `cancelled`, `job_failed`
- Date window logic:
  - Primary date: `approved_at` (fallback `created_at` when missing).
  - Filter records in `[now(), now()+14 days]`.
- Return:
  - `window_start`, `window_end`
  - `projected_payout_total`
  - `payout_count`
  - `breakdown_by_status`

Optional phase-2 additions:
- Include approved reimbursements expected in the same window.
- Include configurable risk multiplier (e.g. 1.05).

### 3) Define Bottom-Line Rule

Add config file `config/dwolla_balance_alert.php`:

- `enabled` (bool)
- `forecast_days` (default 14)
- `fixed_floor_amount` (nullable decimal)
- `buffer_amount` (default e.g. 5000.00)
- `severity_warning_gap` (e.g. 0 means any breach)
- `severity_critical_gap` (e.g. 10000)
- `cooldown_hours` (e.g. 12)
- `notify_roles` (default: Administrator + Manager)
- `notify_user_ids` (optional explicit override list)

Required minimum formula:

- `required_minimum = max(fixed_floor_amount, forecast_total + buffer_amount)`
- If `fixed_floor_amount` is null, use `forecast_total + buffer_amount`

### 4) Add Breach Evaluation Service

Create `app/Services/Dwolla/OperatingBalanceAlertService.php`:

- Pull forecast (step 2).
- Pull live Dwolla operating balance (step 1).
- Compute:
  - `shortfall = required_minimum - available_balance`
  - `is_breach = shortfall > 0`
  - `severity = warning|critical` based on configured gaps.
- Persist each run to a snapshot table (step 5).
- Trigger notifications if:
  - breach is true, and
  - not in cooldown window for same severity.

### 5) Persist Forecast/Alert Snapshots

Create migration for `dwolla_balance_alert_snapshots`:

- `id`
- `window_start` datetime
- `window_end` datetime
- `forecast_total` decimal(12,2)
- `buffer_amount` decimal(12,2)
- `fixed_floor_amount` decimal(12,2) nullable
- `required_minimum` decimal(12,2)
- `operating_balance` decimal(12,2)
- `shortfall` decimal(12,2)
- `currency` string(10)
- `is_breach` boolean
- `severity` string(20) nullable
- `notified_at` datetime nullable
- `meta` json nullable
- timestamps

Purpose:
- audit trail
- trend analysis
- suppress duplicate notifications during cooldown

### 6) Notification Strategy (Push + Email)

Use `NotificationDispatchService` for each target user:

- `type`: `dwolla_balance_alert`
- `title`: `Dwolla Operating Balance Low`
- `message` sample:
  - `Operating balance $X is below required 14-day forecast minimum $Y (shortfall: $Z).`

Email HTML (optional but recommended):
- Include current balance, forecast total, buffer, required minimum, shortfall, run timestamp.

Recipients:
- all users matching configured roles (admin/manager), optionally merged with configured `notify_user_ids`.

### 7) Command + Scheduler

Create command:
- `app/Console/Commands/CheckDwollaOperatingBalance.php`
- Signature: `dwolla:check-operating-balance {--days=14} {--force-notify}`

Behavior:
- Run evaluator service.
- Print summary metrics to console.
- Exit non-zero only on technical failures (not on business breach).

Register scheduler in `routes/console.php`:
- `Schedule::command('dwolla:check-operating-balance')->dailyAt('08:00');`
- Optional second run in afternoon for tighter monitoring.

### 8) Optional Admin Visibility

Phase-2 UI:
- Add admin page showing latest snapshots and breach history.
- Add quick status badge in existing admin payment forecast page.

## Rollout Plan

### Phase 1 (Low-risk, recommended first)

- Implement services + snapshot table + command.
- Run scheduled checks.
- Log only (no notifications) for 3-5 business days to validate forecast quality.

### Phase 2

- Enable push/email notifications with cooldown.
- Validate recipient coverage and OneSignal delivery.

### Phase 3

- Add dashboard/reporting and configurable threshold settings in admin UI.

## Edge Cases & Guardrails

- Dwolla API unavailable:
  - store snapshot with error metadata
  - notify only technical owner channel if desired
- No upcoming payouts:
  - required minimum still respects fixed floor and/or buffer
- Currency mismatch:
  - enforce USD for now, fail fast otherwise
- Duplicate notifications:
  - use cooldown + last notified severity from snapshots

## Testing Plan

### Unit tests

- `PayrollForecastService`:
  - includes/excludes statuses correctly
  - computes 14-day window totals correctly
- `OperatingBalanceAlertService`:
  - required minimum formula
  - severity classification
  - cooldown suppression

### Integration tests

- Fake DwollaClient responses for balance fetch.
- Seed payouts and verify snapshot persistence.
- Verify `NotificationDispatchService::dispatch()` calls for expected recipients.

### Manual verification checklist

- Run command with seeded near-term payouts.
- Confirm snapshot row has correct totals.
- Simulate low balance and verify push/email goes to admin/manager users with opt-in preferences.

## Suggested Defaults (Initial)

- `forecast_days = 14`
- `buffer_amount = 5000.00`
- `fixed_floor_amount = null` (start dynamic)
- `cooldown_hours = 12`
- warning on any breach, critical at shortfall >= 10000

## Future Enhancements

- Include event-level expected payout date instead of approval date once available.
- Add weekday-aware cashflow projection (daily runout chart).
- Add Slack/Microsoft Teams integration for finance alerts.
- Add auto-created task/ticket when in critical state for >24h.
