<?php

use App\Enums\Role;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkLog;
use App\Services\LabourBook;
use App\Services\Ledger;
use Database\Seeders\KhamarSetupSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->actingAs(User::factory()->create(['role' => Role::Admin]));
    $this->dhan = CostCenter::query()->where('code', 'DHAN')->firstOrFail();
    $this->gavi = CostCenter::query()->where('code', 'GAVI')->firstOrFail();
    $this->cash = Account::byCode('1100');
    $this->book = app(LabourBook::class);
});

it('pays two days across two projects in one balanced entry', function () {
    $worker = Worker::query()->create(['name' => 'রহিম', 'kind' => 'daily', 'daily_rate' => 50000]);

    $this->book->log(['date' => now()->subDays(2)->toDateString(), 'cost_center_id' => $this->dhan->id, 'work_type' => 'রোপণ'], [
        ['worker_id' => $worker->id, 'days' => 1, 'rate' => $worker->dayRate()],
    ]);
    $this->book->log(['date' => now()->subDay()->toDateString(), 'cost_center_id' => $this->gavi->id, 'work_type' => 'ঘাস কাটা'], [
        ['worker_id' => $worker->id, 'days' => 0.5, 'rate' => $worker->dayRate()],
    ]);

    $row = $this->book->workerRow($worker);
    expect($row['unpaid_days'])->toEqual(1.5)->and($row['owed'])->toBe(75000);

    $entry = $this->book->pay($worker, now()->toDateString(), $this->cash->id);
    $entry->load('lines');

    $wage = Account::byCode('5300')->id;
    $wageLines = $entry->lines->where('account_id', $wage);

    expect($entry->type)->toBe('expense')
        ->and($entry->lines)->toHaveCount(3)
        ->and((int) $entry->lines->sum('debit'))->toBe((int) $entry->lines->sum('credit'))
        ->and($wageLines)->toHaveCount(2)
        ->and((int) $wageLines->firstWhere('cost_center_id', $this->dhan->id)->debit)->toBe(50000)
        ->and((int) $wageLines->firstWhere('cost_center_id', $this->gavi->id)->debit)->toBe(25000)
        ->and((int) $entry->lines->firstWhere('account_id', $this->cash->id)->credit)->toBe(75000)
        ->and(WorkLog::query()->where('paid_entry_id', $entry->id)->count())->toBe(2)
        ->and($this->book->workerRow($worker->fresh())['owed'])->toBe(0);

    expect(fn () => $this->book->pay($worker, now()->toDateString(), $this->cash->id))->toThrow(ValidationException::class);
});

it('pays only the chosen logs, and owes again when a payment is reversed', function () {
    $worker = Worker::query()->create(['name' => 'করিম', 'kind' => 'monthly', 'monthly_salary' => 900000]);
    expect($worker->dayRate())->toBe(30000);

    [$a, $b] = $this->book->log(['date' => now()->toDateString(), 'cost_center_id' => $this->gavi->id, 'work_type' => 'গোয়াল ও গরু দেখা'], [
        ['worker_id' => $worker->id, 'days' => 1, 'rate' => $worker->dayRate()],
        ['worker_id' => Worker::query()->create(['name' => 'জব্বার', 'kind' => 'daily', 'daily_rate' => 40000])->id, 'days' => 1, 'rate' => 40000],
    ]);

    $entry = $this->book->pay($worker, now()->toDateString(), $this->cash->id, [$a->id, $b->id]);
    expect((int) $entry->lines()->sum('credit'))->toBe(30000)
        ->and($b->fresh()->paid_entry_id)->toBeNull();

    app(Ledger::class)->reverse($entry, now()->toDateString(), 'ভুল');
    expect($this->book->workerRow($worker->fresh())['owed'])->toBe(30000);
});

it('refuses a non-money account', function () {
    $worker = Worker::query()->create(['name' => 'রহিম', 'kind' => 'daily', 'daily_rate' => 50000]);
    $this->book->log(['date' => now()->toDateString(), 'cost_center_id' => $this->dhan->id, 'work_type' => 'চাষ'], [
        ['worker_id' => $worker->id, 'days' => 1, 'rate' => 50000],
    ]);

    expect(fn () => $this->book->pay($worker, now()->toDateString(), Account::byCode('5300')->id))->toThrow(ValidationException::class);
});

it('sums the month by project and by work type', function () {
    $worker = Worker::query()->create(['name' => 'রহিম', 'kind' => 'daily', 'daily_rate' => 50000]);
    $this->book->log(['date' => now()->toDateString(), 'cost_center_id' => $this->dhan->id, 'work_type' => 'চাষ'], [
        ['worker_id' => $worker->id, 'days' => 2, 'rate' => 50000],
    ]);

    $summary = $this->book->monthSummary(now()->format('Y-m'));

    expect($summary['total'])->toBe(100000)
        ->and($summary['by_center'][0]['name'])->toBe($this->dhan->name)
        ->and($summary['by_type'][0])->toMatchArray(['name' => 'চাষ', 'amount' => 100000])
        ->and($this->book->recent())->toHaveCount(1);
});
