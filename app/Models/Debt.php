<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Money that has to come back. Direction "out": the farm gave it (deposit, loan to someone,
 * advance) and will get it back. Direction "in": the farm took it (from an investor or anyone)
 * and has to pay it back, once or in instalments.
 *
 * @property int $id
 * @property string $direction out or in
 * @property int $account_id
 * @property int|null $party_id
 * @property string $person_name
 * @property string|null $phone
 * @property string|null $purpose
 * @property CarbonImmutable $started_on
 * @property CarbonImmutable|null $due_on
 * @property CarbonImmutable|null $closed_on
 * @property string|null $notes
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read Party|null $party
 * @property-read Collection<int, DebtInstallment> $installments
 */
#[Fillable(['direction', 'account_id', 'party_id', 'person_name', 'phone', 'purpose', 'started_on', 'due_on', 'closed_on', 'notes', 'created_by'])]
class Debt extends Model
{
    use Auditable;

    public const DIRECTIONS = ['out' => 'আমরা দিলাম — ফেরত পাবো', 'in' => 'আমরা নিলাম — ফেরত দিতে হবে'];

    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'due_on' => 'date',
            'closed_on' => 'date',
        ];
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return BelongsTo<Party, $this> */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    /** @return HasMany<DebtInstallment, $this> */
    public function installments(): HasMany
    {
        return $this->hasMany(DebtInstallment::class)->orderBy('due_on');
    }

    /** Still outstanding, in poisha: what is out with them, or what we still owe. */
    public function balance(): int
    {
        $net = (int) JournalLine::query()->where('account_id', $this->account_id)
            ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as net')->value('net');

        return $this->direction === 'out' ? $net : -$net;
    }
}
