import { Head } from '@inertiajs/react';
import { Printer } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { TotalsList } from '@/components/quotations/totals-card';
import type { Totals } from '@/components/quotations/types';
import { Button } from '@/components/ui/button';
import { formatDate, formatIDR, formatNumber } from '@/lib/format';

type Props = {
    document: 'quotation' | 'delivery-order' | 'invoice' | 'down-payment';
    number: string;
    date: string;
    company: {
        name: string;
        address: string[];
        phone: string;
        email: string;
        tax_id: string;
        bank: string;
    };
    quotation: {
        id: number;
        number: string;
        customer_po_number: string | null;
        delivery_point: string;
        lead_time: string;
        validity: string;
        payment: string;
        notes: string | null;
        sales_rep: string;
        discount_pct: number;
        ppn_rate: number;
    };
    recipient: { name: string; address: string };
    attention: string | null;
    lines: {
        no: number;
        part_number: string;
        description: string;
        uom: string;
        quantity: number;
        unit_price: number;
        line_total: number;
    }[];
    totals: Totals | null;
    amountDue: number | null;
    deliveryNotes: string | null;
};

const titles = {
    quotation: 'Quotation',
    'delivery-order': 'Delivery order',
    invoice: 'Invoice',
    'down-payment': 'Down payment invoice',
};

/**
 * An A4 document laid out for the browser's print dialog (or "Save as PDF").
 * Production renders the same layouts with dompdf and keeps every print.
 */
export default function PrintDocument({
    document,
    number,
    date,
    company,
    quotation,
    recipient,
    attention,
    lines,
    totals,
    amountDue,
    deliveryNotes,
}: Props) {
    const priced = document !== 'delivery-order';

    return (
        <div className="min-h-screen bg-muted/40 py-8 print:bg-white print:py-0">
            <Head title={`${titles[document]} ${number}`} />

            <div className="mx-auto mb-4 flex max-w-[210mm] items-center justify-between px-4 print:hidden">
                <p className="text-sm text-muted-foreground">
                    Print, or choose “Save as PDF” in the print dialog.
                </p>
                <Button onClick={() => window.print()}>
                    <Printer /> Print
                </Button>
            </div>

            <article className="relative mx-auto max-w-[210mm] overflow-hidden bg-white p-[14mm] text-[13px] leading-relaxed text-zinc-900 shadow-sm print:max-w-none print:p-0 print:shadow-none">
                <div className="pointer-events-none absolute inset-0 flex items-center justify-center print:fixed">
                    <span className="-rotate-30 text-[64px] font-bold tracking-widest whitespace-nowrap text-zinc-900/[0.04]">
                        DEMO · FICTIONAL DATA
                    </span>
                </div>

                <header className="flex items-start justify-between gap-6 border-b-2 border-zinc-900 pb-4">
                    <div className="flex gap-3">
                        <span className="flex size-12 items-center justify-center rounded-md bg-[#2F7FE0] text-white">
                            <AppLogoIcon className="size-8" />
                        </span>
                        <div>
                            <p className="text-base font-bold">
                                {company.name}
                            </p>
                            {company.address.map((line) => (
                                <p key={line} className="text-xs text-zinc-600">
                                    {line}
                                </p>
                            ))}
                            <p className="text-xs text-zinc-600">
                                {company.phone} · {company.email}
                            </p>
                        </div>
                    </div>
                    <div className="text-right">
                        <p className="text-xl font-bold tracking-wide uppercase">
                            {titles[document]}
                        </p>
                        <p className="font-mono text-xs">{number}</p>
                        <p className="text-xs text-zinc-600">
                            {formatDate(date)}
                        </p>
                    </div>
                </header>

                <section className="mt-5 grid grid-cols-2 gap-6">
                    <div>
                        <p className="text-[11px] font-semibold tracking-wide text-zinc-500 uppercase">
                            {document === 'delivery-order'
                                ? 'Ship to'
                                : document === 'quotation'
                                  ? 'To'
                                  : 'Bill to'}
                        </p>
                        <p className="font-semibold">{recipient.name}</p>
                        <p className="text-zinc-700">{recipient.address}</p>
                        {attention && (
                            <p className="mt-1 text-zinc-700">
                                Attn. {attention}
                            </p>
                        )}
                    </div>
                    <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-0.5 self-start text-xs">
                        {document !== 'quotation' && (
                            <>
                                <dt className="text-zinc-500">Quotation</dt>
                                <dd>{quotation.number}</dd>
                            </>
                        )}
                        {quotation.customer_po_number &&
                            document !== 'quotation' && (
                                <>
                                    <dt className="text-zinc-500">Your PO</dt>
                                    <dd>{quotation.customer_po_number}</dd>
                                </>
                            )}
                        <dt className="text-zinc-500">Delivery point</dt>
                        <dd>{quotation.delivery_point}</dd>
                        {document === 'quotation' && (
                            <>
                                <dt className="text-zinc-500">Lead time</dt>
                                <dd>{quotation.lead_time}</dd>
                                <dt className="text-zinc-500">Valid for</dt>
                                <dd>{quotation.validity}</dd>
                            </>
                        )}
                        {priced && (
                            <>
                                <dt className="text-zinc-500">Payment</dt>
                                <dd>{quotation.payment}</dd>
                            </>
                        )}
                        <dt className="text-zinc-500">Sales</dt>
                        <dd>{quotation.sales_rep}</dd>
                    </dl>
                </section>

                {document === 'down-payment' ? (
                    <section className="mt-6 rounded border border-zinc-300 p-4">
                        <div className="flex justify-between">
                            <span>
                                Down payment for quotation {quotation.number}
                            </span>
                            <span className="font-semibold tabular-nums">
                                {formatIDR(amountDue ?? 0)}
                            </span>
                        </div>
                    </section>
                ) : (
                    <table className="mt-6 w-full border-collapse text-xs">
                        <thead>
                            <tr className="border-y border-zinc-900 text-left">
                                <th className="py-1.5 pr-2">No</th>
                                <th className="py-1.5 pr-2">Part number</th>
                                <th className="py-1.5 pr-2">Description</th>
                                <th className="py-1.5 pr-2 text-right">Qty</th>
                                {priced && (
                                    <th className="py-1.5 pr-2 text-right">
                                        Unit price
                                    </th>
                                )}
                                {priced && (
                                    <th className="py-1.5 text-right">Total</th>
                                )}
                            </tr>
                        </thead>
                        <tbody>
                            {lines.map((line) => (
                                <tr
                                    key={line.no}
                                    className="border-b border-zinc-200 align-top"
                                >
                                    <td className="py-1.5 pr-2">{line.no}</td>
                                    <td className="py-1.5 pr-2 font-mono whitespace-nowrap">
                                        {line.part_number}
                                    </td>
                                    <td className="py-1.5 pr-2">
                                        {line.description}
                                    </td>
                                    <td className="py-1.5 pr-2 text-right whitespace-nowrap tabular-nums">
                                        {formatNumber(line.quantity)} {line.uom}
                                    </td>
                                    {priced && (
                                        <td className="py-1.5 pr-2 text-right whitespace-nowrap tabular-nums">
                                            {formatIDR(line.unit_price)}
                                        </td>
                                    )}
                                    {priced && (
                                        <td className="py-1.5 text-right whitespace-nowrap tabular-nums">
                                            {formatIDR(line.line_total)}
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}

                {totals && (
                    <div className="mt-4 ml-auto w-72">
                        <TotalsList
                            totals={totals}
                            discountPct={quotation.discount_pct}
                            ppnRate={quotation.ppn_rate}
                            showDownPayment={document === 'invoice'}
                            className="text-xs [&_dt]:text-zinc-600"
                        />
                        {document === 'invoice' && amountDue !== null && (
                            <div className="mt-2 flex justify-between border-t-2 border-zinc-900 pt-1.5 text-sm font-bold">
                                <span>Amount due</span>
                                <span className="tabular-nums">
                                    {formatIDR(amountDue)}
                                </span>
                            </div>
                        )}
                    </div>
                )}

                <footer className="mt-10 grid grid-cols-2 gap-8 text-xs">
                    <div className="space-y-1 text-zinc-600">
                        {document === 'quotation' && quotation.notes && (
                            <p>Note: {quotation.notes}</p>
                        )}
                        {document === 'delivery-order' && deliveryNotes && (
                            <p>Note: {deliveryNotes}</p>
                        )}
                        {(document === 'invoice' ||
                            document === 'down-payment') && (
                            <>
                                <p>Please transfer to: {company.bank}</p>
                                <p>Tax ID (NPWP): {company.tax_id}</p>
                            </>
                        )}
                    </div>
                    <div className="grid grid-cols-2 gap-6 text-center">
                        {document === 'delivery-order' ? (
                            <>
                                <Signature label="Received by" />
                                <Signature label="Sent by" />
                            </>
                        ) : (
                            <div className="col-span-2 ml-auto w-1/2">
                                <Signature label={company.name} />
                            </div>
                        )}
                    </div>
                </footer>
            </article>
        </div>
    );
}

function Signature({ label }: { label: string }) {
    return (
        <div>
            <div className="h-16 border-b border-zinc-400" />
            <p className="mt-1 text-zinc-600">{label}</p>
        </div>
    );
}
