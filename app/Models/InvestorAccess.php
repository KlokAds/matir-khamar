<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One project an investor's login may see. The party links the login to the investor's own
 * capital account; the share turns the project's profit into the investor's part of it.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $party_id
 * @property int $cost_center_id
 * @property float|null $share_percent
 * @property CarbonImmutable|null $since
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read Party|null $party
 * @property-read CostCenter $costCenter
 */
#[Fillable(['user_id', 'party_id', 'cost_center_id', 'share_percent', 'since'])]
class InvestorAccess extends Model
{
    use Auditable;

    protected $table = 'investor_access';

    protected function casts(): array
    {
        return [
            'share_percent' => 'float',
            'since' => 'date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Party, $this> */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    /** @return BelongsTo<CostCenter, $this> */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }
}
