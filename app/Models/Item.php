<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A stocked material: vushi, danadar, kacha ghas, medicine. Purchases go into
 * stock at cost and leave it at the weighted average rate when used.
 */
#[Fillable(['name', 'unit', 'kind', 'inventory_account_id', 'expense_account_id', 'qty_on_hand', 'value_on_hand', 'reorder_level', 'is_active'])]
class Item extends Model
{
    use Auditable;

    public const KINDS = [
        'roughage' => 'খড় / ভুসি জাতীয়',
        'concentrate' => 'দানাদার খাদ্য',
        'green' => 'কাঁচা ঘাস',
        'medicine' => 'ঔষধ ও টিকা',
        'crop_input' => 'বীজ / সার',
        'product' => 'উৎপাদিত পণ্য',
        'other' => 'অন্যান্য',
    ];

    public const UNITS = ['kg' => 'কেজি', 'liter' => 'লিটার', 'pcs' => 'পিস', 'bag' => 'বস্তা', 'dose' => 'ডোজ'];

    protected function casts(): array
    {
        return [
            'qty_on_hand' => 'float',
            'reorder_level' => 'float',
            'value_on_hand' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** Weighted average cost in poisha per unit. */
    public function averageCost(): float
    {
        return $this->qty_on_hand > 0 ? $this->value_on_hand / $this->qty_on_hand : 0.0;
    }

    public function inventoryAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'inventory_account_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
