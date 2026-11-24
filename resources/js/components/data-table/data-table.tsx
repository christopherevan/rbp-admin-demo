import { router } from '@inertiajs/react';
import {
    flexRender,
    getCoreRowModel,
    useReactTable,
} from '@tanstack/react-table';
import type { ColumnDef } from '@tanstack/react-table';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    ChevronLeft,
    ChevronRight,
    Search,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { useEffect, useRef, useState } from 'react';
import { clean } from '@/components/status-filter-pills';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import type { Paginated, TableFilters } from '@/types';

declare module '@tanstack/react-table' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface ColumnMeta<TData, TValue> {
        /** Query-string sort key; the column header becomes a sort toggle. */
        sort?: string;
        className?: string;
    }
}

/**
 * A server-side table: search, sort, page and page size all live in the
 * query string and are applied by App\Support\TableQuery.
 */
export function DataTable<T>({
    data,
    columns,
    filters,
    searchPlaceholder = 'Search…',
    toolbar,
    empty = 'Nothing here yet.',
    onRowClick,
}: {
    data: Paginated<T>;
    columns: ColumnDef<T>[];
    filters: TableFilters;
    searchPlaceholder?: string;
    toolbar?: ReactNode;
    empty?: ReactNode;
    onRowClick?: (row: T) => void;
}) {
    const [search, setSearch] = useState(filters.q ?? '');
    const first = useRef(true);

    const visit = (changes: Record<string, unknown>) => {
        router.get(
            window.location.pathname,
            clean({ ...filters, ...changes }),
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    useEffect(() => {
        if (first.current) {
            first.current = false;

            return;
        }

        const timer = setTimeout(
            () => visit({ q: search, page: undefined }),
            300,
        );

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    // eslint-disable-next-line react-hooks/incompatible-library
    const table = useReactTable({
        data: data.data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        manualPagination: true,
        manualSorting: true,
        rowCount: data.total,
    });

    const sort = filters.sort ?? '';

    const toggleSort = (key: string) => {
        const next = sort === key ? `-${key}` : sort === `-${key}` ? '' : key;
        visit({ sort: next || undefined, page: undefined });
    };

    return (
        <div className="space-y-3">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div className="relative w-full sm:max-w-xs">
                    <Search className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder={searchPlaceholder}
                        className="pl-8"
                    />
                </div>
                {toolbar && (
                    <div className="flex flex-wrap items-center gap-2">
                        {toolbar}
                    </div>
                )}
            </div>

            <div className="overflow-hidden rounded-xl border bg-card">
                <Table>
                    <TableHeader className="bg-muted/40">
                        {table.getHeaderGroups().map((group) => (
                            <TableRow
                                key={group.id}
                                className="hover:bg-transparent"
                            >
                                {group.headers.map((header) => {
                                    const key =
                                        header.column.columnDef.meta?.sort;
                                    const label = header.isPlaceholder
                                        ? null
                                        : flexRender(
                                              header.column.columnDef.header,
                                              header.getContext(),
                                          );

                                    return (
                                        <TableHead
                                            key={header.id}
                                            className={cn(
                                                'text-xs font-semibold tracking-wide text-muted-foreground uppercase',
                                                header.column.columnDef.meta
                                                    ?.className,
                                            )}
                                        >
                                            {key ? (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        toggleSort(key)
                                                    }
                                                    className="-ml-1 inline-flex items-center gap-1 rounded px-1 uppercase hover:text-foreground"
                                                >
                                                    {label}
                                                    {sort === key ? (
                                                        <ArrowUp className="size-3.5" />
                                                    ) : sort === `-${key}` ? (
                                                        <ArrowDown className="size-3.5" />
                                                    ) : (
                                                        <ArrowUpDown className="size-3.5 opacity-40" />
                                                    )}
                                                </button>
                                            ) : (
                                                label
                                            )}
                                        </TableHead>
                                    );
                                })}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {table.getRowModel().rows.length ? (
                            table.getRowModel().rows.map((row) => (
                                <TableRow
                                    key={row.id}
                                    onClick={
                                        onRowClick
                                            ? () => onRowClick(row.original)
                                            : undefined
                                    }
                                    className={cn(
                                        onRowClick && 'cursor-pointer',
                                    )}
                                >
                                    {row.getVisibleCells().map((cell) => (
                                        <TableCell
                                            key={cell.id}
                                            className={
                                                cell.column.columnDef.meta
                                                    ?.className
                                            }
                                        >
                                            {flexRender(
                                                cell.column.columnDef.cell,
                                                cell.getContext(),
                                            )}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow className="hover:bg-transparent">
                                <TableCell
                                    colSpan={columns.length}
                                    className="h-28 text-center text-muted-foreground"
                                >
                                    {empty}
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            <div className="flex flex-col items-center justify-between gap-2 text-sm text-muted-foreground sm:flex-row">
                <p>
                    {data.total
                        ? `Showing ${data.from}–${data.to} of ${data.total}`
                        : 'No results'}
                </p>
                <div className="flex items-center gap-2">
                    <Select
                        value={String(data.per_page)}
                        onValueChange={(value) =>
                            visit({ per_page: value, page: undefined })
                        }
                    >
                        <SelectTrigger size="sm">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {[10, 15, 25, 50].map((n) => (
                                <SelectItem key={n} value={String(n)}>
                                    {n} per page
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-8"
                        disabled={data.current_page <= 1}
                        onClick={() => visit({ page: data.current_page - 1 })}
                        aria-label="Previous page"
                    >
                        <ChevronLeft />
                    </Button>
                    <span className="tabular-nums">
                        {data.current_page} / {data.last_page}
                    </span>
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-8"
                        disabled={data.current_page >= data.last_page}
                        onClick={() => visit({ page: data.current_page + 1 })}
                        aria-label="Next page"
                    >
                        <ChevronRight />
                    </Button>
                </div>
            </div>
        </div>
    );
}
