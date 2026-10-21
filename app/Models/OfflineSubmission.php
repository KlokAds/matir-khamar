<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['uuid', 'user_id', 'kind', 'payload', 'status', 'message', 'journal_entry_id', 'recorded_at'])]
class OfflineSubmission extends Model
{
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'recorded_at' => 'datetime',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    /** @return array{uuid:string, status:string, message:?string, number:?string} */
    public function result(): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status,
            'message' => $this->message,
            'number' => $this->entry?->number,
        ];
    }
}
