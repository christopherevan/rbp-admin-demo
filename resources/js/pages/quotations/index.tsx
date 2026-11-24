import { Head, Link, router, usePage } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { FilePlus2 } from 'lucide-react';
import { DataTable } from '@/components/data-table/data-table';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { clean, StatusFilterPills } from '@/components/status-filter-pills';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { formatDate, formatIDR } from '@/lib/format';
import { quotationStatuses } from '@/lib/status';
import quotations from '@/routes/quotations';
import type { Paginated, QuotationStatus, TableFilters } from '@/types';

type Row = {
    id: number;
    ulid: string;
    number: string;
    customer: string;
    status: QuotationStatus;
    total: number;
    customer_po_number: string | null;
    sales_rep: string;
    created_at: string;
};

const columns: ColumnDef<Row>[] = [
    {
        accessorKey: 'number',
        header: 'Quotation',
        meta: { sort: 'number' },
        cell: ({ row }) => (
            <Link
                href={quotations.show(row.original.ulid)}
                className="font-medium text-primary hover:underline"
            >
                {row.original.number}
            </Link>
        ),
    },
    {
        accessorKey: 'customer',
        header: 'Customer',
        cell: ({ row }) => (
            <span className="line-clamp-1">{row.original.customer}</span>
        ),
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => (
            <StatusBadge kind="quotation" status={row.original.status} />
        ),
    },
    {
        accessorKey: 'total',
        header: 'Total',
        meta: { className: 'text-right' },
        cell: ({ row }) => (
            <span className="tabular-nums">
                {row.original.total ? formatIDR(row.original.total) : '—'}
            </span>
        ),
    },
    {
        accessorKey: 'customer_po_number',
        header: 'Customer PO',
        cell: ({ row }) =>
            row.original.customer_po_number ?? (
                <span className="text-muted-foreground">—</span>
            ),
    },
    { accessorKey: 'sales_rep', header: 'Sales' },
    {
        accessorKey: 'created_at',
        header: 'Created',
        meta: { sort: 'created_at' },
        cell: ({ row }) => formatDate(row.original.created_at),
    },
];

export default function QuotationIndex({
    quotations: data,
    filters,
    counts,
}: {
    quotations: Paginated<Row>;
    filters: TableFilters & { status: QuotationStatus | null; mine: boolean };
    counts: Record<string, number>;
}) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Quotations" />
            <PageHeader
                title="Quotations"
                description="From first draft to paid invoice."
                note="quotations"
                actions={
                    <Button asChild size="sm">
                        <Link href={quotations.create()}>
                            <FilePlus2 /> New quotation
                        </Link>
                    </Button>
                }
            />

            <StatusFilterPills
                statuses={quotationStatuses}
                counts={counts}
                active={filters.status}
                filters={filters}
            />

            <DataTable
                data={data}
                columns={columns}
                filters={filters}
                searchPlaceholder="Number, customer or PO…"
                toolbar={
                    auth.user?.role === 'sales' && (
                        <label className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Switch
                                checked={filters.mine}
                                onCheckedChange={(mine) =>
                                    router.get(
                                        window.location.pathname,
                                        clean({
                                            ...filters,
                                            mine,
                                            page: undefined,
                                        }),
                                        {
                                            preserveState: true,
                                            replace: true,
                                        },
                                    )
                                }
                            />
                            Only mine
                        </label>
                    )
                }
                empty="No quotations match."
            />
        </>
    );
}

QuotationIndex.layout = {
    breadcrumbs: [{ title: 'Quotations', href: quotations.index() }],
};
