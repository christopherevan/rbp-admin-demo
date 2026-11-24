import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Archive, ArchiveRestore, FilePlus2, Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { FormField } from '@/components/form-field';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { formatDate, formatIDR, formatNumber } from '@/lib/format';
import customers from '@/routes/customers';
import quotations from '@/routes/quotations';
import type { QuotationStatus } from '@/types';

type Contact = {
    id?: number;
    salutation: 'mr' | 'ms';
    name: string;
    phone: string;
    email: string;
};

type Props = {
    customer: {
        id: number;
        ulid: string;
        code: string;
        name: string;
        address_line1: string | null;
        address_line2: string | null;
        channel: 'direct' | 'marketplace';
        notes: string | null;
        archived: boolean;
        contacts: Contact[];
    } | null;
    quotations?: {
        id: number;
        ulid: string;
        number: string;
        status: QuotationStatus;
        sales_rep: string;
        created_at: string;
    }[];
    quotedLines?: {
        id: number;
        quotation_id: number;
        quotation_ulid: string;
        quotation_number: string;
        quoted_at: string;
        part_number: string;
        description: string;
        quantity: number;
        unit_price: number;
    }[];
};

export default function CustomerForm({
    customer,
    quotations: history = [],
    quotedLines = [],
}: Props) {
    const { auth } = usePage().props;

    const form = useForm({
        code: customer?.code ?? '',
        name: customer?.name ?? '',
        address_line1: customer?.address_line1 ?? '',
        address_line2: customer?.address_line2 ?? '',
        channel: customer?.channel ?? 'direct',
        notes: customer?.notes ?? '',
        contacts: (
            customer?.contacts ?? [
                { salutation: 'mr', name: '', phone: '', email: '' },
            ]
        ).map((c) => ({
            ...c,
            phone: c.phone ?? '',
            email: c.email ?? '',
        })) as Contact[],
    });

    const setContact = (index: number, changes: Partial<Contact>) =>
        form.setData(
            'contacts',
            form.data.contacts.map((c, i) =>
                i === index ? { ...c, ...changes } : c,
            ),
        );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(
            customer ? customers.update(customer.ulid) : customers.store(),
            {
                preserveScroll: true,
                // New contacts get ids on save; pick them up so a second save updates rather than duplicates.
                onSuccess: (page) => {
                    const fresh = (page.props as unknown as Props).customer;

                    if (fresh) {
                        form.setData(
                            'contacts',
                            fresh.contacts.map((c) => ({
                                ...c,
                                phone: c.phone ?? '',
                                email: c.email ?? '',
                            })),
                        );
                    }
                },
            },
        );
    };

    const errors = form.errors as Record<string, string>;
    const title = customer ? customer.name : 'New customer';

    const details = (
        <form onSubmit={submit} className="grid gap-4">
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Company</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-[8rem_1fr]">
                    <FormField label="Code" htmlFor="code" error={errors.code}>
                        <Input
                            id="code"
                            value={form.data.code}
                            maxLength={5}
                            onChange={(e) =>
                                form.setData(
                                    'code',
                                    e.target.value.toUpperCase(),
                                )
                            }
                            className="font-mono uppercase"
                            placeholder="SPL"
                        />
                    </FormField>
                    <FormField label="Name" htmlFor="name" error={errors.name}>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                            placeholder="PT Contoh Industri"
                        />
                    </FormField>
                    <FormField
                        label="Address"
                        htmlFor="address_line1"
                        error={errors.address_line1}
                        className="sm:col-span-2"
                    >
                        <Input
                            id="address_line1"
                            value={form.data.address_line1}
                            onChange={(e) =>
                                form.setData('address_line1', e.target.value)
                            }
                            placeholder="Street, industrial estate"
                        />
                        <Input
                            value={form.data.address_line2}
                            onChange={(e) =>
                                form.setData('address_line2', e.target.value)
                            }
                            placeholder="District, city, postcode"
                        />
                    </FormField>
                    <FormField label="Channel" error={errors.channel}>
                        <Select
                            value={form.data.channel}
                            onValueChange={(value) =>
                                form.setData(
                                    'channel',
                                    value as 'direct' | 'marketplace',
                                )
                            }
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="direct">Direct</SelectItem>
                                <SelectItem value="marketplace">
                                    Marketplace
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </FormField>
                    <FormField
                        label="Notes"
                        htmlFor="notes"
                        error={errors.notes}
                    >
                        <Textarea
                            id="notes"
                            rows={2}
                            value={form.data.notes}
                            onChange={(e) =>
                                form.setData('notes', e.target.value)
                            }
                        />
                    </FormField>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle className="text-base">
                            Contact people
                        </CardTitle>
                        <CardDescription>
                            Shown on quotations as “Attn.”
                        </CardDescription>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={form.data.contacts.length >= 5}
                        onClick={() =>
                            form.setData('contacts', [
                                ...form.data.contacts,
                                {
                                    salutation: 'mr',
                                    name: '',
                                    phone: '',
                                    email: '',
                                },
                            ])
                        }
                    >
                        <Plus /> Add contact
                    </Button>
                </CardHeader>
                <CardContent className="space-y-3">
                    {form.data.contacts.map((contact, index) => (
                        <div
                            key={contact.id ?? `new-${index}`}
                            className="grid gap-2 rounded-lg border p-3 sm:grid-cols-[6rem_1fr_1fr_1fr_auto]"
                        >
                            <Select
                                value={contact.salutation}
                                onValueChange={(value) =>
                                    setContact(index, {
                                        salutation: value as 'mr' | 'ms',
                                    })
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="mr">Mr.</SelectItem>
                                    <SelectItem value="ms">Ms.</SelectItem>
                                </SelectContent>
                            </Select>
                            <Input
                                placeholder="Name"
                                value={contact.name}
                                onChange={(e) =>
                                    setContact(index, { name: e.target.value })
                                }
                                aria-invalid={
                                    !!errors[`contacts.${index}.name`]
                                }
                            />
                            <Input
                                placeholder="Phone"
                                value={contact.phone}
                                onChange={(e) =>
                                    setContact(index, { phone: e.target.value })
                                }
                            />
                            <Input
                                placeholder="Email"
                                type="email"
                                value={contact.email}
                                onChange={(e) =>
                                    setContact(index, { email: e.target.value })
                                }
                                aria-invalid={
                                    !!errors[`contacts.${index}.email`]
                                }
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="text-muted-foreground hover:text-destructive"
                                onClick={() =>
                                    form.setData(
                                        'contacts',
                                        form.data.contacts.filter(
                                            (_, i) => i !== index,
                                        ),
                                    )
                                }
                                aria-label="Remove contact"
                            >
                                <Trash2 />
                            </Button>
                            {(errors[`contacts.${index}.name`] ||
                                errors[`contacts.${index}.email`]) && (
                                <p className="text-sm text-red-600 sm:col-span-5">
                                    {errors[`contacts.${index}.name`] ??
                                        errors[`contacts.${index}.email`]}
                                </p>
                            )}
                        </div>
                    ))}
                    {form.data.contacts.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            No contacts.
                        </p>
                    )}
                </CardContent>
            </Card>

            <div className="flex justify-end gap-2">
                <Button variant="outline" asChild>
                    <Link href={customers.index()}>Back</Link>
                </Button>
                <Button
                    type="submit"
                    disabled={form.processing || customer?.archived}
                >
                    {form.processing && <Spinner />}
                    {customer ? 'Save customer' : 'Add customer'}
                </Button>
            </div>
        </form>
    );

    return (
        <>
            <Head title={title} />
            <PageHeader
                title={
                    <>
                        {title}
                        {customer?.archived && (
                            <span className="rounded-md bg-muted px-2 py-0.5 text-xs font-medium uppercase">
                                Archived
                            </span>
                        )}
                    </>
                }
                description={
                    customer
                        ? `Code ${customer.code}`
                        : 'The code appears in every document number, e.g. 2026/SPL/2710-001.'
                }
                note="customers"
                actions={
                    customer && (
                        <>
                            {!customer.archived && (
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={quotations.create({
                                            query: { customer: customer.ulid },
                                        })}
                                    >
                                        <FilePlus2 /> New quotation
                                    </Link>
                                </Button>
                            )}
                            {auth.can?.archiveRecords &&
                                (customer.archived ? (
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() =>
                                            router.post(
                                                customers.restore.url(
                                                    customer.ulid,
                                                ),
                                            )
                                        }
                                    >
                                        <ArchiveRestore /> Restore
                                    </Button>
                                ) : (
                                    <ConfirmDialog
                                        trigger={
                                            <Button size="sm" variant="outline">
                                                <Archive /> Archive
                                            </Button>
                                        }
                                        title={`Archive ${customer.name}?`}
                                        description="They disappear from pickers, but their quotations keep their full history. You can restore them later."
                                        confirmLabel="Archive"
                                        onConfirm={() =>
                                            router.delete(
                                                customers.destroy.url(
                                                    customer.ulid,
                                                ),
                                            )
                                        }
                                    />
                                ))}
                        </>
                    )
                }
            />

            {customer ? (
                <Tabs defaultValue="details" className="max-w-5xl">
                    <TabsList>
                        <TabsTrigger value="details">Details</TabsTrigger>
                        <TabsTrigger value="quotations">
                            Quotations ({history.length})
                        </TabsTrigger>
                        <TabsTrigger value="report">Quoted lines</TabsTrigger>
                    </TabsList>
                    <TabsContent value="details">{details}</TabsContent>
                    <TabsContent value="quotations">
                        <Card>
                            <CardContent>
                                {history.length ? (
                                    <ul className="divide-y">
                                        {history.map((q) => (
                                            <li
                                                key={q.id}
                                                className="flex flex-wrap items-center justify-between gap-2 py-2.5 text-sm"
                                            >
                                                <Link
                                                    href={quotations.show(
                                                        q.ulid,
                                                    )}
                                                    className="font-medium text-primary hover:underline"
                                                >
                                                    {q.number}
                                                </Link>
                                                <span className="text-muted-foreground">
                                                    {q.sales_rep} ·{' '}
                                                    {formatDate(q.created_at)}
                                                </span>
                                                <StatusBadge
                                                    kind="quotation"
                                                    status={q.status}
                                                />
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="py-6 text-center text-sm text-muted-foreground">
                                        No quotations yet.
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                    <TabsContent value="report">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Every line quoted to {customer.name}
                                </CardTitle>
                                <CardDescription>
                                    The original system's "customer report",
                                    handy when they ask for last time's price.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="text-left text-xs text-muted-foreground uppercase">
                                        <tr className="border-b">
                                            <th className="py-2 pr-3">Date</th>
                                            <th className="py-2 pr-3">
                                                Quotation
                                            </th>
                                            <th className="py-2 pr-3">Part</th>
                                            <th className="py-2 pr-3 text-right">
                                                Qty
                                            </th>
                                            <th className="py-2 text-right">
                                                Unit price
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {quotedLines.map((line) => (
                                            <tr key={line.id}>
                                                <td className="py-2 pr-3 whitespace-nowrap">
                                                    {formatDate(line.quoted_at)}
                                                </td>
                                                <td className="py-2 pr-3">
                                                    <Link
                                                        href={quotations.show(
                                                            line.quotation_ulid,
                                                        )}
                                                        className="text-primary hover:underline"
                                                    >
                                                        {line.quotation_number}
                                                    </Link>
                                                </td>
                                                <td className="py-2 pr-3">
                                                    <p className="font-medium">
                                                        {line.part_number}
                                                    </p>
                                                    <p className="line-clamp-1 text-xs text-muted-foreground">
                                                        {line.description}
                                                    </p>
                                                </td>
                                                <td className="py-2 pr-3 text-right tabular-nums">
                                                    {formatNumber(
                                                        line.quantity,
                                                    )}
                                                </td>
                                                <td className="py-2 text-right tabular-nums">
                                                    {formatIDR(line.unit_price)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                                {quotedLines.length === 0 && (
                                    <p className="py-6 text-center text-sm text-muted-foreground">
                                        Nothing quoted yet.
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            ) : (
                <div className="max-w-5xl">{details}</div>
            )}
        </>
    );
}

CustomerForm.layout = {
    breadcrumbs: [{ title: 'Customers', href: customers.index() }],
};
