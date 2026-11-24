import { Head, router, useForm, usePage } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import {
    AlertTriangle,
    Archive,
    ArchiveRestore,
    PackagePlus,
    Pencil,
    PlusSquare,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table/data-table';
import { FormField } from '@/components/form-field';
import { MoneyInput } from '@/components/money-input';
import { PageHeader } from '@/components/page-header';
import { clean } from '@/components/status-filter-pills';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { formatDate, formatIDR, formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import parts from '@/routes/parts';
import type { Paginated, TableFilters } from '@/types';

type Part = {
    id: number;
    ulid: string;
    part_number: string;
    description: string;
    uom_id: number;
    uom: string;
    list_price: number;
    stocked: boolean;
    stock_qty: number;
    updated_at: string;
    discontinued: boolean;
};

type Uom = { id: number; code: string; name: string };

export default function PartIndex({
    parts: data,
    filters,
    uoms,
    lowStockCount,
    discontinuedCount,
}: {
    parts: Paginated<Part>;
    filters: TableFilters & { stock?: string; discontinued: boolean };
    uoms: Uom[];
    lowStockCount: number;
    discontinuedCount: number;
}) {
    const { auth } = usePage().props;
    const [editing, setEditing] = useState<Part | 'new' | null>(null);
    const [adjusting, setAdjusting] = useState<Part | null>(null);

    const columns: ColumnDef<Part>[] = [
        {
            accessorKey: 'part_number',
            header: 'Part number',
            meta: { sort: 'part_number' },
            cell: ({ row }) => (
                <span className="font-mono text-xs font-semibold">
                    {row.original.part_number}
                </span>
            ),
        },
        {
            accessorKey: 'description',
            header: 'Description',
            cell: ({ row }) => (
                <span className="line-clamp-2 max-w-md">
                    {row.original.description}
                </span>
            ),
        },
        {
            accessorKey: 'list_price',
            header: 'List price',
            meta: { sort: 'list_price', className: 'text-right' },
            cell: ({ row }) => (
                <span className="tabular-nums">
                    {formatIDR(row.original.list_price)}
                </span>
            ),
        },
        {
            accessorKey: 'stock_qty',
            header: 'Stock',
            meta: { sort: 'stock_qty', className: 'text-right' },
            cell: ({ row }) =>
                row.original.stocked ? (
                    <span
                        className={cn(
                            'inline-flex items-center gap-1 tabular-nums',
                            row.original.stock_qty < 10 &&
                                'font-medium text-amber-600 dark:text-amber-400',
                        )}
                    >
                        {row.original.stock_qty < 10 && (
                            <AlertTriangle className="size-3.5" />
                        )}
                        {formatNumber(row.original.stock_qty)}{' '}
                        {row.original.uom}
                    </span>
                ) : (
                    <span className="text-xs text-muted-foreground">
                        Service
                    </span>
                ),
        },
        {
            accessorKey: 'updated_at',
            header: 'Updated',
            meta: { sort: 'updated_at' },
            cell: ({ row }) => formatDate(row.original.updated_at),
        },
        {
            id: 'actions',
            header: '',
            meta: { className: 'w-0 whitespace-nowrap text-right' },
            cell: ({ row }) =>
                row.original.discontinued ? (
                    auth.can?.archiveRecords && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() =>
                                router.post(
                                    parts.restore.url(row.original.ulid),
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <ArchiveRestore /> Restore
                        </Button>
                    )
                ) : (
                    <div className="flex justify-end gap-1">
                        {row.original.stocked && (
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-8"
                                title="Adjust stock"
                                onClick={() => setAdjusting(row.original)}
                            >
                                <PlusSquare />
                            </Button>
                        )}
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-8"
                            title="Edit"
                            onClick={() => setEditing(row.original)}
                        >
                            <Pencil />
                        </Button>
                        {auth.can?.archiveRecords && (
                            <ConfirmDialog
                                trigger={
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="size-8 text-muted-foreground hover:text-destructive"
                                        title="Discontinue"
                                    >
                                        <Trash2 />
                                    </Button>
                                }
                                title={`Discontinue ${row.original.part_number}?`}
                                description="It can no longer be added to quotations. Existing quotations keep showing it."
                                confirmLabel="Discontinue"
                                destructive
                                onConfirm={() =>
                                    router.delete(
                                        parts.destroy.url(row.original.ulid),
                                        { preserveScroll: true },
                                    )
                                }
                            />
                        )}
                    </div>
                ),
        },
    ];

    const visit = (params: Record<string, unknown>) =>
        router.get(
            window.location.pathname,
            clean({ ...filters, ...params, page: undefined }),
            { preserveState: true, replace: true },
        );

    return (
        <>
            <Head title="Parts & stock" />
            <PageHeader
                title={
                    filters.discontinued
                        ? 'Discontinued parts'
                        : 'Parts & stock'
                }
                description="The parts master: list prices pre-fill quotation lines; delivery orders deduct stock."
                note="parts"
                actions={
                    <Button size="sm" onClick={() => setEditing('new')}>
                        <PackagePlus /> New part
                    </Button>
                }
            />
            <DataTable
                data={data}
                columns={columns}
                filters={filters}
                searchPlaceholder="Part number or description…"
                toolbar={
                    <>
                        {!filters.discontinued && (
                            <Button
                                variant={
                                    filters.stock === 'low'
                                        ? 'secondary'
                                        : 'ghost'
                                }
                                size="sm"
                                onClick={() =>
                                    visit({
                                        stock:
                                            filters.stock === 'low'
                                                ? undefined
                                                : 'low',
                                    })
                                }
                            >
                                <AlertTriangle /> Low stock ({lowStockCount})
                            </Button>
                        )}
                        <Button
                            variant={
                                filters.discontinued ? 'secondary' : 'ghost'
                            }
                            size="sm"
                            onClick={() =>
                                visit({
                                    discontinued: !filters.discontinued,
                                    stock: undefined,
                                })
                            }
                        >
                            <Archive />{' '}
                            {filters.discontinued
                                ? 'Show active'
                                : `Discontinued (${discontinuedCount})`}
                        </Button>
                    </>
                }
            />

            {editing && (
                <PartDialog
                    key={editing === 'new' ? 'new' : editing.id}
                    part={editing === 'new' ? null : editing}
                    uoms={uoms}
                    onClose={() => setEditing(null)}
                />
            )}
            {adjusting && (
                <StockDialog
                    key={adjusting.id}
                    part={adjusting}
                    onClose={() => setAdjusting(null)}
                />
            )}
        </>
    );
}

function PartDialog({
    part,
    uoms,
    onClose,
}: {
    part: Part | null;
    uoms: Uom[];
    onClose: () => void;
}) {
    const form = useForm({
        part_number: part?.part_number ?? '',
        description: part?.description ?? '',
        uom_id: part?.uom_id ? String(part.uom_id) : String(uoms[0]?.id ?? ''),
        list_price: part?.list_price ?? 0,
        stocked: part?.stocked ?? true,
        stock_qty: 0,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(part ? parts.update(part.ulid) : parts.store(), {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <form onSubmit={submit} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>
                            {part ? `Edit ${part.part_number}` : 'New part'}
                        </DialogTitle>
                        <DialogDescription>
                            Part numbers are unique and stored in upper case.
                        </DialogDescription>
                    </DialogHeader>
                    <FormField
                        label="Part number"
                        htmlFor="part_number"
                        error={form.errors.part_number}
                    >
                        <Input
                            id="part_number"
                            value={form.data.part_number}
                            onChange={(e) =>
                                form.setData(
                                    'part_number',
                                    e.target.value.toUpperCase(),
                                )
                            }
                            className="font-mono"
                        />
                    </FormField>
                    <FormField
                        label="Description"
                        htmlFor="description"
                        error={form.errors.description}
                    >
                        <Input
                            id="description"
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                        />
                    </FormField>
                    <div className="grid grid-cols-2 gap-3">
                        <FormField label="Unit" error={form.errors.uom_id}>
                            <Select
                                value={form.data.uom_id}
                                onValueChange={(value) =>
                                    form.setData('uom_id', value)
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {uoms.map((uom) => (
                                        <SelectItem
                                            key={uom.id}
                                            value={String(uom.id)}
                                        >
                                            {uom.code} · {uom.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </FormField>
                        <FormField
                            label="List price"
                            error={form.errors.list_price}
                        >
                            <MoneyInput
                                value={form.data.list_price}
                                onValueChange={(value) =>
                                    form.setData('list_price', value)
                                }
                            />
                        </FormField>
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={form.data.stocked}
                            onCheckedChange={(checked) =>
                                form.setData('stocked', checked === true)
                            }
                        />
                        Track stock (untick for services such as splicing or
                        delivery)
                    </label>
                    {!part && form.data.stocked && (
                        <FormField
                            label="Opening stock"
                            htmlFor="stock_qty"
                            error={form.errors.stock_qty}
                        >
                            <Input
                                id="stock_qty"
                                type="number"
                                min={0}
                                value={form.data.stock_qty}
                                onChange={(e) =>
                                    form.setData(
                                        'stock_qty',
                                        Number(e.target.value),
                                    )
                                }
                            />
                        </FormField>
                    )}
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}{' '}
                            {part ? 'Save' : 'Add part'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function StockDialog({ part, onClose }: { part: Part; onClose: () => void }) {
    const form = useForm({ delta: 0, reason: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(parts.stock(part.ulid), {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <form onSubmit={submit} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>
                            Adjust stock · {part.part_number}
                        </DialogTitle>
                        <DialogDescription>
                            Currently {formatNumber(part.stock_qty)} {part.uom}.
                            Use a negative number to remove stock. The change is
                            written to the part's audit log.
                        </DialogDescription>
                    </DialogHeader>
                    <FormField
                        label="Change"
                        htmlFor="delta"
                        error={form.errors.delta}
                        hint={`New level: ${formatNumber(part.stock_qty + form.data.delta)} ${part.uom}`}
                    >
                        <Input
                            id="delta"
                            type="number"
                            value={form.data.delta}
                            onChange={(e) =>
                                form.setData('delta', Number(e.target.value))
                            }
                            autoFocus
                        />
                    </FormField>
                    <FormField
                        label="Reason"
                        htmlFor="reason"
                        error={form.errors.reason}
                    >
                        <Input
                            id="reason"
                            value={form.data.reason}
                            onChange={(e) =>
                                form.setData('reason', e.target.value)
                            }
                            placeholder="e.g. Goods received from supplier, stock take"
                        />
                    </FormField>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={form.processing || form.data.delta === 0}
                        >
                            Save
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

PartIndex.layout = {
    breadcrumbs: [{ title: 'Parts & stock', href: parts.index() }],
};
