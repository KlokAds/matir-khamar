<?php

use App\Enums\Role;
use App\Models\Account;
use App\Models\Animal;
use App\Models\CostCenter;
use App\Models\MilkRecord;
use App\Models\User;
use App\Services\AnimalService;
use App\Services\Ledger;
use App\Services\ProfitBook;
use Database\Seeders\KhamarSetupSeeder;
use Illuminate\Support\Facades\Date;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->actingAs(User::factory()->create(['role' => Role::Admin]));
    $this->code = fn (string $c) => CostCenter::query()->where('code', $c)->firstOrFail();
});

it('works out the milk numbers that decide profit', function () {
    $gavi = ($this->code)('GAVI');
    $cow = app(AnimalService::class)->register(['tag' => 'G-1', 'sex' => 'female', 'cost_center_id' => $gavi->id, 'acquisition' => 'opening', 'acquired_on' => '2026-01-01'], 0, null);

    // 100 L of milk sold for 6,000; 2,500 of feed and 500 of other costs in the dairy category.
    MilkRecord::query()->create(['date' => '2026-10-05', 'session' => 'morning', 'cost_center_id' => $gavi->id, 'animal_id' => $cow->id, 'liters' => 100, 'recorded_by' => auth()->id()]);
    $ledger = app(Ledger::class);
    $ledger->post('income', '2026-10-06', 'দুধ বিক্রি', [
        ['account_id' => Account::byCode('1100')->id, 'debit' => 600000],
        ['account_id' => Account::byCode('4100')->id, 'credit' => 600000, 'cost_center_id' => $gavi->id],
    ]);
    $ledger->post('expense', '2026-10-06', 'খাবার ও ঔষধ', [
        ['account_id' => Account::byCode('5110')->id, 'debit' => 250000, 'cost_center_id' => $gavi->id],
        ['account_id' => Account::byCode('5200')->id, 'debit' => 50000, 'cost_center_id' => $gavi->id],
        ['account_id' => Account::byCode('1100')->id, 'credit' => 300000],
    ]);

    $book = app(ProfitBook::class);
    $ratios = $book->ratios(Date::parse('2026-10-01'), Date::parse('2026-10-31'));

    expect($ratios['milk_price'])->toEqual(60.0)
        ->and($ratios['feed_per_liter'])->toEqual(25.0)
        ->and($ratios['cost_per_liter'])->toEqual(30.0);

    $projects = collect($book->projects(Date::parse('2026-10-01'), Date::parse('2026-10-31')));
    expect($projects->firstWhere('id', $gavi->id)['profit'])->toBe(300000);

    $row = collect($book->animals(Date::parse('2026-10-01'), Date::parse('2026-10-31'), 60.0))->firstWhere('tag', 'G-1');
    expect($row['milk_value'])->toBe(600000)->and($row['result_kind'])->toBe('milk');

    expect(Animal::query()->count())->toBe(1);
});

it('starts the planner from the farm\'s own numbers', function () {
    $defaults = app(ProfitBook::class)->plannerDefaults();

    expect($defaults)->toHaveKeys(['rent_per_bigha', 'mon_per_bigha', 'paddy_price_per_mon', 'fattening_feed_per_day', 'cow_feed_per_day'])
        ->and($defaults['cow_feed_per_day'])->toBeGreaterThan(0);
});
