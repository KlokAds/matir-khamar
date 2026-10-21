<?php

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\CraterImporter;
use Database\Seeders\KhamarSetupSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->admin = User::factory()->create(['role' => Role::Admin]);
    $this->form = [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'u477584574_billing',
        'username' => 'khamar_reader',
        'password' => 'billing-secret-123',
        'company_id' => 2,
        'current_password' => 'password',
    ];

    // No billing database exists here; the live connection check is stubbed.
    $this->partialMock(CraterImporter::class, fn ($mock) => $mock->shouldReceive('testConnection')
        ->andReturn(['ok' => true, 'message' => 'সংযোগ হয়েছে।', 'can_write' => false, 'counts' => []]));
});

it('keeps the billing password encrypted and never sends it to the page', function () {
    $this->actingAs($this->admin)->put('/setup/import/connection', $this->form)->assertRedirect();

    $raw = DB::table('settings')->where('key', 'crater.password')->value('value');

    expect($raw)->not->toContain('billing-secret-123')
        ->and(Setting::get('crater.password'))->toBe('billing-secret-123');

    $this->actingAs($this->admin)->get('/setup/import')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('connection.source', 'app')
            ->where('connection.password_set', true)
            ->missing('connection.password'));

    $this->actingAs($this->admin)->get('/setup/import')->assertDontSee('billing-secret-123');
    expect(AuditLog::query()->where('action', 'billing_connection_saved')->first()->new_values)->not->toHaveKey('password')
        ->and(json_encode(AuditLog::query()->get()))->not->toContain('billing-secret-123');
});

it('asks for the admin\'s own password before saving', function () {
    $this->actingAs($this->admin)->put('/setup/import/connection', [...$this->form, 'current_password' => 'wrong'])
        ->assertSessionHasErrors('current_password');

    expect(Setting::query()->count())->toBe(0);
});

it('keeps the saved password when the box is left empty', function () {
    $this->actingAs($this->admin)->put('/setup/import/connection', $this->form);
    $this->actingAs($this->admin)->put('/setup/import/connection', [...$this->form, 'password' => '', 'username' => 'reader2']);

    expect(Setting::get('crater.password'))->toBe('billing-secret-123')
        ->and(Setting::get('crater.username'))->toBe('reader2');
});

it('uses the saved connection instead of .env', function () {
    $this->actingAs($this->admin)->put('/setup/import/connection', $this->form);

    $importer = app(CraterImporter::class);

    expect($importer->configured())->toBeTrue()
        ->and(config('database.connections.crater.username'))->toBe('khamar_reader')
        ->and(config('database.connections.crater.password'))->toBe('billing-secret-123')
        ->and((string) config('services.crater.company_id'))->toBe('2');
});

it('lets only the admin change the billing connection', function () {
    $manager = User::factory()->create(['role' => Role::Manager]);

    $this->actingAs($manager)->put('/setup/import/connection', $this->form)->assertForbidden();
});
