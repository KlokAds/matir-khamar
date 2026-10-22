import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { currentLocale } from '@/lib/i18n';

/** Poisha (integer) to "৳ 1,23,450.00" with South-Asian digit grouping. */
export function taka(
    poisha: number | null | undefined,
    withSymbol = true,
): string {
    const value = Math.abs((poisha ?? 0) / 100);
    const sign = (poisha ?? 0) < 0 ? '−' : '';
    const text = value.toLocaleString('en-IN', {
        minimumFractionDigits: Number.isInteger(value) ? 0 : 2,
        maximumFractionDigits: 2,
    });

    return withSymbol ? `${sign}৳ ${text}` : `${sign}${text}`;
}

export function qty(value: number | null | undefined, unit = ''): string {
    const text = (value ?? 0).toLocaleString('en-IN', {
        maximumFractionDigits: 3,
    });

    return unit ? `${text} ${unit}` : text;
}

const months = [
    'জানু',
    'ফেব্রু',
    'মার্চ',
    'এপ্রিল',
    'মে',
    'জুন',
    'জুলাই',
    'আগস্ট',
    'সেপ্টে',
    'অক্টো',
    'নভে',
    'ডিসে',
];
const monthsEn = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
];
const short = () => (currentLocale() === 'en' ? monthsEn : months);

/** "2026-10-21" -> "21 অক্টো 2026" */
export function day(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const [y, m, d] = value.slice(0, 10).split('-').map(Number);

    return `${d} ${short()[m - 1]} ${y}`;
}

const monthsFull = [
    'জানুয়ারি',
    'ফেব্রুয়ারি',
    'মার্চ',
    'এপ্রিল',
    'মে',
    'জুন',
    'জুলাই',
    'আগস্ট',
    'সেপ্টেম্বর',
    'অক্টোবর',
    'নভেম্বর',
    'ডিসেম্বর',
];
const monthsFullEn = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

/** "2026-10" -> "অক্টোবর 2026" */
export function monthFull(ym: string): string {
    const [y, m] = ym.split('-').map(Number);

    const names = currentLocale() === 'en' ? monthsFullEn : monthsFull;

    return `${names[m - 1]} ${y}`;
}

export function monthLabel(ym: string): string {
    const [y, m] = ym.split('-').map(Number);

    return `${short()[m - 1]} ${String(y).slice(2)}`;
}

/** Feeding time by the clock: before 11 morning, before 3 pm noon, then afternoon. */
export function currentSession(): string {
    const hour = new Date().getHours();

    if (hour < 4 || hour >= 20) {
        return 'night';
    }

    return hour < 11 ? 'morning' : hour < 15 ? 'noon' : 'evening';
}

export function today(): string {
    const now = new Date();
    const offset = now.getTimezoneOffset() * 60000;

    return new Date(now.getTime() - offset).toISOString().slice(0, 10);
}

export type Abilities = Partial<
    Record<
        | 'view-reports'
        | 'post-entries'
        | 'post-daily'
        | 'manage-setup'
        | 'manage-users'
        | 'lock-periods'
        | 'reverse-entries'
        | 'view-audit'
        | 'manage-import'
        | 'manage-roles'
        | 'manage-update'
        | 'investor-view',
        boolean
    >
>;

export function useCan() {
    const page = usePage();

    return computed(
        () => (page.props.auth as unknown as { can: Abilities }).can ?? {},
    );
}

/** Indented label for the category tree in a native select. */
export function centerLabel(center: { name: string; depth: number }): string {
    return `${'   '.repeat(center.depth)}${center.depth > 0 ? '└ ' : ''}${center.name}`;
}

export type Center = {
    id: number;
    name: string;
    code: string;
    kind: string;
    holds_animals: boolean;
    depth: number;
    is_active: boolean;
};

export type Option = {
    id: number;
    name: string;
    code?: string;
    subtype?: string | null;
};

export type TreeNode = {
    id: number;
    code: string;
    name: string;
    kind: string;
    is_active: boolean;
    holds_animals: boolean;
    income: number;
    expense: number;
    profit: number;
    rolled: {
        income: number;
        expense: number;
        internal_income: number;
        internal_expense: number;
    };
    children: TreeNode[];
};
