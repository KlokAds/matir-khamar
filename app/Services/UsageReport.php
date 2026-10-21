<?php

namespace App\Services;

use App\Models\CostCenter;
use App\Models\Item;
use App\Models\StockMovement;
use App\Support\Tr;
use Carbon\CarbonInterface;

/**
 * Where every kilo went, in farm words: grass cut from how much land, feed given to which
 * category for how many animals, fertiliser spread on which plot. Taka figures come from the
 * same stock movements the ledger was posted from, so this never disagrees with the books.
 */
class UsageReport
{
    /**
     * @return list<array<string, mixed>>
     */
    public function items(CarbonInterface $from, CarbonInterface $to): array
    {
        $movements = StockMovement::query()
            ->with('entry:id,type,reversal_of_id,reversed_by_id')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString().' 23:59:59'])
            // Cancelled entries and their reversals cancel out; leave both out.
            ->whereHas('entry', fn ($query) => $query->whereNull('reversed_by_id')->whereNull('reversal_of_id'))
            ->orderBy('date')
            ->get()
            ->groupBy('item_id');

        $items = Item::query()->whereIn('id', $movements->keys())->get()->keyBy('id');
        $centers = CostCenter::query()->pluck('name', 'id');
        $rows = [];

        foreach ($movements as $itemId => $list) {
            $item = $items[$itemId] ?? null;

            if ($item === null) {
                continue;
            }

            $produced = $list->where('direction', 'in')->filter(fn (StockMovement $m) => $m->entry->type === 'production');
            $bought = $list->where('direction', 'in')->filter(fn (StockMovement $m) => $m->entry->type === 'stock_purchase');
            $used = [];

            foreach ($list->where('direction', 'out')->groupBy('cost_center_id') as $centerId => $out) {
                // Several feeds a day (সকাল/দুপুর/বিকাল) share one herd: count each day once.
                $byDay = $out->groupBy(fn (StockMovement $m) => $m->date->toDateString());
                $animalDays = $byDay->sum(fn ($day) => (int) $day->max('head_count'));
                $qty = round((float) $out->sum('qty'), 3);
                $land = round((float) $out->sum('land_decimals'), 2);

                $used[] = [
                    'center_id' => (int) $centerId,
                    'center' => $centers[$centerId] ?? '—',
                    'qty' => $qty,
                    'value' => (int) $out->sum('value'),
                    'days' => $byDay->count(),
                    'animals' => $byDay->count() > 0 && $animalDays > 0 ? round($animalDays / $byDay->count(), 1) : null,
                    'per_animal_day' => $animalDays > 0 ? round($qty / $animalDays, 2) : null,
                    'land' => $land > 0 ? $land : null,
                    'per_land' => $land > 0 ? round($qty / $land, 2) : null,
                ];
            }

            usort($used, fn ($a, $b) => $b['qty'] <=> $a['qty']);
            $producedQty = round((float) $produced->sum('qty'), 3);
            $producedLand = round((float) $produced->sum('land_decimals'), 2);

            $rows[] = [
                'id' => $item->id,
                'name' => $item->name,
                'kind' => $item->kind,
                'unit' => Tr::get(Item::UNITS[$item->unit] ?? $item->unit),
                'produced' => $produced->isEmpty() ? null : [
                    'qty' => $producedQty,
                    'value' => (int) $produced->sum('value'),
                    'land' => $producedLand > 0 ? $producedLand : null,
                    'per_land' => $producedLand > 0 ? round($producedQty / $producedLand, 1) : null,
                    'from' => $produced->pluck('cost_center_id')->unique()->map(fn ($id) => $centers[$id] ?? '—')->values()->all(),
                ],
                'bought' => $bought->isEmpty() ? null : [
                    'qty' => round((float) $bought->sum('qty'), 3),
                    'value' => (int) $bought->sum('value'),
                ],
                'used' => $used,
                'used_qty' => round(array_sum(array_column($used, 'qty')), 3),
                'used_value' => array_sum(array_column($used, 'value')),
                'on_hand' => $item->qty_on_hand,
            ];
        }

        return $rows;
    }
}
