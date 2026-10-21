<?php

use App\Enums\Role;
use App\Models\CostCenter;
use App\Models\User;
use App\Support\BanglaDate;
use Database\Seeders\KhamarSetupSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
});

it('sends labels and messages in English to someone who picked English', function () {
    $this->actingAs(User::factory()->create(['role' => Role::Admin, 'locale' => 'en']));

    $this->get('/animals')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('statuses.active', 'On the farm')
        ->where('statuses.sold', 'Sold'));

    $farm = CostCenter::query()->where('code', 'FARM')->firstOrFail();
    $this->post("/setup/centers/{$farm->id}/toggle")
        ->assertSessionHasErrors(['center' => 'This cannot be turned off — all the accounts run on it.']);
});

it('keeps everything in Bangla for everyone else', function () {
    $this->actingAs(User::factory()->create(['role' => Role::Admin, 'locale' => 'bn']));

    $this->get('/animals')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('statuses.active', 'খামারে আছে'));

    $farm = CostCenter::query()->where('code', 'FARM')->firstOrFail();
    $this->post("/setup/centers/{$farm->id}/toggle")
        ->assertSessionHasErrors(['center' => 'এটি বন্ধ করা যায় না — পুরো হিসাব এর ওপর চলে।']);
});

it('writes dates in the reader\'s language', function () {
    $date = now()->setDate(2027, 3, 15);

    app()->setLocale('en');
    expect(BanglaDate::day($date))->toBe('15 March 2027')
        ->and(BanglaDate::span(125))->toBe('4 months 5 days')
        // Ledger descriptions are stored, so they stay in Bangla.
        ->and(BanglaDate::month($date, 'bn'))->toBe('মার্চ 2027');

    app()->setLocale('bn');
    expect(BanglaDate::span(125))->toBe('4 মাস 5 দিন');
});
