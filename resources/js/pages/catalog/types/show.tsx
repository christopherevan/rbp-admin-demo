import { Head, Link, router, useForm } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, Check, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { FormField } from '@/components/form-field';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import catalog from '@/routes/catalog';

type CategoryRow = {
    id: number;
    supplier: string;
    full_path: string;
    product_type_id: number | null;
    assigned_type: string | null;
    product_count: number;
};

type AttributeGroup = {
    id: number;
    supplier: string;
    label: string;
    unit: string | null;
};

type KeyAttribute = {
    id: number;
    ulid: string;
    label: string;
    unit: string | null;
    position: number;
    group_ids: number[];
};

type ProductTypeDetail = {
    id: number;
    ulid: string;
    name: string;
    slug: string;
    header_image: string | null;
    sort_order: number;
    key_attributes: KeyAttribute[];
};

type Props = {
    type: ProductTypeDetail;
    headerPresets: string[];
    defaultHeader: string;
    categories: Record<string, CategoryRow[]>;
    groups: Record<string, AttributeGroup[]>;
    claimed: Record<number, string>;
    pending: { set: number; clear: number };
    canManage: boolean;
};

const DEFAULT_HEADER_VALUE = '__default__';

export default function CatalogTypeShow({
    type,
    headerPresets,
    defaultHeader,
    categories,
    groups,
    claimed,
    pending,
    canManage,
}: Props) {
    const initialCategoryIds = Object.values(categories)
        .flat()
        .filter((row) => row.product_type_id === type.id)
        .map((row) => row.id);

    const [selectedCategories, setSelectedCategories] =
        useState<number[]>(initialCategoryIds);

    const settingsForm = useForm({
        name: type.name,
        slug: type.slug,
        header_image: type.header_image ?? '',
        sort_order: String(type.sort_order),
    });

    const addAttrForm = useForm({
        label: '',
        unit: '',
    });

    const hasDrift = pending.set > 0 || pending.clear > 0;

    const saveSettings = (event: FormEvent) => {
        event.preventDefault();
        settingsForm.submit(catalog.types.update(type.ulid), {
            preserveScroll: true,
        });
    };

    const saveCategories = (event: FormEvent) => {
        event.preventDefault();
        router.post(
            catalog.types.categories.url(type.ulid),
            { categories: selectedCategories },
            { preserveScroll: true },
        );
    };

    const addKeyAttribute = (event: FormEvent) => {
        event.preventDefault();
        addAttrForm.submit(catalog.types.attributes.store(type.ulid), {
            preserveScroll: true,
            onSuccess: () => addAttrForm.reset(),
        });
    };

    const toggleCategory = (id: number, checked: boolean) => {
        setSelectedCategories((prev) =>
            checked ? [...prev, id] : prev.filter((item) => item !== id),
        );
    };

    return (
        <>
            <Head title={`${type.name} - Product types`} />
            <PageHeader
                title={
                    <>
                        <span>{type.name}</span>
                        <span className="rounded-md bg-muted px-2 py-0.5 font-mono text-xs font-normal text-muted-foreground">
                            /{type.slug}
                        </span>
                    </>
                }
                description="Configure URL slug, supplier category mappings, and key product specifications."
                note="catalogTypes"
                actions={
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={catalog.types.index.url()}>
                                <ArrowLeft className="size-4" /> All types
                            </Link>
                        </Button>
                        {canManage && (
                            <Button
                                size="sm"
                                onClick={() =>
                                    router.post(
                                        catalog.types.apply.url(),
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                Apply to products
                            </Button>
                        )}
                    </div>
                }
            />

            {!canManage && (
                <p className="rounded-lg border bg-muted/40 px-4 py-2.5 text-sm text-muted-foreground">
                    Sales reps have read-only access to catalog taxonomy. Switch
                    to Admin to edit settings, categories, or key attributes.
                </p>
            )}

            {hasDrift && (
                <div className="flex flex-wrap items-center gap-3 rounded-xl border border-amber-500/30 bg-amber-500/5 px-4 py-3">
                    <AlertTriangle className="size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                    <div className="min-w-0 flex-1">
                        <p className="text-sm font-medium">
                            Assignments are not live yet
                        </p>
                        <p className="text-xs text-muted-foreground tabular-nums">
                            {formatNumber(pending.set)}{' '}
                            {pending.set === 1 ? 'product' : 'products'} would
                            be assigned or moved, {formatNumber(pending.clear)}{' '}
                            cleared.
                        </p>
                    </div>
                    {canManage && (
                        <Button
                            size="sm"
                            onClick={() =>
                                router.post(
                                    catalog.types.apply.url(),
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            Apply now
                        </Button>
                    )}
                </div>
            )}

            <Card>
                <CardContent>
                    <form
                        onSubmit={saveSettings}
                        className="grid gap-4 md:grid-cols-12 md:items-start"
                    >
                        <FormField
                            label="Name"
                            htmlFor="type-name"
                            error={settingsForm.errors.name}
                            className="md:col-span-3"
                        >
                            <Input
                                id="type-name"
                                value={settingsForm.data.name}
                                onChange={(e) =>
                                    settingsForm.setData('name', e.target.value)
                                }
                                disabled={!canManage}
                                required
                            />
                        </FormField>

                        <FormField
                            label="URL slug"
                            htmlFor="type-slug"
                            error={settingsForm.errors.slug}
                            hint="Reserved for public URLs. Clear to regenerate from name."
                            className="md:col-span-3"
                        >
                            <Input
                                id="type-slug"
                                className="font-mono"
                                value={settingsForm.data.slug}
                                onChange={(e) =>
                                    settingsForm.setData('slug', e.target.value)
                                }
                                disabled={!canManage}
                            />
                        </FormField>

                        <FormField
                            label="Header image"
                            htmlFor="type-header"
                            error={settingsForm.errors.header_image}
                            hint="Preset banner in public headers."
                            className="md:col-span-3"
                        >
                            <Select
                                value={
                                    settingsForm.data.header_image ||
                                    DEFAULT_HEADER_VALUE
                                }
                                onValueChange={(val) =>
                                    settingsForm.setData(
                                        'header_image',
                                        val === DEFAULT_HEADER_VALUE ? '' : val,
                                    )
                                }
                                disabled={!canManage}
                            >
                                <SelectTrigger id="type-header">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={DEFAULT_HEADER_VALUE}>
                                        Default ({defaultHeader})
                                    </SelectItem>
                                    {headerPresets.map((file) => (
                                        <SelectItem key={file} value={file}>
                                            {file}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </FormField>

                        <FormField
                            label="Order"
                            htmlFor="type-order"
                            error={settingsForm.errors.sort_order}
                            className="md:col-span-1"
                        >
                            <Input
                                id="type-order"
                                type="number"
                                min={0}
                                className="tabular-nums"
                                value={settingsForm.data.sort_order}
                                onChange={(e) =>
                                    settingsForm.setData(
                                        'sort_order',
                                        e.target.value,
                                    )
                                }
                                disabled={!canManage}
                            />
                        </FormField>

                        <div className="md:col-span-2 md:pt-5.5">
                            <Button
                                type="submit"
                                className="w-full"
                                disabled={!canManage || settingsForm.processing}
                            >
                                {settingsForm.processing && <Spinner />}
                                Save
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <div className="grid gap-6 lg:grid-cols-2">
                <form onSubmit={saveCategories} className="flex flex-col">
                    <Card className="flex-1 gap-4">
                        <CardHeader className="flex flex-row items-center justify-between gap-2 border-b pb-4">
                            <div>
                                <CardTitle className="text-base">
                                    Categories
                                </CardTitle>
                                <CardDescription>
                                    Exact path only — sub-categories are not
                                    included.
                                </CardDescription>
                            </div>
                            {canManage && (
                                <Button type="submit" size="sm">
                                    <Check className="size-4" /> Save
                                    assignments
                                </Button>
                            )}
                        </CardHeader>
                        <CardContent className="max-h-[28rem] space-y-5 overflow-y-auto">
                            {Object.entries(categories).map(
                                ([supplier, rows]) => (
                                    <div key={supplier} className="space-y-1">
                                        <h3 className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                            {supplier}
                                        </h3>
                                        <div className="divide-y rounded-lg border">
                                            {rows.map((row) => {
                                                const checked =
                                                    selectedCategories.includes(
                                                        row.id,
                                                    );
                                                const takenByOther =
                                                    row.product_type_id !==
                                                        null &&
                                                    row.product_type_id !==
                                                        type.id;

                                                return (
                                                    <label
                                                        key={row.id}
                                                        htmlFor={`cat-${row.id}`}
                                                        className={cn(
                                                            'flex cursor-pointer items-center gap-3 px-3 py-2 text-sm',
                                                            checked &&
                                                                'bg-primary/5',
                                                            !canManage &&
                                                                'cursor-default',
                                                        )}
                                                    >
                                                        <Checkbox
                                                            id={`cat-${row.id}`}
                                                            checked={checked}
                                                            disabled={
                                                                !canManage
                                                            }
                                                            onCheckedChange={(
                                                                val,
                                                            ) =>
                                                                toggleCategory(
                                                                    row.id,
                                                                    Boolean(
                                                                        val,
                                                                    ),
                                                                )
                                                            }
                                                        />
                                                        <span className="min-w-0 flex-1">
                                                            <span className="block truncate">
                                                                {row.full_path}
                                                            </span>
                                                            {takenByOther &&
                                                                row.assigned_type && (
                                                                    <span className="mt-0.5 inline-flex rounded bg-amber-50 px-1.5 py-0.5 text-xs text-amber-800 ring-1 ring-amber-600/20 ring-inset dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/30">
                                                                        moves
                                                                        from{' '}
                                                                        {
                                                                            row.assigned_type
                                                                        }
                                                                    </span>
                                                                )}
                                                        </span>
                                                        <span className="rounded bg-muted px-2 py-0.5 text-xs font-medium tabular-nums">
                                                            {formatNumber(
                                                                row.product_count,
                                                            )}
                                                        </span>
                                                    </label>
                                                );
                                            })}
                                        </div>
                                    </div>
                                ),
                            )}
                        </CardContent>
                    </Card>
                </form>

                <Card className="gap-4 self-start">
                    <CardHeader className="border-b pb-4">
                        <CardTitle className="text-base">
                            Key attributes
                        </CardTitle>
                        <CardDescription>
                            Printed under the description on the product page.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {canManage && (
                            <form
                                onSubmit={addKeyAttribute}
                                className="grid grid-cols-12 items-start gap-2"
                            >
                                <div className="col-span-6">
                                    <Input
                                        placeholder="Bore"
                                        value={addAttrForm.data.label}
                                        onChange={(e) =>
                                            addAttrForm.setData(
                                                'label',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                    {addAttrForm.errors.label && (
                                        <p className="mt-1 text-xs text-destructive">
                                            {addAttrForm.errors.label}
                                        </p>
                                    )}
                                </div>
                                <div className="col-span-3">
                                    <Input
                                        placeholder="mm"
                                        value={addAttrForm.data.unit}
                                        onChange={(e) =>
                                            addAttrForm.setData(
                                                'unit',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="col-span-3">
                                    <Button
                                        type="submit"
                                        variant="secondary"
                                        className="w-full"
                                        disabled={addAttrForm.processing}
                                    >
                                        {addAttrForm.processing ? (
                                            <Spinner />
                                        ) : (
                                            <Plus className="size-4" />
                                        )}
                                        Add
                                    </Button>
                                </div>
                            </form>
                        )}

                        {type.key_attributes.length === 0 ? (
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                No key attributes yet. Add one above, then tick
                                the supplier fields that feed it.
                            </p>
                        ) : (
                            <div className="space-y-4">
                                {type.key_attributes.map((attr) => (
                                    <KeyAttributeCard
                                        key={attr.id}
                                        attribute={attr}
                                        typeName={type.name}
                                        groups={groups}
                                        claimed={claimed}
                                        canManage={canManage}
                                    />
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {canManage && (
                <div className="flex flex-wrap items-center justify-between gap-3 border-t pt-4">
                    <ConfirmDialog
                        trigger={
                            <Button variant="destructive" size="sm">
                                <Trash2 className="size-4" /> Delete type
                            </Button>
                        }
                        title={`Delete “${type.name}”?`}
                        description="Its category assignments and key attributes go with it, and its products become untyped until mapped elsewhere."
                        confirmLabel="Delete type"
                        destructive
                        onConfirm={() =>
                            router.delete(catalog.types.destroy.url(type.ulid))
                        }
                    />
                    <Button
                        onClick={() =>
                            router.post(
                                catalog.types.apply.url(),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        Apply to products
                    </Button>
                </div>
            )}
        </>
    );
}

function KeyAttributeCard({
    attribute,
    typeName,
    groups,
    claimed,
    canManage,
}: {
    attribute: KeyAttribute;
    typeName: string;
    groups: Record<string, AttributeGroup[]>;
    claimed: Record<number, string>;
    canManage: boolean;
}) {
    const form = useForm({
        label: attribute.label,
        unit: attribute.unit ?? '',
        position: String(attribute.position),
        groups: attribute.group_ids,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(catalog.types.attributes.update(attribute.ulid), {
            preserveScroll: true,
        });
    };

    const toggleGroup = (groupId: number, checked: boolean) => {
        form.setData(
            'groups',
            checked
                ? [...form.data.groups, groupId]
                : form.data.groups.filter((id) => id !== groupId),
        );
    };

    return (
        <div className="rounded-lg border p-4">
            <form onSubmit={submit} className="space-y-3">
                <div className="grid grid-cols-12 items-end gap-2">
                    <FormField
                        label="Label"
                        htmlFor={`attr-label-${attribute.id}`}
                        error={form.errors.label}
                        className="col-span-5"
                    >
                        <Input
                            id={`attr-label-${attribute.id}`}
                            value={form.data.label}
                            onChange={(e) =>
                                form.setData('label', e.target.value)
                            }
                            disabled={!canManage}
                            required
                        />
                    </FormField>

                    <FormField
                        label="Unit"
                        htmlFor={`attr-unit-${attribute.id}`}
                        error={form.errors.unit}
                        className="col-span-3"
                    >
                        <Input
                            id={`attr-unit-${attribute.id}`}
                            value={form.data.unit}
                            onChange={(e) =>
                                form.setData('unit', e.target.value)
                            }
                            disabled={!canManage}
                        />
                    </FormField>

                    <FormField
                        label="Order"
                        htmlFor={`attr-pos-${attribute.id}`}
                        error={form.errors.position}
                        className="col-span-2"
                    >
                        <Input
                            id={`attr-pos-${attribute.id}`}
                            type="number"
                            min={0}
                            className="tabular-nums"
                            value={form.data.position}
                            onChange={(e) =>
                                form.setData('position', e.target.value)
                            }
                            disabled={!canManage}
                        />
                    </FormField>

                    <div className="col-span-2">
                        <Button
                            type="submit"
                            size="sm"
                            className="w-full"
                            disabled={!canManage || form.processing}
                        >
                            {form.processing && <Spinner />}
                            Save
                        </Button>
                    </div>
                </div>

                {form.errors.groups && (
                    <p className="text-xs text-destructive">
                        {form.errors.groups}
                    </p>
                )}

                <div className="max-h-56 space-y-3 overflow-y-auto border-t pt-3">
                    {Object.entries(groups).map(
                        ([supplier, supplierGroups]) => (
                            <div key={supplier} className="space-y-1.5">
                                <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    {supplier}
                                </p>
                                <div className="grid gap-1 sm:grid-cols-2">
                                    {supplierGroups.map((group) => {
                                        const checked =
                                            form.data.groups.includes(group.id);
                                        const claimedBy =
                                            !attribute.group_ids.includes(
                                                group.id,
                                            )
                                                ? claimed[group.id]
                                                : undefined;

                                        return (
                                            <label
                                                key={group.id}
                                                htmlFor={`g-${attribute.id}-${group.id}`}
                                                className={cn(
                                                    'flex items-center gap-2 rounded px-1.5 py-1 text-xs',
                                                    claimedBy || !canManage
                                                        ? 'cursor-not-allowed opacity-60'
                                                        : 'cursor-pointer hover:bg-muted/50',
                                                )}
                                            >
                                                <Checkbox
                                                    id={`g-${attribute.id}-${group.id}`}
                                                    checked={checked}
                                                    disabled={
                                                        !canManage ||
                                                        Boolean(claimedBy)
                                                    }
                                                    onCheckedChange={(val) =>
                                                        toggleGroup(
                                                            group.id,
                                                            Boolean(val),
                                                        )
                                                    }
                                                />
                                                <span className="min-w-0 truncate">
                                                    {group.label}
                                                    {group.unit && (
                                                        <span className="text-muted-foreground">
                                                            {' '}
                                                            ({group.unit})
                                                        </span>
                                                    )}
                                                    {claimedBy && (
                                                        <span className="text-amber-700 dark:text-amber-400">
                                                            {' '}
                                                            — used by “
                                                            {claimedBy}”
                                                        </span>
                                                    )}
                                                </span>
                                            </label>
                                        );
                                    })}
                                </div>
                            </div>
                        ),
                    )}
                </div>
            </form>

            {canManage && (
                <div className="mt-3 flex justify-end border-t pt-2">
                    <ConfirmDialog
                        trigger={
                            <Button
                                variant="ghost"
                                size="sm"
                                className="text-destructive hover:text-destructive"
                            >
                                Remove
                            </Button>
                        }
                        title={`Remove “${attribute.label}”?`}
                        description={`This removes the key attribute from ${typeName}.`}
                        confirmLabel="Remove"
                        destructive
                        onConfirm={() =>
                            router.delete(
                                catalog.types.attributes.destroy.url(
                                    attribute.ulid,
                                ),
                                { preserveScroll: true },
                            )
                        }
                    />
                </div>
            )}
        </div>
    );
}

CatalogTypeShow.layout = {
    breadcrumbs: [{ title: 'Product types', href: catalog.types.index() }],
};
