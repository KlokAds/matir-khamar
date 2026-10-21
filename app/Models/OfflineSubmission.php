<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $kind
 * @property array<string, mixed> $payload
 * @property string $status
 * @property string|null $message
 * @property int|null $journal_entry_id
 * @property CarbonImmutable $recorded_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read JournalEntry|null $entry
 */
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

    /** @return BelongsTo<JournalEntry, $this> */
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
