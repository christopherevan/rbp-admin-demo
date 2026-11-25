import type { Role } from '@/types/auth';

export type QuotationStatus =
    | 'not_submitted'
    | 'waiting_customer'
    | 'on_progress'
    | 'waiting_payment'
    | 'completed'
    | 'cancelled';

export type InquiryStatus =
    | 'new'
    | 'in_review'
    | 'approved'
    | 'quoted'
    | 'rejected';

export type DescriptionStatus = 'pending' | 'approved' | 'rejected';

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type TableFilters = {
    q?: string | null;
    sort?: string | null;
    per_page?: string | number | null;
    [key: string]: unknown;
};

export type Activity = {
    id: number;
    action: string;
    description: string;
    user: string | null;
    created_at: string;
};

export type CustomerOption = {
    id: number;
    code: string;
    name: string;
    contacts: { id: number; name: string }[];
};

export type PartOption = {
    id: number;
    part_number: string;
    description: string;
    uom: string;
    list_price: number;
    stocked: boolean;
    stock_qty: number;
};

export type DemoProps = {
    inSandbox: boolean;
    sandboxStartedAt: string | null;
    idleMinutes: number;
    tour: boolean;
    personas: Record<Role, string>;
    sourceUrl: string | null;
    portfolioUrl: string | null;
    turnstileSiteKey: string | null;
};
