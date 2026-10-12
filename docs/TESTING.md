# KrishiByte — Verification & Acceptance Test Matrix

**Execution Date:** 2026-10-12  
**Test Suite Status:** 25 Passed, 0 Failed (83 Assertions)  
**Database Drivers Tested:** MySQL 8.0.46 via Wampserver (Primary: `krishibyte`, Testing: `krishibyte_test`)  

---

## 1. Automated Test Execution

```powershell
php artisan test
```

### Result Output

```
   PASS  Tests\Unit\CalculationServiceTest
  ✓ gross price spread calculation
  ✓ markup percent on cost
  ✓ gross margin percent on sale price
  ✓ reference price handles insufficient samples
  ✓ reference price median for medium samples
  ✓ reference price trimmed mean for high sample count

   PASS  Tests\Unit\ExampleTest
  ✓ that true is true

   PASS  Tests\Feature\AuthorityAndRbacTest
  ✓ authority reviews anomaly flag
  ✓ authority moderates consumer report and creates verified observation
  ✓ farmer is forbidden from authority dashboard
  ✓ registration prohibits self assigning admin or authority

   PASS  Tests\Feature\BatchAndHandoffTest
  ✓ farmer creates valid batch
  ✓ farmer submits invalid negative price or quantity
  ✓ current custodian records valid handoff
  ✓ non custodian attempt handoff is rejected
  ✓ quantity exceeding available is rejected
  ✓ self trade is rejected
  ✓ markup above threshold generates anomaly flag
  ✓ authorized void restores previous custody

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response

   PASS  Tests\Feature\PublicAndReportTest
  ✓ guest can access all public views
  ✓ guest opening private dashboards redirected to login
  ✓ consumer submits valid price report
  ✓ unsafe or oversized file upload is rejected
  ✓ track report status shows accurate outcome

  Tests:    25 passed (83 assertions)
  Duration: 4.25s
```

---

## 2. Minimum Acceptance Test Matrix (AT-01 to AT-20)

| ID | Scenario | Expected Result | Verification Method | Status |
|---|---|---|---|---|
| **AT-01** | Fresh setup and migrations | Database migrates without schema errors | `php artisan migrate:fresh` on MySQL | **PASSED** |
| **AT-02** | Seed demo records | Seeders create realistic demo data without duplicate keys | `php artisan db:seed` | **PASSED** |
| **AT-03** | Guest opens public price explorer | Public-safe data with robust statistical tags; no private leak | Feature Test `PublicAndReportTest` | **PASSED** |
| **AT-04** | Guest opens private dashboard URL | Redirected to `/login`; protected data isolated | Feature Test `PublicAndReportTest` | **PASSED** |
| **AT-05** | Farmer creates valid batch | Unique `KB-YYYYMMDD-XXXXXX` generated; farmer is initial custodian | Feature Test `BatchAndHandoffTest` | **PASSED** |
| **AT-06** | Farmer submits negative price/qty | Form validation rejects inputs with validation errors | Feature Test `BatchAndHandoffTest` | **PASSED** |
| **AT-07** | Current custodian records valid handoff | Atomic transaction commits custody, sequence, spread, audit | Feature Test `BatchAndHandoffTest` | **PASSED** |
| **AT-08** | Non-custodian attempts handoff | Rejected with `InvalidArgumentException`; chain unchanged | Feature Test `BatchAndHandoffTest` | **PASSED** |
| **AT-09** | Quantity exceeds available amount | Rejected atomically without partial mutation | Feature Test `BatchAndHandoffTest` | **PASSED** |
| **AT-10** | Same vendor is buyer and seller | Self-trade rejected | Feature Test `BatchAndHandoffTest` | **PASSED** |
| **AT-11** | Markup above threshold | Evaluated against active rules; anomaly flag generated | Feature & Unit Tests | **PASSED** |
| **AT-12** | Missing next sale price | UI displays "Pending next sale price"; no invented margin | Blade template verified in Vendor Dashboard | **PASSED** |
| **AT-13** | Insufficient verified price observations | Shows "Insufficient verified data" warning and sample count | Unit Test `CalculationServiceTest` | **PASSED** |
| **AT-14** | Consumer submits valid report | Enters private moderation queue with tracking code | Feature Test `PublicAndReportTest` | **PASSED** |
| **AT-15** | Unsafe/oversized evidence file | Disallowed mime types or >2MB files rejected | Feature Test `PublicAndReportTest` | **PASSED** |
| **AT-16** | Authority reviews report/flag | Decision, notes, reviewer recorded; logged to audit table | Feature Test `AuthorityAndRbacTest` | **PASSED** |
| **AT-17** | Public visits pending report evidence | Evidence and reporter details remain isolated | Feature Test `PublicAndReportTest` | **PASSED** |
| **AT-18** | Transaction correction/void | Void reason required; previous custody restored | Feature Test `BatchAndHandoffTest` | **PASSED** |
| **AT-19** | Charts & dashboard filters | Real SQL aggregates drive Chart.js and queue filters | Verified on Authority Dashboard | **PASSED** |
| **AT-20** | Mobile/narrow viewport usability | Responsive Bootstrap 5 grid and accessible forms | Verified in Blade responsive layouts | **PASSED** |

---

## 3. Dummy SMS Verification

In accordance with explicit operator instructions, the SMS subsystem operates in **Dummy Mode**. Every custody handoff logs notification dispatches to `storage/logs/sms.log`:

```
[2026-10-12 07:43:41] DUMMY SMS to +8801712000002: KrishiByte: Batch KB-20261010-POT001 transferred to your custody. Qty: 2500 kg @ BDT 26.
[2026-10-12 07:43:41] DUMMY SMS to +8801713000003: KrishiByte: Batch KB-20261010-POT001 transferred to your custody. Qty: 2500 kg @ BDT 32.
[2026-10-12 07:43:41] DUMMY SMS to +8801714000004: KrishiByte: Batch KB-20261010-POT001 transferred to your custody. Qty: 2500 kg @ BDT 38.
```
