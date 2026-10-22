<script setup lang="ts">
import { Download, Smartphone } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { t } from '@/lib/i18n';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="grid min-h-svh lg:grid-cols-[1.1fr_1fr]">
        <!-- A field at dawn: sun, paddy rows and soil, drawn rather than photographed so it loads instantly. -->
        <aside
            class="relative hidden overflow-hidden bg-[oklch(0.93_0.05_85)] lg:block dark:bg-[oklch(0.24_0.03_60)]"
        >
            <svg
                viewBox="0 0 600 700"
                preserveAspectRatio="xMidYMax slice"
                class="absolute inset-0 h-full w-full"
                aria-hidden="true"
            >
                <circle
                    cx="420"
                    cy="230"
                    r="90"
                    fill="var(--turmeric)"
                    opacity="0.9"
                />
                <path
                    d="M0 380 C150 340 450 340 600 380 V700 H0Z"
                    fill="var(--paddy)"
                    opacity="0.85"
                />
                <path
                    d="M0 450 C150 410 450 410 600 450 V700 H0Z"
                    fill="oklch(0.45 0.1 135)"
                />
                <g
                    stroke="oklch(0.62 0.13 130)"
                    stroke-width="3"
                    stroke-linecap="round"
                    opacity="0.7"
                >
                    <path
                        v-for="i in 14"
                        :key="i"
                        :d="`M${i * 44 - 10} 470 q6 -22 0 -40 M${i * 44 - 10} 470 q-6 -18 4 -34`"
                        fill="none"
                    />
                </g>
                <path
                    d="M0 540 C150 505 450 505 600 540 V700 H0Z"
                    fill="var(--clay)"
                />
                <g
                    stroke="oklch(0.5 0.11 45)"
                    stroke-width="4"
                    fill="none"
                    opacity="0.8"
                >
                    <path d="M0 575 C150 545 450 545 600 575" />
                    <path d="M0 610 C150 582 450 582 600 610" />
                    <path d="M0 645 C150 620 450 620 600 645" />
                    <path d="M0 680 C150 658 450 658 600 680" />
                </g>
            </svg>
            <div class="relative z-10 p-12">
                <p
                    class="font-display text-5xl leading-tight text-soil dark:text-[oklch(0.92_0.03_85)]"
                >
                    {{ t('মাটির খামার') }}
                </p>
                <p
                    class="mt-3 max-w-sm text-lg text-soil/75 dark:text-[oklch(0.85_0.03_85)]"
                >
                    {{
                        t(
                            'গোপাই কাটখৈর, নওগাঁ — প্রতিটি গরু, প্রতি কেজি খাবার, প্রতিটি টাকার সঠিক হিসাব।',
                        )
                    }}
                </p>
            </div>
        </aside>

        <main
            class="furrows flex flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
        >
            <div class="w-full max-w-sm">
                <div class="flex flex-col gap-8">
                    <div class="flex flex-col items-center gap-4">
                        <div
                            class="flex size-16 items-center justify-center rounded-2xl bg-card shadow-sm ring-1 ring-border"
                        >
                            <AppLogoIcon class="size-12" />
                        </div>
                        <div class="space-y-2 text-center">
                            <h1 class="font-display text-2xl">
                                {{ t(title ?? '') }}
                            </h1>
                            <p
                                class="text-center text-sm text-muted-foreground"
                            >
                                {{ t(description ?? '') }}
                            </p>
                        </div>
                    </div>
                    <slot />

                    <!-- The Android app: the same site, with its own notifications and sound. -->
                    <details
                        class="group rounded-xl border border-border bg-card/70 text-sm"
                    >
                        <summary
                            class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 [&::-webkit-details-marker]:hidden"
                        >
                            <span class="flex items-center gap-2 font-medium">
                                <Smartphone class="size-4 text-paddy" />
                                {{ t('Android অ্যাপ') }}
                            </span>
                            <a
                                href="https://github.com/KlokAds/matir-khamar/releases/latest/download/matir-khamar.apk"
                                class="btn-chip-primary"
                                @click.stop
                                ><Download /> {{ t('ডাউনলোড') }}</a
                            >
                        </summary>
                        <ol
                            class="list-decimal space-y-1 border-t border-border px-4 py-3 pl-8 text-xs leading-normal text-muted-foreground"
                        >
                            <li>
                                {{
                                    t(
                                        '“ডাউনলোড” চাপুন, ফাইলটি নামা পর্যন্ত অপেক্ষা করুন।',
                                    )
                                }}
                            </li>
                            <li>
                                {{
                                    t(
                                        'ফাইলটি খুলুন। “অজানা উৎস থেকে ইনস্টল” চাইলে এই একবার অনুমতি দিন।',
                                    )
                                }}
                            </li>
                            <li>
                                {{
                                    t(
                                        'ইনস্টল হলে অ্যাপ খুলে লগইন করুন, নোটিফিকেশনের অনুমতি দিন।',
                                    )
                                }}
                            </li>
                            <li>
                                {{
                                    t(
                                        'সাইটে যা নতুন আসে, অ্যাপেও সাথে সাথে চলে আসে — অ্যাপ আবার নামাতে হয় না।',
                                    )
                                }}
                            </li>
                        </ol>
                    </details>
                </div>
            </div>
            <p class="text-xs text-muted-foreground">
                {{ t('Klok Ads-এর একটি কৃষি উদ্যোগ') }}
            </p>
        </main>
    </div>
</template>
