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

## 2026-10-13 review remediation and destination markets

This section supersedes earlier descriptions of retailer customer sales and global reviewer access.

- Retail shops accept incoming custody, then record asking prices in `/retail/prices`. Customer handoffs are disabled at the HTTP endpoint; public traces omit historical customer transfers. Quotes retain price/unit/time, shop, batch and marketplace without a customer, payment claim or stock deduction. Earlier partial/sold-out records are retained as historical data.
- Public `/traceability` filters destination district, then marketplace. It shows approved shops and currently stocked goods, their origin districts, recorded quantities, latest shop prices and journey links. Origins are independent of destination markets.
- Admin → Manage district marketplaces creates markets with district-scoped unique names and an audit record; submission requires recent password confirmation. Retail registration requires district, an active market in that district, shop name and trade license. New trader accounts require separate administrator trading approval; badges do not grant capability.
- `showcase:populate` now also creates 39 explicitly illustrative markets across the 13 configured districts, 78 shops, 156 lots and 312 initial asking-price records. Each market has two shops and four cross-district journeys. Seed reruns preserve presentation actions and prices. These are synthetic names/records, not a registry of actual marketplaces or all 64 Bangladesh districts.
- Core review fixes cover percentage precision, terminal flag lifecycle, accepted-transfer correction requests, idempotent account suspension, consent/withdrawal, assisted private farmer access, unit grouping, decimal quantity display and evidence budgets. See `docs/REVIEW_REMEDIATION_2026-10-13.md` for B01-B12, permissions and limitations.

Latest verification: 81 tests / 585 assertions on `krishibyte_test`; live market, registration and retail-price checks; concurrent MySQL acceptance/legacy-stock checks; exact restore comparison on 28 tables. Screenshots and commands are recorded in `WORK_SUMMARY.md` and `docs/TESTING.md`.
