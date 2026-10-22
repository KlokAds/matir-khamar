<?php

use App\Enums\Role;
use App\Models\AppNotification;
use App\Models\PushSubscription;
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

it('clears the bell, one by one or all at once', function () {
    app(PushSender::class)->toUsers([$this->user], ['title' => 'এক']);
    app(PushSender::class)->toUsers([$this->user], ['title' => 'দুই']);
    $first = AppNotification::query()->where('title', 'এক')->firstOrFail();

    $this->deleteJson("/notifications/{$first->id}")->assertNoContent();
    expect(AppNotification::query()->pluck('title')->all())->toBe(['দুই']);

    $this->deleteJson('/notifications')->assertNoContent();
    expect(AppNotification::query()->count())->toBe(0);
});

it('records what a phone says it received, and sends empty pushes to a phone that never does', function () {
    $sub = PushSubscription::query()->create([
        'user_id' => $this->user->id, 'endpoint' => 'https://fcm.example/x', 'endpoint_hash' => hash('sha256', 'https://fcm.example/x'),
        'public_key' => 'k', 'auth_token' => 'a', 'last_sent_at' => now()->subMinutes(10),
    ]);

    $this->postJson('/push/received', ['endpoint' => 'https://fcm.example/x'])->assertNoContent();
    expect($sub->fresh()->last_received_at)->not->toBeNull();

    $sub->update(['last_received_at' => now()->subMinutes(30), 'last_sent_at' => now()->subMinutes(10)]);
    app(PushSender::class)->toUsers([$this->user], ['title' => 'তিন']);

    expect($sub->fresh()->mode)->toBe('tickle');
});
