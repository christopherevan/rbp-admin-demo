import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Boxes,
    Briefcase,
    FileText,
    Inbox,
    KeyRound,
    LayoutGrid,
    RotateCcw,
    ShieldCheck,
    Sparkles,
    UserCog,
} from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import PublicLayout from '@/layouts/public-layout';
import { dashboard } from '@/routes';
import demo from '@/routes/demo';

const features = [
    {
        icon: FileText,
        title: 'Quote-to-cash pipeline',
        body: 'Quotation → PO → sales order → delivery → invoice → payment, driven by a server-side state machine with a full audit trail.',
    },
    {
        icon: Inbox,
        title: 'Web inquiry inbox',
        body: 'Buyer requests from the public site, triaged and converted into draft quotations in one click.',
    },
    {
        icon: Sparkles,
        title: 'AI copy review',
        body: 'LLM-written product descriptions, fact-checked by a guard, then approved by a person.',
    },
    {
        icon: LayoutGrid,
        title: 'Dashboard',
        body: 'Pipeline at a glance, most-quoted parts with their price history, and a live activity feed.',
    },
    {
        icon: Boxes,
        title: 'Master data',
        body: 'Customers and contacts, a parts master with stock that delivery orders deduct, units of measure.',
    },
    {
        icon: ShieldCheck,
        title: 'Roles & policies',
        body: 'Sales own their quotations; admins fulfil and bill. Switch persona and watch the UI explain why.',
    },
];

const stack = [
    'Laravel 13',
    'Inertia 3',
    'React 19',
    'TypeScript',
    'Tailwind CSS 4',
    'shadcn/ui',
    'TanStack Table',
    'Recharts',
    'SQLite',
    'Pest',
    'FrankenPHP',
    'Docker',
];

export default function Welcome() {
    const { auth } = usePage().props;
    const [showLogin, setShowLogin] = useState(false);

    return (
        <PublicLayout>
            <Head title="Interactive demo" />

            <section className="mx-auto grid max-w-6xl gap-10 px-4 py-14 lg:grid-cols-[1.15fr_1fr] lg:py-20">
                <div className="space-y-6">
                    <span className="inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/5 px-3 py-1 text-xs font-medium text-primary">
                        <RotateCcw className="size-3.5" /> Live demo · your own
                        throwaway copy of the data
                    </span>
                    <h1 className="text-4xl font-semibold tracking-tight text-balance sm:text-5xl">
                        The admin panel behind an industrial parts distributor.
                    </h1>
                    <p className="max-w-xl text-lg text-muted-foreground">
                        A working replica of a system I built and run in
                        production: quotations through to payment, web
                        inquiries, inventory, and an AI copywriting pipeline
                        with a human in the loop. Fictional company, real code
                        paths.
                    </p>
                    <div className="flex flex-wrap gap-1.5">
                        {stack.map((item) => (
                            <span
                                key={item}
                                className="rounded-md border bg-muted/40 px-2 py-0.5 text-xs text-muted-foreground"
                            >
                                {item}
                            </span>
                        ))}
                    </div>
                </div>

                <Card className="gap-4 p-5 lg:p-6">
                    {auth.user ? (
                        <>
                            <p className="font-medium">
                                You're signed in as {auth.user.name}.
                            </p>
                            <Button asChild size="lg">
                                <Link href={dashboard()}>
                                    Continue to the dashboard <ArrowRight />
                                </Link>
                            </Button>
                        </>
                    ) : (
                        <>
                            <div>
                                <p className="font-semibold">
                                    Pick a persona to start
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    You get a private copy of the demo data.
                                    Nothing you do is visible to anyone else,
                                    and you can reset at any time.
                                </p>
                            </div>
                            <Persona
                                role="admin"
                                name="Anonymous Capybara"
                                icon={UserCog}
                                body="Admin: fulfilment, invoicing, payments, inquiry approvals and AI review."
                            />
                            <Persona
                                role="sales"
                                name="Anonymous Narwhal"
                                icon={Briefcase}
                                body="Sales: own quotations up to the customer's PO, customers and parts."
                            />

                            {showLogin ? (
                                <Form
                                    {...demo.enter.form()}
                                    className="space-y-3 border-t pt-4"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <input
                                                type="text"
                                                name="website"
                                                className="hidden"
                                                tabIndex={-1}
                                                autoComplete="off"
                                            />
                                            <div className="grid grid-cols-2 gap-3">
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="username">
                                                        Username
                                                    </Label>
                                                    <Input
                                                        id="username"
                                                        name="username"
                                                        defaultValue="demo"
                                                        autoComplete="username"
                                                    />
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="password">
                                                        Password
                                                    </Label>
                                                    <Input
                                                        id="password"
                                                        name="password"
                                                        type="password"
                                                        defaultValue="demo1234"
                                                        autoComplete="current-password"
                                                    />
                                                </div>
                                            </div>
                                            <InputError
                                                message={errors.username}
                                            />
                                            <Button
                                                type="submit"
                                                variant="secondary"
                                                className="w-full"
                                                disabled={processing}
                                            >
                                                {processing && <Spinner />} Sign
                                                in
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            ) : (
                                <button
                                    type="button"
                                    onClick={() => setShowLogin(true)}
                                    className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                                >
                                    <KeyRound className="size-3.5" /> Or sign in
                                    with a username (demo / demo1234)
                                </button>
                            )}
                        </>
                    )}
                </Card>
            </section>

            <section className="border-t bg-muted/30">
                <div className="mx-auto max-w-6xl px-4 py-14">
                    <h2 className="mb-6 text-2xl font-semibold tracking-tight">
                        What's inside
                    </h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {features.map(({ icon: Icon, title, body }) => (
                            <Card key={title} className="gap-2 p-5">
                                <span className="flex size-9 items-center justify-center rounded-md bg-primary/10 text-primary">
                                    <Icon className="size-4" />
                                </span>
                                <p className="font-medium">{title}</p>
                                <p className="text-sm text-muted-foreground">
                                    {body}
                                </p>
                            </Card>
                        ))}
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-6xl px-4 py-14">
                <h2 className="mb-6 text-2xl font-semibold tracking-tight">
                    How the sandbox works
                </h2>
                <ol className="grid gap-4 md:grid-cols-3">
                    {[
                        [
                            'A fresh copy, just for you',
                            'Entering the demo copies a pre-seeded SQLite database: four months of fictional history, replayed through the real workflow code.',
                        ],
                        [
                            'Every request uses your copy',
                            'A middleware points the database connection at your file before authentication or routing touch it. Nobody else can see your changes.',
                        ],
                        [
                            'Throw it away',
                            'Reset whenever you like. Idle copies are deleted after three hours, and the template is rebuilt every night so dates stay current.',
                        ],
                    ].map(([title, body], i) => (
                        <li key={title} className="rounded-xl border p-5">
                            <span className="mb-3 flex size-7 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground">
                                {i + 1}
                            </span>
                            <p className="font-medium">{title}</p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {body}
                            </p>
                        </li>
                    ))}
                </ol>
            </section>
        </PublicLayout>
    );
}

function Persona({
    role,
    name,
    body,
    icon: Icon,
}: {
    role: 'admin' | 'sales';
    name: string;
    body: string;
    icon: typeof UserCog;
}) {
    return (
        <Form {...demo.enter.form()}>
            {({ processing }) => (
                <>
                    <input type="hidden" name="role" value={role} />
                    <input
                        type="text"
                        name="website"
                        className="hidden"
                        tabIndex={-1}
                        autoComplete="off"
                    />
                    <button
                        type="submit"
                        disabled={processing}
                        className="group flex w-full items-center gap-3 rounded-lg border p-3 text-left transition hover:border-primary hover:bg-primary/5 disabled:opacity-60"
                    >
                        <span className="flex size-10 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                            {processing ? (
                                <Spinner />
                            ) : (
                                <Icon className="size-5" />
                            )}
                        </span>
                        <span className="min-w-0 flex-1">
                            <span className="block font-medium">
                                Explore as{' '}
                                {role === 'admin' ? 'Admin' : 'Sales'}{' '}
                                <span className="font-normal text-muted-foreground">
                                    · {name}
                                </span>
                            </span>
                            <span className="block text-sm text-muted-foreground">
                                {body}
                            </span>
                        </span>
                        <ArrowRight className="size-4 text-muted-foreground transition group-hover:translate-x-0.5 group-hover:text-primary" />
                    </button>
                </>
            )}
        </Form>
    );
}
