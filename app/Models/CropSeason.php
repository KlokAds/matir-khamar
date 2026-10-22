<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One crop on some plots for one season (e.g. "আমন ২০২৬" of paddy): from land preparation to
 * harvest. Its costs are the project's expenses in that window; its harvest is the produce
 * booked against it, so profit per bigha and cost per kg come straight from the books.
 *
 * @property int $id
 * @property int $cost_center_id
 * @property string $crop
 * @property string $name
 * @property string|null $variety
 * @property CarbonImmutable $started_on
 * @property CarbonImmutable|null $sown_on
 * @property CarbonImmutable|null $harvested_on
 * @property CarbonImmutable|null $closed_on
 * @property float|null $target_mon_per_bigha
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read CostCenter $costCenter
 * @property-read Collection<int, Plot> $plots
 */
#[Fillable(['cost_center_id', 'crop', 'name', 'variety', 'started_on', 'sown_on', 'harvested_on', 'closed_on', 'target_mon_per_bigha', 'notes'])]
class CropSeason extends Model
{
    use Auditable;

    /**
     * Crops the farm grows or plans to, with their usual seasons and what each gives.
     * Free text is allowed too: a new crop needs no code.
     *
     * @var list<array{crop:string, seasons:list<string>, outputs:list<string>}>
     */
    public const CROPS = [
        ['crop' => 'ধান', 'seasons' => ['আমন', 'বোরো', 'আউশ'], 'outputs' => ['ধান', 'খড় (নিজের জমির)']],
        ['crop' => 'আলু', 'seasons' => ['রবি'], 'outputs' => ['আলু']],
        ['crop' => 'সরিষা', 'seasons' => ['রবি'], 'outputs' => ['সরিষা']],
        ['crop' => 'ভুট্টা', 'seasons' => ['রবি', 'খরিফ'], 'outputs' => ['ভুট্টা', 'ভুট্টার গাছ (গরুর খাবার)']],
    ];

    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'sown_on' => 'date',
            'harvested_on' => 'date',
            'closed_on' => 'date',
            'target_mon_per_bigha' => 'float',
        ];
    }

    /** @return BelongsTo<CostCenter, $this> */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    /** @return BelongsToMany<Plot, $this> */
    public function plots(): BelongsToMany
    {
        return $this->belongsToMany(Plot::class)->withPivot('area_decimals');
    }

    /** The reference written on harvest entries, so they can be found again. */
    public function reference(): string
    {
        return "season:{$this->id}";
    }

    public function areaDecimals(): float
    {
        return (float) $this->plots->sum(fn (Plot $plot) => (float) $plot->getRelationValue('pivot')->area_decimals);
    }
}
