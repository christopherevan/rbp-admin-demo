# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Primary evaluators (portfolio demo visitors):** hiring managers, engineering leads, and technical peers exploring the live sandboxed demo to evaluate architecture, domain modeling, and frontend craft. They switch between Admin and Sales roles, read the per-page "About this feature" notes, mutate records freely, and reset their copy at will.
- **Operational personas (modeled workflows):** sales representatives and operations/finance administrators at an industrial parts distributor handling quotations, customer purchase orders, sales orders, deliveries, invoicing, web inquiry triage, inventory adjustments, and human-in-the-loop AI product copy review.

## Product Purpose

Kinetra Admin is a working, sandboxed replica of a production sales and order-management system for an industrial parts distributor. Every visitor receives a private throwaway SQLite copy seeded by replaying four months of dated fictional events through the real workflow services. Success means two things at once: operational screens feel fast, dense, trustworthy, and authentic to a daily sales desk, while public pages clearly frame the demo, its architecture, and how it compares to production.

## Positioning

Unlike static UI mockups or shared demo accounts that break when two visitors click at once, Kinetra Admin isolates every visitor in a per-cookie SQLite database driven by the real server-side state machine (`QuotationWorkflow`, `InquiryWorkflow`), authorization policies, atomic daily document numbering, stock-deducting delivery orders, and a rule-based AI copy guard (`DescriptionGuard`).

## Operating Context

- **Admin workspace (Operate):** desktop-first daily operational tool that remains usable on tablets and narrow screens. Core tasks include scanning pipeline counts, charting part price/quantity history, triaging web inquiries, advancing a single quotation from draft to paid cash, managing server-side paginated tables, and reviewing AI-drafted catalog descriptions with `J`/`K`/`A`/`R` keyboard shortcuts.
- **Printable business documents:** A4 browser-printable quotation, delivery order, invoice, and down-payment invoice views (`resources/js/pages/print/document.tsx`).
- **Public surfaces (Persuade / Read):** interactive persona launcher (`/`), architecture breakdown (`/about`), and public buyer quote-request form (`/request-quote`) that feeds straight into the visitor's private inquiry inbox.

## Capabilities and Constraints

- **Server-owned workflow & policies:** `QuotationWorkflow` and Laravel policies determine the valid next actions offered in the UI; Sales own their quotations up to customer PO while Admins handle fulfilment, invoicing, payments, inquiry triage, and AI copy approval.
- **Financial & domain precision:** monetary figures are whole Indonesian rupiah (IDR) integers formatted with tabular numerals; document numbers follow `YYYY/CUSTOMER/DDMM-NNN`; quotations use ULID URLs while preserving integer primary keys for audit logs.
- **Preserved production status color coding:** quotation, inquiry, description, and document status badge colors in `resources/js/lib/status.ts` mirror the production system so operators recognize workflow states at a glance.
- **Strict demo honesty:** company names, people, and figures are entirely fictional. Print documents keep the `DEMO · FICTIONAL DATA` watermark, and "About this feature" popovers explicitly distinguish demo behavior from production (e.g., HTML print vs. dompdf versioned PDFs, IDR-only vs. multi-currency).
- **Security & CSP:** production enforces a strict nonce-based Content Security Policy (`app/Http/Middleware/ContentSecurityPolicy.php`), while local development skips CSP for Vite HMR and local tooling.

## Brand Commitments

- **Name:** Kinetra Admin (portfolio demo by Christopher Evan).
- **Mark:** Geometric conveyor belt over two pulleys (`resources/js/components/app-logo-icon.tsx`).
- **Voice:** Direct, candid, and engineering-grounded ("fictional company, real code paths"). Avoid hype, marketing filler, or fabricated enterprise claims.

## Evidence on Hand

- Live seeded demo data replayed through real services (`database/seeders/DemoSeeder.php`).
- Per-page feature documentation and production-vs-demo notes (`resources/js/lib/feature-notes.ts`, `resources/js/pages/about.tsx`).
- Automated Pest test suite covering workflow transitions, totals, policies, AI guard rules, and sandbox isolation (`tests/`).
- **Absences not to fabricate:** there are no real customer logos, real client testimonials, real company metrics, or third-party trust badges in this repository; future UI work must never invent them.

## Product Principles

1. **Operational clarity first:** in admin screens, fast scanability, accurate state visibility, and low-friction task completion always outrank decorative flourish.
2. **Server rules stay visible:** permissions, workflow guards, and AI copy-guard findings explain _why_ an action is available or blocked rather than silently hiding or failing.
3. **Honest portfolio framing:** public pages and feature notes demonstrate craft through working software and transparent architectural trade-offs, never inflated claims.
4. **Print and keyboard parity:** high-frequency operational flows (table filtering, copy review, document printing) remain first-class alongside mouse interactions.

## Accessibility & Inclusion

Target **WCAG 2.1 AA** across both admin and public surfaces: minimum 4.5:1 body text contrast (3:1 for large text and UI components) in light and dark modes, clear keyboard focus visibility, non-color-only state cues (status badges always carry text labels), safe keyboard shortcuts that ignore form inputs and contenteditable regions, and respectful handling of `prefers-reduced-motion`.
