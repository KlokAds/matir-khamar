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
 * One animal's card. Its purchase cost sits on the books as livestock until it is sold.
 *
 * @property int $id
 * @property string $tag
 * @property string|null $name
 * @property string $sex
 * @property string|null $breed
 * @property int $cost_center_id
 * @property int|null $mother_id
 * @property string $status
 * @property string $acquisition
 * @property CarbonImmutable $acquired_on
 * @property CarbonImmutable|null $birth_date
 * @property int $purchase_cost
 * @property int $book_value
 * @property CarbonImmutable|null $disposed_on
 * @property int|null $sale_amount
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read CostCenter $costCenter
 * @property-read Animal|null $mother
 * @property-read Collection<int, AnimalPlacement> $placements
 * @property-read Collection<int, JournalLine> $lines
 */
#[Fillable([
    'tag', 'name', 'sex', 'breed', 'cost_center_id', 'mother_id', 'status', 'acquisition',
    'acquired_on', 'birth_date', 'purchase_cost', 'book_value', 'disposed_on', 'sale_amount', 'notes',
])]
class Animal extends Model
{
    use Auditable;

    public const STATUSES = ['active' => 'খামারে আছে', 'sold' => 'বিক্রি হয়েছে', 'dead' => 'মৃত'];

    public const ACQUISITIONS = ['purchase' => 'ক্রয়', 'born' => 'খামারে জন্ম', 'opening' => 'আগে থেকেই ছিল'];

    protected function casts(): array
    {
        return [
            'acquired_on' => 'date',
            'birth_date' => 'date',
            'disposed_on' => 'date',
            'purchase_cost' => 'integer',
            'book_value' => 'integer',
            'sale_amount' => 'integer',
        ];
    }

    /** @return BelongsTo<CostCenter, $this> */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    /** @return BelongsTo<Animal, $this> */
    public function mother(): BelongsTo
    {
        return $this->belongsTo(self::class, 'mother_id');
    }

    /** @return HasMany<AnimalPlacement, $this> */
    public function placements(): HasMany
    {
        return $this->hasMany(AnimalPlacement::class)->orderBy('from_date');
    }

    /** @return HasMany<JournalLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
