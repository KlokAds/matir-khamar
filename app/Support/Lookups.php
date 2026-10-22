<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Animal;
use App\Models\BudgetHead;
use App\Models\CostCenter;
use App\Models\Item;
use App\Models\Party;

/** Option lists the entry forms need, in one place. */
final class Lookups
{
    /** @return list<array{id:int, name:string, code:string, kind:string, holds_animals:bool, depth:int, is_active:bool}> */
    public static function centers(bool $activeOnly = true): array
    {
        $centers = CostCenter::query()->orderBy('sort')->orderBy('name')->get();
        $byParent = $centers->groupBy(fn ($center) => $center->parent_id ?? 0);
        $flat = [];

        $walk = function (int $parentId, int $depth) use (&$walk, &$flat, $byParent, $activeOnly) {
            foreach ($byParent->get($parentId, collect()) as $center) {
                // A project that is off hides everything inside it too.
                if ($activeOnly && ! $center->is_active) {
                    continue;
                }

                $flat[] = [
                    'id' => $center->id,
                    'name' => $center->name,
                    'code' => $center->code,
                    'kind' => $center->kind,
                    'holds_animals' => $center->holds_animals,
                    'depth' => $depth,
                    'is_active' => $center->is_active,
                ];
                $walk($center->id, $depth + 1);
            }
        };

        $walk(0, 0);

        return $flat;
    }

    /** @return array<int, Account> id, name, code, subtype */
    public static function accounts(string ...$types): array
    {
        // Accounts the system posts to by itself (internal, animal cost) stay out of hand entry.
        return Account::query()
            ->whereIn('type', $types)
            ->where('is_active', true)
            ->whereNotNull('parent_id')
            ->where(fn ($query) => $query->whereNull('subtype')->orWhereNotIn('subtype', ['animal_cost', Account::INTERNAL_SUBTYPE]))
            ->orderBy('code')
            ->get(['id', 'name', 'code', 'subtype'])
            ->all();
    }

    /** @return array<int, Account> id, name, code, subtype */
    public static function moneyAccounts(): array
    {
        return Account::query()->money()->orderBy('code')->get(['id', 'name', 'code', 'subtype'])->all();
    }

    /** @return array<int, Account> id, name, code, subtype */
    public static function assetAccounts(): array
    {
        return Account::query()->whereIn('subtype', ['land', 'fixed_asset'])->where('is_active', true)->orderBy('code')->get(['id', 'name', 'code', 'subtype'])->all();
    }

    /** @return array<int, BudgetHead> id, code, name */
    public static function budgetHeads(): array
    {
        return BudgetHead::query()->orderBy('sort')->get(['id', 'code', 'name'])->all();
    }

    /** @return array<int, Party> id, name, type */
    public static function parties(?string $type = null): array
    {
        return Party::query()
            ->where('is_active', true)
            ->when($type, fn ($query) => $query->where('type', $type))
            ->orderBy('name')
            ->get(['id', 'name', 'type'])
            ->all();
    }

    /** @return array<int, array{id:int, name:string, unit:string, unit_label:string, kind:string, qty_on_hand:float, value_on_hand:int, average_cost:float}> */
    public static function items(): array
    {
        return Item::query()->where('is_active', true)->orderBy('kind')->orderBy('name')->get()->map(fn (Item $item) => [
            'id' => $item->id,
            'name' => $item->name,
            'unit' => $item->unit,
            'unit_label' => Tr::get(Item::UNITS[$item->unit] ?? $item->unit),
            'kind' => $item->kind,
            'qty_on_hand' => $item->qty_on_hand,
            'value_on_hand' => $item->value_on_hand,
            'average_cost' => round($item->averageCost(), 4),
        ])->all();
    }

    /** @return array<int, Animal> id, tag, name, cost_center_id */
    public static function activeAnimals(): array
    {
        return Animal::query()->where('status', 'active')->orderBy('tag')->get(['id', 'tag', 'name', 'cost_center_id'])->all();
    }
}
