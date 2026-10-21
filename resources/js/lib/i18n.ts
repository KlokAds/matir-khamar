import { usePage } from '@inertiajs/vue3';

/**
 * Bangla is written straight into the pages; English comes from the dictionaries in
 * resources/js/lang/en/*.json, keyed by the Bangla text itself. A phrase missing from the
 * dictionary simply shows in Bangla, so nothing ever goes blank.
 *
 *   t('দুধের হিসাব')                       → "Milk records" in English
 *   t('মোট :count টি', { count: 5 })       → replacements work in both languages
 */
const dictionaries = import.meta.glob<Record<string, string>>(
    '../lang/en/*.json',
    {
        eager: true,
        import: 'default',
    },
);

const english: Record<string, string> = Object.assign(
    {},
    ...Object.values(dictionaries),
);

export type Locale = 'bn' | 'en';

export function currentLocale(): Locale {
    try {
        return (usePage().props.locale as Locale) ?? 'bn';
    } catch {
        // Outside a component (no page context): read what the server rendered.
        return document.documentElement.lang === 'en' ? 'en' : 'bn';
    }
}

export function t(
    text: string,
    replace: Record<string, string | number> = {},
): string {
    let out = currentLocale() === 'en' ? (english[text] ?? text) : text;

    for (const [key, value] of Object.entries(replace)) {
        out = out.replaceAll(`:${key}`, String(value));
    }

    return out;
}

/** Translate a Record of labels (e.g. the kinds/units lists the server sends). */
export function tAll<T extends Record<string, string>>(labels: T): T {
    return Object.fromEntries(
        Object.entries(labels).map(([key, value]) => [key, t(value)]),
    ) as T;
}
