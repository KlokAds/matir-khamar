<?php

namespace App\Support;

use Carbon\CarbonInterface;

final class BanglaDate
{
    private const MONTHS = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];

    /** "অক্টোবর 2026" */
    public static function month(CarbonInterface $date): string
    {
        return self::MONTHS[$date->month - 1].' '.$date->year;
    }

    /** "15 মার্চ 2027" */
    public static function day(CarbonInterface $date): string
    {
        return $date->day.' '.self::MONTHS[$date->month - 1].' '.$date->year;
    }

    /** A day count in months and days, the way people say it: "4 মাস 5 দিন". */
    public static function span(int $days): string
    {
        $months = intdiv(max(0, $days), 30);
        $rest = max(0, $days) % 30;

        return match (true) {
            $months === 0 => "{$rest} দিন",
            $rest === 0 => "{$months} মাস",
            default => "{$months} মাস {$rest} দিন",
        };
    }
}
