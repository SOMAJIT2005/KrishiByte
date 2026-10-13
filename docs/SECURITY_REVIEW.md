# Local implementation review — 13 October 2026

This records implementation against the supplied consolidated review plan. The user's later request changes farm-gate confirmation to a collector's attestation with a private handwritten agreement. Documents supplied background findings; they did not authorize external deployment, real transactions, messages, key rotation or deletion of existing data.

## Findings and decisions

| Finding | Implemented behavior / remaining boundary |
|---|---|
| R01 / R04 | Shared comparable-price query: exact Bangladesh calendar day, crop, unit, source and optional district. Analytics has bounded 7/14/30-day history. Unsupported historical units retain original labels and require review. No quantity conversion or stale fallback. |
| R02 | Middlemen/shop custody proposals require recipient acceptance; disputes/cancellations/expiry do not change custody. Farmer purchases use collector-attested terms, read-aloud attestation, witness and a private agreement image, per the user's revised workflow. This is not independent farmer confirmation. Legacy imports retain `legacy_unconfirmed`. |
| R03 | Public trace hides retail consumer names and precise GPS/farm addresses. Contacts, identity identifiers and agreement images stay private. Farmer profiles publish name, district, declared crops, recorded quantities and moderated ratings. |
| R05 / R06 | Markup and absolute-spread rules use their own metrics, relative-excess severity, inclusive effective dates and stored rule snapshots. Zero caps remain zero. Flags request review, not guilt findings. |
| R07 | Retail sale audit retains the retailer as custodian. Anonymous customers need no account. Notifications distinguish sales and custody, run after commit, and mask logged phone numbers. SMS is still a local mock. |
| R08 | References state sample sufficiency, market/contributor counts and method. At least 3 distinct samples are required; counts do not prove independence or accuracy. Demo and citizen complaint sources are separate from real market surveys. |
| R09 | Unreviewed lots may trade and appear publicly with an explicit review label. Rejected lots are blocked from trading and hidden publicly. Account badges and batch verification are distinct. No claim that every displayed lot is verified. |
| R10 / R11 | Registration/login/demo login rotate sessions. Named endpoint limits separate registration, login, tracking, reports, evidence, decisions, analytics, enrollment and ratings. Login is limited by client and account/IP; attachment quotas are applied separately. |
| R12 | Exact role gates replace blanket admin bypass. Admin governance actions are explicit; admin page access does not grant arbitrary farmer/vendor business actions. Sensitive account/rule/enrollment/rating changes require recent password authentication. Suspended sessions are logged out on web requests. |
| R13 | Rule input preserves zero spread caps, validates dates and amounts, and writes changes with audit records atomically. |
| R14 | Model-level audit updates/deletes are rejected. These are application audit records; raw SQL/DB administrators can still alter them. Operational immutability is not established. |

## Additional implementation

- Farm purchase, proposal acceptance, retail stock mutation, flags and audit records are transactional. Unique actor/request keys reject changed-payload retries and prevent duplicate transactions. Pending proposals snapshot their unit.
- Private image evidence is decoded, limited to 2MB, 4096 pixels per side and 12 megapixels, then re-encoded to strip metadata. The original SHA-256 and transformation description are recorded. Failed writes/transactions remove newly created orphan files. The original photograph is not retained or claimed to be untouched evidence.
- NID/trade identifiers use Laravel encrypted casts, including existing values migrated with the current APP_KEY. User serialization hides credentials, identifiers and contacts.
- Successful web responses set basic security headers. Authenticated responses are private/no-store. This is not a complete nonce-based CSP or proof of exception-response/header behavior at a reverse proxy.
- Demo login is POST/CSRF protected and requires local environment plus loopback host and client address. Local showcase population is disabled in production. Seeded reports retain `is_demo` when moderated into observations.
- Farmer enrollment needs no email/password. Admin approval generates a profile with private contact data and an inaccessible random login credential. Optional legacy farmer accounts/routes remain for compatibility; the normal entry and navigation now lead to profiles.
- Middlemen/shops and public consumer accounts can submit one rating per account per farmer. Only approved ratings enter the public summary; edits return to moderation. Ratings do not establish identity uniqueness or a verified purchase.

## Verified locally

- Full automated suite: 59 tests / 395 assertions on the normal test DB and independent security QA DB; these include the original regression tests and new provenance, authorization, upload, session, rate-limit, agreement, rating, analytics and seeding checks.
- Real concurrent MySQL processes: duplicate acceptance creates one transaction; competing 60kg sales on 100kg do not oversell; duplicate retail sale deducts once.
- Backup/restore compared schemas and exact ordered rows on 19 tables before hardening and 23 tables after farmer workflow changes, in isolated restore DBs. Sources, matching environment/key and private evidence were backed up outside the project.
- Composer audit found no locked-package advisories/abandoned packages. npm audit could not run because the project has no npm lockfile; no zero-vulnerability claim is made for frontend/CDN assets.
- Local browser evidence includes a collector-attested receipt, moderated profile rating, admin password confirmation, anonymous retail sale, source/district/product filters, high/low price history and mobile layout.

## Production and roadmap work still required

The local prototype is not a production security certification. Before external deployment: privileged MFA and recovery, HTTPS/proxy/session configuration, least-privilege database credentials, production debug/secret review, matching-key backup protection and rotation procedure, off-host encrypted restore exercises, storage-wide quotas and monitoring, stronger abuse controls/identity review, complete dependency/CDN integrity handling, deployment headers/CSP and independent security testing. Local backups are not encrypted or off-host. Prior secrets in Git history are not erased by ignoring `.env`; no key rotation/history rewrite was performed.

Splitting/merging/loss accounting needs a lineage and inventory model. Real payment verification, case management, QR exports, jurisdiction/institution assignment, fully bilingual/voice-assisted onboarding and independent farmer consent verification remain future work. The current reviewer scope is the local national pilot. One-account ratings still permit multi-account abuse without additional identity controls.

## 2026-10-13 — active permissions and retail pricing

See [review remediation](REVIEW_REMEDIATION_2026-10-13.md) for B01-B12, role matrix and migration/recovery details. It supersedes earlier global reviewer/private-evidence and customer-sale descriptions. Reviewer case/evidence access requires a district assignment and no participant conflict; administrator privileges do not confer trader impersonation. Consent/withdrawal, assisted farmer access, counterparty corrections, explicit status versions and bounded evidence storage are implemented.

Retailers now post asking prices for received goods, without customer handoffs or stock deductions. Registration requires matching district/market/shop/license and separate trading approval. Earlier retail sales remain private historical records and legacy stock tests; they are omitted from public custody timelines. Deployment limitations listed above still apply.
