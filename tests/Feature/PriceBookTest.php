<?php

use App\Enums\Role;
use App\Models\Item;
use App\Models\MarketPrice;
use App\Models\User;
use App\Services\PriceBook;
use Database\Seeders\KhamarSetupSeeder;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->actingAs(User::factory()->create(['role' => Role::Admin]));
    $this->book = app(PriceBook::class);
});

function paddyPrice(int $daysAgo, int $taka): MarketPrice
{
    return MarketPrice::query()->create([
        'date' => now()->subDays($daysAgo)->toDateString(),
        'commodity' => 'ধান',
        'unit' => 'mon',
        'price' => $taka * 100,
        'place' => 'নওগাঁ হাট',
    ]);
}

it('reads latest, previous, change and averages for paddy in maund', function () {
    paddyPrice(60, 1200);
    paddyPrice(20, 1250);
    paddyPrice(0, 1500);

    $cards = $this->book->latest();
    expect($cards)->toHaveCount(1);
    $card = $cards[0];

    expect($card['commodity'])->toBe('ধান')
        ->and($card['unit'])->toBe('mon')
        ->and($card['latest'])->toBe(150000)
        ->and($card['previous'])->toBe(125000)
        ->and($card['change_pct'])->toEqual(20.0)
        ->and($card['avg30'])->toBe(137500)
        ->and($card['avg90'])->toBe(131667)
        ->and($card['min12'])->toBe(120000)
        ->and($card['max12'])->toBe(150000)
        ->and($card['per_kg'])->toEqual(3750.0)
        ->and($card['count'])->toBe(3)
        ->and($card['spark'])->toHaveCount(3)
        ->and($card['spark'][2]['price'])->toBe(150000)
        ->and($card['history'][0]['price'])->toBe(150000)
        ->and($card['history'][0]['deletable'])->toBeTrue();
});

it('converts maund prices to per kg', function () {
    paddyPrice(0, 1200);

    expect($this->book->perKg('ধান'))->toEqual(3000.0)
        ->and($this->book->perKg('ধান', 'mon'))->toEqual(3000.0)
        ->and($this->book->perKg('ধান', 'kg'))->toBeNull()
        ->and(MarketPrice::toPerKg(80000, 'mon'))->toEqual(2000.0)
        ->and(MarketPrice::toPerKg(5000, 'head'))->toBeNull();
});

it('says it is a good time to sell paddy when the price is over 10% above its 90-day average', function () {
    paddyPrice(60, 1200);
    paddyPrice(20, 1250);

    expect(collect($this->book->suggestion())->pluck('title'))->not->toContain('ধান বিক্রির ভালো সময়');

    paddyPrice(0, 1500);

    expect(collect($this->book->suggestion())->pluck('title'))->toContain('ধান বিক্রির ভালো সময়');
});

it('says own straw is cheaper when the haat price per kg is higher', function () {
    Item::query()->where('name', 'খড় (নিজের জমির)')->update(['qty_on_hand' => 1000, 'value_on_hand' => 200000]); // ৳2/kg

    MarketPrice::query()->create(['date' => now()->toDateString(), 'commodity' => 'খড়', 'unit' => 'kg', 'price' => 600]);

    expect(collect($this->book->suggestion())->pluck('title'))->toContain('খড় কেনার চেয়ে নিজের জমির খড় সস্তা');
});
