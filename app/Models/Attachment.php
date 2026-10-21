<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A voucher or receipt image, kept on the private disk.
 *
 * @property int $id
 * @property int $journal_entry_id
 * @property string $path
 * @property string $original_name
 * @property string $mime
 * @property int $size
 * @property int $uploaded_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read JournalEntry $entry
 */
#[Fillable(['journal_entry_id', 'path', 'original_name', 'mime', 'size', 'uploaded_by'])]
class Attachment extends Model
{
    /** @return BelongsTo<JournalEntry, $this> */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}
