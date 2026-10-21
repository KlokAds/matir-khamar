<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

/**
 * Encrypted key/value settings. Not audited through the Auditable trait on
 * purpose: that would copy secrets into the audit log. Changes are logged
 * by the caller without their values.
 */
#[Fillable(['key', 'value', 'updated_by'])]
#[Hidden(['value'])]
class Setting extends Model
{
    protected function casts(): array
    {
        return ['value' => 'encrypted'];
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->first()?->value ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => auth()->id()]);
    }

    public static function has(string $key): bool
    {
        return static::query()->where('key', $key)->whereNotNull('value')->exists();
    }
}
