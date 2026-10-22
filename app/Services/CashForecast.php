<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Batch;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Plot;
use App\Support\BanglaDate;
use App\Support\Tr;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * How much money the farm will have over the coming months. Every figure is a plain,
 * explained part: the usual monthly income and spending of the last three full months,
 * plus the known items ahead (lease renewals, dues to collect and to pay). Lumpy one-offs
 * such as selling a fattening batch are shown but left out unless the owner adds them.
 *
 * @phpstan-type Part array{key:string, kind:string, label:string, amount:int, why:string, optional:bool, counted:bool}
 * @phpstan-type Month array{month:string, partial:bool, days:int, fraction:float, opening:int, parts:list<Part>, income:int, expense:int, closing:int, flag:string}
 * @phpstan-type Line array{code:string, name:string, amount:int}
 */
class CashForecast
{
    /** Entry types whose income/expense lines move no money: spreading, stock use, own produce. */
    private const NON_CASH_TYPES = ['lease_amortization', 'stock_issue', 'production', 'allocation', 'animal_transfer', 'animal_death', 'animal_sale', 'animal_purchase', 'opening', 'reversal'];

    private const HISTORY_MONTHS = 3;

    public function __construct(private DuesBook $dues) {}

    /**
     * The rest of this month, then $count full months.
     *
     * @return array{today:string, start_cash:int, money: list<array{name:string, balance:int}>, history: array{from:string, to:string, months:int, income:list<Line>, expense:list<Line>, income_total:int, expense_total:int, animal_sales:int}, months: list<Month>, avg_income:int, avg_expense:int}
     */
    public function months(int $count = 3, ?CarbonInterface $today = null): array
    {
        $today = CarbonImmutable::parse(($today ?? now())->toDateString());
        $money = $this->money();
        $startCash = array_sum(array_column($money, 'balance'));
        $history = $this->history($today);
        $avgIncome = $history['income_total'];
        $avgExpense = $history['expense_total'];
        $dues = $this->dues->book($today);

        $rows = [];
        $daysInMonth = $today->daysInMonth;
        $daysLeft = $daysInMonth - $today->day;

        if ($daysLeft > 0) {
            $rows[] = ['start' => $today->startOfMonth(), 'partial' => true, 'days' => $daysLeft, 'fraction' => $daysLeft / $daysInMonth];
        }

        for ($i = 1; $i <= max(1, $count); $i++) {
            $start = $today->startOfMonth()->addMonthsNoOverflow($i);
            $rows[] = ['start' => $start, 'partial' => false, 'days' => $start->daysInMonth, 'fraction' => 1.0];
        }

        $months = [];
        $balance = $startCash;

        foreach ($rows as $index => $row) {
            /** @var CarbonImmutable $start */
            $start = $row['start'];
            $end = $start->endOfMonth();
            $fraction = (float) $row['fraction'];
            $parts = [];

            if ($avgIncome > 0) {
                $parts[] = $this->part('avg_income', 'income', 'নিয়মিত আয় (গড়)', (int) round($avgIncome * $fraction), $row['partial']
                    ? Tr::get('গত :n মাসের গড় মাসিক আয়, মাসের বাকি :days দিনের ভাগ', ['n' => $history['months'], 'days' => $row['days']])
                    : Tr::get('গত :n মাসের গড় মাসিক আয় (পশু বিক্রি বাদে)', ['n' => $history['months']]));
            }

            if ($avgExpense > 0) {
                $parts[] = $this->part('avg_expense', 'expense', 'নিয়মিত খরচ (গড়)', (int) round($avgExpense * $fraction), $row['partial']
                    ? Tr::get('গত :n মাসের গড় মাসিক খরচ, মাসের বাকি :days দিনের ভাগ', ['n' => $history['months'], 'days' => $row['days']])
                    : Tr::get('গত :n মাসের গড় মাসিক খরচ (পশু কেনা ও লিজ অগ্রিম বাদে)', ['n' => $history['months']]));
            }

            // A lease ending in the month is renewed for a year: its yearly rent goes out.
            $from = $row['partial'] ? $today->subDays(60) : $start;
            $leases = Plot::query()->where('is_active', true)->where('tenure', 'lease')->whereNotNull('lease_to')->where('yearly_rent', '>', 0)
                ->whereDate('lease_to', '>=', $from->toDateString())->whereDate('lease_to', '<=', $end->toDateString())->orderBy('lease_to')->get();

            foreach ($leases as $plot) {
                /** @var CarbonInterface $leaseTo */
                $leaseTo = $plot->lease_to;
                $parts[] = $this->part('lease-'.$plot->id, 'expense', Tr::get('লিজ নবায়ন: :name', ['name' => $plot->name]), (int) $plot->yearly_rent,
                    Tr::get('লিজ শেষ :date · এক বছরের ভাড়া ধরা হলো · মালিক :owner', ['date' => BanglaDate::day($leaseTo), 'owner' => $plot->owner_name ?? '—']));
            }

            // Dues are assumed settled within about a month: in the first full month.
            if (! $row['partial'] && $index === ($rows[0]['partial'] ? 1 : 0)) {
                if ($dues['total_receivable'] > 0) {
                    $parts[] = $this->part('receivables', 'income', 'বাকি আদায় (আশা)', $dues['total_receivable'],
                        Tr::get(':n জনের কাছে বাকি বিক্রির টাকা, এক মাসের মধ্যে আদায় ধরে', ['n' => count($dues['receivables'])]), optional: true);
                }

                if ($dues['total_payable'] > 0) {
                    $parts[] = $this->part('payables', 'expense', 'সরবরাহকারীর বাকি শোধ', $dues['total_payable'],
                        Tr::get(':n জনকে বাকি কেনার টাকা, এক মাসের মধ্যে শোধ ধরে', ['n' => count($dues['payables'])]), optional: true);
                }
            }

            // Fattening rounds reaching their target: shown, not counted, since the price is unknown.
            foreach (Batch::query()->whereNull('closed_on')->with(['animals' => fn ($q) => $q->where('status', 'active')])->get() as $batch) {
                $targetOn = $batch->started_on->addDays($batch->target_days);
                $inMonth = $targetOn->lessThanOrEqualTo($end) && ($row['partial'] || $targetOn->greaterThanOrEqualTo($start));

                if (! $inMonth || $batch->animals->isEmpty()) {
                    continue;
                }

                $parts[] = $this->part('batch-'.$batch->id, 'income', Tr::get('বিক্রির সময়: :batch (:n টি পশু)', ['batch' => $batch->label(), 'n' => $batch->animals->count()]),
                    (int) $batch->animals->sum('book_value'),
                    Tr::get('এককালীন · লক্ষ্যের দিন :date · খাতার দাম (ক্রয়মূল্য) দেখানো হলো, ধরা হয়নি', ['date' => BanglaDate::day($targetOn)]),
                    optional: true, counted: false);
            }

            $income = 0;
            $expense = 0;

            foreach ($parts as $part) {
                if ($part['counted']) {
                    $part['kind'] === 'income' ? $income += $part['amount'] : $expense += $part['amount'];
                }
            }

            $opening = $balance;
            $balance = $opening + $income - $expense;

            $months[] = [
                'month' => $start->format('Y-m'),
                'partial' => $row['partial'],
                'days' => (int) $row['days'],
                'fraction' => round($fraction, 4),
                'opening' => $opening,
                'parts' => $parts,
                'income' => $income,
                'expense' => $expense,
                'closing' => $balance,
                'flag' => self::flag($balance, $avgExpense),
            ];
        }

        return [
            'today' => $today->toDateString(),
            'start_cash' => $startCash,
            'money' => $money,
            'history' => $history,
            'months' => $months,
            'avg_income' => $avgIncome,
            'avg_expense' => $avgExpense,
        ];
    }

    /** danger: money runs out; thin: less than one month of usual spending left. */
    public static function flag(int $closing, int $monthlyExpense): string
    {
        if ($closing < 0) {
            return 'danger';
        }

        return $monthlyExpense > 0 && $closing < $monthlyExpense ? 'thin' : 'ok';
    }

    /**
     * Money in hand, bank, wallets and with people, right now.
     *
     * @return list<array{name:string, balance:int}>
     */
    public function money(): array
    {
        $accounts = Account::query()->money()->orderBy('code')->get(['id', 'name']);
        $sums = JournalLine::query()->toBase()->whereIn('account_id', $accounts->pluck('id'))
            ->groupBy('account_id')->selectRaw('account_id, SUM(debit) - SUM(credit) as net')->pluck('net', 'account_id');

        return array_values($accounts->map(fn (Account $a) => ['name' => $a->name, 'balance' => (int) ($sums[$a->id] ?? 0)])->all());
    }

    /**
     * Average monthly money-moving income and spending of the last three full months, by account.
     *
     * @return array{from:string, to:string, months:int, income:list<Line>, expense:list<Line>, income_total:int, expense_total:int, animal_sales:int}
     */
    public function history(CarbonImmutable $today): array
    {
        $from = $today->startOfMonth()->subMonthsNoOverflow(self::HISTORY_MONTHS);
        $to = $today->startOfMonth()->subDay();
        $range = [$from->toDateString(), $to->toDateString().' 23:59:59'];

        // A young farm with one month of books is averaged over that month, not three.
        $active = JournalEntry::query()->whereBetween('date', $range)->whereNotIn('type', ['opening'])->pluck('date')
            ->map(fn ($date) => CarbonImmutable::parse($date)->format('Y-m'))->unique()->count();
        $months = max(1, min(self::HISTORY_MONTHS, $active));

        $rows = JournalLine::query()
            ->toBase()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->whereBetween('journal_entries.date', $range)
            ->whereNull('journal_entries.reversed_by_id')
            ->where(fn ($q) => $q
                ->where(fn ($inner) => $inner->whereIn('accounts.type', ['income', 'expense'])->whereNotIn('journal_entries.type', self::NON_CASH_TYPES))
                // Feed and medicine bought into stock is money spent now, though used later.
                ->orWhere(fn ($inner) => $inner->where('journal_entries.type', 'stock_purchase')->where('accounts.subtype', 'inventory')))
            ->where(fn ($q) => $q->whereNull('accounts.subtype')->orWhereNotIn('accounts.subtype', [Account::INTERNAL_SUBTYPE, 'animal_cost']))
            ->groupBy('accounts.code', 'accounts.name', 'accounts.type')
            ->selectRaw('accounts.code, accounts.name, accounts.type, SUM(journal_lines.debit) as d, SUM(journal_lines.credit) as c')
            ->orderBy('accounts.code')
            ->get();

        $income = [];
        $expense = [];
        $animalSales = 0;

        foreach ($rows as $row) {
            $net = $row->type === 'income' ? (int) $row->c - (int) $row->d : (int) $row->d - (int) $row->c;

            if ($row->code === AccountCodes::ANIMAL_SALES) {
                $animalSales += $net;

                continue;
            }

            $average = (int) round($net / $months);

            if ($average === 0) {
                continue;
            }

            $line = ['code' => (string) $row->code, 'name' => $row->type === 'asset' ? Tr::get(':name কেনা', ['name' => $row->name]) : (string) $row->name, 'amount' => $average];
            $row->type === 'income' ? $income[] = $line : $expense[] = $line;
        }

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'months' => $months,
            'income' => $income,
            'expense' => $expense,
            'income_total' => array_sum(array_column($income, 'amount')),
            'expense_total' => array_sum(array_column($expense, 'amount')),
            'animal_sales' => $animalSales + $this->animalSales($range),
        ];
    }

    /**
     * Animal sales through the animal card in the window (they are left out of the average).
     *
     * @param  array{0:string, 1:string}  $range
     */
    private function animalSales(array $range): int
    {
        return (int) JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->whereBetween('journal_entries.date', $range)
            ->where('journal_entries.type', 'animal_sale')
            ->whereNull('journal_entries.reversed_by_id')
            ->where('accounts.code', AccountCodes::ANIMAL_SALES)
            ->selectRaw('COALESCE(SUM(journal_lines.credit) - SUM(journal_lines.debit), 0) as net')
            ->value('net');
    }

    /** @return Part */
    private function part(string $key, string $kind, string $label, int $amount, string $why, bool $optional = false, bool $counted = true): array
    {
        return ['key' => $key, 'kind' => $kind, 'label' => Tr::get($label), 'amount' => $amount, 'why' => $why, 'optional' => $optional, 'counted' => $counted];
    }
}
