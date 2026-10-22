<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Batch;
use App\Models\CostCenter;
use App\Models\CropSeason;
use App\Models\JournalLine;
use App\Models\MilkRecord;
use App\Models\Plot;
use Carbon\CarbonInterface;

/**
 * Where the farm makes and loses money, and the few numbers that decide it: feed cost per
 * litre of milk, the milk price that only covers cost, what a kilo of fattening gain costs,
 * profit per bigha. Also the starting figures for the "what if" planner, taken from the
 * farm's own history so plans start from reality.
 */
class ProfitBook
{
    public function __construct(
        private ReportService $reports,
        private AllocationService $allocation,
        private HerdCare $care,
        private CropBook $crops,
    ) {}

    /**
     * Each project (leaf category) over the period: income, cost, profit, margin.
     *
     * @return list<array{id:int, name:string, kind:string, income:int, expense:int, profit:int, margin:float|null}>
     */
    public function projects(CarbonInterface $from, CarbonInterface $to): array
    {
        $totals = $this->reports->centerTotals($from, $to);
        $rows = [];

        foreach (CostCenter::query()->whereNotIn('kind', ['group', 'overhead'])->orderBy('sort')->get() as $center) {
            $t = $totals[$center->id] ?? null;

            if ($t === null) {
                continue;
            }

            $income = $t['income'] + $t['internal_income'];
            $expense = $t['expense'] + $t['internal_expense'];

            $rows[] = [
                'id' => $center->id,
                'name' => $center->name,
                'kind' => $center->kind,
                'income' => $income,
                'expense' => $expense,
                'profit' => $income - $expense,
                'margin' => $income > 0 ? round(($income - $expense) / $income * 100, 1) : null,
            ];
        }

        usort($rows, fn ($a, $b) => $b['profit'] <=> $a['profit']);

        return $rows;
    }

    /**
     * Every animal with what it has cost and what it has brought: sale for sold ones, milk
     * at the farm's average milk price for cows, book value for those still on the farm.
     *
     * @return list<array<string, mixed>>
     */
    public function animals(CarbonInterface $from, CarbonInterface $to, ?float $milkPrice): array
    {
        $milk = MilkRecord::query()->whereNotNull('animal_id')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString().' 23:59:59'])
            ->selectRaw('animal_id, SUM(liters) as l')->groupBy('animal_id')->pluck('l', 'animal_id');
        $rows = [];

        $animals = Animal::query()->with(['costCenter:id,name', 'placements', 'weights'])
            ->where(fn ($q) => $q->where('status', 'active')->orWhereDate('disposed_on', '>=', $from->toDateString()))
            ->get();

        foreach ($animals as $animal) {
            $cost = $this->reports->animalCost($animal, $this->allocation);
            $spent = $cost['purchase'] + $cost['direct'] + $cost['shared'];
            $liters = (float) ($milk[$animal->id] ?? 0);
            $milkValue = $milkPrice !== null ? (int) round($liters * $milkPrice * 100) : 0;
            $growth = $this->care->growth($animal);

            $rows[] = [
                'id' => $animal->id,
                'tag' => $animal->tag,
                'name' => $animal->name,
                'center' => $animal->costCenter->name,
                'status' => $animal->status,
                'purchase' => $cost['purchase'],
                'upkeep' => $cost['direct'] + $cost['shared'],
                'spent' => $spent,
                'sale' => $cost['sale'],
                'milk_liters' => round($liters, 1),
                'milk_value' => $milkValue,
                'gain_per_day' => $growth['per_day'],
                // Sold: real profit. On the farm: what milk has paid back of its upkeep so far.
                'result' => $cost['sale'] !== null ? $cost['sale'] - $spent : $milkValue - ($cost['direct'] + $cost['shared']),
                'result_kind' => $cost['sale'] !== null ? 'sold' : ($liters > 0 ? 'milk' : 'growing'),
            ];
        }

        usort($rows, fn ($a, $b) => $b['result'] <=> $a['result']);

        return $rows;
    }

    /**
     * The handful of numbers that decide profit.
     *
     * @return array<string, float|int|null>
     */
    public function ratios(CarbonInterface $from, CarbonInterface $to): array
    {
        $gavi = CostCenter::query()->where('code', 'GAVI')->first();
        $liters = (float) MilkRecord::query()->whereBetween('date', [$from->toDateString(), $to->toDateString().' 23:59:59'])->sum('liters');
        $gaviIds = $gavi ? $gavi->descendantIds() : [];

        $sum = fn (array $ids, string $type, ?array $codes = null) => $ids === [] ? 0 : (int) JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->whereIn('journal_lines.cost_center_id', $ids)
            ->where('accounts.type', $type)
            ->when($codes, fn ($q) => $q->whereIn('accounts.code', $codes))
            ->whereBetween('journal_entries.date', [$from->toDateString(), $to->toDateString().' 23:59:59'])
            ->selectRaw($type === 'income'
                ? 'COALESCE(SUM(journal_lines.credit) - SUM(journal_lines.debit), 0) as v'
                : 'COALESCE(SUM(journal_lines.debit) - SUM(journal_lines.credit), 0) as v')
            ->value('v');

        $feedCodes = ['5100', '5110', '5120', '5125', '5130'];
        $gaviFeed = $sum($gaviIds, 'expense', $feedCodes);
        $gaviCost = $sum($gaviIds, 'expense');
        $milkIncome = $sum($gaviIds, 'income', ['4100']);
        $soldLiters = $milkIncome > 0 && $liters > 0 ? $liters : null;

        // Fattening: what each kilo of live-weight gain cost, across open and closed batches.
        $batchSpent = 0;
        $batchGain = 0.0;

        foreach (Batch::query()->get() as $batch) {
            $card = $this->care->batchCard($batch);

            foreach ($card['animals'] as $a) {
                if ($a['first_kg'] !== null && $a['last_kg'] !== null && $a['last_kg'] > $a['first_kg']) {
                    $batchGain += $a['last_kg'] - $a['first_kg'];
                }
            }

            $batchSpent += $card['cost']['direct'] + $card['cost']['shared'];
        }

        $seasons = CropSeason::query()->whereNotNull('harvested_on')->get()->map(fn ($s) => $this->crops->seasonCard($s));

        return [
            'milk_liters' => round($liters, 1),
            'milk_price' => $soldLiters ? round($milkIncome / 100 / $soldLiters, 2) : null,
            // Zero cost means nothing recorded yet, not free milk: show "no figures yet" instead of ৳0.
            'feed_per_liter' => $liters > 0 && $gaviFeed > 0 ? round($gaviFeed / 100 / $liters, 2) : null,
            'cost_per_liter' => $liters > 0 && $gaviCost > 0 ? round($gaviCost / 100 / $liters, 2) : null,
            'cost_per_kg_gain' => $batchGain > 0 ? round($batchSpent / 100 / $batchGain, 2) : null,
            // All ratios are in taka (not poisha), for display and the planner.
            'profit_per_bigha' => $seasons->isNotEmpty() ? (int) round(($seasons->avg('profit_per_bigha') ?? 0) / 100) : null,
        ];
    }

    /**
     * Starting figures for the planner, from the farm's own records where they exist and
     * sensible local defaults where they do not yet.
     *
     * @return array<string, float|int>
     */
    public function plannerDefaults(): array
    {
        $plots = Plot::query()->where('tenure', 'lease')->whereNotNull('yearly_rent')->get();
        $rentPerBigha = $plots->isNotEmpty()
            ? (int) round($plots->sum(fn (Plot $p) => $p->yearly_rent / max(0.01, $this->crops->bigha($p->area_decimals, $p->bigha_decimals))) / $plots->count() / 100)
            : 15000;

        $last = CropSeason::query()->whereNotNull('harvested_on')->orderByDesc('harvested_on')->first();
        $card = $last ? $this->crops->seasonCard($last) : null;
        $straw = null;

        foreach ($card['outputs'] ?? [] as $output) {
            if ($output['kind'] !== 'product') {
                $straw = $output;
                break;
            }
        }

        $ratios = $this->ratios(now()->subYear(), now());
        $batch = Batch::query()->orderByDesc('started_on')->first();
        $batchCard = $batch ? $this->care->batchCard($batch) : null;
        $perHeadDay = $batchCard && $batchCard['head'] > 0 && $batchCard['days'] > 0
            ? (int) round(($batchCard['cost']['direct'] + $batchCard['cost']['shared']) / 100 / $batchCard['head'] / $batchCard['days'])
            : 180;

        return [
            'rent_per_bigha' => $rentPerBigha,
            'cost_per_bigha' => $card && $card['cost_per_bigha'] ? (int) round($card['cost_per_bigha'] / 100) : 9000,
            'mon_per_bigha' => $card && $card['main'] && $card['main']['mon_per_bigha'] ? (float) $card['main']['mon_per_bigha'] : 20.0,
            'paddy_price_per_mon' => $card && $card['main'] && $card['main']['qty'] > 0 ? (int) round($card['main']['value'] / 100 / $card['main']['qty'] * CropBook::KG_PER_MON) : 1200,
            'straw_kg_per_bigha' => $straw && $card['bigha'] > 0 ? (int) round($straw['qty'] / $card['bigha']) : 1200,
            'straw_price_per_kg' => $straw && $straw['qty'] > 0 ? round($straw['value'] / 100 / $straw['qty'], 2) : 2.5,
            'fattening_buy_price' => 90000,
            'fattening_feed_per_day' => $perHeadDay,
            'fattening_days' => $batch->target_days ?? 120,
            'fattening_sale_price' => 130000,
            'cow_price' => 120000,
            'cow_liters_per_day' => 8,
            'milk_price' => (float) ($ratios['milk_price'] ?? 60),
            'cow_feed_per_day' => (int) round(((float) ($ratios['feed_per_liter'] ?? 0) ?: 25) * 8),
        ];
    }
}
