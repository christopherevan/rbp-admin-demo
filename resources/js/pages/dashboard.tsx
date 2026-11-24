import { Head, Link, router } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import {
    FilePlus2,
    FileText,
    Inbox,
    TrendingUp,
    Truck,
    UserPlus,
} from 'lucide-react';
import { useEffect } from 'react';
import type { ReactNode } from 'react';
import {
    Bar,
    CartesianGrid,
    ComposedChart,
    Line,
    ReferenceLine,
    XAxis,
    YAxis,
} from 'recharts';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { ChartContainer, ChartTooltip } from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
import { formatDate, formatIDR, formatNumber, timeAgo } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import customers from '@/routes/customers';
import inquiries from '@/routes/inquiries';
import quotations from '@/routes/quotations';
import type { Activity, InquiryStatus, QuotationStatus } from '@/types';

type Point = {
    quotation_id: number;
    number: string;
    customer: string;
    date: string;
    quantity: number;
    unit_price: number;
};

type Props = {
    quotationCounts: Record<QuotationStatus, number>;
    inquiryCounts: Record<InquiryStatus, number>;
    mostQuoted: {
        id: number;
        part_number: string;
        description: string;
        uom: string;
        quotations: number;
    }[];
    onDelivery: number;
    latestQuotation: {
        id: number;
        ulid: string;
        number: string;
        customer: string;
        status: QuotationStatus;
        created_at: string;
    } | null;
    latestInquiry: {
        id: number;
        ulid: string;
        number: string;
        name: string;
        company: string | null;
        status: InquiryStatus;
        created_at: string;
    } | null;
    recentActivity: (Activity & {
        subject: {
            type: 'quotation' | 'inquiry';
            id: number;
            key: string | number;
            number: string | null;
        };
    })[];
    priceHistory?: {
        part: {
            id: number;
            part_number: string;
            description: string;
            uom: string;
            list_price: number;
            stock_qty: number;
        };
        averagePrice: number;
        points: Point[];
    } | null;
};

const chartConfig = {
    unit_price: { label: 'Unit price', color: 'var(--chart-1)' },
    quantity: { label: 'Quantity', color: 'var(--chart-2)' },
} satisfies ChartConfig;

const compact = new Intl.NumberFormat('en', {
    notation: 'compact',
    maximumFractionDigits: 1,
});

const QUOTATION_PIPELINE_STATUSES = [
    'not_submitted',
    'waiting_customer',
    'on_progress',
    'waiting_payment',
] as const;

const INQUIRY_PIPELINE_STATUSES = [
    'new',
    'in_review',
    'approved',
    'rejected',
] as const;

export default function Dashboard({
    quotationCounts,
    inquiryCounts,
    mostQuoted,
    onDelivery,
    latestQuotation,
    latestInquiry,
    recentActivity,
    priceHistory,
}: Props) {
    const showPart = (id: number) =>
        router.reload({ only: ['priceHistory'], data: { part: id } });

    // Show the chart straight away for the most-quoted part. Watching the
    // prop (not just mount) also covers POSTs that redirect back here, like
    // a role switch or reset: Inertia keeps the page mounted and the full
    // response drops the optional prop.
    useEffect(() => {
        if (priceHistory === undefined && mostQuoted.length > 0) {
            showPart(mostQuoted[0].id);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [priceHistory]);

    const openQuotations = QUOTATION_PIPELINE_STATUSES.reduce(
        (sum, status) => sum + (quotationCounts[status] ?? 0),
        0,
    );
    const activeInquiries = INQUIRY_PIPELINE_STATUSES.reduce(
        (sum, status) => sum + (inquiryCounts[status] ?? 0),
        0,
    );

    const points = priceHistory?.points ?? [];
    const prices = points.map((p) => p.unit_price);
    const minPrice = prices.length > 0 ? Math.min(...prices) : null;
    const maxPrice = prices.length > 0 ? Math.max(...prices) : null;
    const totalQuotedQty = points.reduce((sum, p) => sum + p.quantity, 0);
    const listPrice = priceHistory?.part.list_price ?? 0;
    const avgVsListPct =
        priceHistory && listPrice > 0
            ? ((priceHistory.averagePrice - listPrice) / listPrice) * 100
            : null;

    return (
        <>
            <Head title="Dashboard" />

            <PageHeader
                title="Dashboard"
                description="Where every quotation and inquiry stands today."
                note="dashboard"
                actions={
                    <>
                        <Button asChild size="sm">
                            <Link href={quotations.create()}>
                                <FilePlus2 /> New quotation
                            </Link>
                        </Button>
                        <Button asChild size="sm" variant="outline">
                            <Link href={customers.create()}>
                                <UserPlus /> New customer
                            </Link>
                        </Button>
                    </>
                }
            />

            <section
                aria-label="Pipeline overview"
                className="grid gap-4 lg:grid-cols-2"
            >
                <PipelinePanel
                    title="Quotation pipeline"
                    totalLabel={`${formatNumber(openQuotations)} open`}
                    viewAllHref={quotations.index()}
                    items={QUOTATION_PIPELINE_STATUSES.map((status) => ({
                        key: status,
                        badge: <StatusBadge kind="quotation" status={status} />,
                        value: quotationCounts[status],
                        href: quotations.index({ query: { status } }),
                    }))}
                />

                <PipelinePanel
                    title="Web inquiries"
                    totalLabel={`${formatNumber(activeInquiries)} total`}
                    viewAllHref={inquiries.index()}
                    items={INQUIRY_PIPELINE_STATUSES.map((status) => ({
                        key: status,
                        badge: <StatusBadge kind="inquiry" status={status} />,
                        value: inquiryCounts[status],
                        href: inquiries.index({ query: { status } }),
                    }))}
                />
            </section>

            <div className="grid gap-4 lg:grid-cols-3">
                <Card className="gap-0 overflow-hidden py-0">
                    <CardHeader className="border-b px-5 py-3.5">
                        <CardTitle className="text-base">
                            Most quoted parts
                        </CardTitle>
                        <CardDescription>
                            Click a part to chart its price history.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        {mostQuoted.length > 0 ? (
                            <ul className="divide-y">
                                {mostQuoted.map((part, i) => {
                                    const selected =
                                        priceHistory?.part.id === part.id;

                                    return (
                                        <li key={part.id}>
                                            <button
                                                type="button"
                                                aria-pressed={selected}
                                                onClick={() =>
                                                    showPart(part.id)
                                                }
                                                className={cn(
                                                    'flex w-full items-center gap-3 px-4 py-2 text-left text-sm transition-colors hover:bg-muted/50 focus-visible:relative focus-visible:z-10 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset',
                                                    selected && 'bg-primary/8',
                                                )}
                                            >
                                                <span className="w-4 text-xs text-muted-foreground tabular-nums">
                                                    {i + 1}
                                                </span>
                                                <span className="min-w-0 flex-1">
                                                    <span
                                                        className={cn(
                                                            'block truncate font-mono font-medium text-primary',
                                                            selected &&
                                                                'font-semibold',
                                                        )}
                                                    >
                                                        {part.part_number}
                                                    </span>
                                                    <span className="block truncate text-xs text-muted-foreground">
                                                        {part.description}
                                                    </span>
                                                </span>
                                                <span className="rounded-md bg-muted px-1.5 py-0.5 text-xs tabular-nums">
                                                    {part.quotations}×
                                                </span>
                                            </button>
                                        </li>
                                    );
                                })}
                            </ul>
                        ) : (
                            <p className="px-5 py-8 text-sm text-muted-foreground">
                                No quoted parts yet.
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card className="gap-0 overflow-hidden py-0 lg:col-span-2">
                    <CardHeader className="flex flex-row items-start justify-between gap-4 border-b px-5 py-3.5">
                        <div className="min-w-0 space-y-1">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <TrendingUp className="size-4 shrink-0 text-primary" />
                                <span
                                    className={cn(
                                        'truncate',
                                        priceHistory && 'font-mono',
                                    )}
                                >
                                    {priceHistory
                                        ? priceHistory.part.part_number
                                        : 'Price history'}
                                </span>
                            </CardTitle>
                            <CardDescription className="truncate">
                                {priceHistory
                                    ? priceHistory.part.description
                                    : mostQuoted.length > 0
                                      ? 'Loading price history…'
                                      : 'Select a quoted part to inspect its price history.'}
                            </CardDescription>
                        </div>
                        {priceHistory && (
                            <span className="shrink-0 rounded-md border bg-muted/40 px-2.5 py-1 text-xs font-medium text-foreground tabular-nums">
                                {formatNumber(priceHistory.part.stock_qty)}{' '}
                                {priceHistory.part.uom} in stock
                            </span>
                        )}
                    </CardHeader>

                    {priceHistory && (
                        <div className="grid grid-cols-2 gap-px border-b bg-border sm:grid-cols-4">
                            <div className="bg-card px-4 py-2.5">
                                <p className="text-xs text-muted-foreground">
                                    List price
                                </p>
                                <p className="mt-0.5 text-sm font-semibold tabular-nums">
                                    {formatIDR(priceHistory.part.list_price)}
                                </p>
                            </div>
                            <div className="bg-card px-4 py-2.5">
                                <p className="text-xs text-muted-foreground">
                                    Avg quoted
                                </p>
                                <p className="mt-0.5 flex flex-wrap items-baseline gap-x-1.5 text-sm font-semibold tabular-nums">
                                    <span>
                                        {formatIDR(priceHistory.averagePrice)}
                                    </span>
                                    {avgVsListPct !== null && (
                                        <span className="text-xs font-normal text-muted-foreground">
                                            {Math.abs(avgVsListPct) < 0.05
                                                ? 'at list'
                                                : `${avgVsListPct > 0 ? '+' : ''}${avgVsListPct.toFixed(1)}%`}
                                        </span>
                                    )}
                                </p>
                            </div>
                            <div className="bg-card px-4 py-2.5">
                                <p className="text-xs text-muted-foreground">
                                    Quoted range
                                </p>
                                <p className="mt-0.5 text-sm font-semibold tabular-nums">
                                    {minPrice !== null && maxPrice !== null ? (
                                        minPrice === maxPrice ? (
                                            <span className="whitespace-nowrap">
                                                {formatIDR(minPrice)}
                                            </span>
                                        ) : (
                                            <>
                                                <span className="whitespace-nowrap">
                                                    {formatIDR(minPrice)}
                                                </span>{' '}
                                                <span className="font-normal text-muted-foreground">
                                                    –
                                                </span>{' '}
                                                <span className="whitespace-nowrap">
                                                    {formatIDR(maxPrice)}
                                                </span>
                                            </>
                                        )
                                    ) : (
                                        '—'
                                    )}
                                </p>
                            </div>
                            <div className="bg-card px-4 py-2.5">
                                <p className="text-xs text-muted-foreground">
                                    Total quoted
                                </p>
                                <p className="mt-0.5 flex flex-wrap items-baseline gap-x-1.5 text-sm font-semibold tabular-nums">
                                    <span>
                                        {formatNumber(totalQuotedQty)}{' '}
                                        {priceHistory.part.uom}
                                    </span>
                                    <span className="text-xs font-normal text-muted-foreground">
                                        {points.length}{' '}
                                        {points.length === 1
                                            ? 'quote'
                                            : 'quotes'}
                                    </span>
                                </p>
                            </div>
                        </div>
                    )}

                    <CardContent className="flex flex-1 flex-col p-0">
                        <div className="flex flex-1 flex-col px-4 pt-4 pb-2">
                            <div className="relative min-h-72 flex-1">
                                {priceHistory ? (
                                    points.length > 0 ? (
                                        <ChartContainer
                                            config={chartConfig}
                                            className="aspect-auto h-72 w-full lg:absolute lg:inset-0 lg:h-full"
                                        >
                                            <ComposedChart
                                                data={points}
                                                margin={{
                                                    top: 8,
                                                    right: 4,
                                                    bottom: 0,
                                                    left: 4,
                                                }}
                                            >
                                                <CartesianGrid
                                                    vertical={false}
                                                />
                                                <XAxis
                                                    dataKey="date"
                                                    tickLine={false}
                                                    axisLine={false}
                                                    tickFormatter={(
                                                        value: string,
                                                    ) =>
                                                        formatDate(
                                                            value,
                                                        ).replace(/ \d{4}$/, '')
                                                    }
                                                    minTickGap={24}
                                                />
                                                <YAxis
                                                    yAxisId="price"
                                                    tickLine={false}
                                                    axisLine={false}
                                                    width={56}
                                                    padding={{
                                                        top: 12,
                                                        bottom: 12,
                                                    }}
                                                    tickFormatter={(
                                                        value: number,
                                                    ) => compact.format(value)}
                                                    domain={['auto', 'auto']}
                                                />
                                                <YAxis
                                                    yAxisId="qty"
                                                    orientation="right"
                                                    tickLine={false}
                                                    axisLine={false}
                                                    width={32}
                                                />
                                                <ChartTooltip
                                                    content={
                                                        <PriceTooltip
                                                            uom={
                                                                priceHistory
                                                                    .part.uom
                                                            }
                                                        />
                                                    }
                                                />
                                                {listPrice > 0 && (
                                                    <ReferenceLine
                                                        yAxisId="price"
                                                        y={listPrice}
                                                        ifOverflow="hidden"
                                                        stroke="var(--muted-foreground)"
                                                        strokeDasharray="4 4"
                                                        strokeOpacity={0.45}
                                                    />
                                                )}
                                                <Bar
                                                    yAxisId="qty"
                                                    dataKey="quantity"
                                                    fill="var(--color-quantity)"
                                                    fillOpacity={0.35}
                                                    radius={[4, 4, 0, 0]}
                                                    maxBarSize={22}
                                                    animationDuration={250}
                                                />
                                                <Line
                                                    yAxisId="price"
                                                    dataKey="unit_price"
                                                    type="linear"
                                                    stroke="var(--color-unit_price)"
                                                    strokeWidth={2}
                                                    dot={{ r: 3 }}
                                                    activeDot={{ r: 5 }}
                                                    animationDuration={250}
                                                />
                                            </ComposedChart>
                                        </ChartContainer>
                                    ) : (
                                        <div className="flex h-72 items-center justify-center text-sm text-muted-foreground lg:h-full">
                                            No quotation history recorded for
                                            this part yet.
                                        </div>
                                    )
                                ) : (
                                    <div className="h-72 animate-pulse rounded-lg bg-muted/40 lg:h-full" />
                                )}
                            </div>
                        </div>

                        {priceHistory && (
                            <div className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 border-t bg-muted/20 px-5 py-2.5 text-xs text-muted-foreground">
                                <div className="flex flex-wrap items-center gap-x-4 gap-y-1">
                                    <span className="flex items-center gap-1.5">
                                        <span className="h-0.5 w-4 rounded bg-chart-1" />
                                        Unit price (left)
                                    </span>
                                    <span className="flex items-center gap-1.5">
                                        <span className="size-2.5 rounded-sm bg-chart-2/40" />
                                        Quantity (right)
                                    </span>
                                    {listPrice > 0 && (
                                        <span className="flex items-center gap-1.5">
                                            <span className="w-4 border-t border-dashed border-muted-foreground/70" />
                                            List price
                                        </span>
                                    )}
                                </div>
                                {points.length > 0 && (
                                    <span className="tabular-nums">
                                        {points.length === 1
                                            ? formatDate(points[0].date)
                                            : `${formatDate(points[0].date)} – ${formatDate(points[points.length - 1].date)}`}
                                    </span>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <div className="grid gap-4 lg:grid-cols-3">
                <Card className="gap-0 overflow-hidden py-0 lg:col-span-2">
                    <CardHeader className="border-b px-5 py-3.5">
                        <CardTitle className="text-base">
                            Recent activity
                        </CardTitle>
                        <CardDescription>
                            Straight from the audit log every workflow step
                            writes to.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        {recentActivity.length > 0 ? (
                            <ul className="divide-y">
                                {recentActivity.map((activity) => (
                                    <li
                                        key={activity.id}
                                        className="flex gap-3 px-5 py-3 text-sm"
                                    >
                                        <span className="mt-1.5 size-2 shrink-0 rounded-full bg-primary" />
                                        <div className="min-w-0 flex-1">
                                            <Link
                                                href={
                                                    activity.subject.type ===
                                                    'quotation'
                                                        ? quotations.show(
                                                              activity.subject
                                                                  .key,
                                                          )
                                                        : inquiries.show(
                                                              activity.subject
                                                                  .key,
                                                          )
                                                }
                                                className="font-mono font-medium hover:text-primary focus-visible:rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                            >
                                                {activity.subject.number}
                                            </Link>{' '}
                                            <span className="text-muted-foreground">
                                                {activity.description}
                                            </span>
                                            <p className="mt-0.5 text-xs text-muted-foreground tabular-nums">
                                                {activity.user ?? 'Web form'} ·{' '}
                                                {timeAgo(activity.created_at)}
                                            </p>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="px-5 py-8 text-sm text-muted-foreground">
                                No recent workflow activity yet.
                            </p>
                        )}
                    </CardContent>
                </Card>

                <div className="grid content-start gap-4">
                    <Card className="flex-row items-center gap-4 p-4">
                        <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-sky-500/10 text-sky-600 dark:text-sky-400">
                            <Truck className="size-5" />
                        </span>
                        <div>
                            <p className="text-2xl leading-none font-semibold tabular-nums">
                                {formatNumber(onDelivery)}
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Orders on delivery
                            </p>
                        </div>
                    </Card>

                    {latestQuotation && (
                        <Link
                            href={quotations.show(latestQuotation.ulid)}
                            className="block rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <Card className="gap-2 p-4 transition hover:border-foreground/20 hover:shadow-md">
                                <p className="flex items-center gap-1.5 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    <FileText className="size-3.5" /> Latest
                                    quotation
                                </p>
                                <p className="font-mono text-sm font-medium">
                                    {latestQuotation.number}
                                </p>
                                <p className="truncate text-sm text-muted-foreground">
                                    {latestQuotation.customer}
                                </p>
                                <div className="flex items-center justify-between gap-2 pt-0.5">
                                    <StatusBadge
                                        kind="quotation"
                                        status={latestQuotation.status}
                                    />
                                    <span className="text-xs text-muted-foreground tabular-nums">
                                        {timeAgo(latestQuotation.created_at)}
                                    </span>
                                </div>
                            </Card>
                        </Link>
                    )}

                    {latestInquiry && (
                        <Link
                            href={inquiries.show(latestInquiry.ulid)}
                            className="block rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <Card className="gap-2 p-4 transition hover:border-foreground/20 hover:shadow-md">
                                <p className="flex items-center gap-1.5 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    <Inbox className="size-3.5" /> Latest
                                    inquiry
                                </p>
                                <p className="font-mono text-sm font-medium">
                                    {latestInquiry.number}
                                </p>
                                <p className="truncate text-sm text-muted-foreground">
                                    {latestInquiry.name}
                                    {latestInquiry.company &&
                                        ` · ${latestInquiry.company}`}
                                </p>
                                <div className="flex items-center justify-between gap-2 pt-0.5">
                                    <StatusBadge
                                        kind="inquiry"
                                        status={latestInquiry.status}
                                    />
                                    <span className="text-xs text-muted-foreground tabular-nums">
                                        {timeAgo(latestInquiry.created_at)}
                                    </span>
                                </div>
                            </Card>
                        </Link>
                    )}
                </div>
            </div>
        </>
    );
}

function PipelinePanel({
    title,
    totalLabel,
    viewAllHref,
    items,
}: {
    title: string;
    totalLabel: string;
    viewAllHref: NonNullable<InertiaLinkProps['href']>;
    items: {
        key: string;
        badge: ReactNode;
        value: number;
        href: NonNullable<InertiaLinkProps['href']>;
    }[];
}) {
    return (
        <Card className="@container gap-0 overflow-hidden py-0">
            <div className="flex items-center justify-between gap-2 border-b bg-muted/30 px-4 py-2">
                <h2 className="text-sm font-medium text-foreground">{title}</h2>
                <Link
                    href={viewAllHref}
                    prefetch
                    className="text-xs font-medium text-muted-foreground tabular-nums transition-colors hover:text-primary focus-visible:rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    {totalLabel}
                </Link>
            </div>
            <div className="grid grid-cols-2 gap-px bg-border @[42rem]:grid-cols-4">
                {items.map((item) => (
                    <Link
                        key={item.key}
                        href={item.href}
                        prefetch
                        className="group flex flex-col items-start justify-between gap-1.5 bg-card px-3.5 py-2.5 transition-colors hover:bg-muted/50 focus-visible:relative focus-visible:z-10 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset @[24rem]:flex-row @[24rem]:items-center @[42rem]:flex-col @[42rem]:items-start"
                    >
                        {item.badge}
                        <span className="text-xl leading-none font-semibold tabular-nums transition-colors group-hover:text-primary @[42rem]:text-2xl">
                            {formatNumber(item.value)}
                        </span>
                    </Link>
                ))}
            </div>
        </Card>
    );
}

function PriceTooltip({
    active,
    payload,
    uom,
}: {
    active?: boolean;
    payload?: ReadonlyArray<{ payload: Point }>;
    uom: string;
}) {
    const point = active ? payload?.[0]?.payload : null;

    if (!point) {
        return null;
    }

    return (
        <div className="rounded-lg border bg-background px-3 py-2 text-xs shadow-md">
            <p className="font-medium">{point.number}</p>
            <p className="text-muted-foreground">{point.customer}</p>
            <p className="mt-1">
                <span className="text-muted-foreground">Price </span>
                <span className="font-medium tabular-nums">
                    {formatIDR(point.unit_price)}
                </span>
            </p>
            <p>
                <span className="text-muted-foreground">Qty </span>
                <span className="font-medium tabular-nums">
                    {formatNumber(point.quantity)} {uom}
                </span>
            </p>
            <p className="text-muted-foreground">{formatDate(point.date)}</p>
        </div>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
