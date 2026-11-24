import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    Check,
    FilePlus2,
    Lightbulb,
    Link2,
    Mail,
    MapPin,
    Phone,
    UserCheck,
    X,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { FormField } from '@/components/form-field';
import { LogTimeline } from '@/components/log-timeline';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime, formatIDR, formatNumber } from '@/lib/format';
import inquiries from '@/routes/inquiries';
import quotations from '@/routes/quotations';
import type { Activity, CustomerOption, InquiryStatus, Role } from '@/types';

type Props = {
    inquiry: {
        id: number;
        ulid: string;
        number: string;
        name: string;
        company: string | null;
        email: string;
        phone: string | null;
        city: string | null;
        message: string | null;
        source: string;
        status: InquiryStatus;
        rejection_reason: string | null;
        decided_at: string | null;
        created_at: string;
        assignee: { id: number; name: string } | null;
        decider: string | null;
        customer: {
            id: number;
            ulid: string;
            name: string;
            code: string;
        } | null;
        contact: { id: number; name: string } | null;
        quotation: {
            id: number;
            ulid: string;
            number: string;
            status: string;
        } | null;
        items: {
            id: number;
            title: string;
            part_number: string | null;
            quantity: number;
            unit: string | null;
            note: string | null;
            part: {
                id: number;
                part_number: string;
                list_price: number;
                stock_qty: number;
                uom: string;
                discontinued: boolean;
            } | null;
        }[];
    };
    activities: Activity[];
    suggestedCustomerId: number | null;
    salesReps: { id: number; name: string; role: Role }[];
    customers: CustomerOption[];
    can: { triage: boolean; work: boolean };
};

export default function InquiryShow({
    inquiry,
    activities,
    suggestedCustomerId,
    salesReps,
    customers,
    can,
}: Props) {
    const [rejecting, setRejecting] = useState(false);
    const open = inquiry.status === 'new' || inquiry.status === 'in_review';
    const matched = inquiry.items.filter(
        (item) => item.part && !item.part.discontinued,
    ).length;
    const suggested = customers.find((c) => c.id === suggestedCustomerId);

    return (
        <>
            <Head title={inquiry.number} />
            <PageHeader
                title={
                    <>
                        {inquiry.number}
                        <StatusBadge kind="inquiry" status={inquiry.status} />
                    </>
                }
                description={`Received ${formatDateTime(inquiry.created_at)} via ${inquiry.source === 'web' ? 'the website' : inquiry.source}`}
                note="inquiry"
            />

            <div className="grid gap-4 lg:grid-cols-3">
                <div className="space-y-4 lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                {inquiry.name}
                                {inquiry.company && (
                                    <span className="font-normal text-muted-foreground">
                                        {' '}
                                        · {inquiry.company}
                                    </span>
                                )}
                            </CardTitle>
                            <CardDescription className="flex flex-wrap gap-x-4 gap-y-1">
                                <span className="inline-flex items-center gap-1">
                                    <Mail className="size-3.5" />{' '}
                                    {inquiry.email}
                                </span>
                                {inquiry.phone && (
                                    <span className="inline-flex items-center gap-1">
                                        <Phone className="size-3.5" />{' '}
                                        {inquiry.phone}
                                    </span>
                                )}
                                {inquiry.city && (
                                    <span className="inline-flex items-center gap-1">
                                        <MapPin className="size-3.5" />{' '}
                                        {inquiry.city}
                                    </span>
                                )}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {inquiry.message && (
                                <blockquote className="border-l-2 pl-3 text-sm text-muted-foreground italic">
                                    {inquiry.message}
                                </blockquote>
                            )}
                            <div className="overflow-x-auto rounded-lg border">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted/40 text-left text-xs text-muted-foreground uppercase">
                                        <tr>
                                            <th className="px-3 py-2">
                                                Requested
                                            </th>
                                            <th className="px-3 py-2 text-right">
                                                Qty
                                            </th>
                                            <th className="px-3 py-2">
                                                Matched part
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {inquiry.items.map((item) => (
                                            <tr
                                                key={item.id}
                                                className="align-top"
                                            >
                                                <td className="px-3 py-2">
                                                    <p>{item.title}</p>
                                                    {item.note && (
                                                        <p className="text-xs text-muted-foreground">
                                                            “{item.note}”
                                                        </p>
                                                    )}
                                                </td>
                                                <td className="px-3 py-2 text-right whitespace-nowrap tabular-nums">
                                                    {formatNumber(
                                                        item.quantity,
                                                    )}{' '}
                                                    {item.unit}
                                                </td>
                                                <td className="px-3 py-2">
                                                    {item.part ? (
                                                        <div className="text-xs">
                                                            <p className="flex items-center gap-1 font-medium text-foreground">
                                                                <Check className="size-3.5 text-emerald-600" />{' '}
                                                                {
                                                                    item.part
                                                                        .part_number
                                                                }
                                                            </p>
                                                            <p className="text-muted-foreground">
                                                                {formatIDR(
                                                                    item.part
                                                                        .list_price,
                                                                )}{' '}
                                                                ·{' '}
                                                                {
                                                                    item.part
                                                                        .stock_qty
                                                                }{' '}
                                                                {item.part.uom}{' '}
                                                                in stock
                                                            </p>
                                                        </div>
                                                    ) : (
                                                        <span className="text-xs text-muted-foreground">
                                                            Free text, needs a
                                                            manual quote
                                                        </span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Activity
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <LogTimeline activities={activities} />
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-4">
                    <Card className="gap-3 border-primary/30">
                        <CardHeader>
                            <CardTitle className="text-base">Triage</CardTitle>
                            <CardDescription>
                                New → assign a rep → approve or reject → create
                                the quotation.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <AssignForm
                                inquiryUlid={inquiry.ulid}
                                current={inquiry.assignee?.id ?? null}
                                salesReps={salesReps}
                                disabled={!can.triage || !open}
                            />
                            {!can.triage && open && (
                                <p className="text-xs text-muted-foreground">
                                    Assigning, approving and rejecting are admin
                                    decisions. Switch to Admin to try them.
                                </p>
                            )}

                            {inquiry.status === 'in_review' && can.triage && (
                                <div className="grid grid-cols-2 gap-2">
                                    <Button
                                        onClick={() =>
                                            router.post(
                                                inquiries.approve.url(
                                                    inquiry.ulid,
                                                ),
                                                {},
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <Check /> Approve
                                    </Button>
                                    <Button
                                        variant="outline"
                                        onClick={() => setRejecting(true)}
                                    >
                                        <X /> Reject
                                    </Button>
                                </div>
                            )}
                            {inquiry.status === 'new' && can.triage && (
                                <Button
                                    variant="outline"
                                    className="w-full"
                                    onClick={() => setRejecting(true)}
                                >
                                    <X /> Reject as spam or out of scope
                                </Button>
                            )}

                            {inquiry.status === 'rejected' && (
                                <p className="rounded-lg bg-red-500/5 p-3 text-sm">
                                    Rejected by {inquiry.decider}:{' '}
                                    <span className="text-muted-foreground">
                                        {inquiry.rejection_reason}
                                    </span>
                                </p>
                            )}
                            {inquiry.status === 'approved' && (
                                <p className="text-sm text-muted-foreground">
                                    Approved by {inquiry.decider}. Link a
                                    customer, then draft the quotation.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="gap-3">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Link2 className="size-4" /> Customer
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {inquiry.customer ? (
                                <p className="text-sm">
                                    <span className="font-medium">
                                        {inquiry.customer.name}
                                    </span>
                                    {inquiry.contact && (
                                        <span className="block text-muted-foreground">
                                            {inquiry.contact.name}
                                        </span>
                                    )}
                                </p>
                            ) : suggested ? (
                                <p className="flex gap-2 rounded-lg bg-amber-500/10 p-2.5 text-xs">
                                    <Lightbulb className="size-4 shrink-0 text-amber-600" />
                                    Their email domain matches a contact at{' '}
                                    {suggested.name}.
                                </p>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    Not linked to a customer yet.
                                </p>
                            )}
                            {inquiry.status !== 'quoted' &&
                                inquiry.status !== 'rejected' && (
                                    <LinkCustomerForm
                                        inquiryUlid={inquiry.ulid}
                                        customers={customers}
                                        initialCustomer={
                                            inquiry.customer?.id ??
                                            suggestedCustomerId
                                        }
                                        disabled={!can.work}
                                    />
                                )}
                        </CardContent>
                    </Card>

                    <Card className="gap-3">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <FilePlus2 className="size-4" /> Quotation
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {inquiry.quotation ? (
                                <Link
                                    href={quotations.show(
                                        inquiry.quotation.ulid,
                                    )}
                                    className="flex items-center justify-between rounded-lg border p-3 text-sm hover:bg-muted/50"
                                >
                                    <span className="font-medium">
                                        {inquiry.quotation.number}
                                    </span>
                                    <StatusBadge
                                        kind="quotation"
                                        status={inquiry.quotation.status}
                                    />
                                </Link>
                            ) : (
                                <>
                                    <Button
                                        className="w-full"
                                        disabled={
                                            inquiry.status !== 'approved' ||
                                            !inquiry.customer ||
                                            !can.work
                                        }
                                        onClick={() =>
                                            router.post(
                                                inquiries.convert.url(
                                                    inquiry.ulid,
                                                ),
                                            )
                                        }
                                    >
                                        <UserCheck /> Create quotation (
                                        {matched} matched{' '}
                                        {matched === 1 ? 'item' : 'items'})
                                    </Button>
                                    <p className="text-xs text-muted-foreground">
                                        {inquiry.status !== 'approved'
                                            ? 'Available once the inquiry is approved.'
                                            : !inquiry.customer
                                              ? 'Link a customer first.'
                                              : 'Drafts a quotation with the matched parts at list price.'}
                                    </p>
                                </>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            <RejectDialog
                inquiryUlid={inquiry.ulid}
                open={rejecting}
                onClose={() => setRejecting(false)}
            />
        </>
    );
}

function AssignForm({
    inquiryUlid,
    current,
    salesReps,
    disabled,
}: {
    inquiryUlid: string;
    current: number | null;
    salesReps: Props['salesReps'];
    disabled: boolean;
}) {
    const form = useForm({ user_id: current ? String(current) : '' });

    return (
        <form
            onSubmit={(event: FormEvent) => {
                event.preventDefault();
                form.submit(inquiries.assign(inquiryUlid), {
                    preserveScroll: true,
                });
            }}
            className="flex gap-2"
        >
            <Select
                disabled={disabled}
                value={form.data.user_id}
                onValueChange={(value) => form.setData('user_id', value)}
            >
                <SelectTrigger className="w-full">
                    <SelectValue placeholder="Assign a sales rep" />
                </SelectTrigger>
                <SelectContent>
                    {salesReps.map((rep) => (
                        <SelectItem key={rep.id} value={String(rep.id)}>
                            {rep.name}{' '}
                            <span className="text-muted-foreground capitalize">
                                · {rep.role}
                            </span>
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <Button
                type="submit"
                variant="secondary"
                disabled={
                    disabled ||
                    !form.data.user_id ||
                    form.data.user_id === String(current) ||
                    form.processing
                }
            >
                {form.processing ? <Spinner /> : 'Assign'}
            </Button>
        </form>
    );
}

function LinkCustomerForm({
    inquiryUlid,
    customers,
    initialCustomer,
    disabled,
}: {
    inquiryUlid: string;
    customers: CustomerOption[];
    initialCustomer: number | null;
    disabled: boolean;
}) {
    const form = useForm<{
        customer_id: number | null;
        contact_id: number | null;
    }>({ customer_id: initialCustomer, contact_id: null });
    const customer = customers.find((c) => c.id === form.data.customer_id);

    return (
        <form
            onSubmit={(event: FormEvent) => {
                event.preventDefault();
                form.submit(inquiries.linkCustomer(inquiryUlid), {
                    preserveScroll: true,
                });
            }}
            className="space-y-2"
        >
            <Select
                disabled={disabled}
                value={
                    form.data.customer_id
                        ? String(form.data.customer_id)
                        : undefined
                }
                onValueChange={(value) =>
                    form.setData({
                        customer_id: Number(value),
                        contact_id: null,
                    })
                }
            >
                <SelectTrigger className="w-full">
                    <SelectValue placeholder="Choose a customer" />
                </SelectTrigger>
                <SelectContent>
                    {customers.map((c) => (
                        <SelectItem key={c.id} value={String(c.id)}>
                            {c.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            {customer && customer.contacts.length > 0 && (
                <Select
                    disabled={disabled}
                    value={
                        form.data.contact_id
                            ? String(form.data.contact_id)
                            : undefined
                    }
                    onValueChange={(value) =>
                        form.setData('contact_id', Number(value))
                    }
                >
                    <SelectTrigger className="w-full">
                        <SelectValue placeholder="Contact person (optional)" />
                    </SelectTrigger>
                    <SelectContent>
                        {customer.contacts.map((contact) => (
                            <SelectItem
                                key={contact.id}
                                value={String(contact.id)}
                            >
                                {contact.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            )}
            <Button
                type="submit"
                variant="outline"
                size="sm"
                className="w-full"
                disabled={disabled || !form.data.customer_id || form.processing}
            >
                Link customer
            </Button>
            {disabled && (
                <p className="text-xs text-muted-foreground">
                    Only the assigned rep or an admin can link a customer.
                </p>
            )}
        </form>
    );
}

function RejectDialog({
    inquiryUlid,
    open,
    onClose,
}: {
    inquiryUlid: string;
    open: boolean;
    onClose: () => void;
}) {
    const form = useForm({ reason: '' });

    return (
        <Dialog open={open} onOpenChange={(value) => !value && onClose()}>
            <DialogContent>
                <form
                    onSubmit={(event: FormEvent) => {
                        event.preventDefault();
                        form.submit(inquiries.reject(inquiryUlid), {
                            preserveScroll: true,
                            onSuccess: onClose,
                        });
                    }}
                    className="grid gap-4"
                >
                    <DialogHeader>
                        <DialogTitle>Reject inquiry</DialogTitle>
                        <DialogDescription>
                            A reason is required and stays on the record.
                        </DialogDescription>
                    </DialogHeader>
                    <FormField
                        label="Reason"
                        htmlFor="reason"
                        error={form.errors.reason}
                    >
                        <Textarea
                            id="reason"
                            rows={3}
                            value={form.data.reason}
                            onChange={(e) =>
                                form.setData('reason', e.target.value)
                            }
                            placeholder="e.g. Duplicate of an earlier inquiry."
                            autoFocus
                        />
                    </FormField>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={form.processing}
                        >
                            Reject
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

InquiryShow.layout = {
    breadcrumbs: [{ title: 'Inquiries', href: inquiries.index() }],
};
