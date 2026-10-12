# KrishiByte

Agricultural batch traceability, price review, and participant verification for Bangladesh. Laravel 12, MySQL, Blade, Bootstrap, and vanilla JavaScript.

## Run locally

Start Wampserver MySQL and configure your local `.env` using `.env.example`. Keep the application environment `local` for the demo shortcuts.

```powershell
php artisan migrate
php artisan db:seed
php -S 127.0.0.1:8000 -t public
```

Open http://127.0.0.1:8000. The login page has local-only demo shortcuts for all seven roles. Seeded data is illustrative, not official market data. Seeding preserves existing users and existing demo batches; it does not reset them.

## What works

- Admin grant/revoke badges for active farmers, collectors, wholesalers, and retailers, with reasons and audit records.
- Farmer registration of harvest lots; collector farm-gate collection; repeated collector/wholesaler transfers; retail sales with stock conservation.
- Every handoff keeps farmer provenance, participants, quantity, price, location, sequence, and recorded time.
- Price, quantity, quality, and traceability reports for guests and all account roles; private evidence; owner history; authority moderation.
- Crop/unit-specific market comparisons, sample minimums, markup and absolute-spread flags, and auditable transaction reversal.
- Responsive public pages and role workspaces, accessible table scrolling, and Bangladesh display times.

Intermediate custody transfers move the entire lot. Retail sales can consume part of a lot. Separate simultaneous custodians, split lots, merging, and loss accounting need a future inventory model.

## Verification and documentation

36 automated tests / 155 assertions pass. Desktop and mobile browser checks include actual badge, collection, transfer, reporting, moderation, sale, and reversal actions. See [testing](docs/TESTING.md), [architecture](docs/ARCHITECTURE.md), [environment](docs/ENVIRONMENT.md), and the latest section of [work summary](WORK_SUMMARY.md).
