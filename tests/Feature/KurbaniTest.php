<?php

use App\Enums\Role;
use App\Models\Account;
use App\Models\Animal;
use App\Models\CostCenter;
use App\Models\Setting;
use App\Models\User;
use App\Services\KurbaniPlanner;
use Database\Seeders\KhamarSetupSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->actingAs(User::factory()->create(['role' => Role::Admin]));

    $this->planner = app(KurbaniPlanner::class);
    $this->addAnimal = fn (string $tag, string $batch, string $kg) => $this->post('/animals', [
        'tag' => $tag,
        'sex' => 'male',
        'cost_center_id' => CostCenter::query()->where('code', 'MOTATAJA')->value('id'),
        'acquisition' => 'purchase',
        'acquired_on' => now()->subDays(10)->toDateString(),
        'cost' => '90000',
        'paid_from_account_id' => Account::byCode('1100')->id,
        'batch' => $batch,
        'weight_kg' => $kg,
    ])->assertRedirect();
});

it('projects a batch of two weighed animals to the saved Eid date', function () {
    ($this->addAnimal)('M-1', 'new', '200');
    $batchId = (string) Animal::query()->where('tag', 'M-1')->value('batch_id');
    ($this->addAnimal)('M-2', $batchId, '220');

    foreach (['M-1' => '208', 'M-2' => '226'] as $tag => $kg) {
        $animal = Animal::query()->where('tag', $tag)->firstOrFail();
        $this->post("/animals/{$animal->id}/weights", ['date' => now()->toDateString(), 'kg' => $kg])->assertRedirect();
    }

    Setting::put(KurbaniPlanner::EID_KEY, now()->addDays(100)->toDateString());

    $plan = $this->planner->plan(now());

    expect($plan['eid'])->toBe(now()->addDays(100)->toDateString())
        ->and($plan['eid_saved'])->toBeTrue()
        ->and($plan['days_left'])->toBe(100)
        ->and($plan['phase'])->toBe('buy')
        ->and($plan['window']['from'])->toBe(now()->addDays(100 - 120)->toDateString())
        ->and($plan['batches'])->toHaveCount(1);

    $batch = $plan['batches'][0];
    $rows = collect($batch['animals'])->keyBy('tag');

    // 0.8 and 0.6 kg a day over 10 days, carried 100 days to Eid.
    expect($batch['head'])->toBe(2)
        ->and($batch['days_by_eid'])->toBe(111)
        ->and($batch['ready'])->toBeFalse()
        ->and($batch['short_days'])->toBe(9)
        ->and($rows['M-1']['gain'])->toEqual(0.8)
        ->and($rows['M-1']['gain_source'])->toBe('own')
        ->and($rows['M-1']['expected_kg'])->toEqual(288.0)
        ->and($rows['M-2']['expected_kg'])->toEqual(286.0)
        ->and($batch['expected_kg'])->toEqual(574.0)
        // ৳500/kg → ৳1,44,000 for 288 kg.
        ->and($rows['M-1']['value'])->toBe(14400000)
        ->and($rows['M-1']['future_cost'])->toBe(KurbaniPlanner::DEFAULT_FEED_PER_DAY * 100)
        ->and($rows['M-1']['profit'])->toBe(14400000 - $rows['M-1']['spent'] - KurbaniPlanner::DEFAULT_FEED_PER_DAY * 100)
        ->and($rows['M-1']['spent'])->toBeGreaterThanOrEqual(9000000)
        ->and($batch['profit'])->toBe($rows['M-1']['profit'] + $rows['M-2']['profit']);
});

it('assumes 0.7 kg a day for an animal weighed once and asks for a weight when there is none', function () {
    ($this->addAnimal)('M-1', 'new', '200');
    $batchId = (string) Animal::query()->where('tag', 'M-1')->value('batch_id');
    ($this->addAnimal)('M-2', $batchId, '');

    Setting::put(KurbaniPlanner::EID_KEY, now()->addDays(50)->toDateString());
    $rows = collect($this->planner->plan(now())['batches'][0]['animals'])->keyBy('tag');

    // Weighed 10 days ago: 200 + 0.7 × 60.
    expect($rows['M-1']['gain_source'])->toBe('assumed')
        ->and($rows['M-1']['expected_kg'])->toEqual(242.0)
        ->and($rows['M-2']['expected_kg'])->toBeNull()
        ->and($rows['M-2']['value'])->toBeNull();
});

it('picks the next known Eid date when none is saved', function () {
    expect($this->planner->eid(now()->setDate(2026, 10, 22))['date']->toDateString())->toBe('2027-05-17')
        ->and($this->planner->eid(now()->setDate(2027, 5, 18))['date']->toDateString())->toBe('2028-05-05');
});

it('reminds to buy inside the window and to get ready in the last month', function () {
    Setting::put(KurbaniPlanner::EID_KEY, now()->addDays(100)->toDateString());
    $titles = array_column($this->planner->reminders(now()), 'title');
    expect($titles)->toContain('কোরবানির জন্য গরু কেনার সময় শুরু');

    Setting::put(KurbaniPlanner::EID_KEY, now()->addDays(130)->toDateString());
    expect($this->planner->reminders(now()))->toBe([]);

    Setting::put(KurbaniPlanner::EID_KEY, now()->addDays(20)->toDateString());
    $items = $this->planner->reminders(now());
    expect($items)->toHaveCount(1)
        ->and($items[0]['title'])->toBe('কোরবানির 20 দিন বাকি — ব্যাচ বিক্রির প্রস্তুতি')
        ->and($items[0]['href'])->toBe('/kurbani')
        ->and($items[0])->toHaveKeys(['kind', 'title', 'detail', 'href', 'when', 'urgent']);
});

it('loads the page and lets setup save the Eid date and price', function () {
    $this->get('/kurbani')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('herd/Kurbani')
        ->where('plan.eid', $this->planner->eid(now())['date']->toDateString())
        ->has('calc.buy_price'));

    $date = now()->addDays(80)->toDateString();
    $this->put('/kurbani/eid', ['eid_date' => $date, 'price_per_kg' => '550'])->assertRedirect();

    expect(Setting::get(KurbaniPlanner::EID_KEY))->toBe($date)
        ->and($this->planner->pricePerKg())->toBe(55000);

    $worker = User::factory()->create(['role' => Role::Viewer]);
    $this->actingAs($worker)->put('/kurbani/eid', ['eid_date' => $date])->assertForbidden();
});
