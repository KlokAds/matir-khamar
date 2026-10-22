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
 * Someone who works on the farm: a day labourer (দিনমজুর) paid by the day, or a monthly hand
 * (মাসিক বেতন) whose days are logged at salary ÷ 30.
 *
 * @property int $id
 * @property string $name
 * @property string|null $phone
 * @property string $kind daily or monthly
 * @property int|null $daily_rate poisha
 * @property int|null $monthly_salary poisha
 * @property int|null $cost_center_id the project they usually work on
 * @property bool $is_active
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read CostCenter|null $costCenter
 * @property-read Collection<int, WorkLog> $logs
 */
#[Fillable(['name', 'phone', 'kind', 'daily_rate', 'monthly_salary', 'cost_center_id', 'is_active', 'notes'])]
class Worker extends Model
{
    use Auditable;

    public const KINDS = ['daily' => 'দিনমজুর', 'monthly' => 'মাসিক বেতন'];

    protected function casts(): array
    {
        return [
            'daily_rate' => 'integer',
            'monthly_salary' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** A day's pay in poisha: the daily rate, or a monthly salary over 30 days. */
    public function dayRate(): int
    {
        if ($this->kind === 'monthly' && $this->monthly_salary) {
            return (int) round($this->monthly_salary / 30);
        }

        return (int) ($this->daily_rate ?? 0);
    }

    /** @return BelongsTo<CostCenter, $this> */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    /** @return HasMany<WorkLog, $this> */
    public function logs(): HasMany
    {
        return $this->hasMany(WorkLog::class);
    }
}
