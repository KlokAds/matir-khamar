<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Chart of accounts. Asset and expense accounts grow with debits; liability,
 * equity and income accounts grow with credits.
 */
#[Fillable(['parent_id', 'code', 'name', 'type', 'subtype', 'party_id', 'is_system', 'is_active'])]
class Account extends Model
{
    use Auditable;

    public const TYPES = [
        'asset' => 'সম্পদ',
        'liability' => 'দায়',
        'equity' => 'মূলধন',
        'income' => 'আয়',
        'expense' => 'ব্যয়',
    ];

    /** Accounts money can be paid from or received into. */
    public const MONEY_SUBTYPES = ['cash', 'bank', 'wallet'];

    /**
     * Internal transfers (own grass valued and fed to own cattle) show inside
     * each category but cancel out in the farm-wide totals.
     */
    public const INTERNAL_SUBTYPE = 'internal';

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function isDebitNormal(): bool
    {
        return in_array($this->type, ['asset', 'expense'], true);
    }

    /** Balance in poisha, positive in the account's normal direction. */
    public function balance(): int
    {
        $totals = $this->lines()->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')->first();
        $net = (int) $totals->d - (int) $totals->c;

        return $this->isDebitNormal() ? $net : -$net;
    }

    public function scopeMoney(Builder $query): void
    {
        $query->whereIn('subtype', self::MONEY_SUBTYPES)->where('is_active', true);
    }

    public function scopeOfType(Builder $query, string $type): void
    {
        $query->where('type', $type)->where('is_active', true);
    }

    public static function byCode(string $code): self
    {
        return static::query()->where('code', $code)->firstOrFail();
    }
}
