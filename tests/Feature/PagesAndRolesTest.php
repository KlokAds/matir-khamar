<?php

use App\Enums\Role;
use App\Models\Animal;
use App\Models\CostCenter;
use App\Models\JournalEntry;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\KhamarSetupSeeder;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    User::factory()->create(['email' => 'admin@khamar.test', 'role' => Role::Admin]);
    $this->seed(DemoSeeder::class);
});

it('opens every page for the hishab admin', function () {
    $admin = User::query()->where('email', 'admin@khamar.test')->first();
    $pages = [
        '/dashboard', '/milk', '/stock', '/stock/move?mode=issue', '/stock/move?mode=purchase', '/stock/move?mode=produce',
        '/centers', '/centers/'.CostCenter::query()->where('code', 'GORU')->value('id'),
        '/entries', '/entries/create', '/entries/create?form=lease', '/entries/'.JournalEntry::query()->value('id'),
        '/animals', '/animals/create', '/animals/'.Animal::query()->value('id'),
        '/allocation', '/reports', '/reports/budget', '/reports/usage', '/guide/split', '/breeding', '/batches', '/diary', '/land', '/profit', '/prices', '/labour', '/cash', '/health',
        '/setup/accounts', '/setup/centers', '/setup/investors', '/reminders', '/setup/parties', '/setup/users', '/setup/periods', '/setup/audit',
    ];

    foreach ($pages as $page) {
        $this->actingAs($admin)->get($page)->assertOk();
    }
});

it('keeps the daily entry role to milk and feed only', function () {
    $entry = User::factory()->create(['role' => Role::Entry]);

    $this->actingAs($entry)->get('/milk')->assertOk();
    $this->actingAs($entry)->get('/stock/move?mode=issue')->assertOk();

    foreach (['/entries', '/reports', '/entries/create', '/setup/users', '/animals'] as $page) {
        $this->actingAs($entry)->get($page)->assertForbidden();
    }
});

it('lets a viewer see reports but change nothing', function () {
    $viewer = User::factory()->create(['role' => Role::Viewer]);

    $this->actingAs($viewer)->get('/reports')->assertOk();
    $this->actingAs($viewer)->post('/entries', ['form' => 'expense'])->assertForbidden();
    $this->actingAs($viewer)->get('/setup/users')->assertForbidden();
});

it('sends an admin without two-factor to the security page when 2FA is required', function () {
    config(['khamar.require_two_factor' => true]);
    $admin = User::factory()->create(['role' => Role::Admin]);

    $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('security.edit'));
});

it('logs out a disabled user at once', function () {
    $user = User::factory()->create(['role' => Role::Manager, 'is_active' => false]);

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));
    $this->assertGuest();
});

it('finds animals, entries and pages from the search box, and hides books from the daily role', function () {
    $admin = User::query()->where('email', 'admin@khamar.test')->first();

    $this->actingAs($admin)->getJson('/search?q=B-0')->assertOk()
        ->assertJsonFragment(['group' => 'গরু'])
        ->assertJsonFragment(['group' => 'খাতার এন্ট্রি']);

    $worker = User::factory()->create(['role' => Role::Entry]);
    $results = $this->actingAs($worker)->getJson('/search?q=B-0')->assertOk()->json('results');
    expect($results)->toBe([]);

    $this->actingAs($worker)->get('/guide')->assertOk();
});
