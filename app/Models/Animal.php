<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One animal's card. Its purchase cost sits on the books as livestock until it is sold. */
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

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(self::class, 'mother_id');
    }

    public function placements(): HasMany
    {
        return $this->hasMany(AnimalPlacement::class)->orderBy('from_date');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
