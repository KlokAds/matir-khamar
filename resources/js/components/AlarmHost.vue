<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { BellRing, Check, Clock } from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { t } from '@/lib/i18n';

/**
 * The full-screen ringing alarm. It opens when an alarm reminder arrives while the app is
 * open (the service worker passes it on), or when its notification is tapped, and it keeps
 * beeping and shaking the phone until "হয়ে গেছে" or a snooze is pressed.
 */
type Alarm = { id: number; title: string; body?: string };

const alarm = ref<Alarm | null>(null);
let audio: AudioContext | null = null;
let beeper: ReturnType<typeof setInterval> | null = null;

function beep() {
    try {
        audio ??= new AudioContext();
        const now = audio.currentTime;

        // Two quick rising tones, like a farm clock alarm.
        [0, 0.25].forEach((offset, i) => {
            const osc = audio!.createOscillator();
            const gain = audio!.createGain();
            osc.type = 'square';
            osc.frequency.value = i === 0 ? 880 : 1175;
            gain.gain.setValueAtTime(0.0001, now + offset);
            gain.gain.exponentialRampToValueAtTime(0.25, now + offset + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + offset + 0.2);
            osc.connect(gain).connect(audio!.destination);
            osc.start(now + offset);
            osc.stop(now + offset + 0.22);
        });
    } catch {
        // No sound allowed (browser blocked audio): the screen and vibration still show it.
    }

    if ('vibrate' in navigator) {
        navigator.vibrate([500, 200, 500]);
    }
}

function ring(next: Alarm) {
    alarm.value = next;
    stopSound();
    beep();
    beeper = setInterval(beep, 1500);
}

function stopSound() {
    if (beeper) {
        clearInterval(beeper);
        beeper = null;
    }

    if ('vibrate' in navigator) {
        navigator.vibrate(0);
    }
}

function answer(action: 'done' | 'snooze', minutes = 10) {
    const current = alarm.value;
    stopSound();
    alarm.value = null;

    if (current) {
        router.put(
            `/reminders/${current.id}`,
            { action, minutes },
            { preserveScroll: true },
        );
    }
}

function onMessage(event: MessageEvent) {
    if (event.data?.type === 'khamar-alarm' && event.data.id) {
        ring({
            id: Number(event.data.id),
            title: event.data.title ?? '',
            body: event.data.body,
        });
    }
}

onMounted(() => {
    navigator.serviceWorker?.addEventListener('message', onMessage);

    // Opened from a tapped alarm notification: /reminders?ring=12&title=…
    const params = new URLSearchParams(window.location.search);
    const id = Number(params.get('ring'));

    if (id) {
        ring({ id, title: params.get('title') ?? '' });
        window.history.replaceState({}, '', window.location.pathname);
    }
});

onBeforeUnmount(() => {
    navigator.serviceWorker?.removeEventListener('message', onMessage);
    stopSound();
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="alarm"
            class="fixed inset-0 z-[100] flex flex-col items-center justify-center gap-6 bg-background/95 p-6 text-center backdrop-blur-sm"
            role="alertdialog"
            aria-live="assertive"
        >
            <div class="relative">
                <span
                    class="absolute inset-0 animate-ping rounded-full bg-turmeric/40"
                />
                <div
                    class="relative flex size-28 items-center justify-center rounded-full bg-turmeric text-white shadow-xl"
                >
                    <BellRing
                        class="size-14 animate-[wiggle_0.6s_ease-in-out_infinite]"
                    />
                </div>
            </div>
            <div class="max-w-sm">
                <p class="font-display text-3xl leading-tight">
                    {{ alarm.title.replace(/^🔔\s*/, '') }}
                </p>
                <p v-if="alarm.body" class="mt-2 text-muted-foreground">
                    {{ alarm.body }}
                </p>
            </div>
            <div class="grid w-full max-w-sm gap-2">
                <button
                    type="button"
                    class="flex h-14 items-center justify-center gap-2 rounded-2xl bg-primary text-lg font-semibold text-primary-foreground"
                    @click="answer('done')"
                >
                    <Check class="size-6" /> {{ t('হয়ে গেছে') }}
                </button>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        class="flex h-12 items-center justify-center gap-2 rounded-xl border border-border bg-card"
                        @click="answer('snooze', 10)"
                    >
                        <Clock class="size-4" /> {{ t('১০ মিনিট পরে') }}
                    </button>
                    <button
                        type="button"
                        class="flex h-12 items-center justify-center gap-2 rounded-xl border border-border bg-card"
                        @click="answer('snooze', 60)"
                    >
                        <Clock class="size-4" /> {{ t('১ ঘণ্টা পরে') }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
@keyframes wiggle {
    0%,
    100% {
        transform: rotate(-12deg);
    }
    50% {
        transform: rotate(12deg);
    }
}
</style>
