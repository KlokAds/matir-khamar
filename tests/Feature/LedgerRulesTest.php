<?php

use App\Enums\Role;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\PeriodLock;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\AllocationService;
use App\Services\AnimalService;
use App\Services\Ledger;
use App\Services\PrepaidService;
use App\Services\ReportService;
use App\Services\StockService;
use Database\Seeders\KhamarSetupSeeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->actingAs(User::factory()->create(['role' => Role::Admin]));
    $this->cash = Account::byCode('1100');
    $this->center = fn (string $code) => CostCenter::query()->where('code', $code)->firstOrFail();
});

it('refuses an entry whose debits and credits differ', function () {
    app(Ledger::class)->post('expense', '2026-10-01', 'test', [
        ['account_id' => Account::byCode('5400')->id, 'debit' => 1000],
        ['account_id' => $this->cash->id, 'credit' => 900],
    ]);
})->throws(ValidationException::class);

it('never lets a posted entry be edited or deleted', function () {
    $entry = app(Ledger::class)->post('expense', '2026-10-01', 'বিদ্যুৎ', [
        ['account_id' => Account::byCode('5400')->id, 'debit' => 1000],
        ['account_id' => $this->cash->id, 'credit' => 1000],
    ]);

    expect(fn () => $entry->update(['description' => 'changed']))->toThrow(LogicException::class)
        ->and(fn () => $entry->delete())->toThrow(LogicException::class);
});

it('splits one vushi purchase across bokna and motataja by kg used', function () {
    $vushi = Item::query()->where('name', 'ভুসি')->firstOrFail();
    $stock = app(StockService::class);

    // 400 kg for 6,000 taka -> 15 taka per kg.
    $stock->purchase($vushi, '2026-10-01', 400, 600000, ['credit_account_id' => $this->cash->id]);
    $stock->issue($vushi->fresh(), '2026-10-07', [
        ['cost_center_id' => ($this->center)('BOKNA')->id, 'qty' => 250],
        ['cost_center_id' => ($this->center)('MOTATAJA')->id, 'qty' => 150],
    ], null);

    $totals = app(ReportService::class)->centerTotals(Date::parse('2026-10-01'), Date::parse('2026-10-31'));

    expect($totals[($this->center)('BOKNA')->id]['expense'])->toBe(375000)
        ->and($totals[($this->center)('MOTATAJA')->id]['expense'])->toBe(225000)
        ->and($vushi->fresh()->qty_on_hand)->toBe(0.0)
        ->and($vushi->fresh()->value_on_hand)->toBe(0);
});

it('uses the weighted average rate when stock was bought at two prices', function () {
    $vushi = Item::query()->where('name', 'ভুসি')->firstOrFail();
    $stock = app(StockService::class);

    $stock->purchase($vushi, '2026-10-01', 100, 150000, ['credit_account_id' => $this->cash->id]); // 15/kg
    $stock->purchase($vushi->fresh(), '2026-10-02', 100, 180000, ['credit_account_id' => $this->cash->id]); // 18/kg
    $entry = $stock->issue($vushi->fresh(), '2026-10-03', [['cost_center_id' => ($this->center)('BOKNA')->id, 'qty' => 50]], null);

    expect($entry->lines()->sum('debit'))->toBe(82500); // 50 kg x 16.50
});

it('refuses to issue more than is in stock', function () {
    $vushi = Item::query()->where('name', 'ভুসি')->firstOrFail();
    app(StockService::class)->issue($vushi, '2026-10-03', [['cost_center_id' => ($this->center)('BOKNA')->id, 'qty' => 5]], null);
})->throws(ValidationException::class);

it('spreads a lease over its months into the land category', function () {
    Date::setTestNow('2026-12-15');

    app(PrepaidService::class)->payLease('2026-10-01', 'ঘাসের জমি লিজ', 1200000, 12, '2026-10-01', ($this->center)('GHAS')->id, $this->cash->id, null, null);

    $totals = app(ReportService::class)->centerTotals(Date::parse('2026-10-01'), Date::parse('2026-12-31'));

    // October, November and December have started: 3 x 1,000 taka.
    expect($totals[($this->center)('GHAS')->id]['expense'])->toBe(300000);
    expect(app(PrepaidService::class)->amortizeDue())->toBe(0);
});

it('shares a common cost by animal-days', function () {
    $animals = app(AnimalService::class);

    foreach (['B-1', 'B-2', 'B-3'] as $tag) {
        $animals->register(['tag' => $tag, 'sex' => 'female', 'cost_center_id' => ($this->center)('BOKNA')->id, 'acquisition' => 'opening', 'acquired_on' => '2026-01-01'], 7000000, null);
    }
    $animals->register(['tag' => 'M-1', 'sex' => 'male', 'cost_center_id' => ($this->center)('MOTATAJA')->id, 'acquisition' => 'opening', 'acquired_on' => '2026-01-01'], 13000000, null);

    app(Ledger::class)->post('expense', '2026-10-05', 'বিদ্যুৎ', [
        ['account_id' => Account::byCode('5400')->id, 'debit' => 100000, 'cost_center_id' => ($this->center)('SHADHARON')->id],
        ['account_id' => $this->cash->id, 'credit' => 100000],
    ]);

    $allocation = app(AllocationService::class);
    $month = Date::parse('2026-10-01');
    $allocation->post(($this->center)('SHADHARON'), $month, $allocation->animalDays($month), 'animal_days');

    $totals = app(ReportService::class)->centerTotals($month, $month->endOfMonth());

    expect($totals[($this->center)('BOKNA')->id]['expense'])->toBe(75000)
        ->and($totals[($this->center)('MOTATAJA')->id]['expense'])->toBe(25000)
        ->and($totals[($this->center)('SHADHARON')->id]['expense'])->toBe(0);
});

it('moves an animal and its value when a bokna becomes a gavi', function () {
    $animals = app(AnimalService::class);
    $animal = $animals->register(['tag' => 'B-9', 'sex' => 'female', 'cost_center_id' => ($this->center)('BOKNA')->id, 'acquisition' => 'purchase', 'acquired_on' => '2026-01-01'], 7000000, $this->cash->id);

    $animals->transfer($animal, ($this->center)('GAVI')->id, '2026-10-01');

    $livestock = Account::byCode('1400');
    $inGavi = $livestock->lines()->where('cost_center_id', ($this->center)('GAVI')->id)->selectRaw('SUM(debit) - SUM(credit) as net')->value('net');

    expect((int) $inGavi)->toBe(7000000)
        ->and($animal->fresh()->placements)->toHaveCount(2);
});

it('blocks postings into a locked month', function () {
    PeriodLock::query()->create(['month' => '2026-09-01', 'locked_by' => auth()->id()]);

    app(Ledger::class)->post('expense', '2026-09-20', 'late', [
        ['account_id' => Account::byCode('5400')->id, 'debit' => 1000],
        ['account_id' => $this->cash->id, 'credit' => 1000],
    ]);
})->throws(ValidationException::class);

it('reverses a stock purchase and puts the stock back', function () {
    $vushi = Item::query()->where('name', 'ভুসি')->firstOrFail();
    $entry = app(StockService::class)->purchase($vushi, '2026-10-01', 100, 150000, ['credit_account_id' => $this->cash->id]);

    app(Ledger::class)->reverse($entry->load('lines'), '2026-10-02', 'ভুল');

    expect($vushi->fresh()->qty_on_hand)->toBe(0.0)
        ->and($this->cash->balance())->toBe(0)
        ->and(JournalEntry::query()->find($entry->id)->reversed_by_id)->not->toBeNull();
});

it('keeps own grass internal so the farm total is not inflated', function () {
    $grass = Item::query()->where('name', 'কাঁচা ঘাস (নিজের জমির)')->firstOrFail();
    $stock = app(StockService::class);

    $stock->produce($grass, '2026-10-01', 1000, 300, ($this->center)('GHAS')->id, null); // 3 taka/kg
    $stock->issue($grass->fresh(), '2026-10-02', [['cost_center_id' => ($this->center)('BOKNA')->id, 'qty' => 1000]], null);

    $report = app(ReportService::class);
    $range = [Date::parse('2026-10-01'), Date::parse('2026-10-31')];
    $totals = $report->centerTotals(...$range);

    expect($totals[($this->center)('GHAS')->id]['internal_income'])->toBe(300000)
        ->and($totals[($this->center)('BOKNA')->id]['internal_expense'])->toBe(300000)
        ->and($report->farmTotals(...$range))->toBe(['income' => 0, 'expense' => 0, 'profit' => 0]);
});

it('remembers the feeding time of day on each issue', function () {
    $vushi = Item::query()->where('name', 'ভুসি')->firstOrFail();
    $stock = app(StockService::class);
    $stock->purchase($vushi, '2026-10-01', 30, 45000, ['credit_account_id' => $this->cash->id]);

    $entry = $stock->issue($vushi->fresh(), '2026-10-02', [['cost_center_id' => ($this->center)('BOKNA')->id, 'qty' => 10]], null, 'noon');

    expect(StockMovement::query()->where('journal_entry_id', $entry->id)->value('session'))->toBe('noon')
        ->and($entry->description)->toContain('দুপুর');
});
