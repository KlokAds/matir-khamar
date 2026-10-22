<?php

use App\Enums\Role;
use App\Models\CostCenter;
use App\Models\MilkRecord;
use App\Models\User;
use App\Support\Lookups;
use Database\Seeders\KhamarSetupSeeder;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->actingAs(User::factory()->create(['role' => Role::Admin]));
    $this->code = fn (string $code) => CostCenter::query()->where('code', $code)->firstOrFail();
});

it('hides and shows a project with one switch', function () {
    $fish = ($this->code)('MACH');

    $this->post("/setup/centers/{$fish->id}/toggle")->assertRedirect();
    expect($fish->fresh()->is_active)->toBeTrue();

    $this->post("/setup/centers/{$fish->id}/toggle")->assertRedirect();
    expect($fish->fresh()->is_active)->toBeFalse();
});

it('deletes only an unused category and never the ones the app relies on', function () {
    $this->delete('/setup/centers/'.($this->code)('STORE')->id)->assertRedirect();
    expect(CostCenter::query()->where('code', 'STORE')->exists())->toBeFalse();

    $this->delete('/setup/centers/'.($this->code)('GAVI')->id)->assertSessionHasErrors('center');
    expect(CostCenter::query()->where('code', 'GAVI')->exists())->toBeTrue();
});

it('moves a category above its neighbour', function () {
    $sobji = ($this->code)('SHOBJI');
    $joibo = ($this->code)('JOIBO');

    $this->post("/setup/centers/{$sobji->id}/move", ['direction' => 'up'])->assertRedirect();

    expect($sobji->fresh()->sort)->toBeLessThan($joibo->fresh()->sort);
});

it('keeps the code of a system category', function () {
    $gavi = ($this->code)('GAVI');

    $this->put("/setup/centers/{$gavi->id}", ['name' => 'দুধের গাভী', 'code' => 'COWS', 'kind' => 'livestock', 'parent_id' => $gavi->parent_id, 'holds_animals' => true, 'is_active' => true])
        ->assertSessionHasErrors('code');
    $this->put("/setup/centers/{$gavi->id}", ['name' => 'দুধের গাভী', 'code' => 'GAVI', 'kind' => 'livestock', 'parent_id' => $gavi->parent_id, 'holds_animals' => true, 'is_active' => true])
        ->assertSessionHasNoErrors();

    expect($gavi->fresh()->name)->toBe('দুধের গাভী');
});

it('hides everything inside a project that is off, from the menu and the lists', function () {
    ($this->code)('GORU')->update(['is_active' => false]);

    $codes = array_column(Lookups::centers(), 'code');
    expect($codes)->not->toContain('GAVI')->not->toContain('MOTATAJA')
        ->and(CostCenter::modules())->toMatchArray(['cattle' => false, 'milk' => false, 'fattening' => false])
        ->and(CostCenter::runningIds())->not->toContain(($this->code)('GAVI')->id);

    $this->get('/dashboard')->assertInertia(fn ($page) => $page->where('modules.cattle', false));
});

it('keeps the milk pages out of the menu until there is milk to write', function () {
    expect(CostCenter::modules()['milk'])->toBeFalse()
        ->and(CostCenter::modules()['cattle'])->toBeTrue();

    MilkRecord::query()->create([
        'date' => now()->toDateString(), 'session' => 'morning', 'liters' => 4,
        'cost_center_id' => ($this->code)('GAVI')->id, 'recorded_by' => auth()->id(),
    ]);

    expect(CostCenter::modules()['milk'])->toBeTrue();
});
