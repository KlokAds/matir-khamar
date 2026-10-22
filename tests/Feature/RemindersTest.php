<?php

use App\Enums\Role;
use App\Models\Account;
use App\Models\Animal;
use App\Models\Breeding;
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
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
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

it('changes a reminder that was already sent, so it rings again at the new time, and deletes one', function () {
    $this->post('/reminders', ['title' => 'পুরোনো', 'due_at' => now()->addMinutes(5)->format('Y-m-d\TH:i'), 'repeat' => 'none']);
    $this->travel(6)->minutes();
    app(Notifier::class)->sendDueReminders();
    $reminder = Reminder::query()->sole();
    expect($reminder->sent_at)->not->toBeNull();

    $this->patch("/reminders/{$reminder->id}", [
        'title' => 'নতুন কথা', 'due_at' => now()->addMinutes(10)->format('Y-m-d\TH:i'), 'repeat' => 'none', 'alarm' => true,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $reminder->refresh();
    expect($reminder->title)->toBe('নতুন কথা')->and($reminder->alarm)->toBeTrue()->and($reminder->sent_at)->toBeNull();

    $this->travel(11)->minutes();
    expect(app(Notifier::class)->sendDueReminders())->toBe(1)
        ->and(end($this->sent)['payload']['title'])->toContain('নতুন কথা');

    $this->delete("/reminders/{$reminder->id}")->assertRedirect();
    expect(Reminder::query()->count())->toBe(0);
});

it('gives the farm app the coming week\'s reminders to ring on the phone without internet', function () {
    $this->post('/reminders', ['title' => 'সার দিতে হবে', 'due_at' => now()->addHours(3)->format('Y-m-d\TH:i'), 'repeat' => 'none', 'alarm' => true]);
    $this->post('/reminders', ['title' => 'অনেক পরে', 'due_at' => now()->addDays(20)->format('Y-m-d\TH:i'), 'repeat' => 'none']);
    $other = User::factory()->create(['role' => Role::Manager]);
    Reminder::query()->create(['title' => 'অন্যের', 'due_at' => now()->addHour(), 'repeat' => 'none', 'created_by' => $other->id, 'for_user_id' => $other->id]);

    $items = $this->getJson('/reminders/upcoming')->assertOk()->json();

    expect($items)->toHaveCount(1)
        ->and($items[0]['title'])->toBe('🔔 সার দিতে হবে')
        ->and($items[0]['alarm'])->toBeTrue()
        ->and($items[0]['at'])->toBe(Reminder::query()->where('title', 'সার দিতে হবে')->value('due_at')->toIso8601String())
        ->and($items[0]['actions'])->toHaveCount(2);

    // The "done" button still works once the reminder falls due, days from now.
    $this->travel(3)->hours();
    auth()->logout();
    $this->postJson($items[0]['actions'][0]['url'])->assertOk();
    expect(Reminder::query()->where('title', 'সার দিতে হবে')->value('done_at'))->not->toBeNull();
});

it('does not let one person change another person\'s private reminder', function () {
    $other = User::factory()->create(['role' => Role::Manager]);
    $reminder = Reminder::query()->create(['title' => 'নিজের', 'due_at' => now()->addHour(), 'repeat' => 'none', 'created_by' => $other->id, 'for_user_id' => $other->id]);
    $worker = User::factory()->create(['role' => Role::Manager]);

    $this->actingAs($worker)->delete("/reminders/{$reminder->id}")->assertForbidden();
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

it('nudges at 8 pm and once more at 9:30, even when an update clears the cache', function () {
    [$first, $again] = collect(app(Schedule::class)->events())
        ->filter(fn ($event) => str_contains((string) $event->command, 'khamar:notify --evening'))->values()->all();

    $this->travelTo(now()->setTime(19, 30));
    expect($first->filtersPass(app()))->toBeFalse();

    $this->travelTo(now()->setTime(20, 1));
    expect($first->filtersPass(app()))->toBeTrue()
        ->and($again->filtersPass(app()))->toBeFalse();
    Cache::flush();
    expect($first->filtersPass(app()))->toBeFalse();

    $this->travelTo(now()->setTime(21, 31));
    expect($again->filtersPass(app()))->toBeTrue()
        ->and($again->filtersPass(app()))->toBeFalse();

    $this->travel(1)->days();
    expect($first->filtersPass(app()))->toBeTrue();
});

it('asks for milk only once there is milk to write', function () {
    $cow = Animal::query()->create(['tag' => 'G-01', 'sex' => 'female', 'status' => 'active', 'acquisition' => 'born', 'acquired_on' => now()->subYears(3)->toDateString(), 'cost_center_id' => CostCenter::query()->where('code', 'GAVI')->value('id')]);

    app(Notifier::class)->sendEveningCheck();
    expect($this->sent[0]['payload']['title'])->not->toContain('দুধ');

    Breeding::query()->create(['animal_id' => $cow->id, 'served_on' => now()->subDays(290)->toDateString(), 'method' => 'ai', 'result' => 'pregnant', 'expected_on' => now()->subDays(7)->toDateString(), 'calved_on' => now()->subDays(3)->toDateString()]);
    app(Notifier::class)->sendEveningCheck();
    expect($this->sent[1]['payload']['title'])->toContain('দুধ');
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

it('keeps ringing an alarm every two minutes until it is answered', function () {
    $alarm = Reminder::query()->create(['title' => 'গাভী দোহানো', 'due_at' => now()->subMinute(), 'alarm' => true, 'created_by' => $this->admin->id]);
    $notifier = app(Notifier::class);

    $notifier->sendDueReminders();
    expect($this->sent)->toHaveCount(1)->and($this->sent[0]['payload']['alarm'])->toBeTrue();

    // Not yet two minutes: quiet.
    $notifier->sendDueReminders();
    expect($this->sent)->toHaveCount(1);

    $this->travel(2)->minutes();
    $notifier->sendDueReminders();
    expect($this->sent)->toHaveCount(2)->and($alarm->fresh()->rings)->toBe(2);

    // Answered: no more rings.
    $alarm->update(['done_at' => now()]);
    $this->travel(3)->minutes();
    $notifier->sendDueReminders();
    expect($this->sent)->toHaveCount(2);
});
