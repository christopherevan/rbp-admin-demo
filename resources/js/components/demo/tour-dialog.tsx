import { Link, usePage } from '@inertiajs/react';
import {
    Boxes,
    Compass,
    FileText,
    Inbox,
    LayoutGrid,
    ShieldCheck,
    Sparkles,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { dashboard } from '@/routes';
import catalog from '@/routes/catalog';
import inquiries from '@/routes/inquiries';
import parts from '@/routes/parts';
import quotations from '@/routes/quotations';

const modules = [
    {
        icon: FileText,
        title: 'Quote-to-cash pipeline',
        body: 'Quotation → customer PO → sales order → delivery → invoice → payment, with a state machine and an audit trail.',
        href: quotations.index({ query: { status: 'on_progress' } }),
    },
    {
        icon: Inbox,
        title: 'Web inquiry inbox',
        body: 'Triage buyer requests from the public site and draft a quotation from one in a click.',
        href: inquiries.index({ query: { status: 'new' } }),
    },
    {
        icon: Sparkles,
        title: 'AI description review',
        body: 'LLM-written catalog copy, fact-checked by a guard and approved by a person.',
        href: catalog.descriptions.index(),
    },
    {
        icon: LayoutGrid,
        title: 'Dashboard',
        body: 'Pipeline counts, most-quoted parts with price history, and the live activity feed.',
        href: dashboard(),
    },
    {
        icon: Boxes,
        title: 'Master data',
        body: 'Customers with contacts, a parts master with stock, and units of measure.',
        href: parts.index(),
    },
    {
        icon: ShieldCheck,
        title: 'Roles',
        body: 'Sales own their quotations; admins handle fulfilment and billing. Switch persona from the banner.',
        href: null,
    },
];

const walkthrough = [
    'As Sales, open a quotation that is waiting for the customer and record their PO.',
    'Try creating the sales order: Sales is refused. Switch to Admin from the banner.',
    'As Admin, take it through sales order → delivery order → invoice → payment.',
    'Open the public quote form (sidebar), send a request, then triage it in Inquiries.',
    'Review the AI drafts: the guard has flagged three of them.',
];

/** Welcome tour, opened automatically on first entry and from the header after. */
export function TourDialog() {
    const { demo } = usePage().props;
    const [open, setOpen] = useState(demo.tour);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="ghost" size="sm" className="gap-1.5">
                    <Compass className="size-4" />
                    <span className="hidden sm:inline">Guide</span>
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle className="text-xl">
                        Welcome to Kinetra Admin
                    </DialogTitle>
                    <DialogDescription>
                        A working replica of a sales and order-management system
                        I built for an industrial parts distributor. Every
                        company and number is fictional, and this copy of the
                        data is yours alone: change anything, then reset.
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-3 sm:grid-cols-2">
                    {modules.map(({ icon: Icon, title, body, href }) => {
                        const card = (
                            <div className="flex h-full gap-3 rounded-lg border p-3 transition hover:border-primary/40 hover:bg-primary/5">
                                <span className="flex size-9 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                                    <Icon className="size-4" />
                                </span>
                                <div>
                                    <p className="text-sm font-medium">
                                        {title}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {body}
                                    </p>
                                </div>
                            </div>
                        );

                        return href ? (
                            <Link
                                key={title}
                                href={href}
                                onClick={() => setOpen(false)}
                            >
                                {card}
                            </Link>
                        ) : (
                            <div key={title}>{card}</div>
                        );
                    })}
                </div>

                <div className="rounded-lg bg-muted/50 p-4">
                    <p className="mb-2 text-sm font-medium">
                        A five-minute walkthrough
                    </p>
                    <ol className="list-decimal space-y-1 pl-5 text-sm text-muted-foreground">
                        {walkthrough.map((step) => (
                            <li key={step}>{step}</li>
                        ))}
                    </ol>
                    <p className="mt-3 text-xs text-muted-foreground">
                        Look for{' '}
                        <span className="font-medium text-primary">
                            About this feature
                        </span>{' '}
                        on every page: it explains what the production system
                        does beyond this demo.
                    </p>
                </div>

                <div className="flex justify-end">
                    <Button onClick={() => setOpen(false)}>
                        Start exploring
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
