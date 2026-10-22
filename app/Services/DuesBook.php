<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalLine;
use App\Models\Party;
use App\Support\Tr;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Who owes the farm (credit sales, account 1200) and whom the farm owes (credit purchases,
 * account 2100), party by party. Payments settle the oldest dues first, so the age of a
 * balance is the age of its oldest unpaid sale or purchase.
 *
 * @phpstan-type Due array{party_id:int|null, name:string, phone:string|null, type:string|null, balance:int, oldest_on:string|null, age_days:int, last_paid_on:string|null, entries:int}
 */
class DuesBook
{
    /** Dues older than this many days come up as reminders. */
    public const REMIND_AFTER_DAYS = 30;

    /**
     * @return array{receivables: list<Due>, payables: list<Due>, total_receivable:int, total_payable:int}
     */
    public function book(?CarbonInterface $today = null): array
    {
        $today = CarbonImmutable::parse(($today ?? now())->toDateString());
        $receivables = $this->side(AccountCodes::RECEIVABLE, true, $today);
        $payables = $this->side(AccountCodes::PAYABLE, false, $today);

        return [
            'receivables' => $receivables,
            'payables' => $payables,
            'total_receivable' => array_sum(array_column($receivables, 'balance')),
            'total_payable' => array_sum(array_column($payables, 'balance')),
        ];
    }

    /**
     * Dues left unpaid for more than a month, in the HerdCare reminder shape.
     *
     * @return list<array{kind:string, title:string, detail:string, href:string, when:string, urgent:bool}>
     */
    public function reminders(?CarbonInterface $today = null): array
    {
        $today = CarbonImmutable::parse(($today ?? now())->toDateString());
        $book = $this->book($today);
        $items = [];

        foreach ($book['receivables'] as $due) {
            if ($due['age_days'] > self::REMIND_AFTER_DAYS) {
                $items[] = [
                    'kind' => 'due_in',
                    'title' => Tr::get(':name-এর কাছে :amount পাওনা', ['name' => $due['name'], 'amount' => self::taka($due['balance'])]),
                    'detail' => Tr::get(':days দিন ধরে বাকি · আদায়ের কথা বলুন', ['days' => $due['age_days']]),
                    'href' => '/cash?tab=dues',
                    'when' => $today->toDateString(),
                    'urgent' => $due['age_days'] > 60,
                ];
            }
        }

        foreach ($book['payables'] as $due) {
            if ($due['age_days'] > self::REMIND_AFTER_DAYS) {
                $items[] = [
                    'kind' => 'due_out',
                    'title' => Tr::get(':name-কে :amount দিতে হবে', ['name' => $due['name'], 'amount' => self::taka($due['balance'])]),
                    'detail' => Tr::get(':days দিন ধরে বাকি · শোধের তারিখ ঠিক করুন', ['days' => $due['age_days']]),
                    'href' => '/cash?tab=dues',
                    'when' => $today->toDateString(),
                    'urgent' => $due['age_days'] > 60,
                ];
            }
        }

        return $items;
    }

    /** "৳ 12,500" for server-built reminder text. */
    public static function taka(int $poisha): string
    {
        $value = abs($poisha) / 100;

        return ($poisha < 0 ? '−' : '').'৳ '.number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 2);
    }

    /**
     * @return list<Due>
     */
    private function side(string $code, bool $debitGrows, CarbonImmutable $today): array
    {
        $account = Account::query()->where('code', $code)->first();

        if (! $account) {
            return [];
        }

        $lines = JournalLine::query()
            ->toBase()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.account_id', $account->id)
            ->orderBy('journal_entries.date')
            ->orderBy('journal_lines.id')
            ->get(['journal_entries.party_id', 'journal_entries.date', 'journal_lines.debit', 'journal_lines.credit']);

        /** @var array<string, array{party_id:int|null, owed: list<array{date:string, left:int}>, paid:int, last_paid_on:string|null, entries:int}> $byParty */
        $byParty = [];

        foreach ($lines as $line) {
            $partyId = $line->party_id !== null ? (int) $line->party_id : null;
            $key = (string) ($partyId ?? 'none');
            $byParty[$key] ??= ['party_id' => $partyId, 'owed' => [], 'paid' => 0, 'last_paid_on' => null, 'entries' => 0];
            $grow = $debitGrows ? (int) $line->debit - (int) $line->credit : (int) $line->credit - (int) $line->debit;
            $date = substr((string) $line->date, 0, 10);

            if ($grow > 0) {
                $byParty[$key]['owed'][] = ['date' => $date, 'left' => $grow];
                $byParty[$key]['entries']++;
            } elseif ($grow < 0) {
                $byParty[$key]['paid'] += -$grow;
                $byParty[$key]['last_paid_on'] = $date;
            }
        }

        $parties = Party::query()->whereIn('id', array_filter(array_column($byParty, 'party_id')))->get()->keyBy('id');
        $rows = [];

        foreach ($byParty as $group) {
            // Payments clear the oldest dues first.
            $paid = $group['paid'];
            $balance = 0;
            $oldest = null;

            foreach ($group['owed'] as $owed) {
                $settled = min($paid, $owed['left']);
                $paid -= $settled;
                $left = $owed['left'] - $settled;

                if ($left > 0) {
                    $balance += $left;
                    $oldest ??= $owed['date'];
                }
            }

            if ($balance <= 0) {
                continue;
            }

            $party = $group['party_id'] !== null ? $parties->get($group['party_id']) : null;
            $rows[] = [
                'party_id' => $group['party_id'],
                'name' => $party->name ?? Tr::get('নাম ছাড়া (পক্ষ বাছা হয়নি)'),
                'phone' => $party?->phone,
                'type' => $party?->type,
                'balance' => $balance,
                'oldest_on' => $oldest,
                'age_days' => $oldest ? max(0, (int) CarbonImmutable::parse($oldest)->diffInDays($today)) : 0,
                'last_paid_on' => $group['last_paid_on'],
                'entries' => $group['entries'],
            ];
        }

        usort($rows, fn (array $a, array $b) => [$b['age_days'], $b['balance']] <=> [$a['age_days'], $a['balance']]);

        return $rows;
    }
}
