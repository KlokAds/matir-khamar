<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The farm diary: what happened today, a mistake not to repeat, a lesson, an idea to try.
 * A note can carry a follow-up day, and then shows on the dashboard until it is done.
 *
 * @property int $id
 * @property CarbonImmutable $date
 * @property string $kind
 * @property string $title
 * @property string|null $body
 * @property int|null $cost_center_id
 * @property int|null $animal_id
 * @property CarbonImmutable|null $follow_up_on
 * @property CarbonImmutable|null $done_at
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read CostCenter|null $costCenter
 * @property-read Animal|null $animal
 * @property-read User|null $author
 */
#[Fillable(['date', 'kind', 'title', 'body', 'cost_center_id', 'animal_id', 'follow_up_on', 'done_at', 'created_by'])]
class FarmNote extends Model
{
    use Auditable;

    public const KINDS = [
        'daily' => 'আজকের কথা',
        'mistake' => 'ভুল — আর করা যাবে না',
        'lesson' => 'শিক্ষা',
        'idea' => 'নতুন চিন্তা',
        'health' => 'স্বাস্থ্য / চিকিৎসা',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'follow_up_on' => 'date',
            'done_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<CostCenter, $this> */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    /** @return BelongsTo<Animal, $this> */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
