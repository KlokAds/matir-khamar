<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How a billing-side label lands in the farm books, e.g. Crater expense
 * category "Goru khabar" -> account Danadar khaddo, category Bokna.
 */
#[Fillable(['source', 'kind', 'external_id', 'external_name', 'account_id', 'cost_center_id', 'item_id', 'ignore'])]
class ImportMapping extends Model
{
    use Auditable;

    public const KINDS = [
        'expense_category' => 'খরচের ক্যাটাগরি',
        'payment_method' => 'টাকা দেওয়া/নেওয়ার মাধ্যম',
        'customer' => 'ক্রেতা (আয়ের খাত)',
    ];

    protected function casts(): array
    {
        return ['ignore' => 'boolean'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /** Not decided yet: neither mapped nor marked "not farm". */
    public function isUndecided(): bool
    {
        return ! $this->ignore && ! $this->isComplete();
    }

    public function isComplete(): bool
    {
        if ($this->ignore) {
            return true;
        }

        return match ($this->kind) {
            'payment_method' => $this->account_id !== null,
            // Feed and medicine bought through billing go into stock and are shared out by kg later.
            'expense_category' => $this->item_id !== null || ($this->account_id !== null && $this->cost_center_id !== null),
            default => $this->account_id !== null && $this->cost_center_id !== null,
        };
    }
}
