import { Head } from '@inertiajs/react';
import { Check, Minus } from 'lucide-react';
import { Card } from '@/components/ui/card';
import PublicLayout from '@/layouts/public-layout';

const comparison: [string, string, string][] = [
    [
        'Quote-to-cash workflow',
        'Full state machine, partial sales orders, down payments, payments',
        'Same rules, ported',
    ],
    [
        'Documents',
        'dompdf PDFs, every print stored as a numbered, hashed version',
        'Print-ready HTML (print or save as PDF)',
    ],
    [
        'File uploads',
        'Customer POs, payment proofs and part drawings on a private disk',
        'File names only, nothing stored',
    ],
    [
        'Web inquiries',
        'Public catalog quote list, email verification, Turnstile, Telegram alerts',
        'Simple public form straight into the inbox',
    ],
    [
        'AI descriptions',
        'Claude Batches API from per-product fact sheets, a guard with several hundred rules',
        'Pre-generated drafts, four-rule guard, real review flow',
    ],
    [
        'Public catalog',
        'Product types, key attributes, bilingual Cloudflare Workers KV pages, Meilisearch search',
        'Product types and key attributes (no public storefront or KV publisher)',
    ],
    [
        'Warehouse',
        'QR inventory labels, scan-to-fulfil screen',
        'Stock levels and adjustments',
    ],
    ['Currencies', 'IDR, USD, SGD, EUR, RMB per line', 'IDR'],
];

const decisions = [
    [
        'One sandbox per visitor',
        'A middleware copies a pre-seeded SQLite template on entry and points the connection at it on every request, before auth, route model binding or shared props touch the database. Reset moves you to a new copy rather than overwriting the old one, so an in-flight request can never corrupt it.',
    ],
    [
        'History replayed, not inserted',
        'The seeder plans four months of quotations and inquiries as dated events, then replays them through the same workflow services the UI calls, with the clock set to each event. Numbers, totals, stock and the audit log come out exactly as the app produces them.',
    ],
    [
        'The server owns the state machine',
        'QuotationWorkflow has one method per transition, each with its own guard and audit entry. The page asks the server for the valid next actions, permission checks included, so the rules exist in one place.',
    ],
    [
        'Money as integers',
        'Amounts are whole rupiah in bigints. PPN is charged on the discounted amount, invoices snapshot what they bill, and partial sales orders bill only what was ordered.',
    ],
    [
        'Hardened while rebuilding',
        'Document numbers come from an atomic per-day counter. Ids are auto-increment with route model binding. Policies separate Sales from Admin work.',
    ],
];

export default function About() {
    return (
        <PublicLayout>
            <Head title="How it's built" />
            <div className="mx-auto max-w-4xl space-y-12 px-4 py-12">
                <header className="space-y-3">
                    <h1 className="text-3xl font-semibold tracking-tight">
                        How it's built
                    </h1>
                    <p className="text-lg text-muted-foreground">
                        The production system runs a distributor's sales desk:
                        quotations, purchase orders, deliveries, invoicing and a
                        bilingual public catalog. This demo rebuilds its core on
                        a modern stack, with a fictional company and data.
                    </p>
                </header>

                <section className="space-y-4">
                    <h2 className="text-xl font-semibold">
                        Production vs this demo
                    </h2>
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-4 py-2.5">Area</th>
                                    <th className="px-4 py-2.5">Production</th>
                                    <th className="px-4 py-2.5">Demo</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {comparison.map(([area, production, demo]) => (
                                    <tr key={area} className="align-top">
                                        <td className="px-4 py-3 font-medium">
                                            {area}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {production}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className="flex gap-1.5">
                                                {demo === 'Not included' ? (
                                                    <Minus className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                                ) : (
                                                    <Check className="mt-0.5 size-4 shrink-0 text-emerald-600" />
                                                )}
                                                {demo}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="space-y-4">
                    <h2 className="text-xl font-semibold">Design decisions</h2>
                    <div className="grid gap-4 md:grid-cols-2">
                        {decisions.map(([title, body]) => (
                            <Card key={title} className="gap-2 p-5">
                                <p className="font-medium">{title}</p>
                                <p className="text-sm text-muted-foreground">
                                    {body}
                                </p>
                            </Card>
                        ))}
                    </div>
                </section>

                <section className="space-y-4">
                    <h2 className="text-xl font-semibold">Request path</h2>
                    <pre className="overflow-x-auto rounded-xl border bg-muted/40 p-4 text-xs leading-relaxed">
                        {`browser ── cookie: demo_sandbox=<id> (encrypted, httpOnly)
   │
   ▼
FrankenPHP (classic mode: every request boots fresh)
   │
   ├─ StartSession
   ├─ DemoSandbox ── storage/demo/sandboxes/<id>.sqlite ──▶ default DB connection
   ├─ auth · route model binding · shared Inertia props   (all read your copy)
   └─ controller ─▶ QuotationWorkflow / InquiryWorkflow ─▶ audit log
                                   ▲
scheduler: prune idle sandboxes every 15 min · rebuild the template nightly`}
                    </pre>
                </section>

                <section className="space-y-3">
                    <h2 className="text-xl font-semibold">Stack</h2>
                    <p className="text-sm text-muted-foreground">
                        Laravel 13, Inertia 3 and React 19 with TypeScript,
                        Tailwind CSS 4 and shadcn/ui; TanStack Table for
                        server-side tables; Recharts for charts; Wayfinder for
                        typed routes; SQLite; Pest for tests. It ships as one
                        Docker image on FrankenPHP, plus a scheduler container.
                    </p>
                </section>
            </div>
        </PublicLayout>
    );
}
