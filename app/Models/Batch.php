<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A group of animals bought together to fatten and sell, numbered within its category
 * (মোটাতাজা ব্যাচ-১, ব্যাচ-২…), so each round's days, cost and gain can be read on its own.
 *
 * @property int $id
 * @property int $cost_center_id
 * @property int $number
 * @property string|null $name
 * @property CarbonImmutable $started_on
 * @property int $target_days
 * @property float|null $target_gain_kg daily gain aimed for, per animal
 * @property CarbonImmutable|null $closed_on
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read CostCenter $costCenter
 * @property-read Collection<int, Animal> $animals
 */
#[Fillable(['cost_center_id', 'number', 'name', 'started_on', 'target_days', 'target_gain_kg', 'closed_on', 'notes'])]
class Batch extends Model
{
    use Auditable;

    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'closed_on' => 'date',
            'target_days' => 'integer',
            'target_gain_kg' => 'float',
        ];
    }

    /** @return BelongsTo<CostCenter, $this> */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    /** @return HasMany<Animal, $this> */
    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }

    public function label(): string
    {
        return $this->name ?: "ব্যাচ-{$this->number}";
    }

    /** Days the batch has run: to its close, or to today while it is open. */
    public function daysRunning(): int
    {
        return (int) $this->started_on->diffInDays($this->closed_on ?? now()->startOfDay()) + 1;
    }

    public static function nextNumber(int $costCenterId): int
    {
        return (int) self::query()->where('cost_center_id', $costCenterId)->max('number') + 1;
    }
}
