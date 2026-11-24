import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    Bot,
    Check,
    CheckCheck,
    CircleX,
    Pencil,
    Search,
    ShieldCheck,
    X,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { clean } from '@/components/status-filter-pills';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime } from '@/lib/format';
import { descriptionStatuses } from '@/lib/status';
import { cn } from '@/lib/utils';
import catalog from '@/routes/catalog';
import type { DescriptionStatus } from '@/types';

type Item = {
    id: number;
    ulid: string;
    title: string;
    supplier: string;
    category: string;
    errors: number;
    warnings: number;
};

type Selected = {
    id: number;
    ulid: string;
    title: string;
    supplier: string;
    category: string;
    family: string;
    facts: Record<string, string[]>;
    manufacturer_text: string;
    description_en: string;
    description_id: string;
    meta_en: string;
    ai_status: DescriptionStatus;
    ai_findings: { errors: string[]; warnings: string[] };
    ai_meta: {
        model?: string;
        batch_id?: string;
        generated_at?: string;
        similarity?: number;
        confidence?: string;
        edited?: boolean;
        usage?: { input_tokens: number; output_tokens: number };
    };
    rejection_note: string | null;
    decided_at: string | null;
    decider: string | null;
    product_type: { ulid: string; name: string; slug: string } | null;
};

type Props = {
    filters: { status: DescriptionStatus; q: string | null };
    counts: Record<string, number>;
    products: Item[];
    selected: Selected | null;
    similarityLimit: number;
    canDecide: boolean;
};

const route = catalog.descriptions;

export default function Descriptions({
    filters,
    counts,
    products,
    selected,
    similarityLimit,
    canDecide,
}: Props) {
    const [editing, setEditing] = useState(false);
    const [checked, setChecked] = useState<number[]>([]);
    const [search, setSearch] = useState(filters.q ?? '');

    const visit = (params: Record<string, unknown>) =>
        router.get(route.index.url(), clean({ ...filters, ...params }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });

    const select = (ulid: string) => {
        setEditing(false);
        visit({ selected: ulid });
    };

    const decide = (decision: 'approve' | 'reject', note?: string) => {
        if (!selected) {
            return;
        }

        router.post(
            route.decide.url(selected.ulid),
            { decision, note: note ?? null },
            { preserveScroll: true, onSuccess: () => setEditing(false) },
        );
    };

    const index = products.findIndex((p) => p.id === selected?.id);
    const pending = filters.status === 'pending';
    const blocked = (selected?.ai_findings.errors.length ?? 0) > 0;

    // Keyboard-first review: J/K to move, A to approve, R to reject.
    useEffect(() => {
        const onKey = (event: KeyboardEvent) => {
            const target = event.target as HTMLElement;

            if (
                editing ||
                event.metaKey ||
                event.ctrlKey ||
                ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName) ||
                target.isContentEditable
            ) {
                return;
            }

            const key = event.key.toLowerCase();

            if (key === 'j' && index < products.length - 1) {
                select(products[index + 1].ulid);
            } else if (key === 'k' && index > 0) {
                select(products[index - 1].ulid);
            } else if (key === 'a' && pending && canDecide && !blocked) {
                decide('approve');
            } else if (key === 'r' && pending && canDecide) {
                decide(
                    'reject',
                    window.prompt('Reason for rejecting (optional)') ??
                        undefined,
                );
            }
        };

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    });

    useEffect(() => {
        const timer = setTimeout(() => {
            if ((filters.q ?? '') !== search) {
                visit({ q: search, selected: undefined });
            }
        }, 300);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const clean_ = products.filter((p) => p.errors === 0).map((p) => p.id);

    return (
        <>
            <Head title="AI descriptions" />
            <PageHeader
                title="AI description review"
                description="LLM-written catalog copy, fact-checked by a guard, approved by a person."
                note="catalog"
            />

            <div className="flex flex-wrap items-center gap-2">
                {(Object.keys(descriptionStatuses) as DescriptionStatus[]).map(
                    (status) => (
                        <button
                            key={status}
                            type="button"
                            onClick={() => {
                                setChecked([]);
                                visit({
                                    status,
                                    selected: undefined,
                                    q: undefined,
                                });
                            }}
                            className={cn(
                                'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm',
                                filters.status === status
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'hover:bg-muted',
                            )}
                        >
                            {descriptionStatuses[status].label}
                            <span className="rounded-full bg-black/10 px-1.5 text-xs tabular-nums dark:bg-white/15">
                                {counts[status] ?? 0}
                            </span>
                        </button>
                    ),
                )}
                <span className="ml-auto hidden text-xs text-muted-foreground md:block">
                    Keys: <kbd className="rounded border px-1">J</kbd>/
                    <kbd className="rounded border px-1">K</kbd> move ·{' '}
                    <kbd className="rounded border px-1">A</kbd> approve ·{' '}
                    <kbd className="rounded border px-1">R</kbd> reject
                </span>
            </div>

            <div className="grid gap-4 lg:grid-cols-[20rem_1fr]">
                <Card className="gap-0 self-start py-0">
                    <div className="border-b p-3">
                        <div className="relative">
                            <Search className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search products…"
                                className="pl-8"
                            />
                        </div>
                    </div>
                    <ul className="divide-y">
                        {products.map((product) => (
                            <li
                                key={product.id}
                                className={cn(
                                    'flex items-start gap-2 px-3 py-2.5',
                                    product.id === selected?.id &&
                                        'bg-primary/5',
                                )}
                            >
                                {pending && canDecide && (
                                    <Checkbox
                                        className="mt-1"
                                        disabled={product.errors > 0}
                                        checked={checked.includes(product.id)}
                                        onCheckedChange={(value) =>
                                            setChecked((c) =>
                                                value
                                                    ? [...c, product.id]
                                                    : c.filter(
                                                          (id) =>
                                                              id !== product.id,
                                                      ),
                                            )
                                        }
                                        aria-label={`Select ${product.title}`}
                                    />
                                )}
                                <button
                                    type="button"
                                    onClick={() => select(product.ulid)}
                                    className="min-w-0 flex-1 text-left"
                                >
                                    <p className="truncate text-sm font-medium">
                                        {product.title}
                                    </p>
                                    <p className="truncate text-xs text-muted-foreground">
                                        {product.supplier} · {product.category}
                                    </p>
                                    <div className="mt-1 flex gap-1.5">
                                        {product.errors > 0 && (
                                            <span className="inline-flex items-center gap-1 rounded bg-red-500/10 px-1.5 text-xs text-red-700 dark:text-red-400">
                                                <CircleX className="size-3" />{' '}
                                                {product.errors}
                                            </span>
                                        )}
                                        {product.warnings > 0 && (
                                            <span className="inline-flex items-center gap-1 rounded bg-amber-500/10 px-1.5 text-xs text-amber-700 dark:text-amber-400">
                                                <AlertTriangle className="size-3" />{' '}
                                                {product.warnings}
                                            </span>
                                        )}
                                        {product.errors === 0 &&
                                            product.warnings === 0 && (
                                                <span className="inline-flex items-center gap-1 rounded bg-emerald-500/10 px-1.5 text-xs text-emerald-700 dark:text-emerald-400">
                                                    <ShieldCheck className="size-3" />{' '}
                                                    Clean
                                                </span>
                                            )}
                                    </div>
                                </button>
                            </li>
                        ))}
                        {products.length === 0 && (
                            <li className="px-3 py-8 text-center text-sm text-muted-foreground">
                                Nothing here. The queue is clear.
                            </li>
                        )}
                    </ul>
                    {pending && canDecide && products.length > 0 && (
                        <div className="flex items-center gap-2 border-t p-3">
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() =>
                                    setChecked(
                                        checked.length === clean_.length
                                            ? []
                                            : clean_,
                                    )
                                }
                            >
                                {checked.length === clean_.length &&
                                clean_.length > 0
                                    ? 'Clear'
                                    : 'Select clean'}
                            </Button>
                            <Button
                                size="sm"
                                className="ml-auto"
                                disabled={checked.length === 0}
                                onClick={() =>
                                    router.post(
                                        route.bulkApprove.url(),
                                        { ids: checked },
                                        { onSuccess: () => setChecked([]) },
                                    )
                                }
                            >
                                <CheckCheck /> Approve {checked.length || ''}
                            </Button>
                        </div>
                    )}
                </Card>

                {selected ? (
                    <div className="min-w-0 space-y-4">
                        <Card className="gap-4">
                            <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-2">
                                <div>
                                    <CardTitle className="flex items-center gap-2">
                                        {selected.title}
                                        <StatusBadge
                                            kind="description"
                                            status={selected.ai_status}
                                        />
                                    </CardTitle>
                                    <div className="mt-1.5 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                                        <span>
                                            {selected.supplier} ·{' '}
                                            {selected.category}
                                        </span>
                                        {selected.product_type ? (
                                            <Link
                                                href={catalog.types.show.url(
                                                    selected.product_type.ulid,
                                                )}
                                                className="inline-flex items-center rounded-md bg-muted px-2 py-0.5 text-xs font-medium text-foreground hover:bg-muted/80"
                                            >
                                                {selected.product_type.name}
                                            </Link>
                                        ) : (
                                            <Link
                                                href={catalog.types.index.url()}
                                                className="inline-flex items-center rounded-md border px-2 py-0.5 text-xs text-muted-foreground hover:bg-muted/50"
                                            >
                                                Untyped
                                            </Link>
                                        )}
                                    </div>
                                </div>
                                {selected.ai_status === 'pending' && (
                                    <div className="flex gap-2">
                                        {!editing && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => setEditing(true)}
                                            >
                                                <Pencil /> Edit
                                            </Button>
                                        )}
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            disabled={!canDecide || editing}
                                            onClick={() =>
                                                decide(
                                                    'reject',
                                                    window.prompt(
                                                        'Reason for rejecting (optional)',
                                                    ) ?? undefined,
                                                )
                                            }
                                        >
                                            <X /> Reject
                                        </Button>
                                        <Button
                                            size="sm"
                                            disabled={
                                                !canDecide || blocked || editing
                                            }
                                            onClick={() => decide('approve')}
                                            title={
                                                blocked
                                                    ? 'Fix the errors first'
                                                    : undefined
                                            }
                                        >
                                            <Check /> Approve
                                        </Button>
                                    </div>
                                )}
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <GuardPanel
                                    selected={selected}
                                    similarityLimit={similarityLimit}
                                />
                                {!canDecide &&
                                    selected.ai_status === 'pending' && (
                                        <p className="text-xs text-muted-foreground">
                                            Sales can edit drafts; approving and
                                            rejecting are admin decisions.
                                        </p>
                                    )}
                                {selected.ai_status !== 'pending' &&
                                    selected.decider && (
                                        <p className="text-sm text-muted-foreground">
                                            {selected.ai_status === 'approved'
                                                ? 'Approved'
                                                : 'Rejected'}{' '}
                                            by {selected.decider} on{' '}
                                            {formatDateTime(
                                                selected.decided_at,
                                            )}
                                            {selected.rejection_note &&
                                                `: “${selected.rejection_note}”`}
                                        </p>
                                    )}

                                {editing ? (
                                    <EditForm
                                        key={selected.id}
                                        selected={selected}
                                        onDone={() => setEditing(false)}
                                    />
                                ) : (
                                    <Tabs defaultValue="en">
                                        <TabsList>
                                            <TabsTrigger value="en">
                                                English
                                            </TabsTrigger>
                                            <TabsTrigger value="id">
                                                Bahasa Indonesia
                                            </TabsTrigger>
                                            <TabsTrigger value="facts">
                                                Fact sheet
                                            </TabsTrigger>
                                            <TabsTrigger value="source">
                                                Manufacturer text
                                            </TabsTrigger>
                                        </TabsList>
                                        <TabsContent
                                            value="en"
                                            className="space-y-3"
                                        >
                                            <Prose
                                                text={selected.description_en}
                                            />
                                            <p className="rounded-md bg-muted/50 p-2 text-xs">
                                                <span className="font-medium">
                                                    Meta description:{' '}
                                                </span>
                                                {selected.meta_en}
                                            </p>
                                        </TabsContent>
                                        <TabsContent value="id">
                                            <Prose
                                                text={selected.description_id}
                                            />
                                        </TabsContent>
                                        <TabsContent value="facts">
                                            <dl className="grid gap-2 text-sm sm:grid-cols-2">
                                                {Object.entries(
                                                    selected.facts,
                                                ).map(([key, values]) => (
                                                    <div
                                                        key={key}
                                                        className="rounded-md border p-2"
                                                    >
                                                        <dt className="text-xs text-muted-foreground">
                                                            {key}
                                                        </dt>
                                                        <dd>
                                                            {values.join(', ')}
                                                        </dd>
                                                    </div>
                                                ))}
                                            </dl>
                                            <p className="mt-2 text-xs text-muted-foreground">
                                                The only facts the model was
                                                given. Any other figure is
                                                flagged.
                                            </p>
                                        </TabsContent>
                                        <TabsContent value="source">
                                            <Prose
                                                text={
                                                    selected.manufacturer_text
                                                }
                                            />
                                            <p className="mt-2 text-xs text-muted-foreground">
                                                Drafts that reuse too many of
                                                these phrases are rejected.
                                            </p>
                                        </TabsContent>
                                    </Tabs>
                                )}
                            </CardContent>
                        </Card>

                        <p className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                            <Bot className="size-3.5" />
                            <span>{selected.ai_meta.model}</span>
                            <span>
                                batch {selected.ai_meta.batch_id?.slice(0, 22)}…
                            </span>
                            <span>
                                generated{' '}
                                {formatDateTime(selected.ai_meta.generated_at)}
                            </span>
                            {selected.ai_meta.usage && (
                                <span>
                                    {selected.ai_meta.usage.input_tokens.toLocaleString()}{' '}
                                    in /{' '}
                                    {selected.ai_meta.usage.output_tokens.toLocaleString()}{' '}
                                    out tokens
                                </span>
                            )}
                            {selected.ai_meta.edited && (
                                <span className="font-medium text-foreground">
                                    edited by a reviewer
                                </span>
                            )}
                        </p>
                    </div>
                ) : (
                    <Card className="items-center justify-center py-16 text-sm text-muted-foreground">
                        Select a product to review.
                    </Card>
                )}
            </div>
        </>
    );
}

function GuardPanel({
    selected,
    similarityLimit,
}: {
    selected: Selected;
    similarityLimit: number;
}) {
    const { errors, warnings } = selected.ai_findings;
    const similarity = selected.ai_meta.similarity ?? 0;

    return (
        <div
            className={cn(
                'rounded-lg border p-3',
                errors.length
                    ? 'border-red-500/30 bg-red-500/5'
                    : warnings.length
                      ? 'border-amber-500/30 bg-amber-500/5'
                      : 'border-emerald-500/30 bg-emerald-500/5',
            )}
        >
            <p className="mb-2 flex items-center gap-1.5 text-sm font-medium">
                <ShieldCheck className="size-4" />
                Guard:{' '}
                {errors.length
                    ? `${errors.length} ${errors.length === 1 ? 'error' : 'errors'}, approval blocked`
                    : warnings.length
                      ? 'passed with warnings'
                      : 'passed'}
            </p>
            <ul className="space-y-1 text-sm">
                {errors.map((error) => (
                    <li
                        key={error}
                        className="flex gap-2 text-red-700 dark:text-red-400"
                    >
                        <CircleX className="mt-0.5 size-4 shrink-0" /> {error}
                    </li>
                ))}
                {warnings.map((warning) => (
                    <li
                        key={warning}
                        className="flex gap-2 text-amber-700 dark:text-amber-400"
                    >
                        <AlertTriangle className="mt-0.5 size-4 shrink-0" />{' '}
                        {warning}
                    </li>
                ))}
            </ul>
            <div className="mt-3 flex items-center gap-3 text-xs text-muted-foreground">
                <span className="w-44 shrink-0">
                    Overlap with manufacturer text
                </span>
                <div className="relative h-1.5 flex-1 rounded-full bg-muted">
                    <div
                        className={cn(
                            'h-full rounded-full',
                            similarity > similarityLimit
                                ? 'bg-red-500'
                                : 'bg-emerald-500',
                        )}
                        style={{ width: `${Math.min(100, similarity * 100)}%` }}
                    />
                    <div
                        className="absolute -top-1 h-3.5 w-px bg-foreground/50"
                        style={{ left: `${similarityLimit * 100}%` }}
                        title="Limit"
                    />
                </div>
                <span className="w-10 text-right tabular-nums">
                    {Math.round(similarity * 100)}%
                </span>
            </div>
        </div>
    );
}

function EditForm({
    selected,
    onDone,
}: {
    selected: Selected;
    onDone: () => void;
}) {
    const form = useForm({
        description_en: selected.description_en,
        description_id: selected.description_id,
        meta_en: selected.meta_en,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(route.update(selected.ulid), {
            preserveScroll: true,
            onSuccess: onDone,
        });
    };

    return (
        <form onSubmit={submit} className="space-y-3">
            <label className="grid gap-1.5 text-sm font-medium">
                English
                <Textarea
                    rows={9}
                    value={form.data.description_en}
                    onChange={(e) =>
                        form.setData('description_en', e.target.value)
                    }
                    className="font-normal"
                />
            </label>
            <label className="grid gap-1.5 text-sm font-medium">
                Bahasa Indonesia
                <Textarea
                    rows={6}
                    value={form.data.description_id}
                    onChange={(e) =>
                        form.setData('description_id', e.target.value)
                    }
                    className="font-normal"
                />
            </label>
            <label className="grid gap-1.5 text-sm font-medium">
                Meta description
                <Input
                    value={form.data.meta_en}
                    onChange={(e) => form.setData('meta_en', e.target.value)}
                    className="font-normal"
                />
            </label>
            <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" onClick={onDone}>
                    Cancel
                </Button>
                <Button type="submit" disabled={form.processing}>
                    {form.processing && <Spinner />} Save & re-check
                </Button>
            </div>
        </form>
    );
}

function Prose({ text }: { text: string }) {
    return (
        <div className="space-y-3 text-sm leading-relaxed">
            {text.split(/\n{2,}/).map((paragraph, i) => (
                <p key={i}>{paragraph}</p>
            ))}
        </div>
    );
}

Descriptions.layout = {
    breadcrumbs: [
        { title: 'AI descriptions', href: catalog.descriptions.index() },
    ],
};
