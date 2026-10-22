<?php

use App\Enums\Role;
use App\Models\Account;
use App\Models\Animal;
use App\Models\AnimalWeight;
use App\Models\Breeding;
use App\Models\CostCenter;
use App\Models\Item;
use App\Models\MilkRecord;
use App\Models\User;
use App\Services\RationBook;
use App\Services\StockService;
use Database\Seeders\KhamarSetupSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->user = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($this->user);
    $this->animal = fn (string $code, string $tag) => Animal::query()->create([
        'tag' => $tag, 'sex' => 'female', 'cost_center_id' => CostCenter::query()->where('code', $code)->firstOrFail()->id,
        'status' => 'active', 'acquisition' => 'opening', 'acquired_on' => '2026-01-01',
    ]);
});

it('gives a 300 kg cow milking 10 litres about 9 kg of dry matter and 5-6 kg concentrate', function () {
    $r = RationBook::ration(['category' => 'gavi', 'weight' => 300, 'milk' => 10], RationBook::DEFAULTS);

    // 3% of 300 kg; 1 kg base + 1 kg per 2–2.5 L.
    expect($r['dm'])->toEqualWithDelta(9.0, 0.01)
        ->and($r['conc'])->toBeGreaterThanOrEqual(4.5)->toBeLessThanOrEqual(6.0)
        ->and($r['green'])->toBeGreaterThan(0)->toBeLessThanOrEqual(30.0)
        ->and($r['straw'])->toBeGreaterThan(0)
        ->and($r['bran'] + $r['concentrate'] + $r['oilcake'])->toEqualWithDelta($r['conc'], 0.02)
        ->and($r['mineral_g'])->toEqual(50)
        ->and($r['salt_g'])->toEqual(30)
        ->and($r['water'])->toBeGreaterThanOrEqual(40)->toBeLessThanOrEqual(60)
        ->and($r['cost_day'])->toBeGreaterThan(0);

    // Roughage stays at least 60% of the dry matter.
    $roughageDm = $r['green'] * 0.2 + $r['straw'] * 0.9;
    expect($roughageDm)->toBeGreaterThanOrEqual(9 * 0.6 - 0.05);
});

it('gives a 250 kg fattening animal 1–1.5% of its weight as concentrate', function () {
    foreach ([500, 750, 1000, 1500] as $gain) {
        $r = RationBook::ration(['category' => 'motataja', 'weight' => 250], [...RationBook::DEFAULTS, 'fattening_gain_g' => $gain]);
        expect($r['conc'])->toBeGreaterThanOrEqual(2.5)->toBeLessThanOrEqual(3.75)
            ->and($r['dm'])->toEqualWithDelta(7.5, 0.01);
    }
});

it('adds a kilo of concentrate in the last two months of pregnancy and uses 2.5% for heifers', function () {
    $dry = RationBook::ration(['category' => 'gavi', 'weight' => 300, 'milk' => 0], RationBook::DEFAULTS);
    $late = RationBook::ration(['category' => 'gavi', 'weight' => 300, 'milk' => 0, 'pregnant' => true, 'late_pregnancy' => true], RationBook::DEFAULTS);
    $heifer = RationBook::ration(['category' => 'bokna', 'weight' => 200], RationBook::DEFAULTS);

    expect($late['conc'] - $dry['conc'])->toEqualWithDelta(1.0, 0.01)
        ->and($dry['dm'])->toEqualWithDelta(7.5, 0.01)
        ->and($late['dm'])->toEqualWithDelta(9.0, 0.01)
        ->and($heifer['dm'])->toEqualWithDelta(5.0, 0.01)
        ->and($heifer['conc'])->toBeGreaterThanOrEqual(1.0)->toBeLessThanOrEqual(1.5);
});

it('reads the herd, weights, milk and pregnancy, and the totals add up', function () {
    $cow = ($this->animal)('GAVI', 'G-1');
    $cow2 = ($this->animal)('GAVI', 'G-2');
    $heifer = ($this->animal)('BOKNA', 'B-1');
    $bull = ($this->animal)('MOTATAJA', 'M-1');

    AnimalWeight::query()->create(['animal_id' => $cow->id, 'date' => now()->subDays(30)->toDateString(), 'kg' => 290]);
    AnimalWeight::query()->create(['animal_id' => $cow->id, 'date' => now()->subDays(2)->toDateString(), 'kg' => 300]);
    AnimalWeight::query()->create(['animal_id' => $bull->id, 'date' => now()->toDateString(), 'kg' => 250]);

    foreach ([1, 2] as $d) {
        foreach (['morning', 'evening'] as $session) {
            MilkRecord::query()->create(['date' => now()->subDays($d)->toDateString(), 'session' => $session, 'cost_center_id' => $cow->cost_center_id, 'animal_id' => $cow->id, 'liters' => 5, 'recorded_by' => $this->user->id]);
        }
    }

    Breeding::query()->create([
        'animal_id' => $cow2->id, 'served_on' => now()->subDays(240)->toDateString(), 'method' => 'ai', 'result' => 'pregnant',
        'expected_on' => now()->addDays(43)->toDateString(),
    ]);

    $book = app(RationBook::class);
    $animals = collect($book->animals())->keyBy('tag');

    expect($animals)->toHaveCount(4)
        ->and($animals['G-1']['weight'])->toEqual(300.0)
        ->and($animals['G-1']['weight_estimated'])->toBeFalse()
        ->and($animals['G-1']['milk'])->toEqual(10.0)
        ->and($animals['G-1']['gain_g'])->toBe(357)
        ->and($animals['G-2']['weight'])->toEqual(300.0)
        ->and($animals['G-2']['weight_estimated'])->toBeTrue()
        ->and($animals['G-2']['late_pregnancy'])->toBeTrue()
        ->and($animals['B-1']['weight'])->toEqual(200.0)
        ->and($animals['M-1']['category'])->toBe('motataja');

    $prices = array_map(fn ($p) => $p['price'], $book->prices());
    $plan = $book->plan($animals->values()->all(), RationBook::DEFAULTS, $prices);

    $sum = fn (string $key) => array_sum(array_column($plan['rows'], $key));
    expect($plan['totals']['count'])->toBe(4)
        ->and($plan['totals']['cost_day'])->toBe($sum('cost_day'))
        ->and($plan['totals']['cost_month'])->toBe($sum('cost_day') * 30)
        ->and($plan['totals']['straw'])->toEqualWithDelta($sum('straw'), 0.01)
        ->and($plan['totals']['conc'])->toEqualWithDelta($sum('conc'), 0.01)
        ->and(array_sum(array_column($plan['groups'], 'cost_day')))->toBe($plan['totals']['cost_day'])
        ->and($plan['groups']['gavi']['count'])->toBe(2);
});

it('prices the feed from stock and reports the stock on hand and recent use', function () {
    $straw = Item::query()->where('name', 'খড়')->firstOrFail();
    $stock = app(StockService::class);
    // 1,000 kg of straw for 8,000 taka, then 140 kg fed over the fortnight.
    $stock->purchase($straw, now()->subDays(10)->toDateString(), 1000, 800000, ['credit_account_id' => Account::byCode('1100')->id]);
    $stock->issue($straw, now()->subDays(3)->toDateString(), [['cost_center_id' => CostCenter::query()->where('code', 'GAVI')->value('id'), 'qty' => 140]], null);

    $book = app(RationBook::class);
    $prices = $book->prices();
    $rows = collect($book->stock())->keyBy('key');

    expect($prices['straw']['price'])->toBe(800)
        ->and($prices['straw']['source'])->toBe('stock')
        ->and($prices['bran']['source'])->toBe('typical')
        ->and($rows['straw']['qty'])->toEqual(860.0)
        ->and($rows['straw']['actual_per_day'])->toEqual(10.0)
        ->and($rows['green']['items'])->toContain('কাঁচা ঘাস (নিজের জমির)');
});

it('loads the ration page', function () {
    ($this->animal)('GAVI', 'G-1');

    $this->get('/ration')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('ration/Index')
        ->has('animals', 1)
        ->has('defaults.dm_pct')
        ->has('prices.straw')
        ->has('stock', 5)
        ->where('totals.count', 1));
});
