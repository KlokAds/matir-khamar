<?php

namespace App\Services;

use App\Models\CropSeason;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Plot;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Support\BanglaDate;
use App\Support\Tr;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Field crops in farm terms: land in bigha, paddy in মণ, and for each season what it cost,
 * what it gave, profit per bigha and what a kg of paddy really cost once the straw that went
 * to the cattle is valued.
 */
class CropBook
{
    /** A মণ, the way paddy is counted at the haat. */
    public const KG_PER_MON = 40;

    public function __construct(private StockService $stock) {}

    /** Decimals (শতাংশ) in one bigha here. Bigha differs by district; Naogaon mostly uses 33. */
    public function bighaDecimals(): float
    {
        $value = (float) Setting::get('land.bigha_decimals', '33');

        return $value > 0 ? $value : 33.0;
    }

    public function bigha(float $decimals): float
    {
        return round($decimals / $this->bighaDecimals(), 2);
    }

    /** @return array<string, mixed> */
    public function plotRow(Plot $plot): array
    {
        $plot->loadMissing('costCenter:id,name');
        $leftDays = $plot->lease_to ? (int) now()->startOfDay()->diffInDays($plot->lease_to, false) : null;

        return [
            'id' => $plot->id,
            'name' => $plot->name,
            'cost_center_id' => $plot->cost_center_id,
            'center' => $plot->costCenter?->name,
            'area_decimals' => $plot->area_decimals,
            'bigha' => $this->bigha($plot->area_decimals),
            'tenure' => $plot->tenure,
            'tenure_label' => Tr::get(Plot::TENURES[$plot->tenure] ?? $plot->tenure),
            'owner_name' => $plot->owner_name,
            'owner_phone' => $plot->owner_phone,
            'lease_from' => $plot->lease_from?->toDateString(),
            'lease_to' => $plot->lease_to?->toDateString(),
            'lease_days_left' => $leftDays,
            'yearly_rent' => $plot->yearly_rent,
            'rent_per_bigha' => $plot->yearly_rent && $plot->area_decimals > 0 ? (int) round($plot->yearly_rent / $this->bigha($plot->area_decimals)) : null,
            'notes' => $plot->notes,
            'is_active' => $plot->is_active,
        ];
    }

    /**
     * A plot's year: every season on it that started in the year (আমন → সরিষা → বোরো),
     * each season's profit taken in proportion to the area this plot gave it.
     *
     * @return array{year:int, seasons:list<string>, profit:int, profit_per_bigha:int|null}
     */
    public function plotYear(Plot $plot, int $year): array
    {
        $profit = 0;
        $names = [];

        foreach ($plot->seasons()->whereYear('started_on', $year)->get() as $season) {
            $card = $this->seasonCard($season);
            $share = $card['area_decimals'] > 0 ? (float) $season->getRelationValue('pivot')->area_decimals / $card['area_decimals'] : 0;
            $profit += (int) round($card['profit'] * $share);
            $names[] = $season->name;
        }

        $bigha = $this->bigha($plot->area_decimals);

        return [
            'year' => $year,
            'seasons' => $names,
            'profit' => $profit,
            'profit_per_bigha' => $names !== [] && $bigha > 0 ? (int) round($profit / $bigha) : null,
        ];
    }

    /** @return array<string, mixed> */
    public function seasonCard(CropSeason $season): array
    {
        $season->loadMissing(['plots', 'costCenter']);
        $ids = $season->costCenter->descendantIds();
        $from = $season->started_on->toDateString();
        $to = ($season->closed_on ?? now())->toDateString().' 23:59:59';

        $costs = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->whereIn('journal_lines.cost_center_id', $ids)
            ->where('accounts.type', 'expense')
            ->whereBetween('journal_entries.date', [$from, $to])
            ->groupBy('accounts.id', 'accounts.name')
            ->selectRaw('accounts.name as name, SUM(journal_lines.debit) - SUM(journal_lines.credit) as amount')
            ->get()
            ->map(fn ($row) => ['name' => (string) $row->getAttribute('name'), 'amount' => (int) $row->getAttribute('amount')])
            ->filter(fn ($row) => $row['amount'] !== 0)
            ->sortByDesc('amount')
            ->values();

        $sales = (int) JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->whereIn('journal_lines.cost_center_id', $ids)
            ->where('accounts.type', 'income')
            ->where(fn ($q) => $q->whereNull('accounts.subtype')->orWhere('accounts.subtype', '!=', 'internal'))
            ->whereBetween('journal_entries.date', [$from, $to])
            ->selectRaw('COALESCE(SUM(journal_lines.credit) - SUM(journal_lines.debit), 0) as net')
            ->value('net');

        $outputs = StockMovement::query()
            ->with('item:id,name,unit,kind')
            ->where('direction', 'in')
            ->whereHas('entry', fn ($q) => $q->where('reference', $season->reference())->whereNull('reversed_by_id'))
            ->get()
            ->groupBy('item_id')
            ->map(fn ($rows) => [
                'item_id' => (int) $rows->first()->item_id,
                'name' => $rows->first()->item->name,
                'kind' => $rows->first()->item->kind,
                'unit' => Tr::get(Item::UNITS[$rows->first()->item->unit] ?? $rows->first()->item->unit),
                'qty' => round((float) $rows->sum('qty'), 2),
                'value' => (int) $rows->sum('value'),
            ])
            ->sortByDesc('value')
            ->values();

        $cost = (int) $costs->sum('amount');
        $value = (int) $outputs->sum('value');
        $decimals = $season->areaDecimals();
        $bigha = $this->bigha($decimals);
        // The main crop is the largest product (paddy); straw and the rest are by-products.
        $main = $outputs->firstWhere('kind', 'product') ?? $outputs->first();
        $byProducts = $main ? $value - $main['value'] : 0;
        $mainKgCost = $main && $main['qty'] > 0 ? round(($cost - $byProducts) / 100 / $main['qty'], 2) : null;
        $mainMon = $main ? round($main['qty'] / self::KG_PER_MON, 2) : null;
        $days = (int) $season->started_on->diffInDays($season->harvested_on ?? $season->closed_on ?? now()->startOfDay());

        return [
            'id' => $season->id,
            'name' => $season->name,
            'crop' => $season->crop,
            'variety' => $season->variety,
            'cost_center_id' => $season->cost_center_id,
            'center' => $season->costCenter->name,
            'started_on' => $season->started_on->toDateString(),
            'sown_on' => $season->sown_on?->toDateString(),
            'harvested_on' => $season->harvested_on?->toDateString(),
            'closed_on' => $season->closed_on?->toDateString(),
            'days' => $days,
            'span' => BanglaDate::span($days),
            'notes' => $season->notes,
            'target_mon_per_bigha' => $season->target_mon_per_bigha,
            'plots' => $season->plots->map(fn (Plot $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'area_decimals' => (float) $p->getRelationValue('pivot')->area_decimals,
            ])->values(),
            'area_decimals' => $decimals,
            'bigha' => $bigha,
            'costs' => $costs,
            'cost' => $cost,
            'outputs' => $outputs,
            'value' => $value,
            'sales' => $sales,
            'profit' => $value - $cost,
            'profit_per_bigha' => $bigha > 0 && $outputs->isNotEmpty() ? (int) round(($value - $cost) / $bigha) : null,
            'cost_per_bigha' => $bigha > 0 ? (int) round($cost / $bigha) : null,
            'main' => $main ? [
                ...$main,
                'mon' => $mainMon,
                'mon_per_bigha' => $bigha > 0 && $mainMon !== null ? round($mainMon / $bigha, 2) : null,
                'cost_per_kg' => $mainKgCost,
                'cost_per_mon' => $mainKgCost !== null ? round($mainKgCost * self::KG_PER_MON, 2) : null,
            ] : null,
            'by_products' => $byProducts,
        ];
    }

    /**
     * Book a harvest: every output (paddy, straw…) goes into stock at today's market rate,
     * credited to the crop project and marked with the season.
     *
     * @param  list<array{item_id:int|string, qty:float|string, rate:float|string}>  $outputs  rate in taka per unit
     * @return list<JournalEntry>
     */
    public function harvest(CropSeason $season, string $date, array $outputs): array
    {
        $outputs = array_values(array_filter($outputs, fn ($o) => (float) $o['qty'] > 0 && (float) $o['rate'] > 0));

        if ($outputs === []) {
            throw ValidationException::withMessages(['outputs' => Tr::get('অন্তত একটি ফসলের পরিমাণ ও দর দিন।')]);
        }

        return DB::transaction(function () use ($season, $date, $outputs) {
            $entries = [];

            foreach ($outputs as $output) {
                $item = Item::query()->findOrFail((int) $output['item_id']);
                $entries[] = $this->stock->produce(
                    $item,
                    $date,
                    (float) $output['qty'],
                    (int) round((float) $output['rate'] * 100),
                    $season->cost_center_id,
                    "{$season->name}: {$item->name} তোলা",
                    null,
                    $season->reference(),
                );
            }

            if ($season->harvested_on === null || $season->harvested_on->lessThan(Date::parse($date))) {
                $season->update(['harvested_on' => $date]);
            }

            return $entries;
        });
    }

    /**
     * Leases ending within a month, for the reminder list.
     *
     * @return list<array{kind:string, title:string, detail:string, href:string, when:string, urgent:bool}>
     */
    public function leaseReminders(CarbonInterface $today): array
    {
        $items = [];

        foreach (Plot::query()->where('is_active', true)->where('tenure', 'lease')->whereNotNull('lease_to')
            ->whereDate('lease_to', '<=', $today->addDays(30))->get() as $plot) {
            /** @var CarbonInterface $end */
            $end = $plot->lease_to;
            $left = (int) $today->diffInDays($end, false);
            $items[] = [
                'kind' => 'lease',
                'title' => $left >= 0
                    ? Tr::get(':name: লিজ শেষ হবে :n দিন পর', ['name' => $plot->name, 'n' => $left])
                    : Tr::get(':name: লিজ শেষ হয়ে গেছে', ['name' => $plot->name]),
                'detail' => Tr::get('মালিক :owner · নবায়নের কথা বলুন', ['owner' => $plot->owner_name ?? '—']),
                'href' => '/land',
                'when' => $end->toDateString(),
                'urgent' => $left <= 7,
            ];
        }

        return $items;
    }
}
