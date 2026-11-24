import {
    Ban,
    CircleCheck,
    CircleDot,
    FilePlus2,
    FileText,
    Inbox,
    Link2,
    ListChecks,
    PackageCheck,
    Receipt,
    Truck,
    UserCheck,
    Wallet,
    XCircle,
} from 'lucide-react';
import { formatDateTime, timeAgo } from '@/lib/format';
import type { Activity } from '@/types';

const icons: Record<string, typeof CircleDot> = {
    created: FilePlus2,
    updated: FileText,
    lines_saved: ListChecks,
    po_received: FileText,
    so_created: ListChecks,
    so_deleted: XCircle,
    do_created: Truck,
    delivered: PackageCheck,
    invoice_created: Receipt,
    payment_received: Wallet,
    completed: CircleCheck,
    cancelled: Ban,
    submitted: Inbox,
    assigned: UserCheck,
    customer_linked: Link2,
    approved: CircleCheck,
    rejected: XCircle,
    converted: FilePlus2,
    from_inquiry: Inbox,
};

/** The audit trail every workflow step writes to. */
export function LogTimeline({ activities }: { activities: Activity[] }) {
    if (activities.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">No activity yet.</p>
        );
    }

    return (
        <ol className="relative space-y-4 before:absolute before:top-2 before:bottom-2 before:left-[11px] before:w-px before:bg-border">
            {activities.map((activity) => {
                const Icon = icons[activity.action] ?? CircleDot;

                return (
                    <li key={activity.id} className="relative flex gap-3">
                        <span className="z-10 flex size-6 shrink-0 items-center justify-center rounded-full border bg-background">
                            <Icon className="size-3.5 text-primary" />
                        </span>
                        <div className="min-w-0 text-sm">
                            <p className="leading-snug">
                                {activity.description}
                            </p>
                            <p
                                className="mt-0.5 text-xs text-muted-foreground"
                                title={formatDateTime(activity.created_at)}
                            >
                                {activity.user ?? 'Website'} ·{' '}
                                {timeAgo(activity.created_at)}
                            </p>
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
