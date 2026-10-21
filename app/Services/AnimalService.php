<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Animal;
use App\Models\AnimalPlacement;
use App\Models\CostCenter;
use App\Models\JournalEntry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * An animal's life on the books: bought (or born) into a center, moved when
 * a bokna becomes a gavi, and finally sold or lost. Its purchase cost moves
 * with it, so each center carries exactly the animals it holds.
 */
class AnimalService
{
    public function __construct(private Ledger $ledger) {}

    /**
     * @param  array<string, mixed>  $data  tag, name, sex, breed, cost_center_id, acquisition, acquired_on, birth_date, mother_id, notes
     * @param  list<UploadedFile>  $attachments
     */
    public function register(array $data, int $cost, ?int $paidFromAccountId, ?int $budgetHeadId = null, array $attachments = []): Animal
    {
        $center = CostCenter::query()->findOrFail((int) $data['cost_center_id']);

        if (! $center->holds_animals) {
            throw ValidationException::withMessages(['cost_center_id' => 'এই ক্যাটাগরিতে পশু রাখা যায় না।']);
        }

        return DB::transaction(function () use ($data, $cost, $paidFromAccountId, $budgetHeadId, $attachments) {
            $animal = Animal::query()->create([
                ...$data,
                'status' => 'active',
                'purchase_cost' => $cost,
                'book_value' => $cost,
            ]);

            AnimalPlacement::query()->create([
                'animal_id' => $animal->id,
                'cost_center_id' => $animal->cost_center_id,
                'from_date' => $animal->acquired_on,
            ]);

            if ($cost > 0) {
                $creditAccountId = $data['acquisition'] === 'opening'
                    ? Account::byCode(AccountCodes::OPENING)->id
                    : $paidFromAccountId;

                if (! $creditAccountId) {
                    throw ValidationException::withMessages(['paid_from_account_id' => 'কোন হিসাব থেকে টাকা গেছে তা বাছুন।']);
                }

                $this->ledger->post(
                    $data['acquisition'] === 'opening' ? 'opening' : 'animal_purchase',
                    $animal->acquired_on->toDateString(),
                    "পশু: {$animal->tag}".($animal->name ? " ({$animal->name})" : ''),
                    [
                        ['account_id' => Account::byCode(AccountCodes::LIVESTOCK)->id, 'debit' => $cost, 'cost_center_id' => $animal->cost_center_id, 'animal_id' => $animal->id, 'budget_head_id' => $budgetHeadId],
                        ['account_id' => $creditAccountId, 'credit' => $cost],
                    ],
                    ['attachments' => $attachments],
                );
            }

            return $animal;
        });
    }

    /** E.g. a bokna calves and becomes a gavi. */
    public function transfer(Animal $animal, int $toCenterId, string $date): ?JournalEntry
    {
        $this->assertActive($animal);

        if ($animal->cost_center_id === $toCenterId) {
            throw ValidationException::withMessages(['cost_center_id' => 'পশুটি আগে থেকেই এই ক্যাটাগরিতে আছে।']);
        }

        $to = CostCenter::query()->findOrFail($toCenterId);

        if (! $to->holds_animals) {
            throw ValidationException::withMessages(['cost_center_id' => 'এই ক্যাটাগরিতে পশু রাখা যায় না।']);
        }

        return DB::transaction(function () use ($animal, $to, $date) {
            $from = $animal->costCenter;
            $entry = null;

            if ($animal->book_value > 0) {
                $livestock = Account::byCode(AccountCodes::LIVESTOCK)->id;
                $entry = $this->ledger->post('animal_transfer', $date, "{$animal->tag}: {$from->name} → {$to->name}", [
                    ['account_id' => $livestock, 'debit' => $animal->book_value, 'cost_center_id' => $to->id, 'animal_id' => $animal->id],
                    ['account_id' => $livestock, 'credit' => $animal->book_value, 'cost_center_id' => $from->id, 'animal_id' => $animal->id],
                ]);
            }

            $this->closePlacement($animal, $date);
            AnimalPlacement::query()->create(['animal_id' => $animal->id, 'cost_center_id' => $to->id, 'from_date' => $date]);
            $animal->update(['cost_center_id' => $to->id]);

            return $entry;
        });
    }

    /** @param  list<UploadedFile>  $attachments */
    public function sell(Animal $animal, string $date, int $amount, int $receivedIntoAccountId, ?int $partyId, array $attachments = []): JournalEntry
    {
        $this->assertActive($animal);

        return DB::transaction(function () use ($animal, $date, $amount, $receivedIntoAccountId, $partyId, $attachments) {
            $center = $animal->cost_center_id;
            $lines = [
                ['account_id' => $receivedIntoAccountId, 'debit' => $amount],
                ['account_id' => Account::byCode(AccountCodes::ANIMAL_SALES)->id, 'credit' => $amount, 'cost_center_id' => $center, 'animal_id' => $animal->id],
            ];

            if ($animal->book_value > 0) {
                $lines[] = ['account_id' => Account::byCode(AccountCodes::COST_OF_ANIMALS_SOLD)->id, 'debit' => $animal->book_value, 'cost_center_id' => $center, 'animal_id' => $animal->id];
                $lines[] = ['account_id' => Account::byCode(AccountCodes::LIVESTOCK)->id, 'credit' => $animal->book_value, 'cost_center_id' => $center, 'animal_id' => $animal->id];
            }

            $entry = $this->ledger->post('animal_sale', $date, "পশু বিক্রি: {$animal->tag}", $lines, [
                'party_id' => $partyId,
                'attachments' => $attachments,
            ]);

            $this->closePlacement($animal, $date);
            $animal->update(['status' => 'sold', 'disposed_on' => $date, 'sale_amount' => $amount, 'book_value' => 0]);

            return $entry;
        });
    }

    public function recordDeath(Animal $animal, string $date, ?string $note): ?JournalEntry
    {
        $this->assertActive($animal);

        return DB::transaction(function () use ($animal, $date, $note) {
            $entry = null;

            if ($animal->book_value > 0) {
                $entry = $this->ledger->post('animal_death', $date, "পশু মৃত্যু: {$animal->tag}".($note ? " — {$note}" : ''), [
                    ['account_id' => Account::byCode(AccountCodes::ANIMAL_LOSS)->id, 'debit' => $animal->book_value, 'cost_center_id' => $animal->cost_center_id, 'animal_id' => $animal->id],
                    ['account_id' => Account::byCode(AccountCodes::LIVESTOCK)->id, 'credit' => $animal->book_value, 'cost_center_id' => $animal->cost_center_id, 'animal_id' => $animal->id],
                ]);
            }

            $this->closePlacement($animal, $date);
            $animal->update(['status' => 'dead', 'disposed_on' => $date, 'book_value' => 0]);

            return $entry;
        });
    }

    private function closePlacement(Animal $animal, string $date): void
    {
        $animal->placements()->whereNull('to_date')->update(['to_date' => $date]);
    }

    private function assertActive(Animal $animal): void
    {
        if ($animal->status !== 'active') {
            throw ValidationException::withMessages(['animal' => 'এই পশু আর খামারে নেই।']);
        }
    }
}
