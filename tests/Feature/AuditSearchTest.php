<?php

use App\Enums\Role;
use App\Models\Party;
use App\Models\User;
use Database\Seeders\KhamarSetupSeeder;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->actingAs(User::factory()->create(['role' => Role::Admin]));
});

it('finds audit entries by a Bangla word, by action and by date', function () {
    Party::query()->create(['name' => 'রহিম মিয়া', 'type' => 'customer']);
    Party::query()->create(['name' => 'করিম', 'type' => 'supplier']);

    $this->get('/setup/audit?q='.urlencode('রহিম'))->assertInertia(fn ($page) => $page
        ->has('logs.data', 1)
        ->where('logs.data.0.new.name', 'রহিম মিয়া'));

    $this->get('/setup/audit?action=created&from='.now()->toDateString())
        ->assertInertia(fn ($page) => $page->where('logs.data', fn ($rows) => collect($rows)->every(fn ($r) => $r['action'] === 'created')));

    $this->get('/setup/audit?q=nothing-like-this')->assertInertia(fn ($page) => $page->has('logs.data', 0));
});
