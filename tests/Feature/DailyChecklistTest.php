<?php

use App\Enums\Role;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\MilkRecord;
use App\Models\Reminder;
use App\Models\User;
use App\Services\DailyChecklist;
use Database\Seeders\KhamarSetupSeeder;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->worker = User::factory()->create(['role' => Role::Entry]);
    $this->actingAs($this->worker);
    $this->center = fn (string $code) => CostCenter::query()->where('code', $code)->firstOrFail();
});

it('lists the day\'s jobs and ticks milk off once it is written', function () {
    // Milk jobs appear with the first milk; here one cow already gave yesterday.
    MilkRecord::query()->create(['date' => now()->subDay()->toDateString(), 'session' => 'morning', 'liters' => 5, 'cost_center_id' => ($this->center)('GAVI')->id, 'recorded_by' => $this->worker->id]);
    Reminder::query()->create(['created_by' => $this->worker->id, 'title' => 'ডাক্তার আসবেন', 'due_at' => now()->setTime(17, 0)]);

    $jobs = collect(app(DailyChecklist::class)->for($this->worker));
    expect($jobs->pluck('key'))->toContain('milk-morning', 'milk-evening', 'feed')
        ->and($jobs->firstWhere('key', 'milk-morning')['done'])->toBeFalse()
        ->and($jobs->pluck('title'))->toContain('ডাক্তার আসবেন');

    MilkRecord::query()->create(['date' => now()->toDateString(), 'session' => 'morning', 'liters' => 6, 'cost_center_id' => ($this->center)('GAVI')->id, 'recorded_by' => $this->worker->id]);

    $jobs = collect(app(DailyChecklist::class)->for($this->worker));
    expect($jobs->firstWhere('key', 'milk-morning')['done'])->toBeTrue()
        ->and($jobs->firstWhere('key', 'milk-evening')['done'])->toBeFalse();
});

it('asks to weigh fattening animals not weighed for a month, and shows on the dashboard', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($admin)->post('/animals', [
        'tag' => 'M-77', 'sex' => 'male', 'cost_center_id' => ($this->center)('MOTATAJA')->id,
        'acquisition' => 'purchase', 'acquired_on' => now()->subDays(40)->toDateString(), 'cost' => '80000',
        'paid_from_account_id' => Account::byCode('1100')->id,
    ])->assertRedirect();

    $jobs = collect(app(DailyChecklist::class)->for($this->worker));
    expect($jobs->firstWhere('key', 'weigh')['title'])->toContain('M-77');

    $this->actingAs($this->worker)->get('/dashboard')->assertInertia(fn ($page) => $page->has('today'));
});
