import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Archive, UserPlus } from 'lucide-react';
import { DataTable } from '@/components/data-table/data-table';
import { PageHeader } from '@/components/page-header';
import { clean } from '@/components/status-filter-pills';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import customers from '@/routes/customers';
import type { Paginated, TableFilters } from '@/types';

type Row = {
    id: number;
    ulid: string;
    code: string;
    name: string;
    address: string;
    channel: 'direct' | 'marketplace';
    contacts: { name: string; phone: string | null; email: string | null }[];
    quotations_count: number;
    updated_at: string;
    archived: boolean;
};

const columns: ColumnDef<Row>[] = [
    {
        accessorKey: 'code',
        header: 'Code',
        meta: { sort: 'code' },
        cell: ({ row }) => (
            <span className="font-mono text-xs font-semibold">
                {row.original.code}
            </span>
        ),
    },
    {
        accessorKey: 'name',
        header: 'Customer',
        meta: { sort: 'name' },
        cell: ({ row }) => (
            <div>
                <Link
                    href={customers.edit(row.original.ulid)}
                    className="font-medium text-primary hover:underline"
                >
                    {row.original.name}
                </Link>
                <p className="line-clamp-1 text-xs text-muted-foreground">
                    {row.original.address}
                </p>
            </div>
        ),
    },
    {
        id: 'contacts',
        header: 'Contacts',
        cell: ({ row }) => {
            const [first, ...rest] = row.original.contacts;

            return first ? (
                <div className="text-xs">
                    <p>
                        {first.name}
                        {rest.length > 0 && (
                            <span className="text-muted-foreground">
                                {' '}
                                +{rest.length}
                            </span>
                        )}
                    </p>
                    <p className="text-muted-foreground">{first.email}</p>
                </div>
            ) : (
                <span className="text-muted-foreground">—</span>
            );
        },
    },
    {
        accessorKey: 'channel',
        header: 'Channel',
        cell: ({ row }) => (
            <span
                className={cn(
                    'rounded-md px-1.5 py-0.5 text-xs capitalize',
                    row.original.channel === 'marketplace'
                        ? 'bg-violet-500/10 text-violet-700 dark:text-violet-300'
                        : 'bg-muted',
                )}
            >
                {row.original.channel}
            </span>
        ),
    },
    {
        accessorKey: 'quotations_count',
        header: 'Quotations',
        meta: { className: 'text-center' },
    },
    {
        accessorKey: 'updated_at',
        header: 'Updated',
        meta: { sort: 'updated_at' },
        cell: ({ row }) => formatDate(row.original.updated_at),
    },
];

export default function CustomerIndex({
    customers: data,
    filters,
    archivedCount,
}: {
    customers: Paginated<Row>;
    filters: TableFilters & { archived: boolean };
    archivedCount: number;
}) {
    return (
        <>
            <Head title="Customers" />
            <PageHeader
                title={filters.archived ? 'Archived customers' : 'Customers'}
                description="Companies you quote, with their contact people."
                note="customers"
                actions={
                    <Button asChild size="sm">
                        <Link href={customers.create()}>
                            <UserPlus /> New customer
                        </Link>
                    </Button>
                }
            />
            <DataTable
                data={data}
                columns={columns}
                filters={filters}
                searchPlaceholder="Code, name or contact…"
                toolbar={
                    <Button
                        variant={filters.archived ? 'secondary' : 'ghost'}
                        size="sm"
                        onClick={() =>
                            router.get(
                                window.location.pathname,
                                clean({
                                    ...filters,
                                    archived: !filters.archived,
                                    page: undefined,
                                }),
                                { preserveState: true, replace: true },
                            )
                        }
                    >
                        <Archive />{' '}
                        {filters.archived
                            ? 'Show active'
                            : `Archived (${archivedCount})`}
                    </Button>
                }
            />
        </>
    );
}

CustomerIndex.layout = {
    breadcrumbs: [{ title: 'Customers', href: customers.index() }],
};
