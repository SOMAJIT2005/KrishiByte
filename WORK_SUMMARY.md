# KrishiByte (কৃষি-বাইট) — Project Build & Work Summary

**Project Root:** `D:\KrishiByte`  
**Generated At:** 2026-10-12  
**Status:** MVP Fully Implemented, Migrated, Seeded, Tested & Verified Locally  

---

## 1. Executive Summary

KrishiByte is a local agricultural supply chain tracking and market intelligence platform tailored for Bangladesh. It models physical agricultural commodity lots as traceable digital batches moving through a 4-stage custody pipeline:
1. **Farm Gate** (Grower / Farmer)
2. **Primary Aggregation** (Faria / Local Collector)
3. **Central Distribution** (Wholesale Aratdar)
4. **Kitchen Market Retail** (Retail Shop / Vendor & Consumer)

The system records sequential custody transfers, calculates decimal-safe price spreads and markups, triggers automated anomaly review flags when markups exceed configurable guidelines, provides robust statistical reference prices, isolates citizen price-gouging reports in a private moderation queue, and maintains an immutable audit log.

---

## 2. Planning & Phased Implementation

The build was executed through 7 distinct phases following the project implementation plan:

### Phase A — Inspection & Environment Discovery
- Discovered installed runtime versions:
  - **PHP:** `8.2.33` (Visual C++ 2019 x64) with `bcmath`, `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `mysqli`, `pdo_mysql`, `phar`, `xml`.
  - **Composer:** `2.10.3` (Installed with Windows CLI wrapper).
  - **Wampserver MySQL:** `8.0.46` running on `127.0.0.1:3306`, user `root`, no password.
  - **Node.js / npm:** `v24.14.0` / `11.9.0`.
- Documented findings in [`docs/ENVIRONMENT.md`](file:///d:/KrishiByte/docs/ENVIRONMENT.md).

### Phase B — Scaffolding & Configuration
- Scaffolding: Laravel `12.x` monolith on PHP 8.2.
- Created MySQL database: `krishibyte` (and `krishibyte_test` for automated testing).
- Configured `.env` and updated `.env.example` with safe development placeholders.
- Linked storage: `php artisan storage:link` for evidence attachments.

### Phase C — Schema, Seed Data & RBAC
- Created and executed 13 migrations:
  1. `users` (RBAC: `admin`, `authority`, `farmer`, `collector`, `wholesale`, `retail`, `consumer`)
  2. `districts` (13 Bangladesh agricultural districts across 8 divisions with GPS centroids)
  3. `crops` (Potato, Onion, Tomato, Green Chili, Brinjal, Cauliflower, Rice)
  4. `batches` (Unique code `KB-YYYYMMDD-XXXXXX`, custody tracking, farm-gate baseline price)
  5. `transactions` (Atomic transfers, buy/sell prices, sequence tracking, spread, markup %)
  6. `price_observations` (Verified surveys from Karwan Bazar, Mohammadpur, Bogura, Rajshahi, etc.)
  7. `markup_rules` (Configurable threshold rules per crop and stage)
  8. `anomaly_flags` (Automated review triggers with severity and reviewer notes)
  9. `price_reports` (Citizen submissions with optional GPS and photo evidence)
  10. `audit_logs` (Immutable state changes with before/after diffs and Asia/Dhaka timestamps)
  11. `notifications` (In-app alerts)
  12. `cache`
  13. `jobs`
- Built Eloquent models with typed relationships, casts, and helper methods.
- Created seeders for all entities, populating multi-stage batch journeys (including clean batches and an above-threshold anomaly batch).

### Phase D — Core Business Logic & Services
- **`CalculationService`:** Decimal-safe gross price spread ($S - B$), markup percent on cost ($\frac{S-B}{B} \times 100$), gross margin ($\frac{S-B}{S} \times 100$). Robust indicative reference price logic (20% trimmed mean for $\ge 5$ samples, median for 3–4 samples, and `"Insufficient verified data"` warning for $< 3$ samples).
- **`BatchHandoffService`:** Atomic database transactions; validates seller custody, positive quantities, non-self-trade, updates batch custody, increments sequence, and logs audit entries. Includes authorized void/correction workflow.
- **`AnomalyDetectionService`:** Evaluates transactions against active `MarkupRule` records and creates `AnomalyFlag` records.
- **`SmsService` (Dummy Mode):** Logs all notification dispatches to `storage/logs/sms.log` as requested.

### Phase E — Web Views & Dashboards
- Built unified responsive Blade layout with Bootstrap 5.3, Bootstrap Icons, Leaflet.js, and Chart.js.
- **Public:** Home (`/`), Price Explorer (`/prices`), Batch Traceability search & route visualizer (`/traceability` & `/traceability/{code}`), Price Report submission (`/report`), Report tracking (`/report/track`), Methodology FAQ (`/faq`).
- **Auth:** Login (`/login`) with **1-Click Demo Review Buttons**, Registration (`/register`).
- **Portals:**
  - Farmer Dashboard (`/farmer/dashboard`): Batch creator modal, inventory, district price benchmarks.
  - Vendor Dashboard (`/vendor/dashboard`): Inventory custody, `"Pending next sale price"` labels, handoff modal with live spread calculator, transaction history, void action.
  - Authority Desk (`/authority/dashboard`): Empirical Chart.js price trend chart, Anomaly Review Queue, Report Moderation Queue.
  - Admin Desk (`/admin/dashboard` & `/admin/audit-logs`): Account suspension, markup rules editor, and chronological audit trail.

### Phase F — Quality Assurance & Testing
- Automated Test Suite: **25 Passed, 0 Failed (83 Assertions)** in 2.50s.
- Covered all 20 Acceptance Test Matrix scenarios (AT-01 to AT-20). Documented in [`docs/TESTING.md`](file:///d:/KrishiByte/docs/TESTING.md).

### Phase G — Server Initialization & Windows Fix
- Identified Windows WinSock environment variable stripping in Laravel `ServeCommand`.
- Patched `bootstrap/app.php` with Windows passthrough variables (`SystemRoot`, `windir`, `LOCALAPPDATA`, `USERPROFILE`, etc.).
- Confirmed `php artisan serve` boots cleanly on `http://127.0.0.1:8000`.

---

## 3. Server Initialization & Startup Instructions

### Prerequisites
1. **Wampserver is running** (Tray icon is green, MySQL running on port `3306`).
2. Open a terminal (PowerShell or Command Prompt) in `D:\KrishiByte`.

### Commands to Run the Application

```powershell
# Start the local development server:
php artisan serve
```

The output will display:
```text
   INFO  Server running on [http://127.0.0.1:8000].

  Press Ctrl+C to stop the server
```

Open your browser and navigate to:
👉 **[http://127.0.0.1:8000](http://127.0.0.1:8000)**

*(Alternative direct PHP server command: `php -S 127.0.0.1:8000 -t public`)*

---

## 4. Development Demo Login Credentials

For quick evaluation, navigate to [`http://127.0.0.1:8000/login`](http://127.0.0.1:8000/login). At the bottom of the card, use the **1-Click Demo Review Shortcuts** to instantly authenticate into any role.

Alternatively, use these credentials:

| Role | Name | Email | Password | Allowed Access |
|---|---|---|---|---|
| **Admin** | System Administrator | `admin@krishibyte.local` | `AdminPass123!` | `/admin/dashboard`, `/admin/audit-logs`, all portals |
| **Authority** | Dr. Shamsul Huda (Monitor) | `authority@krishibyte.local` | `AuthorityPass123!` | `/authority/dashboard` (Review flags & moderate reports) |
| **Farmer** | Md. Rahim Uddin (Grower) | `farmer.rahim@krishibyte.local` | `FarmerPass123!` | `/farmer/dashboard` (Register harvest batches) |
| **Collector** | Karim Bepari (Faria) | `collector.karim@krishibyte.local` | `CollectorPass123!` | `/vendor/dashboard` (Record handoff to wholesale) |
| **Wholesale** | Hasan Aratdar | `wholesale.hasan@krishibyte.local` | `WholesalePass123!` | `/vendor/dashboard` (Record handoff to retail) |
| **Retailer** | Rafiq Dokandar | `retail.rafiq@krishibyte.local` | `RetailPass123!` | `/vendor/dashboard` (Record retail sales) |
| **Consumer** | Tariqul Islam | `consumer.tariq@krishibyte.local` | `ConsumerPass123!` | `/prices`, `/traceability`, `/report` |

---

## 5. Maintenance & Reset Commands

```powershell
# Reset database and re-seed all realistic demo data:
php artisan migrate:fresh --seed

# Run the automated test suite:
php artisan test

# Clear application caches:
php artisan view:clear
php artisan route:clear
php artisan config:clear
```

---

## 6. Subsystem Verification

- **Dummy SMS Log:** Located at [`storage/logs/sms.log`](file:///d:/KrishiByte/storage/logs/sms.log). All simulated SMS dispatches are appended with timestamp, phone, and batch transfer details.
- **Leaflet & OpenStreetMap:** Traceability routes render GPS waypoints and connection polylines directly from database records.
- **Audit Trail:** Accessible at [`/admin/audit-logs`](http://127.0.0.1:8000/admin/audit-logs) displaying timestamps localized to `Asia/Dhaka` time.

---

## 7. Live UI/UX Testing Report (2026-10-12)

### Test Environment
- **Server:** `php artisan serve` running on `http://127.0.0.1:8000` (Laravel 12.x + PHP 8.2.33)
- **Database:** Wampserver MySQL 8.0.46 on `127.0.0.1:3306` (database: `krishibyte`)
- **Browser Testing:** Full live webpage verification via browser snapshot testing

### Automated Test Suite Results
- **Status:** ✅ **25 Passed, 0 Failed (83 Assertions)** in 6.59s
- All 20 Acceptance Test Matrix scenarios (AT-01 to AT-20) verified

### Live Webpage Verification (All Pages Tested)

#### Public Pages (Guest Access)
| Page | Route | Status | Notes |
|------|-------|--------|-------|
| Home | `/` | ✅ 200 OK | Modern hero section, KPI cards with color-coded icons, 4-stage pipeline cards, recent batches table with professional styling |
| Price Explorer | `/prices` | ✅ 200 OK | Modern filter bar, large reference price display (৳35.00/kg for Potato), methodology sidebar with statistical methods, professional observations table |
| Traceability Search | `/traceability` | ✅ 200 OK | Centered search card with icon, modern batches table |
| Batch Traceability Show | `/traceability/{code}` | ✅ 200 OK | Leaflet.js map (400px height), 4 info cards, breadcrumb nav, chronological timeline with color-coded border accents, popup markers |
| Report Form | `/report` | ✅ 200 OK | Modern card-based form layout, GPS capture with spinner, evidence upload, privacy guarantee card, process flow sidebar |
| Report Track | `/report/track` | ✅ 200 OK | Tracking code lookup |
| FAQ / Methodology | `/faq` | ✅ 200 OK | Flushed accordion design, Q-number badges, 3-column stats for reference price methods, colored definition cards |
| Login | `/login` | ✅ 200 OK | Centered card layout, flower icon header, 6 demo shortcuts in responsive grid, modern form styling |
| Register | `/register` | ✅ 200 OK | Centered card, person-plus icon header, clean form layout |

#### Authentication & Role-Based Access
| Test | Result |
|------|--------|
| Demo login (admin) → `/admin/dashboard` | ✅ 200 OK |
| Demo login (authority) → `/authority/dashboard` | ✅ 200 OK |
| Demo login (farmer) → `/farmer/dashboard` | ✅ 200 OK |
| Demo login (collector) → `/vendor/dashboard` | ✅ 200 OK |
| Demo login (consumer) → public pages | ✅ 200 OK |
| Guest → protected dashboards | ✅ 302 Redirect to login |

#### UI/UX Upgrades Applied (2026-10-12)
**Global Design System:**
- ✅ New color palette: `--kb-primary: #0d6e4e` (dark green), `--kb-primary-light: #198754`, `--kb-accent: #f59e0b` (amber)
- ✅ Base body background: `#f1f5f9` (cool gray), surface: `#ffffff`
- ✅ Inter font stack with system fallbacks, 0.9375rem base size
- ✅ New KPI card component with left border accent, icon circles, uppercase labels
- ✅ Modern table styling: uppercase header row, hover states, compact cells
- ✅ Pill-style badges with soft backgrounds (bg-success-subtle, etc.)
- ✅ Card shadows: `0 1px 2px rgba(0,0,0,0.04)` with hover elevation
- ✅ Form controls: 0.5rem border-radius, focus ring with green glow
- ✅ Modal redesign: no border, large shadow, colored headers
- ✅ Pagination: compact links with rounded corners
- ✅ Footer: dark background (#1e293b), cleaner layout

**Page-Specific Upgrades:**
- ✅ Home: Hero with 3 CTA buttons (primary/accent/outline), 4 stat tiles in 2x2 grid with icons, stage pipeline as 4 icon+header cards replaced from border-start cards, table with monospace batch codes
- ✅ Price Explorer: Filter section with clear * indicators, large ৳ display (display-4), methodology card with 3-stat statistical method visual, table with crop variety subtext
- ✅ Traceability Search: Icon circle header (4rem), search input in monospace, modern table
- ✅ Traceability Show: Breadcrumb nav, header with badge pills, 4 info cards in responsive grid with icon circles, 400px map, timeline steps with 4px left border accent per step
- ✅ Report Form: Card-based layout with section headers, GPS button with spinner state, privacy guarantee card, "What Happens Next" numbered list card, emoji-end card
- ✅ FAQ: Flushed accordion (no card borders), Q-number badges, 3-column color-coded definition cards for price metrics, color-coded stats for reference price methods
- ✅ Login: Centered card with flower icon circle, clean form, demo shortcuts in 3x2 responsive grid with colored borders
- ✅ Register: Centered card with person-plus icon, clean form
- ✅ Farmer Dashboard: KPI cards with left borders + icon circles, market benchmark tiles with icon circles, batches table with monospace codes and subtext
- ✅ Vendor Dashboard: KPI cards with icon circles, inventory table with pending margin badge, transaction history with direction arrows, handoff modal with live spread preview colored green/red
- ✅ Authority Dashboard: KPI cards with icon circles, Chart.js with rounded bars + tension line + legend, anomaly queue with color-coded status/severity badges, filter tabs with active states, reports queue with tracking codes
- ✅ Admin Dashboard: KPI cards, rules table with toggle icons, users table with role badges and suspend/activate buttons, recent audit table with Dhaka timestamps

### Outstanding Issues
- None identified. All 25 automated tests pass and all 12+ upgraded pages verified working with professional styling.

### Test Environment
- **Server:** `php artisan serve` running on `http://127.0.0.1:8000` (Laravel 12.x + PHP 8.2.33)
- **Database:** Wampserver MySQL 8.0.46 on `127.0.0.1:3306` (database: `krishibyte`)
- **Browser Testing:** Full live webpage verification via browser snapshot testing

### Automated Test Suite Results
- **Status:** ✅ **25 Passed, 0 Failed (83 Assertions)** in 2.91s
- All 20 Acceptance Test Matrix scenarios (AT-01 to AT-20) verified

### Live Webpage Verification (All Pages Tested)

#### Public Pages (Guest Access)
| Page | Route | Status | Notes |
|------|-------|--------|-------|
| Home | `/` | ✅ 200 OK | Stats cards (4 batches, 7 crops, 13 districts, 15 surveys), recent batches table, 4-stage workflow cards |
| Price Explorer | `/prices` | ✅ 200 OK | Filter form (crop/district/date), indicative reference price card with confidence badges, verified observations table. Potato shows ৳35.00/kg (trimmed mean, 8 samples, high confidence) |
| Traceability Search | `/traceability` | ✅ 200 OK | Batch code search input, recent batches table with trace links |
| Batch Traceability Show | `/traceability/{code}` | ✅ 200 OK | Leaflet.js map with GPS waypoints + polylines, chronological custody timeline with price spreads & markup % |
| Report Form | `/report` | ✅ 200 OK | Full form with crop/district/market/price/date/narrative, GPS capture button, photo upload (max 2MB), reporter details |
| Report Track | `/report/track` | ✅ 200 OK | Tracking code lookup |
| FAQ / Methodology | `/faq` | ✅ 200 OK | 5 expandable FAQ items covering KrishiByte purpose, spread/markup/margin definitions, reference price methodology, anomaly flags, report moderation |
| Login | `/login` | ✅ 200 OK | Email/password form + **1-Click Demo Review Shortcuts** for all 6 roles |
| Register | `/register` | ✅ 200 OK | Registration form with role selection, self-admin/authority assignment prohibited |

#### Authentication & Role-Based Access
| Test | Result |
|------|--------|
| Demo login (admin) → `/admin/dashboard` | ✅ 200 OK |
| Demo login (authority) → `/authority/dashboard` | ✅ 200 OK |
| Demo login (farmer) → `/farmer/dashboard` | ✅ 200 OK |
| Demo login (collector) → `/vendor/dashboard` | ✅ 200 OK |
| Demo login (consumer) → public pages | ✅ 200 OK |
| Guest → protected dashboards | ✅ 302 Redirect to login |

#### Admin Panel — `/admin/dashboard`
- KPI cards: 7 users, 6 active rules, 7 crops, 7 audit events
- **Markup Rules Table:** 6 rules displayed (1 global + 5 crop/stage-specific), enable/disable toggle buttons
- **User Accounts Table:** 7 users with role badges, district, status, suspend/activate actions
- **Recent Audit Log:** 7 recent entries with Dhaka timestamps, actor names, action codes
- **Add Markup Rule Modal:** Crop selector, stage dropdown, max markup %, max spread, explanation

#### Authority Desk — `/authority/dashboard`
- KPI cards: 4 batches, 7 transactions, 1 pending flag, 1 pending report
- **Chart.js Bar+Line Chart:** Stage-wise price progression (avg price BDT + avg markup %)
- **Anomaly Review Queue:** 1 flagged transaction (KB-20261011-ONI002, 47.4% markup vs 28% cap, MEDIUM severity), filter tabs (All/New/Escalated/Cleared)
- **Citizen Reports Queue:** 3 reports (1 pending, 1 verified, 1 rejected), evidence photo links, moderate buttons
- **Review Flag Modal:** Status dropdown (reviewing/cleared/escalated), mandatory notes textarea
- **Moderate Report Modal:** Status dropdown (verified/rejected/duplicate), moderation notes

#### Farmer Portal — `/farmer/dashboard`
- KPI cards: 4 total batches, 1 active in custody, 3 transferred
- **Market Benchmarks Card:** Potato ৳28.00/kg (3 verified surveys) for Bogura district
- **Batches Table:** 4 batches with codes, crops, origin fields, harvest dates, quantities, farm-gate prices, current custodians, stages, traceability links
- **Create Batch Modal:** Crop/district selectors, farm location, harvest date, quantity, unit (kg/mon/sack), baseline price, GPS coords, quality notes

#### Vendor Workspace — `/vendor/dashboard` (Collector)
- KPI cards: 1 in custody, 2 outgoing sales, 3 incoming purchases, 0 flagged alerts
- **Inventory Table:** KB-20261012-TOM003 (Tomato, 1,200 kg @ ৳48/kg), "Pending next sale price" label, Record Handoff button
- **Transaction History:** 5 transactions with direction badges (Incoming/Outgoing), counterparty, stage, qty, price, spread, markup %, validation status, void buttons
- **Record Handoff Modal:** Batch selector, buyer selector, stage dropdown, district, quantity, price with live spread calculator preview, market name, notes
- **Void Transaction Modal:** Mandatory reason textarea, warning about audit log

### UI/UX Consistency Verification
- ✅ Bootstrap 5.3 responsive layout working across all pages
- ✅ Bootstrap Icons rendering correctly throughout
- ✅ Leaflet.js map with OpenStreetMap tiles loading on traceability show page
- ✅ Chart.js bar+line dual-axis chart rendering on authority dashboard
- ✅ Color scheme consistent: green primary (#198754), yellow accent (#ffc107)
- ✅ Bengali crop names displaying correctly (গোল আলু, দেশি পেঁয়াজ, etc.)
- ✅ BDT currency format (৳) used consistently
- ✅ Asia/Dhaka timezone timestamps on audit logs
- ✅ Responsive navbar with mobile toggle
- ✅ Alert banners (success/error/info) with dismiss buttons
- ✅ Form validation error display
- ✅ Pagination on tables (15-25 items per page)

### Outstanding Issues
- None identified. All 25 automated tests pass and all live pages verified working.
