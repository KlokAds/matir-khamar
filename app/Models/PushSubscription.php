<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One phone or browser that agreed to receive notifications for a user.
 *
 * @property int $id
 * @property int $user_id
 * @property string $kind web (browser push) or fcm (the farm's own Android app)
 * @property string $endpoint
 * @property string $endpoint_hash
 * @property string $public_key
 * @property string $auth_token
 * @property string $content_encoding
 * @property string|null $device
 * @property int $failures
 * @property CarbonImmutable|null $last_sent_at
 * @property CarbonImmutable|null $last_received_at
 * @property string $mode payload (words in the push) or tickle (empty push; the phone fetches the words)
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 */
#[Fillable(['user_id', 'kind', 'endpoint', 'endpoint_hash', 'public_key', 'auth_token', 'content_encoding', 'device', 'failures', 'last_sent_at', 'last_received_at', 'mode'])]
#[Hidden(['endpoint', 'public_key', 'auth_token'])]
class PushSubscription extends Model
{
    protected function casts(): array
    {
        return [
            'last_sent_at' => 'datetime',
            'last_received_at' => 'datetime',
            'failures' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
