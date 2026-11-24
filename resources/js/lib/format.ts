const TIME_ZONE = 'Asia/Jakarta';

const idr = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

const number = new Intl.NumberFormat('id-ID');

const date = new Intl.DateTimeFormat('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    timeZone: TIME_ZONE,
});

const dateTime = new Intl.DateTimeFormat('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    timeZone: TIME_ZONE,
});

const relative = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });

/** Rp 1.234.567, as written in Indonesia. */
export function formatIDR(amount: number): string {
    return idr.format(amount).replace(/ /g, ' ');
}

export function formatNumber(value: number): string {
    return number.format(value);
}

export function formatDate(iso: string | null | undefined): string {
    return iso ? date.format(new Date(iso)) : '—';
}

export function formatDateTime(iso: string | null | undefined): string {
    return iso ? dateTime.format(new Date(iso)) : '—';
}

export function timeAgo(iso: string): string {
    const seconds = (new Date(iso).getTime() - Date.now()) / 1000;
    const units: [Intl.RelativeTimeFormatUnit, number][] = [
        ['year', 31536000],
        ['month', 2592000],
        ['week', 604800],
        ['day', 86400],
        ['hour', 3600],
        ['minute', 60],
    ];

    for (const [unit, size] of units) {
        if (Math.abs(seconds) >= size) {
            return relative.format(Math.round(seconds / size), unit);
        }
    }

    return 'just now';
}
