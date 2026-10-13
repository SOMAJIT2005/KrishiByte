# Showcase KrishiByte

Start Wampserver, then double-click `Showcase-KrishiByte.cmd` in the project folder. The launcher checks MySQL, reuses the local website or starts a hidden PHP development server, and opens http://127.0.0.1:8000/demo-showcase. It does not reset or seed the database, change credentials, or expose the website to the internet.

The page offers seven role shortcuts and a five-minute walkthrough. Shortcuts use POST with CSRF protection and only work on localhost in the local environment. Administrative account/rule changes require recent password authentication using the current account password.

To populate the examples again, double-click `Populate-Showcase.cmd` or run `php artisan showcase:populate`. It adds missing workflow examples and refreshes the synthetic 14-day price window without resetting the application or overwriting completed showcase actions. It runs only in local/test environments. Migrations and the base role/crop/district seeders must already be installed. It does not reset passwords, grant new privileged accounts, or submit real commerce.

The showcase page includes expandable instructions for every role and links to ten prepared scenarios. Analytics: choose **Illustrative demo samples**, **Chattogram**, **14 days** for a consistent presentation. All seven crops have five daily samples per district in Dhaka, Bogura and Chattogram: 1,470 generated observations. Older demo samples remain, so the all-district totals may be larger. The current real-survey view honestly remains empty until real reviewed observations exist.

Suggested 10-minute presentation:

| Role | Demonstration |
|---|---|
| Public | Analytics: Potato rising, Onion falling, Brinjal stable, Chili spike/drop. Show latest/lowest/highest dates, chart and daily sample table; switch district/product. |
| Farmer | Farmers → enrollment → enter synthetic details/crops and profile consent. Admin review publishes a profile. Show crops, quantities, sales and rating report. No farmer dashboard/login is needed. |
| Collector | Record farmer purchase: Rahim, Potato, 10kg, BDT21/kg, witness and demo-agreement.png. Read-aloud checkbox → BDT210 collector-attested receipt/private agreement. Propose the whole lot to Hasan (wholesale). |
| Wholesale | Review proposals → accept KB-DEMO-PENDING (150kg). Custody changes on acceptance. Propose the lot onward to Rafiq (retail), or show disputed/cancelled cases without moving their stock. |
| Retail | Accept the wholesaler's proposal. KB-DEMO-RETAIL starts at 450kg; sell 5kg at BDT76 with no customer selected → 445kg. KB-DEMO-SOLD-OUT shows zero stock. |
| Consumer | Submit a farmer rating and a price/quantity/quality/traceability report. My reports is scoped to the account; preserve the tracking code. Rating stays hidden until moderation. |
| Authority | Review KB-DEMO-HIGH-MARKUP flags and RPT-DEMO-PRICE-PENDING. Set reviewing/cleared/escalated with notes, or verify/reject reports. Demo price reports remain demo observations; non-price reports never create prices. |
| Admin | Review enrollment and ratings; publish with reasons. Grant/revoke participant badges, review batches/rules and inspect audit records. Sensitive actions require the current password and then resubmission of the action. |

Prepared lots: KB-DEMO-COLLECTOR (250kg), WHOLESALE (200kg), RETAIL (450kg remaining), HIGH-MARKUP (100kg), PENDING (150kg), DISPUTED (120kg), CANCELLED (80kg), SOLD-OUT (0kg), FARM-READY (200kg), REJECTED (60kg, private admin view). All names are prefixed `KB-DEMO-`.

The fixture `docs/screenshots/demo-agreement.png` is explicitly synthetic and matches the 10kg Potato / BDT21 example. A real purchase needs a matching handwritten agreement photo, not this fixture. For another demonstration purchase, adjust the image/terms together. Farmer agreements are collector attestations; uploaded images do not independently authenticate farmer consent, signatures or payments. Middlemen/shop handoffs still require recipient acceptance. Proposals expire after 48 hours.

Local seeded credentials (use only in the local demo): admin@krishibyte.local / AdminPass123!; authority@krishibyte.local / AuthorityPass123!; farmer.rahim@krishibyte.local / FarmerPass123!; collector.karim@krishibyte.local / CollectorPass123!; wholesale.hasan@krishibyte.local / WholesalePass123!; retail.rafiq@krishibyte.local / RetailPass123!; consumer.tariq@krishibyte.local / ConsumerPass123!. Existing changed passwords are preserved by population. Role shortcuts are the easiest way to switch; admin sensitive actions still ask for the current password.

To verify without opening a browser, run `tools/Start-Showcase.ps1 -NoBrowser` or `tools/Populate-Showcase.ps1 -NoBrowser`. Local scripts must be permitted by your normal Windows execution policy; these files do not override it. Website port defaults to 8000; MySQL port is 3306. `-Port 8011` is available for an isolated launcher check. Logs are in `storage/logs`. This is a source-project launcher, not a standalone installer or production server.
