<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Which center an animal sat in, and for how long: the base of animal-day sharing.
 *
 * @property int $id
 * @property int $animal_id
 * @property int $cost_center_id
 * @property CarbonImmutable $from_date
 * @property CarbonImmutable|null $to_date
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Animal $animal
 * @property-read CostCenter $costCenter
 */
#[Fillable(['animal_id', 'cost_center_id', 'from_date', 'to_date'])]
class AnimalPlacement extends Model
{
    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
        ];
    }

    /** @return BelongsTo<Animal, $this> */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    /** @return BelongsTo<CostCenter, $this> */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }
}
