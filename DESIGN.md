---
name: Kinetra Admin
description: Utilitarian industrial quote-to-cash and operations workspace built for clarity, tabular precision, and restrained cobalt emphasis.
colors:
    cobalt-primary: 'oklch(0.598 0.166 255.5)'
    cobalt-primary-dark: 'oklch(0.674 0.176 254.6)'
    destructive-red: 'oklch(0.577 0.245 27.325)'
    canvas-white: 'oklch(1 0 0)'
    sidebar-paper: 'oklch(0.985 0 0)'
    surface-muted: 'oklch(0.97 0 0)'
    hairline-border: 'oklch(0.922 0 0)'
    ink-muted: 'oklch(0.556 0 0)'
    ink-secondary: 'oklch(0.205 0 0)'
    ink-primary: 'oklch(0.145 0 0)'
    dark-canvas: 'oklch(0.145 0 0)'
    dark-sidebar: 'oklch(0.205 0 0)'
    dark-surface: 'oklch(0.269 0 0)'
    dark-ink-muted: 'oklch(0.708 0 0)'
    dark-ink-primary: 'oklch(0.985 0 0)'
    chart-quantity-rose: 'oklch(0.656 0.212 354.3)'
    chart-emerald: 'oklch(0.696 0.17 162.48)'
    chart-amber: 'oklch(0.828 0.189 84.429)'
    chart-ochre: 'oklch(0.769 0.188 70.08)'
typography:
    display:
        fontFamily: "'Instrument Sans', ui-sans-serif, system-ui, sans-serif"
        fontSize: '2.25rem'
        fontWeight: 600
        lineHeight: 1.1
        letterSpacing: '-0.025em'
    headline:
        fontFamily: "'Instrument Sans', ui-sans-serif, system-ui, sans-serif"
        fontSize: '1.25rem'
        fontWeight: 600
        lineHeight: 1.3
        letterSpacing: '-0.025em'
    title:
        fontFamily: "'Instrument Sans', ui-sans-serif, system-ui, sans-serif"
        fontSize: '1rem'
        fontWeight: 600
        lineHeight: 1.25
    body:
        fontFamily: "'Instrument Sans', ui-sans-serif, system-ui, sans-serif"
        fontSize: '0.875rem'
        fontWeight: 400
        lineHeight: 1.5
    label:
        fontFamily: "'Instrument Sans', ui-sans-serif, system-ui, sans-serif"
        fontSize: '0.75rem'
        fontWeight: 600
        lineHeight: 1.33
        letterSpacing: '0.025em'
rounded:
    sm: '6px'
    md: '8px'
    lg: '10px'
    xl: '12px'
    full: '9999px'
spacing:
    xs: '4px'
    sm: '8px'
    md: '16px'
    lg: '24px'
    xl: '32px'
    section: '56px'
components:
    button-primary:
        backgroundColor: '{colors.cobalt-primary}'
        textColor: '{colors.sidebar-paper}'
        typography: '{typography.body}'
        rounded: '{rounded.md}'
        height: '36px'
        padding: '8px 16px'
    button-outline:
        backgroundColor: '{colors.canvas-white}'
        textColor: '{colors.ink-primary}'
        typography: '{typography.body}'
        rounded: '{rounded.md}'
        height: '36px'
        padding: '8px 16px'
    button-secondary:
        backgroundColor: '{colors.surface-muted}'
        textColor: '{colors.ink-secondary}'
        typography: '{typography.body}'
        rounded: '{rounded.md}'
        height: '36px'
        padding: '8px 16px'
    button-destructive:
        backgroundColor: '{colors.destructive-red}'
        textColor: '{colors.canvas-white}'
        typography: '{typography.body}'
        rounded: '{rounded.md}'
        height: '36px'
        padding: '8px 16px'
    card:
        backgroundColor: '{colors.canvas-white}'
        textColor: '{colors.ink-primary}'
        rounded: '{rounded.xl}'
        padding: '24px'
    input:
        backgroundColor: '{colors.canvas-white}'
        textColor: '{colors.ink-primary}'
        typography: '{typography.body}'
        rounded: '{rounded.md}'
        height: '36px'
        padding: '4px 12px'
    stat-card:
        backgroundColor: '{colors.canvas-white}'
        textColor: '{colors.ink-primary}'
        rounded: '{rounded.xl}'
        padding: '16px'
---

# Design System: Kinetra Admin

## Overview

**Creative North Star: "The Precision Dispatch Desk"**

Kinetra Admin is built like a well-kept industrial sales counter where every quotation, purchase order, stock level, and invoice reads clearly without visual noise. Surfaces stay neutral and flat by default so numbers, part codes, and workflow statuses carry the hierarchy.

A single industrial cobalt primary anchors actions, active navigation, and price trends against white and deep charcoal canvases. Semantic color stays strictly functional—reserved for preserved production status badges, validation states, and chart series.

**Key Characteristics:**

- High-contrast light and dark OKLCH canvases (`resources/css/app.css`).
- Hairline 1px borders and tonal surfaces instead of heavy drop shadows.
- `Instrument Sans` with system monospace part/document codes and tabular numerals.
- Preserved production status palette (`resources/js/lib/status.ts`) with inset rings and text labels.
- Cohesive transition across dense admin screens, public portfolio pages, and A4 print sheets.

## Colors

An achromatic neutral ramp paired with one industrial cobalt accent; normative OKLCH tokens live in `resources/css/app.css` and the frontmatter above.

### Primary

- **Industrial Cobalt** (`cobalt-primary`) / **Luminous Cobalt** (`cobalt-primary-dark`): Primary CTAs, brand mark tiles, active chart line, 50%-opacity focus rings, active links, and subtle 5%-opacity row/banner highlights.

### Secondary

- **Signal Crimson** (`destructive-red`): Destructive actions, cancellations, and invalid-field states.
- **Chart Series Accents** (`chart-quantity-rose`, `chart-emerald`, `chart-amber`, `chart-ochre`): Reserved strictly for Recharts encodings.

### Neutral

- **Light Ramp** (`canvas-white`, `sidebar-paper`, `surface-muted`, `hairline-border`, `ink-muted`, `ink-secondary`, `ink-primary`): Clean workspace sheet framed by an off-white inset sidebar, muted table headers, and 1px structural borders.
- **Dark Ramp** (`dark-canvas`, `dark-sidebar`, `dark-surface`, `dark-ink-muted`, `dark-ink-primary`): Mirrored charcoal ladder for dark mode.

### Named Rules

**The One Cobalt Voice Rule.** Industrial Cobalt is reserved for primary actions, active selections, links, and the brand mark. Never wash full sections in decorative gradients or competing accents.

**The Semantic Status Rule.** Status badges in `resources/js/lib/status.ts` (red, amber, slate, violet, emerald, sky, zinc) carry fixed production meanings and always pair color with an uppercase label and 1px inset ring; never invent ad-hoc status colors.

## Typography

**Display & Body Font:** `Instrument Sans` (weights 400, 500, 600 via `vite.config.ts`; fallback: system sans-serif)
**Mono Font:** System monospace for part numbers, document numbers, and print codes.

### Hierarchy

- **Display** (600, 36px–48px, line-height 1.1, tracking -0.025em): Public landing hero (`resources/js/pages/welcome.tsx`).
- **Headline** (600, 20px admin headers; 30px secondary public pages, line-height 1.3): Primary screen titles.
- **Title** (600, 16px, line-height 1.25): Card, section, and dialog titles.
- **Body** (400–500, 14px admin; 16px–18px public prose, line-height 1.5): Tables, forms, and descriptions.
- **Label** (600, 12px, line-height 1.33, tracking 0.025em, uppercase): Table headers, status badges, and kickers.

### Named Rules

**The Tabular Figures Rule.** Every currency amount (IDR), stock count, quantity, KPI value, and pagination counter uses tabular numerals so columns align vertically.

**The Three-Weight Ceiling Rule.** Stay within weights 400, 500, and 600 of `Instrument Sans` across screen UI (700 appears only on A4 print headers).

## Layout

Admin pages use `resources/js/layouts/app/app-sidebar-layout.tsx`: a collapsible inset sidebar beside a rounded content sheet topped by the sandbox banner and breadcrumbs (24px vertical section rhythm; 16px mobile / 24px desktop padding). KPI strips use 2-column mobile / 4-column desktop grids (12px gap); detail screens (`resources/js/pages/dashboard.tsx`, `resources/js/pages/inquiries/show.tsx`, `resources/js/pages/quotations/show.tsx`) split 2:1 on desktop. Public pages center at 1152px, 896px, or 768px; print sheets lock to A4 (210mm width, 14mm margins).

## Elevation & Depth

Surfaces are flat and 1px-border-driven by default; grouping relies on hairline dividers and tonal contrast rather than heavy shadows.

- **Control Whisper** (`box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05)`): Buttons and text inputs.
- **Card Resting Edge** (`box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)`): Cards and active tabs.
- **Interactive Lift** (`box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)`): Hover-only lift on linked stat cards and floating tooltips.

**The Flat-By-Default Rule.** Non-interactive panels, tables, and nested rows never cast elevated shadows; elevation increases only on hoverable cards or transient overlays.

## Shapes

Derived from a 10px base radius: controls (buttons, inputs, badges) use 8px corners; structural containers (cards, table frames, dialogs) use 12px corners; pills (9999px) serve small indicators and eyebrow tags; print sheets tighten to 4px or square rules.

## Components

- **Buttons:** 8px radius, 36px height (32px small, 40px large, 36px square icon), 150ms transition, 3px cobalt focus ring at 50% opacity. Variants: Industrial Cobalt primary, hairline outline, muted secondary, ghost, and Signal Crimson destructive.
- **Status Badges (`resources/js/components/status-badge.tsx`):** 6px–8px corners, 2px 8px padding, 12px uppercase semibold text, soft semantic tint, 1px inset ring; paired with filter pill counts on list pages.
- **Cards & Stat Cards:** 12px corners, 1px hairline border, resting edge shadow, 24px padding (16px on stat cards; flush body for hairline-divided lists). Linked stat cards lift 2px on hover.
- **Inputs:** 36px height, 8px radius, 1px border, 12px horizontal padding, 14px desktop / 16px mobile text, cobalt focus halo, Signal Crimson invalid ring.
- **Data Tables (`resources/js/components/data-table/data-table.tsx`):** 12px bordered card, 40%-opacity muted header strip, 12px uppercase sortable toggles, hairline row dividers, debounced search, tabular pagination footer.
- **Navigation & Print:** Collapsible inset sidebar (`Overview`, `Sales`, `Master data`, `Catalog`) with live inquiry badge; 56px hairline public header; fixed 210mm white A4 print sheet (`resources/js/pages/print/document.tsx`) with faint diagonal demo watermark and print-hidden toolbar.

## Do's and Don'ts

### Do:

- **Do** use semantic CSS custom properties from `resources/css/app.css` so light and dark modes stay synchronized.
- **Do** apply tabular numerals to all rupiah figures, quantities, KPI counts, and pagination indicators.
- **Do** route all workflow statuses through `resources/js/components/status-badge.tsx` and `resources/js/lib/status.ts`.
- **Do** preserve keyboard accessibility, visible focus rings, and reduced-motion safety on interactive controls.
- **Do** keep 1px borders and flat surfaces as the primary grouping mechanism across admin screens.

### Don't:

- **Don't** introduce decorative gradients, neon dark-mode glows, or competing brand accents alongside Industrial Cobalt.
- **Don't** nest shadowed cards inside cards; use hairline dividers or subtle muted bands instead.
- **Don't** hard-code hex colors in React components outside the A4 print sheet (`resources/js/pages/print/document.tsx`).
- **Don't** omit dark-mode pairings when using semantic status or icon-well tints.
- **Don't** fabricate real company claims, customer logos, or unverified metrics.
