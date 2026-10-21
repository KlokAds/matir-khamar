<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Attachment;
use App\Models\JournalEntry;
use App\Models\PeriodLock;
use App\Models\PrepaidSchedule;
use App\Support\BanglaDate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The only way money enters the books. Every posting is checked to balance,
 * to use active accounts and to fall in an open month before it is written.
 */
class Ledger
{
    /**
     * @param  list<array{account_id:int, debit?:int, credit?:int, cost_center_id?:int|null, animal_id?:int|null, budget_head_id?:int|null, memo?:string|null}>  $lines
     * @param  array{party_id?:int|null, reference?:string|null, reversal_of_id?:int|null, attachments?:list<UploadedFile>}  $options
     */
    public function post(string $type, string $date, string $description, array $lines, array $options = []): JournalEntry
    {
        $this->assertPostable($date, $lines);

        return DB::transaction(function () use ($type, $date, $description, $lines, $options) {
            $entry = JournalEntry::query()->create([
                'number' => $this->nextNumber($date),
                'date' => $date,
                'type' => $type,
                'description' => $description,
                'party_id' => $options['party_id'] ?? null,
                'reference' => $options['reference'] ?? null,
                'reversal_of_id' => $options['reversal_of_id'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'cost_center_id' => $line['cost_center_id'] ?? null,
                    'animal_id' => $line['animal_id'] ?? null,
                    'budget_head_id' => $line['budget_head_id'] ?? null,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'memo' => $line['memo'] ?? null,
                ]);
            }

            foreach ($options['attachments'] ?? [] as $file) {
                $this->attach($entry, $file);
            }

            return $entry;
        });
    }

    /**
     * Cancels an entry by posting its mirror image. Stock taken in or out by
     * the entry is put back, so quantities and values stay true.
     */
    public function reverse(JournalEntry $entry, string $date, string $reason): JournalEntry
    {
        if ($entry->reversed_by_id !== null || $entry->type === 'reversal') {
            throw ValidationException::withMessages(['entry' => __('এই এন্ট্রি আগেই বাতিল করা হয়েছে।')]);
        }

        if (str_starts_with($entry->type, 'animal_')) {
            throw ValidationException::withMessages(['entry' => __('পশুর এন্ট্রি এখান থেকে বাতিল হয় না। পশুর কার্ড থেকে ঠিক করুন।')]);
        }

        $schedule = PrepaidSchedule::query()->where('journal_entry_id', $entry->id)->first();

        if ($schedule && $schedule->amortized_months > 0) {
            throw ValidationException::withMessages(['entry' => __('এই লিজের মাসিক খরচ ইতিমধ্যে বসেছে, তাই এটি বাতিল করা যাবে না।')]);
        }

        $lines = array_values($entry->lines->map(fn ($line) => [
            'account_id' => $line->account_id,
            'cost_center_id' => $line->cost_center_id,
            'animal_id' => $line->animal_id,
            'budget_head_id' => $line->budget_head_id,
            'debit' => $line->credit,
            'credit' => $line->debit,
            'memo' => $line->memo,
        ])->all());

        return DB::transaction(function () use ($entry, $date, $reason, $lines, $schedule) {
            $reversal = $this->post('reversal', $date, "বাতিল: {$entry->number} — {$reason}", $lines, [
                'party_id' => $entry->party_id,
                'reversal_of_id' => $entry->id,
            ]);

            $entry->update(['reversed_by_id' => $reversal->id]);

            app(StockService::class)->reverseMovements($entry, $reversal);
            $schedule?->delete();

            return $reversal;
        });
    }

    public function attach(JournalEntry $entry, UploadedFile $file): Attachment
    {
        $path = $file->storeAs(
            'vouchers/'.now()->format('Y/m'),
            Str::random(40).'.'.$file->extension(),
            'local',
        );

        return $entry->attachments()->create([
            'path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 200, ''),
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);
    }

    /** @param  list<array<string, mixed>>  $lines */
    private function assertPostable(string $date, array $lines): void
    {
        if (PeriodLock::isLocked($date)) {
            throw ValidationException::withMessages([
                'date' => __(':month মাসের হিসাব লক করা আছে। নতুন এন্ট্রি দেওয়া যাবে না।', ['month' => BanglaDate::month(Date::parse($date))]),
            ]);
        }

        if (count($lines) < 2) {
            throw ValidationException::withMessages(['lines' => __('একটি এন্ট্রিতে অন্তত দুটি লাইন লাগবে।')]);
        }

        $debits = 0;
        $credits = 0;

        foreach ($lines as $line) {
            $debit = (int) ($line['debit'] ?? 0);
            $credit = (int) ($line['credit'] ?? 0);

            if ($debit < 0 || $credit < 0 || ($debit > 0) === ($credit > 0)) {
                throw ValidationException::withMessages(['lines' => __('প্রতিটি লাইনে হয় ডেবিট, নয় ক্রেডিট থাকবে, এবং টাকা শূন্যের বেশি হবে।')]);
            }

            $debits += $debit;
            $credits += $credit;
        }

        if ($debits !== $credits) {
            throw ValidationException::withMessages(['lines' => __('ডেবিট ও ক্রেডিট সমান হয়নি।')]);
        }

        $accountIds = array_unique(array_column($lines, 'account_id'));
        $activeCount = Account::query()->whereIn('id', $accountIds)->where('is_active', true)->count();

        if ($activeCount !== count($accountIds)) {
            throw ValidationException::withMessages(['lines' => __('একটি হিসাব খাত বন্ধ বা পাওয়া যায়নি।')]);
        }
    }

    private function nextNumber(string $date): string
    {
        $year = Date::parse($date)->year;
        $prefix = "MK-{$year}-";

        $last = JournalEntry::query()
            ->where('number', 'like', $prefix.'%')
            ->orderByDesc('number')
            ->value('number');

        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
