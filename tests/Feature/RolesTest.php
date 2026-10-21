<?php

use App\Enums\Role;
use App\Models\AccessRole;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => Role::Admin]);
});

it('starts with the four built-in roles and their abilities', function () {
    expect(AccessRole::query()->pluck('key')->sort()->values()->all())->toBe(['admin', 'entry', 'manager', 'viewer'])
        ->and(User::factory()->create(['role' => Role::Entry])->abilities())->toBe(['post-daily']);
});

it('lets the admin change what a role may do, and the change takes effect at once', function () {
    $manager = User::factory()->create(['role' => Role::Manager]);
    $role = AccessRole::query()->where('key', 'manager')->firstOrFail();

    $this->actingAs($manager)->get('/setup/audit')->assertForbidden();

    $this->actingAs($this->admin)->put("/setup/roles/{$role->id}", [
        'name' => 'খামার ম্যানেজার', 'abilities' => ['view-reports', 'post-daily', 'view-audit'], 'requires_two_factor' => false,
    ])->assertRedirect();

    $this->actingAs($manager->fresh())->get('/setup/audit')->assertOk();
    $this->actingAs($manager->fresh())->get('/entries/create')->assertForbidden();
});

it('never lets the admin role lose an ability', function () {
    $role = AccessRole::query()->where('key', 'admin')->firstOrFail();

    $this->actingAs($this->admin)->put("/setup/roles/{$role->id}", ['name' => 'অ্যাডমিন', 'abilities' => [], 'requires_two_factor' => true]);

    expect($this->admin->fresh()->hasAbility('manage-roles'))->toBeTrue();
});

it('adds a custom role that users can be given, and removes it only when empty', function () {
    $this->actingAs($this->admin)->post('/setup/roles', ['name' => 'Accountant', 'abilities' => ['view-reports', 'post-entries'], 'requires_two_factor' => true]);
    $role = AccessRole::query()->where('name', 'Accountant')->firstOrFail();

    $this->actingAs($this->admin)->post('/setup/users', ['name' => 'Hisab', 'email' => 'hisab@khamar.test', 'role' => $role->key, 'password' => 'abcdef12345']);
    $user = User::query()->where('email', 'hisab@khamar.test')->firstOrFail();

    expect($user->abilities())->toBe(['view-reports', 'post-entries'])
        ->and($user->mustUseTwoFactor())->toBeTrue();

    $this->actingAs($this->admin)->delete("/setup/roles/{$role->id}")->assertSessionHasErrors('role');
    $user->update(['role' => 'viewer']);
    $this->actingAs($this->admin)->delete("/setup/roles/{$role->id}");
    expect(AccessRole::query()->find($role->id))->toBeNull();
});

it('keeps roles pages away from people without the ability', function () {
    $this->actingAs(User::factory()->create(['role' => Role::Manager]))->get('/setup/roles')->assertForbidden();
});
