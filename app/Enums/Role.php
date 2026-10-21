<?php

namespace App\Enums;

/**
 * The built-in roles and what they start with. The live list of roles and their abilities is
 * the `roles` table (App\Models\AccessRole), which the owner edits in Setup → Roles; this enum
 * only seeds it and names the roles the code refers to directly.
 */
enum Role: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Entry = 'entry';
    case Viewer = 'viewer';

    /** Every ability a role can be given, with the Bangla description shown in Setup → Roles. */
    public const ABILITIES = [
        'view-reports' => 'হিসাব, খাতা, ক্যাটাগরি ও রিপোর্ট দেখা',
        'post-daily' => 'দুধের হিসাব ও গরুকে খাবার দেওয়া লেখা',
        'post-entries' => 'টাকার এন্ট্রি: খরচ, আয়, মাল কেনা, গরু কেনা-বেচা, খরচ ভাগ',
        'reverse-entries' => 'ভুল এন্ট্রি বাতিল (উল্টো এন্ট্রি)',
        'manage-setup' => 'হিসাব খাত, ক্যাটাগরি, পার্টি, স্টকের মাল, বাজেট সাজানো',
        'manage-import' => 'বিলিং (billing.klokads.com) থেকে আনা',
        'lock-periods' => 'মাসের হিসাব লক/খোলা',
        'view-audit' => 'অডিট লগ দেখা',
        'manage-users' => 'ব্যবহারকারী যোগ/বদল/বন্ধ',
        'manage-roles' => 'রোল ও অনুমতি বদল',
        'manage-update' => 'সাইট আপডেট',
    ];

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'হিসাব অ্যাডমিন',
            self::Manager => 'খামার ম্যানেজার',
            self::Entry => 'দৈনিক এন্ট্রি',
            self::Viewer => 'শুধু দেখা (বিনিয়োগকারী/ব্যাংক)',
        };
    }

    /** @return list<string> */
    public function abilities(): array
    {
        return match ($this) {
            self::Admin => array_keys(self::ABILITIES),
            self::Manager => ['view-reports', 'post-entries', 'post-daily'],
            self::Entry => ['post-daily'],
            self::Viewer => ['view-reports'],
        };
    }

    /** Roles that can move money start with two-factor login required. */
    public function requiresTwoFactor(): bool
    {
        return in_array($this, [self::Admin, self::Manager], true);
    }
}
