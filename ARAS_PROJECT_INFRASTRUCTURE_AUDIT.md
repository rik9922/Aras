# Aras Promotions — Project & Infrastructure Audit

**Audit date:** 2026-08-20 UTC  
**Scope:** Read-only repository, documentation, configuration-template, branch, and public HTTP inspection. No code, configuration, database, server, or deployment changes were made.

## 1. Executive Summary

Aras Promotions is a promotional-event operations platform. It manages clients, brands, markets, venues, distributors, events, managers, Brand Ambassadors, event execution, recaps, expenses, invoicing, accounting synchronization, and ambassador payouts.

The accessible source is organized as **one GitLab repository with branch-separated applications**:

- `main`: Laravel backend plus Laravel Blade web portal and Vite/Tailwind/Alpine frontend assets.
- `ion_app`: Ionic + Angular + Capacitor mobile application for iOS/Android and web/PWA output.
- `staging-main` and `staging-ion_app`: staging-oriented branches; a separate staging domain was not verified.

The production application is publicly reachable at `https://aras.betaplanets.com/`. Public response headers confirmed **LiteSpeed** and **PHP 8.2.30**. Project deployment notes identify cPanel/file-upload deployment, but no deployment automation or CI/CD configuration was found in the repository.

## 2. Project Overview

### Purpose

The system centralizes the promotional-event lifecycle:

```text
Client / Brand
    → Event planning
    → Manager and Ambassador assignment
    → Notifications and event execution
    → Check-in / check-out
    → Recap and expense submission
    → Review / approval
    → Invoicing, accounting, and Ambassador payout
    → Reporting
```

### Primary users

- **Administrators:** system, user, event, integration, payment, reporting, and settings management.
- **Managers:** market/event operations, Ambassador coordination, recap and expense review.
- **Brand Ambassadors:** assigned events, check-in/check-out, recaps, photos/documents, expenses, banking, and payment status.
- **Clients:** client/brand stakeholders associated with promotional campaigns. The exact client-facing scope is less extensive than the administrative and Ambassador workflows in the inspected source.

### Major modules observed

- Authentication, password reset, OTP, profile, and role-based access.
- Admin management for users, clients, brands, markets, venues, locations, distributors, departments, events, recaps, invoices, payments, and settings.
- Manager operations and event coordination.
- Ambassador mobile workflow, including event assignment, check-in/check-out, recap, reimbursement, banking, documents, and pay forecast.
- Client, Brand, and Location event views.
- Messaging, notifications, device registration, and push-notification support.
- Expense, reimbursement, payroll, invoice, W-9, and payout workflows.
- Dwolla, QuickBooks, OneSignal, Google services, and BEST integration code.

## 3. Repository Access and Application Mapping

### Repositories found

| Repository | Purpose | Application/platform | Technology | Status |
|---|---|---|---|---|
| `https://git.bluestoneapps.com/knoxweb-staff/aras.git` | Shared source repository for the backend/web portal and mobile app | Web portal, API, and mobile app by branch | Laravel/PHP, Blade/Vite, Ionic/Angular/Capacitor | Accessible locally; active branches present |

No additional Aras-specific repository, GitHub remote, Docker repository, CI repository, or separate backend/mobile repository was found in the available local project checkouts.

### Latest commits by repository branch

Only one Aras repository was accessible. Its latest local and corresponding remote-tracking branch commits were:

| Branch | Latest commit | Date (UTC) | Message |
|---|---|---|---|
| `main` | `232c340` | 2026-08-14 12:35:37 | Fix: require ambassador date of birth on profile update |
| `ion_app` | `8579749` | 2026-08-14 12:35:57 | Fix: require ambassador date of birth |
| `staging-main` | `06b428a` | 2026-08-05 07:19:12 | Event CSV import: copy event address/city/coordinates from matched event location |
| `staging-ion_app` | `d4991d1` | 2026-08-10 09:58:13 | Revert unsupported `platform.domReady()` usage; keep `platform.ready()` |

The local branches matched their corresponding `origin/*` tracking branches during this inspection. `origin/HEAD` points to `origin/main`.

### Branch mapping

| Branch | Application | Evidence/status |
|---|---|---|
| `main` | Laravel backend, Blade web portal, server-rendered web UI, Vite/Tailwind/Alpine assets | Confirmed from branch tree and source structure |
| `ion_app` | Ionic + Angular + Capacitor mobile application; includes iOS/Android configuration and web output | Confirmed from `package.json`, `capacitor.config.ts`, and `src/app` |
| `staging-main` | Staging-oriented backend/web branch | Branch exists; separate deployment target not verified |
| `staging-ion_app` | Staging-oriented mobile branch | Branch exists; separate deployment target not verified |

### Communication model

- The Ionic application uses the production domain as its configured base URL.
- Login/API root: `https://aras.betaplanets.com/api/`.
- Authenticated API root: `https://aras.betaplanets.com/api/auth/v1/`.
- Laravel route groups expose role-specific API prefixes for Admin, Location, Brand, Client, Ambassador, and common functions.
- Browser/web portal functionality is served by Laravel routes and Blade views on the same application/domain.

## 4. Technology Stack

### Web portal and backend

- **Backend framework:** Laravel `^11.31`.
- **Language/runtime:** PHP `^8.2`; public production headers reported PHP **8.2.30** at audit time.
- **Web UI:** Laravel Blade.
- **Asset/build tooling:** Vite `^5.2.14`, npm, PostCSS, Tailwind CSS `^3.4.3`, Alpine.js.
- **Authentication:** `tymon/jwt-auth` `^2.1` for API JWT flows; Laravel Sanctum `^4.0` is also installed/configured; web routes use Laravel session authentication and role middleware.
- **Testing:** PHPUnit configuration is present; Laravel route listing could not be executed locally because the local PHP/runtime environment did not produce usable route-list output.

### Mobile application

- **Framework:** Ionic Angular `^8.0.0` with Angular `^20.0.0`.
- **Native wrapper:** Capacitor `^7.4.4` for iOS and Android.
- **Languages:** TypeScript and HTML/CSS/SCSS.
- **Build tools:** Angular CLI `^20.0.0`, TypeScript `~5.8.0`, npm.
- **Important libraries:** RxJS, FullCalendar, dayjs, Moment, ng-select, Quill/ngx-quill, Firebase, Ionicons, jQuery, OneSignal Cordova plugin, Capacitor Preferences, Geolocation, File Viewer, and notifications-related plugins.
- **Configured app ID:** `io.araspromotions.com`.
- **Configured app name:** `Aras Promotions`.

### API and integrations

- REST-style Laravel API routes under `/api/` and `/api/auth/v1/`.
- JWT-protected role-specific API groups.
- Dwolla payout, funding-source, transfer, webhook, subscription, and operating-balance services.
- QuickBooks customer, W-9, account, invoice, token, and accounting services.
- OneSignal push-notification support.
- BEST event/liquor synchronization services.
- Google Maps/geocoding/address-related service references.
- Firebase configuration is present in the mobile environment files.

## 5. Database and Data Layer

### Confirmed from repository configuration/templates

- **Database engine:** MySQL is the documented and configured default in `.env.example` (`DB_CONNECTION=mysql`). The local template uses a nonstandard development DB port; production database values are not exposed in this report.
- **ORM/data access:** Laravel Eloquent ORM and Laravel query builder.
- **Schema:** 94 migration files were present at audit time, covering users/roles, clients, brands, events, locations, markets, venues, distributors, recaps, expenses, reimbursements, invoices, payroll, W-9 forms, Dwolla records/webhooks, QuickBooks records, notifications, and related entities.
- **Cache default:** database cache in the project template/config defaults.
- **Queue default:** database queue in the project template/config defaults. Queue jobs are used for payouts, invoices, Dwolla webhook processing, QuickBooks synchronization, payroll, and BEST synchronization.
- **Redis:** Laravel Redis configuration is present as an optional connection, but active production use was not verified.
- **File storage:** local filesystem is the configured default; S3 configuration is available as an option. Actual production storage selection was not verified.
- **Scheduler:** `routes/console.php` schedules Dwolla balance checks, Ambassador first-shift notifications, recap summaries, invoice milestone notifications, late/check-in-ready notifications, and banking setup reminders.

### Not verified

- Live database host, database name, engine version, schema state, replication, backups, and monitoring.
- Whether production uses database cache/queue or Redis.
- Whether production uses local disk or object storage for uploaded documents/media.

## 6. Backend Architecture and Access

### Backend repository

The backend is in the shared `aras` repository on `main`.

### API/authentication

- Public authentication endpoints include login, registration, password reset, OTP, and role retrieval.
- Protected API groups use JWT middleware (`jwt.verify` / `jwt.auth`) and role-specific URL prefixes.
- Web portal routes use Laravel web/session authentication and role middleware for Administrator, Manager, Client, Location, Brand, and Ambassador areas.
- A Dwolla webhook endpoint is exposed under the API routes. It is implemented in Laravel and dispatches processing jobs/services.

### Environment configuration

- Environment values are supplied through Laravel `.env` configuration.
- `.env.example` documents application URL, database, mail, queue/cache, JWT, Dwolla, QuickBooks, Google, and notification settings.
- Actual `.env` values were not included in this report. No credentials, tokens, keys, private values, or connection strings are disclosed.

### Services and workers

- Laravel queue workers are required for payout and invoice workflows according to the repository README.
- The repository documents a worker command using the `payouts` and `invoices` queues.
- Supervisor is mentioned as a possible worker manager in documentation, but no Supervisor configuration file was present in the repository.
- No PM2, systemd unit, Docker, or Jenkins deployment configuration was found.

## 7. Server and Hosting

| Area | Status | Finding |
|---|---|---|
| Production domain | **Confirmed** | `https://aras.betaplanets.com/` responds and redirects unauthenticated `/` requests to `/login`. |
| Hosting control panel | **Confirmed by project records** | cPanel access is documented for the Aras account. Credentials are intentionally omitted. |
| Web server | **Confirmed** | Public response header reports `LiteSpeed`. |
| PHP runtime | **Confirmed** | Public response header reports PHP `8.2.30`; composer requires PHP `^8.2`. |
| HTTPS/SSL | **Confirmed** | HTTPS responds successfully; HSTS header was observed. |
| Operating system | **Not verified** | No reliable OS evidence was found in the repository or public headers. |
| Nginx/Apache | **Not verified / unlikely as front server** | Public header identifies LiteSpeed; backend web-server topology was not inspected directly. |
| Node.js version on server | **Not verified** | Package/build requirements are present, but the server runtime version was not verified. |
| Supervisor/PM2/systemd | **Not verified** | Queue worker requirement is documented; process-manager configuration was not accessible in the repository. |
| CDN/object storage | **Not verified** | No CDN or active production object-storage configuration was confirmed. |

## 8. Deployment Process

### Confirmed from project records

The project’s recorded workflow is:

1. Commit backend changes to the `main` branch.
2. Commit/push mobile changes to `ion_app`; the project notes indicate synchronization to `staging-ion_app` as part of the mobile workflow.
3. Deploy the Laravel backend through cPanel/file upload rather than editing server files directly.
4. Build/install dependencies and run the required Laravel/frontend commands as appropriate for the changed application.
5. Ensure queue workers and scheduled tasks are running for payout, invoice, notification, and integration workflows.

### Repository evidence

- No `.github/workflows`, `.gitlab-ci.yml`, Dockerfile, Docker Compose file, Jenkinsfile, or checked-in deployment script was found.
- The README documents `composer install`, `npm install`, `php artisan migrate`, `npm run build`, and queue-worker usage for local/operational setup.
- Exact production deployment commands, build ordering, migration policy, cache-clearing commands, worker restart procedure, and scheduler installation were **not verified** from repository files.

### Deployment status table

| Deployment concern | Finding |
|---|---|
| Source location | GitLab repository `knoxweb-staff/aras.git` |
| Backend branch | `main` |
| Mobile branch | `ion_app` |
| Production transfer | cPanel/file-upload workflow recorded; exact mechanism not independently executed during audit |
| Dependency installation | Documented for local setup; production procedure not verified |
| Frontend build | `npm run build` exists for both Laravel assets and Angular mobile project; production execution not performed |
| Database migrations | `php artisan migrate` documented; production migration procedure not verified |
| Cache clear | Not verified |
| Queue restart | Not verified |
| Scheduler setup | Scheduled commands confirmed in source; server cron invocation not verified |
| CI/CD | No repository CI/CD configuration found |

## 9. Server Access

| Access method | Status | Details |
|---|---|---|
| cPanel | **Available according to project records** | cPanel URL/account information is maintained in project context; credential values are redacted. |
| FTP/file upload | **Available according to project records** | Project records identify cPanel/file-upload deployment. Exact current FTP endpoint/permissions were not independently tested. |
| SSH | **Not verified** | No Aras-specific SSH host/user/access procedure was found in the repository or project skill context. |
| Cloud console/VPN | **Not verified** | No evidence found. |
| CI/CD deployment credentials | **Not verified** | No CI/CD configuration found. |

## 10. Environment Overview

| Environment | Application | Backend | Database | Domain | Deployment | Status |
|---|---|---|---|---|---|---|
| Local/development | Laravel web/API and Ionic app | Local Laravel runtime | MySQL documented in template; actual local DB not inspected | Local URL not verified | Composer/npm/Artisan commands documented | Confirmed as supported setup, not executed as part of audit |
| Production | Laravel web portal/API and mobile app configured against API | Laravel 11 / PHP 8.2-compatible | MySQL is project default; live details not verified | `https://aras.betaplanets.com/` | cPanel/file-upload workflow recorded | Domain/web server/PHP confirmed; deeper server state not verified |
| Staging | Branches `staging-main` and `staging-ion_app` exist | Expected branch counterparts | Not verified | No staging domain found | Not verified | Branch existence confirmed; environment not confirmed |

## 11. Architecture Overview

```mermaid
flowchart LR
    Web[Laravel Blade Web Portal]
    Mobile[Ionic Angular Capacitor App]
    API[Laravel REST API\n/api/auth/v1/*]
    DB[(MySQL / Eloquent)]
    Queue[Laravel Queue\nDatabase default in template]
    Storage[Local filesystem default\nS3 option]
    Dwolla[Dwolla payouts and webhooks]
    QB[QuickBooks accounting and invoices]
    OneSignal[OneSignal push notifications]
    BEST[BEST event synchronization]
    Firebase[Firebase services/configuration]

    Web --> API
    Mobile --> API
    API --> DB
    API --> Queue
    API --> Storage
    Queue --> Dwolla
    Queue --> QB
    Queue --> OneSignal
    Queue --> BEST
    Mobile --> Firebase
    Dwolla --> API
}
```

In simple terms, the web portal and mobile app are two clients of the Laravel application. Laravel provides both server-rendered web pages and authenticated REST-style APIs. Eloquent persists operational and financial data in MySQL. Queue jobs handle longer-running payout, invoice, notification, webhook, and synchronization work. Uploaded documents use the application filesystem configuration. External services support payments, accounting, notifications, and business-system synchronization.

## 12. Access Summary

| Area | Access available | Details |
|---|---|---|
| Web Portal Repository | Yes | Shared GitLab repository; `main` branch contains Laravel web/backend source. |
| Mobile App Repository | Yes | Same shared repository; `ion_app` branch contains Ionic Angular Capacitor source. |
| Backend Repository | Yes | Same shared repository; Laravel backend on `main`. |
| Database | No direct live access verified | Schema/migrations and configuration template available; live credentials and live database access not verified. |
| Staging Server | No | Staging branches exist, but no staging host/domain/server access was verified. |
| Production Server | Partial | Public HTTPS domain and cPanel/file-upload access are recorded; direct OS/SSH/server-shell access was not verified. |
| Deployment System | Partial | cPanel/file-upload workflow is recorded; no automated deployment system was found in the repository. |
| CI/CD | No | No GitHub Actions, GitLab CI, Docker, Jenkins, or equivalent configuration found. |

## 13. Missing or Recommended Follow-up Information

The following items remain unverified and should be obtained from the operations owner before relying on this report for production operations:

- Exact production hosting account/server identity and operating system.
- Current production database engine version, backup/restore process, and migration status.
- Actual production queue/cache backend and worker process manager.
- Cron entry or scheduler mechanism invoking `php artisan schedule:run`.
- Exact cPanel deployment procedure, including whether the complete Laravel tree or a built artifact is uploaded.
- Production build/runtime versions for Composer, PHP extensions, Node.js, and npm.
- Staging domain, staging server, staging database, and staging deployment procedure.
- Production file/media storage location and backup policy.
- Current CI/CD status and ownership, if automation exists outside this repository.
- Mobile release pipeline for iOS and Android signing/build distribution.

## 14. Audit Evidence and Limitations

Inspected sources included:

- Local clones of the shared GitLab repository on `main` and `ion_app` branches.
- `composer.json`, `package.json`, Capacitor/Ionic configuration, Laravel route/config files, migration list, models/jobs/services, README and project documentation.
- Branch and remote metadata, without reproducing credentials.
- Public HTTP headers for `https://aras.betaplanets.com/` and its `/cpanel` path.
- Project operational notes for cPanel deployment/access, with all credential values excluded.

No deployment, login, database connection, FTP upload, SSH session, migration, queue restart, service restart, or configuration change was performed.
