<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One weighing (or a tape-measure estimate). A run of them shows the daily gain.
 *
 * @property int $id
 * @property int $animal_id
 * @property CarbonImmutable $date
 * @property float $kg
 * @property string|null $note
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Animal $animal
 */
#[Fillable(['animal_id', 'date', 'kg', 'note', 'created_by'])]
class AnimalWeight extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'kg' => 'float',
        ];
    }

    /** @return BelongsTo<Animal, $this> */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
