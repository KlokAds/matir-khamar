<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Debt;
use App\Models\DebtInstallment;
use App\Models\JournalEntry;
use App\Support\BanglaDate;
use App\Support\Tr;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Money that has to come back, both ways. "out": the farm gave it and gets it back (an asset,
 * account under 1750). "in": the farm took it and pays it back (a liability under 2200), with an
 * optional instalment plan. Cash in hand never includes either.
 */
class DebtBook
{
    public const OUT_GROUP = '1750';

    public function __construct(private Ledger $ledger) {}

    /**
     * Open a new one: give or take the first amount and set the plan.
     *
     * @param  array{direction:string, person_name:string, phone?:string|null, purpose?:string|null, party_id?:int|null, date:string, due_on?:string|null, notes?:string|null}  $data
     * @param  list<array{due_on:string, amount:int}>  $installments  poisha
     */
    public function open(array $data, int $amount, int $moneyAccountId, array $installments = []): Debt
    {
        $this->assertMoney($moneyAccountId);

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => Tr::get('টাকার পরিমাণ দিন।')]);
        }

        return DB::transaction(function () use ($data, $amount, $moneyAccountId, $installments) {
            $out = $data['direction'] === 'out';
            $parent = $out ? $this->outGroup() : Account::byCode(AccountCodes::LOANS);
            $siblings = Account::query()->where('parent_id', $parent->id)->count();

            $account = Account::query()->create([
                'parent_id' => $parent->id,
                'code' => $parent->code.'-'.str_pad((string) ($siblings + 1), 2, '0', STR_PAD_LEFT),
                'name' => ($out ? 'ফেরত পাবো: ' : 'ফেরত দিতে হবে: ').$data['person_name'].(! empty($data['purpose']) ? ' — '.$data['purpose'] : ''),
                'type' => $out ? 'asset' : 'liability',
                'subtype' => $out ? 'lent' : 'loan',
                'party_id' => $data['party_id'] ?? null,
                'is_system' => false,
            ]);

            $debt = Debt::query()->create([
                'direction' => $data['direction'],
                'account_id' => $account->id,
                'party_id' => $data['party_id'] ?? null,
                'person_name' => $data['person_name'],
                'phone' => $data['phone'] ?? null,
                'purpose' => $data['purpose'] ?? null,
                'started_on' => $data['date'],
                'due_on' => $data['due_on'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $this->move($debt, 'more', $amount, $moneyAccountId, $data['date']);
            $this->plan($debt, $installments);

            return $debt;
        });
    }

    /**
     * Give or take more ("more"), or settle part or all of it ("settle": we get money back for
     * "out", we pay back for "in"). Settling fills the earliest planned instalments first.
     */
    public function move(Debt $debt, string $kind, int $amount, int $moneyAccountId, string $date, ?string $note = null): void
    {
        $this->assertMoney($moneyAccountId);
        $out = $debt->direction === 'out';

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => Tr::get('টাকার পরিমাণ দিন।')]);
        }

        if ($kind === 'settle' && $amount > $debt->balance()) {
            throw ValidationException::withMessages(['amount' => Tr::get('বাকি আছে :left, এর বেশি লেখা যাবে না।', ['left' => number_format($debt->balance() / 100)])]);
        }

        // out+more: money leaves us to them. out+settle: it comes back. in+more: we receive. in+settle: we pay.
        $toDebt = ($out && $kind === 'more') || (! $out && $kind === 'settle');
        $type = match (true) {
            $out && $kind === 'more' => 'lend',
            $out => 'lend_return',
            $kind === 'more' => 'loan',
            default => 'loan_repayment',
        };
        $label = match ($type) {
            'lend' => 'দেওয়া হলো',
            'lend_return' => 'ফেরত পাওয়া গেল',
            'loan' => 'নেওয়া হলো',
            default => 'ফেরত দেওয়া হলো',
        };

        DB::transaction(function () use ($debt, $kind, $amount, $moneyAccountId, $date, $note, $toDebt, $type, $label) {
            $this->ledger->post($type, $date, "{$debt->person_name}: {$label}".($debt->purpose ? " — {$debt->purpose}" : '').($note ? " — {$note}" : ''), $toDebt
                ? [['account_id' => $debt->account_id, 'debit' => $amount], ['account_id' => $moneyAccountId, 'credit' => $amount]]
                : [['account_id' => $moneyAccountId, 'debit' => $amount], ['account_id' => $debt->account_id, 'credit' => $amount]],
                ['party_id' => $debt->party_id, 'reference' => "debt:{$debt->id}"]);

            if ($kind === 'settle') {
                $left = $amount;

                foreach ($debt->installments()->get() as $installment) {
                    if ($left <= 0) {
                        break;
                    }

                    $take = min($left, $installment->left());

                    if ($take > 0) {
                        $installment->update(['paid' => $installment->paid + $take]);
                        $left -= $take;
                    }
                }
            }

            $debt->update(['closed_on' => $debt->balance() === 0 ? $date : null]);
        });
    }

    /**
     * Replace the plan of future instalments (amounts in poisha).
     *
     * @param  list<array{due_on:string, amount:int}>  $installments
     */
    public function plan(Debt $debt, array $installments): void
    {
        $debt->installments()->where('paid', 0)->delete();

        foreach ($installments as $row) {
            if ((int) $row['amount'] > 0) {
                $debt->installments()->create(['due_on' => $row['due_on'], 'amount' => (int) $row['amount']]);
            }
        }
    }

    /** @return array<string, mixed> */
    public function card(Debt $debt, ?CarbonInterface $today = null): array
    {
        $today = ($today ?? now())->startOfDay();
        $debt->loadMissing('installments');
        $balance = $debt->balance();
        $next = $debt->installments->first(fn (DebtInstallment $i) => $i->left() > 0);
        $nextDue = $next ? $next->due_on : ($balance > 0 ? $debt->due_on : null);

        return [
            'id' => $debt->id,
            'direction' => $debt->direction,
            'person_name' => $debt->person_name,
            'phone' => $debt->phone,
            'purpose' => $debt->purpose,
            'party_id' => $debt->party_id,
            'started_on' => $debt->started_on->toDateString(),
            'due_on' => $debt->due_on?->toDateString(),
            'closed_on' => $debt->closed_on?->toDateString(),
            'notes' => $debt->notes,
            'balance' => $balance,
            'next_due_on' => $nextDue?->toDateString(),
            'next_amount' => $next ? $next->left() : ($nextDue ? $balance : null),
            'days_left' => $nextDue ? (int) $today->diffInDays($nextDue, false) : null,
            'installments' => $debt->installments->map(fn (DebtInstallment $i) => [
                'id' => $i->id,
                'due_on' => $i->due_on->toDateString(),
                'amount' => $i->amount,
                'paid' => $i->paid,
                'left' => $i->left(),
                'overdue' => $i->left() > 0 && $i->due_on->lessThan($today),
            ])->values(),
            'history' => JournalEntry::query()->where('reference', "debt:{$debt->id}")
                ->orWhere(fn ($q) => $q->whereHas('lines', fn ($l) => $l->where('account_id', $debt->account_id)))
                ->with('lines')->orderBy('date')->orderBy('id')->get()
                ->map(fn ($e) => [
                    'id' => $e->id,
                    'number' => $e->number,
                    'date' => $e->date->toDateString(),
                    'description' => $e->description,
                    'amount' => (int) $e->lines->where('account_id', $debt->account_id)->sum(fn ($l) => $l->debit + $l->credit),
                ])->unique('id')->values(),
        ];
    }

    /**
     * Instalments to pay within 3 days or overdue, and money we lent that is due back.
     *
     * @return list<array{kind:string, title:string, detail:string, href:string, when:string, urgent:bool}>
     */
    public function reminders(CarbonInterface $today): array
    {
        $today = $today->startOfDay();
        $items = [];

        foreach (Debt::query()->whereNull('closed_on')->with('installments')->get() as $debt) {
            $card = $this->card($debt, $today);

            if ($card['next_due_on'] === null || $card['days_left'] === null || $card['days_left'] > 3 || $card['balance'] <= 0) {
                continue;
            }

            $amount = number_format(($card['next_amount'] ?? 0) / 100);
            $when = $card['days_left'] < 0
                ? Tr::get(':n দিন পার হয়ে গেছে', ['n' => abs($card['days_left'])])
                : ($card['days_left'] === 0 ? Tr::get('আজ') : Tr::get(':n দিন পর', ['n' => $card['days_left']]));

            $items[] = [
                'kind' => $debt->direction === 'in' ? 'debt_pay' : 'debt_collect',
                'title' => $debt->direction === 'in'
                    ? Tr::get(':name-কে ফেরত দিতে হবে ৳:amount', ['name' => $debt->person_name, 'amount' => $amount])
                    : Tr::get(':name-এর কাছ থেকে ফেরত পাওয়ার কথা ৳:amount', ['name' => $debt->person_name, 'amount' => $amount]),
                'detail' => $when.' · '.BanglaDate::day(Date::parse($card['next_due_on'])),
                'href' => '/debts',
                'when' => $card['next_due_on'],
                'urgent' => $card['days_left'] <= 0,
            ];
        }

        return $items;
    }

    /**
     * Planned flows between two dates, for the cash forecast: instalments we pay (negative) and
     * money expected back (positive), in poisha.
     *
     * @return list<array{date:string, amount:int, label:string}>
     */
    public function plannedFlows(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = [];

        foreach (Debt::query()->whereNull('closed_on')->with('installments')->get() as $debt) {
            $sign = $debt->direction === 'in' ? -1 : 1;
            $planned = $debt->installments->filter(fn (DebtInstallment $i) => $i->left() > 0);

            if ($planned->isEmpty() && $debt->due_on) {
                $planned = collect([new DebtInstallment(['due_on' => $debt->due_on, 'amount' => $debt->balance(), 'paid' => 0])]);
            }

            foreach ($planned as $i) {
                if ($i->due_on->betweenIncluded($from, $to)) {
                    $rows[] = ['date' => $i->due_on->toDateString(), 'amount' => $sign * $i->left(), 'label' => $debt->person_name];
                }
            }
        }

        return $rows;
    }

    private function outGroup(): Account
    {
        return Account::query()->firstOrCreate(['code' => self::OUT_GROUP], [
            'name' => 'ফেরত পাবো (অন্যের কাছে পাওনা)',
            'type' => 'asset',
            'parent_id' => Account::byCode('1000')->id,
            'is_system' => true,
        ]);
    }

    private function assertMoney(int $accountId): void
    {
        if (! Account::query()->whereKey($accountId)->whereIn('subtype', Account::MONEY_SUBTYPES)->exists()) {
            throw ValidationException::withMessages(['money_account_id' => Tr::get('টাকা কোন হিসাব থেকে/তে, সেটা বাছুন।')]);
        }
    }
}
