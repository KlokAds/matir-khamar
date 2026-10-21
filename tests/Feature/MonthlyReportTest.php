<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\KhamarSetupSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // The page is checked through its Inertia props, so a fresh Vite build is not needed.
    $this->withoutVite();
    $this->seed(KhamarSetupSeeder::class);
    User::factory()->create(['email' => 'admin@khamar.test', 'role' => Role::Admin]);
    $this->seed(DemoSeeder::class);
});

it('opens the monthly report for this month and a chosen month', function () {
    $admin = User::query()->where('email', 'admin@khamar.test')->first();

    $this->actingAs($admin)->get('/reports/monthly')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('reports/Monthly')->where('month', now()->format('Y-m'))->has('farm')->has('herd'));

    $this->actingAs($admin)->get('/reports/monthly?month=2026-09')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('month', '2026-09')->where('previousMonth', '2026-08'));
});

it('rejects a malformed month', function () {
    $admin = User::query()->where('email', 'admin@khamar.test')->first();

    $this->actingAs($admin)->get('/reports/monthly?month=2026-9-1')->assertSessionHasErrors('month');
});
