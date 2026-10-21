<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A lease (or other advance) paid at once and spread over its months. */
#[Fillable(['journal_entry_id', 'prepaid_account_id', 'expense_account_id', 'cost_center_id', 'description', 'total', 'months', 'start_month', 'amortized_months'])]
class PrepaidSchedule extends Model
{
    protected function casts(): array
    {
        return [
            'start_month' => 'date',
            'total' => 'integer',
        ];
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    /** Amount for the n-th month (0-based); the last month absorbs the rounding. */
    public function amountForMonth(int $index): int
    {
        $base = intdiv($this->total, $this->months);

        return $index === $this->months - 1 ? $this->total - $base * ($this->months - 1) : $base;
    }
}
