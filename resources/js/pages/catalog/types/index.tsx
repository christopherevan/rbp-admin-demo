import { Head, Link, router, useForm } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, Plus, Tags } from 'lucide-react';
import type { FormEvent } from 'react';
import { FormField } from '@/components/form-field';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatNumber } from '@/lib/format';
import catalog from '@/routes/catalog';

type ProductTypeSummary = {
    id: number;
    ulid: string;
    name: string;
    slug: string;
    header_image: string | null;
    sort_order: number;
    products_count: number;
    category_mappings_count: number;
    key_attributes_count: number;
};

type Props = {
    types: ProductTypeSummary[];
    unmapped: number;
    pending: { set: number; clear: number };
    canManage: boolean;
};

export default function CatalogTypesIndex({
    types,
    unmapped,
    pending,
    canManage,
}: Props) {
    const form = useForm({ name: '' });
    const hasDrift = pending.set > 0 || pending.clear > 0;

    const createType = (event: FormEvent) => {
        event.preventDefault();
        form.submit(catalog.types.store(), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <>
            <Head title="Product types" />
            <PageHeader
                title="Product types"
                description="Group supplier category trees into shared types and define the key specs shown on product pages."
                note="catalogTypes"
            />

            {!canManage && (
                <p className="rounded-lg border bg-muted/40 px-4 py-2.5 text-sm text-muted-foreground">
                    Sales reps have read-only access to catalog taxonomy. Switch
                    to Admin to edit types, mappings, or key attributes.
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

            <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
                <Card className="gap-0 self-start py-0">
                    <CardHeader className="flex flex-row items-center justify-between border-b py-4">
                        <div>
                            <CardTitle className="text-base">Types</CardTitle>
                            <CardDescription>
                                Ordered as they appear in public navigation.
                            </CardDescription>
                        </div>
                        <span className="inline-flex items-center rounded-md bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700 tabular-nums ring-1 ring-sky-600/20 ring-inset dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/30">
                            {formatNumber(unmapped)} products still untyped
                        </span>
                    </CardHeader>

                    {types.length === 0 ? (
                        <CardContent className="py-12 text-center">
                            <Tags className="mx-auto mb-2 size-6 text-muted-foreground" />
                            <p className="text-sm font-medium">No types yet</p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                A type groups Beltora’s and Conveyix’s own
                                categories into one thing you control.
                            </p>
                        </CardContent>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Type</TableHead>
                                    <TableHead className="text-center">
                                        Categories
                                    </TableHead>
                                    <TableHead className="text-center">
                                        Key attributes
                                    </TableHead>
                                    <TableHead className="text-center">
                                        Products
                                    </TableHead>
                                    <TableHead className="w-24 text-right" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {types.map((type) => (
                                    <TableRow key={type.id}>
                                        <TableCell>
                                            <Link
                                                href={catalog.types.show.url(
                                                    type.ulid,
                                                )}
                                                className="font-medium hover:text-primary"
                                            >
                                                {type.name}
                                            </Link>
                                            <p className="font-mono text-xs text-muted-foreground">
                                                /{type.slug}
                                            </p>
                                        </TableCell>
                                        <TableCell className="text-center tabular-nums">
                                            <span className="inline-flex rounded-md bg-muted px-2 py-0.5 text-xs font-medium">
                                                {formatNumber(
                                                    type.category_mappings_count,
                                                )}
                                            </span>
                                        </TableCell>
                                        <TableCell className="text-center tabular-nums">
                                            {type.key_attributes_count > 0 ? (
                                                <span className="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-600/20 ring-inset dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/30">
                                                    {formatNumber(
                                                        type.key_attributes_count,
                                                    )}
                                                </span>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-center font-medium tabular-nums">
                                            {formatNumber(type.products_count)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={catalog.types.show.url(
                                                        type.ulid,
                                                    )}
                                                >
                                                    Manage
                                                    <ArrowRight className="size-3.5" />
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </Card>

                <div className="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                New type
                            </CardTitle>
                        </CardHeader>
                        <form onSubmit={createType}>
                            <CardContent>
                                <FormField
                                    label="Name"
                                    htmlFor="type-name"
                                    error={form.errors.name}
                                    hint="A URL slug is generated from the name and then left alone — renaming later will not move it."
                                >
                                    <Input
                                        id="type-name"
                                        placeholder="Sprockets"
                                        value={form.data.name}
                                        onChange={(e) =>
                                            form.setData('name', e.target.value)
                                        }
                                        disabled={!canManage}
                                        required
                                    />
                                </FormField>
                            </CardContent>
                            <CardFooter className="mt-4">
                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={!canManage || form.processing}
                                >
                                    {form.processing ? (
                                        <Spinner />
                                    ) : (
                                        <Plus className="size-4" />
                                    )}
                                    Create type
                                </Button>
                            </CardFooter>
                        </form>
                    </Card>

                    <Card className="bg-muted/30">
                        <CardHeader>
                            <CardTitle className="text-sm">
                                How this works
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ol className="list-decimal space-y-2 pl-4 text-xs text-muted-foreground">
                                <li>Create a type.</li>
                                <li>
                                    Assign the suppliers’ categories that belong
                                    to it.
                                </li>
                                <li>
                                    Name the specs it should show, and tick
                                    which supplier fields feed each one.
                                </li>
                                <li>
                                    <strong className="text-foreground">
                                        Apply
                                    </strong>{' '}
                                    to move the products.
                                </li>
                            </ol>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

CatalogTypesIndex.layout = {
    breadcrumbs: [{ title: 'Product types', href: catalog.types.index() }],
};
