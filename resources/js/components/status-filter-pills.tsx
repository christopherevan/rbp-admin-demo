import { router } from '@inertiajs/react';
import { cn } from '@/lib/utils';

/**
 * "All / New / In review / …" filter pills with counts, kept in sync with
 * ?status= so a filtered view can be linked to.
 */
export function StatusFilterPills({
    statuses,
    counts,
    active,
    filters,
}: {
    statuses: Record<string, { label: string }>;
    counts: Record<string, number>;
    active: string | null | undefined;
    filters: Record<string, unknown>;
}) {
    const total = Object.values(counts).reduce((sum, n) => sum + Number(n), 0);

    const select = (status: string | null) => {
        router.get(
            window.location.pathname,
            clean({ ...filters, status, page: undefined }),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <div className="flex flex-wrap gap-1.5">
            <Pill
                label="All"
                count={total}
                active={!active}
                onClick={() => select(null)}
            />
            {Object.entries(statuses).map(([status, { label }]) => (
                <Pill
                    key={status}
                    label={label}
                    count={Number(counts[status] ?? 0)}
                    active={active === status}
                    onClick={() => select(status)}
                />
            ))}
        </div>
    );
}

function Pill({
    label,
    count,
    active,
    onClick,
}: {
    label: string;
    count: number;
    active: boolean;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm transition-colors',
                active
                    ? 'border-primary bg-primary text-primary-foreground'
                    : 'bg-background hover:bg-muted',
            )}
        >
            {label}
            <span
                className={cn(
                    'rounded-full px-1.5 text-xs tabular-nums',
                    active
                        ? 'bg-primary-foreground/20'
                        : 'bg-muted text-muted-foreground',
                )}
            >
                {count}
            </span>
        </button>
    );
}

export function clean(
    params: Record<string, unknown>,
): Record<string, string | number | boolean> {
    return Object.fromEntries(
        Object.entries(params).filter(
            ([, value]) =>
                value !== null &&
                value !== undefined &&
                value !== '' &&
                value !== false,
        ),
    ) as Record<string, string | number | boolean>;
}
