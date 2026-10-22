<?php

use App\Enums\Role;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Party;
use App\Models\Plot;
use App\Models\User;
use App\Services\CashForecast;
use App\Services\DuesBook;
use App\Services\Ledger;
use Database\Seeders\KhamarSetupSeeder;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->actingAs(User::factory()->create(['role' => Role::Admin]));
    $this->travelTo('2026-10-15 09:00:00');

    $ledger = app(Ledger::class);
    $cash = Account::byCode('1100')->id;
    $center = CostCenter::query()->where('code', 'DHAN')->value('id');
    $this->center = $center;

    // 1,00,000 taka in hand before the window.
    $ledger->post('opening', '2026-06-30', 'প্রারম্ভিক', [
        ['account_id' => $cash, 'debit' => 10000000],
        ['account_id' => Account::byCode('3900')->id, 'credit' => 10000000],
    ]);

    // July to September: 30,000 milk in and 18,000 feed out every month.
    foreach (['2026-07-10', '2026-08-10', '2026-09-10'] as $date) {
        $ledger->post('income', $date, 'দুধ বিক্রি', [
            ['account_id' => Account::byCode('4100')->id, 'credit' => 3000000, 'cost_center_id' => $center],
            ['account_id' => $cash, 'debit' => 3000000],
        ]);
        $ledger->post('expense', $date, 'দানাদার', [
            ['account_id' => Account::byCode('5110')->id, 'debit' => 1800000, 'cost_center_id' => $center],
            ['account_id' => $cash, 'credit' => 1800000],
        ]);
    }

    // A one-off cow sale of 50,000 stays out of the average.
    $ledger->post('income', '2026-08-20', 'গরু বিক্রি', [
        ['account_id' => Account::byCode('4200')->id, 'credit' => 5000000, 'cost_center_id' => $center],
        ['account_id' => $cash, 'debit' => 5000000],
    ]);

    // 9,000 sold on credit to রহিম on 1 September, still unpaid.
    $this->customer = Party::query()->create(['type' => 'customer', 'name' => 'রহিম', 'phone' => '01711000000']);
    $ledger->post('income', '2026-09-01', 'বাকি বিক্রি', [
        ['account_id' => Account::byCode('4100')->id, 'credit' => 900000, 'cost_center_id' => $center],
        ['account_id' => Account::byCode('1200')->id, 'debit' => 900000],
    ], ['party_id' => $this->customer->id]);
});

it('forecasts the coming months from the last three months, with a lease renewal as outflow', function () {
    Plot::query()->create(['name' => 'উত্তরের জমি', 'cost_center_id' => $this->center, 'area_decimals' => 66, 'tenure' => 'lease', 'owner_name' => 'করিম',
        'lease_from' => '2025-11-21', 'lease_to' => '2026-11-20', 'yearly_rent' => 3000000, 'is_active' => true]);

    $f = app(CashForecast::class)->months(3);

    // 1,00,000 + 3×30,000 − 3×18,000 + 50,000 sale.
    expect($f['start_cash'])->toBe(18600000)
        ->and($f['history']['months'])->toBe(3)
        ->and($f['avg_income'])->toBe(3300000)
        ->and($f['avg_expense'])->toBe(1800000)
        ->and($f['history']['animal_sales'])->toBe(5000000)
        ->and(array_column($f['months'], 'month'))->toBe(['2026-10', '2026-11', '2026-12', '2027-01']);

    // Rest of October: 16 of 31 days.
    [$oct, $nov, $dec, $jan] = $f['months'];
    expect($oct['partial'])->toBeTrue()
        ->and($oct['income'])->toBe(1703226)
        ->and($oct['expense'])->toBe(929032)
        ->and($oct['closing'])->toBe(19374194);

    $parts = collect($nov['parts'])->keyBy('key');
    expect($parts->keys()->all())->toContain('lease-'.Plot::query()->value('id'), 'receivables')
        ->and($parts['receivables']['amount'])->toBe(900000)
        ->and($nov['income'])->toBe(3300000 + 900000)
        ->and($nov['expense'])->toBe(1800000 + 3000000)
        ->and($nov['closing'])->toBe(18774194)
        ->and($dec['closing'])->toBe(20274194)
        ->and($jan['closing'])->toBe(21774194)
        ->and($jan['flag'])->toBe('ok');

    expect(CashForecast::flag(-1, 100))->toBe('danger')
        ->and(CashForecast::flag(50, 100))->toBe('thin')
        ->and(CashForecast::flag(500, 100))->toBe('ok');
});

it('lists a customer whose credit sale is over a month old and reminds about it', function () {
    $book = app(DuesBook::class)->book();

    expect($book['total_receivable'])->toBe(900000)
        ->and($book['payables'])->toBe([])
        ->and($book['receivables'][0])->toMatchArray([
            'party_id' => $this->customer->id,
            'name' => 'রহিম',
            'phone' => '01711000000',
            'balance' => 900000,
            'oldest_on' => '2026-09-01',
            'age_days' => 44,
        ]);

    $reminders = app(DuesBook::class)->reminders();
    expect($reminders)->toHaveCount(1)
        ->and($reminders[0]['kind'])->toBe('due_in')
        ->and($reminders[0]['title'])->toContain('রহিম')
        ->and($reminders[0]['href'])->toBe('/cash?tab=dues')
        ->and($reminders[0]['urgent'])->toBeFalse();

    // A part payment clears the oldest due first; paying the rest clears the reminder.
    app(Ledger::class)->post('receipt', '2026-10-14', 'আদায়', [
        ['account_id' => Account::byCode('1100')->id, 'debit' => 900000],
        ['account_id' => Account::byCode('1200')->id, 'credit' => 900000],
    ], ['party_id' => $this->customer->id]);

    expect(app(DuesBook::class)->book()['receivables'])->toBe([])
        ->and(app(DuesBook::class)->reminders())->toBe([]);
});
