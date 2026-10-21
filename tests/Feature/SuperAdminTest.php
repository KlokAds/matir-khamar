<?php

use App\Enums\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->super = User::factory()->create(['email' => 'owner@khamar.test', 'role' => Role::Admin]);
    $this->super->forceFill(['is_super' => true])->save();
    $this->admin = User::factory()->create(['role' => Role::Admin]);
});

it('hides the super admin from other admins', function () {
    $this->actingAs($this->admin)->get('/setup/users')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('users', fn ($users) => collect($users)->doesntContain('email', 'owner@khamar.test')));

    $this->actingAs($this->super)->get('/setup/users')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('users', fn ($users) => collect($users)->contains('email', 'owner@khamar.test')));
});

it('stops other admins from changing or disabling the super admin', function () {
    $this->actingAs($this->admin)->put("/setup/users/{$this->super->id}", ['role' => 'viewer', 'is_active' => false])->assertNotFound();

    expect($this->super->fresh()->is_active)->toBeTrue()
        ->and($this->super->fresh()->role)->toBe(Role::Admin);
});

it('does not force two-factor on the super admin, but does on other admins', function () {
    config(['khamar.require_two_factor' => true]);

    $this->actingAs($this->super)->get('/dashboard')->assertOk();
    $this->actingAs($this->admin)->get('/dashboard')->assertRedirect(route('security.edit'));
});

it('cannot be made super through a form', function () {
    $this->actingAs($this->super)->post('/setup/users', [
        'name' => 'X', 'email' => 'x@khamar.test', 'role' => 'admin', 'password' => 'abcdef12345', 'is_super' => true,
    ]);

    expect(User::query()->where('email', 'x@khamar.test')->first()->is_super)->toBeFalse();
});
