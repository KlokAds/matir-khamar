<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\AnimalWeight;
use App\Models\Breeding;
use App\Models\CostCenter;
use App\Models\Item;
use App\Models\MilkRecord;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * খাবারের রেশন হিসাব: how much straw, green grass and concentrate each head of cattle should get
 * a day, what that costs, and how long the farm's stock lasts at that ration.
 *
 * These are the plain rules of thumb a Bangladeshi smallholder and the upazila livestock office
 * use — not a nutrition model. The page says so: "আনুমানিক — প্রাণিসম্পদ কর্মকর্তার সাথে মিলিয়ে নিন".
 *
 *  - Dry matter (DM) eaten a day ≈ 3% of body weight for milking or pregnant cows and fattening
 *    cattle, 2.5% for heifers and dry, empty cows; calves 3%.
 *  - Roughage (straw + green grass) is at least 60% of the DM for cows and heifers (60–70%),
 *    40% for fattening (40–50%), 50% for calves.
 *  - DM content: green grass ~20%, straw ~90%, concentrate ~90%.
 *  - Concentrate: milking cow 1 kg base + 1 kg per 2–2.5 L of milk; a cow or heifer in the last
 *    2 months of pregnancy +1 kg; heifer 1–1.5 kg; fattening 1–1.5% of body weight (more for a
 *    higher target gain); calf about 1% of body weight (calf starter).
 *  - Green grass up to ~10% of body weight fresh a day; the rest of the roughage is straw.
 *  - Water 40–60 L a day for grown cattle; mineral mixture ~50 g and salt ~30 g.
 *
 * The same arithmetic runs in resources/js/pages/ration/Index.vue so the owner can change the
 * assumptions and see the result at once; keep the two in step.
 */
class RationBook
{
    /** Categories of cattle, by the code of the center they live in. */
    public const CATEGORIES = ['GAVI' => 'gavi', 'BOKNA' => 'bokna', 'MOTATAJA' => 'motataja', 'BACHUR' => 'bachur'];

    /** Weight to assume when an animal has never been weighed, in kg. */
    public const DEFAULT_WEIGHTS = ['gavi' => 300, 'bokna' => 200, 'motataja' => 250, 'bachur' => 80];

    /** How many past days of milk records set a cow's daily milk. */
    public const MILK_DAYS = 14;

    /** Last stage of pregnancy that gets extra concentrate (about 2 months before calving). */
    public const LATE_PREGNANCY_DAYS = 60;

    /** The feeds of the ration, with the haat commodity name used in the বাজারদর book. */
    public const FEEDS = [
        'straw' => 'খড়',
        'green' => 'কাঁচা ঘাস',
        'bran' => 'ভুসি',
        'concentrate' => 'দানাদার খাদ্য',
        'oilcake' => 'খৈল',
    ];

    /** Price to fall back on when neither the stock nor the haat book has one, in poisha per kg. */
    public const FALLBACK_PRICES = [
        'straw' => 800, 'green' => 300, 'bran' => 5500, 'concentrate' => 5000, 'oilcake' => 6000,
        'mineral' => 15000, 'salt' => 4000,
    ];

    /**
     * The rules of thumb, all editable on the page.
     *
     * @var array{
     *     dm_pct: array{gavi: float, gavi_dry: float, bokna: float, motataja: float, bachur: float},
     *     roughage_min: array{gavi: float, bokna: float, motataja: float, bachur: float},
     *     dm_green: float, dm_straw: float, dm_conc: float,
     *     green_pct_bw: float, green_share_max: float,
     *     cow_base_kg: float, liters_per_kg: float, pregnant_extra_kg: float, heifer_kg: float,
     *     fattening_gain_g: float, calf_conc_pct: float,
     *     mix: array{bran: float, concentrate: float, oilcake: float},
     *     mineral_g: float, salt_g: float
     * }
     */
    public const DEFAULTS = [
        'dm_pct' => ['gavi' => 3.0, 'gavi_dry' => 2.5, 'bokna' => 2.5, 'motataja' => 3.0, 'bachur' => 3.0],
        'roughage_min' => ['gavi' => 60.0, 'bokna' => 60.0, 'motataja' => 40.0, 'bachur' => 50.0],
        'dm_green' => 20.0,
        'dm_straw' => 90.0,
        'dm_conc' => 90.0,
        'green_pct_bw' => 10.0,
        // Of the roughage DM, at most this share from green grass so straw is never left out.
        'green_share_max' => 60.0,
        'cow_base_kg' => 1.0,
        'liters_per_kg' => 2.5,
        'pregnant_extra_kg' => 1.0,
        'heifer_kg' => 1.25,
        'fattening_gain_g' => 750.0,
        'calf_conc_pct' => 1.0,
        'mix' => ['bran' => 40.0, 'concentrate' => 40.0, 'oilcake' => 20.0],
        'mineral_g' => 50.0,
        'salt_g' => 30.0,
    ];

    public function __construct(private PriceBook $prices, private StockForecast $forecast) {}

    /**
     * Everything the ration page needs.
     *
     * @return array<string, mixed>
     */
    public function page(?CarbonImmutable $today = null): array
    {
        $animals = $this->animals($today);
        $prices = $this->prices();
        $plan = $this->plan($animals, self::DEFAULTS, array_map(fn (array $p) => $p['price'], $prices));

        return [
            'animals' => $animals,
            'defaults' => self::DEFAULTS,
            'defaultWeights' => self::DEFAULT_WEIGHTS,
            'prices' => $prices,
            'stock' => $this->stock(),
            'totals' => $plan['totals'],
            'milkDays' => self::MILK_DAYS,
            'windowDays' => StockForecast::WINDOW_DAYS,
        ];
    }

    /**
     * Active cattle of the running cow centers, with what the ration depends on.
     *
     * @return list<array{
     *     id: int, tag: string, name: string|null, category: string, center: string,
     *     weight: float, weight_estimated: bool, weight_date: string|null, gain_g: int|null,
     *     milk: float, milk_source: string|null, pregnant: bool, expected_on: string|null, late_pregnancy: bool
     * }>
     */
    public function animals(?CarbonImmutable $today = null): array
    {
        $today = ($today ?? CarbonImmutable::today())->startOfDay();
        $categories = $this->categoryOfCenters();

        if ($categories === []) {
            return [];
        }

        $animals = Animal::query()->with('costCenter:id,name,sort')->where('status', 'active')
            ->whereIn('cost_center_id', array_keys($categories))->orderBy('tag')->get();
        $ids = $animals->pluck('id')->all();

        $weights = AnimalWeight::query()->whereIn('animal_id', $ids)->orderBy('date')->orderBy('id')->get()->groupBy('animal_id');

        $pregnancies = Breeding::query()->whereIn('animal_id', $ids)->where('result', 'pregnant')->whereNull('calved_on')
            ->orderBy('expected_on')->get()->keyBy('animal_id');

        $milk = $this->milkPerCow(array_values($animals->all()), $categories, $today);

        $rows = [];

        foreach ($animals as $animal) {
            $category = $categories[$animal->cost_center_id];
            $own = $weights->get($animal->id, collect());
            $last = $own->last();
            $breeding = $pregnancies->get($animal->id);
            $expected = $breeding?->expected_on;
            $daysToCalving = $expected ? (int) $today->diffInDays($expected, false) : null;

            $rows[] = [
                'id' => $animal->id,
                'tag' => $animal->tag,
                'name' => $animal->name,
                'category' => $category,
                'center' => $animal->costCenter->name,
                'weight' => $last ? round($last->kg, 1) : (float) self::DEFAULT_WEIGHTS[$category],
                'weight_estimated' => $last === null,
                'weight_date' => $last?->date->toDateString(),
                'gain_g' => $this->gain(array_values($own->all())),
                'milk' => $milk[$animal->id]['liters'] ?? 0.0,
                'milk_source' => $milk[$animal->id]['source'] ?? null,
                'pregnant' => $breeding !== null,
                'expected_on' => $expected?->toDateString(),
                'late_pregnancy' => $daysToCalving !== null && $daysToCalving <= self::LATE_PREGNANCY_DAYS && $daysToCalving >= -14,
            ];
        }

        return $rows;
    }

    /**
     * One animal's ration a day. Pure arithmetic on the rules of thumb above.
     *
     * @param  array{category: string, weight: float|int, milk?: float|int, pregnant?: bool, late_pregnancy?: bool}  $animal
     * @param  array<string, mixed>  $a  the assumptions, shaped like DEFAULTS
     * @param  array<string, int|float>  $prices  poisha per kg, keyed like FALLBACK_PRICES
     * @return array{
     *     dm: float, dm_pct: float, green: float, straw: float, conc: float, bran: float, concentrate: float, oilcake: float,
     *     mineral_g: float, salt_g: float, water: float, offered_dm: float, conc_share: float, cost_day: int
     * }
     */
    public static function ration(array $animal, array $a, array $prices = []): array
    {
        $a = array_replace_recursive(self::DEFAULTS, $a);
        $category = $animal['category'];
        $weight = max(0.0, (float) $animal['weight']);
        $milk = max(0.0, (float) ($animal['milk'] ?? 0));
        $pregnant = (bool) ($animal['pregnant'] ?? false);
        $late = (bool) ($animal['late_pregnancy'] ?? false);

        // Dry matter the animal eats in a day.
        $dmPct = match ($category) {
            'gavi' => ($milk > 0 || $pregnant) ? $a['dm_pct']['gavi'] : $a['dm_pct']['gavi_dry'],
            'bokna' => $pregnant ? $a['dm_pct']['gavi'] : $a['dm_pct']['bokna'],
            'motataja' => $a['dm_pct']['motataja'],
            default => $a['dm_pct']['bachur'],
        };
        $dm = $weight * $dmPct / 100;

        // Concentrate, fresh kg.
        $conc = match ($category) {
            'gavi' => $a['cow_base_kg'] + ($a['liters_per_kg'] > 0 ? $milk / $a['liters_per_kg'] : 0),
            'bokna' => $a['heifer_kg'],
            'motataja' => $weight * self::fatteningPct($a['fattening_gain_g']) / 100,
            default => $weight * $a['calf_conc_pct'] / 100,
        };

        if ($late && in_array($category, ['gavi', 'bokna'], true)) {
            $conc += $a['pregnant_extra_kg'];
        }

        $concDm = $conc * $a['dm_conc'] / 100;

        // Roughage fills the rest of the DM, but never below its minimum share.
        $roughageMin = $a['roughage_min'][$category] ?? 60.0;
        $roughageDm = max($dm - $concDm, $dm * $roughageMin / 100);

        // Green grass first, up to 10% of body weight fresh and its share of the roughage; the rest straw.
        $greenDmCap = min($weight * $a['green_pct_bw'] / 100 * $a['dm_green'] / 100, $roughageDm * $a['green_share_max'] / 100);
        $greenDm = max(0.0, $greenDmCap);
        $strawDm = max(0.0, $roughageDm - $greenDm);
        $green = $a['dm_green'] > 0 ? $greenDm / ($a['dm_green'] / 100) : 0.0;
        $straw = $a['dm_straw'] > 0 ? $strawDm / ($a['dm_straw'] / 100) : 0.0;

        $mixTotal = max(1.0, $a['mix']['bran'] + $a['mix']['concentrate'] + $a['mix']['oilcake']);
        $bran = $conc * $a['mix']['bran'] / $mixTotal;
        $concentrate = $conc * $a['mix']['concentrate'] / $mixTotal;
        $oilcake = $conc * $a['mix']['oilcake'] / $mixTotal;

        // Calves get half the mineral and salt of grown cattle.
        $small = $category === 'bachur' || $weight < 150;
        $mineral = $a['mineral_g'] * ($small ? 0.5 : 1);
        $salt = $a['salt_g'] * ($small ? 0.5 : 1);

        // Water: 40 L for grown cattle plus 2 L per litre of milk, up to 60 L; a calf about 12% of its weight.
        $water = $small ? max(8.0, $weight * 0.12) : min(60.0, 40 + 2 * $milk);

        $price = fn (string $key): float => (float) ($prices[$key] ?? self::FALLBACK_PRICES[$key]);
        $cost = $green * $price('green') + $straw * $price('straw') + $bran * $price('bran')
            + $concentrate * $price('concentrate') + $oilcake * $price('oilcake')
            + $mineral / 1000 * $price('mineral') + $salt / 1000 * $price('salt');

        $offered = $greenDm + $strawDm + $concDm;

        return [
            'dm' => round($dm, 2),
            'dm_pct' => $dmPct,
            'green' => round($green, 2),
            'straw' => round($straw, 2),
            'conc' => round($conc, 2),
            'bran' => round($bran, 2),
            'concentrate' => round($concentrate, 2),
            'oilcake' => round($oilcake, 2),
            'mineral_g' => round($mineral),
            'salt_g' => round($salt),
            'water' => round($water),
            'offered_dm' => round($offered, 2),
            'conc_share' => $offered > 0 ? round($concDm / $offered * 100, 1) : 0.0,
            'cost_day' => (int) round($cost),
        ];
    }

    /** Fattening concentrate as % of body weight: 1% for 500 g/day gain up to 1.5% for 1 kg/day. */
    public static function fatteningPct(float|int $gainG): float
    {
        return max(1.0, min(1.5, 1 + ((float) $gainG - 500) / 1000));
    }

    /**
     * Rations for every animal, summed per category and for the farm.
     *
     * @param  list<array{id?: int, category: string, weight: float|int, milk?: float|int, pregnant?: bool, late_pregnancy?: bool}>  $animals
     * @param  array<string, mixed>  $a
     * @param  array<string, int|float>  $prices
     * @return array{rows: list<array<string, mixed>>, groups: array<string, array<string, float|int>>, totals: array<string, float|int>}
     */
    public function plan(array $animals, array $a, array $prices): array
    {
        $keys = ['green', 'straw', 'conc', 'bran', 'concentrate', 'oilcake', 'mineral_g', 'salt_g', 'water', 'cost_day'];
        $blank = array_fill_keys($keys, 0) + ['count' => 0];
        $rows = [];
        $groups = [];
        $totals = $blank;

        foreach ($animals as $animal) {
            $ration = self::ration($animal, $a, $prices);
            $rows[] = ['id' => $animal['id'] ?? null, 'category' => $animal['category'], ...$ration];
            $groups[$animal['category']] ??= $blank;

            foreach ($keys as $key) {
                $groups[$animal['category']][$key] += $ration[$key];
                $totals[$key] += $ration[$key];
            }

            $groups[$animal['category']]['count']++;
            $totals['count']++;
        }

        $finish = function (array $sum): array {
            foreach (['green', 'straw', 'conc', 'bran', 'concentrate', 'oilcake'] as $key) {
                $sum[$key] = round($sum[$key], 2);
            }
            $sum['cost_day'] = (int) $sum['cost_day'];
            $sum['cost_month'] = $sum['cost_day'] * 30;

            return $sum;
        };

        return ['rows' => $rows, 'groups' => array_map($finish, $groups), 'totals' => $finish($totals)];
    }

    /**
     * Price of each feed in poisha per kg: the stock's average cost, else the latest haat price,
     * else a typical figure — with where it came from.
     *
     * @return array<string, array{price: int, source: string, stock_price: int|null, market_price: int|null}>
     */
    public function prices(): array
    {
        $out = [];

        foreach (self::FEEDS as $key => $commodity) {
            $items = $this->itemsFor($key);
            $qty = $items->sum('qty_on_hand');
            $stockPrice = $qty > 0 ? (int) round($items->sum('value_on_hand') / $qty) : null;
            $market = $this->prices->perKg($commodity, 'kg') ?? $this->prices->perKg($commodity);
            $marketPrice = $market !== null ? (int) round($market) : null;

            $out[$key] = [
                'price' => $stockPrice ?: ($marketPrice ?: self::FALLBACK_PRICES[$key]),
                'source' => $stockPrice ? 'stock' : ($marketPrice ? 'market' : 'typical'),
                'stock_price' => $stockPrice,
                'market_price' => $marketPrice,
            ];
        }

        foreach (['mineral', 'salt'] as $key) {
            $out[$key] = ['price' => self::FALLBACK_PRICES[$key], 'source' => 'typical', 'stock_price' => null, 'market_price' => null];
        }

        return $out;
    }

    /**
     * Stock on hand of each ration feed and how much went out a day over the last two weeks.
     *
     * @return list<array{key: string, qty: float, actual_per_day: float|null, items: list<string>}>
     */
    public function stock(): array
    {
        $usage = collect($this->forecast->items())->keyBy('id');
        $out = [];

        foreach (array_keys(self::FEEDS) as $key) {
            $items = $this->itemsFor($key);
            $perDay = $items->map(fn (Item $item) => $usage->get($item->id)['per_day'] ?? null)->filter(fn ($v) => $v !== null);

            $out[] = [
                'key' => $key,
                'qty' => round((float) $items->sum('qty_on_hand'), 2),
                'actual_per_day' => $perDay->isEmpty() ? null : round((float) $perDay->sum(), 2),
                'items' => array_values($items->map(fn (Item $item) => $item->name)->all()),
            ];
        }

        return $out;
    }

    /**
     * Stocked items (in kg) that count as one feed of the ration: every খড়, every green fodder,
     * ভুসি, দানাদার and খৈল by name.
     *
     * @return Collection<int, Item>
     */
    private function itemsFor(string $key): Collection
    {
        $query = Item::query()->where('is_active', true)->where('unit', 'kg');

        return match ($key) {
            'green' => $query->where('kind', 'green')->orderBy('name')->get(),
            default => $query->where('name', 'like', self::FEEDS[$key].'%')->whereIn('kind', ['roughage', 'concentrate'])->orderBy('name')->get(),
        };
    }

    /**
     * Running cattle centers and the category each stands for (a sub-center takes its parent's).
     *
     * @return array<int, string>
     */
    private function categoryOfCenters(): array
    {
        $running = CostCenter::runningIds();
        $centers = CostCenter::query()->get(['id', 'parent_id', 'code', 'holds_animals'])->keyBy('id');
        $out = [];

        foreach ($centers as $center) {
            if (! $center->holds_animals || ! in_array($center->id, $running, true)) {
                continue;
            }

            $node = $center;

            while ($node !== null) {
                if (isset(self::CATEGORIES[$node->code])) {
                    $out[$center->id] = self::CATEGORIES[$node->code];
                    break;
                }

                $node = $node->parent_id ? $centers->get($node->parent_id) : null;
            }
        }

        return $out;
    }

    /**
     * Litres a day per cow over the last two weeks: her own records on the days she was milked,
     * or the center's unassigned milk shared among the cows with no records of their own.
     *
     * @param  list<Animal>  $animals
     * @param  array<int, string>  $categories
     * @return array<int, array{liters: float, source: string}>
     */
    private function milkPerCow(array $animals, array $categories, CarbonImmutable $today): array
    {
        $since = $today->subDays(self::MILK_DAYS)->toDateString();
        $cows = array_values(array_filter($animals, fn (Animal $a) => $categories[$a->cost_center_id] === 'gavi'));

        if ($cows === []) {
            return [];
        }

        $out = [];
        $own = MilkRecord::query()->whereNotNull('animal_id')->whereIn('animal_id', array_map(fn (Animal $a) => $a->id, $cows))
            ->whereDate('date', '>=', $since)
            ->selectRaw('animal_id, SUM(liters) as l, COUNT(DISTINCT DATE(date)) as d')->groupBy('animal_id')->get();

        foreach ($own as $row) {
            $days = (int) $row->getAttribute('d');

            if ($days > 0) {
                $out[(int) $row->getAttribute('animal_id')] = ['liters' => round((float) $row->getAttribute('l') / $days, 1), 'source' => 'own'];
            }
        }

        $byCenter = MilkRecord::query()->whereNull('animal_id')->whereDate('date', '>=', $since)
            ->whereIn('cost_center_id', array_unique(array_map(fn (Animal $a) => $a->cost_center_id, $cows)))
            ->selectRaw('cost_center_id, SUM(liters) as l, COUNT(DISTINCT DATE(date)) as d')->groupBy('cost_center_id')->get();

        foreach ($byCenter as $row) {
            $centerId = (int) $row->getAttribute('cost_center_id');
            $days = (int) $row->getAttribute('d');
            $without = array_filter($cows, fn (Animal $a) => $a->cost_center_id === $centerId && ! isset($out[$a->id]));

            if ($days === 0 || $without === []) {
                continue;
            }

            $each = round((float) $row->getAttribute('l') / $days / count($without), 1);

            foreach ($without as $cow) {
                $out[$cow->id] = ['liters' => $each, 'source' => 'center'];
            }
        }

        return $out;
    }

    /**
     * Grams gained a day between the last weighing and the one before it at least a week earlier.
     *
     * @param  list<AnimalWeight>  $weights  oldest first
     */
    private function gain(array $weights): ?int
    {
        $last = end($weights);

        if ($last === false) {
            return null;
        }

        for ($i = count($weights) - 2; $i >= 0; $i--) {
            $days = (int) $weights[$i]->date->diffInDays($last->date);

            if ($days >= 7) {
                return (int) round(($last->kg - $weights[$i]->kg) / $days * 1000);
            }
        }

        return null;
    }
}
