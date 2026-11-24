import { Check, ChevronsUpDown } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { formatIDR } from '@/lib/format';
import { cn } from '@/lib/utils';
import parts from '@/routes/parts';
import type { PartOption } from '@/types';

/** Server-side typeahead over the parts master (replaces the original's Select2). */
export function PartCombobox({
    value,
    excluded = [],
    onSelect,
    disabled,
}: {
    value: PartOption | null;
    excluded?: number[];
    onSelect: (part: PartOption) => void;
    disabled?: boolean;
}) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<PartOption[]>([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (!open) {
            return;
        }

        const controller = new AbortController();
        const timer = setTimeout(() => {
            setLoading(true);
            fetch(parts.search.url({ query: { q: query } }), {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            })
                .then((response) => response.json())
                .then((data: PartOption[]) => setResults(data))
                .catch(() => {})
                .finally(() => setLoading(false));
        }, 200);

        return () => {
            clearTimeout(timer);
            controller.abort();
        };
    }, [query, open]);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    disabled={disabled}
                    className="w-full justify-between font-normal"
                >
                    <span className="truncate text-left">
                        {value ? (
                            <>
                                <span className="font-medium">
                                    {value.part_number}
                                </span>
                                <span className="ml-2 text-muted-foreground">
                                    {value.description}
                                </span>
                            </>
                        ) : (
                            <span className="text-muted-foreground">
                                Search parts…
                            </span>
                        )}
                    </span>
                    <ChevronsUpDown className="opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                className="w-[min(32rem,calc(100vw-2rem))] p-0"
                align="start"
            >
                <Command shouldFilter={false}>
                    <CommandInput
                        placeholder="Part number or description"
                        value={query}
                        onValueChange={setQuery}
                    />
                    <CommandList>
                        <CommandEmpty>
                            {loading ? 'Searching…' : 'No parts found.'}
                        </CommandEmpty>
                        <CommandGroup>
                            {results.map((part) => {
                                const taken =
                                    excluded.includes(part.id) &&
                                    part.id !== value?.id;

                                return (
                                    <CommandItem
                                        key={part.id}
                                        value={String(part.id)}
                                        disabled={taken}
                                        onSelect={() => {
                                            onSelect(part);
                                            setOpen(false);
                                        }}
                                    >
                                        <Check
                                            className={cn(
                                                'size-4',
                                                value?.id === part.id
                                                    ? 'opacity-100'
                                                    : 'opacity-0',
                                            )}
                                        />
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate font-medium">
                                                {part.part_number}
                                                {taken && (
                                                    <span className="ml-2 text-xs text-muted-foreground">
                                                        (already added)
                                                    </span>
                                                )}
                                            </p>
                                            <p className="truncate text-xs text-muted-foreground">
                                                {part.description}
                                            </p>
                                        </div>
                                        <div className="text-right text-xs">
                                            <p className="tabular-nums">
                                                {formatIDR(part.list_price)}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {part.stocked
                                                    ? `${part.stock_qty} ${part.uom} in stock`
                                                    : 'Service'}
                                            </p>
                                        </div>
                                    </CommandItem>
                                );
                            })}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
