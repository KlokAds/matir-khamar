<?php

use App\Enums\Role;
use App\Models\Account;
use App\Models\Debt;
use App\Models\User;
use App\Services\Udyog;
use App\Support\Lookups;
use Database\Seeders\KhamarSetupSeeder;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->actingAs(User::factory()->create(['role' => Role::Admin]));
});

it('keeps the Udyog heads out of every form until the loan is approved', function () {
    expect(Udyog::approved())->toBeFalse()
        ->and(Lookups::budgetHeads())->toBe([]);

    $this->get('/entries/create')->assertInertia(fn ($page) => $page
        ->where('budgetHeads', [])
        ->missing('forms.grant'));
    $this->get('/udyog')->assertOk();
    $this->get('/reports/budget')->assertRedirect('/udyog');
});

it('shows a yearly plan: equal principal, interest on what is left', function () {
    $rows = app(Udyog::class)->schedule(1000000_00, 5, 5);

    expect($rows)->toHaveCount(5)
        ->and($rows[0])->toMatchArray(['principal' => 200000_00, 'interest' => 50000_00, 'left' => 800000_00])
        ->and($rows[4])->toMatchArray(['principal' => 200000_00, 'interest' => 10000_00, 'left' => 0])
        ->and(array_sum(array_column($rows, 'interest')))->toBe(150000_00);
});

it('opens the loan with its instalments on approval, and only then offers the heads', function () {
    $this->post('/udyog/approve', [
        'date' => now()->toDateString(), 'amount' => '1000000', 'money_account_id' => Account::byCode('1100')->id,
    ])->assertRedirect();

    $debt = Debt::query()->where('direction', 'in')->firstOrFail();
    expect(Udyog::approved())->toBeTrue()
        ->and($debt->balance())->toBe(1000000_00)
        ->and($debt->installments()->count())->toBe(5)
        ->and(Lookups::budgetHeads())->not->toBe([]);

    $this->post('/udyog/approve', [
        'date' => now()->toDateString(), 'amount' => '1000000', 'money_account_id' => Account::byCode('1100')->id,
    ])->assertSessionHasErrors('date');
});
