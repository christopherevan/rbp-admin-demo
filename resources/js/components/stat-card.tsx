import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Card } from '@/components/ui/card';
import { cn } from '@/lib/utils';

export function StatCard({
    label,
    value,
    href,
    badge,
    hint,
}: {
    label: string;
    value: ReactNode;
    href?: NonNullable<InertiaLinkProps['href']>;
    badge?: ReactNode;
    hint?: ReactNode;
}) {
    const body = (
        <Card
            className={cn(
                'h-full gap-2 p-4 transition',
                href && 'hover:-translate-y-0.5 hover:shadow-md',
            )}
        >
            <div className="flex items-center justify-between gap-2">
                {badge ?? (
                    <span className="text-sm text-muted-foreground">
                        {label}
                    </span>
                )}
            </div>
            <div className="text-3xl font-semibold tabular-nums">{value}</div>
            {hint && (
                <div className="text-xs text-muted-foreground">{hint}</div>
            )}
        </Card>
    );

    return href ? (
        <Link
            href={href}
            prefetch
            className="block h-full rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        >
            {body}
        </Link>
    ) : (
        body
    );
}
