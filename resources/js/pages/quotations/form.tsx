import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FormField } from '@/components/form-field';
import { MoneyInput } from '@/components/money-input';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import quotations from '@/routes/quotations';
import type { CustomerOption, Role } from '@/types';

type Terms = {
    customer_id: number | null;
    contact_id: number | null;
    sales_user_id: number | null;
    delivery_point: string;
    discount_pct: number;
    ppn_rate: number;
    lead_time_value: number;
    lead_time_unit: string;
    validity_value: number;
    validity_unit: string;
    payment_method: string;
    payment_terms_days: number;
    dp_amount: number;
    notes: string;
};

type Props = {
    customers: CustomerOption[];
    salesReps: { id: number; name: string; role: Role }[];
    deliveryPoints: string[];
    ppnRate: number;
    preselectedCustomer: number | null;
    quotation: (Terms & { id: number; ulid: string; number: string }) | null;
};

const units = ['days', 'weeks', 'months'];

export default function QuotationForm({
    customers,
    salesReps,
    deliveryPoints,
    ppnRate,
    preselectedCustomer,
    quotation,
}: Props) {
    const { auth } = usePage().props;
    const isAdmin = auth.user?.role === 'admin';

    const form = useForm<Terms>({
        customer_id: quotation?.customer_id ?? preselectedCustomer,
        contact_id: quotation?.contact_id ?? null,
        sales_user_id: quotation?.sales_user_id ?? auth.user?.id ?? null,
        delivery_point: quotation?.delivery_point ?? deliveryPoints[0],
        discount_pct: quotation?.discount_pct ?? 0,
        ppn_rate: quotation?.ppn_rate ?? ppnRate,
        lead_time_value: quotation?.lead_time_value ?? 2,
        lead_time_unit: quotation?.lead_time_unit ?? 'weeks',
        validity_value: quotation?.validity_value ?? 14,
        validity_unit: quotation?.validity_unit ?? 'days',
        payment_method: quotation?.payment_method ?? 'invoice',
        payment_terms_days: quotation?.payment_terms_days ?? 30,
        dp_amount: quotation?.dp_amount ?? 0,
        notes: quotation?.notes ?? '',
    });

    const customer = customers.find((c) => c.id === form.data.customer_id);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (quotation) {
            form.transform(({ customer_id: _customer, ...data }) => data);
            form.submit(quotations.update(quotation.ulid));
        } else {
            form.submit(quotations.store());
        }
    };

    const title = quotation ? `Edit ${quotation.number}` : 'New quotation';

    return (
        <>
            <Head title={title} />
            <PageHeader
                title={title}
                description={
                    quotation
                        ? 'Terms can change until the customer sends a purchase order.'
                        : 'Set the terms first; you add line items on the next screen.'
                }
                note="quotationForm"
            />

            <form onSubmit={submit} className="grid max-w-4xl gap-4">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Customer</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <FormField
                            label="Customer"
                            error={form.errors.customer_id}
                            hint={
                                quotation
                                    ? 'The customer code is part of the number, so it can’t change.'
                                    : undefined
                            }
                        >
                            <Select
                                disabled={!!quotation}
                                value={
                                    form.data.customer_id
                                        ? String(form.data.customer_id)
                                        : undefined
                                }
                                onValueChange={(value) => {
                                    form.setData((data) => ({
                                        ...data,
                                        customer_id: Number(value),
                                        contact_id: null,
                                    }));
                                }}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="Choose a customer" />
                                </SelectTrigger>
                                <SelectContent>
                                    {customers.map((c) => (
                                        <SelectItem
                                            key={c.id}
                                            value={String(c.id)}
                                        >
                                            {c.name}{' '}
                                            <span className="text-muted-foreground">
                                                ({c.code})
                                            </span>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </FormField>
                        <FormField
                            label="Contact person"
                            error={form.errors.contact_id}
                        >
                            <Select
                                disabled={!customer}
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
                                    <SelectValue
                                        placeholder={
                                            customer
                                                ? 'Choose a contact'
                                                : 'Choose a customer first'
                                        }
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {customer?.contacts.map((contact) => (
                                        <SelectItem
                                            key={contact.id}
                                            value={String(contact.id)}
                                        >
                                            {contact.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </FormField>
                        <FormField
                            label="Delivery point (franco)"
                            error={form.errors.delivery_point}
                        >
                            <Select
                                value={form.data.delivery_point}
                                onValueChange={(value) =>
                                    form.setData('delivery_point', value)
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {deliveryPoints.map((point) => (
                                        <SelectItem key={point} value={point}>
                                            {point}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </FormField>
                        <FormField
                            label="Sales rep"
                            error={form.errors.sales_user_id}
                            hint={
                                isAdmin
                                    ? undefined
                                    : 'Sales reps write quotations for themselves.'
                            }
                        >
                            <Select
                                disabled={!isAdmin}
                                value={
                                    form.data.sales_user_id
                                        ? String(form.data.sales_user_id)
                                        : undefined
                                }
                                onValueChange={(value) =>
                                    form.setData('sales_user_id', Number(value))
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {salesReps.map((rep) => (
                                        <SelectItem
                                            key={rep.id}
                                            value={String(rep.id)}
                                        >
                                            {rep.name}{' '}
                                            <span className="text-muted-foreground capitalize">
                                                · {rep.role}
                                            </span>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </FormField>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Pricing & terms
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <FormField
                            label="Discount (%)"
                            htmlFor="discount"
                            error={form.errors.discount_pct}
                        >
                            <Input
                                id="discount"
                                type="number"
                                min={0}
                                max={30}
                                step={0.5}
                                value={form.data.discount_pct}
                                onChange={(e) =>
                                    form.setData(
                                        'discount_pct',
                                        Number(e.target.value),
                                    )
                                }
                            />
                        </FormField>
                        <FormField
                            label="PPN (VAT)"
                            error={form.errors.ppn_rate}
                        >
                            <Select
                                value={String(form.data.ppn_rate)}
                                onValueChange={(value) =>
                                    form.setData('ppn_rate', Number(value))
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={String(ppnRate)}>
                                        {ppnRate}%
                                    </SelectItem>
                                    <SelectItem value="0">No PPN</SelectItem>
                                </SelectContent>
                            </Select>
                        </FormField>
                        <FormField
                            label="Down payment"
                            error={form.errors.dp_amount}
                            hint="Leave empty for none."
                        >
                            <MoneyInput
                                value={form.data.dp_amount}
                                onValueChange={(value) =>
                                    form.setData('dp_amount', value)
                                }
                            />
                        </FormField>
                        <FormField
                            label="Lead time"
                            error={form.errors.lead_time_value}
                        >
                            <div className="flex gap-2">
                                <Input
                                    type="number"
                                    min={1}
                                    max={52}
                                    value={form.data.lead_time_value}
                                    onChange={(e) =>
                                        form.setData(
                                            'lead_time_value',
                                            Number(e.target.value),
                                        )
                                    }
                                />
                                <UnitSelect
                                    value={form.data.lead_time_unit}
                                    onChange={(value) =>
                                        form.setData('lead_time_unit', value)
                                    }
                                />
                            </div>
                        </FormField>
                        <FormField
                            label="Valid for"
                            error={form.errors.validity_value}
                        >
                            <div className="flex gap-2">
                                <Input
                                    type="number"
                                    min={1}
                                    max={90}
                                    value={form.data.validity_value}
                                    onChange={(e) =>
                                        form.setData(
                                            'validity_value',
                                            Number(e.target.value),
                                        )
                                    }
                                />
                                <UnitSelect
                                    value={form.data.validity_unit}
                                    onChange={(value) =>
                                        form.setData('validity_unit', value)
                                    }
                                />
                            </div>
                        </FormField>
                        <FormField
                            label="Payment"
                            error={
                                form.errors.payment_method ??
                                form.errors.payment_terms_days
                            }
                        >
                            <div className="flex gap-2">
                                <Select
                                    value={form.data.payment_method}
                                    onValueChange={(value) =>
                                        form.setData('payment_method', value)
                                    }
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="invoice">
                                            Invoice
                                        </SelectItem>
                                        <SelectItem value="cod">
                                            Cash on delivery
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {form.data.payment_method === 'invoice' && (
                                    <div className="relative w-28 shrink-0">
                                        <Input
                                            type="number"
                                            min={0}
                                            max={120}
                                            value={form.data.payment_terms_days}
                                            onChange={(e) =>
                                                form.setData(
                                                    'payment_terms_days',
                                                    Number(e.target.value),
                                                )
                                            }
                                            className="pr-11"
                                        />
                                        <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-muted-foreground">
                                            days
                                        </span>
                                    </div>
                                )}
                            </div>
                        </FormField>
                        <FormField
                            label="Notes"
                            htmlFor="notes"
                            error={form.errors.notes}
                            className="sm:col-span-2 lg:col-span-3"
                        >
                            <Textarea
                                id="notes"
                                rows={3}
                                value={form.data.notes}
                                onChange={(e) =>
                                    form.setData('notes', e.target.value)
                                }
                                placeholder="Printed on the quotation, e.g. “Price includes delivery to site.”"
                            />
                        </FormField>
                    </CardContent>
                </Card>

                <div className="flex justify-end gap-2">
                    <Button variant="outline" asChild>
                        <Link
                            href={
                                quotation
                                    ? quotations.show(quotation.ulid)
                                    : quotations.index()
                            }
                        >
                            Cancel
                        </Link>
                    </Button>
                    <Button type="submit" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        {quotation ? 'Save terms' : 'Create & add items'}
                    </Button>
                </div>
            </form>
        </>
    );
}

function UnitSelect({
    value,
    onChange,
}: {
    value: string;
    onChange: (value: string) => void;
}) {
    return (
        <Select value={value} onValueChange={onChange}>
            <SelectTrigger className="w-32 shrink-0">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                {units.map((unit) => (
                    <SelectItem key={unit} value={unit} className="capitalize">
                        {unit}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

QuotationForm.layout = {
    breadcrumbs: [{ title: 'Quotations', href: quotations.index() }],
};
