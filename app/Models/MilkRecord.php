<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property CarbonImmutable $date
 * @property string $session
 * @property int $cost_center_id
 * @property int|null $animal_id
 * @property float $liters
 * @property int $recorded_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Animal|null $animal
 * @property-read User $recorder
 */
#[Fillable(['date', 'session', 'cost_center_id', 'animal_id', 'liters', 'recorded_by'])]
class MilkRecord extends Model
{
    /** Milking times; most farms use two, some three or a night milking. */
    public const SESSIONS = ['morning' => 'সকাল', 'noon' => 'দুপুর', 'evening' => 'বিকাল', 'night' => 'রাত'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'liters' => 'float',
        ];
    }

    /** @return BelongsTo<Animal, $this> */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
