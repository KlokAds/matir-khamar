<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One line of the Udyog financing budget (Part-4 Kha of the application).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $details
 * @property int $approved_amount
 * @property int $sort
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, JournalLine> $lines
 */
#[Fillable(['code', 'name', 'details', 'approved_amount', 'sort'])]
class BudgetHead extends Model
{
    use Auditable;

    protected function casts(): array
    {
        return ['approved_amount' => 'integer'];
    }

    /** @return HasMany<JournalLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
