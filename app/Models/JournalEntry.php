<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * A posted voucher. Posted entries are never edited or deleted: a mistake is
 * fixed by posting a reversal, so the books always show what happened.
 *
 * @property int $id
 * @property string $number
 * @property CarbonImmutable $date
 * @property string $type
 * @property string $description
 * @property int|null $party_id
 * @property string|null $reference
 * @property int|null $reversal_of_id
 * @property int|null $reversed_by_id
 * @property int $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, JournalLine> $lines
 * @property-read Collection<int, Attachment> $attachments
 * @property-read Party|null $party
 * @property-read User $creator
 * @property-read JournalEntry|null $reversalOf
 * @property-read JournalEntry|null $reversedBy
 */
#[Fillable(['number', 'date', 'type', 'description', 'party_id', 'reference', 'reversal_of_id', 'reversed_by_id', 'created_by'])]
class JournalEntry extends Model
{
    use Auditable;

    public const TYPES = [
        'expense' => 'খরচ',
        'income' => 'আয় / বিক্রি',
        'stock_purchase' => 'মাল ক্রয় (স্টকে)',
        'stock_issue' => 'স্টক থেকে ব্যবহার',
        'production' => 'নিজস্ব উৎপাদন',
        'payment' => 'বাকি পরিশোধ',
        'receipt' => 'বাকি আদায়',
        'transfer' => 'টাকা স্থানান্তর',
        'asset' => 'সম্পদ ক্রয়',
        'lease' => 'লিজ অগ্রিম',
        'lease_amortization' => 'লিজের মাসিক খরচ',
        'investment' => 'বিনিয়োগ গ্রহণ',
        'withdrawal' => 'মূলধন উত্তোলন',
        'loan' => 'ঋণ গ্রহণ',
        'loan_repayment' => 'ঋণের কিস্তি',
        'grant' => 'অনুদান গ্রহণ',
        'allocation' => 'সাধারণ খরচ ভাগ',
        'animal_purchase' => 'পশু ক্রয়',
        'animal_transfer' => 'পশুর ক্যাটাগরি বদল',
        'animal_sale' => 'পশু বিক্রি',
        'animal_death' => 'পশু মৃত্যু',
        'opening' => 'প্রারম্ভিক জের',
        'reversal' => 'বাতিল (উল্টো এন্ট্রি)',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $entry) {
            if (array_diff(array_keys($entry->getDirty()), ['reversed_by_id', 'updated_at']) !== []) {
                throw new LogicException('Posted journal entries cannot be edited. Post a reversal instead.');
            }
        });

        static::deleting(function () {
            throw new LogicException('Posted journal entries cannot be deleted.');
        });
    }

    /** @return HasMany<JournalLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    /** @return HasMany<Attachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /** @return BelongsTo<Party, $this> */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<JournalEntry, $this> */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    /** @return BelongsTo<JournalEntry, $this> */
    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_by_id');
    }
}
