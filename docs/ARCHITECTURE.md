# Architecture and workflow — 12 October 2026

## Application

Laravel 12.69.3 / PHP 8.2 / MySQL. Blade pages share `resources/views/layouts/app.blade.php` and `public/css/refined.css`. Bootstrap, Chart.js and Leaflet remain the existing frontend libraries. Roles are farmer, collector, wholesale, retail, consumer, authority and admin; privileged registration is rejected.

## Custody and stock

A `Batch` retains its original farmer, harvest details, quantity, unit and farmer price. It has one current custodian. `BatchHandoffService` locks the batch and records the transaction, inventory changes, audit record and anomaly flags in one database transaction.

Allowed transitions:

- farmer → collector (collector records agreed terms with a private contract image, read-aloud attestation and witness; optional legacy farmer accounts may use proposals)
- collector/wholesale → collector/wholesale/retail, including repeated intermediaries
- retail receives deliveries and publishes shop asking prices; customer handoffs are disabled. Retained historical retail-sale service records are compatibility data, not a current customer workflow.

Middlemen and shops propose terms and the recipient accepts before custody moves. Proposals reserve a whole lot for up to 48 hours; dispute/cancellation/expiry do not move stock. Farm purchases are explicitly collector-attested, not independently farmer-confirmed. Private receipts and images are participant/reviewer scoped. Actor/request keys make acceptance/purchase/sale retries idempotent, and changed payloads are rejected.

The service derives stage from participants instead of trusting form input. Seller must be current custodian; participants must be active. Quantity and price are positive and limited to two decimal places. Intermediate transfers must move all remaining stock because one lot has one custodian. Partial retail sales reduce stock while the retailer keeps custody; zero stock marks the lot sold out.

Acquisition cost is the most recent non-void custody purchase. Retail sales use that cost rather than treating earlier sales as acquisition. Sequence numbers include voided records. Only the latest active transaction may be reversed by its recorder or an admin, with a reason. Reversal restores prior custody/stock and clears related flags without deleting history.

## Verification and review

Participant badges are admin decisions, with grant/revoke reasons and audit records. Only active, verified farmers, collectors, wholesalers and retailers display the badge. A badge identifies an account review; it does not certify crop quality. Batch verification remains separate. Rejected batches are excluded from public traceability and cannot be handed off; admins may verify them later.

Markup rules honor effective dates, select the most specific crop/stage match, then the newest rule. Each breached metric gets its own flag, severity and rule snapshot. Flags prompt review; they do not automatically freeze trading or prove wrongdoing. Audit records reject updates/deletes through the model, but DB-level operational immutability is not established.

## Reporting and prices

Reports support price, quantity, quality and traceability categories, optional batch context, and authenticated owner identity. Price is required only for price concerns. Private images have random names and trusted MIME-derived extensions. Only the report owner and authority/admin reviewers can fetch evidence. Public tracking shows a safe summary without narrative, contact details, images or moderator notes. Submission throttling and duplicate detection reduce repeat reports.

Authority moderation locks the report. A verified price report updates one uniquely linked price observation. Rejecting or marking it duplicate withdraws that observation; re-verifying restores the same sample. Other report categories never create price samples.

Reference prices compare crop, district, exact Bangladesh calendar date, source and unit using one shared service. Fewer than three distinct verified samples produce no indicative reference; three/four use a median; five or more use a 20% trimmed mean. Demo, reviewed survey and reviewed complaint sources remain separate. Moderation preserves demo report provenance. Public analytics groups comparable daily samples into 7/14/30-day histories with explicit gaps, latest-two-day comparisons and highest/lowest dates.

## Farmer profiles and showcase data

Enrollment applications need public-profile consent and admin review before a profile is generated. Contact details remain private. Crop declarations are separate from recorded harvest/sale quantities; units are never silently combined. One rating per eligible account/farmer is moderated before publication, with separate middleman/shop and public summaries. Optional old farmer account routes remain compatible; profiles are the main entry.

`showcase:populate` adds 10 synthetic lot scenarios, 18 transactions, 10 proposals, 8 private generated agreement fixtures, 12 report examples and 1,470 daily analytics samples (before user demo actions). It preserves completed workflow actions and existing credentials. Population is local/test-only; the showcase page and role sign-in shortcuts are removed. All website roles sign in normally with passwords.

## Boundaries

No split/merge lot inventory or loss ledger is implemented. GPS is optional user-supplied location, not continuous tracking. SMS is the existing mock/log adapter. External map tiles and frontend CDNs require connectivity. Timestamps remain stored in UTC and are displayed in Asia/Dhaka; historical values were not shifted.

## 2026-10-13 — district marketplaces and lifecycle safeguards

`marketplaces` belongs to a district; a retailer's registered marketplace determines destination lookup. `retail_prices` is append-only through the application: quoted price/unit/time and retailer/batch/market with a unique actor/request key. Quotes do not move goods, deduct stock, create customer transactions or assert payment. Public timelines retain custody receipts through the retailer and exclude historical customer sale entries; latest acquisition price is labelled separately from shop asking price.

`correction_requests` reserves a lot for latest-transfer, counterparty-approved reversal while keeping original voided history. `review_assignments` scopes private reviewer cases and aggregates, with participant conflict checks. `farmer_access_requests` stores supervised verification notes and hashed expiring single-use codes; a bounded farmer session can read own agreements, request correction and withdraw publication. New traders require explicit capability approval, separate from badges. Consent records, per-unit profile totals, status versions and evidence budgets address the supplied bug review; full matrix and recovery approach: [review remediation](REVIEW_REMEDIATION_2026-10-13.md).

Authority graphs compare independent custody-stage groups over recorded dates for the selected crop/unit and assigned districts. They exclude voided transactions, historical customer sales and units pending review. Horizontal Price/Markup views use separate scales; exact arithmetic averages, ranges and record counts are available below. Null acquisition-based markup stays missing. These comparisons do not represent a single batch trajectory or a reviewed survey market reference; demo/legacy coverage is labelled.
