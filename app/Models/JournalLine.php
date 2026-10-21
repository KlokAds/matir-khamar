<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property int $id
 * @property int $journal_entry_id
 * @property int $account_id
 * @property int|null $cost_center_id
 * @property int|null $animal_id
 * @property int|null $budget_head_id
 * @property int $debit
 * @property int $credit
 * @property string|null $memo
 * @property-read JournalEntry $entry
 * @property-read Account $account
 * @property-read CostCenter|null $costCenter
 * @property-read Animal|null $animal
 * @property-read BudgetHead|null $budgetHead
 */
#[Fillable(['journal_entry_id', 'account_id', 'cost_center_id', 'animal_id', 'budget_head_id', 'debit', 'credit', 'memo'])]
class JournalLine extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'debit' => 'integer',
            'credit' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Posted journal lines cannot be edited.');
        });

        static::deleting(function () {
            throw new LogicException('Posted journal lines cannot be deleted.');
        });
    }

    /** @return BelongsTo<JournalEntry, $this> */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
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

    /** @return BelongsTo<BudgetHead, $this> */
    public function budgetHead(): BelongsTo
    {
        return $this->belongsTo(BudgetHead::class);
    }
}
