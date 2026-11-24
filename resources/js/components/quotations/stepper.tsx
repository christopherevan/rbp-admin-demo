import { Check, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { QuotationPageProps } from './types';

const steps = [
    { title: 'Draft', hint: 'Terms and line items' },
    { title: 'Sent', hint: 'Waiting for the customer' },
    { title: 'In progress', hint: 'PO, sales order, delivery' },
    { title: 'Invoiced', hint: 'Waiting for payment' },
    { title: 'Completed', hint: 'Paid in full' },
];

const checkLabels: [keyof QuotationPageProps['progress']['checks'], string][] =
    [
        ['po', 'Customer PO'],
        ['so', 'Sales order'],
        ['do', 'Delivery order'],
        ['delivered', 'Delivered'],
    ];

/** The original's five-step progress bar, with the fulfilment sub-steps of step 3. */
export function QuotationStepper({
    progress,
}: {
    progress: QuotationPageProps['progress'];
}) {
    return (
        <div className="rounded-xl border bg-card p-4">
            <ol className="grid grid-cols-5 gap-2">
                {steps.map((step, index) => {
                    const number = index + 1;
                    const done =
                        number < progress.step ||
                        (number === 5 &&
                            progress.step === 5 &&
                            !progress.cancelled);
                    const current = number === progress.step && !done;
                    const stopped = current && progress.cancelled;

                    return (
                        <li
                            key={step.title}
                            className="relative flex flex-col items-center text-center"
                        >
                            {index > 0 && (
                                <span
                                    className={cn(
                                        'absolute top-4 right-1/2 -z-0 h-0.5 w-full -translate-y-1/2',
                                        number <= progress.step
                                            ? 'bg-primary'
                                            : 'bg-border',
                                    )}
                                />
                            )}
                            <span
                                className={cn(
                                    'relative z-10 flex size-8 items-center justify-center rounded-full border-2 text-sm font-semibold',
                                    done &&
                                        'border-primary bg-primary text-primary-foreground',
                                    current &&
                                        !stopped &&
                                        'border-primary bg-background text-primary ring-4 ring-primary/15',
                                    stopped &&
                                        'border-zinc-900 bg-zinc-900 text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900',
                                    !done &&
                                        !current &&
                                        'border-border bg-background text-muted-foreground',
                                )}
                            >
                                {done ? (
                                    <Check className="size-4" />
                                ) : stopped ? (
                                    <X className="size-4" />
                                ) : (
                                    number
                                )}
                            </span>
                            <span
                                className={cn(
                                    'mt-2 text-xs font-medium sm:text-sm',
                                    !done &&
                                        !current &&
                                        'text-muted-foreground',
                                )}
                            >
                                {step.title}
                            </span>
                            <span className="hidden text-xs text-muted-foreground md:block">
                                {stopped ? 'Cancelled here' : step.hint}
                            </span>
                        </li>
                    );
                })}
            </ol>
            {progress.step >= 3 && (
                <div className="mt-4 flex flex-wrap justify-center gap-2 border-t pt-3">
                    {checkLabels.map(([key, label]) => (
                        <span
                            key={key}
                            className={cn(
                                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs',
                                progress.checks[key]
                                    ? 'bg-primary/10 text-primary'
                                    : 'bg-muted text-muted-foreground',
                            )}
                        >
                            {progress.checks[key] ? (
                                <Check className="size-3" />
                            ) : (
                                <span className="size-1.5 rounded-full bg-current opacity-50" />
                            )}
                            {label}
                        </span>
                    ))}
                </div>
            )}
        </div>
    );
}
