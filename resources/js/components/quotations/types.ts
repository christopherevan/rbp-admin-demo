import type { Activity, QuotationStatus } from '@/types';

export type Totals = {
    subtotal: number;
    discount: number;
    taxBase: number;
    ppn: number;
    grandTotal: number;
    downPayment: number;
    balanceDue: number;
};

export type Line = {
    id: number;
    position: number;
    part: {
        id: number;
        part_number: string;
        description: string;
        uom: string;
        list_price: number;
        stocked: boolean;
        stock_qty: number;
        discontinued: boolean;
    };
    quantity: number;
    unit_price: number;
    line_total: number;
};

export type Invoice = {
    id: number;
    ulid: string;
    type: 'down_payment' | 'final';
    number: string;
    bill_to_name: string;
    amount_due: number;
    amount_paid: number;
    balance: number;
    status: 'pending' | 'paid';
    paid_at: string | null;
    created_at: string;
    payments: {
        id: number;
        amount: number;
        note: string | null;
        proof_file_name: string | null;
        received_at: string;
        received_by: string;
    }[];
};

export type NextAction = {
    key:
        | 'edit_lines'
        | 'record_po'
        | 'create_so'
        | 'delete_so'
        | 'create_do'
        | 'complete_delivery'
        | 'create_invoice'
        | 'create_dp_invoice'
        | 'record_payment'
        | 'cancel';
    label: string;
    primary: boolean;
    allowed: boolean;
    reason: string | null;
    invoice_id?: number;
};

export type QuotationPageProps = {
    quotation: {
        id: number;
        ulid: string;
        number: string;
        status: QuotationStatus;
        customer: {
            id: number;
            ulid: string;
            code: string;
            name: string;
            address: string;
            archived: boolean;
        };
        contact: {
            name: string;
            phone: string | null;
            email: string | null;
        } | null;
        sales_rep: { id: number; name: string };
        delivery_point: string;
        discount_pct: number;
        ppn_rate: number;
        lead_time: string;
        validity: string;
        payment: string;
        dp_amount: number;
        notes: string | null;
        customer_po_number: string | null;
        customer_po_file: string | null;
        customer_po_at: string | null;
        cancel_reason: string | null;
        cancelled_at: string | null;
        completed_at: string | null;
        created_at: string;
        inquiry: { id: number; ulid: string; number: string } | null;
    };
    lines: Line[];
    totals: Totals;
    salesOrder: {
        number: string;
        created_at: string;
        lines: {
            id: number;
            quotation_line_id: number;
            part_number: string;
            description: string;
            uom: string;
            quantity: number;
            unit_price: number;
            line_total: number;
        }[];
        totals: Totals | null;
    } | null;
    deliveryOrder: {
        id: number;
        number: string;
        status: 'on_delivery' | 'completed';
        ship_to_name: string;
        ship_to_address: string;
        notes: string | null;
        delivered_at: string | null;
        created_at: string;
    } | null;
    invoices: Invoice[];
    activities: Activity[];
    progress: {
        step: number;
        cancelled: boolean;
        checks: Record<
            'po' | 'so' | 'do' | 'delivered' | 'invoiced' | 'paid',
            boolean
        >;
    };
    nextActions: NextAction[];
    can: { update: boolean; fulfil: boolean; bill: boolean };
    defaults: { name: string; address: string };
    maxLines: number;
};

/** The same arithmetic as App\Support\QuotationTotals, for the live preview. */
export function calculateTotals(
    subtotal: number,
    discountPct: number,
    ppnRate: number,
    downPayment = 0,
): Totals {
    const discount = Math.round((subtotal * discountPct) / 100);
    const taxBase = subtotal - discount;
    const ppn = Math.round((taxBase * ppnRate) / 100);
    const grandTotal = taxBase + ppn;

    return {
        subtotal,
        discount,
        taxBase,
        ppn,
        grandTotal,
        downPayment,
        balanceDue: Math.max(0, grandTotal - downPayment),
    };
}
