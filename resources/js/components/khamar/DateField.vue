<script setup lang="ts">
import { CalendarDays, ChevronLeft, ChevronRight, Clock, X } from '@lucide/vue';
import {
    PopoverContent,
    PopoverPortal,
    PopoverRoot,
    PopoverTrigger,
} from 'reka-ui';
import { computed, ref, useAttrs, watch } from 'vue';
import { currentLocale, t } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/**
 * The app's own date, month and date-time picker, in place of the browser's grey box:
 * Bangla month and day names, "আজ / গতকাল" in one tap, and for times a few farm hours
 * (সকাল ৭টা, সন্ধ্যা ৬টা…) plus simple +/− steps. Values stay in the form's usual shape:
 * date "2026-10-22", month "2026-10", datetime "2026-10-22T07:00".
 */
defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        mode?: 'date' | 'month' | 'datetime';
        min?: string;
        max?: string;
        placeholder?: string;
        required?: boolean;
        disabled?: boolean;
        /** Show the × to empty an optional date (birth date, follow-up, filters). */
        clearable?: boolean;
    }>(),
    {
        mode: 'date',
        min: undefined,
        max: undefined,
        placeholder: undefined,
        required: false,
        disabled: false,
        clearable: false,
    },
);

const model = defineModel<string | null | undefined>();
const attrs = useAttrs();
const open = ref(false);

const bn = computed(() => currentLocale() === 'bn');
const MONTHS_BN = [
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
const MONTHS_EN = [
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
const DAYS_BN = ['রবি', 'সোম', 'মঙ্গল', 'বুধ', 'বৃহঃ', 'শুক্র', 'শনি'];
const DAYS_EN = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
const WEEKDAY_BN = [
    'রবিবার',
    'সোমবার',
    'মঙ্গলবার',
    'বুধবার',
    'বৃহস্পতিবার',
    'শুক্রবার',
    'শনিবার',
];
const DIGITS = '০১২৩৪৫৬৭৮৯';

const months = computed(() => (bn.value ? MONTHS_BN : MONTHS_EN));
const num = (n: number | string) =>
    bn.value ? String(n).replace(/\d/g, (d) => DIGITS[Number(d)]) : String(n);
const pad = (n: number) => String(n).padStart(2, '0');
const ymd = (y: number, m: number, d: number) => `${y}-${pad(m + 1)}-${pad(d)}`;

const now = new Date();
const todayStr = ymd(now.getFullYear(), now.getMonth(), now.getDate());
const yesterday = new Date(now.getTime() - 86400_000);
const yesterdayStr = ymd(
    yesterday.getFullYear(),
    yesterday.getMonth(),
    yesterday.getDate(),
);

/* ---------- parsing the model ---------- */
const datePart = computed(() => (model.value ?? '').slice(0, 10));
const monthPart = computed(() => (model.value ?? '').slice(0, 7));
const hour = ref(7);
const minute = ref(0);

function readTime() {
    const time = (model.value ?? '').slice(11, 16);

    if (/^\d\d:\d\d$/.test(time)) {
        hour.value = Number(time.slice(0, 2));
        minute.value = Number(time.slice(3, 5));
    }
}

/* ---------- the calendar being looked at ---------- */
const view = ref({ year: now.getFullYear(), month: now.getMonth() });
const monthGrid = ref(props.mode === 'month');

function syncView() {
    const source = props.mode === 'month' ? monthPart.value : datePart.value;

    if (/^\d{4}-\d{2}/.test(source)) {
        view.value = {
            year: Number(source.slice(0, 4)),
            month: Number(source.slice(5, 7)) - 1,
        };
    }

    readTime();
    monthGrid.value = props.mode === 'month';
}

watch(open, (value) => value && syncView());

function shift(by: number) {
    if (monthGrid.value) {
        view.value = { ...view.value, year: view.value.year + by };

        return;
    }

    const d = new Date(view.value.year, view.value.month + by, 1);
    view.value = { year: d.getFullYear(), month: d.getMonth() };
}

const days = computed(() => {
    const first = new Date(view.value.year, view.value.month, 1);
    const count = new Date(view.value.year, view.value.month + 1, 0).getDate();
    const cells: ({ day: number; value: string } | null)[] = Array.from(
        { length: first.getDay() },
        () => null,
    );

    for (let day = 1; day <= count; day++) {
        cells.push({ day, value: ymd(view.value.year, view.value.month, day) });
    }

    return cells;
});

const outOfRange = (value: string) =>
    (props.min !== undefined && value < props.min.slice(0, value.length)) ||
    (props.max !== undefined && value > props.max.slice(0, value.length));

/* ---------- choosing ---------- */
const time = () => `${pad(hour.value)}:${pad(minute.value)}`;

function pickDay(value: string) {
    if (outOfRange(value)) {
        return;
    }

    if (props.mode === 'datetime') {
        model.value = `${value}T${time()}`;
    } else {
        model.value = value;
        open.value = false;
    }
}

function pickMonth(index: number) {
    const value = `${view.value.year}-${pad(index + 1)}`;

    if (props.mode === 'month') {
        if (!outOfRange(value)) {
            model.value = value;
            open.value = false;
        }

        return;
    }

    view.value = { ...view.value, month: index };
    monthGrid.value = false;
}

function setTime(h: number, m: number) {
    hour.value = (h + 24) % 24;
    minute.value = (m + 60) % 60;
    model.value = `${datePart.value || todayStr}T${time()}`;
}

const stepHour = (by: number) => setTime(hour.value + by, minute.value);

/** Typed straight in: hour 1–12 on the shown side of noon, minute 0–59. */
function typeHour(event: Event) {
    const n = Number((event.target as HTMLInputElement).value);

    if (Number.isInteger(n) && n >= 1 && n <= 12) {
        setTime((n % 12) + (hour.value >= 12 ? 12 : 0), minute.value);
    } else {
        (event.target as HTMLInputElement).value = String(clock12(hour.value));
    }
}

function typeMinute(event: Event) {
    const n = Number((event.target as HTMLInputElement).value);

    if (Number.isInteger(n) && n >= 0 && n <= 59) {
        setTime(hour.value, n);
    } else {
        (event.target as HTMLInputElement).value = pad(minute.value);
    }
}

const flipNoon = () => setTime(hour.value + 12, minute.value);
const stepMinute = (by: number) => {
    const total =
        (((hour.value * 60 + minute.value + by) % 1440) + 1440) % 1440;
    setTime(Math.floor(total / 60), total % 60);
};

const farmHours = [
    { label: 'সকাল ৭টা', en: '7 AM', h: 7 },
    { label: 'সকাল ৯টা', en: '9 AM', h: 9 },
    { label: 'দুপুর ১২টা', en: '12 PM', h: 12 },
    { label: 'বিকাল ৩টা', en: '3 PM', h: 15 },
    { label: 'সন্ধ্যা ৬টা', en: '6 PM', h: 18 },
    { label: 'রাত ৯টা', en: '9 PM', h: 21 },
];

function period(h: number): string {
    if (!bn.value) {
        return h < 12 ? 'AM' : 'PM';
    }

    return h < 5
        ? 'রাত'
        : h < 12
          ? 'সকাল'
          : h < 15
            ? 'দুপুর'
            : h < 18
              ? 'বিকাল'
              : h < 20
                ? 'সন্ধ্যা'
                : 'রাত';
}

const clock12 = (h: number) => (h % 12 === 0 ? 12 : h % 12);

/* ---------- what the box shows ---------- */
// Date, weekday and a short d/m/y: a narrow box shows less instead of cutting the date off.
const SEP = '|';
const label = computed(() => {
    if (!model.value) {
        return '';
    }

    if (props.mode === 'month') {
        const m = Number(monthPart.value.slice(5, 7)) - 1;

        return `${months.value[m]} ${num(monthPart.value.slice(0, 4))}`;
    }

    const [y, m, d] = datePart.value.split('-').map(Number);

    if (!y || !m || !d) {
        return model.value;
    }

    const date = new Date(y, m - 1, d);
    const relative =
        datePart.value === todayStr
            ? t('আজ')
            : datePart.value === yesterdayStr
              ? t('গতকাল')
              : bn.value
                ? WEEKDAY_BN[date.getDay()]
                : date.toLocaleDateString('en-GB', { weekday: 'short' });
    let text = `${num(d)} ${months.value[m - 1]} ${num(y)}${SEP}${relative}${SEP}${num(d)}/${num(m)}/${num(y)}`;

    if (props.mode === 'datetime' && model.value.length >= 16) {
        const h = Number(model.value.slice(11, 13));
        const mm = model.value.slice(14, 16);
        text = `${num(d)} ${months.value[m - 1]}, ${period(h)} ${num(clock12(h))}:${num(mm)}`;
    }

    return text;
});

const placeholderText = computed(
    () =>
        props.placeholder ??
        (props.mode === 'month'
            ? t('মাস বাছুন')
            : props.mode === 'datetime'
              ? t('তারিখ ও সময় বাছুন')
              : t('তারিখ বাছুন')),
);

const passThrough = computed(() => {
    const { class: _class, ...rest } = attrs;

    return rest;
});
</script>

<template>
    <PopoverRoot v-model:open="open">
        <PopoverTrigger as-child>
            <button
                type="button"
                v-bind="passThrough"
                :disabled="disabled"
                :data-state="open ? 'open' : 'closed'"
                :class="
                    cn(
                        '@container flex h-10 w-full min-w-0 items-center gap-2 rounded-md border border-input bg-card px-3 text-left text-[15px] shadow-xs transition-[color,box-shadow,border-color] outline-none hover:border-ring/60 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/40 disabled:cursor-not-allowed disabled:opacity-50 data-[state=open]:border-ring data-[state=open]:ring-[3px] data-[state=open]:ring-ring/25',
                        attrs.class as string,
                    )
                "
            >
                <CalendarDays
                    v-if="mode !== 'datetime'"
                    class="size-4 shrink-0 text-paddy"
                />
                <Clock v-else class="size-4 shrink-0 text-paddy" />
                <span
                    :class="[
                        'min-w-0 flex-1 truncate py-px leading-normal',
                        !label && 'text-muted-foreground',
                    ]"
                    ><template v-if="label.includes(SEP)"
                        ><span class="@[10.5rem]:hidden">{{
                            label.split(SEP)[2]
                        }}</span
                        ><span class="hidden @[10.5rem]:inline">{{
                            label.split(SEP)[0]
                        }}</span></template
                    ><template v-else>{{ label || placeholderText }}</template
                    ><span
                        v-if="label.includes(SEP)"
                        class="hidden text-muted-foreground @[14rem]:inline"
                    >
                        · {{ label.split(SEP)[1] }}</span
                    ></span
                >
                <span
                    v-if="model && clearable && !disabled"
                    role="button"
                    tabindex="-1"
                    class="-mr-1 rounded p-0.5 text-muted-foreground hover:bg-accent hover:text-foreground"
                    :aria-label="t('মুছুন')"
                    @click.stop="model = ''"
                    ><X class="size-3.5"
                /></span>
            </button>
        </PopoverTrigger>

        <PopoverPortal>
            <PopoverContent
                align="start"
                :side-offset="6"
                :collision-padding="12"
                class="z-50 w-[19rem] rounded-xl border border-border bg-popover p-3 text-popover-foreground shadow-lg data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95"
            >
                <!-- One-tap days -->
                <div v-if="mode !== 'month'" class="mb-2 flex gap-1.5">
                    <button
                        type="button"
                        class="quick"
                        :disabled="outOfRange(todayStr)"
                        @click="pickDay(todayStr)"
                    >
                        {{ t('আজ') }}
                    </button>
                    <button
                        type="button"
                        class="quick"
                        :disabled="outOfRange(yesterdayStr)"
                        @click="pickDay(yesterdayStr)"
                    >
                        {{ t('গতকাল') }}
                    </button>
                </div>

                <!-- Month / year header -->
                <div class="mb-2 flex items-center justify-between">
                    <button type="button" class="nav" @click="shift(-1)">
                        <ChevronLeft class="size-4" />
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-2 py-1 text-sm font-semibold hover:bg-accent"
                        @click="monthGrid = !monthGrid || mode === 'month'"
                    >
                        <template v-if="monthGrid">{{
                            num(view.year)
                        }}</template>
                        <template v-else
                            >{{ months[view.month] }}
                            {{ num(view.year) }}</template
                        >
                    </button>
                    <button type="button" class="nav" @click="shift(1)">
                        <ChevronRight class="size-4" />
                    </button>
                </div>

                <!-- Months -->
                <div v-if="monthGrid" class="grid grid-cols-3 gap-1">
                    <button
                        v-for="(name, index) in months"
                        :key="name"
                        type="button"
                        :disabled="
                            mode === 'month' &&
                            outOfRange(`${view.year}-${pad(index + 1)}`)
                        "
                        :class="[
                            'h-10 rounded-md text-sm transition-colors disabled:opacity-30',
                            monthPart === `${view.year}-${pad(index + 1)}`
                                ? 'bg-primary font-semibold text-primary-foreground'
                                : 'hover:bg-accent',
                        ]"
                        @click="pickMonth(index)"
                    >
                        {{ name }}
                    </button>
                </div>

                <!-- Days -->
                <template v-else>
                    <div
                        class="mb-1 grid grid-cols-7 text-center text-[11px] text-muted-foreground"
                    >
                        <span v-for="d in bn ? DAYS_BN : DAYS_EN" :key="d">{{
                            d
                        }}</span>
                    </div>
                    <div class="grid grid-cols-7 gap-0.5">
                        <template v-for="(cell, i) in days" :key="i">
                            <span v-if="!cell" />
                            <button
                                v-else
                                type="button"
                                :disabled="outOfRange(cell.value)"
                                :class="[
                                    'num h-9 rounded-md text-sm transition-colors disabled:opacity-25',
                                    cell.value === datePart
                                        ? 'bg-primary font-semibold text-primary-foreground'
                                        : cell.value === todayStr
                                          ? 'font-semibold text-paddy ring-1 ring-paddy/50 hover:bg-accent'
                                          : 'hover:bg-accent',
                                ]"
                                @click="pickDay(cell.value)"
                            >
                                {{ num(cell.day) }}
                            </button>
                        </template>
                    </div>
                </template>

                <!-- Time -->
                <div
                    v-if="mode === 'datetime'"
                    class="mt-3 border-t border-border/70 pt-3"
                >
                    <div class="mb-2 grid grid-cols-3 gap-1">
                        <button
                            v-for="f in farmHours"
                            :key="f.h"
                            type="button"
                            :class="[
                                'quick',
                                hour === f.h && minute === 0 && 'is-on',
                            ]"
                            @click="setTime(f.h, 0)"
                        >
                            {{ bn ? f.label : f.en }}
                        </button>
                    </div>
                    <div class="flex items-center justify-center gap-2">
                        <button
                            type="button"
                            class="quick"
                            style="flex: none"
                            :title="t('দুপুরের আগে/পরে বদলান')"
                            @click="flipNoon"
                        >
                            {{ period(hour) }}
                        </button>
                        <div class="stepper">
                            <button type="button" @click="stepHour(-1)">
                                −
                            </button>
                            <input
                                class="num"
                                inputmode="numeric"
                                maxlength="2"
                                :aria-label="t('ঘণ্টা')"
                                :value="clock12(hour)"
                                @change="typeHour"
                                @focus="
                                    ($event.target as HTMLInputElement).select()
                                "
                            />
                            <button type="button" @click="stepHour(1)">
                                +
                            </button>
                        </div>
                        <span class="font-bold">:</span>
                        <div class="stepper">
                            <button type="button" @click="stepMinute(-1)">
                                −
                            </button>
                            <input
                                class="num"
                                inputmode="numeric"
                                maxlength="2"
                                :aria-label="t('মিনিট')"
                                :value="pad(minute)"
                                @change="typeMinute"
                                @focus="
                                    ($event.target as HTMLInputElement).select()
                                "
                            />
                            <button type="button" @click="stepMinute(1)">
                                +
                            </button>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="mt-3 h-10 w-full rounded-md bg-primary text-sm font-medium text-primary-foreground"
                        @click="
                            model = `${datePart || todayStr}T${time()}`;
                            open = false;
                        "
                    >
                        {{ t('ঠিক আছে') }}
                    </button>
                </div>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>

<style scoped>
@reference '../../../css/app.css';

.quick {
    @apply h-8 flex-1 rounded-md border border-border px-2 text-xs font-medium transition-colors hover:bg-accent disabled:opacity-30;
}

.quick.is-on {
    @apply border-primary bg-primary text-primary-foreground;
}

.nav {
    @apply flex size-8 items-center justify-center rounded-full border border-border bg-card text-muted-foreground shadow-xs hover:bg-accent hover:text-foreground;
}

.stepper {
    @apply flex items-center overflow-hidden rounded-md border border-input;
}

.stepper button {
    @apply h-9 w-9 text-lg text-muted-foreground hover:bg-accent hover:text-foreground;
}

.stepper input {
    @apply h-9 w-10 bg-transparent text-center text-base font-bold outline-none focus:bg-accent;
}
</style>
