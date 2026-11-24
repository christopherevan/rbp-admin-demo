import type {
    DescriptionStatus,
    InquiryStatus,
    QuotationStatus,
} from '@/types';

type StatusStyle = { label: string; className: string };

const tone = {
    red: 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/30',
    amber: 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/30',
    slate: 'bg-slate-100 text-slate-700 ring-slate-500/20 dark:bg-slate-500/10 dark:text-slate-300 dark:ring-slate-400/30',
    violet: 'bg-violet-50 text-violet-700 ring-violet-600/20 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-500/30',
    green: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/30',
    blue: 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/30',
    dark: 'bg-zinc-900 text-zinc-50 ring-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 dark:ring-zinc-100',
} as const;

/** The original system's colour coding, kept so its users would feel at home. */
export const quotationStatuses: Record<QuotationStatus, StatusStyle> = {
    not_submitted: { label: 'Not submitted', className: tone.red },
    waiting_customer: { label: 'Waiting customer', className: tone.slate },
    on_progress: { label: 'On progress', className: tone.amber },
    waiting_payment: { label: 'Waiting payment', className: tone.violet },
    completed: { label: 'Completed', className: tone.green },
    cancelled: { label: 'Cancelled', className: tone.dark },
};

export const inquiryStatuses: Record<InquiryStatus, StatusStyle> = {
    new: { label: 'New', className: tone.blue },
    in_review: { label: 'In review', className: tone.amber },
    approved: { label: 'Approved', className: tone.green },
    quoted: { label: 'Quoted', className: tone.violet },
    rejected: { label: 'Rejected', className: tone.red },
};

export const descriptionStatuses: Record<DescriptionStatus, StatusStyle> = {
    pending: { label: 'Pending review', className: tone.amber },
    approved: { label: 'Approved', className: tone.green },
    rejected: { label: 'Rejected', className: tone.red },
};

export const documentStatuses: Record<string, StatusStyle> = {
    pending: { label: 'Unpaid', className: tone.amber },
    paid: { label: 'Paid', className: tone.green },
    on_delivery: { label: 'On delivery', className: tone.blue },
    completed: { label: 'Delivered', className: tone.green },
};
