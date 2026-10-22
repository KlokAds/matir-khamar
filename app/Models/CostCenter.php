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
 * A category or project of the farm (Gavi, Bokna, Motataja, Ghas jomi ...).
 * Centers nest, so a report on "Goru project" includes every child center.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $code
 * @property string $name
 * @property string $kind
 * @property bool $holds_animals
 * @property bool $is_active
 * @property int $sort
 * @property string|null $description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read CostCenter|null $parent
 * @property-read Collection<int, CostCenter> $children
 * @property-read Collection<int, Animal> $animals
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

    /** @return BelongsTo<CostCenter, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<CostCenter, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    /** @return HasMany<Animal, $this> */
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

    /**
     * Centers that are really running: on themselves and every project above them on too.
     * Turning a project off hides everything inside it; its books stay as they are.
     *
     * @return list<int>
     */
    public static function runningIds(): array
    {
        $centers = static::query()->get(['id', 'parent_id', 'is_active'])->keyBy('id');
        $ids = [];

        foreach ($centers as $center) {
            $node = $center;

            while ($node && $node->is_active) {
                $node = $node->parent_id ? $centers->get($node->parent_id) : null;

                if ($node === null) {
                    $ids[] = $center->id;
                }
            }
        }

        return $ids;
    }

    /** The animal kind kept here: goats under the goat project, cattle everywhere else. */
    public static function speciesOf(?int $centerId): string
    {
        $goat = static::query()->where('code', 'CHAGOL')->first();

        return $goat && $centerId && in_array($centerId, $goat->descendantIds(), true) ? 'goat' : 'cattle';
    }

    /**
     * Which parts of the farm are running, for the menu and the pages: cattle, goats, milk,
     * fattening, land and crops. Milk counts only once there is milk to write.
     *
     * @return array{cattle:bool, goat:bool, milk:bool, fattening:bool, land:bool}
     */
    public static function modules(): array
    {
        $running = static::runningIds();
        $codes = static::query()->whereIn('id', $running)->pluck('code')->all();
        $on = fn (string ...$c) => array_intersect($c, $codes) !== [];

        return [
            'cattle' => $on('GORU'),
            'goat' => $on('CHAGOL'),
            // Milk pages wait for the first milk: a cow that has calved, or milk already written.
            'milk' => $on('GAVI') && (MilkRecord::query()->exists() || Breeding::query()->whereNotNull('calved_on')->exists()),
            'fattening' => $on('MOTATAJA'),
            'land' => $on('GHAS', 'FOSOL', 'DHAN'),
        ];
    }
}
