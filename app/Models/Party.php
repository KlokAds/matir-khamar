<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Anyone the farm deals with: buyers, suppliers, investors, lenders, staff.
 *
 * @property int $id
 * @property string $type
 * @property string $name
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $notes
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, Account> $accounts
 */
#[Fillable(['type', 'name', 'phone', 'address', 'notes', 'is_active'])]
class Party extends Model
{
    use Auditable;

    public const TYPES = [
        'customer' => 'ক্রেতা',
        'supplier' => 'সরবরাহকারী',
        'investor' => 'বিনিয়োগকারী / অংশীদার',
        'lender' => 'ঋণদাতা (ব্যাংক)',
        'employee' => 'কর্মী',
        'other' => 'অন্যান্য',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return HasMany<Account, $this> */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
