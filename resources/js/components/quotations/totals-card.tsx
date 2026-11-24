import { formatIDR } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Totals } from './types';

export function TotalsList({
    totals,
    discountPct,
    ppnRate,
    showDownPayment = true,
    className,
}: {
    totals: Totals;
    discountPct: number;
    ppnRate: number;
    showDownPayment?: boolean;
    className?: string;
}) {
    return (
        <dl className={cn('space-y-1.5 text-sm', className)}>
            <Row label="Subtotal" value={totals.subtotal} />
            {totals.discount > 0 && (
                <Row
                    label={`Discount ${discountPct}%`}
                    value={-totals.discount}
                />
            )}
            <Row
                label={ppnRate ? `PPN ${ppnRate}%` : 'No PPN'}
                value={totals.ppn}
            />
            <div className="flex justify-between border-t pt-1.5 font-semibold">
                <dt>Grand total</dt>
                <dd className="tabular-nums">{formatIDR(totals.grandTotal)}</dd>
            </div>
            {showDownPayment && totals.downPayment > 0 && (
                <>
                    <Row
                        label="Down payment"
                        value={-totals.downPayment}
                        muted
                    />
                    <div className="flex justify-between font-medium">
                        <dt>Balance after DP</dt>
                        <dd className="tabular-nums">
                            {formatIDR(totals.balanceDue)}
                        </dd>
                    </div>
                </>
            )}
        </dl>
    );
}

function Row({
    label,
    value,
    muted,
}: {
    label: string;
    value: number;
    muted?: boolean;
}) {
    return (
        <div
            className={cn(
                'flex justify-between',
                muted && 'text-muted-foreground',
            )}
        >
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="tabular-nums">
                {value < 0 ? `− ${formatIDR(-value)}` : formatIDR(value)}
            </dd>
        </div>
    );
}
