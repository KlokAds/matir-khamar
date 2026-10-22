<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Beef, ChevronRight, Coins, HeartCrack, Plus } from '@lucide/vue';
import { computed } from 'vue';
import CenterIcon from '@/components/khamar/CenterIcon.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { t, tAll } from '@/lib/i18n';
import { day, taka, useCan, useHerdWords } from '@/lib/khamar';

defineOptions({
    layout: { breadcrumbs: [{ title: 'পশুর তালিকা', href: '/animals' }] },
});

type StatusKey = 'active' | 'sold' | 'dead';

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
    animals: {
        id: number;
        species: 'cattle' | 'goat';
        tag: string;
        name: string | null;
        sex: string;
        breed: string | null;
        center: string;
        acquired_on: string;
        purchase_cost: number;
        sale_amount: number | null;
    }[];
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

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="a in animals"
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
                v-if="!animals.length"
                class="col-span-full rounded-xl border border-dashed border-border p-8 text-center text-muted-foreground"
            >
                {{ t(herd.none) }}
            </p>
        </div>
    </div>
</template>
