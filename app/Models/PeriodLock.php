<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Date;

/**
 * A closed month: nothing can be posted into it any more.
 *
 * @property int $id
 * @property CarbonImmutable $month
 * @property int $locked_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $locker
 */
#[Fillable(['month', 'locked_by'])]
class PeriodLock extends Model
{
    use Auditable;

    protected function casts(): array
    {
        return ['month' => 'date'];
    }

    public static function isLocked(CarbonInterface|string $date): bool
    {
        $month = Date::parse($date)->startOfMonth()->toDateString();

        return static::query()->whereDate('month', $month)->exists();
    }

    /** @return BelongsTo<User, $this> */
    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
}
