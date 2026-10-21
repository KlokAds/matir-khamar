<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $source
 * @property string $external_table
 * @property string $external_id
 * @property string|null $external_number
 * @property CarbonImmutable $external_date
 * @property int $amount
 * @property float|null $qty
 * @property string $checksum
 * @property array<string, mixed> $raw
 * @property string $status
 * @property string|null $problem
 * @property int|null $journal_entry_id
 * @property CarbonImmutable|null $posted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read JournalEntry|null $entry
 */
#[Fillable(['source', 'external_table', 'external_id', 'external_number', 'external_date', 'amount', 'qty', 'checksum', 'raw', 'status', 'problem', 'journal_entry_id', 'posted_at'])]
class ImportedRecord extends Model
{
    public const STATUSES = [
        'pending' => 'ম্যাপিং বাকি',
        'posted' => 'হিসাবে উঠেছে',
        'ignored' => 'খামারের নয় (বাদ)',
        'changed' => 'বিলিংয়ে বদলেছে — দেখুন',
    ];

    protected function casts(): array
    {
        return [
            'external_date' => 'date',
            'amount' => 'integer',
            'qty' => 'float',
            'raw' => 'array',
            'posted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<JournalEntry, $this> */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}
