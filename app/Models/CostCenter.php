<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A category or project of the farm (Gavi, Bokna, Motataja, Ghas jomi ...).
 * Centers nest, so a report on "Goru project" includes every child center.
 */
#[Fillable(['parent_id', 'code', 'name', 'kind', 'holds_animals', 'is_active', 'sort', 'description'])]
class CostCenter extends Model
{
    use Auditable;

    public const KINDS = [
        'group' => 'প্রজেক্ট (গ্রুপ)',
        'livestock' => 'পশুপালন',
        'crop' => 'চাষাবাদ / ঘাস',
        'fishery' => 'মৎস্য',
        'processing' => 'প্রক্রিয়াজাত পণ্য',
        'store' => 'দোকান / বিক্রয়',
        'overhead' => 'সাধারণ (পরে ভাগ হবে)',
        'other' => 'অন্যান্য',
    ];

    protected function casts(): array
    {
        return [
            'holds_animals' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }

    /**
     * This center and every center below it.
     *
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $parents = static::query()->pluck('parent_id', 'id');
        $ids = [$this->id];

        for ($i = 0; $i < count($ids); $i++) {
            foreach ($parents as $id => $parentId) {
                if ($parentId === $ids[$i]) {
                    $ids[] = $id;
                }
            }
        }

        return $ids;
    }
}
