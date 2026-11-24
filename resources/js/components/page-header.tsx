import type { ReactNode } from 'react';
import { FeatureNote } from '@/components/feature-note';
import type { FeatureNoteKey } from '@/lib/feature-notes';

export function PageHeader({
    title,
    description,
    note,
    actions,
    children,
}: {
    title: ReactNode;
    description?: ReactNode;
    note?: FeatureNoteKey;
    actions?: ReactNode;
    children?: ReactNode;
}) {
    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div className="min-w-0 space-y-1">
                <h1 className="flex flex-wrap items-center gap-2 text-xl font-semibold tracking-tight">
                    {title}
                </h1>
                {description && (
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
                {children}
            </div>
            <div className="flex shrink-0 flex-wrap items-center gap-2">
                {note && <FeatureNote note={note} />}
                {actions}
            </div>
        </div>
    );
}
