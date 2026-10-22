<?php

namespace App\Services;

use App\Models\Item;
use App\Models\MarketPrice;
use App\Support\Tr;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Reads the বাজারদর book: the latest price of each commodity, how it moved, its averages,
 * and a few plain hints that set the haat price against the farm's own numbers.
 */
class PriceBook
{
    /** How many past prices each card keeps for its history list. */
    private const HISTORY = 30;

    /**
     * One card per commodity and unit, most recently priced first.
     *
     * @return list<array{
     *     key: string, commodity: string, unit: string, unit_label: string,
     *     latest: int, latest_date: string, place: string|null, quality: string|null,
     *     previous: int|null, change_pct: float|null, avg30: int|null, avg90: int|null,
     *     min12: int|null, max12: int|null, per_kg: float|null, count: int,
     *     spark: list<array{date: string, price: int}>,
     *     history: list<array{id: int, date: string, price: int, place: string|null, quality: string|null, notes: string|null, deletable: bool}>
     * }>
     */
    public function latest(?CarbonImmutable $today = null): array
    {
        $today = ($today ?? CarbonImmutable::today())->startOfDay();

        $groups = MarketPrice::query()->orderBy('date')->orderBy('id')->get()
            ->groupBy(fn (MarketPrice $p) => $p->commodity.'|'.$p->unit);

        $cards = [];

        foreach ($groups as $key => $rows) {
            $cards[] = $this->card((string) $key, $rows->values(), $today);
        }

        usort($cards, fn (array $a, array $b) => [$b['latest_date'], $a['commodity']] <=> [$a['latest_date'], $b['commodity']]);

        return $cards;
    }

    /**
     * The latest price of a commodity in poisha per kg, from entries in kg or মণ
     * (or only the given unit). Null when nothing weighable has been written.
     */
    public function perKg(string $commodity, ?string $unit = null): ?float
    {
        $units = $unit !== null ? [$unit] : array_keys(MarketPrice::KG_PER_UNIT);

        $row = MarketPrice::query()->where('commodity', $commodity)->whereIn('unit', $units)
            ->orderByDesc('date')->orderByDesc('id')->first();

        return $row ? MarketPrice::toPerKg($row->price, $row->unit) : null;
    }

    /**
     * Zero to four short hints, each explaining the numbers behind it.
     *
     * @return list<array{tone: string, title: string, detail: string}>
     */
    public function suggestion(?CarbonImmutable $today = null): array
    {
        $today = ($today ?? CarbonImmutable::today())->startOfDay();
        $hints = [];

        // Straw: the haat price per kg against what our own straw is booked at.
        $strawKg = $this->perKg('খড়');
        $own = Item::query()->where('name', 'খড় (নিজের জমির)')->first();
        $ownCost = $own?->averageCost() ?? 0.0;

        if ($strawKg !== null && $ownCost > 0) {
            if ($strawKg > $ownCost) {
                $hints[] = [
                    'tone' => 'good',
                    'title' => Tr::get('খড় কেনার চেয়ে নিজের জমির খড় সস্তা'),
                    'detail' => Tr::get('হাটে খড় ৳:market/কেজি, নিজের জমির খড়ের হিসাব ৳:own/কেজি।', [
                        'market' => $this->taka($strawKg), 'own' => $this->taka($ownCost),
                    ]),
                ];
            } elseif ($strawKg < $ownCost * 0.9) {
                $hints[] = [
                    'tone' => 'info',
                    'title' => Tr::get('হাটে খড় নিজের খড়ের চেয়ে সস্তা'),
                    'detail' => Tr::get('হাটে খড় ৳:market/কেজি, নিজের জমির খড়ের হিসাব ৳:own/কেজি।', [
                        'market' => $this->taka($strawKg), 'own' => $this->taka($ownCost),
                    ]),
                ];
            }
        }

        // Selling: paddy, fattened cattle and mustard against their own 90-day average.
        $sells = [
            ['ধান', 'mon', 'ধান বিক্রির ভালো সময়', 'ধানের দাম কম — পারলে রেখে দিন'],
            ['গরু — মোটাতাজা', 'head', 'মোটাতাজা গরু বিক্রির ভালো সময়', 'গরুর দাম কম — পারলে আরও কিছুদিন রাখুন'],
            ['সরিষা', 'mon', 'সরিষা বিক্রির ভালো সময়', 'সরিষার দাম কম — পারলে রেখে দিন'],
        ];

        foreach ($sells as [$commodity, $unit, $high, $low]) {
            $move = $this->againstAverage($commodity, $unit, $today);

            if ($move === null) {
                continue;
            }

            [$latest, $avg, $pct] = $move;
            $detail = Tr::get('এখন ৳:latest/:unit, ৯০ দিনের গড় ৳:avg (:pct%)।', [
                'latest' => $this->taka($latest), 'avg' => $this->taka($avg),
                'unit' => Tr::get(MarketPrice::UNITS[$unit]), 'pct' => ($pct > 0 ? '+' : '').round($pct, 1),
            ]);

            if ($pct > 10) {
                $hints[] = ['tone' => 'good', 'title' => Tr::get($high), 'detail' => $detail];
            } elseif ($pct < -10) {
                $hints[] = ['tone' => 'warn', 'title' => Tr::get($low), 'detail' => $detail];
            }
        }

        return array_slice($hints, 0, 4);
    }

    /**
     * Latest price, its 90-day average and the gap in percent; null without at least two prices.
     *
     * @return array{0: int, 1: float, 2: float}|null
     */
    private function againstAverage(string $commodity, string $unit, CarbonImmutable $today): ?array
    {
        $rows = MarketPrice::query()->where('commodity', $commodity)->where('unit', $unit)
            ->where('date', '>=', $today->subDays(90)->toDateString())
            ->orderBy('date')->orderBy('id')->pluck('price');

        if ($rows->count() < 2) {
            return null;
        }

        $latest = (int) $rows->last();
        $avg = (float) $rows->avg();

        return $avg > 0 ? [$latest, $avg, ($latest - $avg) / $avg * 100] : null;
    }

    /**
     * @param  Collection<int, MarketPrice>  $rows  oldest first
     * @return array{
     *     key: string, commodity: string, unit: string, unit_label: string,
     *     latest: int, latest_date: string, place: string|null, quality: string|null,
     *     previous: int|null, change_pct: float|null, avg30: int|null, avg90: int|null,
     *     min12: int|null, max12: int|null, per_kg: float|null, count: int,
     *     spark: list<array{date: string, price: int}>,
     *     history: list<array{id: int, date: string, price: int, place: string|null, quality: string|null, notes: string|null, deletable: bool}>
     * }
     */
    private function card(string $key, Collection $rows, CarbonImmutable $today): array
    {
        /** @var MarketPrice $last */
        $last = $rows->last();
        $previous = $rows->count() > 1 ? $rows[$rows->count() - 2] : null;

        $since = fn (CarbonImmutable $from) => $rows->filter(fn (MarketPrice $p) => $p->date->greaterThanOrEqualTo($from))->pluck('price');
        $in30 = $since($today->subDays(30));
        $in90 = $since($today->subDays(90));
        $in12 = $since($today->subYear());

        $change = $previous && $previous->price > 0 ? round(($last->price - $previous->price) / $previous->price * 100, 1) : null;
        $dayAgo = now()->subDay();

        return [
            'key' => $key,
            'commodity' => $last->commodity,
            'unit' => $last->unit,
            'unit_label' => Tr::get(MarketPrice::UNITS[$last->unit] ?? $last->unit),
            'latest' => $last->price,
            'latest_date' => $last->date->toDateString(),
            'place' => $last->place,
            'quality' => $last->quality,
            'previous' => $previous?->price,
            'change_pct' => $change,
            'avg30' => $in30->isEmpty() ? null : (int) round((float) $in30->avg()),
            'avg90' => $in90->isEmpty() ? null : (int) round((float) $in90->avg()),
            'min12' => $in12->isEmpty() ? null : (int) $in12->min(),
            'max12' => $in12->isEmpty() ? null : (int) $in12->max(),
            'per_kg' => MarketPrice::toPerKg($last->price, $last->unit),
            'count' => $rows->count(),
            'spark' => array_values($rows->slice(-12)->map(fn (MarketPrice $p) => ['date' => $p->date->toDateString(), 'price' => $p->price])->all()),
            'history' => array_values($rows->slice(-self::HISTORY)->reverse()->map(fn (MarketPrice $p) => [
                'id' => $p->id,
                'date' => $p->date->toDateString(),
                'price' => $p->price,
                'place' => $p->place,
                'quality' => $p->quality,
                'notes' => $p->notes,
                'deletable' => (bool) $p->created_at?->greaterThan($dayAgo),
            ])->all()),
        ];
    }

    /** Poisha to plain taka text with up to two decimals, for hint sentences. */
    private function taka(float|int $poisha): string
    {
        return number_format($poisha / 100, fmod($poisha / 100, 1.0) === 0.0 ? 0 : 2);
    }
}
