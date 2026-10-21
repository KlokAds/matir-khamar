<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Dates the way the farm reads them, in Bangla, or in English when the reader picked English.
 * Pass $locale 'bn' for text that is stored (ledger descriptions), so the books stay in one language.
 */
final class BanglaDate
{
    private const MONTHS = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];

    private const MONTHS_EN = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    /** "অক্টোবর 2026" */
    public static function month(CarbonInterface $date, ?string $locale = null): string
    {
        return self::months($locale)[$date->month - 1].' '.$date->year;
    }

    /** "15 মার্চ 2027" */
    public static function day(CarbonInterface $date, ?string $locale = null): string
    {
        return $date->day.' '.self::months($locale)[$date->month - 1].' '.$date->year;
    }

    /** A day count in months and days, the way people say it: "4 মাস 5 দিন" ("4 months 5 days"). */
    public static function span(int $days, ?string $locale = null): string
    {
        $months = intdiv(max(0, $days), 30);
        $rest = max(0, $days) % 30;

        if (self::english($locale)) {
            $monthText = $months === 1 ? '1 month' : "{$months} months";
            $dayText = $rest === 1 ? '1 day' : "{$rest} days";

            return match (true) {
                $months === 0 => $dayText,
                $rest === 0 => $monthText,
                default => "{$monthText} {$dayText}",
            };
        }

        return match (true) {
            $months === 0 => "{$rest} দিন",
            $rest === 0 => "{$months} মাস",
            default => "{$months} মাস {$rest} দিন",
        };
    }

    /** @return list<string> */
    private static function months(?string $locale): array
    {
        return self::english($locale) ? self::MONTHS_EN : self::MONTHS;
    }

    private static function english(?string $locale): bool
    {
        return ($locale ?? app()->getLocale()) === 'en';
    }
}
