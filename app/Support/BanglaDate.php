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
}
