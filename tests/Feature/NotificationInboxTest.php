<?php

use App\Enums\Role;
use App\Models\AppNotification;
use App\Models\User;
use App\Services\PushSender;
use Database\Seeders\KhamarSetupSeeder;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->user = User::factory()->create(['role' => Role::Manager]);
    $this->actingAs($this->user);
});

it('keeps every notification for the bell, even with no phone signed up', function () {
    app(PushSender::class)->toUsers([$this->user], ['title' => 'দুধ লিখুন', 'body' => 'বিকালের দুধ বাকি', 'url' => '/milk', 'tag' => 'evening']);

    expect(AppNotification::query()->where('user_id', $this->user->id)->count())->toBe(1);

    $this->get('/dashboard')->assertInertia(fn ($page) => $page->where('notificationsUnread', 1));
    $this->getJson('/notifications')->assertOk()->assertJsonPath('items.0.title', 'দুধ লিখুন')->assertJsonPath('items.0.read', false);
    $this->getJson('/notifications/latest')->assertOk()->assertJsonPath('0.body', 'বিকালের দুধ বাকি');

    $this->postJson('/notifications/read')->assertNoContent();
    expect(AppNotification::query()->whereNull('read_at')->count())->toBe(0);
});

it('shows each person only their own notifications', function () {
    $other = User::factory()->create(['role' => Role::Manager]);
    app(PushSender::class)->toUsers([$other], ['title' => 'অন্যের']);

    $this->getJson('/notifications')->assertJsonCount(0, 'items');
});
