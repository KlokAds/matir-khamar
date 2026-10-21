<?php

namespace App\Services;

use App\Models\AnimalPlacement;
use App\Models\CostCenter;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Support\BanglaDate;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Shared costs (electricity, a labourer who looks after every shed) are
 * first booked to the "Shadharon" center, then shared out at month end:
 * by animal-days, or by percentages the owner sets. Each expense keeps its
 * own account, so electricity stays electricity inside every category.
 */
class AllocationService
{
    public function __construct(private Ledger $ledger) {}

    /**
     * Net expense per account sitting in the source center for the month.
     *
     * @return array<int, int> account_id => poisha
     */
    public function pool(CostCenter $source, CarbonInterface $month): array
    {
        return JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_lines.cost_center_id', $source->id)
            ->where('accounts.type', 'expense')
            ->whereBetween('journal_entries.date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString().' 23:59:59'])
            ->groupBy('journal_lines.account_id')
            ->selectRaw('journal_lines.account_id, SUM(journal_lines.debit) - SUM(journal_lines.credit) as net')
            ->pluck('net', 'account_id')
            ->map(fn ($net) => (int) $net)
            ->filter(fn ($net) => $net > 0)
            ->all();
    }

    /**
     * Animal-days each animal-holding center had in the month.
     *
     * @return array<int, int> cost_center_id => animal-days
     */
    public function animalDays(CarbonInterface $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $days = [];

        $placements = AnimalPlacement::query()
            ->whereDate('from_date', '<=', $end)
            ->where(fn ($query) => $query->whereNull('to_date')->orWhereDate('to_date', '>', $start))
            ->get();

        foreach ($placements as $placement) {
            $from = $placement->from_date->max($start);
            $to = $placement->to_date ? $placement->to_date->copy()->subDay()->min($end) : $end;
            $count = (int) $from->diffInDays($to) + 1;

            if ($count > 0) {
                $days[$placement->cost_center_id] = ($days[$placement->cost_center_id] ?? 0) + $count;
            }
        }

        return $days;
    }

    /**
     * Splits every pooled account across the targets by weight. The largest
     * target absorbs the rounding so the source empties to the poisha.
     *
     * @param  array<int, float|int>  $weights  cost_center_id => weight
     * @return array{lines: list<array<string, int|null>>, shares: array<int, int>, total: int}
     */
    public function preview(CostCenter $source, CarbonInterface $month, array $weights): array
    {
        $weights = array_filter($weights, fn ($weight) => $weight > 0);
        unset($weights[$source->id]);

        if ($weights === []) {
            throw ValidationException::withMessages(['weights' => 'ভাগ করার জন্য অন্তত একটি ক্যাটাগরি ও তার অংশ দিন।']);
        }

        $pool = $this->pool($source, $month);
        $weightTotal = array_sum($weights);
        arsort($weights);
        $largest = array_key_first($weights);
        $lines = [];
        $shares = array_fill_keys(array_keys($weights), 0);

        foreach ($pool as $accountId => $amount) {
            $given = 0;

            foreach ($weights as $centerId => $weight) {
                $share = $centerId === $largest ? 0 : (int) floor($amount * $weight / $weightTotal);
                $given += $share;
                $shares[$centerId] += $share;

                if ($share > 0) {
                    $lines[] = ['account_id' => $accountId, 'cost_center_id' => $centerId, 'debit' => $share];
                }
            }

            $remainder = $amount - $given;
            $shares[$largest] += $remainder;
            $lines[] = ['account_id' => $accountId, 'cost_center_id' => $largest, 'debit' => $remainder];
            $lines[] = ['account_id' => $accountId, 'cost_center_id' => $source->id, 'credit' => $amount];
        }

        return ['lines' => $lines, 'shares' => $shares, 'total' => array_sum($pool)];
    }

    /** @param  array<int, float|int>  $weights */
    public function post(CostCenter $source, CarbonInterface $month, array $weights, string $method): JournalEntry
    {
        $preview = $this->preview($source, $month, $weights);

        if ($preview['total'] === 0) {
            throw ValidationException::withMessages(['month' => 'এই মাসে "'.$source->name.'"-এ ভাগ করার মতো কোনো খরচ নেই।']);
        }

        $label = $method === 'animal_days' ? 'পশু-দিন অনুযায়ী' : 'শতাংশ অনুযায়ী';

        return DB::transaction(fn () => $this->ledger->post(
            'allocation',
            $month->copy()->endOfMonth()->toDateString(),
            "{$source->name} খরচ ভাগ — ".BanglaDate::month($month)." ({$label})",
            $preview['lines'],
            ['reference' => 'alloc-'.$source->id.'-'.$month->format('Y-m')],
        ));
    }
}
