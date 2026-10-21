<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Entry = 'entry';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'হিসাব অ্যাডমিন',
            self::Manager => 'খামার ম্যানেজার',
            self::Entry => 'দৈনিক এন্ট্রি',
            self::Viewer => 'শুধু দেখা (বিনিয়োগকারী/ব্যাংক)',
        };
    }

    /**
     * Abilities are registered as gates in AppServiceProvider, so a page and
     * its controller can never disagree about who may act.
     *
     * @return list<string>
     */
    public function abilities(): array
    {
        return match ($this) {
            self::Admin => ['view-reports', 'post-entries', 'post-daily', 'manage-setup', 'manage-users', 'lock-periods', 'reverse-entries', 'view-audit'],
            self::Manager => ['view-reports', 'post-entries', 'post-daily'],
            self::Entry => ['post-daily'],
            self::Viewer => ['view-reports'],
        };
    }

    /** Roles that can move money must protect the login with a second factor. */
    public function requiresTwoFactor(): bool
    {
        return in_array($this, [self::Admin, self::Manager], true);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $role) {
            $options[$role->value] = $role->label();
        }

        return $options;
    }
}
