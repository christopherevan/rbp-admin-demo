import { router } from '@inertiajs/react';
import {
    Ban,
    Banknote,
    CircleCheck,
    ClipboardList,
    FileCheck2,
    Lock,
    PackageCheck,
    Pencil,
    Receipt,
    Trash2,
    Truck,
    UserCog,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import demo from '@/routes/demo';
import type { NextAction, QuotationPageProps } from './types';

const ACTION_ICONS: Record<NextAction['key'], LucideIcon> = {
    edit_lines: Pencil,
    record_po: FileCheck2,
    create_so: ClipboardList,
    delete_so: Trash2,
    create_do: Truck,
    complete_delivery: PackageCheck,
    create_invoice: Receipt,
    create_dp_invoice: Receipt,
    record_payment: Banknote,
    cancel: Ban,
};

/**
 * What can happen next, exactly as the server's state machine and policies
 * say. Steps this user isn't allowed to take explain why.
 */
export function NextStepCard({
    actions,
    quotation,
    onAction,
}: {
    actions: NextAction[];
    quotation: QuotationPageProps['quotation'];
    onAction: (action: NextAction) => void;
}) {
    const visible = actions.filter((action) => action.key !== 'cancel');
    const cancel = actions.find((action) => action.key === 'cancel');
    const reasons = [
        ...new Set(
            visible
                .filter((action) => !action.allowed && action.reason)
                .map((action) => action.reason as string),
        ),
    ];
    const adminOnly = actions.some(
        (action) => !action.allowed && action.reason?.startsWith('Admin only'),
    );

    return (
        <Card className="gap-4 border-primary/30">
            <CardHeader>
                <CardTitle className="text-base">Next step</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                {quotation.status === 'completed' && (
                    <p className="flex items-start gap-2 text-sm">
                        <CircleCheck className="mt-0.5 size-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                        <span>
                            Paid in full and completed on{' '}
                            {formatDate(quotation.completed_at)}.
                        </span>
                    </p>
                )}
                {quotation.status === 'cancelled' && (
                    <p className="flex items-start gap-2 text-sm">
                        <Ban className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                        <span>
                            Cancelled on {formatDate(quotation.cancelled_at)}:{' '}
                            <span className="text-muted-foreground">
                                {quotation.cancel_reason}
                            </span>
                        </span>
                    </p>
                )}

                {visible.length > 0 && (
                    <div className="space-y-2">
                        {visible.map((action) => {
                            const Icon = action.allowed
                                ? ACTION_ICONS[action.key]
                                : Lock;
                            const destructive = action.key === 'delete_so';

                            return (
                                <Button
                                    key={`${action.key}-${action.invoice_id ?? ''}`}
                                    className={cn(
                                        'w-full',
                                        destructive &&
                                            'border-destructive-foreground/25 text-destructive-foreground hover:bg-destructive/10 hover:text-destructive-foreground dark:hover:bg-destructive/30',
                                    )}
                                    variant={
                                        action.primary
                                            ? 'default'
                                            : destructive
                                              ? 'outline'
                                              : 'secondary'
                                    }
                                    disabled={!action.allowed}
                                    onClick={() => onAction(action)}
                                >
                                    <Icon />
                                    {action.label}
                                </Button>
                            );
                        })}
                    </div>
                )}

                {(reasons.length > 0 || adminOnly) && (
                    <div className="space-y-2.5 rounded-lg border bg-muted/40 p-3">
                        {reasons.map((reason) => (
                            <p
                                key={reason}
                                className="text-xs leading-relaxed text-muted-foreground"
                            >
                                {reason}
                            </p>
                        ))}
                        {adminOnly && (
                            <Button
                                variant="secondary"
                                size="sm"
                                className="w-full"
                                onClick={() =>
                                    router.post(
                                        demo.switchRole.url(),
                                        { role: 'admin' },
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                <UserCog /> Switch to Admin
                            </Button>
                        )}
                    </div>
                )}

                {cancel && (
                    <div className={cn(visible.length > 0 && 'border-t pt-3')}>
                        <Button
                            variant="ghost"
                            size="sm"
                            className="w-full text-muted-foreground hover:bg-destructive/10 hover:text-destructive-foreground dark:hover:bg-destructive/30"
                            disabled={!cancel.allowed}
                            onClick={() => onAction(cancel)}
                            title={cancel.reason ?? undefined}
                        >
                            {cancel.allowed ? <Ban /> : <Lock />}
                            Cancel quotation
                        </Button>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
