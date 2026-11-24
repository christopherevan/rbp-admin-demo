/**
 * The "About this feature" notes on every page: what the screen does, what
 * the production system does beyond the demo, and what it's built with.
 */
export type FeatureNote = {
    title: string;
    what: string[];
    production?: string[];
    builtWith: string[];
};

export const featureNotes = {
    dashboard: {
        title: 'Operations dashboard',
        what: [
            'Counts of quotations and web inquiries by status; each card opens the filtered list.',
            'The most-quoted parts. Click one to chart every price it was quoted at, and the quantity, over time.',
            'A live feed from the audit log every workflow step writes to.',
        ],
        production: ['Same widgets, over several years of real quotations.'],
        builtWith: [
            'Grouped aggregate queries',
            'Inertia optional props (the chart loads only when asked)',
            'shadcn/ui charts on Recharts',
        ],
    },
    quotations: {
        title: 'Quotation pipeline',
        what: [
            'Every quotation with its status, value and owner. Filter by status, search by number, customer or PO.',
            'Numbers follow the business format YYYY/CUSTOMER/DDMM-NNN from an atomic daily counter.',
        ],
        production: ['Multi-currency lines (IDR, USD, SGD, EUR, RMB).'],
        builtWith: [
            'Server-side pagination, search and sort',
            'TanStack Table',
            'Query-string state, so filtered views can be bookmarked',
        ],
    },
    quotationForm: {
        title: 'Quotation terms',
        what: [
            'Customer and contact, delivery point, discount, PPN, lead time, validity, payment terms and an optional down payment.',
            'Sales reps can only write quotations for themselves; admins can assign them to anyone.',
        ],
        builtWith: ['Inertia useForm', 'Server-side validation', 'Policies'],
    },
    quotation: {
        title: 'Quote-to-cash workspace',
        what: [
            'One page takes an order from first draft to cash: line items → customer PO → sales order → delivery order → invoice → payments.',
            'The "Next step" card comes from the server-side state machine, together with the permission check, so the UI never re-implements the rules.',
            'Sales orders can cover part of a quotation. Invoices bill what was ordered, less any down payment, and snapshot the amount.',
            'Every step writes to the audit trail. Creating a delivery order deducts stock.',
        ],
        production: [
            'PDFs rendered with dompdf. Every print is stored as a numbered version with its SHA-256, with print-time overrides.',
            'Real file uploads for customer POs and payment proofs, on a private disk.',
            'QR-coded inventory labels and a scan-to-fulfil screen for the warehouse.',
        ],
        builtWith: [
            'A QuotationWorkflow service with one method per transition',
            'Policies (Sales vs Admin)',
            'Integer-rupiah totals',
            'DB transactions',
        ],
    },
    inquiries: {
        title: 'Web inquiry inbox',
        what: [
            'Buyers build a quote list on the public catalog and send it here.',
            'Triage: assign a rep (moves it to review), link it to a customer, approve or reject (with a reason), then draft a quotation from it in one click.',
            'The sidebar badge counts new inquiries.',
        ],
        production: [
            'Email verification with hashed, expiring tokens. A remembered-device cookie skips it for returning buyers.',
            'Cloudflare Turnstile, a honeypot, timing checks and per-step rate limits.',
            'A Telegram alert per inquiry and a daily digest for the sales team.',
        ],
        builtWith: [
            'An InquiryWorkflow service',
            'Status filters with counts',
            'Policies',
        ],
    },
    inquiry: {
        title: 'Inquiry triage',
        what: [
            'Requested items are matched to parts where possible, with list price and stock alongside.',
            'If the buyer’s email domain matches an existing customer’s contacts, that customer is suggested.',
            '"Create quotation" drafts one with the matched items at list price, ready for the rep to review.',
        ],
        builtWith: ['InquiryWorkflow → QuotationWorkflow', 'Audit trail'],
    },
    customers: {
        title: 'Customers & contacts',
        what: [
            'Each customer has several contact people. Their code appears in every document number.',
            'Archiving keeps the history: old quotations still show the customer and contact they were made for.',
            'The edit page includes the "customer report": every line ever quoted to them.',
        ],
        builtWith: ['Soft deletes', 'Contacts synced in a transaction'],
    },
    parts: {
        title: 'Parts & inventory',
        what: [
            'Part master with list prices, which pre-fill quotation lines.',
            'Stock adjustments with a reason, written to the part’s audit log. Delivery orders deduct stock automatically.',
            'Discontinued parts are soft-deleted, so old quotations still show them.',
        ],
        production: ['Attachments (drawings, datasheets) and QR label sheets.'],
        builtWith: ['Server-side table', 'Activity log', 'Soft deletes'],
    },
    uoms: {
        title: 'Units of measure',
        what: ['Units used by parts. A unit in use can’t be deleted.'],
        builtWith: ['Foreign keys with restrict'],
    },
    catalog: {
        title: 'AI description review queue',
        what: [
            'Product descriptions for the public catalog are written by an LLM from a fact sheet, then checked by a guard before a person reviews them.',
            'The guard flags invented figures, compliance claims with no evidence (FDA, Halal…), the wrong kind of product, and text copied from the manufacturer.',
            'Edit a draft and the guard runs again. Approval stays blocked while errors remain. Keyboard: J/K to move, A to approve, R to reject.',
        ],
        production: [
            'Drafts generated in bulk with the Claude Batches API from a per-product fact sheet.',
            'A guard of several hundred rules, including glossaries and per-family checks.',
            'Approved copy published as static pages to Cloudflare Workers KV, and indexed in Meilisearch.',
        ],
        builtWith: [
            'A DescriptionGuard service',
            'Word 4-gram similarity',
            'Keyboard-first review UI',
        ],
    },
    catalogTypes: {
        title: 'Catalog product types',
        what: [
            'Groups different suppliers’ category paths (Beltora and Conveyix) into unified customer-facing product types with reserved URL slugs and page header presets.',
            'Maps supplier-specific specification groups onto shared key attributes per type, preventing duplicate field mappings.',
            'Editing category assignments shows pending drift until you run Apply, which resolves types onto unlocked products.',
        ],
        production: [
            'Resolves across ~2,200 products from supplier XML/HTML imports via `catalog:apply-types`.',
            'Type pages are rendered statically and pushed to Cloudflare Workers KV across EN and ID locales.',
        ],
        builtWith: [
            'CatalogTypeService drift detector and SQLite-safe apply transaction',
            'Unique supplier/path mapping table',
            'Admin-only gate with read-only Sales preview',
        ],
    },
} satisfies Record<string, FeatureNote>;

export type FeatureNoteKey = keyof typeof featureNotes;
