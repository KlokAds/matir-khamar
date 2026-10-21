<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One line of the Udyog financing budget (Part-4 Kha of the application). */
#[Fillable(['code', 'name', 'details', 'approved_amount', 'sort'])]
class BudgetHead extends Model
{
    use Auditable;

    protected function casts(): array
    {
        return ['approved_amount' => 'integer'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
