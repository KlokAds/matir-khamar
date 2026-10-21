<?php

use App\Enums\Role;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\MilkRecord;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\KhamarSetupSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->admin = User::factory()->create(['role' => Role::Admin]);
    $this->entry = User::factory()->create(['role' => Role::Entry]);
    $this->bokna = CostCenter::query()->where('code', 'BOKNA')->value('id');
    $this->vushi = Item::query()->where('name', 'ভুসি')->firstOrFail();

    $this->actingAs($this->admin);
    app(StockService::class)->purchase($this->vushi, now()->toDateString(), 100, 150000, ['credit_account_id' => Account::byCode('1100')->id]);
});

function feedItem(string $uuid, float $qty, int $center): array
{
    return [
        'uuid' => $uuid,
        'kind' => 'feed',
        'recorded_at' => now()->toIso8601String(),
        'payload' => ['item_id' => Item::query()->where('name', 'ভুসি')->value('id'), 'date' => now()->toDateString(), 'splits' => [['cost_center_id' => $center, 'qty' => $qty]]],
    ];
}

it('books an offline feed entry exactly once even if the phone sends it twice', function () {
    $item = feedItem((string) Str::uuid(), 20, $this->bokna);

    $first = $this->actingAs($this->entry)->postJson('/offline/sync', ['items' => [$item]])->assertOk()->json('results.0');
    $second = $this->actingAs($this->entry)->postJson('/offline/sync', ['items' => [$item]])->assertOk()->json('results.0');

    expect($first['status'])->toBe('done')
        ->and($second)->toBe($first)
        ->and(JournalEntry::query()->where('type', 'stock_issue')->count())->toBe(1)
        ->and($this->vushi->fresh()->qty_on_hand)->toBe(80.0);
});

it('keeps a clear refusal, such as asking for more feed than is in stock', function () {
    $result = $this->actingAs($this->entry)->postJson('/offline/sync', ['items' => [feedItem((string) Str::uuid(), 500, $this->bokna)]])->json('results.0');

    expect($result['status'])->toBe('failed')
        ->and($result['message'])->toContain('স্টকে আছে');
});

it('records offline milk', function () {
    $this->actingAs($this->entry)->postJson('/offline/sync', ['items' => [[
        'uuid' => (string) Str::uuid(),
        'kind' => 'milk',
        'recorded_at' => now()->toIso8601String(),
        'payload' => ['date' => now()->subDays(10)->toDateString(), 'session' => 'morning', 'rows' => [['animal_id' => null, 'liters' => 12.5]]],
    ]]])->assertOk()->assertJsonPath('results.0.status', 'done');

    expect(MilkRecord::query()->sum('liters'))->toEqual(12.5);
});

it('does not let the daily-entry role post money offline', function () {
    $result = $this->actingAs($this->entry)->postJson('/offline/sync', ['items' => [[
        'uuid' => (string) Str::uuid(),
        'kind' => 'expense',
        'recorded_at' => now()->toIso8601String(),
        'payload' => ['date' => now()->toDateString(), 'money_account_id' => Account::byCode('1100')->id, 'account_id' => Account::byCode('5400')->id, 'cost_center_id' => $this->bokna, 'amount' => 500],
    ]]])->json('results.0');

    expect($result['status'])->toBe('failed')
        ->and(JournalEntry::query()->where('type', 'expense')->count())->toBe(0);
});

it('lets a manager post a cash expense offline', function () {
    $manager = User::factory()->create(['role' => Role::Manager]);

    $this->actingAs($manager)->postJson('/offline/sync', ['items' => [[
        'uuid' => (string) Str::uuid(),
        'kind' => 'expense',
        'recorded_at' => now()->toIso8601String(),
        'payload' => ['date' => now()->toDateString(), 'money_account_id' => Account::byCode('1100')->id, 'account_id' => Account::byCode('5210')->id, 'cost_center_id' => $this->bokna, 'amount' => 500, 'description' => 'ডাক্তার'],
    ]]])->assertJsonPath('results.0.status', 'done');

    expect(Account::byCode('5210')->balance())->toBe(50000);
});

it('never hands one user the result of another user\'s entry', function () {
    $item = feedItem((string) Str::uuid(), 5, $this->bokna);
    $this->actingAs($this->entry)->postJson('/offline/sync', ['items' => [$item]]);

    $other = User::factory()->create(['role' => Role::Entry]);
    $result = $this->actingAs($other)->postJson('/offline/sync', ['items' => [$item]])->json('results.0');

    expect($result['status'])->toBe('failed')->and($result['number'])->toBeNull();
});

it('serves the offline page to the daily-entry role', function () {
    $this->actingAs($this->entry)->get('/offline')->assertOk();
});
