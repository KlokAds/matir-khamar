<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Animal;
use App\Models\BudgetHead;
use App\Models\CostCenter;
use App\Models\JournalLine;
use App\Models\MilkRecord;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every number on the dashboards comes from journal lines, so a category's
 * page, the farm total and the bank report can never tell different stories.
 */
class ReportService
{
    /**
     * Income and expense per center for a period, own figures only.
     *
     * @return array<int, array{income:int, expense:int, internal_income:int, internal_expense:int}>
     */
    public function centerTotals(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = JournalLine::query()
            ->toBase()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->whereIn('accounts.type', ['income', 'expense'])
            ->whereNotNull('journal_lines.cost_center_id')
            ->whereBetween('journal_entries.date', [$from->toDateString(), $to->toDateString().' 23:59:59'])
            ->groupBy('journal_lines.cost_center_id', 'accounts.type', DB::raw("COALESCE(accounts.subtype, '') = 'internal'"))
            ->selectRaw("journal_lines.cost_center_id as center, accounts.type as type, COALESCE(accounts.subtype, '') = 'internal' as internal, SUM(journal_lines.debit) as d, SUM(journal_lines.credit) as c")
            ->get();

        $totals = [];

        foreach ($rows as $row) {
            $center = (int) $row->center;
            $totals[$center] ??= ['income' => 0, 'expense' => 0, 'internal_income' => 0, 'internal_expense' => 0];
            $prefix = $row->internal ? 'internal_' : '';

            if ($row->type === 'income') {
                $totals[$center][$prefix.'income'] += (int) $row->c - (int) $row->d;
            } else {
                $totals[$center][$prefix.'expense'] += (int) $row->d - (int) $row->c;
            }
        }

        return $totals;
    }

    /**
     * The center tree with own and rolled-up totals. A category shows its
     * internal transfers (own grass in, fed out); the farm-wide figure drops
     * them, since money never left the farm.
     *
     * @return list<array<string, mixed>>
     */
    public function centerTree(CarbonInterface $from, CarbonInterface $to): array
    {
        $totals = $this->centerTotals($from, $to);
        $centers = CostCenter::query()->orderBy('sort')->orderBy('name')->get();
        $byParent = $centers->groupBy(fn ($center) => $center->parent_id ?? 0);

        $build = function (int $parentId) use (&$build, $byParent, $totals): array {
            $nodes = [];

            foreach ($byParent->get($parentId, collect()) as $center) {
                $children = $build($center->id);
                $own = $totals[$center->id] ?? ['income' => 0, 'expense' => 0, 'internal_income' => 0, 'internal_expense' => 0];
                $rolled = $own;

                foreach ($children as $child) {
                    foreach ($rolled as $key => $value) {
                        $rolled[$key] = $value + $child['rolled'][$key];
                    }
                }

                $nodes[] = [
                    'id' => $center->id,
                    'code' => $center->code,
                    'name' => $center->name,
                    'kind' => $center->kind,
                    'is_active' => $center->is_active,
                    'holds_animals' => $center->holds_animals,
                    'own' => $own,
                    'rolled' => $rolled,
                    'income' => $rolled['income'] + $rolled['internal_income'],
                    'expense' => $rolled['expense'] + $rolled['internal_expense'],
                    'profit' => $rolled['income'] + $rolled['internal_income'] - $rolled['expense'] - $rolled['internal_expense'],
                    'children' => $children,
                ];
            }

            return $nodes;
        };

        return $build(0);
    }

    /**
     * Farm-wide result with internal transfers removed.
     *
     * @return array{income:int, expense:int, profit:int}
     */
    public function farmTotals(CarbonInterface $from, CarbonInterface $to): array
    {
        $income = 0;
        $expense = 0;

        foreach ($this->centerTotals($from, $to) as $row) {
            $income += $row['income'];
            $expense += $row['expense'];
        }

        // Income or expense posted without a center (rare) still belongs to the farm.
        $unassigned = $this->accountTotals($from, $to, null, onlyUnassigned: true);
        $income += $unassigned['income'];
        $expense += $unassigned['expense'];

        return ['income' => $income, 'expense' => $expense, 'profit' => $income - $expense];
    }

    /**
     * Totals for one center (and its children) by account.
     *
     * @param  list<int>|null  $centerIds
     * @return array{income:int, expense:int, accounts: list<array{id:int, code:string, name:string, type:string, internal:bool, amount:int}>}
     */
    public function accountTotals(CarbonInterface $from, CarbonInterface $to, ?array $centerIds, bool $onlyUnassigned = false): array
    {
        $query = JournalLine::query()
            ->toBase()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->whereIn('accounts.type', ['income', 'expense'])
            ->whereBetween('journal_entries.date', [$from->toDateString(), $to->toDateString().' 23:59:59']);

        if ($onlyUnassigned) {
            $query->whereNull('journal_lines.cost_center_id')->where(fn ($q) => $q->whereNull('accounts.subtype')->orWhere('accounts.subtype', '!=', Account::INTERNAL_SUBTYPE));
        } elseif ($centerIds !== null) {
            $query->whereIn('journal_lines.cost_center_id', $centerIds);
        }

        $rows = $query
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type', 'accounts.subtype')
            ->selectRaw('accounts.id, accounts.code, accounts.name, accounts.type, accounts.subtype, SUM(journal_lines.debit) as d, SUM(journal_lines.credit) as c')
            ->orderBy('accounts.code')
            ->get();

        $accounts = [];
        $income = 0;
        $expense = 0;

        foreach ($rows as $row) {
            $amount = $row->type === 'income' ? (int) $row->c - (int) $row->d : (int) $row->d - (int) $row->c;

            if ($amount === 0) {
                continue;
            }

            $row->type === 'income' ? $income += $amount : $expense += $amount;
            $accounts[] = [
                'id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'type' => $row->type,
                'internal' => $row->subtype === Account::INTERNAL_SUBTYPE,
                'amount' => $amount,
            ];
        }

        return ['income' => $income, 'expense' => $expense, 'accounts' => $accounts];
    }

    /**
     * Month-by-month income and expense for the last N months.
     *
     * @param  list<int>|null  $centerIds
     * @return list<array{month:string, label:string, income:int, expense:int}>
     */
    public function monthlyTrend(int $months, ?array $centerIds, bool $excludeInternal): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);
        $monthExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', journal_entries.date)"
            : "DATE_FORMAT(journal_entries.date, '%Y-%m')";

        $query = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->whereIn('accounts.type', ['income', 'expense'])
            ->whereDate('journal_entries.date', '>=', $start);

        if ($centerIds !== null) {
            $query->whereIn('journal_lines.cost_center_id', $centerIds);
        }

        if ($excludeInternal) {
            $query->where(fn ($q) => $q->whereNull('accounts.subtype')->orWhere('accounts.subtype', '!=', Account::INTERNAL_SUBTYPE));
        }

        $rows = $query
            ->groupBy(DB::raw($monthExpression), 'accounts.type')
            ->selectRaw("{$monthExpression} as ym, accounts.type as type, SUM(journal_lines.debit) as d, SUM(journal_lines.credit) as c")
            ->get()
            ->groupBy('ym');

        $trend = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $income = 0;
            $expense = 0;

            foreach ($rows->get($key, collect()) as $row) {
                $row->type === 'income'
                    ? $income += (int) $row->c - (int) $row->d
                    : $expense += (int) $row->d - (int) $row->c;
            }

            $trend[] = ['month' => $key, 'label' => $month->format('M y'), 'income' => $income, 'expense' => $expense];
        }

        return $trend;
    }

    /**
     * Balances of every money, asset, loan and capital account.
     *
     * @param  list<string>|null  $types
     * @return Collection<int, array{id:int, code:string, name:string, type:string, subtype:?string, balance:int}>
     */
    public function balances(?array $types = null): Collection
    {
        $sums = JournalLine::query()
            ->toBase()
            ->groupBy('account_id')
            ->selectRaw('account_id, SUM(debit) as d, SUM(credit) as c')
            ->get()
            ->keyBy('account_id');

        return Account::query()
            ->when($types, fn ($query) => $query->whereIn('type', $types))
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($sums) {
                $row = $sums->get($account->id);
                $net = $row ? (int) $row->d - (int) $row->c : 0;

                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'subtype' => $account->subtype,
                    'balance' => $account->isDebitNormal() ? $net : -$net,
                ];
            });
    }

    /**
     * Udyog budget heads: approved vs spent, and how many spends lack a voucher.
     *
     * @return array<int, array{id:int, code:string, name:string, details:?string, approved:int, spent:int, remaining:int, missing_vouchers:int}>
     */
    public function budget(): array
    {
        $spent = JournalLine::query()
            ->whereNotNull('budget_head_id')
            ->groupBy('budget_head_id')
            ->selectRaw('budget_head_id, SUM(debit) - SUM(credit) as net')
            ->pluck('net', 'budget_head_id');

        $missing = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->whereNotNull('journal_lines.budget_head_id')
            ->whereNull('journal_entries.reversed_by_id')
            ->where('journal_entries.type', '!=', 'reversal')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('attachments')->whereColumn('attachments.journal_entry_id', 'journal_entries.id'))
            ->groupBy('journal_lines.budget_head_id')
            ->selectRaw('journal_lines.budget_head_id, COUNT(DISTINCT journal_entries.id) as n')
            ->pluck('n', 'budget_head_id');

        return BudgetHead::query()->orderBy('sort')->get()->map(fn (BudgetHead $head) => [
            'id' => $head->id,
            'code' => $head->code,
            'name' => $head->name,
            'details' => $head->details,
            'approved' => $head->approved_amount,
            'spent' => (int) ($spent[$head->id] ?? 0),
            'remaining' => $head->approved_amount - (int) ($spent[$head->id] ?? 0),
            'missing_vouchers' => (int) ($missing[$head->id] ?? 0),
        ])->all();
    }

    public function milkLiters(CarbonInterface $from, CarbonInterface $to): float
    {
        return (float) MilkRecord::query()->whereBetween('date', [$from->toDateString(), $to->toDateString().' 23:59:59'])->sum('liters');
    }

    /**
     * An animal's share of its centers' costs, month by month, by animal-days,
     * plus its own direct costs. Gives a true profit per sold motataja.
     *
     * @return array{direct:int, shared:int, purchase:int, sale:?int, profit:?int}
     */
    public function animalCost(Animal $animal, AllocationService $allocation): array
    {
        $direct = (int) JournalLine::query()
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_lines.animal_id', $animal->id)
            ->where('accounts.type', 'expense')
            ->whereNotIn('accounts.code', [AccountCodes::COST_OF_ANIMALS_SOLD, AccountCodes::ANIMAL_LOSS])
            ->selectRaw('COALESCE(SUM(journal_lines.debit) - SUM(journal_lines.credit), 0) as net')
            ->value('net');

        $shared = 0;

        foreach ($animal->placements as $placement) {
            $cursor = $placement->from_date->copy()->startOfMonth();
            $end = ($placement->to_date ?? now())->copy();

            while ($cursor->lessThanOrEqualTo($end)) {
                $monthStart = $cursor->copy()->startOfMonth();
                $monthEnd = $cursor->copy()->endOfMonth();
                $centerDays = $allocation->animalDays($cursor)[$placement->cost_center_id] ?? 0;

                if ($centerDays > 0) {
                    $from = $placement->from_date->max($monthStart);
                    $to = ($placement->to_date ? $placement->to_date->copy()->subDay() : now())->min($monthEnd);
                    $ownDays = max(0, (int) $from->diffInDays($to) + 1);

                    $centerExpense = (int) JournalLine::query()
                        ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
                        ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
                        ->where('journal_lines.cost_center_id', $placement->cost_center_id)
                        ->whereNull('journal_lines.animal_id')
                        ->where('accounts.type', 'expense')
                        ->whereBetween('journal_entries.date', [$monthStart->toDateString(), $monthEnd->toDateString().' 23:59:59'])
                        ->selectRaw('COALESCE(SUM(journal_lines.debit) - SUM(journal_lines.credit), 0) as net')
                        ->value('net');

                    $shared += (int) round($centerExpense * $ownDays / $centerDays);
                }

                $cursor = $cursor->addMonthNoOverflow()->startOfMonth();
            }
        }

        $profit = $animal->sale_amount !== null
            ? $animal->sale_amount - $animal->purchase_cost - $direct - $shared
            : null;

        return [
            'direct' => $direct,
            'shared' => $shared,
            'purchase' => $animal->purchase_cost,
            'sale' => $animal->sale_amount,
            'profit' => $profit,
        ];
    }
}
