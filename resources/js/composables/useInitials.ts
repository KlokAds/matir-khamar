export type UseInitialsReturn = {
    getInitials: (fullName?: string) => string;
};

const graphemes =
    typeof Intl !== 'undefined' && 'Segmenter' in Intl
        ? new Intl.Segmenter(undefined, { granularity: 'grapheme' })
        : null;

/**
 * The first letter as a reader sees it. In Bangla a letter carries its vowel sign and joined
 * consonants (হি, অ্যা, ক্ষ), so splitting by code point would leave a broken half (হ for হি).
 */
function getInitial(name: string): string {
    if (graphemes) {
        for (const { segment } of graphemes.segment(name)) {
            return segment;
        }

        return '';
    }

    return Array.from(name)[0] ?? '';
}

const isBangla = (text: string) => /[ঀ-৿]/u.test(text);

export function getInitials(fullName?: string): string {
    if (!fullName) {
        return '';
    }

    const names = fullName.trim().split(/\s+/u).filter(Boolean);

    if (names.length === 0) {
        return '';
    }

    // A Bangla letter is already wide; two of them would not fit the badge, so use one.
    if (names.length === 1 || isBangla(fullName)) {
        return getInitial(names[0]).toUpperCase();
    }

    return `${getInitial(names[0])}${getInitial(names[names.length - 1])}`.toUpperCase();
}

export function useInitials(): UseInitialsReturn {
    return { getInitials };
}
