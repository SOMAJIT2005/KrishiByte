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

For a ready presentation, double-click `Populate-Showcase.cmd` once, then `Showcase-KrishiByte.cmd` whenever you want to open the showcase. The live showcase at http://127.0.0.1:8000/demo-showcase includes role shortcuts, prepared scenarios and walkthroughs. See [role-by-role guide](docs/SHOWCASE.md). CLI equivalent: `php artisan showcase:populate`.

## What works

- Admin grant/revoke badges for active farmers, collectors, wholesalers, and retailers, with reasons and audit records.
- Farmer enrollment without a login, admin-generated public profiles, declared crops, recorded sales/quantities and moderated public/middleman ratings.
- Collector-entered purchases with private handwritten agreement uploads, read-aloud attestation, witness and printable receipt. Onward collector/wholesaler/shop transfers require recipient acceptance; retail sales preserve stock.
- Every handoff keeps farmer provenance, participants, quantity, price, location, sequence, and recorded time.
- Price, quantity, quality, and traceability reports for guests and all account roles; private evidence; owner history; authority moderation.
- Crop/unit-specific market comparisons, sample minimums, markup and absolute-spread flags, and auditable transaction reversal.
- Responsive public pages and role workspaces, accessible table scrolling, and Bangladesh display times.
- Public analytics: all crops, district/source/date filters, price history, latest/lowest/highest daily references and rising/falling insights. Synthetic history includes 1,470 samples across seven crops, three districts and 14 days, separate from real survey references.

Intermediate custody transfers move the entire lot. Retail sales can consume part of a lot. Separate simultaneous custodians, split lots, merging, and loss accounting need a future inventory model.

## Verification and documentation

59 automated tests / 395 assertions pass on both test databases. Live local browser checks cover agreement-backed collection, profiles/ratings, admin confirmation, retail sales and analytics; concurrent MySQL checks prove duplicate/oversell protections. See [testing](docs/TESTING.md), [architecture](docs/ARCHITECTURE.md), [security review and remaining deployment work](docs/SECURITY_REVIEW.md), [environment](docs/ENVIRONMENT.md), and the latest section of [work summary](WORK_SUMMARY.md).
