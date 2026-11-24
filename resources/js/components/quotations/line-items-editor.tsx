import { router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    Plus,
    RotateCcw,
    Save,
    Send,
    Trash2,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { MoneyInput } from '@/components/money-input';
import { PartCombobox } from '@/components/part-combobox';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useUnsavedChanges } from '@/hooks/use-unsaved-changes';
import { formatIDR } from '@/lib/format';
import { cn } from '@/lib/utils';
import quotations from '@/routes/quotations';
import type { PartOption, QuotationStatus } from '@/types';
import { TotalsList } from './totals-card';
import { calculateTotals } from './types';
import type { Line } from './types';

type Row = {
    key: string;
    part: PartOption | null;
    quantity: number;
    unit_price: number;
};

const toRows = (lines: Line[]): Row[] =>
    lines.map((line) => ({
        key: String(line.id),
        part: { ...line.part },
        quantity: line.quantity,
        unit_price: line.unit_price,
    }));

let nextKey = 0;

/**
 * Editable line items: part typeahead, quantity, negotiated price, live
 * totals, and a guard against leaving with unsaved edits.
 */
export function LineItemsEditor({
    quotationId,
    status,
    lines,
    discountPct,
    ppnRate,
    dpAmount,
    maxLines,
}: {
    quotationId: string;
    status: QuotationStatus;
    lines: Line[];
    discountPct: number;
    ppnRate: number;
    dpAmount: number;
    maxLines: number;
}) {
    const { errors } = usePage().props as { errors: Record<string, string> };
    const [rows, setRows] = useState<Row[]>(() =>
        lines.length ? toRows(lines) : [blank()],
    );
    const [saving, setSaving] = useState(false);

    // Blank rows (no part picked yet) aren't changes worth warning about.
    const fingerprint = (list: Row[]) =>
        JSON.stringify(
            list
                .filter((r) => r.part)
                .map((r) => [r.part!.id, r.quantity, r.unit_price]),
        );
    const serialized = fingerprint(rows);
    const original = useMemo(() => fingerprint(toRows(lines)), [lines]);
    const dirty = serialized !== original;

    useUnsavedChanges(dirty);

    const subtotal = rows.reduce(
        (sum, row) => sum + (row.part ? row.quantity * row.unit_price : 0),
        0,
    );
    const totals = calculateTotals(subtotal, discountPct, ppnRate, dpAmount);
    const complete =
        rows.length > 0 && rows.every((row) => row.part && row.quantity > 0);

    const update = (key: string, changes: Partial<Row>) =>
        setRows((current) =>
            current.map((row) =>
                row.key === key ? { ...row, ...changes } : row,
            ),
        );

    const save = () => {
        router.put(
            quotations.lines.update.url(quotationId),
            {
                lines: rows
                    .filter((row) => row.part)
                    .map((row) => ({
                        part_id: row.part!.id,
                        quantity: row.quantity,
                        unit_price: row.unit_price,
                    })),
            },
            {
                preserveScroll: true,
                onStart: () => setSaving(true),
                onFinish: () => setSaving(false),
            },
        );
    };

    const lineErrors = Object.entries(errors).filter(([key]) =>
        key.startsWith('lines'),
    );

    return (
        <div className="space-y-4">
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full min-w-[760px] table-fixed text-sm">
                    <thead className="bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                        <tr>
                            <th className="w-8 px-3 py-2">#</th>
                            <th className="px-3 py-2">Part</th>
                            <th className="w-36 px-3 py-2">Qty</th>
                            <th className="w-44 px-3 py-2">Unit price</th>
                            <th className="w-36 px-3 py-2 text-right">
                                Line total
                            </th>
                            <th className="w-10" />
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {rows.map((row, index) => {
                            const short =
                                row.part?.stocked &&
                                row.quantity > row.part.stock_qty;

                            return (
                                <tr key={row.key} className="align-top">
                                    <td className="px-3 py-2.5 text-muted-foreground tabular-nums">
                                        {index + 1}
                                    </td>
                                    <td className="px-3 py-2">
                                        <PartCombobox
                                            value={row.part}
                                            excluded={rows
                                                .map((r) => r.part?.id)
                                                .filter(
                                                    (id): id is number => !!id,
                                                )}
                                            onSelect={(part) =>
                                                update(row.key, {
                                                    part,
                                                    unit_price: part.list_price,
                                                })
                                            }
                                        />
                                        {short && (
                                            <p className="mt-1 flex items-center gap-1 text-xs text-amber-600 dark:text-amber-400">
                                                <AlertTriangle className="size-3" />{' '}
                                                Only {row.part!.stock_qty}{' '}
                                                {row.part!.uom} in stock
                                            </p>
                                        )}
                                        {row.part &&
                                            row.unit_price !==
                                                row.part.list_price && (
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    List price{' '}
                                                    {formatIDR(
                                                        row.part.list_price,
                                                    )}
                                                </p>
                                            )}
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="relative">
                                            <Input
                                                type="number"
                                                min={1}
                                                value={row.quantity}
                                                onChange={(e) =>
                                                    update(row.key, {
                                                        quantity: Math.max(
                                                            0,
                                                            Number(
                                                                e.target.value,
                                                            ),
                                                        ),
                                                    })
                                                }
                                                className={cn(
                                                    'tabular-nums',
                                                    row.part && 'pr-12',
                                                )}
                                            />
                                            <span className="pointer-events-none absolute inset-y-0 right-2 flex items-center text-xs text-muted-foreground">
                                                {row.part?.uom}
                                            </span>
                                        </div>
                                    </td>
                                    <td className="px-3 py-2">
                                        <MoneyInput
                                            value={row.unit_price}
                                            onValueChange={(unit_price) =>
                                                update(row.key, { unit_price })
                                            }
                                        />
                                    </td>
                                    <td className="px-3 py-2.5 text-right font-medium whitespace-nowrap tabular-nums">
                                        {row.part
                                            ? formatIDR(
                                                  row.quantity * row.unit_price,
                                              )
                                            : '—'}
                                    </td>
                                    <td className="py-2 pr-2">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-9 text-muted-foreground hover:text-destructive"
                                            onClick={() =>
                                                setRows((current) =>
                                                    current.filter(
                                                        (r) =>
                                                            r.key !== row.key,
                                                    ),
                                                )
                                            }
                                            aria-label="Remove line"
                                        >
                                            <Trash2 />
                                        </Button>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>

            {lineErrors.length > 0 && (
                <ul className="space-y-0.5 text-sm text-red-600 dark:text-red-400">
                    {lineErrors.map(([key, message]) => (
                        <li key={key}>{message}</li>
                    ))}
                </ul>
            )}

            <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <Button
                    variant="outline"
                    size="sm"
                    disabled={rows.length >= maxLines}
                    onClick={() => setRows((current) => [...current, blank()])}
                >
                    <Plus /> Add line
                </Button>
                <div className="w-full space-y-3 md:w-80">
                    <TotalsList
                        totals={totals}
                        discountPct={discountPct}
                        ppnRate={ppnRate}
                    />
                    <div className="flex items-center justify-end gap-2">
                        {dirty && (
                            <>
                                <span className="mr-auto text-xs font-medium text-amber-600 dark:text-amber-400">
                                    Unsaved changes
                                </span>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() =>
                                        setRows(
                                            lines.length
                                                ? toRows(lines)
                                                : [blank()],
                                        )
                                    }
                                >
                                    <RotateCcw /> Undo
                                </Button>
                            </>
                        )}
                        <Button
                            size="sm"
                            onClick={save}
                            disabled={
                                !complete ||
                                saving ||
                                (!dirty && status !== 'not_submitted')
                            }
                        >
                            {saving ? (
                                <Spinner />
                            ) : status === 'not_submitted' ? (
                                <Send />
                            ) : (
                                <Save />
                            )}
                            {status === 'not_submitted'
                                ? 'Save & send to customer'
                                : 'Save changes'}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}

function blank(): Row {
    nextKey += 1;

    return { key: `new-${nextKey}`, part: null, quantity: 1, unit_price: 0 };
}
