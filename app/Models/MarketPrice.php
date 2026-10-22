<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One price seen at a haat or market on a day (বাজারদর), in poisha per unit.
 *
 * @property int $id
 * @property CarbonImmutable $date
 * @property string $commodity
 * @property string $unit kg, mon (মণ = 40 kg), liter, piece, head or bundle
 * @property int $price poisha per unit
 * @property string|null $place
 * @property string|null $quality
 * @property int|null $item_id
 * @property string|null $notes
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Item|null $item
 */
#[Fillable(['date', 'commodity', 'unit', 'price', 'place', 'quality', 'item_id', 'notes', 'created_by'])]
class MarketPrice extends Model
{
    use Auditable;

    public const UNITS = [
        'kg' => 'কেজি',
        'mon' => 'মণ',
        'liter' => 'লিটার',
        'piece' => 'পিস',
        'head' => 'মাথাপিছু',
        'bundle' => 'আঁটি',
    ];

    /** Kilograms in one unit, for the units that weigh. */
    public const KG_PER_UNIT = ['kg' => 1, 'mon' => 40];

    /** What the farm usually watches, with the unit it is quoted in at the haat. */
    public const PRESETS = [
        ['commodity' => 'ধান', 'unit' => 'mon'],
        ['commodity' => 'চাল মোটা', 'unit' => 'kg'],
        ['commodity' => 'চাল চিকন', 'unit' => 'kg'],
        ['commodity' => 'খড়', 'unit' => 'bundle'],
        ['commodity' => 'খড়', 'unit' => 'kg'],
        ['commodity' => 'ভুসি', 'unit' => 'kg'],
        ['commodity' => 'দানাদার খাদ্য', 'unit' => 'kg'],
        ['commodity' => 'খৈল', 'unit' => 'kg'],
        ['commodity' => 'কাঁচা ঘাস', 'unit' => 'kg'],
        ['commodity' => 'দুধ', 'unit' => 'liter'],
        ['commodity' => 'আলু', 'unit' => 'kg'],
        ['commodity' => 'সরিষা', 'unit' => 'mon'],
        ['commodity' => 'ভুট্টা', 'unit' => 'mon'],
        ['commodity' => 'ইউরিয়া সার', 'unit' => 'kg'],
        ['commodity' => 'গরু — মোটাতাজা', 'unit' => 'head'],
        ['commodity' => 'গাভী', 'unit' => 'head'],
        ['commodity' => 'বাছুর', 'unit' => 'head'],
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'price' => 'integer',
            'item_id' => 'integer',
        ];
    }

    /** Poisha per kg for a price in a weighing unit; null for head, piece, bundle or liter. */
    public static function toPerKg(int|float $price, string $unit): ?float
    {
        $kg = self::KG_PER_UNIT[$unit] ?? null;

        return $kg ? $price / $kg : null;
    }

    /** @return BelongsTo<Item, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
