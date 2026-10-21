<?php

namespace App\Services;

use App\Models\Item;
use App\Models\StockMovement;
use App\Support\Tr;

/**
 * "ভুসি আর ৪ দিন চলবে": how long each item lasts at the rate it was used over the last two
 * weeks, so it can be bought before it runs out rather than after.
 */
class StockForecast
{
    public const WINDOW_DAYS = 14;

    /** Warn when an item has this many days or fewer left. */
    public const WARN_DAYS = 5;

    /**
     * @return list<array{id:int, name:string, unit:string, qty_on_hand:float, per_day:float|null, days_left:float|null, low:bool}>
     */
    public function items(): array
    {
        $since = now()->subDays(self::WINDOW_DAYS)->startOfDay();
        $used = StockMovement::query()
            ->where('direction', 'out')
            ->where('date', '>=', $since->toDateString())
            ->whereHas('entry', fn ($query) => $query->whereNull('reversed_by_id')->whereNull('reversal_of_id'))
            ->selectRaw('item_id, SUM(qty) as qty')
            ->groupBy('item_id')
            ->pluck('qty', 'item_id');

        $rows = [];

        foreach (Item::query()->where('is_active', true)->orderBy('name')->get() as $item) {
            $perDay = isset($used[$item->id]) ? round((float) $used[$item->id] / self::WINDOW_DAYS, 2) : null;
            $daysLeft = $perDay ? round($item->qty_on_hand / $perDay, 1) : null;
            $belowLevel = $item->reorder_level !== null && $item->qty_on_hand <= $item->reorder_level;

            $rows[] = [
                'id' => $item->id,
                'name' => $item->name,
                'unit' => Tr::get(Item::UNITS[$item->unit] ?? $item->unit),
                'qty_on_hand' => $item->qty_on_hand,
                'per_day' => $perDay,
                'days_left' => $daysLeft,
                'low' => $belowLevel || ($daysLeft !== null && $daysLeft <= self::WARN_DAYS),
            ];
        }

        return $rows;
    }

    /** @return list<array{id:int, name:string, unit:string, qty_on_hand:float, per_day:float|null, days_left:float|null, low:bool}> */
    public function low(): array
    {
        return array_values(array_filter($this->items(), fn ($row) => $row['low']));
    }
}
