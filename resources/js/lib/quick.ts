import {
    AlarmClock,
    Beef,
    CalendarHeart,
    ChartColumnBig,
    CloudOff,
    HandCoins,
    LayoutGrid,
    Layers,
    LifeBuoy,
    Milk,
    NotebookPen,
    Package,
    Settings2,
    Sprout,
    Truck,
    Wallet,
    Wheat,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import type { Abilities } from '@/lib/khamar';

type Ability = keyof Abilities;

/**
 * What the ＋ button offers, and the pages the search box knows about. Each carries the
 * ability it needs, so nobody is offered a page that would refuse them.
 */
export type QuickAction = {
    title: string;
    hint: string;
    href: string;
    icon: LucideIcon;
    ability: Ability | null;
    tone: string;
};

export const quickActions: QuickAction[] = [
    {
        title: 'দুধ লিখুন',
        hint: 'সকাল · দুপুর · বিকাল · রাত',
        href: '/milk',
        icon: Milk,
        ability: 'post-daily',
        tone: 'text-pond',
    },
    {
        title: 'খাবার দিন',
        hint: 'কোন ক্যাটাগরিতে কত কেজি',
        href: '/stock/move?mode=issue',
        icon: Wheat,
        ability: 'post-daily',
        tone: 'text-turmeric',
    },
    {
        title: 'খরচ / আয় লিখুন',
        hint: 'নতুন এন্ট্রি, রসিদসহ',
        href: '/entries/create',
        icon: HandCoins,
        ability: 'post-entries',
        tone: 'text-paddy',
    },
    {
        title: 'মাল কেনা',
        hint: 'ভুসি, দানাদার, সার — স্টকে',
        href: '/stock/move?mode=purchase',
        icon: Truck,
        ability: 'post-entries',
        tone: 'text-clay',
    },
    {
        title: 'নতুন গরু',
        hint: 'একটি বা একসাথে কয়েকটি',
        href: '/animals/create',
        icon: Beef,
        ability: 'post-entries',
        tone: 'text-clay',
    },
    {
        title: 'রিমাইন্ডার',
        hint: '১ ঘণ্টা পরে / কাল সকালে',
        href: '/reminders',
        icon: AlarmClock,
        ability: null,
        tone: 'text-turmeric',
    },
    {
        title: 'ডায়েরিতে লিখুন',
        hint: 'আজকের কথা, ভুল, শিক্ষা',
        href: '/diary',
        icon: NotebookPen,
        ability: 'post-daily',
        tone: 'text-paddy',
    },
    {
        title: 'অফলাইন এন্ট্রি',
        hint: 'নেট না থাকলে',
        href: '/offline',
        icon: CloudOff,
        ability: 'post-daily',
        tone: 'text-muted-foreground',
    },
];

export type PageLink = {
    title: string;
    words: string;
    href: string;
    icon: LucideIcon;
    ability: Ability | null;
};

/** Pages by name and the words people would type to find them (Bangla and English). */
export const pageLinks: PageLink[] = [
    {
        title: 'আজকের খামার',
        words: 'dashboard home ড্যাশবোর্ড',
        href: '/dashboard',
        icon: LayoutGrid,
        ability: null,
    },
    {
        title: 'দুধের হিসাব',
        words: 'milk দুধ লিটার',
        href: '/milk',
        icon: Milk,
        ability: 'post-daily',
    },
    {
        title: 'গরুকে খাবার দেওয়া',
        words: 'feed খাবার ভুসি দানাদার ঘাস',
        href: '/stock/move?mode=issue',
        icon: Wheat,
        ability: 'post-daily',
    },
    {
        title: 'মাল কেনা',
        words: 'purchase stock কেনা',
        href: '/stock/move?mode=purchase',
        icon: Truck,
        ability: 'post-entries',
    },
    {
        title: 'নিজের উৎপাদন (ঘাস)',
        words: 'grass produce ঘাস কাটা',
        href: '/stock/move?mode=produce',
        icon: Wheat,
        ability: 'post-entries',
    },
    {
        title: 'স্টকে কী আছে',
        words: 'stock inventory স্টক মাল',
        href: '/stock',
        icon: Package,
        ability: 'view-reports',
    },
    {
        title: 'কোথায় কত কেজি গেল',
        words: 'usage kg জমি সার শতাংশ',
        href: '/reports/usage',
        icon: Package,
        ability: 'view-reports',
    },
    {
        title: 'গরুর তালিকা',
        words: 'animals cows গরু',
        href: '/animals',
        icon: Beef,
        ability: 'view-reports',
    },
    {
        title: 'নতুন গরু',
        words: 'add animal buy গরু কেনা',
        href: '/animals/create',
        icon: Beef,
        ability: 'post-entries',
    },
    {
        title: 'গর্ভ ও বাছুর',
        words: 'pregnancy breeding calf বীজ বাচ্চা',
        href: '/breeding',
        icon: CalendarHeart,
        ability: 'view-reports',
    },
    {
        title: 'জমি ও ফসল',
        words: 'land crop paddy rice potato mustard maize জমি ধান চাল আলু সরিষা ভুট্টা লিজ মৌসুম বিঘা',
        href: '/land',
        icon: Sprout,
        ability: 'view-reports',
    },
    {
        title: 'টিকা ও কৃমি',
        words: 'vaccine deworm health টিকা কৃমি চিকিৎসা',
        href: '/health',
        icon: CalendarHeart,
        ability: 'view-reports',
    },
    {
        title: 'মোটাতাজা ব্যাচ',
        words: 'batch fattening ব্যাচ',
        href: '/batches',
        icon: Layers,
        ability: 'view-reports',
    },
    {
        title: 'খামার ডায়েরি ও লক্ষ্য',
        words: 'diary goals notes ডায়েরি লক্ষ্য ভুল শিক্ষা',
        href: '/diary',
        icon: NotebookPen,
        ability: 'view-reports',
    },
    {
        title: 'মনে করিয়ে দিন',
        words: 'reminder notification রিমাইন্ডার নোটিফিকেশন',
        href: '/reminders',
        icon: AlarmClock,
        ability: null,
    },
    {
        title: 'নতুন এন্ট্রি',
        words: 'entry expense income খরচ আয়',
        href: '/entries/create',
        icon: HandCoins,
        ability: 'post-entries',
    },
    {
        title: 'টাকা কোথায় আছে',
        words: 'money cash bank bkash টাকা ব্যাংক বিকাশ',
        href: '/money',
        icon: Wallet,
        ability: 'view-reports',
    },
    {
        title: 'খাতা (সব এন্ট্রি)',
        words: 'ledger entries খাতা',
        href: '/entries',
        icon: HandCoins,
        ability: 'view-reports',
    },
    {
        title: 'খরচ ভাগ (মাস শেষে)',
        words: 'allocation overhead ভাগ সাধারণ খরচ',
        href: '/allocation',
        icon: HandCoins,
        ability: 'post-entries',
    },
    {
        title: 'ক্যাটাগরি ও প্রজেক্ট',
        words: 'projects categories প্রজেক্ট',
        href: '/centers',
        icon: ChartColumnBig,
        ability: 'view-reports',
    },
    {
        title: 'লাভ-ক্ষতি ও সম্পদ',
        words: 'reports profit লাভ ক্ষতি',
        href: '/reports',
        icon: ChartColumnBig,
        ability: 'view-reports',
    },
    {
        title: 'মাসিক রিপোর্ট',
        words: 'monthly report pdf whatsapp মাসিক',
        href: '/reports/monthly',
        icon: ChartColumnBig,
        ability: 'view-reports',
    },
    {
        title: 'উদ্যোগ বাজেট',
        words: 'budget loan বাজেট ঋণ',
        href: '/reports/budget',
        icon: ChartColumnBig,
        ability: 'view-reports',
    },
    {
        title: 'ভাগ কীভাবে হয় (উদাহরণ)',
        words: 'guide split example উদাহরণ',
        href: '/guide/split',
        icon: LifeBuoy,
        ability: 'view-reports',
    },
    {
        title: 'সাহায্য ও গাইড',
        words: 'help guide সাহায্য',
        href: '/guide',
        icon: LifeBuoy,
        ability: null,
    },
    {
        title: 'প্রজেক্ট সাজান',
        words: 'manage categories edit delete hide প্রজেক্ট',
        href: '/setup/centers',
        icon: Settings2,
        ability: 'manage-setup',
    },
    {
        title: 'হিসাব খাত',
        words: 'accounts খাত',
        href: '/setup/accounts',
        icon: Settings2,
        ability: 'manage-setup',
    },
    {
        title: 'ক্রেতা, বিনিয়োগকারী',
        words: 'parties customers suppliers investors',
        href: '/setup/parties',
        icon: Settings2,
        ability: 'manage-setup',
    },
    {
        title: 'ব্যবহারকারী',
        words: 'users ইউজার',
        href: '/setup/users',
        icon: Settings2,
        ability: 'manage-users',
    },
    {
        title: 'রোল ও অনুমতি',
        words: 'roles permissions',
        href: '/setup/roles',
        icon: Settings2,
        ability: 'manage-roles',
    },
    {
        title: 'মাস লক',
        words: 'lock period',
        href: '/setup/periods',
        icon: Settings2,
        ability: 'lock-periods',
    },
    {
        title: 'অডিট লগ',
        words: 'audit log',
        href: '/setup/audit',
        icon: Settings2,
        ability: 'view-audit',
    },
    {
        title: 'অ্যাপ আপডেট',
        words: 'update',
        href: '/setup/update',
        icon: Settings2,
        ability: 'manage-update',
    },
    {
        title: 'অফলাইন এন্ট্রি',
        words: 'offline নেট নেই',
        href: '/offline',
        icon: CloudOff,
        ability: 'post-daily',
    },
];
