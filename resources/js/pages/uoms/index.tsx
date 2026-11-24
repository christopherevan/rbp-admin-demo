import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Check, Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import uomRoutes from '@/routes/uoms';

type Uom = {
    id: number;
    ulid: string;
    code: string;
    name: string;
    parts_count: number;
};

export default function UomIndex({ uoms }: { uoms: Uom[] }) {
    const { auth } = usePage().props;
    const [editing, setEditing] = useState<number | null>(null);
    const create = useForm({ code: '', name: '' });

    const add = (event: FormEvent) => {
        event.preventDefault();
        create.submit(uomRoutes.store(), {
            preserveScroll: true,
            onSuccess: () => create.reset(),
        });
    };

    return (
        <>
            <Head title="Units" />
            <PageHeader
                title="Units of measure"
                description="Units used by parts. A unit in use can't be deleted."
                note="uoms"
            />

            <Card className="max-w-2xl py-0">
                <CardContent className="p-0">
                    <ul className="divide-y">
                        {uoms.map((uom) =>
                            editing === uom.id ? (
                                <EditRow
                                    key={uom.id}
                                    uom={uom}
                                    onDone={() => setEditing(null)}
                                />
                            ) : (
                                <li
                                    key={uom.id}
                                    className="flex items-center gap-3 px-4 py-2.5 text-sm"
                                >
                                    <span className="w-16 font-mono font-semibold">
                                        {uom.code}
                                    </span>
                                    <span className="flex-1">{uom.name}</span>
                                    <span className="text-xs text-muted-foreground">
                                        {uom.parts_count} parts
                                    </span>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="size-8"
                                        onClick={() => setEditing(uom.id)}
                                        aria-label="Edit"
                                    >
                                        <Pencil />
                                    </Button>
                                    {auth.can?.archiveRecords && (
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-8 text-muted-foreground hover:text-destructive"
                                            onClick={() =>
                                                router.delete(
                                                    uomRoutes.destroy.url(
                                                        uom.ulid,
                                                    ),
                                                    { preserveScroll: true },
                                                )
                                            }
                                            aria-label="Delete"
                                        >
                                            <Trash2 />
                                        </Button>
                                    )}
                                </li>
                            ),
                        )}
                    </ul>
                    <form
                        onSubmit={add}
                        className="flex flex-wrap items-start gap-2 border-t bg-muted/30 px-4 py-3"
                    >
                        <div>
                            <Input
                                placeholder="Code"
                                value={create.data.code}
                                onChange={(e) =>
                                    create.setData(
                                        'code',
                                        e.target.value.toUpperCase(),
                                    )
                                }
                                className="w-24 font-mono"
                            />
                            <InputError message={create.errors.code} />
                        </div>
                        <div className="flex-1">
                            <Input
                                placeholder="Name, e.g. Kilogram"
                                value={create.data.name}
                                onChange={(e) =>
                                    create.setData('name', e.target.value)
                                }
                            />
                            <InputError message={create.errors.name} />
                        </div>
                        <Button type="submit" disabled={create.processing}>
                            <Plus /> Add
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}

function EditRow({ uom, onDone }: { uom: Uom; onDone: () => void }) {
    const form = useForm({ code: uom.code, name: uom.name });

    return (
        <li className="px-4 py-2">
            <form
                onSubmit={(event: FormEvent) => {
                    event.preventDefault();
                    form.submit(uomRoutes.update(uom.ulid), {
                        preserveScroll: true,
                        onSuccess: onDone,
                    });
                }}
                className="flex items-center gap-2"
            >
                <Input
                    value={form.data.code}
                    onChange={(e) =>
                        form.setData('code', e.target.value.toUpperCase())
                    }
                    className="w-24 font-mono"
                />
                <Input
                    value={form.data.name}
                    onChange={(e) => form.setData('name', e.target.value)}
                />
                <Button
                    type="submit"
                    size="icon"
                    className="size-9"
                    aria-label="Save"
                >
                    <Check />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="size-9"
                    onClick={onDone}
                    aria-label="Cancel"
                >
                    <X />
                </Button>
            </form>
            <InputError message={form.errors.code ?? form.errors.name} />
        </li>
    );
}

UomIndex.layout = {
    breadcrumbs: [{ title: 'Units', href: uomRoutes.index() }],
};
