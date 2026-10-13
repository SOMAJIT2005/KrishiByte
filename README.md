# 🌾 KrishiByte (কৃষি-বাইট)

**Agricultural batch traceability, price transparency, and participant verification for Bangladesh.**

KrishiByte follows a harvest from the farm gate to the kitchen market. Every custody handoff (farmer → collector → wholesaler → retailer) is recorded with quantity, price, location and time, so that price spreads and markups at each step are visible to consumers, farmers and market monitors.

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)
![Tests](https://img.shields.io/badge/tests-87%20passing-brightgreen)
![License](https://img.shields.io/badge/license-MIT-blue)

> ⚠️ **Status: local pilot / demonstration project.** All seeded data (prices, markets, shops, farmers, agreements) is **synthetic and illustrative**. It is not official market data and not a registry of real marketplaces. See [Limitations](#-limitations-and-production-readiness) before any real deployment.

---

## 📑 Table of contents

1. [Why KrishiByte?](#-why-krishibyte)
2. [Key features](#-key-features)
3. [Tech stack](#-tech-stack)
4. [Quick start](#-quick-start)
5. [Demo showcase](#-demo-showcase)
6. [Roles and permissions](#-roles-and-permissions)
7. [How the supply chain works](#-how-the-supply-chain-works)
8. [Price intelligence and analytics](#-price-intelligence-and-analytics)
9. [Markets, shops and destination lookup](#-markets-shops-and-destination-lookup)
10. [Reporting and moderation](#-reporting-and-moderation)
11. [Architecture](#-architecture)
12. [Database overview](#-database-overview)
13. [Security and privacy](#-security-and-privacy)
14. [Testing](#-testing)
15. [Project structure](#-project-structure)
16. [Configuration reference](#-configuration-reference)
17. [Limitations and production readiness](#-limitations-and-production-readiness)
18. [Further documentation](#-further-documentation)
19. [License](#-license)

---

## 💡 Why KrishiByte?

Produce prices in Bangladesh often multiply between farm gate (e.g. Bogura, Dinajpur) and urban retail (e.g. Dhaka, Chattogram). Without a record of who held the goods and at what price, it is hard to tell legitimate transport and storage costs from speculation.

KrishiByte models each harvest **lot as a traceable batch** with a single current custodian, records each transfer atomically, calculates decimal-safe spreads and markups, and raises **review flags** (not accusations) when configured thresholds are exceeded.

## ✨ Key features

### Traceability
- Unique batch codes (`KB-YYYYMMDD-XXXXXX`) with original farmer, harvest date, quantity, unit and farm-gate price preserved through the whole journey.
- Public journey page with a **Leaflet map** (district-centre coordinates only; precise farm and transaction GPS is never disclosed), custody timeline, per-step spread and markup.
- Repeated intermediaries supported: collector → collector, wholesaler → wholesaler, etc.
- **Counterparty acceptance** is required before custody moves. Proposals reserve the lot for up to 48 hours; disputes, cancellations and expiry never move stock.

### Farmers without logins
- Farmers **enroll without email or password** (a collector or relative can help). An administrator reviews the application and generates a public profile with declared crops, recorded sales and moderated ratings. Phone numbers and agreements stay private.
- Collectors record farm-gate purchases with a **private handwritten-agreement photo**, read-aloud attestation and witness name, and a printable receipt. These are explicitly labelled *collector attested* (not independently confirmed by the farmer).
- Optional **assisted private access** for farmers: admin-verified, hashed, single-use 24-hour code, then a 1-hour bounded session to view own agreements, request corrections or withdraw publication.

### Prices and analytics
- Crop/unit/district/date-specific reference prices (median for 3–4 samples, 20% trimmed mean for 5+, none below 3).
- Public analytics: 7/14/30-day history for all crops, rising/falling/stable insights, latest/lowest/highest daily references, source filters (reviewed surveys, reviewed citizen reports, illustrative demo).
- Authority dashboard comparing **custody stages** (price and markup) for a selected crop and unit.

### Markets and shops
- District-owned **marketplaces**; retail shops register with district, market, shop name and trade license.
- Public lookup: choose a destination district → market → see approved shops, stocked goods, **origin districts**, recorded stock, latest shop asking prices and journey links.
- Retail shops accept incoming goods and post **asking prices** (no customer handoffs, no payment claims, no stock deduction).

### Governance and trust
- Admin-granted **verification badges** (reasoned, audited). Badges ≠ trading approval ≠ crop-quality certification.
- Separate **trading approval** for collectors, wholesalers and retailers.
- District-scoped **reviewer assignments** and conflict-of-interest checks for authority/admin review.
- Configurable **markup / absolute-spread rules** with effective dates, rule snapshots on every flag and relative-excess severity.
- Append-only application audit log; recent-password confirmation for sensitive admin actions.

### Reporting
- Price, quantity, quality and traceability concerns from guests and every role, with optional batch context, private evidence and tracking codes.
- Authority moderation; verified price reports update exactly one linked observation.

---

## 🧰 Tech stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12.x, PHP ≥ 8.2 |
| Database | MySQL 8.0 (Wampserver in the reference setup) |
| Views | Blade templates |
| UI | Bootstrap 5.3.3, Bootstrap Icons, vanilla JavaScript |
| Charts / maps | Chart.js 4.4.2, Leaflet 1.9.4 + OpenStreetMap tiles |
| Testing | PHPUnit 11 (Feature + Unit) |
| Tooling | Laravel Pint, Vite 7 / Tailwind 4 (scaffold only; UI uses CDN Bootstrap) |
| Local launchers | Windows `.cmd` + PowerShell scripts |

Frontend libraries load from CDNs, so maps and charts need internet access.

---

## 🚀 Quick start

### Prerequisites
- PHP 8.2+ with `bcmath`, `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `mysqli`, `pdo_mysql`, `openssl`, `xml`
- Composer 2.x
- MySQL 8.0 running on `127.0.0.1:3306`
- (Optional) Node.js, only if you want to build the Vite scaffold

### Installation

```bash
git clone https://github.com/somajit2005/krishibyte.git
cd krishibyte

composer install
cp .env.example .env
php artisan key:generate
```

Create two databases (one for the app, one for tests):

```sql
CREATE DATABASE krishibyte CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE krishibyte_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Edit `.env` (defaults: user `root`, empty password, database `krishibyte`, `APP_ENV=local`). Then:

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
php -S 127.0.0.1:8000 -t public      # or: php artisan serve
```

Open <http://127.0.0.1:8000>.

> Keep `APP_ENV=local` for the demo shortcuts. Seeding preserves existing users and demo batches; it does **not** reset them. Do **not** use `migrate:fresh` on a database you care about.

### Windows one-click launchers

| File | What it does |
|---|---|
| `Populate-Showcase.cmd` | Runs `php artisan showcase:populate`, generates the synthetic demo agreement image, then starts the showcase |
| `Showcase-KrishiByte.cmd` | Checks MySQL, reuses or starts a hidden PHP server on port 8000, opens `/demo-showcase` |

Both accept `-NoBrowser`; the start script accepts `-Port <n>`. Wampserver MySQL must be running.

### Artisan commands

```bash
php artisan showcase:populate      # idempotent demo data (local/testing only)
php artisan reports:secure-evidence # move legacy public evidence to private storage
php artisan test                   # run the suite
```

---

## 🎬 Demo showcase

After `showcase:populate`, open <http://127.0.0.1:8000/demo-showcase> (localhost + local env only). It provides **one-click role shortcuts**, ten prepared scenarios (`KB-DEMO-*` lots) and role-by-role walkthroughs.

**Populated demo dataset**

| Dataset | Count |
|---|---|
| Analytics price samples | 1,470 (7 crops × 3 districts × 14 days × 5 samples) |
| Workflow lots | 10 (collector custody, wholesale, retail, high markup, pending/disputed/cancelled, sold out, farm-ready, rejected) |
| Report examples | 12 (4 categories × pending/verified/rejected) |
| Districts / marketplaces | 13 / 39 illustrative markets |
| Demo shops / market lots / shop quotes | 78 / 156 / 312 |

Every synthetic record is labelled as demo and is kept out of real survey statistics.

**Local seeded accounts (demo only — change or remove before any real use)**

| Role | Email | Password |
|---|---|---|
| Admin | `admin@krishibyte.local` | `AdminPass123!` |
| Authority | `authority@krishibyte.local` | `AuthorityPass123!` |
| Farmer | `farmer.rahim@krishibyte.local` | `FarmerPass123!` |
| Collector | `collector.karim@krishibyte.local` | `CollectorPass123!` |
| Wholesale | `wholesale.hasan@krishibyte.local` | `WholesalePass123!` |
| Retail | `retail.rafiq@krishibyte.local` | `RetailPass123!` |
| Consumer | `consumer.tariq@krishibyte.local` | `ConsumerPass123!` |

**Suggested 10-minute walkthrough**

1. **Public:** Analytics → *Illustrative demo samples* → Chattogram → 14 days (potato rising, onion falling, brinjal stable, chili spike then drop).
2. **Traceability:** open a potato journey with five intermediary transfers; then Dhaka → Wholesale Market to see multi-origin goods.
3. **Admin:** approve a farmer enrollment (password confirmation required) → profile appears.
4. **Collector:** record a farmer purchase with an agreement photo → collector-attested receipt → propose the lot to a wholesaler.
5. **Wholesaler / Retailer:** accept proposals; retailer posts a selling price.
6. **Consumer:** rate a farmer and submit a concern.
7. **Authority:** review a high-markup flag and moderate a report.

---

## 👥 Roles and permissions

| Role | Can do | Key boundaries |
|---|---|---|
| **Guest / Consumer** | Browse prices, analytics, traceability, farmer profiles; submit and track reports; consumers submit moderated ratings | No custody or price mutations |
| **Farmer** | Enroll, hold a public profile, optional login, private access to own agreements, request corrections, withdraw publication | Phone/agreement private; support form alone proves nothing |
| **Collector** | Record farmer purchases with agreements; propose/accept custody handoffs; request corrections | Needs admin trading approval, trade license, district; 100 agreements/day cap |
| **Wholesaler** | Accept/dispute/propose custody handoffs | Cannot attest farm-gate purchases or post retail quotes |
| **Retail shop** | Accept incoming goods; post asking prices for own stock | District + market + shop name + license required; customer handoffs disabled |
| **Authority** | Review flags and reports in **assigned districts** | Cannot review own or participant cases |
| **Admin** | Badges, trading approval, enrollments, ratings, markets, rules, audit log, assisted access, reviewer assignments | No blanket trader impersonation; sensitive actions need recent password |

Trading approval is **separate** from verification badges. Privileged roles cannot be self-registered.

---

## 🔗 How the supply chain works

```
Farm gate ─► Collector(s) ─► Wholesaler(s) ─► Retail shop ─► (asking price posted)
 (profile)   (agreement)     (acceptance)      (acceptance)    (no customer handoff)
```

- A **batch** has exactly one current custodian. Intermediate transfers move the **whole lot**.
- **Stage** is derived from participant roles (never trusted from form input).
- **District-first recipient selection:** a seller picks the recipient's district, then role, then an active, approved, registered account. The server re-checks district membership.
- **Acceptance:** the counterparty accepts, disputes or the proposer cancels. Actor-scoped idempotency keys make retries safe; changed payloads are rejected.
- **Corrections:** only the latest active custody transfer can be corrected, and only with the affected counterparty's acceptance. History is retained as *voided*, never deleted.
- **Calculations** (decimal-safe, `bcmath`):
  - Gross spread = `S − B`
  - Markup on cost = `((S − B) / B) × 100`
  - Gross margin = `((S − B) / S) × 100`
- Acquisition cost is the latest non-void custody purchase price.

---

## 📈 Price intelligence and analytics

- **Reference price** compares crop + canonical unit + district + exact Bangladesh calendar date + source. Duplicates are removed. Fewer than 3 distinct samples → no reference.
- **Sources are never mixed:** *reviewed market surveys*, *reviewed citizen reports* (a possibly biased complaint sample) and *illustrative demo samples*.
- Analytics shows explicit gaps rather than invented prices, and compares the latest two qualifying days.
- In `local` the analytics page defaults to demo data; in production it defaults to surveys.
- **Anomaly rules** use markup % and/or absolute spread, honour effective dates, keep zero caps, store a **rule snapshot** per flag and classify severity by relative excess (≥100% high, ≥25% medium, else low). A flag is a review signal, not a finding of wrongdoing.

---

## 🏪 Markets, shops and destination lookup

1. Admin creates district-scoped, uniquely named marketplaces (password confirmation + audit).
2. Retailers register with district, matching active market, shop name and trade license; an admin separately approves trading.
3. Retailers accept incoming goods, then post asking prices (`/retail/prices`) with price, unit and time retained per quote.
4. The public `/traceability` page filters by **destination** district → market and lists shops, goods, origin districts, recorded stock, latest quote and journey link.

Asking prices are shop-entered quotes, not verified payments, and are not promoted into survey references.

---

## 📣 Reporting and moderation

- Categories: **price, quantity, quality, traceability**; price is required only for price concerns.
- Evidence images are decoded, size/dimension limited (2 MB, 4096 px, 12 MP), **re-encoded** to strip metadata, stored privately with random names and an original-file SHA-256.
- Only the owner and assigned reviewers can fetch evidence; public tracking shows a safe summary only.
- Submission throttling, duplicate detection and daily evidence quotas.
- Verified price reports create/update exactly one linked observation; rejecting withdraws it. Demo provenance is preserved.

---

## 🏗 Architecture

**Pattern:** Laravel monolith (controllers → services → Eloquent models), server-rendered Blade.

### Core services (`app/Services`)

| Service | Responsibility |
|---|---|
| `BatchHandoffService` | Atomic proposals, acceptance, retail records (CLI legacy), voids; row locks, idempotency, stock |
| `CorrectionService` | Counterparty-approved reversal of the latest custody transfer |
| `FarmPurchaseService` | Collector-attested farm-gate purchase + private agreement |
| `AnomalyDetectionService` | Rule selection, flags, snapshots, notifications |
| `CalculationService` | Spread, markup, margin, reference price |
| `PriceReferenceService` / `PublicAnalyticsService` | Comparable-sample queries, daily history, trends |
| `EvidenceService` | Image validation, re-encode, storage budget (512 MiB, serialized by MySQL lock) |
| `ReviewAccess` | District-scoped reviewer authorization and conflict checks |
| `FarmerAccess` | Assisted farmer session handling |
| `AuditLoggerService` | Append-only audit entries |
| `SmsService` | **Mock** SMS adapter (logs masked numbers to `storage/logs/sms.log`) |

### Middleware
`RoleMiddleware` (exact role gates, suspended-account logout) and `AccountAndSecurityHeaders` (nosniff, frame deny, referrer policy, CSP frame/object restrictions, private/no-store for authenticated pages).

### Main routes (see `routes/web.php`)

| Area | Examples |
|---|---|
| Public | `/`, `/prices`, `/analytics`, `/farmers`, `/farmers/enroll`, `/traceability`, `/traceability/{code}`, `/report`, `/report/track`, `/faq`, `/demo-showcase` (local) |
| Auth | `/login`, `/register`, `/demo-login/{role}` (local POST), `/logout` |
| Vendor | `/vendor/dashboard`, `/vendor/farmer-purchase`, `/handoffs`, `/corrections`, `/retail/prices` |
| Farmer private | `/farmer-access`, `/farmer-agreements/{id}` |
| Authority | `/authority/dashboard`, flag review, report moderation |
| Admin | `/admin/dashboard`, `/admin/marketplaces`, `/admin/farmer-review`, `/admin/audit-logs`, `/admin/batches/*` |

### Named rate limiters
`registration`, `login` (per client and per account/IP), `tracking`, `reports`, `demo`, `decisions`, `confirmation`, `analytics`, `enrollment`, `ratings`, `purchases`.

---

## 🗄 Database overview

Application timestamps are stored in UTC and displayed in **Asia/Dhaka**.

| Table | Purpose |
|---|---|
| `users` | Roles, status + version, verification, trading approval, publication consent, encrypted NID/trade license, marketplace/shop |
| `districts`, `crops`, `marketplaces` | Reference data (13 districts, 7 crops, district-owned markets) |
| `batches` | Lots: provenance, quantity, unit, farm-gate price, custodian, stage, verification |
| `transactions` | Custody records: sequence, price, spread, markup, confirmation status, idempotency key, void state |
| `handoff_proposals` | Pending/accepted/disputed/cancelled/expired proposals with term snapshots |
| `correction_requests` | Counterparty-approved corrections |
| `farm_purchases` | Collector-attested agreements and private evidence metadata |
| `farmer_enrollments`, `farmer_ratings`, `farmer_access_requests` | Enrollment, moderated ratings, assisted access |
| `retail_prices` | Append-only shop asking prices |
| `price_observations`, `price_reports` | Price evidence and citizen concerns |
| `markup_rules`, `anomaly_flags` | Thresholds and review flags with rule snapshots |
| `review_assignments` | Reviewer ↔ district scope |
| `audit_logs`, `notifications` | Audit trail and in-app alerts |

Migrations are additive. `down()` methods that would discard financial, consent or evidence history deliberately **refuse** to run; restore from a verified backup instead.

---

## 🔒 Security and privacy

- **Public pages never show:** phone/email, NID/trade license, precise farm or transaction GPS, agreement images, historical customer names, report narratives, evidence, moderator notes.
- NID/trade license encrypted with Laravel's encrypted cast (keep the matching `APP_KEY` with backups).
- CSRF on all mutations; demo login is POST-only, local-environment, loopback-only.
- Session regeneration on login/registration; suspended sessions rejected.
- Recent-password confirmation for admin governance actions.
- Application-level append-only audit (model update/delete rejected). *DB administrators can still alter SQL rows.*
- Concurrency protection: batch/transaction row locks, unique actor/request keys, MySQL named lock for evidence budget.
- `.env` is untracked. **Secrets committed earlier remain in Git history** — rotate any real credentials and clean history before publishing a real deployment.

Full findings and the B01–B12 remediation matrix: [`docs/SECURITY_REVIEW.md`](docs/SECURITY_REVIEW.md), [`docs/REVIEW_REMEDIATION_2026-10-13.md`](docs/REVIEW_REMEDIATION_2026-10-13.md).

---

## 🧪 Testing

```bash
# prepare the disposable test database once
php artisan migrate --env=testing
php artisan db:seed --env=testing

php artisan test --compact
# or
php -d memory_limit=256M vendor/bin/phpunit
```

- Latest recorded full run: **87 tests / 695 assertions** passing on MySQL `krishibyte_test` (feature tests roll back via `DatabaseTransactions`).
- Coverage includes: RBAC, registration rules, custody/proposals/corrections, idempotency, anomaly rules and severity, analytics source separation, evidence privacy and quotas, farmer enrollment/ratings/assisted access, marketplace and retail quotes, district-first recipient selection, authority charts, showcase seeding.
- **Real concurrency check** (overlapping MySQL processes):

```powershell
$env:DB_DATABASE = 'krishibyte_test'
php tools/concurrency-check.php
```

- `tools/security-backup.php <date>` creates a local snapshot and verifies an exact restore in an isolated database.

Details: [`docs/TESTING.md`](docs/TESTING.md).

---

## 📁 Project structure

```
krishibyte/
├── app/
│   ├── Http/Controllers/     # Admin, Auth, Authority, Correction, FarmerAccess, FarmerProfile,
│   │                         # Handoff, Market, Public, Vendor, Farmer
│   ├── Http/Middleware/      # RoleMiddleware, AccountAndSecurityHeaders
│   ├── Models/               # Eloquent models
│   ├── Providers/            # Rate limiters, Bootstrap 5 pagination
│   └── Services/             # Business logic (see table above)
├── bootstrap/                # App bootstrap (+ Windows serve env passthrough)
├── config/
├── database/
│   ├── migrations/           # Additive, history-preserving
│   └── seeders/              # Users, districts, crops, rules, demo/showcase/marketplace seeders
├── docs/                     # ARCHITECTURE, ENVIRONMENT, SECURITY_REVIEW, SHOWCASE, TESTING,
│                             # REVIEW_REMEDIATION_2026-10-13, screenshots/
├── public/                   # Front controller, refined.css
├── resources/views/          # layouts, public, admin, authority, vendor, farmer, auth, components
├── routes/                   # web.php, console.php (showcase:populate, reports:secure-evidence)
├── tests/                    # Feature + Unit suites
├── tools/                    # concurrency-check, security-backup, demo-agreement, PowerShell launchers
├── Populate-Showcase.cmd
├── Showcase-KrishiByte.cmd
├── WORK_SUMMARY.md           # Chronological build and verification log
└── LICENSE
```

---

## ⚙️ Configuration reference

Key `.env` settings (see `.env.example`):

| Variable | Default | Notes |
|---|---|---|
| `APP_ENV` | `local` | Demo shortcuts, showcase and demo analytics default require `local` (or `testing` for population) |
| `APP_KEY` | generated | **Never regenerate against an existing database** (encrypted identifiers) |
| `DB_*` | MySQL `krishibyte` @ `127.0.0.1:3306`, `root` / empty | Use a least-privilege account in production |
| `SMS_DRIVER` | `dummy` | Mock adapter only |
| `SESSION_DRIVER` / `CACHE_STORE` / `QUEUE_CONNECTION` | `file` / `database` / `database` | Test env overrides these in `.env.testing` / `phpunit.xml` |
| `config('evidence.storage_budget_bytes')` | 512 MiB | Private evidence ceiling |

Time zone display: **Asia/Dhaka**. Currency: **BDT (৳)**. Supported units: `kg`, `mon`, `sack`, `piece` (no automatic conversion).

---

## 🚧 Limitations and production readiness

This is a **verified local prototype**, not a production certification.

**Product boundaries**
- One custodian per lot; intermediate transfers move the whole lot. Split/merge lots and loss accounting would need an inventory/lineage model.
- Retail customer sales are disabled; shops publish asking prices only. Payment is never verified.
- Farm purchases are **collector attestations** — an uploaded image does not authenticate a farmer's consent, signature or payment.
- Ratings are one per account per farmer; multi-account abuse needs stronger identity controls.
- GPS is optional user-supplied location, not tracking. SMS is a mock. 13 seeded districts and 39 demo markets are a demonstration dataset, not all 64 districts or a real market registry.
- Existing reviewers received explicit *national pilot* assignments — narrow them before institutional use.

**Before external deployment**
- Staff MFA and account recovery; real verified-contact delivery for assisted farmer access
- HTTPS / proxy / secure-cookie configuration, production debug and secret review
- Least-privilege DB account; monitored, encrypted, off-host backups and tested restores
- Stronger nonce-based CSP and locally hosted frontend assets (CDN integrity)
- Evidence retention/archival policy, storage monitoring
- Independent security testing; npm lockfile + frontend dependency audit
- Credential rotation and Git-history clean-up for secrets committed earlier

---

## 📚 Further documentation

| Document | Contents |
|---|---|
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Custody model, verification, reporting, price methodology |
| [`docs/ENVIRONMENT.md`](docs/ENVIRONMENT.md) | Verified local environment, safe upgrades, test databases |
| [`docs/SHOWCASE.md`](docs/SHOWCASE.md) | Role-by-role presentation guide |
| [`docs/SECURITY_REVIEW.md`](docs/SECURITY_REVIEW.md) | Findings, decisions, remaining deployment work |
| [`docs/REVIEW_REMEDIATION_2026-10-13.md`](docs/REVIEW_REMEDIATION_2026-10-13.md) | B01–B12 mapping, capability matrix, recovery notes |
| [`docs/TESTING.md`](docs/TESTING.md) | Test results, live browser checks, concurrency and restore evidence |
| [`WORK_SUMMARY.md`](WORK_SUMMARY.md) | Full build and verification log |

---

## 📄 License

Released under the [MIT License](LICENSE) — © 2026 Somjit.
