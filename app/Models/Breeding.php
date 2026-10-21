<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One service of a cow or heifer (AI or bull) and what came of it: checked pregnant or not,
 * and the calving. A cow carries about 283 days, so the expected day follows the service day.
 *
 * @property int $id
 * @property int $animal_id
 * @property CarbonImmutable $served_on
 * @property bool $served_on_estimated true when only "how many months" was known
 * @property string $method ai or natural
 * @property string|null $sire
 * @property string|null $by_whom
 * @property string $result pending, pregnant or empty
 * @property CarbonImmutable|null $checked_on
 * @property CarbonImmutable $expected_on
 * @property CarbonImmutable|null $calved_on
 * @property string|null $outcome
 * @property int|null $calf_id
 * @property string|null $notes
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Animal $animal
 * @property-read Animal|null $calf
 */
#[Fillable([
    'animal_id', 'served_on', 'served_on_estimated', 'method', 'sire', 'by_whom', 'result',
    'checked_on', 'expected_on', 'calved_on', 'outcome', 'calf_id', 'notes', 'created_by',
])]
class Breeding extends Model
{
    use Auditable;

    /** Average carrying time of a cow, in days. */
    public const GESTATION_DAYS = 283;

    public const METHODS = ['ai' => 'কৃত্রিম প্রজনন (সিমেন)', 'natural' => 'ষাঁড় দিয়ে'];

    public const RESULTS = ['pending' => 'পরীক্ষা বাকি', 'pregnant' => 'গর্ভবতী', 'empty' => 'গর্ভ হয়নি'];

    public const OUTCOMES = [
        'female' => 'বকনা বাছুর',
        'male' => 'এঁড়ে বাছুর',
        'twins' => 'যমজ',
        'stillborn' => 'মৃত বাচ্চা',
        'aborted' => 'গর্ভপাত',
    ];

    protected function casts(): array
    {
        return [
            'served_on' => 'date',
            'served_on_estimated' => 'boolean',
            'checked_on' => 'date',
            'expected_on' => 'date',
            'calved_on' => 'date',
        ];
    }

    /** @return BelongsTo<Animal, $this> */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    /** @return BelongsTo<Animal, $this> */
    public function calf(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'calf_id');
    }

    /** Still carrying (or not yet known): no calving and not found empty. */
    public function isOpen(): bool
    {
        return $this->calved_on === null && $this->result !== 'empty';
    }

    public function daysCarried(): int
    {
        return (int) $this->served_on->diffInDays($this->calved_on ?? now()->startOfDay());
    }
}
