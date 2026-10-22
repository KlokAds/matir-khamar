<?php

use App\Enums\Role;
use App\Models\Account;
use App\Models\Debt;
use App\Models\User;
use App\Services\CashForecast;
use App\Services\DebtBook;
use App\Services\HerdCare;
use Database\Seeders\KhamarSetupSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->actingAs(User::factory()->create(['role' => Role::Admin]));
    $this->cash = Account::byCode('1100');
});

it('keeps money taken from an investor as a debt to repay in instalments, with reminders', function () {
    $book = app(DebtBook::class);
    $debt = $book->open(
        ['direction' => 'in', 'person_name' => 'রহিম ভাই', 'purpose' => 'বিনিয়োগকারীর ধার', 'date' => now()->subMonth()->toDateString()],
        6000000, $this->cash->id,
        [['due_on' => now()->addDays(2)->toDateString(), 'amount' => 3000000], ['due_on' => now()->addMonths(2)->toDateString(), 'amount' => 3000000]],
    );

    expect($debt->balance())->toBe(6000000)
        ->and($this->cash->balance())->toBe(6000000)
        ->and($debt->account->type)->toBe('liability');

    $reminder = collect(app(HerdCare::class)->reminders())->firstWhere('kind', 'debt_pay');
    expect($reminder['title'])->toContain('রহিম ভাই')->toContain('30,000');

    // Paying the first instalment fills it, and the next reminder moves on.
    $book->move($debt, 'settle', 3000000, $this->cash->id, now()->toDateString());
    expect($debt->fresh()->balance())->toBe(3000000)
        ->and($debt->installments()->first()->left())->toBe(0)
        ->and(collect(app(HerdCare::class)->reminders())->firstWhere('kind', 'debt_pay'))->toBeNull();

    // The forecast pays the second instalment in its month.
    $parts = collect(app(CashForecast::class)->months(3)['months'])->flatMap(fn ($m) => $m['parts'])->pluck('label')->implode(' | ');
    expect($parts)->toContain('রহিম ভাই');

    // Over-paying is refused; paying the rest closes it.
    expect(fn () => $book->move($debt->fresh(), 'settle', 4000000, $this->cash->id, now()->toDateString()))->toThrow(ValidationException::class);
    $book->move($debt->fresh(), 'settle', 3000000, $this->cash->id, now()->toDateString());
    expect($debt->fresh()->closed_on)->not->toBeNull();
});

it('keeps money given out (a deposit) apart from cash in hand until it comes back', function () {
    $this->post('/debts', [
        'direction' => 'out', 'person_name' => 'মামাতো ভাই', 'purpose' => 'জমির জামানত', 'date' => now()->subDays(40)->toDateString(),
        'due_on' => now()->subDay()->toDateString(), 'amount' => '100000', 'money_account_id' => $this->cash->id,
    ])->assertRedirect();

    $debt = Debt::query()->firstOrFail();
    expect($this->cash->balance())->toBe(-10000000)->and($debt->balance())->toBe(10000000);

    $due = collect(app(HerdCare::class)->reminders())->firstWhere('kind', 'debt_collect');
    expect($due['urgent'])->toBeTrue();

    $this->post("/debts/{$debt->id}/move", ['kind' => 'settle', 'amount' => '40000', 'money_account_id' => $this->cash->id, 'date' => now()->toDateString()])->assertRedirect();
    expect($debt->fresh()->balance())->toBe(6000000);
});
