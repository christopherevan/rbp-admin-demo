import type { ComponentProps } from 'react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

const grouping = new Intl.NumberFormat('id-ID');

/**
 * A rupiah amount typed with thousands separators (1.250.000) but held as a
 * plain integer.
 */
export function MoneyInput({
    value,
    onValueChange,
    className,
    ...props
}: Omit<ComponentProps<'input'>, 'value' | 'onChange'> & {
    value: number;
    onValueChange: (value: number) => void;
}) {
    return (
        <div className="relative">
            <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground">
                Rp
            </span>
            <Input
                inputMode="numeric"
                className={cn('pl-9 text-right tabular-nums', className)}
                value={value ? grouping.format(value) : ''}
                placeholder="0"
                onChange={(event) => {
                    const digits = event.target.value
                        .replace(/\D/g, '')
                        .slice(0, 13);
                    onValueChange(digits ? Number(digits) : 0);
                }}
                {...props}
            />
        </div>
    );
}
