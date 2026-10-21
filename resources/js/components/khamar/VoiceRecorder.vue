<script setup lang="ts">
import { Mic, Square, X } from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import { t } from '@/lib/i18n';

/**
 * A big, thumb-sized voice recorder for diary notes. One tap starts, one tap stops;
 * it stops by itself after three minutes and hands back a ready-to-upload File.
 */
const emit = defineEmits<{
    recorded: [file: File];
    close: [];
}>();

const MAX_SECONDS = 180;
const TYPES = [
    'audio/webm;codecs=opus',
    'audio/webm',
    'audio/mp4',
    'audio/ogg',
];

const recording = ref(false);
const starting = ref(false);
const elapsed = ref(0);
const error = ref('');

let recorder: MediaRecorder | null = null;
let stream: MediaStream | null = null;
let chunks: Blob[] = [];
let timer: ReturnType<typeof setInterval> | null = null;
let startedAt = 0;

const clock = computed(() => {
    const m = Math.floor(elapsed.value / 60);
    const s = elapsed.value % 60;

    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
});

const supported = () =>
    typeof window !== 'undefined' &&
    !!navigator.mediaDevices?.getUserMedia &&
    typeof window.MediaRecorder !== 'undefined';

function pickType(): string {
    return TYPES.find((t) => MediaRecorder.isTypeSupported(t)) ?? '';
}

function extFor(type: string): string {
    if (type.includes('mp4') || type.includes('aac')) {
        return 'm4a';
    }

    if (type.includes('ogg')) {
        return 'ogg';
    }

    if (type.includes('mpeg')) {
        return 'mp3';
    }

    return 'webm';
}

function fileName(ext: string): string {
    const d = new Date();
    const p = (n: number) => String(n).padStart(2, '0');

    return `voice-${d.getFullYear()}${p(d.getMonth() + 1)}${p(d.getDate())}-${p(d.getHours())}${p(d.getMinutes())}.${ext}`;
}

function clearTimer() {
    if (timer) {
        clearInterval(timer);
        timer = null;
    }
}

function stopTracks() {
    stream?.getTracks().forEach((t) => t.stop());
    stream = null;
}

async function start() {
    error.value = '';

    if (!supported()) {
        error.value = 'এই ব্রাউজারে রেকর্ড হয় না';

        return;
    }

    starting.value = true;

    try {
        stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    } catch (e) {
        starting.value = false;
        const name = (e as DOMException)?.name;
        error.value =
            name === 'NotAllowedError' || name === 'SecurityError'
                ? 'মাইক্রোফোনের অনুমতি দিন'
                : name === 'NotFoundError'
                  ? 'কোনো মাইক্রোফোন পাওয়া যায়নি'
                  : 'এই ব্রাউজারে রেকর্ড হয় না';

        return;
    }

    try {
        const mimeType = pickType();
        recorder = mimeType
            ? new MediaRecorder(stream, { mimeType })
            : new MediaRecorder(stream);
    } catch {
        starting.value = false;
        stopTracks();
        error.value = 'এই ব্রাউজারে রেকর্ড হয় না';

        return;
    }

    chunks = [];
    recorder.ondataavailable = (ev) => {
        if (ev.data && ev.data.size > 0) {
            chunks.push(ev.data);
        }
    };
    recorder.onstop = () => {
        const type = (recorder?.mimeType || chunks[0]?.type || 'audio/webm')
            .split(';')[0]
            .trim();
        const blob = new Blob(chunks, { type });
        chunks = [];
        recorder = null;
        stopTracks();

        if (blob.size > 0) {
            emit(
                'recorded',
                new File([blob], fileName(extFor(type)), { type }),
            );
        }
    };

    recorder.start(1000);
    startedAt = Date.now();
    elapsed.value = 0;
    recording.value = true;
    starting.value = false;

    timer = setInterval(() => {
        elapsed.value = Math.floor((Date.now() - startedAt) / 1000);

        if (elapsed.value >= MAX_SECONDS) {
            stop();
        }
    }, 250);
}

function stop() {
    clearTimer();
    recording.value = false;

    if (recorder && recorder.state !== 'inactive') {
        recorder.stop();
    } else {
        stopTracks();
    }
}

onBeforeUnmount(() => {
    clearTimer();

    if (recorder) {
        recorder.onstop = null;

        if (recorder.state !== 'inactive') {
            recorder.stop();
        }

        recorder = null;
    }

    stopTracks();
});
</script>

<template>
    <div
        class="relative flex flex-col items-center gap-3 rounded-lg border border-paddy/30 bg-paddy/5 px-4 py-5"
    >
        <button
            v-if="!recording"
            type="button"
            class="absolute top-2 right-2 rounded-md p-1.5 text-muted-foreground hover:bg-accent"
            :aria-label="t('বন্ধ করুন')"
            @click="emit('close')"
        >
            <X class="size-4" />
        </button>

        <button
            v-if="!recording"
            type="button"
            class="flex size-20 items-center justify-center rounded-full bg-paddy text-primary-foreground shadow-md transition-transform active:scale-95 disabled:opacity-60"
            :disabled="starting"
            :aria-label="t('রেকর্ড শুরু করুন')"
            @click="start"
        >
            <Mic class="size-9" />
        </button>
        <button
            v-else
            type="button"
            class="flex size-20 animate-pulse items-center justify-center rounded-full bg-destructive text-white shadow-md transition-transform active:scale-95"
            :aria-label="t('রেকর্ড থামান')"
            @click="stop"
        >
            <Square class="size-8 fill-current" />
        </button>

        <div class="text-center">
            <p class="num font-display text-2xl tabular-nums">{{ clock }}</p>
            <p class="text-xs text-muted-foreground">
                {{
                    recording
                        ? t('বলছেন… থামাতে লাল বোতামে চাপুন (সর্বোচ্চ ৩ মিনিট)')
                        : t('চাপ দিয়ে বলা শুরু করুন')
                }}
            </p>
        </div>

        <p v-if="error" class="text-sm font-medium text-destructive">
            {{ t(error) }}
        </p>
    </div>
</template>
