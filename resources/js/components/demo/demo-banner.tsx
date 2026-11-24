import { router, usePage } from '@inertiajs/react';
import { FlaskConical, RotateCcw, UserCog } from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { Button } from '@/components/ui/button';
import { timeAgo } from '@/lib/format';
import demo from '@/routes/demo';

/**
 * Always-visible reminder that this is a private, disposable sandbox, with
 * the two things a visitor most wants: switch role, start over.
 */
export function DemoBanner() {
    const { auth, demo: info } = usePage().props;

    if (!auth.user) {
        return null;
    }

    const otherRole = auth.user.role === 'admin' ? 'sales' : 'admin';

    return (
        <div className="flex flex-col gap-2 border-b border-primary/15 bg-primary/5 px-4 py-2 text-sm sm:flex-row sm:items-center md:rounded-t-xl">
            <p className="flex min-w-0 flex-1 items-center gap-2 text-muted-foreground">
                <FlaskConical className="size-4 shrink-0 text-primary" />
                <span className="min-w-0">
                    <span className="font-medium text-foreground">
                        Your private sandbox.
                    </span>
                    <span className="hidden md:inline">
                        {' '}
                        Change anything; nobody else sees it.
                    </span>
                    {info.sandboxStartedAt && (
                        <span className="hidden xl:inline">
                            {' '}
                            Started {timeAgo(info.sandboxStartedAt)}.
                        </span>
                    )}{' '}
                    <span className="whitespace-nowrap">
                        You're{' '}
                        <span className="font-medium text-foreground">
                            {auth.user.name}
                        </span>{' '}
                        <span className="rounded bg-background px-1.5 py-0.5 text-xs font-medium uppercase ring-1 ring-border">
                            {auth.user.role}
                        </span>
                    </span>
                </span>
            </p>
            <div className="flex shrink-0 items-center gap-2">
                <Button
                    size="sm"
                    variant="outline"
                    className="h-7 bg-background"
                    onClick={() =>
                        router.post(
                            demo.switchRole.url(),
                            { role: otherRole },
                            { preserveScroll: true },
                        )
                    }
                >
                    <UserCog className="size-3.5" />
                    Switch to {otherRole === 'admin' ? 'Admin' : 'Sales'}
                </Button>
                <ConfirmDialog
                    trigger={
                        <Button
                            size="sm"
                            variant="outline"
                            className="h-7 bg-background"
                        >
                            <RotateCcw className="size-3.5" />
                            Reset
                        </Button>
                    }
                    title="Reset the demo?"
                    description="Your sandbox is replaced with a fresh copy of the demo data. Anything you created or changed is discarded."
                    confirmLabel="Reset"
                    onConfirm={() => router.post(demo.reset.url())}
                />
            </div>
        </div>
    );
}
