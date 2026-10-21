<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A target the farm is working towards ("দিনে ৩০ লিটার দুধ", "মোটাতাজা দিনে ০.৮ কেজি বাড়বে"),
 * with where it started and where it stands now.
 *
 * @property int $id
 * @property string $title
 * @property string|null $unit
 * @property float $start_value
 * @property float $target_value
 * @property float $current_value
 * @property CarbonImmutable|null $due_on
 * @property int|null $cost_center_id
 * @property CarbonImmutable|null $achieved_at
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read CostCenter|null $costCenter
 */
#[Fillable(['title', 'unit', 'start_value', 'target_value', 'current_value', 'due_on', 'cost_center_id', 'achieved_at', 'notes'])]
class Goal extends Model
{
    use Auditable;

    protected function casts(): array
    {
        return [
            'start_value' => 'float',
            'target_value' => 'float',
            'current_value' => 'float',
            'due_on' => 'date',
            'achieved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<CostCenter, $this> */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    /** 0–100: how far from the start towards the target (works for "less is better" too). */
    public function progress(): int
    {
        $span = $this->target_value - $this->start_value;

        if ($span == 0) {
            return $this->current_value == $this->target_value ? 100 : 0;
        }

        return (int) max(0, min(100, round(($this->current_value - $this->start_value) / $span * 100)));
    }
}
