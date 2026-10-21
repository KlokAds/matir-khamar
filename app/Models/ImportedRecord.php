<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}
