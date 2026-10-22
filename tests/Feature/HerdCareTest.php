<?php

use App\Enums\Role;
use App\Models\Account;
use App\Models\Animal;
use App\Models\Batch;
use App\Models\Breeding;
use App\Models\BudgetHead;
use App\Models\CostCenter;
use App\Models\FarmNote;
use App\Models\Goal;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AnimalService;
use App\Services\HerdCare;
use App\Services\Ledger;
use Database\Seeders\KhamarSetupSeeder;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->actingAs(User::factory()->create(['role' => Role::Admin]));
    $this->center = fn (string $code) => CostCenter::query()->where('code', $code)->firstOrFail();
    $this->cash = Account::byCode('1100');
    $this->addAnimal = fn (array $overrides = []) => $this->post('/animals', [
        'tag' => 'M-'.random_int(100, 999),
        'sex' => 'male',
        'cost_center_id' => ($this->center)('MOTATAJA')->id,
        'acquisition' => 'purchase',
        'acquired_on' => now()->subDays(10)->toDateString(),
        'cost' => '90000',
        'paid_from_account_id' => $this->cash->id,
        ...$overrides,
    ]);
});

it('numbers fattening batches and opens the next one from the new animal form', function () {
    ($this->addAnimal)(['tag' => 'M-1', 'batch' => 'new', 'weight_kg' => '210'])->assertRedirect();
    ($this->addAnimal)(['tag' => 'M-2', 'batch' => (string) Batch::query()->value('id')])->assertRedirect();
    ($this->addAnimal)(['tag' => 'M-3', 'batch' => 'new'])->assertRedirect();

    $batches = Batch::query()->orderBy('number')->get();

    expect($batches->pluck('number')->all())->toBe([1, 2])
        ->and($batches[0]->animals()->count())->toBe(2)
        ->and($batches[0]->daysRunning())->toBe(11)
        ->and(Animal::query()->where('tag', 'M-1')->first()->weights()->value('kg'))->toEqual(210.0);
});

it('shows how much a batch animal gains per day', function () {
    ($this->addAnimal)(['tag' => 'M-1', 'batch' => 'new', 'weight_kg' => '200'])->assertRedirect();
    $animal = Animal::query()->where('tag', 'M-1')->firstOrFail();

    $this->post("/animals/{$animal->id}/weights", ['date' => now()->toDateString(), 'kg' => '208'])->assertRedirect();

    $card = app(HerdCare::class)->batchCard(Batch::query()->firstOrFail());

    expect($card['avg_gain'])->toEqual(0.8)
        ->and($card['cost']['purchase'])->toBe(9000000)
        ->and($card['head'])->toBe(1);
});

it('works out the calving day for a cow bought already carrying', function () {
    $this->post('/animals', [
        'tag' => 'G-7', 'sex' => 'female', 'cost_center_id' => ($this->center)('GAVI')->id,
        'acquisition' => 'purchase', 'acquired_on' => now()->toDateString(), 'cost' => '120000',
        'paid_from_account_id' => $this->cash->id, 'months_pregnant' => '4',
    ])->assertRedirect();

    $breeding = Breeding::query()->firstOrFail();
    $card = app(HerdCare::class)->breedingCard($breeding->load('animal'));

    expect($breeding->result)->toBe('pregnant')
        ->and($breeding->served_on_estimated)->toBeTrue()
        ->and($card['day'])->toBe(122)
        ->and($card['days_left'])->toBe(161)
        ->and($card['stage']['title'])->toContain('৪–৬ মাস');
});

it('reminds to check a service for pregnancy and to prepare for calving', function () {
    $cow = Animal::query()->create(['tag' => 'G-1', 'sex' => 'female', 'cost_center_id' => ($this->center)('GAVI')->id, 'status' => 'active', 'acquisition' => 'opening', 'acquired_on' => '2026-01-01']);
    $heifer = Animal::query()->create(['tag' => 'B-1', 'sex' => 'female', 'cost_center_id' => ($this->center)('BOKNA')->id, 'status' => 'active', 'acquisition' => 'opening', 'acquired_on' => '2026-01-01']);

    $this->post("/animals/{$cow->id}/breedings", ['mode' => 'served', 'served_on' => now()->subDays(70)->toDateString(), 'method' => 'ai'])->assertRedirect();
    $this->post("/animals/{$heifer->id}/breedings", ['mode' => 'months', 'months_pregnant' => '9', 'method' => 'ai'])->assertRedirect();
    FarmNote::query()->create(['date' => now()->toDateString(), 'kind' => 'health', 'title' => 'ক্ষুরা রোগের টিকা', 'follow_up_on' => now()->subDay()->toDateString()]);

    $kinds = collect(app(HerdCare::class)->reminders())->pluck('kind')->all();

    expect($kinds)->toContain('check')->toContain('calving')->toContain('note');

    // A second open service for the same cow is refused.
    $this->post("/animals/{$cow->id}/breedings", ['mode' => 'served', 'served_on' => now()->toDateString(), 'method' => 'ai'])
        ->assertSessionHasErrors('served_on');

    $breeding = Breeding::query()->where('animal_id', $cow->id)->firstOrFail();
    $this->put("/breedings/{$breeding->id}", ['calved_on' => now()->toDateString(), 'outcome' => 'female'])->assertRedirect();

    expect($breeding->fresh()->isOpen())->toBeFalse();
});

it('keeps diary notes and marks a goal achieved when it is reached', function () {
    $this->post('/diary/notes', ['date' => now()->toDateString(), 'kind' => 'mistake', 'title' => 'ভুসি ভিজিয়ে রাখা হয়নি', 'body' => 'পরের বার আগের রাতে ভেজাতে হবে'])->assertRedirect();
    $this->post('/diary/goals', ['title' => 'দিনে দুধ', 'unit' => 'লিটার', 'start_value' => 18, 'target_value' => 30, 'current_value' => 22])->assertRedirect();

    $goal = Goal::query()->firstOrFail();
    expect($goal->progress())->toBe(33)->and($goal->achieved_at)->toBeNull();

    $this->put("/diary/goals/{$goal->id}", ['title' => 'দিনে দুধ', 'unit' => 'লিটার', 'start_value' => 18, 'target_value' => 30, 'current_value' => 31])->assertRedirect();

    expect($goal->fresh()->achieved_at)->not->toBeNull()
        ->and(FarmNote::query()->where('kind', 'mistake')->count())->toBe(1);
});

it('lets the daily role write the diary but not open a batch', function () {
    $worker = User::factory()->create(['role' => Role::Entry]);

    $this->actingAs($worker)->post('/diary/notes', ['date' => now()->toDateString(), 'kind' => 'daily', 'title' => 'সব ঠিক'])->assertRedirect();
    $this->actingAs($worker)->post('/batches', ['cost_center_id' => ($this->center)('MOTATAJA')->id, 'started_on' => now()->toDateString(), 'target_days' => 120])->assertForbidden();
});

it('adds animals bought together under one entry with running tags', function () {
    ($this->addAnimal)(['tag' => 'M-03'])->assertRedirect();
    ($this->addAnimal)(['tag' => null, 'count' => 2, 'cost' => '180000.01', 'batch' => 'new'])->assertRedirect('/animals');

    $pair = Animal::query()->whereIn('tag', ['M-04', 'M-05'])->orderBy('tag')->get();
    $entry = JournalEntry::query()->where('description', 'like', '2টি পশু%')->firstOrFail();

    expect($pair)->toHaveCount(2)
        ->and($pair->sum('purchase_cost'))->toBe(18000001)
        ->and($pair->pluck('batch_id')->unique()->count())->toBe(1)
        ->and($entry->lines()->where('credit', 18000001)->count())->toBe(1)
        ->and($this->cash->balance())->toBe(-27000001);

    // Left blank, a single animal gets the next number too.
    ($this->addAnimal)(['tag' => ''])->assertRedirect();
    expect(Animal::query()->where('tag', 'M-06')->exists())->toBeTrue();
});

it('warns when an enterprise budget head is nearly used up', function () {
    $head = BudgetHead::query()->orderBy('sort')->firstOrFail();
    $spend = (int) round($head->approved_amount * 0.85);

    app(Ledger::class)->post('expense', now()->toDateString(), 'যন্ত্রপাতি', [
        ['account_id' => Account::byCode('5900')->id, 'debit' => $spend, 'budget_head_id' => $head->id],
        ['account_id' => $this->cash->id, 'credit' => $spend],
    ]);

    $warnings = collect(app(HerdCare::class)->budgetWarnings(now()))->pluck('title')->implode(' ');

    expect($warnings)->toContain('85%')->toContain('ভাউচার নেই');
});

it('takes a fattening animal kept as a cow out of its batch', function () {
    ($this->addAnimal)(['tag' => 'M-9', 'sex' => 'female', 'batch' => 'new'])->assertRedirect();
    $animal = Animal::query()->where('tag', 'M-9')->firstOrFail();
    expect($animal->batch_id)->not->toBeNull();

    app(AnimalService::class)->transfer($animal, ($this->center)('GAVI')->id, now()->toDateString());

    expect($animal->fresh()->batch_id)->toBeNull()
        ->and($animal->fresh()->cost_center_id)->toBe(($this->center)('GAVI')->id);
});
