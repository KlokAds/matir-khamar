<?php

use App\Enums\Role;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Item;
use App\Models\PushSubscription;
use App\Models\Reminder;
use App\Models\User;
use App\Services\Notifier;
use App\Services\PushSender;
use App\Services\StockForecast;
use App\Services\StockService;
use Database\Seeders\KhamarSetupSeeder;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->admin = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($this->admin);

    $this->sent = [];
    $sent = &$this->sent;
    $this->mock(PushSender::class, function ($mock) use (&$sent) {
        $mock->shouldReceive('toUsers')->andReturnUsing(function ($users, $payload) use (&$sent) {
            $sent[] = ['users' => collect($users)->pluck('id')->all(), 'payload' => $payload];

            return 1;
        });
        $mock->shouldReceive('publicKey')->andReturn('test-key');
    });
});

it('sends a reminder once it falls due, with buttons on the notification', function () {
    $this->post('/reminders', ['title' => 'জমিতে সার দিতে হবে', 'due_at' => now()->addHour()->format('Y-m-d\TH:i'), 'repeat' => 'none'])->assertRedirect();

    $notifier = app(Notifier::class);
    expect($notifier->sendDueReminders())->toBe(0);

    $this->travel(61)->minutes();
    expect($notifier->sendDueReminders())->toBe(1)
        ->and($this->sent[0]['payload']['title'])->toContain('জমিতে সার')
        ->and($this->sent[0]['payload']['actions'])->toHaveCount(2)
        ->and($notifier->sendDueReminders())->toBe(0);
});

it('lets the notification buttons close or postpone a reminder without a session', function () {
    $reminder = Reminder::query()->create(['title' => 'টিকা', 'due_at' => now(), 'created_by' => $this->admin->id, 'sent_at' => now()]);
    auth()->logout();

    $later = URL::temporarySignedRoute('reminders.act', now()->addDay(), ['reminder' => $reminder->id, 'action' => 'later', 'user' => $this->admin->id]);
    $this->post($later)->assertOk();
    expect($reminder->fresh()->sent_at)->toBeNull()
        ->and($reminder->fresh()->due_at->greaterThan(now()->addMinutes(59)))->toBeTrue();

    $done = URL::temporarySignedRoute('reminders.act', now()->addDay(), ['reminder' => $reminder->id, 'action' => 'done', 'user' => $this->admin->id]);
    $this->post($done)->assertOk();
    expect($reminder->fresh()->done_at)->not->toBeNull();

    // An unsigned link does nothing.
    $this->post("/reminders/{$reminder->id}/act/done")->assertForbidden();
});

it('brings a daily reminder back the next day', function () {
    Reminder::query()->create(['title' => 'পানি বদলান', 'due_at' => now()->subMinute(), 'repeat' => 'daily', 'created_by' => $this->admin->id]);

    app(Notifier::class)->sendDueReminders();

    expect(Reminder::query()->whereNull('sent_at')->first()?->due_at->isTomorrow())->toBeTrue();
});

it('warns in the morning when feed is about to run out', function () {
    $vushi = Item::query()->where('name', 'ভুসি')->firstOrFail();
    $stock = app(StockService::class);
    $stock->purchase($vushi, now()->subDays(10)->toDateString(), 100, 150000, ['credit_account_id' => Account::byCode('1100')->id]);
    $stock->issue($vushi->fresh(), now()->subDays(2)->toDateString(), [['cost_center_id' => CostCenter::query()->where('code', 'BOKNA')->value('id'), 'qty' => 84]], null);

    // 84 kg over 14 days = 6 kg a day; 16 kg left lasts under 3 days.
    $row = collect(app(StockForecast::class)->items())->firstWhere('id', $vushi->id);
    expect($row['per_day'])->toEqual(6.0)->and($row['low'])->toBeTrue();

    app(Notifier::class)->sendMorningDigest();

    expect(collect($this->sent)->pluck('payload.body')->implode(' '))->toContain('ভুসি আর 3 দিন চলবে');
});

it('nudges in the evening only when nothing was written today', function () {
    app(Notifier::class)->sendEveningCheck();

    expect($this->sent)->toHaveCount(1)
        ->and($this->sent[0]['payload']['title'])->toContain('খাবার');
});

it('keeps one subscription per phone', function () {
    $body = ['endpoint' => 'https://fcm.googleapis.com/fcm/send/abc', 'keys' => ['p256dh' => 'key', 'auth' => 'auth']];

    $this->postJson('/push/subscribe', $body)->assertNoContent();
    $this->postJson('/push/subscribe', $body)->assertNoContent();

    expect(PushSubscription::query()->count())->toBe(1);
    $this->get('/reminders')->assertOk();
});
