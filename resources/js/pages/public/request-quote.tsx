import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, Plus, Send, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { FormField } from '@/components/form-field';
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
import { Textarea } from '@/components/ui/textarea';
import PublicLayout from '@/layouts/public-layout';
import inquiries from '@/routes/inquiries';
import quoteRequest from '@/routes/quote-request';

type Part = {
    id: number;
    part_number: string;
    description: string;
    uom: string;
};
type Item = { part_id: string; title: string; quantity: number; note: string };

const FREE_TEXT = 'other';

/**
 * The buyer's side of the inquiry flow. In production this is a quote list
 * built while browsing the public catalog, confirmed by email.
 */
export default function RequestQuote({
    parts,
    sent,
}: {
    parts: Part[];
    sent: { ulid: string; number: string } | null;
}) {
    const { auth } = usePage().props;

    const form = useForm({
        name: '',
        company: '',
        email: '',
        phone: '',
        city: '',
        message: '',
        website: '',
        items: [{ part_id: '', title: '', quantity: 1, note: '' }] as Item[],
    });

    const setItem = (index: number, changes: Partial<Item>) =>
        form.setData(
            'items',
            form.data.items.map((item, i) =>
                i === index ? { ...item, ...changes } : item,
            ),
        );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            items: data.items.map((item) => ({
                part_id:
                    item.part_id && item.part_id !== FREE_TEXT
                        ? Number(item.part_id)
                        : null,
                title:
                    item.part_id === FREE_TEXT || !item.part_id
                        ? item.title
                        : null,
                quantity: item.quantity,
                note: item.note || null,
            })),
        }));
        form.submit(quoteRequest.store(), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const errors = form.errors as Record<string, string>;

    return (
        <PublicLayout>
            <Head title="Request a quote" />
            <div className="mx-auto max-w-3xl space-y-6 px-4 py-10">
                <div className="space-y-2">
                    <span className="rounded-full bg-muted px-2.5 py-0.5 text-xs text-muted-foreground">
                        You're the buyer now
                    </span>
                    <h1 className="text-3xl font-semibold tracking-tight">
                        Request a quotation
                    </h1>
                    <p className="text-muted-foreground">
                        This is the public side of the inquiry flow. Send a
                        request, then open the Inquiries inbox to triage it as
                        the sales team.
                    </p>
                </div>

                {sent && (
                    <Card className="flex-row items-center gap-4 border-emerald-500/30 bg-emerald-500/5 p-5">
                        <CheckCircle2 className="size-8 shrink-0 text-emerald-600" />
                        <div className="flex-1">
                            <p className="font-medium">
                                Request {sent.number} sent.
                            </p>
                            <p className="text-sm text-muted-foreground">
                                It's waiting in the inbox as a new inquiry; the
                                sidebar badge has gone up by one.
                            </p>
                        </div>
                        {auth.user && (
                            <Button asChild>
                                <Link href={inquiries.show(sent.ulid)}>
                                    Open in inbox
                                </Link>
                            </Button>
                        )}
                    </Card>
                )}

                <form onSubmit={submit} className="grid gap-4">
                    <input
                        type="text"
                        name="website"
                        value={form.data.website}
                        onChange={(e) =>
                            form.setData('website', e.target.value)
                        }
                        className="hidden"
                        tabIndex={-1}
                        autoComplete="off"
                    />

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Your details
                            </CardTitle>
                            <CardDescription>
                                Use any made-up details; this is a demo.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <FormField
                                label="Name"
                                htmlFor="name"
                                error={errors.name}
                            >
                                <Input
                                    id="name"
                                    value={form.data.name}
                                    onChange={(e) =>
                                        form.setData('name', e.target.value)
                                    }
                                    placeholder="Anonymous Platypus"
                                />
                            </FormField>
                            <FormField
                                label="Company"
                                htmlFor="company"
                                error={errors.company}
                            >
                                <Input
                                    id="company"
                                    value={form.data.company}
                                    onChange={(e) =>
                                        form.setData('company', e.target.value)
                                    }
                                    placeholder="PT Contoh Pangan"
                                />
                            </FormField>
                            <FormField
                                label="Email"
                                htmlFor="email"
                                error={errors.email}
                            >
                                <Input
                                    id="email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(e) =>
                                        form.setData('email', e.target.value)
                                    }
                                    placeholder="dewi@contoh.example"
                                />
                            </FormField>
                            <FormField
                                label="Phone"
                                htmlFor="phone"
                                error={errors.phone}
                            >
                                <Input
                                    id="phone"
                                    value={form.data.phone}
                                    onChange={(e) =>
                                        form.setData('phone', e.target.value)
                                    }
                                />
                            </FormField>
                            <FormField
                                label="City"
                                htmlFor="city"
                                error={errors.city}
                            >
                                <Input
                                    id="city"
                                    value={form.data.city}
                                    onChange={(e) =>
                                        form.setData('city', e.target.value)
                                    }
                                />
                            </FormField>
                            <FormField
                                label="Message"
                                htmlFor="message"
                                error={errors.message}
                                className="sm:col-span-2"
                            >
                                <Textarea
                                    id="message"
                                    rows={3}
                                    value={form.data.message}
                                    onChange={(e) =>
                                        form.setData('message', e.target.value)
                                    }
                                    placeholder="What's the application? Any deadline?"
                                />
                            </FormField>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle className="text-base">
                                What you need
                            </CardTitle>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={form.data.items.length >= 10}
                                onClick={() =>
                                    form.setData('items', [
                                        ...form.data.items,
                                        {
                                            part_id: '',
                                            title: '',
                                            quantity: 1,
                                            note: '',
                                        },
                                    ])
                                }
                            >
                                <Plus /> Add item
                            </Button>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {form.data.items.map((item, index) => (
                                <div
                                    key={index}
                                    className="grid gap-2 rounded-lg border p-3 sm:grid-cols-[1fr_6rem_auto]"
                                >
                                    <div className="space-y-2">
                                        <Select
                                            value={item.part_id}
                                            onValueChange={(value) =>
                                                setItem(index, {
                                                    part_id: value,
                                                })
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Choose a catalog item" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {parts.map((part) => (
                                                    <SelectItem
                                                        key={part.id}
                                                        value={String(part.id)}
                                                    >
                                                        <span className="font-mono text-xs">
                                                            {part.part_number}
                                                        </span>{' '}
                                                        {part.description}
                                                    </SelectItem>
                                                ))}
                                                <SelectItem value={FREE_TEXT}>
                                                    Something else (describe it)
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {item.part_id === FREE_TEXT && (
                                            <Input
                                                value={item.title}
                                                onChange={(e) =>
                                                    setItem(index, {
                                                        title: e.target.value,
                                                    })
                                                }
                                                placeholder="e.g. Stainless side guide rail, 3 m"
                                            />
                                        )}
                                        <Input
                                            value={item.note}
                                            onChange={(e) =>
                                                setItem(index, {
                                                    note: e.target.value,
                                                })
                                            }
                                            placeholder="Note (optional)"
                                        />
                                    </div>
                                    <Input
                                        type="number"
                                        min={1}
                                        value={item.quantity}
                                        onChange={(e) =>
                                            setItem(index, {
                                                quantity: Number(
                                                    e.target.value,
                                                ),
                                            })
                                        }
                                        aria-label="Quantity"
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        disabled={form.data.items.length === 1}
                                        onClick={() =>
                                            form.setData(
                                                'items',
                                                form.data.items.filter(
                                                    (_, i) => i !== index,
                                                ),
                                            )
                                        }
                                        aria-label="Remove item"
                                    >
                                        <Trash2 />
                                    </Button>
                                    {(errors[`items.${index}.title`] ||
                                        errors[`items.${index}.part_id`] ||
                                        errors[`items.${index}.quantity`]) && (
                                        <p className="text-sm text-red-600 sm:col-span-3">
                                            {errors[`items.${index}.title`]
                                                ? 'Choose an item or describe it.'
                                                : (errors[
                                                      `items.${index}.part_id`
                                                  ] ??
                                                  errors[
                                                      `items.${index}.quantity`
                                                  ])}
                                        </p>
                                    )}
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <div className="flex justify-end">
                        <Button
                            type="submit"
                            size="lg"
                            disabled={form.processing}
                        >
                            {form.processing ? <Spinner /> : <Send />} Send
                            request
                        </Button>
                    </div>
                </form>
            </div>
        </PublicLayout>
    );
}
