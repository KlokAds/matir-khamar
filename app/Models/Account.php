<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Chart of accounts. Asset and expense accounts grow with debits; liability,
 * equity and income accounts grow with credits.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $code
 * @property string $name
 * @property string $type
 * @property string|null $subtype
 * @property int|null $party_id
 * @property bool $is_system
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Party|null $party
 * @property-read Collection<int, JournalLine> $lines
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

    /**
     * Places money can sit, and be paid from or received into: cash in hand, a bank, a mobile
     * wallet, or money left with a person (an advance to the farm hand for daily costs).
     */
    public const MONEY_SUBTYPES = ['cash', 'bank', 'wallet', 'person'];

    public const MONEY_PLACES = [
        'cash' => 'নগদ (হাতে)',
        'bank' => 'ব্যাংক',
        'wallet' => 'মোবাইল ওয়ালেট (বিকাশ/নগদ/রকেট)',
        'person' => 'কারো কাছে রাখা',
    ];

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

    /** @return BelongsTo<Party, $this> */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    /** @return HasMany<JournalLine, $this> */
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
        $totals = $this->lines()->toBase()->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')->first();
        $net = (int) data_get($totals, 'd') - (int) data_get($totals, 'c');

        return $this->isDebitNormal() ? $net : -$net;
    }

    /** @param  Builder<self>  $query */
    public function scopeMoney(Builder $query): void
    {
        $query->whereIn('subtype', self::MONEY_SUBTYPES)->where('is_active', true);
    }

    /** @param  Builder<self>  $query */
    public function scopeOfType(Builder $query, string $type): void
    {
        $query->where('type', $type)->where('is_active', true);
    }

    public static function byCode(string $code): self
    {
        return static::query()->where('code', $code)->firstOrFail();
    }
}
