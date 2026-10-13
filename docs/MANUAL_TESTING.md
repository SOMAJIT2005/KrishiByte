# Manual feature testing — normal website

The showcase page and one-click role logins are removed. Open `http://127.0.0.1:8000/` and sign in through `/login`. Start WAMP MySQL, then the existing desktop launcher (its legacy Showcase filename now opens the normal homepage).

## Test data

The running local database has non-destructively populated manual fixtures. For a separate installation, import [schema and data](../database/sql/README.md) into a new empty DB, or run `php artisan migrate` and `php artisan manual:populate` with APP_ENV=local. Never run fixture population or import synthetic credentials on production. Reruns preserve existing passwords, stocks and completed decisions; the extra fixtures do not reset user actions.

All 28 tables have sample rows. The isolated SQL snapshot contains 103 users, 174 batches, 503 transactions, 326 proposals, 1,486 price observations, 39 markets, 316 retail quotes, 4 corrections, 3 farmer access requests, 3 enrollments and 3 ratings. Exact counts are in `database/sql/manifest.json`; the working database may have additional records from your own testing.

## Sign-in accounts

| Role | Email | Initial synthetic password |
|---|---|---|
| Admin | admin@krishibyte.local | AdminPass123! |
| Authority | authority@krishibyte.local | AuthorityPass123! |
| Collector | collector.karim@krishibyte.local | CollectorPass123! |
| Wholesaler | wholesale.hasan@krishibyte.local | WholesalePass123! |
| Retailer | retail.rafiq@krishibyte.local | RetailPass123! |
| Consumer | consumer.tariq@krishibyte.local | ConsumerPass123! |
| Optional farmer account | farmer.rahim@krishibyte.local | FarmerPass123! |
| Pending / suspended / verified collector | manual.pending@example.test / manual.suspended@example.test / manual.verified@example.test | ManualTest123! |

Changed passwords in an existing installation are preserved. Use a private/incognito window for each role, or sign out before switching. Suspended login should be rejected; pending collectors can sign in but cannot trade until admin approval. Test credentials are not production credentials.

## Workflows and expected results

| Feature / role | Manual steps | Expected result |
|---|---|---|
| Public analytics | `/analytics`, select demo source, product and district; try 7/14/30 days | Potato rises, onion falls, brinjal stays stable, chili spikes/drops; low/high/reference values and sample detail agree. Synthetic samples remain labelled. |
| Markets / public | `/traceability`, select Dhaka then one of its markets | Registered shops and stocked lots appear with origins, asking prices and trace links. Change districts and check market options change. |
| Farmer profile / public | `/farmers`, open Rahim | Declared crops, recorded sales and approved rating appear; phone/agreement remain private. |
| Enrollment / guest + admin | `/farmers/enroll`, submit synthetic farmer/crop/consent; admin `/admin/farmer-review` | Pending enrollment can be approved/rejected; public profile requires consent. Existing fixtures include approved/rejected outcomes. |
| Trading / admin | `/admin/users`, search manual.pending or manual.verified | Review license/district, approve trading, separately grant/revoke a badge with a reason. Suspension prevents trades/login; a badge alone is not trading approval. |
| Market creation / admin | `/admin/marketplaces`, choose district, add a unique synthetic market | It becomes available in retail registration under that district only. |
| Farm purchase / collector | `/vendor/farmer-purchase`, use KB-DEMO-FARM-READY or create a synthetic lot; enter full agreed quantity/price and upload a matching handwritten image, witness/read-aloud attestation | Receipt and private evidence saved; collector sees Received from farmer (collector attested). Existing imported correction origins stay unconfirmed. |
| Handoff / collector | `/vendor/dashboard`, select destination district, recipient role/name, Find recipient; use KB-DEMO-COLLECTOR for a proposal | Whole lot is reserved, custody unchanged until acceptance. Missing/wrong-district/unapproved buyer is rejected. |
| Receive / wholesaler or retailer | `/handoffs`, accept/dispute a current proposal | Acceptance creates one transfer; seller Status becomes Dispatched, buyer sees Received. Dispute/cancellation do not move custody. |
| Repeat intermediaries | Collector/wholesaler workspace: choose another collector/wholesaler | Registered district-scoped recipient is offered; origin farmer and whole-lot quantity remain intact. |
| Retail pricing | `/retail/prices`, inspect Received goods, set asking price for stocked lot | New quote appears in public market/journey. No customer handoff, payment claim or stock deduction. |
| Corrections / collector + wholesaler | `/corrections`, inspect KB-MANUAL-CORRECTION-PENDING; recipient accepts/disputes, requester may cancel | Acceptance voids latest transfer/restores prior custody. Other fixtures show disputed/cancelled/accepted outcomes; older-than-latest correction is blocked. |
| Reports / all roles | `/report`, submit synthetic concern; track its code at `/report/track`; authority `/authority/dashboard` moderates | Tracking preserves privacy; price reports link exactly one observation when verified. Fixtures cover price/quantity/quality/traceability and pending/verified/rejected. |
| Authority / assigned reviewer | Dashboard product filters and separate price/markup charts; review KB-DEMO-HIGH-MARKUP | Price units match; flags show review evidence, not accusations. Participant conflict/district scope remains enforced. |
| Ratings / consumer or trader | Farmer profile, post a rating; admin moderates at farmer review | Pending/rejected reviews are not public; approved fixture is visible; one rating per account/farmer. |
| Assisted farmer access | `/farmer-access`, request access; admin review must independently verify synthetic contact in the manual scenario | Request alone grants no access. Approved fixture code is expired. Issue a new code through admin to test bounded own-agreement access/correction/withdrawal. |
| Privacy / signed-out or unrelated user | Attempt another farmer's private agreement URL, staff page or correction decision | Access is denied; exact GPS, trade IDs and private evidence are not disclosed publicly. |

Proposals are time-limited; old SQL imports may have expired examples. Create a fresh proposal instead of editing timestamps or resetting a completed lot. The saved `docs/screenshots/demo-agreement.png` matches only the documented synthetic 10kg/BDT21 example; use a new matching handwritten image for different terms. Private SQL agreement images reproduce existing fixture receipts, not authenticity of real contracts.

## Resetting a manual test environment

Create another empty test DB and import the two SQL files; keep the existing working DB intact. The exported data is a reproducible starting snapshot, not a migration overlay. Expired session/reset/cache and quarantined job examples exist only to cover framework tables; they are not user-facing workflows and must not be executed/retried.
