import { Info, Server, Sparkles, Wrench } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { featureNotes } from '@/lib/feature-notes';
import type { FeatureNoteKey } from '@/lib/feature-notes';

/**
 * "About this feature": what the screen does and what the production system
 * does beyond the demo. One on every page.
 */
export function FeatureNote({ note }: { note: FeatureNoteKey }) {
    const content = featureNotes[note];

    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className="gap-1.5 border-primary/30 text-primary hover:bg-primary/5 hover:text-primary"
                >
                    <Sparkles className="size-3.5" />
                    About this feature
                </Button>
            </PopoverTrigger>
            <PopoverContent
                align="end"
                className="w-[min(26rem,calc(100vw-2rem))] p-0"
            >
                <div className="border-b px-4 py-3">
                    <p className="text-sm font-semibold">{content.title}</p>
                </div>
                <div className="max-h-[60vh] space-y-4 overflow-y-auto px-4 py-3 text-sm">
                    <Section
                        icon={Info}
                        title="What it does"
                        items={content.what}
                    />
                    {'production' in content && content.production && (
                        <Section
                            icon={Server}
                            title="In production (not in this demo)"
                            items={content.production}
                        />
                    )}
                    <div>
                        <p className="mb-1.5 flex items-center gap-1.5 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            <Wrench className="size-3.5" /> Built with
                        </p>
                        <div className="flex flex-wrap gap-1.5">
                            {content.builtWith.map((item) => (
                                <span
                                    key={item}
                                    className="rounded-md bg-muted px-2 py-0.5 text-xs"
                                >
                                    {item}
                                </span>
                            ))}
                        </div>
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    );
}

function Section({
    icon: Icon,
    title,
    items,
}: {
    icon: typeof Info;
    title: string;
    items: string[];
}) {
    return (
        <div>
            <p className="mb-1.5 flex items-center gap-1.5 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                <Icon className="size-3.5" /> {title}
            </p>
            <ul className="space-y-1.5">
                {items.map((item) => (
                    <li key={item} className="flex gap-2 leading-snug">
                        <span className="mt-2 size-1 shrink-0 rounded-full bg-primary/60" />
                        <span>{item}</span>
                    </li>
                ))}
            </ul>
        </div>
    );
}
