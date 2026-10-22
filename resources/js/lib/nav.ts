import {
    AlarmClock,
    ArrowLeftRight,
    Banknote,
    Beef,
    BookOpen,
    CalendarHeart,
    ChartColumnBig,
    ChartPie,
    CloudOff,
    FileText,
    FolderTree,
    Gauge,
    HandCoins,
    HeartPulse,
    History,
    Landmark,
    Layers,
    LayoutGrid,
    LifeBuoy,
    Lock,
    Milk,
    NotebookPen,
    Package,
    PiggyBank,
    Plus,
    ReceiptText,
    RefreshCw,
    Scale,
    Settings2,
    ShieldCheck,
    Sprout,
    Tags,
    TrendingUp,
    Truck,
    UserCog,
    Users,
    Wallet,
    Wheat,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useCan, useHerdWords, useModules } from '@/lib/khamar';
import { pendingCount } from '@/lib/offlineQueue';

export type NavLink = {
    title: string;
    href: string;
    icon: LucideIcon;
    badge?: number;
    /** Full page load instead of an Inertia visit (lets the service worker answer offline). */
    reload?: boolean;
};

export type NavGroup = {
    title: string;
    icon: LucideIcon;
    /** Colour of the group's tiles in the phone menu. */
    tone: 'paddy' | 'clay' | 'turmeric' | 'pond' | 'muted';
    links: NavLink[];
};

/** The phone menu sheet; the top bar's stall button and the bottom bar both open it. */
export const mobileMenuOpen = ref(false);

/**
 * Every page of the app in one place, for the sidebar on a computer and the menu sheet on a
 * phone. Each entry shows only to someone allowed to open it, and a project turned off takes
 * its pages with it.
 */
export function useNav() {
    const can = useCan();
    const modules = useModules();
    const herd = useHerdWords();

    const top = computed<NavLink[]>(() => {
        const c = can.value;

        if (c['investor-view'] && Object.keys(c).length === 1) {
            return [
                { title: 'আমার বিনিয়োগ', href: '/investor', icon: HandCoins },
            ];
        }

        return [
            { title: 'আজকের খামার', href: '/dashboard', icon: LayoutGrid },
            ...(c['post-daily']
                ? [
                      {
                          title: 'অফলাইন এন্ট্রি',
                          href: '/offline',
                          icon: CloudOff,
                          reload: true,
                          badge: pendingCount.value,
                      },
                  ]
                : []),
            { title: 'মনে করিয়ে দিন', href: '/reminders', icon: AlarmClock },
            ...(c['view-reports']
                ? [
                      {
                          title: 'খামার ডায়েরি ও লক্ষ্য',
                          href: '/diary',
                          icon: NotebookPen,
                      },
                  ]
                : []),
        ];
    });

    const groups = computed<NavGroup[]>(() => {
        const c = can.value;
        const m = modules.value;
        const h = herd.value;
        const herdOn = m.cattle || m.goat;

        if (c['investor-view'] && Object.keys(c).length === 1) {
            return [];
        }

        const group = (
            title: string,
            icon: LucideIcon,
            tone: NavGroup['tone'],
            entries: [string, string, LucideIcon, boolean][],
        ): NavGroup[] => {
            const links = entries
                .filter(([, , , allowed]) => allowed)
                .map(([t, href, i]) => ({ title: t, href, icon: i }));

            return links.length ? [{ title, icon, tone, links }] : [];
        };

        return [
            ...group(h.group, Beef, 'clay', [
                [h.list, '/animals', Beef, herdOn && !!c['view-reports']],
                [h.new, '/animals/create', Plus, herdOn && !!c['post-entries']],
                ['দুধের হিসাব', '/milk', Milk, m.milk && !!c['post-daily']],
                [
                    'গর্ভ ও বাছুর',
                    '/breeding',
                    CalendarHeart,
                    m.cattle && !!c['view-reports'],
                ],
                [
                    'টিকা ও কৃমি',
                    '/health',
                    HeartPulse,
                    herdOn && !!c['view-reports'],
                ],
                [
                    'মোটাতাজা ব্যাচ',
                    '/batches',
                    Layers,
                    m.fattening && !!c['view-reports'],
                ],
            ]),
            ...group('জমি ও ফসল', Sprout, 'paddy', [
                ['জমি ও মৌসুম', '/land', Sprout, m.land && !!c['view-reports']],
            ]),
            ...group('খাবার ও স্টক', Wheat, 'turmeric', [
                [
                    h.feed,
                    '/stock/move?mode=issue',
                    Wheat,
                    herdOn && !!c['post-daily'],
                ],
                [
                    'মাল কেনা',
                    '/stock/move?mode=purchase',
                    Truck,
                    !!c['post-entries'],
                ],
                [
                    'নিজের উৎপাদন (ঘাস)',
                    '/stock/move?mode=produce',
                    Sprout,
                    m.land && !!c['post-entries'],
                ],
                ['স্টকে কী আছে', '/stock', Package, !!c['view-reports']],
                [
                    'কোথায় কত কেজি গেল',
                    '/reports/usage',
                    Scale,
                    !!c['view-reports'],
                ],
            ]),
            ...group('টাকার হিসাব', HandCoins, 'pond', [
                [
                    'নতুন এন্ট্রি',
                    '/entries/create',
                    ReceiptText,
                    !!c['post-entries'],
                ],
                ['মজুরির খাতা', '/labour', Users, !!c['view-reports']],
                ['নগদ ও পাওনা', '/cash', Banknote, !!c['view-reports']],
                ['ধার ও জামানত', '/debts', HandCoins, !!c['view-reports']],
                ['টাকা কোথায় আছে', '/money', Wallet, !!c['view-reports']],
                ['খাতা (সব এন্ট্রি)', '/entries', BookOpen, !!c['view-reports']],
                [
                    'খরচ ভাগ (মাস শেষে)',
                    '/allocation',
                    ArrowLeftRight,
                    !!c['post-entries'],
                ],
            ]),
            ...group('রিপোর্ট', ChartColumnBig, 'paddy', [
                ['হিসাবের সারাংশ', '/summary', Gauge, !!c['view-reports']],
                [
                    'লাভের হিসাব ও পরিকল্পনা',
                    '/profit',
                    TrendingUp,
                    !!c['view-reports'],
                ],
                ['বাজারদর', '/prices', Tags, !!c['view-reports']],
                [
                    'ক্যাটাগরি ও প্রজেক্ট',
                    '/centers',
                    FolderTree,
                    !!c['view-reports'],
                ],
                [
                    'লাভ-ক্ষতি ও সম্পদ',
                    '/reports',
                    ChartColumnBig,
                    !!c['view-reports'],
                ],
                [
                    'মাসিক রিপোর্ট',
                    '/reports/monthly',
                    FileText,
                    !!c['view-reports'],
                ],
                [
                    'উদ্যোগ বাজেট',
                    '/reports/budget',
                    PiggyBank,
                    !!c['view-reports'],
                ],
                [
                    'ভাগ কীভাবে হয় (উদাহরণ)',
                    '/guide/split',
                    ChartPie,
                    !!c['view-reports'],
                ],
                ['সাহায্য ও গাইড', '/guide', LifeBuoy, true],
            ]),
            ...group('সেটআপ ও নিরাপত্তা', Settings2, 'muted', [
                [
                    'প্রজেক্ট সাজান',
                    '/setup/centers',
                    FolderTree,
                    !!c['manage-setup'],
                ],
                ['হিসাব খাত', '/setup/accounts', Landmark, !!c['manage-setup']],
                [
                    'ক্রেতা, বিনিয়োগকারী…',
                    '/setup/parties',
                    Users,
                    !!c['manage-setup'],
                ],
                ['ব্যবহারকারী', '/setup/users', UserCog, !!c['manage-users']],
                [
                    'বিনিয়োগকারী',
                    '/setup/investors',
                    HandCoins,
                    !!c['manage-users'],
                ],
                [
                    'রোল ও অনুমতি',
                    '/setup/roles',
                    ShieldCheck,
                    !!c['manage-roles'],
                ],
                ['মাস লক', '/setup/periods', Lock, !!c['lock-periods']],
                ['অডিট লগ', '/setup/audit', History, !!c['view-audit']],
                ['অ্যাপ আপডেট', '/setup/update', RefreshCw, !!c['manage-update']],
            ]),
        ];
    });

    return { top, groups };
}
