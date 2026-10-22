<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A piece of land the farm works: own, leased in, or shared-cropped. Area is kept in decimals
 * (শতাংশ) and shown in bigha by the farm's own bigha size.
 *
 * @property int $id
 * @property string $name
 * @property int|null $cost_center_id
 * @property float $area_decimals
 * @property string $tenure own, lease or share
 * @property string|null $owner_name
 * @property string|null $owner_phone
 * @property CarbonImmutable|null $lease_from
 * @property CarbonImmutable|null $lease_to
 * @property int|null $yearly_rent poisha
 * @property string|null $notes
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read CostCenter|null $costCenter
 */
#[Fillable(['name', 'cost_center_id', 'area_decimals', 'tenure', 'owner_name', 'owner_phone', 'lease_from', 'lease_to', 'yearly_rent', 'notes', 'is_active'])]
class Plot extends Model
{
    use Auditable;

    public const TENURES = ['own' => 'নিজের', 'lease' => 'লিজ নেওয়া', 'share' => 'বর্গা / ভাগে'];

    protected function casts(): array
    {
        return [
            'area_decimals' => 'float',
            'lease_from' => 'date',
            'lease_to' => 'date',
            'yearly_rent' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<CostCenter, $this> */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    /** @return BelongsToMany<CropSeason, $this> */
    public function seasons(): BelongsToMany
    {
        return $this->belongsToMany(CropSeason::class)->withPivot('area_decimals');
    }
}
