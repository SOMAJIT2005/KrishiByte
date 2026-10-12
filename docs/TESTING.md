# Verification — 12 October 2026

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
