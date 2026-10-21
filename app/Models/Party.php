<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Anyone the farm deals with: buyers, suppliers, investors, lenders, staff. */
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

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
