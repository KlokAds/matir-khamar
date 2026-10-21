<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A voucher or receipt image, kept on the private disk. */
#[Fillable(['journal_entry_id', 'path', 'original_name', 'mime', 'size', 'uploaded_by'])]
class Attachment extends Model
{
    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}
