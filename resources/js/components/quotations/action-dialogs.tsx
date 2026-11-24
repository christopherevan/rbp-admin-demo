import { useForm } from '@inertiajs/react';
import type { InertiaFormProps } from '@inertiajs/react';
import type { FormDataType, UrlMethodPair } from '@inertiajs/core';
import type { FormEvent, ReactNode } from 'react';
import { FormField } from '@/components/form-field';
import { MoneyInput } from '@/components/money-input';
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
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatIDR } from '@/lib/format';
import invoicesRoutes from '@/routes/invoices';
import quotations from '@/routes/quotations';
import type { Invoice, Line, QuotationPageProps } from './types';

type DialogProps = { open: boolean; onClose: () => void };

type SoRow = { id: number; quantity: number };

/** Shared shell: a dialog around a form that posts to one workflow endpoint. */
function ActionDialog<T extends FormDataType<T>>({
    open,
    onClose,
    title,
    description,
    initial,
    route,
    submitLabel,
    wide,
    children,
}: DialogProps & {
    title: string;
    description: ReactNode;
    initial: T;
    route: UrlMethodPair;
    submitLabel: string;
    wide?: boolean;
    children: (form: InertiaFormProps<T>) => ReactNode;
}) {
    const form = useForm<T>(initial) as InertiaFormProps<T>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(route, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onClose();
            },
        });
    };

    const errors = Object.values(form.errors as Record<string, string>);

    return (
        <Dialog open={open} onOpenChange={(value) => !value && onClose()}>
            <DialogContent className={wide ? 'sm:max-w-2xl' : undefined}>
                <form onSubmit={submit} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>{title}</DialogTitle>
                        <DialogDescription>{description}</DialogDescription>
                    </DialogHeader>
                    {children(form)}
                    {errors.length > 0 && (
                        <ul className="space-y-0.5 text-sm text-red-600 dark:text-red-400">
                            {errors.map((message) => (
                                <li key={message}>{message}</li>
                            ))}
                        </ul>
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
                            {form.processing && <Spinner />}
                            {submitLabel}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/** A file picker that keeps only the name: the demo never uploads a byte. */
function FileNameInput({
    value,
    onChange,
    accept,
}: {
    value: string;
    onChange: (name: string) => void;
    accept: string;
}) {
    return (
        <div className="grid gap-1.5">
            <Input
                type="file"
                accept={accept}
                onChange={(e) => onChange(e.target.files?.[0]?.name ?? '')}
            />
            <p className="text-xs text-muted-foreground">
                {value ? `Recorded as “${value}”. ` : ''}Only the file name is
                kept; this demo stores no files.
            </p>
        </div>
    );
}

export function PoDialog({
    quotationId,
    ...props
}: DialogProps & { quotationId: string }) {
    return (
        <ActionDialog
            {...props}
            title="Record customer PO"
            description="The customer accepted the quotation. Recording their purchase order locks the line items and starts fulfilment."
            initial={{ po_number: '', po_file_name: '' }}
            route={quotations.po.store(quotationId)}
            submitLabel="Record PO"
        >
            {(form) => (
                <>
                    <FormField label="Customer PO number" htmlFor="po_number">
                        <Input
                            id="po_number"
                            value={form.data.po_number}
                            onChange={(e) =>
                                form.setData('po_number', e.target.value)
                            }
                            placeholder="e.g. PO/SPL/2610/0412"
                            autoFocus
                        />
                    </FormField>
                    <FormField label="PO document">
                        <FileNameInput
                            value={form.data.po_file_name}
                            onChange={(name) =>
                                form.setData('po_file_name', name)
                            }
                            accept=".pdf,.jpg,.jpeg,.png"
                        />
                    </FormField>
                </>
            )}
        </ActionDialog>
    );
}

export function SalesOrderDialog({
    quotationId,
    lines,
    ...props
}: DialogProps & { quotationId: string; lines: Line[] }) {
    return (
        <ActionDialog
            {...props}
            wide
            title="Create sales order"
            description="Customers often confirm only part of a quotation. Untick lines or lower quantities for a partial order; only what's ordered gets delivered and invoiced."
            initial={{
                lines: lines.map((line) => ({
                    id: line.id,
                    quantity: line.quantity,
                })) as SoRow[],
            }}
            route={quotations.salesOrder.store(quotationId)}
            submitLabel="Create sales order"
        >
            {(form) => {
                const rows = form.data.lines as SoRow[];
                const setRow = (index: number, quantity: number) =>
                    form.setData(
                        'lines',
                        rows.map((r, j) =>
                            j === index ? { ...r, quantity } : r,
                        ),
                    );
                const total = rows.reduce(
                    (sum, row, i) => sum + row.quantity * lines[i].unit_price,
                    0,
                );

                return (
                    <div className="space-y-3">
                        <div className="overflow-x-auto rounded-lg border">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/40 text-left text-xs text-muted-foreground uppercase">
                                    <tr>
                                        <th className="w-10 px-3 py-2" />
                                        <th className="px-3 py-2">Part</th>
                                        <th className="w-24 px-3 py-2 text-right">
                                            Quoted
                                        </th>
                                        <th className="w-28 px-3 py-2">
                                            Order qty
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {lines.map((line, i) => {
                                        const row = rows[i];

                                        return (
                                            <tr key={line.id}>
                                                <td className="px-3 py-2">
                                                    <Checkbox
                                                        checked={
                                                            row.quantity > 0
                                                        }
                                                        onCheckedChange={(
                                                            checked,
                                                        ) =>
                                                            setRow(
                                                                i,
                                                                checked
                                                                    ? line.quantity
                                                                    : 0,
                                                            )
                                                        }
                                                    />
                                                </td>
                                                <td className="px-3 py-2">
                                                    <p className="font-medium">
                                                        {line.part.part_number}
                                                    </p>
                                                    <p className="line-clamp-1 text-xs text-muted-foreground">
                                                        {line.part.description}
                                                    </p>
                                                </td>
                                                <td className="px-3 py-2 text-right tabular-nums">
                                                    {line.quantity}{' '}
                                                    {line.part.uom}
                                                </td>
                                                <td className="px-3 py-2">
                                                    <Input
                                                        type="number"
                                                        min={0}
                                                        max={line.quantity}
                                                        value={row.quantity}
                                                        onChange={(e) =>
                                                            setRow(
                                                                i,
                                                                Math.min(
                                                                    line.quantity,
                                                                    Math.max(
                                                                        0,
                                                                        Number(
                                                                            e
                                                                                .target
                                                                                .value,
                                                                        ),
                                                                    ),
                                                                ),
                                                            )
                                                        }
                                                        className="h-8 tabular-nums"
                                                    />
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                        <p className="text-right text-sm text-muted-foreground">
                            Ordered subtotal{' '}
                            <span className="font-medium text-foreground tabular-nums">
                                {formatIDR(total)}
                            </span>
                        </p>
                    </div>
                );
            }}
        </ActionDialog>
    );
}

export function DeliveryOrderDialog({
    quotationId,
    defaults,
    ...props
}: DialogProps & {
    quotationId: string;
    defaults: QuotationPageProps['defaults'];
}) {
    return (
        <ActionDialog
            {...props}
            title="Create delivery order"
            description="Issues the delivery order (surat jalan) and deducts the ordered parts from stock."
            initial={{
                ship_to_name: defaults.name,
                ship_to_address: defaults.address,
                notes: '',
            }}
            route={quotations.deliveryOrder.store(quotationId)}
            submitLabel="Create delivery order"
        >
            {(form) => (
                <>
                    <FormField label="Ship to" htmlFor="ship_to_name">
                        <Input
                            id="ship_to_name"
                            value={form.data.ship_to_name}
                            onChange={(e) =>
                                form.setData('ship_to_name', e.target.value)
                            }
                        />
                    </FormField>
                    <FormField label="Address" htmlFor="ship_to_address">
                        <Textarea
                            id="ship_to_address"
                            rows={2}
                            value={form.data.ship_to_address}
                            onChange={(e) =>
                                form.setData('ship_to_address', e.target.value)
                            }
                        />
                    </FormField>
                    <FormField label="Notes for the driver" htmlFor="notes">
                        <Input
                            id="notes"
                            value={form.data.notes}
                            onChange={(e) =>
                                form.setData('notes', e.target.value)
                            }
                            placeholder="e.g. Unload at the rear warehouse, door 3."
                        />
                    </FormField>
                </>
            )}
        </ActionDialog>
    );
}

export function InvoiceDialog({
    quotationId,
    type,
    amount,
    defaults,
    ...props
}: DialogProps & {
    quotationId: string;
    type: 'down_payment' | 'final';
    amount: number;
    defaults: QuotationPageProps['defaults'];
}) {
    const final = type === 'final';

    return (
        <ActionDialog
            {...props}
            title={final ? 'Issue invoice' : 'Issue down payment invoice'}
            description={
                final
                    ? 'Bills what the sales order contains, less any down payment already invoiced. The invoice takes the delivery order’s number.'
                    : 'Bills the agreed down payment before the order ships.'
            }
            initial={{
                type,
                bill_to_name: defaults.name,
                bill_to_address: defaults.address,
            }}
            route={quotations.invoices.store(quotationId)}
            submitLabel="Issue invoice"
        >
            {(form) => (
                <>
                    <div className="rounded-lg bg-muted/50 p-3 text-sm">
                        Amount due{' '}
                        <span className="float-right font-semibold tabular-nums">
                            {formatIDR(amount)}
                        </span>
                    </div>
                    <FormField label="Bill to" htmlFor="bill_to_name">
                        <Input
                            id="bill_to_name"
                            value={form.data.bill_to_name}
                            onChange={(e) =>
                                form.setData('bill_to_name', e.target.value)
                            }
                        />
                    </FormField>
                    <FormField
                        label="Billing address"
                        htmlFor="bill_to_address"
                    >
                        <Textarea
                            id="bill_to_address"
                            rows={2}
                            value={form.data.bill_to_address}
                            onChange={(e) =>
                                form.setData('bill_to_address', e.target.value)
                            }
                        />
                    </FormField>
                </>
            )}
        </ActionDialog>
    );
}

export function PaymentDialog({
    invoice,
    ...props
}: DialogProps & { invoice: Invoice }) {
    return (
        <ActionDialog
            {...props}
            title={`Record payment · ${invoice.number}`}
            description={
                <>
                    {formatIDR(invoice.balance)} outstanding of{' '}
                    {formatIDR(invoice.amount_due)}. When the balance reaches
                    zero the invoice is marked paid
                    {invoice.type === 'final'
                        ? ' and the quotation completes.'
                        : '.'}
                </>
            }
            initial={{ amount: invoice.balance, note: '', proof_file_name: '' }}
            route={invoicesRoutes.payments.store(invoice.ulid)}
            submitLabel="Record payment"
        >
            {(form) => (
                <>
                    <FormField label="Amount received">
                        <div className="flex gap-2">
                            <div className="flex-1">
                                <MoneyInput
                                    value={form.data.amount}
                                    onValueChange={(amount) =>
                                        form.setData('amount', amount)
                                    }
                                />
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() =>
                                    form.setData('amount', invoice.balance)
                                }
                            >
                                Pay remaining
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() =>
                                    form.setData(
                                        'amount',
                                        Math.round(
                                            invoice.balance / 2 / 100000,
                                        ) * 100000,
                                    )
                                }
                            >
                                Half
                            </Button>
                        </div>
                    </FormField>
                    <FormField label="Note" htmlFor="note">
                        <Input
                            id="note"
                            value={form.data.note}
                            onChange={(e) =>
                                form.setData('note', e.target.value)
                            }
                            placeholder="e.g. Bank transfer, ref. 4471"
                        />
                    </FormField>
                    <FormField label="Proof of transfer">
                        <FileNameInput
                            value={form.data.proof_file_name}
                            onChange={(name) =>
                                form.setData('proof_file_name', name)
                            }
                            accept=".pdf,.jpg,.jpeg,.png"
                        />
                    </FormField>
                </>
            )}
        </ActionDialog>
    );
}

export function CancelDialog({
    quotationId,
    ...props
}: DialogProps & { quotationId: string }) {
    return (
        <ActionDialog
            {...props}
            title="Cancel quotation"
            description="The quotation and its documents stay on record with your reason. This can't be undone."
            initial={{ reason: '' }}
            route={quotations.cancel(quotationId)}
            submitLabel="Cancel quotation"
        >
            {(form) => (
                <FormField label="Reason" htmlFor="reason">
                    <Textarea
                        id="reason"
                        rows={3}
                        value={form.data.reason}
                        onChange={(e) => form.setData('reason', e.target.value)}
                        placeholder="e.g. Customer chose a cheaper local supplier."
                        autoFocus
                    />
                </FormField>
            )}
        </ActionDialog>
    );
}
