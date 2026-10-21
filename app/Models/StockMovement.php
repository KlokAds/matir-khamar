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
 * @property string|null $session morning, noon, evening or night for feed given
 * @property float $qty
 * @property float|null $land_decimals land the grass came from or the fertiliser went on (শতাংশ)
 * @property int|null $head_count animals in the category on the day it was fed
 * @property int $value
 * @property string|null $note
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Item $item
 * @property-read CostCenter|null $costCenter
 * @property-read JournalEntry $entry
 */
#[Fillable(['item_id', 'journal_entry_id', 'cost_center_id', 'date', 'direction', 'session', 'qty', 'land_decimals', 'head_count', 'value', 'note'])]
class StockMovement extends Model
{
    /** Feeding times through the day. */
    public const SESSIONS = ['morning' => 'সকাল', 'noon' => 'দুপুর', 'evening' => 'বিকাল', 'night' => 'রাত'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'qty' => 'float',
            'land_decimals' => 'float',
            'head_count' => 'integer',
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
