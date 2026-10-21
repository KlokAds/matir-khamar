<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Something to remember at a set time. When it falls due it goes to the phones as a
 * notification; "হয়ে গেছে" closes it, "পরে" moves it on. A repeating one comes back.
 *
 * @property int $id
 * @property int|null $created_by
 * @property int|null $for_user_id
 * @property string $title
 * @property string|null $body
 * @property CarbonImmutable $due_at
 * @property string $repeat none, daily or weekly
 * @property int|null $cost_center_id
 * @property int|null $animal_id
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $done_at
 * @property int|null $done_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $author
 * @property-read User|null $recipient
 * @property-read CostCenter|null $costCenter
 * @property-read Animal|null $animal
 */
#[Fillable(['created_by', 'for_user_id', 'title', 'body', 'due_at', 'repeat', 'cost_center_id', 'animal_id', 'sent_at', 'done_at', 'done_by'])]
class Reminder extends Model
{
    use Auditable;

    public const REPEATS = ['none' => 'একবার', 'daily' => 'প্রতিদিন', 'weekly' => 'প্রতি সপ্তাহে'];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'sent_at' => 'datetime',
            'done_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'for_user_id');
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

    /**
     * Open reminders a user should see: their own, and the ones for everyone.
     *
     * @param  Builder<Reminder>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->whereNull('done_at')
            ->where(fn (Builder $q) => $q->whereNull('for_user_id')->orWhere('for_user_id', $user->id)->orWhere('created_by', $user->id));
    }
}
