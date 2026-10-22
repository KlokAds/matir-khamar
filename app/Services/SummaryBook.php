<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Animal;
use App\Models\CostCenter;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Support\Tr;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * The whole farm on one page for any stretch of time: result, where money came from and went,
 * each project's result, what the money was spent on, month by month, what the farm owns and
 * owes at the end, and the herd. Read-only; built from the same books as every other report.
 */
class SummaryBook
{
    public const PERIODS = [
        'month' => 'এ মাস',
        'last' => 'গত মাস',
        '6m' => '৬ মাস',
        'year' => 'এ বছর',
        'all' => 'শুরু থেকে',
        'custom' => 'নিজে বাছুন',
    ];

    public function __construct(private ReportService $reports) {}

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    public function range(string $period, ?string $from = null, ?string $to = null): array
    {
        $today = CarbonImmutable::today();

        return match ($period) {
            'last' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()],
            '6m' => [$today->subMonthsNoOverflow(5)->startOfMonth(), $today->endOfMonth()],
            'year' => [$today->startOfYear(), $today->endOfMonth()],
            'all' => [CarbonImmutable::parse((string) (JournalEntry::query()->min('date') ?? $today->toDateString()))->startOfMonth(), $today->endOfMonth()],
            'custom' => [
                CarbonImmutable::parse($from ?: $today->startOfMonth()->toDateString())->startOfDay(),
                CarbonImmutable::parse($to ?: $today->toDateString())->endOfDay(),
            ],
            default => [$today->startOfMonth(), $today->endOfMonth()],
        };
    }

    /** @return array<string, mixed> */
    public function build(CarbonInterface $from, CarbonInterface $to): array
    {
        $days = (int) $from->diffInDays($to) + 1;
        $prevTo = $from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

        return [
            'result' => $this->reports->farmTotals($from, $to),
            'previous' => $this->reports->farmTotals($prevFrom, $prevTo),
            'flows' => $this->flows($from, $to),
            'projects' => $this->projects($from, $to),
            'spending' => $this->byAccount($from, $to, 'expense'),
            'earning' => $this->byAccount($from, $to, 'income'),
            'months' => $this->months($from, $to),
            'position' => $this->position($to),
            'herd' => $this->herd($from, $to),
            'milk' => $this->reports->milkLiters($from, $to),
        ];
    }

    /**
     * Money that came in and went out in the period, by kind (capital, sales, purchases...).
     * A reversal counts against the kind it cancels; moves between money places are left out.
     *
     * @return array{in: list<array{label:string, amount:int}>, out: list<array{label:string, amount:int}>, in_total:int, out_total:int}
     */
    public function flows(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = JournalLine::query()->toBase()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->leftJoin('journal_entries as original', 'original.id', '=', 'journal_entries.reversal_of_id')
            ->whereIn('accounts.subtype', Account::MONEY_SUBTYPES)
            ->where('journal_entries.type', '!=', 'transfer')
            ->whereBetween('journal_entries.date', [$from->toDateString(), $to->toDateString().' 23:59:59'])
            ->groupBy('journal_entries.id', 'journal_entries.type', 'original.type')
            ->selectRaw('journal_entries.type as type, original.type as original_type, SUM(journal_lines.debit) - SUM(journal_lines.credit) as net')
            ->get();

        $in = [];
        $out = [];

        foreach ($rows as $row) {
            $type = $row->type === 'reversal' && $row->original_type ? $row->original_type : $row->type;
            $net = (int) $row->net;

            if ($net === 0) {
                continue;
            }

            $bucket = in_array($type, ['investment', 'income', 'animal_sale', 'receipt', 'lend_return', 'loan', 'grant'], true) ? 'in' : 'out';
            $bucket === 'in' ? $in[$type] = ($in[$type] ?? 0) + $net : $out[$type] = ($out[$type] ?? 0) - $net;
        }

        $list = function (array $sums): array {
            arsort($sums);

            return array_map(
                fn (string $type, int $amount) => ['label' => Tr::get(JournalEntry::TYPES[$type] ?? $type), 'amount' => $amount],
                array_keys(array_filter($sums)),
                array_filter($sums),
            );
        };

        return ['in' => $list($in), 'out' => $list($out), 'in_total' => array_sum($in), 'out_total' => array_sum($out)];
    }

    /**
     * Each project under the farm with its result, and the categories inside it.
     *
     * @return list<array{id:int, name:string, kind:string, income:int, expense:int, profit:int, children: list<array{id:int, name:string, income:int, expense:int, profit:int}>}>
     */
    public function projects(CarbonInterface $from, CarbonInterface $to): array
    {
        $tree = $this->reports->centerTree($from, $to);
        $top = count($tree) === 1 ? $tree[0]['children'] : $tree;
        $out = [];

        foreach ($top as $node) {
            // A project with nothing in the period only adds a row of zeros.
            if ($node['income'] === 0 && $node['expense'] === 0) {
                continue;
            }

            $out[] = [
                'id' => $node['id'],
                'name' => $node['name'],
                'kind' => $node['kind'],
                'income' => $node['income'],
                'expense' => $node['expense'],
                'profit' => $node['profit'],
                'children' => array_values(array_map(fn ($c) => [
                    'id' => $c['id'], 'name' => $c['name'], 'income' => $c['income'], 'expense' => $c['expense'], 'profit' => $c['profit'],
                ], array_filter($node['children'], fn ($c) => $c['income'] !== 0 || $c['expense'] !== 0))),
            ];
        }

        usort($out, fn ($a, $b) => ($b['income'] + $b['expense']) <=> ($a['income'] + $a['expense']));

        return $out;
    }

    /** @return list<array{name:string, amount:int}> */
    public function byAccount(CarbonInterface $from, CarbonInterface $to, string $type): array
    {
        $rows = array_filter($this->reports->accountTotals($from, $to, null)['accounts'], fn ($a) => $a['type'] === $type && ! $a['internal'] && $a['amount'] !== 0);
        usort($rows, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        return array_map(fn ($a) => ['name' => $a['name'], 'amount' => $a['amount']], $rows);
    }

    /**
     * Income and expense per month across the period (at most 24 months, the latest ones).
     *
     * @return list<array{month:string, income:int, expense:int}>
     */
    public function months(CarbonInterface $from, CarbonInterface $to): array
    {
        $start = CarbonImmutable::parse($from->toDateString())->startOfMonth();
        $end = CarbonImmutable::parse($to->toDateString())->startOfMonth();

        if ($start->diffInMonths($end) > 23) {
            $start = $end->subMonths(23);
        }

        $ym = DB::connection()->getDriverName() === 'sqlite' ? "strftime('%Y-%m', journal_entries.date)" : "DATE_FORMAT(journal_entries.date, '%Y-%m')";
        $rows = JournalLine::query()->toBase()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->whereIn('accounts.type', ['income', 'expense'])
            ->where(fn ($q) => $q->whereNull('accounts.subtype')->orWhere('accounts.subtype', '!=', Account::INTERNAL_SUBTYPE))
            ->whereBetween('journal_entries.date', [max($start->toDateString(), $from->toDateString()), $to->toDateString().' 23:59:59'])
            ->groupBy(DB::raw($ym), 'accounts.type')
            ->selectRaw("{$ym} as ym, accounts.type as type, SUM(journal_lines.debit) as d, SUM(journal_lines.credit) as c")
            ->get()->groupBy('ym');

        $months = [];

        for ($month = $start; $month->lessThanOrEqualTo($end); $month = $month->addMonth()) {
            $income = 0;
            $expense = 0;

            foreach ($rows->get($month->format('Y-m'), collect()) as $row) {
                $row->type === 'income' ? $income += (int) $row->c - (int) $row->d : $expense += (int) $row->d - (int) $row->c;
            }

            $months[] = ['month' => $month->format('Y-m'), 'income' => $income, 'expense' => $expense];
        }

        return $months;
    }

    /**
     * What the farm owns and owes at the end of the period.
     *
     * @return array{money:int, animals:int, things:int, receivable:int, assets:int, owed:int, net:int, capital:int, earned:int, money_places: list<array{name:string, balance:int}>}
     */
    public function position(CarbonInterface $at): array
    {
        $sums = JournalLine::query()->toBase()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->whereDate('journal_entries.date', '<=', $at->toDateString())
            ->groupBy('journal_lines.account_id')
            ->selectRaw('journal_lines.account_id as id, SUM(journal_lines.debit) - SUM(journal_lines.credit) as net')
            ->pluck('net', 'id');

        $out = ['money' => 0, 'animals' => 0, 'things' => 0, 'receivable' => 0, 'owed' => 0, 'capital' => 0, 'earned' => 0];
        $places = [];
        $capitalRoot = Account::query()->where('code', AccountCodes::CAPITAL)->value('id');

        foreach (Account::query()->get() as $account) {
            $net = (int) ($sums[$account->id] ?? 0);

            if ($net === 0) {
                continue;
            }

            $balance = $account->isDebitNormal() ? $net : -$net;

            match (true) {
                in_array($account->subtype, Account::MONEY_SUBTYPES, true) => [$out['money'] += $balance, $places[] = ['name' => $account->name, 'balance' => $balance]],
                $account->code === AccountCodes::LIVESTOCK => $out['animals'] += $balance,
                $account->type === 'asset' && ($account->code === AccountCodes::RECEIVABLE || $account->subtype === 'lent') => $out['receivable'] += $balance,
                $account->type === 'asset' => $out['things'] += $balance,
                $account->type === 'liability' => $out['owed'] += $balance,
                $account->type === 'equity' && ($account->id === $capitalRoot || $account->parent_id === $capitalRoot) => $out['capital'] += $balance,
                in_array($account->type, ['income', 'expense'], true) => $out['earned'] += $account->type === 'income' ? $balance : -$balance,
                default => null,
            };
        }

        $out['assets'] = $out['money'] + $out['animals'] + $out['things'] + $out['receivable'];
        $out['net'] = $out['assets'] - $out['owed'];
        $out['money_places'] = $places;

        return $out;
    }

    /** @return array{now:int, now_value:int, bought:int, bought_cost:int, sold:int, sold_amount:int, died:int, by_center: list<array{name:string, count:int}>} */
    public function herd(CarbonInterface $from, CarbonInterface $to): array
    {
        $running = CostCenter::runningIds();
        $active = Animal::query()->where('status', 'active')->whereIn('cost_center_id', $running)->with('costCenter:id,name,sort')->get();
        $between = fn (string $column) => Animal::query()->whereBetween($column, [$from->toDateString(), $to->toDateString()]);

        return [
            'now' => $active->count(),
            'now_value' => (int) $active->sum('book_value'),
            'bought' => $between('acquired_on')->count(),
            'bought_cost' => (int) $between('acquired_on')->sum('purchase_cost'),
            'sold' => $between('disposed_on')->where('status', 'sold')->count(),
            'sold_amount' => (int) $between('disposed_on')->where('status', 'sold')->sum('sale_amount'),
            'died' => $between('disposed_on')->where('status', 'dead')->count(),
            'by_center' => array_values($active->groupBy('cost_center_id')
                ->sortBy(fn ($g) => $g->first()->costCenter->sort)
                ->map(fn ($g) => ['name' => $g->first()->costCenter->name, 'count' => $g->count()])->all()),
        ];
    }
}
