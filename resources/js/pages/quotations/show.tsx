import { Head, Link, router } from '@inertiajs/react';
import {
    ExternalLink,
    FileText,
    Inbox,
    PackageCheck,
    Pencil,
    Printer,
    Receipt,
    Truck,
} from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { LogTimeline } from '@/components/log-timeline';
import { PageHeader } from '@/components/page-header';
import {
    CancelDialog,
    DeliveryOrderDialog,
    InvoiceDialog,
    PaymentDialog,
    PoDialog,
    SalesOrderDialog,
} from '@/components/quotations/action-dialogs';
import { LineItemsEditor } from '@/components/quotations/line-items-editor';
import { NextStepCard } from '@/components/quotations/next-step-card';
import { QuotationStepper } from '@/components/quotations/stepper';
import { TotalsList } from '@/components/quotations/totals-card';
import type {
    NextAction,
    QuotationPageProps,
} from '@/components/quotations/types';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    formatDate,
    formatDateTime,
    formatIDR,
    formatNumber,
} from '@/lib/format';
import customers from '@/routes/customers';
import inquiries from '@/routes/inquiries';
import quotations from '@/routes/quotations';

type DialogState = { key: NextAction['key']; invoiceId?: number } | null;

export default function QuotationShow(props: QuotationPageProps) {
    const {
        quotation,
        lines,
        totals,
        salesOrder,
        deliveryOrder,
        invoices,
        activities,
        progress,
        nextActions,
        can,
        defaults,
        maxLines,
    } = props;
    const [tab, setTab] = useState('items');
    const [dialog, setDialog] = useState<DialogState>(null);

    const editable =
        can.update &&
        (quotation.status === 'not_submitted' ||
            quotation.status === 'waiting_customer');
    const close = () => setDialog(null);
    const dpInvoice = invoices.find(
        (invoice) => invoice.type === 'down_payment',
    );
    const finalInvoice = invoices.find((invoice) => invoice.type === 'final');
    const paymentInvoice = invoices.find(
        (invoice) => invoice.id === dialog?.invoiceId,
    );

    const onAction = (action: NextAction) => {
        switch (action.key) {
            case 'edit_lines':
                setTab('items');
                document
                    .getElementById('workspace')
                    ?.scrollIntoView({ behavior: 'smooth' });
                break;
            case 'delete_so':
                if (
                    window.confirm(
                        'Delete the sales order? You can create it again.',
                    )
                ) {
                    router.delete(
                        quotations.salesOrder.destroy.url(quotation.ulid),
                        { preserveScroll: true },
                    );
                }
                break;
            case 'complete_delivery':
                router.post(
                    quotations.deliveryOrder.complete.url(quotation.ulid),
                    {},
                    { preserveScroll: true },
                );
                break;
            default:
                setDialog({ key: action.key, invoiceId: action.invoice_id });
        }
    };

    const documents = [
        lines.length > 0 && { label: 'Quotation', document: 'quotation' },
        deliveryOrder && {
            label: 'Delivery order',
            document: 'delivery-order',
        },
        dpInvoice && {
            label: 'Down payment invoice',
            document: 'down-payment',
        },
        finalInvoice && { label: 'Invoice', document: 'invoice' },
    ].filter(Boolean) as { label: string; document: string }[];

    return (
        <>
            <Head title={quotation.number} />

            <PageHeader
                title={
                    <>
                        {quotation.number}
                        <StatusBadge
                            kind="quotation"
                            status={quotation.status}
                        />
                    </>
                }
                description={
                    <>
                        <Link
                            href={customers.edit(quotation.customer.ulid)}
                            className="font-medium text-foreground hover:text-primary"
                        >
                            {quotation.customer.name}
                        </Link>
                        {quotation.customer.archived && ' (archived)'} ·{' '}
                        {quotation.sales_rep.name} · created{' '}
                        {formatDate(quotation.created_at)}
                    </>
                }
                note="quotation"
                actions={
                    <>
                        {editable && (
                            <Button asChild variant="outline" size="sm">
                                <Link href={quotations.edit(quotation.ulid)}>
                                    <Pencil /> Edit terms
                                </Link>
                            </Button>
                        )}
                        {documents.length > 0 && (
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="outline" size="sm">
                                        <Printer /> Print
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    {documents.map((doc) => (
                                        <DropdownMenuItem
                                            key={doc.document}
                                            asChild
                                        >
                                            <a
                                                href={quotations.print.url({
                                                    quotation: quotation.ulid,
                                                    document: doc.document,
                                                })}
                                                target="_blank"
                                                rel="noreferrer"
                                            >
                                                <FileText /> {doc.label}
                                            </a>
                                        </DropdownMenuItem>
                                    ))}
                                </DropdownMenuContent>
                            </DropdownMenu>
                        )}
                    </>
                }
            >
                {quotation.inquiry && (
                    <Link
                        href={inquiries.show(quotation.inquiry.ulid)}
                        className="inline-flex items-center gap-1 text-xs text-primary hover:underline"
                    >
                        <Inbox className="size-3.5" /> From web inquiry{' '}
                        {quotation.inquiry.number}
                    </Link>
                )}
            </PageHeader>

            <QuotationStepper progress={progress} />

            <div id="workspace" className="grid gap-4 lg:grid-cols-3">
                <div className="min-w-0 lg:col-span-2">
                    <Tabs value={tab} onValueChange={setTab}>
                        <TabsList className="w-full justify-start overflow-x-auto overflow-y-hidden">
                            <TabsTrigger value="items">
                                Items ({lines.length})
                            </TabsTrigger>
                            <TabsTrigger value="sales-order">
                                Sales order
                            </TabsTrigger>
                            <TabsTrigger value="delivery">Delivery</TabsTrigger>
                            <TabsTrigger value="invoices">
                                Invoices ({invoices.length})
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="items">
                            <Card>
                                <CardContent>
                                    {editable ? (
                                        <LineItemsEditor
                                            quotationId={quotation.ulid}
                                            status={quotation.status}
                                            lines={lines}
                                            discountPct={quotation.discount_pct}
                                            ppnRate={quotation.ppn_rate}
                                            dpAmount={quotation.dp_amount}
                                            maxLines={maxLines}
                                        />
                                    ) : (
                                        <ReadOnlyLines
                                            lines={lines}
                                            locked={
                                                quotation.customer_po_at !==
                                                null
                                            }
                                        />
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="sales-order">
                            <Card>
                                <CardContent>
                                    {salesOrder ? (
                                        <div className="space-y-4">
                                            <div className="flex flex-wrap items-baseline justify-between gap-2">
                                                <p className="font-medium">
                                                    {salesOrder.number}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    Created{' '}
                                                    {formatDateTime(
                                                        salesOrder.created_at,
                                                    )}
                                                </p>
                                            </div>
                                            <div className="overflow-x-auto rounded-lg border">
                                                <table className="w-full min-w-[560px] text-sm">
                                                    <thead className="bg-muted/40 text-left text-xs text-muted-foreground uppercase">
                                                        <tr>
                                                            <th className="px-3 py-2">
                                                                Part
                                                            </th>
                                                            <th className="px-3 py-2 text-right">
                                                                Ordered
                                                            </th>
                                                            <th className="px-3 py-2 text-right">
                                                                Unit price
                                                            </th>
                                                            <th className="px-3 py-2 text-right">
                                                                Total
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="divide-y">
                                                        {salesOrder.lines.map(
                                                            (line) => {
                                                                const quoted =
                                                                    lines.find(
                                                                        (l) =>
                                                                            l.id ===
                                                                            line.quotation_line_id,
                                                                    )
                                                                        ?.quantity ??
                                                                    line.quantity;

                                                                return (
                                                                    <tr
                                                                        key={
                                                                            line.id
                                                                        }
                                                                    >
                                                                        <td className="px-3 py-2">
                                                                            <p className="font-medium">
                                                                                {
                                                                                    line.part_number
                                                                                }
                                                                            </p>
                                                                            <p className="line-clamp-1 text-xs text-muted-foreground">
                                                                                {
                                                                                    line.description
                                                                                }
                                                                            </p>
                                                                        </td>
                                                                        <td className="px-3 py-2 text-right whitespace-nowrap tabular-nums">
                                                                            {formatNumber(
                                                                                line.quantity,
                                                                            )}{' '}
                                                                            {
                                                                                line.uom
                                                                            }
                                                                            {line.quantity <
                                                                                quoted && (
                                                                                <span className="block text-xs text-amber-600">
                                                                                    of{' '}
                                                                                    {
                                                                                        quoted
                                                                                    }{' '}
                                                                                    quoted
                                                                                </span>
                                                                            )}
                                                                        </td>
                                                                        <td className="px-3 py-2 text-right whitespace-nowrap tabular-nums">
                                                                            {formatIDR(
                                                                                line.unit_price,
                                                                            )}
                                                                        </td>
                                                                        <td className="px-3 py-2 text-right font-medium whitespace-nowrap tabular-nums">
                                                                            {formatIDR(
                                                                                line.line_total,
                                                                            )}
                                                                        </td>
                                                                    </tr>
                                                                );
                                                            },
                                                        )}
                                                    </tbody>
                                                </table>
                                            </div>
                                            {salesOrder.lines.length <
                                                lines.length && (
                                                <p className="text-sm text-amber-600 dark:text-amber-400">
                                                    Partial order:{' '}
                                                    {lines.length -
                                                        salesOrder.lines
                                                            .length}{' '}
                                                    quoted{' '}
                                                    {lines.length -
                                                        salesOrder.lines
                                                            .length ===
                                                    1
                                                        ? 'line was'
                                                        : 'lines were'}{' '}
                                                    not ordered.
                                                </p>
                                            )}
                                            {salesOrder.totals && (
                                                <TotalsList
                                                    totals={salesOrder.totals}
                                                    discountPct={
                                                        quotation.discount_pct
                                                    }
                                                    ppnRate={quotation.ppn_rate}
                                                    className="ml-auto max-w-xs"
                                                />
                                            )}
                                        </div>
                                    ) : (
                                        <Empty
                                            icon={FileText}
                                            title="No sales order yet"
                                            body="Once the customer's PO is in, an admin turns the quotation into a sales order, in full or in part."
                                        />
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="delivery">
                            <Card>
                                <CardContent>
                                    {deliveryOrder ? (
                                        <div className="space-y-4">
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <p className="flex items-center gap-2 font-medium">
                                                    {deliveryOrder.number}
                                                    <StatusBadge
                                                        kind="document"
                                                        status={
                                                            deliveryOrder.status
                                                        }
                                                    />
                                                </p>
                                                <Button
                                                    asChild
                                                    variant="outline"
                                                    size="sm"
                                                >
                                                    <a
                                                        href={quotations.print.url(
                                                            {
                                                                quotation:
                                                                    quotation.ulid,
                                                                document:
                                                                    'delivery-order',
                                                            },
                                                        )}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                    >
                                                        <Printer /> Delivery
                                                        order
                                                    </a>
                                                </Button>
                                            </div>
                                            <dl className="grid gap-3 text-sm sm:grid-cols-2">
                                                <Detail label="Ship to">
                                                    {deliveryOrder.ship_to_name}
                                                    <span className="block text-muted-foreground">
                                                        {
                                                            deliveryOrder.ship_to_address
                                                        }
                                                    </span>
                                                </Detail>
                                                <Detail label="Dispatched">
                                                    {formatDateTime(
                                                        deliveryOrder.created_at,
                                                    )}
                                                </Detail>
                                                <Detail label="Received">
                                                    {deliveryOrder.delivered_at
                                                        ? formatDateTime(
                                                              deliveryOrder.delivered_at,
                                                          )
                                                        : 'Not yet'}
                                                </Detail>
                                                {deliveryOrder.notes && (
                                                    <Detail label="Notes">
                                                        {deliveryOrder.notes}
                                                    </Detail>
                                                )}
                                            </dl>
                                            {deliveryOrder.status ===
                                                'on_delivery' &&
                                                can.fulfil && (
                                                    <ConfirmDialog
                                                        trigger={
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                            >
                                                                <PackageCheck />{' '}
                                                                Mark as
                                                                delivered
                                                            </Button>
                                                        }
                                                        title="Mark as delivered?"
                                                        description="Records that the customer received the goods."
                                                        confirmLabel="Mark delivered"
                                                        onConfirm={() =>
                                                            router.post(
                                                                quotations.deliveryOrder.complete.url(
                                                                    quotation.ulid,
                                                                ),
                                                                {},
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    />
                                                )}
                                        </div>
                                    ) : (
                                        <Empty
                                            icon={Truck}
                                            title="Not shipped yet"
                                            body="Creating the delivery order prints the surat jalan and deducts stock."
                                        />
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="invoices">
                            <div className="space-y-4">
                                {invoices.length === 0 && (
                                    <Card>
                                        <CardContent>
                                            <Empty
                                                icon={Receipt}
                                                title="No invoices yet"
                                                body="A down payment invoice can be issued once the order is confirmed; the final invoice follows the delivery order."
                                            />
                                        </CardContent>
                                    </Card>
                                )}
                                {invoices.map((invoice) => (
                                    <Card key={invoice.id} className="gap-4">
                                        <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-2">
                                            <div>
                                                <CardTitle className="flex items-center gap-2 text-base">
                                                    {invoice.number}
                                                    <StatusBadge
                                                        kind="document"
                                                        status={invoice.status}
                                                    />
                                                </CardTitle>
                                                <p className="text-sm text-muted-foreground">
                                                    {invoice.type === 'final'
                                                        ? 'Invoice'
                                                        : 'Down payment invoice'}{' '}
                                                    · {invoice.bill_to_name} ·
                                                    issued{' '}
                                                    {formatDate(
                                                        invoice.created_at,
                                                    )}
                                                </p>
                                            </div>
                                            <div className="flex gap-2">
                                                <Button
                                                    asChild
                                                    variant="outline"
                                                    size="sm"
                                                >
                                                    <a
                                                        href={quotations.print.url(
                                                            {
                                                                quotation:
                                                                    quotation.ulid,
                                                                document:
                                                                    invoice.type ===
                                                                    'final'
                                                                        ? 'invoice'
                                                                        : 'down-payment',
                                                            },
                                                        )}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                    >
                                                        <Printer /> Print
                                                    </a>
                                                </Button>
                                                {invoice.status === 'pending' &&
                                                    can.bill &&
                                                    quotation.status !==
                                                        'cancelled' && (
                                                        <Button
                                                            size="sm"
                                                            onClick={() =>
                                                                setDialog({
                                                                    key: 'record_payment',
                                                                    invoiceId:
                                                                        invoice.id,
                                                                })
                                                            }
                                                        >
                                                            Record payment
                                                        </Button>
                                                    )}
                                            </div>
                                        </CardHeader>
                                        <CardContent className="space-y-3">
                                            <div className="grid grid-cols-3 gap-2 rounded-lg bg-muted/40 p-3 text-sm">
                                                <Amount
                                                    label="Amount due"
                                                    value={invoice.amount_due}
                                                />
                                                <Amount
                                                    label="Paid"
                                                    value={invoice.amount_paid}
                                                />
                                                <Amount
                                                    label="Balance"
                                                    value={invoice.balance}
                                                    strong
                                                />
                                            </div>
                                            {invoice.amount_due > 0 && (
                                                <div className="h-1.5 overflow-hidden rounded-full bg-muted">
                                                    <div
                                                        className="h-full bg-emerald-500"
                                                        style={{
                                                            width: `${Math.min(100, (invoice.amount_paid / invoice.amount_due) * 100)}%`,
                                                        }}
                                                    />
                                                </div>
                                            )}
                                            {invoice.payments.length > 0 && (
                                                <ul className="divide-y text-sm">
                                                    {invoice.payments.map(
                                                        (payment) => (
                                                            <li
                                                                key={payment.id}
                                                                className="flex flex-wrap justify-between gap-2 py-2"
                                                            >
                                                                <span>
                                                                    {formatDate(
                                                                        payment.received_at,
                                                                    )}{' '}
                                                                    ·{' '}
                                                                    {payment.note ??
                                                                        'Payment'}
                                                                    {payment.proof_file_name && (
                                                                        <span className="ml-1 text-xs text-muted-foreground">
                                                                            (
                                                                            {
                                                                                payment.proof_file_name
                                                                            }
                                                                            )
                                                                        </span>
                                                                    )}
                                                                    <span className="block text-xs text-muted-foreground">
                                                                        Recorded
                                                                        by{' '}
                                                                        {
                                                                            payment.received_by
                                                                        }
                                                                    </span>
                                                                </span>
                                                                <span className="font-medium tabular-nums">
                                                                    {formatIDR(
                                                                        payment.amount,
                                                                    )}
                                                                </span>
                                                            </li>
                                                        ),
                                                    )}
                                                </ul>
                                            )}
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        </TabsContent>
                    </Tabs>
                </div>

                <div className="space-y-4">
                    <NextStepCard
                        actions={nextActions}
                        quotation={quotation}
                        onAction={onAction}
                    />

                    <Card className="gap-3">
                        <CardHeader>
                            <CardTitle className="text-base">Totals</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <TotalsList
                                totals={totals}
                                discountPct={quotation.discount_pct}
                                ppnRate={quotation.ppn_rate}
                            />
                        </CardContent>
                    </Card>

                    <Card className="gap-3">
                        <CardHeader>
                            <CardTitle className="text-base">Terms</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-3 text-sm">
                                {quotation.contact && (
                                    <Detail label="Attention">
                                        {quotation.contact.name}
                                        <span className="block text-muted-foreground">
                                            {[
                                                quotation.contact.phone,
                                                quotation.contact.email,
                                            ]
                                                .filter(Boolean)
                                                .join(' · ')}
                                        </span>
                                    </Detail>
                                )}
                                <Detail label="Delivery point">
                                    {quotation.delivery_point}
                                </Detail>
                                <Detail label="Lead time · validity">
                                    {quotation.lead_time} · valid{' '}
                                    {quotation.validity}
                                </Detail>
                                <Detail label="Payment">
                                    {quotation.payment}
                                </Detail>
                                {quotation.customer_po_number && (
                                    <Detail label="Customer PO">
                                        {quotation.customer_po_number}
                                        {quotation.customer_po_file && (
                                            <span className="flex items-center gap-1 text-xs text-muted-foreground">
                                                <ExternalLink className="size-3" />{' '}
                                                {quotation.customer_po_file}
                                            </span>
                                        )}
                                    </Detail>
                                )}
                                {quotation.notes && (
                                    <Detail label="Notes">
                                        {quotation.notes}
                                    </Detail>
                                )}
                            </dl>
                        </CardContent>
                    </Card>

                    <Card className="gap-3">
                        <CardHeader>
                            <CardTitle className="text-base">
                                Activity
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <LogTimeline activities={activities} />
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* Mounted only while open, so each starts from the current lines and balances. */}
            {dialog?.key === 'record_po' && (
                <PoDialog open onClose={close} quotationId={quotation.ulid} />
            )}
            {dialog?.key === 'create_so' && (
                <SalesOrderDialog
                    open
                    onClose={close}
                    quotationId={quotation.ulid}
                    lines={lines}
                />
            )}
            {dialog?.key === 'create_do' && (
                <DeliveryOrderDialog
                    open
                    onClose={close}
                    quotationId={quotation.ulid}
                    defaults={defaults}
                />
            )}
            {dialog?.key === 'create_invoice' && (
                <InvoiceDialog
                    open
                    onClose={close}
                    quotationId={quotation.ulid}
                    type="final"
                    amount={salesOrder?.totals?.balanceDue ?? 0}
                    defaults={defaults}
                />
            )}
            {dialog?.key === 'create_dp_invoice' && (
                <InvoiceDialog
                    open
                    onClose={close}
                    quotationId={quotation.ulid}
                    type="down_payment"
                    amount={quotation.dp_amount}
                    defaults={defaults}
                />
            )}
            {dialog?.key === 'record_payment' && paymentInvoice && (
                <PaymentDialog open onClose={close} invoice={paymentInvoice} />
            )}
            {dialog?.key === 'cancel' && (
                <CancelDialog
                    open
                    onClose={close}
                    quotationId={quotation.ulid}
                />
            )}
        </>
    );
}

function ReadOnlyLines({
    lines,
    locked,
}: {
    lines: QuotationPageProps['lines'];
    locked: boolean;
}) {
    if (lines.length === 0) {
        return (
            <Empty
                icon={FileText}
                title="No line items"
                body="Only the assigned sales rep or an admin can add them."
            />
        );
    }

    return (
        <div className="space-y-2">
            {locked && (
                <p className="text-xs text-muted-foreground">
                    Line items are locked: the customer's purchase order has
                    been received.
                </p>
            )}
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full min-w-[560px] text-sm">
                    <thead className="bg-muted/40 text-left text-xs text-muted-foreground uppercase">
                        <tr>
                            <th className="w-8 px-3 py-2">#</th>
                            <th className="px-3 py-2">Part</th>
                            <th className="px-3 py-2 text-right">Qty</th>
                            <th className="px-3 py-2 text-right">Unit price</th>
                            <th className="px-3 py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {lines.map((line) => (
                            <tr key={line.id}>
                                <td className="px-3 py-2 text-muted-foreground tabular-nums">
                                    {line.position}
                                </td>
                                <td className="px-3 py-2">
                                    <p className="font-medium whitespace-nowrap">
                                        {line.part.part_number}
                                        {line.part.discontinued && (
                                            <span className="ml-2 text-xs font-normal text-muted-foreground">
                                                (discontinued)
                                            </span>
                                        )}
                                    </p>
                                    <p className="line-clamp-1 text-xs text-muted-foreground">
                                        {line.part.description}
                                    </p>
                                </td>
                                <td className="px-3 py-2 text-right whitespace-nowrap tabular-nums">
                                    {formatNumber(line.quantity)}{' '}
                                    {line.part.uom}
                                </td>
                                <td className="px-3 py-2 text-right whitespace-nowrap tabular-nums">
                                    {formatIDR(line.unit_price)}
                                </td>
                                <td className="px-3 py-2 text-right font-medium whitespace-nowrap tabular-nums">
                                    {formatIDR(line.line_total)}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function Detail({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div>
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd>{children}</dd>
        </div>
    );
}

function Amount({
    label,
    value,
    strong,
}: {
    label: string;
    value: number;
    strong?: boolean;
}) {
    return (
        <div>
            <p className="text-xs text-muted-foreground">{label}</p>
            <p
                className={
                    strong ? 'font-semibold tabular-nums' : 'tabular-nums'
                }
            >
                {formatIDR(value)}
            </p>
        </div>
    );
}

function Empty({
    icon: Icon,
    title,
    body,
}: {
    icon: typeof FileText;
    title: string;
    body: string;
}) {
    return (
        <div className="flex flex-col items-center gap-2 py-10 text-center">
            <span className="flex size-10 items-center justify-center rounded-full bg-muted text-muted-foreground">
                <Icon className="size-5" />
            </span>
            <p className="font-medium">{title}</p>
            <p className="max-w-sm text-sm text-muted-foreground">{body}</p>
        </div>
    );
}

QuotationShow.layout = {
    breadcrumbs: [{ title: 'Quotations', href: quotations.index() }],
};
