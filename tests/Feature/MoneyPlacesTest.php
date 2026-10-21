<?php

use App\Enums\Role;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\User;
use Database\Seeders\KhamarSetupSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->admin = User::factory()->create(['role' => Role::Admin]);
});

it('adds a place where money is kept, with what it holds today', function () {
    $this->actingAs($this->admin)->post('/setup/money', ['name' => 'মিঠুর হাতে', 'kind' => 'person', 'opening' => 2000, 'opening_date' => now()->toDateString()])
        ->assertRedirect();

    $place = Account::query()->where('name', 'মিঠুর হাতে')->firstOrFail();

    expect($place->subtype)->toBe('person')
        ->and($place->balance())->toBe(200000);
});

it('shows every place, the total, and a place\'s running balance after a transfer', function () {
    $this->actingAs($this->admin)->post('/setup/money', ['name' => 'মিঠুর হাতে', 'kind' => 'person']);
    $bank = Account::byCode('1110');
    $person = Account::query()->where('name', 'মিঠুর হাতে')->firstOrFail();

    $this->actingAs($this->admin)->post('/setup/money', ['name' => 'সোনালী ব্যাংক', 'kind' => 'bank', 'opening' => 20000, 'opening_date' => now()->toDateString()]);
    $sonali = Account::query()->where('name', 'সোনালী ব্যাংক')->firstOrFail();

    $this->actingAs($this->admin)->post('/entries', ['form' => 'transfer', 'date' => now()->toDateString(), 'from_account_id' => $sonali->id, 'to_account_id' => $person->id, 'amount' => 1500]);

    $this->actingAs($this->admin)->get("/money?place={$person->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('selected.rows.0.balance', 150000)
            ->where('places', fn ($places) => collect($places)->firstWhere('name', 'সোনালী ব্যাংক')['balance'] === 1850000));

    expect($bank->balance())->toBe(0);
});

it('lets an expense be paid from money kept with a person', function () {
    $this->actingAs($this->admin)->post('/setup/money', ['name' => 'মিঠুর হাতে', 'kind' => 'person', 'opening' => 500, 'opening_date' => now()->toDateString()]);
    $person = Account::query()->where('name', 'মিঠুর হাতে')->firstOrFail();

    $this->actingAs($this->admin)->post('/entries', [
        'form' => 'expense', 'date' => now()->toDateString(), 'settle' => 'money', 'money_account_id' => $person->id,
        'lines' => [['account_id' => Account::byCode('5210')->id, 'cost_center_id' => CostCenter::query()->where('code', 'BOKNA')->value('id'), 'amount' => 300]],
    ])->assertSessionHasNoErrors();

    expect($person->balance())->toBe(20000);
});
