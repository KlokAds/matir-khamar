<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Beef,
    ChevronRight,
    Coins,
    HeartCrack,
    LayoutGrid,
    Plus,
    Rows3,
    Search,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import CenterIcon from '@/components/khamar/CenterIcon.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { t, tAll } from '@/lib/i18n';
import { day, taka, useCan, useHerdWords } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'পশুর তালিকা', href: '/animals' }] },
});

type StatusKey = 'active' | 'sold' | 'dead';

type Animal = {
    id: number;
    center_id: number;
    center_code: string;
    center_sort: number;
    batch: string | null;
    kg: number | null;
    pregnant_until: string | null;
    care_due: boolean;
    book_value: number;
    species: 'cattle' | 'goat';
    tag: string;
    name: string | null;
    sex: string;
    breed: string | null;
    center: string;
    acquired_on: string;
    purchase_cost: number;
    sale_amount: number | null;
};

const props = defineProps<{
    status: string;
    statuses: Record<string, string>;
    summary: Record<
        StatusKey,
        {
            count: number;
            female: number;
            male: number;
            amount: number;
            by_center: { name: string; count: number }[];
        }
    >;
    animals: Animal[];
}>();

const can = useCan();
const herd = useHerdWords();

/** The three status cards: they show the totals and switch the list below. */
const cards = computed(() => {
    const goat = herd.value.kind === 'goat';
    const female = t(goat ? 'ছাগী' : 'গাভী/বকনা');
    const male = t(goat ? 'খাসি/পাঁঠা' : 'ষাঁড়/এঁড়ে');
    const labels = tAll(props.statuses);
    const centers = (key: StatusKey) =>
        props.summary[key].by_center
            .map((c) => `${t(c.name)} ${c.count}`)
            .join(' · ');
    const { active, sold, dead } = props.summary;

    return [
        {
            key: 'active' as const,
            label: labels.active,
            count: active.count,
            icon: Beef,
            tone: 'bg-paddy/12 text-paddy',
            lines: [
                centers('active'),
                active.count
                    ? `${female} ${active.female} · ${male} ${active.male}`
                    : '',
                active.amount
                    ? t('কেনা দাম :amount', { amount: taka(active.amount) })
                    : '',
            ],
        },
        {
            key: 'sold' as const,
            label: labels.sold,
            count: sold.count,
            icon: Coins,
            tone: 'bg-turmeric/25 text-soil',
            lines: [
                sold.amount
                    ? t('মোট বিক্রি :amount', { amount: taka(sold.amount) })
                    : '',
                centers('sold'),
            ],
        },
        {
            key: 'dead' as const,
            label: labels.dead,
            count: dead.count,
            icon: HeartCrack,
            tone: 'bg-muted text-muted-foreground',
            lines: [centers('dead')],
        },
    ].map((card) => ({ ...card, lines: card.lines.filter(Boolean) }));
});

/*
 * The herd board: one panel per category (and per batch), each animal a small tag chip, so a
 * hundred animals fit on a screen. Marks on a chip: carrying a calf, a dose due. The big cards
 * stay one tap away for a closer look.
 */
const search = ref('');
const view = ref<'board' | 'cards'>('board');

try {
    view.value =
        localStorage.getItem('khamar.animals.view') === 'cards'
            ? 'cards'
            : 'board';
} catch {
    // Private mode: the board it is.
}

watch(view, (value) => {
    try {
        localStorage.setItem('khamar.animals.view', value);
    } catch {
        // Nothing to remember it in.
    }
});

const found = computed(() => {
    const q = search.value.trim().toLowerCase();

    return q
        ? props.animals.filter((a) =>
              `${a.tag} ${a.name ?? ''} ${a.breed ?? ''}`
                  .toLowerCase()
                  .includes(q),
          )
        : props.animals;
});

type Group = {
    key: string;
    title: string;
    sub: string | null;
    sort: number;
    animals: Animal[];
};

const groups = computed<Group[]>(() => {
    const map = new Map<string, Group>();

    for (const a of found.value) {
        const key = `${a.center_id}:${a.batch ?? ''}`;
        const group = map.get(key) ?? {
            key,
            title: a.center,
            sub: a.batch,
            sort: a.center_sort,
            animals: [],
        };

        group.animals.push(a);
        map.set(key, group);
    }

    return [...map.values()].sort(
        (x, y) => x.sort - y.sort || (x.sub ?? '').localeCompare(y.sub ?? ''),
    );
});

const groupValue = (animals: Animal[]) =>
    animals.reduce(
        (sum, a) =>
            sum +
            (props.status === 'sold' ? (a.sale_amount ?? 0) : a.book_value),
        0,
    );

const chipTitle = (a: Animal) =>
    [
        a.tag,
        a.name,
        a.kg ? `${a.kg} kg` : null,
        a.pregnant_until
            ? t('গর্ভবতী — বাছুর :date', { date: day(a.pregnant_until) })
            : null,
        a.care_due ? t('টিকা/কৃমি বাকি') : null,
    ]
        .filter(Boolean)
        .join(' · ');
</script>

<template>
    <Head :title="t(herd.list)" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t(herd.list)"
            :subtitle="
                t(
                    'প্রতিটি পশুর আলাদা কার্ড — কেনা দাম, কোন ক্যাটাগরিতে আছে, কত খরচ হলো, বিক্রিতে কত লাভ',
                )
            "
        >
            <template #actions>
                <Button v-if="can['post-entries']" as-child
                    ><Link href="/animals/create"
                        ><Plus /> {{ t(herd.add) }}</Link
                    ></Button
                >
            </template>
        </PageTitle>

        <nav
            class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-3"
            :aria-label="t(herd.list)"
        >
            <Link
                v-for="card in cards"
                :key="card.key"
                :href="`/animals?status=${card.key}`"
                preserve-state
                :aria-current="status === card.key ? 'page' : undefined"
                :class="[
                    'group relative rounded-xl border bg-card p-3 shadow-[0_1px_0_0_var(--border)] transition-colors hover:bg-accent/40 active:bg-accent/60 sm:p-4',
                    card.key === 'active' && 'col-span-2 sm:col-span-1',
                    status === card.key
                        ? 'border-primary ring-2 ring-primary/25'
                        : 'border-border',
                    card.key === 'dead' &&
                        !card.count &&
                        status !== card.key &&
                        'opacity-70',
                ]"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p
                            :class="[
                                'text-[13px] leading-snug',
                                status === card.key
                                    ? 'font-medium text-primary'
                                    : 'text-muted-foreground',
                            ]"
                        >
                            {{ card.label }}
                        </p>
                        <p
                            :class="[
                                'num mt-1 text-2xl font-semibold tracking-tight',
                                card.key === 'dead' && 'text-muted-foreground',
                            ]"
                        >
                            {{ t(':nটি', { n: card.count }) }}
                        </p>
                    </div>
                    <span :class="['shrink-0 rounded-lg p-2', card.tone]">
                        <component :is="card.icon" class="size-5" />
                    </span>
                </div>
                <p
                    v-for="line in card.lines"
                    :key="line"
                    class="num mt-1 text-xs leading-snug text-muted-foreground"
                >
                    {{ line }}
                </p>
            </Link>
        </nav>

        <!-- Find and view -->
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <div class="relative min-w-0 flex-1 basis-56">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    class="pl-9"
                    :placeholder="t('নম্বর বা নাম দিয়ে খুঁজুন')"
                />
            </div>
            <div
                class="flex rounded-full border border-border bg-card p-0.5"
                role="group"
            >
                <button
                    type="button"
                    :class="['seg', view === 'board' && 'is-on']"
                    :aria-pressed="view === 'board'"
                    @click="view = 'board'"
                >
                    <LayoutGrid class="size-4" /> {{ t('বোর্ড') }}
                </button>
                <button
                    type="button"
                    :class="['seg', view === 'cards' && 'is-on']"
                    :aria-pressed="view === 'cards'"
                    @click="view = 'cards'"
                >
                    <Rows3 class="size-4" /> {{ t('বড় কার্ড') }}
                </button>
            </div>
        </div>

        <!-- Board: one panel per category and batch, a chip per animal -->
        <template v-if="view === 'board'">
            <div class="grid gap-3 lg:grid-cols-2">
                <section
                    v-for="g in groups"
                    :key="g.key"
                    class="rounded-xl border border-border bg-card p-3 sm:p-4"
                >
                    <header class="mb-3 flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <CenterIcon kind="livestock" />
                            <div class="min-w-0">
                                <p class="leading-normal font-medium">
                                    {{ t(g.title)
                                    }}<span
                                        v-if="g.sub"
                                        class="text-muted-foreground"
                                    >
                                        · {{ g.sub }}</span
                                    >
                                </p>
                                <p
                                    class="num text-xs leading-normal text-muted-foreground"
                                >
                                    {{ taka(groupValue(g.animals)) }}
                                </p>
                            </div>
                        </div>
                        <span
                            class="num shrink-0 rounded-full bg-accent px-2.5 py-1 text-sm font-semibold"
                            >{{ t(':nটি', { n: g.animals.length }) }}</span
                        >
                    </header>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="a in g.animals"
                            :key="a.id"
                            :href="`/animals/${a.id}`"
                            :title="chipTitle(a)"
                            :class="[
                                'chip',
                                a.pregnant_until && 'is-pregnant',
                                a.care_due && 'is-due',
                            ]"
                        >
                            <span class="font-semibold">{{ a.tag }}</span>
                            <span
                                v-if="a.name"
                                class="max-w-28 truncate text-muted-foreground"
                                >{{ a.name }}</span
                            >
                            <span v-if="a.kg" class="num text-muted-foreground"
                                >{{ a.kg }}kg</span
                            >
                            <span
                                v-if="a.pregnant_until"
                                class="size-2 shrink-0 rounded-full bg-pink-500"
                            />
                            <span
                                v-if="a.care_due"
                                class="size-2 shrink-0 rounded-full bg-amber-500"
                            />
                        </Link>
                    </div>
                </section>
                <p
                    v-if="!groups.length"
                    class="col-span-full rounded-xl border border-dashed border-border p-8 text-center text-muted-foreground"
                >
                    {{
                        search ? t('এই নামে কিছু পাওয়া যায়নি।') : t(herd.none)
                    }}
                </p>
            </div>
            <p
                v-if="groups.length"
                class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs leading-normal text-muted-foreground"
            >
                <span class="flex items-center gap-1.5"
                    ><span class="size-2 rounded-full bg-pink-500" />
                    {{ t('গর্ভবতী') }}</span
                >
                <span class="flex items-center gap-1.5"
                    ><span class="size-2 rounded-full bg-amber-500" />
                    {{ t('টিকা/কৃমি বাকি') }}</span
                >
                <span>{{
                    t('যেকোনো নম্বরে চাপ দিলে তার পুরো কার্ড খুলবে।')
                }}</span>
            </p>
        </template>

        <!-- Big cards -->
        <div v-else class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="a in found"
                :key="a.id"
                :href="`/animals/${a.id}`"
                class="group rounded-xl border border-border bg-card p-4 transition-shadow hover:shadow-md active:bg-accent/40"
            >
                <div class="flex items-center gap-3">
                    <CenterIcon kind="livestock" />
                    <div class="min-w-0 flex-1">
                        <p class="font-display text-lg leading-tight">
                            {{ a.tag }}
                            <span
                                v-if="a.name"
                                class="text-base text-muted-foreground"
                                >· {{ a.name }}</span
                            >
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{
                                a.species === 'goat'
                                    ? a.sex === 'female'
                                        ? t('ছাগী')
                                        : t('খাসি/পাঁঠা')
                                    : a.sex === 'female'
                                      ? t('গাভী/বকনা')
                                      : t('ষাঁড়/এঁড়ে')
                            }}<span v-if="a.breed"> · {{ a.breed }}</span>
                        </p>
                    </div>
                    <span class="rounded-full bg-accent px-2.5 py-1 text-xs">{{
                        t(a.center)
                    }}</span>
                    <ChevronRight
                        class="size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5"
                    />
                </div>
                <div
                    class="mt-3 flex justify-between border-t border-dashed border-border pt-3 text-sm"
                >
                    <span class="text-muted-foreground">{{
                        t(':date থেকে', { date: day(a.acquired_on) })
                    }}</span>
                    <span class="num">{{
                        a.sale_amount !== null
                            ? t('বিক্রি :amount', {
                                  amount: taka(a.sale_amount),
                              })
                            : t('দাম :amount', {
                                  amount: taka(a.purchase_cost),
                              })
                    }}</span>
                </div>
            </Link>
            <p
                v-if="!found.length"
                class="col-span-full rounded-xl border border-dashed border-border p-8 text-center text-muted-foreground"
            >
                {{ search ? t('এই নামে কিছু পাওয়া যায়নি।') : t(herd.none) }}
            </p>
        </div>
    </div>
</template>

<style scoped>
@reference '../../../css/app.css';

.seg {
    @apply flex h-8 items-center gap-1.5 rounded-full px-3 text-sm text-muted-foreground transition-colors;
}

.seg.is-on {
    @apply bg-primary text-primary-foreground;
}

.chip {
    @apply flex h-9 min-w-16 items-center justify-center gap-1.5 rounded-lg border border-border bg-background px-2.5 text-sm leading-normal transition-colors hover:border-primary/60 hover:bg-accent active:bg-accent;
}

.chip.is-pregnant {
    @apply border-pink-500/50;
}

.chip.is-due {
    @apply border-amber-500/60;
}
</style>
