<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A photo or voice note kept with a diary page. Stored privately; shown only to signed-in users.
 *
 * @property int $id
 * @property int $farm_note_id
 * @property string $path
 * @property string $original_name
 * @property string $mime
 * @property int $size
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read FarmNote $note
 */
#[Fillable(['farm_note_id', 'path', 'original_name', 'mime', 'size'])]
class FarmNoteFile extends Model
{
    /** @return BelongsTo<FarmNote, $this> */
    public function note(): BelongsTo
    {
        return $this->belongsTo(FarmNote::class, 'farm_note_id');
    }

    public function kind(): string
    {
        return str_starts_with($this->mime, 'audio/') ? 'audio' : (str_starts_with($this->mime, 'image/') ? 'image' : 'file');
    }
}
