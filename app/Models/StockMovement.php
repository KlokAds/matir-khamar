<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $item_id
 * @property int $journal_entry_id
 * @property int|null $cost_center_id
 * @property CarbonImmutable $date
 * @property string $direction
 * @property float $qty
 * @property int $value
 * @property string|null $note
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Item $item
 * @property-read CostCenter|null $costCenter
 * @property-read JournalEntry $entry
 */
#[Fillable(['item_id', 'journal_entry_id', 'cost_center_id', 'date', 'direction', 'qty', 'value', 'note'])]
class StockMovement extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'qty' => 'float',
            'value' => 'integer',
        ];
    }

    /** @return BelongsTo<Item, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /** @return BelongsTo<CostCenter, $this> */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    /** @return BelongsTo<JournalEntry, $this> */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}
