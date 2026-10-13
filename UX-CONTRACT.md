# KrishiByte workflow contract

- Authenticated Blade screens use `layouts.app`; existing Bootstrap modals own focus trapping, Escape and dismissal. The bundle is served locally to keep those controls usable without a CDN script download.
- Native district/role/date selects remain the established platform-owned controls. Recipient filters apply explicitly with Find recipient and persist in query parameters/pagination. Disabled proposals explain missing eligible buyers or trading approval.
- A proposed transfer does not mean goods were received or dispatched. Acceptance records one transfer atomically; sender sees Dispatched, buyer sees Received. First farm collection shows Received from farmer, with collector-attested qualification when applicable. Legacy unconfirmed and voided records must never look independently confirmed.
- `Transaction::movementStatusFor` owns movement labels; the shared movement-status Blade component renders them. Validation/anomaly flags are separate review states, not delivery states. These are custody records, not live transport tracking or proof of payment.
- Middleman transaction history uses the existing Status column immediately before Action for movement labels. A flagged record shows an additional Flagged for review note beneath it, rather than replacing the movement with Valid/Flagged.
- Retail screens show only incoming receipt history and stock asking prices. No outgoing customer movement or stock deduction is introduced. Retail receipt pagination uses `receipts_page`; quote pagination uses `page`, both retain current query parameters.
- Middleman histories include their incoming and outgoing transactions only. Never expose another account's private records. Empty receipt history directs users to incoming proposals.
- Existing inline server errors and shared session alerts preserve entered fields; no browser alerts or new destructive actions are added by movement labels.
- Text, times and money follow existing English/Bengali crop naming, Asia/Dhaka and BDT conventions. Tables retain natural page height, labelled horizontal scroll regions and visible focus.
