# Verification — 13 October 2026

Latest verification supersedes the historical results below: **59 tests passed, 395 assertions**, on both `krishibyte_test` and independently migrated/seeded `krishibyte_security_qa`. Test cases roll back their fixtures. New checks cover bounded/source-aware analytics, exact deduplication, encrypted identities, session regeneration, independent request limits, role restrictions, private uploads, proposal acceptance/dispute/cancel/expiry, unit snapshots, idempotent agreements/retail sales, rating moderation, enrollment generation, production-disabled demo population and repeat seeding.

`tools/concurrency-check.php` passed using real parallel MySQL processes in the test DB: one transaction for duplicate acceptance; no oversell for concurrent 60/100kg sales; one deduction for duplicate retail-sale requests. Backup/restore comparisons matched exact rows across 19 tables before hardening and 23 tables after new farmer workflows, using isolated restore databases.

Live browser checks on localhost verified: receipt #15 for a synthetic 10kg purchase at BDT21/kg, private agreement upload, updated farmer quantity/sales profile, middleman rating moderation/publication, synthetic enrollment → admin password confirmation → profile #10, anonymous retail receipt/stock, all-crop analytics, source/district/product selection, latest/high/low chart, and the new showcase guide. At 375px viewport, analytics document width was 360px and chart width 299px: no page overflow. Desktop checks used 1440px. Screenshots are in `docs/screenshots`, including `analytics-price-history.jpg`, `public-analytics.jpg`, `analytics-mobile.jpg`, `farmer-profile.jpg` and `farmer-agreement-receipt.jpg`.

Both launcher branches passed: reuse of the running server on 8000, and start of an isolated hidden test server on 8011 (then stopped after verifying its PID/command line). `Populate-Showcase.ps1 -NoBrowser` passed with 10 workflow lots, 1,470 analytics samples and 12 report examples; scenario tests prove reruns preserve quantities, notes and completed actions.

Fixed defects found during verification: shared numeric-throttle buckets made tracking incorrectly return 429 after registration requests (replaced with named limiters); anonymous customer rendering failed on the admin batch page (nullable handling); farmer purchase used an unsupported batch review enum (corrected to `unverified`); batch/report code dates used host UTC instead of the Bangladesh date (corrected); nested Blade directives in analytics failed compilation (reformatted); demo report moderation lacked demo provenance (now preserved). A transient Windows formatter write lock succeeded on retry.

Composer audit: no locked-package advisories/abandoned packages. npm audit: unavailable without an npm lockfile, so frontend dependency safety is not claimed. PHP lint, Blade compilation, focused Pint and whitespace checks are recorded in WORK_SUMMARY.md. Local checks are not a production, load or exhaustive browser certification; see SECURITY_REVIEW.md for remaining work.

## Historical verification — 12 October

## Automated checks

Final suite: **36 tests passed, 155 assertions** on `krishibyte_test`. The same suite passed on a freshly migrated and seeded `krishibyte_upgrade_qa` database. Re-running the seeders preserved counts: 9 users, 4 batches, 9 transactions, 3 reports, 15 observations and 6 rules on fresh QA before rolled-back tests.

```powershell
php -d memory_limit=256M vendor/bin/phpunit
```

A transient Windows paging/VirtualAlloc failure occurred during one combined fresh-install run. Retrying with the explicit 256 MB PHP memory limit completed successfully.

Covered behavior includes authentication/RBAC, privileged registration rejection, public searches, reference-price sample rules, farmer workflows, repeated collector/wholesaler custody, conserved retail stock, acquisition cost, sold-out/reversal behavior, void sequence preservation, unauthorized and stale reversals, badge authorization/auditing, private evidence, report owner isolation, all role pages, idempotent moderation, non-price reports, absolute-spread thresholds, batch rejection and restoration.

PHP source lint, Blade view compilation and focused Pint formatting passed. No new dependencies were introduced.

## Live local browser checks

Tested at http://127.0.0.1:8000 using the running PHP server and WAMP database:

- Admin granted a farmer badge and checked batch review/audit pages.
- Collector recorded farm-gate collection of `KB-20261012-CHI004`, then transferred it to another collector.
- Submitted a quality report without price, found it in owner history and public tracking, and rejected it as an authority with an apostrophe in the private note.
- Retailer sold 2 kg of `KB-20261010-POT001`; stock dropped to 2,498 kg. Reversing the sale restored 2,500 kg and retained the voided record.
- Inspected public multi-intermediary trace, report forms, prices, and workspaces for all seven roles. Checked 17 key route visits without application exceptions.
- At 375×812, inspected home, report, admin, vendor and batch trace; navigation worked and body width stayed within the viewport. Wide tables scroll inside their containers.
- At 1440×1000, inspected final navigation and homepage. Checked browser error logs during the interaction flows; no errors appeared in those checks.

Live QA leaves clearly labeled demo records: farmer verification audit, the chili collection/onward transfer, report `RPT-20261012-HTCT3D`, and the voided retail sale. They demonstrate the workflows and should not be interpreted as real trade.

Screenshots are in `docs/screenshots`. These checks exercise representative flows; they do not claim exhaustive security, load, production-hosting, or every-browser coverage.

## Known limits

A lot has one custodian: intermediate transfers move its full quantity. Partial retail sales work. Split/merge stock, losses, continuous GPS, real SMS delivery and public hosting were not implemented.

## 2026-10-13 final review and market verification

Supersedes earlier retailer-sale walkthroughs and global reviewer permissions. Full mapping/role matrix: [review remediation](REVIEW_REMEDIATION_2026-10-13.md).

- `php artisan test --compact`: **81 passed / 585 assertions**, PHP 8.2 + MySQL `krishibyte_test`. Tests use transactions/owned fixtures; the application database was not reset. Added ReviewRemediationTest, ReviewSafeguardsTest, MarketplaceWorkflowTest and the trade-license tests.
- New checks include DECIMAL endpoints; mixed/closed/voided flags; participant corrections and assisted farmer corrections; explicit account status/version retries; scoped reviewer evidence/decisions/conflicts; duplicate enrollment, single-use access and withdrawal; crop/unit separation; fractional display; daily/global evidence quotas and orphan/lock cleanup; matching district/market registration; retailer quote ownership/idempotency; no customer handoff or stock deduction; multi-origin market results and admin-only market creation.
- `DB_DATABASE=krishibyte_test php tools/concurrency-check.php` (set variable in PowerShell): PASS with overlapping MySQL processes. Duplicate acceptance produces one record; retained historical retail stock races do not oversell or deduct twice. This tests legacy import/service invariants, not a currently available customer-sale page. No independently raced staff-status test in this round.
- `php artisan view:cache`, changed PHP Pint and `git diff --check`: PASS after final formatting. Population rerun preserved the 39 markets, 78 shops, 156 market lots and 312 initial market quotes.
- `php tools/security-backup.php 20261013_02`: pre-change exact restore of 23 tables. `php tools/security-backup.php 20261013_03`: post-change exact restore of 28 tables. Private matching environment/key and evidence are outside the repository; pause writes for coordinated recovery.
- Live localhost browser: destination Dhaka then its Wholesale Market showed exactly two demo shops and four traced lots from multiple origins; journey map/timeline ended at retailer with a separate asking price. Registration switched to Retail: district/shop/market/license became required and only Dhaka markets were selectable. Retail shortcut opened price workspace; BDT76/kg was posted for KB-DEMO-RETAIL, history/public trace updated, 450kg stock unchanged. Admin marketplace form/list rendered; actual creation/authorization tested by HTTP tests.

Screenshots: `market-traceability.png`, `retail-market-registration.png`, `retail-selling-price.png`, `admin-marketplaces.png` under `docs/screenshots`. Remaining MFA, verified contact delivery, production hosting/backup/retention, split lots and load/every-browser limits remain documented; these are representative local checks.

Final responsive cards were rechecked live on Barishal's Neighbourhood Market and the focused market suite passed after the view change. All 39 demo markets were independently checked for two shops/four lots/multiple origins. Changed PHP files passed syntax lint. The post-change backup also contains a matching source snapshot.

## Authority pagination UI correction — 13 October 2026

Switched Laravel's paginator to its built-in Bootstrap 5 view, matching the project's styles. PHP lint and the existing all-role workspace render test passed (1 test / 15 assertions). Live review desk pagination is compact and Next retains crop_id=5 while changing reports_page=2 (11–16 of 16). Screenshot: `docs/screenshots/authority-pagination-fixed.png`.

## Authority stage chart — 13 October 2026

Focused run: `php artisan test --compact --filter='AuthorityChartTest|UpgradeWorkflowTest|ReviewRemediationTest|ReviewSafeguardsTest'` → **25 passed / 171 assertions**. New coverage checks stage ordering, independent averages/ranges/sample counts, selected crop/unit/assigned-district isolation, voided/customer-sale exclusion, null markup and empty results. Live checks exercised Price/Markup switching, horizontal bars, exact-value table and an empty chart error log. Source lint, Pint, Blade compilation and whitespace checks passed. Screenshot: `authority-stage-chart-fixed.png`. This focused result does not replace the earlier full-suite result.

## District-first registered buyer workflow — 13 October 2026

Collector → wholesale/retail and wholesale → retail are supported with registered active approved recipients and buyer acceptance. Seller workspaces now select recipient district first, then role/name; results exclude other districts/inactive/unapproved accounts. The server checks district membership before proposing. New trader registration and trading approval require district registration. Retail customer handoffs remain disabled.

Full run after the district-transfer changes: 87 tests / 695 assertions. Final focused registration/market/workspace checks: 14 tests / 209 assertions. RegisteredBuyerHandoffTest covers HTTP transfers/provenance, >50-shop role discoverability, shop searching, recipient/district restrictions across farmer/collector/wholesale workspaces, and registration/approval requirements. Live collector checks showed Dhaka wholesalers and Rafiq's matching shop; screenshots saved. No real transfer submitted in live QA.
