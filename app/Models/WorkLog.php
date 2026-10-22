<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One worker's work on one day for one project. It costs nothing in the books until it is
 * paid; paying posts the wage and marks the log with that entry.
 *
 * @property int $id
 * @property CarbonImmutable $date
 * @property int $worker_id
 * @property int $cost_center_id
 * @property int|null $crop_season_id
 * @property string $work_type
 * @property float $days 0.5 = half a day
 * @property int $rate poisha per day
 * @property int $amount poisha
 * @property string|null $notes
 * @property int|null $paid_entry_id
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Worker $worker
 * @property-read CostCenter $costCenter
 * @property-read CropSeason|null $season
 * @property-read JournalEntry|null $paidEntry
 */
#[Fillable(['date', 'worker_id', 'cost_center_id', 'crop_season_id', 'work_type', 'days', 'rate', 'amount', 'notes', 'paid_entry_id', 'created_by'])]
class WorkLog extends Model
{
    use Auditable;

    /** Usual farm jobs, offered as chips; anything else can be typed. */
    public const WORK_TYPES = ['চাষ', 'রোপণ', 'নিড়ানি', 'সেচ', 'কাটা ও মাড়াই', 'গোয়াল ও গরু দেখা', 'ঘাস কাটা', 'অন্য কাজ'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'days' => 'float',
            'rate' => 'integer',
            'amount' => 'integer',
        ];
    }

    /** Paid, and the payment still stands (not cancelled by a reversal). */
    public function isPaid(): bool
    {
        return $this->paid_entry_id !== null && $this->paidEntry?->reversed_by_id === null;
    }

    /**
     * Not yet paid: never paid, or the payment was cancelled by a reversal.
     *
     * @param  Builder<self>  $query
     */
    public function scopeUnpaid(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('paid_entry_id')
            ->orWhereHas('paidEntry', fn (Builder $e) => $e->whereNotNull('reversed_by_id')));
    }

    /** @return BelongsTo<Worker, $this> */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    /** @return BelongsTo<CostCenter, $this> */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    /** @return BelongsTo<CropSeason, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(CropSeason::class, 'crop_season_id');
    }

    /** @return BelongsTo<JournalEntry, $this> */
    public function paidEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'paid_entry_id');
    }
}
