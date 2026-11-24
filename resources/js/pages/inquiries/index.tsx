import { Head, Link } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { ExternalLink } from 'lucide-react';
import { DataTable } from '@/components/data-table/data-table';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { StatusFilterPills } from '@/components/status-filter-pills';
import { Button } from '@/components/ui/button';
import { formatDate, timeAgo } from '@/lib/format';
import { inquiryStatuses } from '@/lib/status';
import inquiries from '@/routes/inquiries';
import quoteRequest from '@/routes/quote-request';
import type { InquiryStatus, Paginated, TableFilters } from '@/types';

type Row = {
    id: number;
    ulid: string;
    number: string;
    name: string;
    company: string | null;
    email: string;
    phone: string | null;
    source: string;
    items_count: number;
    status: InquiryStatus;
    assignee: string | null;
    created_at: string;
};

const columns: ColumnDef<Row>[] = [
    {
        accessorKey: 'number',
        header: 'Inquiry',
        meta: { sort: 'number' },
        cell: ({ row }) => (
            <Link
                href={inquiries.show(row.original.ulid)}
                className="font-medium whitespace-nowrap text-primary hover:underline"
            >
                {row.original.number}
            </Link>
        ),
    },
    {
        id: 'from',
        header: 'From',
        cell: ({ row }) => (
            <div>
                <p className="font-medium">{row.original.name}</p>
                <p className="text-xs text-muted-foreground">
                    {row.original.company ?? 'Private buyer'}
                </p>
            </div>
        ),
    },
    {
        id: 'contact',
        header: 'Contact',
        cell: ({ row }) => (
            <div className="text-xs">
                <p>{row.original.email}</p>
                <p className="text-muted-foreground">{row.original.phone}</p>
            </div>
        ),
    },
    {
        accessorKey: 'source',
        header: 'Source',
        cell: ({ row }) => (
            <span className="capitalize">{row.original.source}</span>
        ),
    },
    {
        accessorKey: 'items_count',
        header: 'Items',
        meta: { className: 'text-center' },
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => (
            <StatusBadge kind="inquiry" status={row.original.status} />
        ),
    },
    {
        accessorKey: 'assignee',
        header: 'Sales',
        cell: ({ row }) =>
            row.original.assignee ?? (
                <span className="text-muted-foreground">Unassigned</span>
            ),
    },
    {
        accessorKey: 'created_at',
        header: 'Received',
        meta: { sort: 'created_at' },
        cell: ({ row }) => (
            <span title={formatDate(row.original.created_at)}>
                {timeAgo(row.original.created_at)}
            </span>
        ),
    },
];

export default function InquiryIndex({
    inquiries: data,
    filters,
    counts,
}: {
    inquiries: Paginated<Row>;
    filters: TableFilters & { status: InquiryStatus | null };
    counts: Record<string, number>;
}) {
    return (
        <>
            <Head title="Inquiries" />
            <PageHeader
                title="Inquiries"
                description="Quote requests from the public website, triaged into quotations."
                note="inquiries"
                actions={
                    <Button asChild size="sm" variant="outline">
                        <a
                            href={quoteRequest.create.url()}
                            target="_blank"
                            rel="noreferrer"
                        >
                            <ExternalLink /> Send a test inquiry
                        </a>
                    </Button>
                }
            />
            <StatusFilterPills
                statuses={inquiryStatuses}
                counts={counts}
                active={filters.status}
                filters={filters}
            />
            <DataTable
                data={data}
                columns={columns}
                filters={filters}
                searchPlaceholder="Number, name, company, email…"
                empty="No inquiries match."
            />
        </>
    );
}

InquiryIndex.layout = {
    breadcrumbs: [{ title: 'Inquiries', href: inquiries.index() }],
};
