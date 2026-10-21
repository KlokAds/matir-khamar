<script setup lang="ts">
import { ask } from '@/lib/dialog';
import { currentLocale, t } from '@/lib/i18n';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { CircleCheck, CircleAlert, GitBranch, RefreshCw } from '@lucide/vue';
import { computed } from 'vue';
import Field from '@/components/khamar/Field.vue';
import PageTitle from '@/components/khamar/PageTitle.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineOptions({
    layout: { breadcrumbs: [{ title: 'অ্যাপ আপডেট', href: '/setup/update' }] },
});

const props = defineProps<{
    status: {
        available: boolean;
        branch: string;
        current: { sha: string; subject: string; date: string } | null;
        latest: string | null;
        behind: boolean | null;
        last_run: {
            ok: boolean;
            from: string | null;
            to: string | null;
            log: string[];
            at: string;
        } | null;
    };
    auto: boolean;
    repository: string;
}>();

const page = usePage();
const error = computed(
    () => (page.props.errors as Record<string, string>).update,
);
const f = useForm({ current_password: '' });
const short = (sha: string | null | undefined) => (sha ? sha.slice(0, 7) : '—');
const when = (iso: string | null | undefined) =>
    iso
        ? new Date(iso).toLocaleString(
              currentLocale() === 'en' ? 'en-GB' : 'bn-BD',
              {
                  dateStyle: 'medium',
                  timeStyle: 'short',
              },
          )
        : '—';

async function run() {
    if (
        await ask.confirm({
            title: t('এখনই আপডেট করবেন?'),
            body: t(
                'সাইট ১-২ মিনিটের জন্য বন্ধ থাকবে। শুরুর আগে ডাটাবেসের ব্যাকআপ নেওয়া হবে।',
            ),
            confirmText: t('আপডেট করুন'),
        })
    ) {
        f.post('/setup/update', {
            preserveScroll: true,
            onFinish: () => f.reset(),
        });
    }
}

const state = computed(() => {
    if (!props.status.available) {
        return {
            tone: 'muted',
            text: t(
                'এই কপিটি git থেকে চলছে না (যেমন আপনার কম্পিউটার), তাই এখান থেকে আপডেট হয় না।',
            ),
        };
    }

    if (props.status.behind === null) {
        return {
            tone: 'clay',
            text: t('GitHub-এ পৌঁছানো যায়নি — পরে আবার দেখুন।'),
        };
    }

    return props.status.behind
        ? { tone: 'turmeric', text: t('GitHub-এ নতুন সংস্করণ আছে।') }
        : { tone: 'paddy', text: t('সাইট সর্বশেষ সংস্করণে আছে।') };
});
</script>

<template>
    <Head :title="t('অ্যাপ আপডেট')" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <PageTitle
            :title="t('অ্যাপ আপডেট')"
            :subtitle="
                t(
                    'GitHub-এ কোড push হলে সেটা আগে তৈরি (build) হয়, তারপর এখান থেকে এক চাপে পুরো সাইট নতুন হয়।',
                )
            "
        />

        <div class="grid max-w-4xl gap-5">
            <section
                :class="[
                    'flex items-start gap-3 rounded-xl border p-4',
                    {
                        paddy: 'border-paddy/40 bg-paddy/8',
                        turmeric: 'border-turmeric bg-turmeric/15',
                        clay: 'border-clay/40 bg-clay/8',
                        muted: 'border-border bg-muted/50',
                    }[state.tone],
                ]"
            >
                <component
                    :is="state.tone === 'paddy' ? CircleCheck : CircleAlert"
                    class="mt-0.5 size-5 shrink-0"
                />
                <div class="min-w-0 flex-1">
                    <p class="font-medium">{{ state.text }}</p>
                    <div
                        class="mt-2 grid gap-1 text-sm text-muted-foreground sm:grid-cols-2"
                    >
                        <p>
                            {{ t('এখন চলছে:') }}
                            <span class="num font-medium text-foreground">{{
                                short(status.current?.sha)
                            }}</span>
                            · {{ when(status.current?.date) }}
                        </p>
                        <p>
                            {{ t('GitHub-এ সর্বশেষ:') }}
                            <span class="num font-medium text-foreground">{{
                                short(status.latest)
                            }}</span>
                        </p>
                        <p class="flex items-center gap-1 sm:col-span-2">
                            <GitBranch class="size-3.5" /> {{ repository }} ·
                            {{ t('শাখা :branch', { branch: status.branch }) }}
                        </p>
                    </div>
                    <p v-if="status.current" class="mt-2 truncate text-sm">
                        “{{ status.current.subject }}”
                    </p>
                </div>
            </section>

            <form
                v-if="status.available"
                class="flex flex-wrap items-end gap-3 rounded-xl border border-border bg-card p-4"
                @submit.prevent="run"
            >
                <Field
                    :label="t('নিশ্চিত করতে আপনার লগইন পাসওয়ার্ড')"
                    :error="f.errors.current_password || error"
                >
                    <Input
                        v-model="f.current_password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="w-64"
                    />
                </Field>
                <Button type="submit" :disabled="f.processing">
                    <RefreshCw :class="f.processing && 'animate-spin'" />
                    {{
                        f.processing ? t('আপডেট হচ্ছে…') : t('এখনই আপডেট করুন')
                    }}
                </Button>
                <p class="w-full text-xs text-muted-foreground">
                    {{
                        t(
                            'ধাপ: ডাটাবেস ব্যাকআপ → GitHub থেকে নতুন build → সাইট সাময়িক বন্ধ → ডাটাবেস আপডেট (migrate) → ক্যাশ → সাইট চালু।',
                        )
                    }}
                    <template v-if="auto">
                        {{
                            t(
                                'স্বয়ংক্রিয় আপডেট চালু আছে: প্রতি ১০ মিনিটে নিজে দেখে নেয়।',
                            )
                        }}</template
                    >
                </p>
            </form>

            <section
                v-if="status.last_run"
                class="rounded-xl border border-border bg-card"
            >
                <div
                    class="flex items-center justify-between border-b border-border p-4"
                >
                    <h2 class="font-display text-lg">{{ t('শেষ আপডেট') }}</h2>
                    <span
                        :class="[
                            'rounded-full px-2.5 py-1 text-xs',
                            status.last_run.ok
                                ? 'bg-paddy/15 text-paddy'
                                : 'bg-destructive/10 text-destructive',
                        ]"
                    >
                        {{ status.last_run.ok ? t('সফল') : t('ব্যর্থ') }} ·
                        {{ when(status.last_run.at) }}
                    </span>
                </div>
                <p class="num px-4 pt-3 text-sm text-muted-foreground">
                    {{ short(status.last_run.from) }} →
                    {{ short(status.last_run.to) }}
                </p>
                <pre
                    class="m-4 max-h-96 overflow-auto rounded-lg bg-muted/60 p-3 text-xs leading-relaxed whitespace-pre-wrap"
                    >{{ status.last_run.log.join('\n') }}</pre>
            </section>
        </div>
    </div>
</template>
