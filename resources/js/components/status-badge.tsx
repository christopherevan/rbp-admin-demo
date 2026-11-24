import { cn } from '@/lib/utils';
import {
    descriptionStatuses,
    documentStatuses,
    inquiryStatuses,
    quotationStatuses,
} from '@/lib/status';

const maps = {
    quotation: quotationStatuses,
    inquiry: inquiryStatuses,
    description: descriptionStatuses,
    document: documentStatuses,
} as const;

export function StatusBadge({
    kind,
    status,
    className,
}: {
    kind: keyof typeof maps;
    status: string;
    className?: string;
}) {
    const style = (
        maps[kind] as Record<string, { label: string; className: string }>
    )[status];

    return (
        <span
            className={cn(
                'inline-flex shrink-0 items-center rounded-md px-2 py-0.5 text-xs font-semibold tracking-wide uppercase ring-1 ring-inset',
                style?.className,
                className,
            )}
        >
            {style?.label ?? status}
        </span>
    );
}
