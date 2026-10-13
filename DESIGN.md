# KrishiByte design context

This is a Bangladesh agricultural custody and market-monitoring application. Preserve the existing green/amber identity, readable English labels and Bengali crop names; workflows must remain useful on small laptops and phones.

Canonical runtime tokens live in `resources/views/layouts/app.blade.php` (`--kb-*` variables); `public/css/refined.css` refines shared tables, buttons and cards. Use those sources and Bootstrap semantic utilities rather than new screen-local palettes. Bootstrap Icons remain the icon system. Do not redesign the shell for one feature.

Use semantic tables with visible column headings, bounded pagination, document scrolling and keyboard-accessible horizontal overflow. Movement badges include text so color is supplementary. Receipt/dispatch history is private to its participant; public traceability continues to show the journey without private agreements or exact coordinates.

The shared movement badge is `resources/views/components/movement-status.blade.php`; its business meaning is owned by `Transaction::movementStatusFor`. Governance/confirmation labels must remain distinct from movement labels. See `UX-CONTRACT.md` for state and navigation behavior.
