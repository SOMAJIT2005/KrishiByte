# Review remediation — 13 October 2026

The supplied bug review was a source-review document; its directives were treated as proposed requirements authorized by the user's request to complete remaining work. Actual repository/runtime evidence determined which issues remained. This file also records the user's subsequent district/market and retail-price requirements.

| Finding | Implementation / evidence |
|---|---|
| B01 vendor views absent from export | All four actual vendor views exist and render. The supplied text export is incomplete/outdated; it was not evidence of missing runtime views. Latest repository and route tests are the release artifact. |
| B02 percentage overflow | Transactions and anomaly measured/threshold values use DECIMAL(16,2); non-finite/out-of-range calculations are rejected before SQL. MySQL tests preserve 999,999,900% markup and -999,999,900% margin without truncation. |
| B03 stale flagged status | Clearing a flag recomputes transaction review status from all open flags. One remaining open flag keeps it flagged; the last cleared flag resolves it. Historical flags are retained. |
| B04 accepted custody reversal | A participant requests a reasoned correction; the affected counterparty accepts/disputes, or requester cancels. Only the latest active transfer can be corrected; pending handoffs/corrections reserve the lot. Acceptance retains and voids original history before restoring custody. No unilateral administrator bypass. |
| B05 farmer/admin HTTP gap | Farmer/collector/wholesale/retail participant correction routes are explicit. Admin cannot impersonate a trader. Assisted farmer access can request a correction without an email login, but collector acceptance is still required. |
| B06 blanket publication | Fresh migration defaults real farmers private; only the exact canonical synthetic farmer is an exception. Forward repair removes unsupported publication from the earlier backfill. Approved enrollment consent and explicit synthetic profiles are retained. Seed reruns do not republish withdrawn profiles. |
| B07 duplicate enrollment / offline access | Approval links exact name+phone+district matches; other candidates require explicit reviewed linking. Consent time/method and operator are recorded. Private support requests confer no access: admin records an independent identity/contact check and privately delivers a hashed, single-use code valid 24h; redemption gives a bounded 1h farmer session for own agreements, correction requests and publication withdrawal. |
| B08 closed/voided flags | Cleared flags cannot be reopened through ordinary review; voided-source reviews are rejected. Source history remains available. |
| B09 suspension retries | Desired active/suspended status plus version replaces blind toggling. Same-state retries do nothing; stale changes return 409. Ordered staff locks, actor recheck, self-suspension prohibition and last-active-admin guard preserve recoverability. |
| B10 incompatible unit sums | Crop profile totals group crop+unit and match sale units. Existing lot agreements reject incompatible default crop units instead of guessing conversions. |
| B11 decimal / sign display | Public quantities retain two decimals; negative markup avoids a '+-' prefix. Retail customer transfers are now omitted under the newer product requirement; received/available quantities remain decimal on stock/journey pages. |
| B12 long-term evidence budget | Per-collector 100 agreements/rolling 24h, existing report quotas, 2MB/image/dimension limits and a serialized 512MiB private-evidence ceiling. Warnings at 80%/limit. Failed/duplicate operations remove newly created files. Quota and budget tests confirm no orphan, and lock release. |

## Current capability matrix

All mutation routes use CSRF. Suspended authenticated sessions are rejected. These controls are enforced in routes/controllers/services, not only menus.

| Role | Allowed | Important boundaries |
|---|---|---|
| Guest / consumer | Public market/trace/analytics/profile lookup; submit/track concerns; consumers submit moderated ratings and view own reports | No custody/quote mutation or staff access. Report owners can read their own evidence. Public views hide contacts/IDs/precise GPS/agreements/customer transfers. |
| Farmer | Consent-based enrollment/profile, optional login, assisted private records, withdrawal, participant correction request | Farmer dashboard is optional legacy compatibility. Support form/claimed phone alone does not prove identity. No independent payment/signature verification. |
| Collector | Once approved: handwritten/read-aloud/witness purchase attestation, custody proposals, participant receipts/corrections and ratings | Only collectors record offline farmer agreements. Approval and quotas apply. Evidence must match purchase terms and stay private. |
| Wholesaler | Once approved: accept/dispute/propose custody, participant receipts/corrections and ratings | Cannot perform collector attestation or retail quotes. |
| Retail shop | Registered district/market/shop/license; once approved: accept goods, post price/unit history for own active stock, participant receipts/corrections and ratings | Customer handoff endpoint disabled. Quotes are asking prices and do not deduct stock or prove payment. |
| Authority | Assigned-district report/flag queues, scoped aggregate charts, decisions and evidence/receipts/agreements | Unassigned resources denied; submitter and any historical batch participant cannot decide own case. No custody impersonation. |
| Admin | Account/badge/trading governance, enrollments/ratings and assisted access, marketplaces, rules/audit; assigned batch/case review | Evidence/case access requires assignment; no blanket trader bypass. Sensitive governance and access grants require recent password. National pilot assignments for existing reviewers are explicit and should be narrowed before institutional use. |

Trading approval is separate from a verification badge. Existing active traders were grandfathered during rollout; new accounts default unapproved. New retail approval also requires a valid active market in the registered district. Local synthetic traders are explicitly seeded approved; their identities/licenses are fictitious. New reviewers get no assignments automatically, except newly created canonical demo staff given explicit national showcase assignments.

## Destination markets and asking prices

`marketplaces` belongs to a district; retailer accounts reference one marketplace and shop name. `retail_prices` preserves batch, retailer, market, unit, price, timestamp and actor/request key with a database unique constraint. Changed-payload reuse returns 409; identical retries record once. Ownership, approval, district/market consistency and available batch status are checked while rows are locked.

A batch retains its origin district and one current custodian. Market lookup uses approved current retail custodians in the selected destination, so goods from different origins appear together in that market. Public custody timeline stops at receipt by the retailer. Latest shop quotes appear separately from acquisition prices and custody charts. Analytics survey/report/demo reference methodology remains unchanged; asking prices are not silently promoted into reviewed survey references.

Synthetic population: 13 existing districts, 39 illustrative markets, 78 new demo shops, 156 new lots, 468 custody records (farm import + two accepted onward proposals per lot), 312 initial quotes (prior/current day with rising/falling/stable examples). The original Rafiq demo shop also has marketplace assignment and quotes for its existing stock. Farm imports are labelled legacy/unconfirmed; synthetic accepted proposals are not real signatures, contracts or payment evidence. Reruns skip existing lots and preserve demonstration actions.

## Migration / recovery evidence

Applied non-destructive migrations 020000, 030000, 040000 and 050000 to local application and disposable MySQL test databases. No working DB reset or encryption-key rotation. Migrations intentionally refuse destructive down operations that discard financial/consent/evidence history; restore matching source, DB snapshot, environment/key and evidence instead.

Pre-change snapshot: `D:/KrishiByte-security-backup-20261013_02`, exact isolated restore of 23 tables. Post-change snapshot: `D:/KrishiByte-security-backup-20261013_03`, exact isolated restore of 28 tables in `krishibyte_security_restore_20261013_03`. Both include matching private environment and evidence outside the repository. These are local snapshots, not offsite backups or production recovery certification. Pause writes before a real coordinated restore.

## Verification and remaining production work

`php artisan test --compact`: 81 tests / 585 assertions, MySQL `krishibyte_test`. New tests cover registration mismatch, quote ownership/idempotency/no stock/customer mutation, market filter origins/shops, admin-only creation, B02-B12 lifecycle/permissions and quota/assisted access cases. `tests/support/concurrency-check.php` passes real overlapping MySQL acceptance and retained legacy retail-stock/idempotency scenarios. Concurrent staff-status changes are protected by ordered locks/guards but were not independently raced in this round.

Live localhost: selected Dhaka then its Wholesale Market, saw two shops/four lots from several origin districts and posted prices; opened a journey with map/timeline ending at retailer; checked dynamic retail required fields/options; demo retailer posted BDT76/kg and public page displayed it while stock stayed 450kg. Updated showcase instructions and saved screenshots. Admin marketplace creation and negative permissions are covered by HTTP tests; no claim of browser-testing every new governance flow. Blade compilation, changed PHP formatting and diff checks passed.

Remaining deployment work: maintained staff MFA/recovery, real verified-contact delivery, HTTPS/proxy/cookie settings, least-privilege DB account, stronger CSP/local asset strategy, monitored offsite backups, evidence retention/archival and institution-specific reviewer assignments. The one-time code flow relies on an operator performing the documented independent check; it is not automated phone ownership verification. Exact actor/stock protection is tested on the current MySQL pilot, not every host/browser/load pattern. Split/merge lots, loss ledgers, receivables and saved analytics comparisons are optional future features; continuous GPS and real SMS remain unimplemented.
