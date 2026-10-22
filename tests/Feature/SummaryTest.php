<?php

use App\Enums\Role;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\User;
use App\Services\Ledger;
use App\Services\SummaryBook;
use Database\Seeders\KhamarSetupSeeder;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->actingAs(User::factory()->create(['role' => Role::Admin]));
});

it('sums up a period: result, money in and out, projects and where the farm stands', function () {
    $ledger = app(Ledger::class);
    $cash = Account::byCode('1100')->id;
    $gavi = CostCenter::query()->where('code', 'GAVI')->value('id');
    $ledger->post('investment', now()->toDateString(), 'মূলধন', [
        ['account_id' => $cash, 'debit' => 100000_00],
        ['account_id' => Account::byCode('3100')->id, 'credit' => 100000_00],
    ]);
    $ledger->post('expense', now()->toDateString(), 'খড়', [
        ['account_id' => Account::byCode('5100')->id, 'debit' => 3000_00, 'cost_center_id' => $gavi],
        ['account_id' => $cash, 'credit' => 3000_00],
    ]);
    $ledger->post('income', now()->toDateString(), 'দুধ', [
        ['account_id' => $cash, 'debit' => 5000_00],
        ['account_id' => Account::byCode('4100')->id, 'credit' => 5000_00, 'cost_center_id' => $gavi],
    ]);

    $book = app(SummaryBook::class);
    [$from, $to] = $book->range('month');
    $data = $book->build($from, $to);

    expect($data['result'])->toBe(['income' => 5000_00, 'expense' => 3000_00, 'profit' => 2000_00])
        ->and($data['flows']['in_total'])->toBe(105000_00)
        ->and($data['flows']['out_total'])->toBe(3000_00)
        ->and($data['position']['money'])->toBe(102000_00)
        ->and($data['position']['capital'])->toBe(100000_00)
        ->and($data['position']['earned'])->toBe(2000_00)
        ->and($data['position']['net'])->toBe(102000_00)
        ->and(collect($data['projects'])->firstWhere('income', 5000_00))->not->toBeNull();

    foreach (array_keys(SummaryBook::PERIODS) as $period) {
        $this->get("/summary?period={$period}")->assertOk();
    }
});
