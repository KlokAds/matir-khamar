<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One planned repayment (or expected return) of a debt: when, how much, how much of it is done.
 *
 * @property int $id
 * @property int $debt_id
 * @property CarbonImmutable $due_on
 * @property int $amount poisha
 * @property int $paid poisha
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Debt $debt
 */
#[Fillable(['debt_id', 'due_on', 'amount', 'paid'])]
class DebtInstallment extends Model
{
    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'amount' => 'integer',
            'paid' => 'integer',
        ];
    }

    /** @return BelongsTo<Debt, $this> */
    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    public function left(): int
    {
        return max(0, $this->amount - $this->paid);
    }
}
