<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Worker;
use App\Models\WorkLog;
use App\Support\Lookups;
use App\Support\Tr;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The labour register (মজুরির খাতা): days worked are written as they happen, and when a
 * worker is paid, everything owed goes into the books as one wage entry, split by project.
 */
class LabourBook
{
    /** শ্রমিক মজুরি */
    public const WAGE_ACCOUNT = '5300';

    public function __construct(private Ledger $ledger) {}

    /**
     * Write one day's work for several workers at once.
     *
     * @param  array{date:string, cost_center_id:int|string, crop_season_id?:int|string|null, work_type:string, notes?:string|null}  $common
     * @param  list<array{worker_id:int|string, days:float|string, rate:int}>  $rows  rate in poisha per day
     * @return list<WorkLog>
     */
    public function log(array $common, array $rows): array
    {
        return DB::transaction(function () use ($common, $rows) {
            $logs = [];

            foreach ($rows as $row) {
                $days = round((float) $row['days'], 2);
                $rate = (int) $row['rate'];
                $logs[] = WorkLog::query()->create([
                    'date' => $common['date'],
                    'worker_id' => (int) $row['worker_id'],
                    'cost_center_id' => (int) $common['cost_center_id'],
                    'crop_season_id' => ($common['crop_season_id'] ?? null) ?: null,
                    'work_type' => $common['work_type'],
                    'days' => $days,
                    'rate' => $rate,
                    'amount' => (int) round($days * $rate),
                    'notes' => $common['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);
            }

            return $logs;
        });
    }

    /**
     * Pay a worker everything owed (or just the chosen logs): one expense entry, a 5300 debit
     * per project, the money account credited, and the logs marked paid.
     *
     * @param  list<int>|null  $logIds
     */
    public function pay(Worker $worker, string $date, int $moneyAccountId, ?array $logIds = null): JournalEntry
    {
        $isMoney = collect(Lookups::moneyAccounts())->contains(fn (Account $a) => $a->id === $moneyAccountId);

        if (! $isMoney) {
            throw ValidationException::withMessages(['money_account_id' => Tr::get('টাকা কোথা থেকে দিলেন, সেই হিসাবটি বাছুন।')]);
        }

        return DB::transaction(function () use ($worker, $date, $moneyAccountId, $logIds) {
            $logs = $worker->logs()->unpaid()
                ->when($logIds !== null, fn ($q) => $q->whereIn('id', $logIds ?? []))
                ->whereDate('date', '<=', $date)
                ->lockForUpdate()
                ->get();

            $total = (int) $logs->sum('amount');

            if ($logs->isEmpty() || $total <= 0) {
                throw ValidationException::withMessages(['log_ids' => Tr::get(':name-এর কোনো বাকি মজুরি নেই।', ['name' => $worker->name])]);
            }

            $wage = Account::byCode(self::WAGE_ACCOUNT)->id;
            $lines = [];

            foreach ($logs->groupBy('cost_center_id') as $centerId => $group) {
                $amount = (int) $group->sum('amount');

                if ($amount > 0) {
                    $lines[] = [
                        'account_id' => $wage,
                        'debit' => $amount,
                        'cost_center_id' => (int) $centerId,
                        'memo' => "{$worker->name}: ".$this->daysText((float) $group->sum('days')).' দিন',
                    ];
                }
            }

            $lines[] = ['account_id' => $moneyAccountId, 'credit' => $total];
            $days = $this->daysText((float) $logs->sum('days'));
            $from = $logs->min('date');
            $to = $logs->max('date');
            $span = $from && $to && ! $from->equalTo($to) ? " ({$from->toDateString()} – {$to->toDateString()})" : '';

            $entry = $this->ledger->post('expense', $date, "মজুরি: {$worker->name}, {$days} দিন{$span}", $lines, [
                'reference' => "worker:{$worker->id}",
            ]);

            WorkLog::query()->whereIn('id', $logs->pluck('id'))->update(['paid_entry_id' => $entry->id]);

            return $entry;
        });
    }

    /**
     * A worker as the page lists them, with what is owed.
     *
     * @return array<string, mixed>
     */
    public function workerRow(Worker $worker): array
    {
        $unpaid = $worker->logs()->unpaid()->with('costCenter:id,name')->orderBy('date')->orderBy('id')->get();

        return [
            'id' => $worker->id,
            'name' => $worker->name,
            'phone' => $worker->phone,
            'kind' => $worker->kind,
            'kind_label' => Tr::get(Worker::KINDS[$worker->kind] ?? $worker->kind),
            'daily_rate' => $worker->daily_rate,
            'monthly_salary' => $worker->monthly_salary,
            'day_rate' => $worker->dayRate(),
            'cost_center_id' => $worker->cost_center_id,
            'center' => $worker->costCenter?->name,
            'is_active' => $worker->is_active,
            'notes' => $worker->notes,
            'unpaid_days' => round((float) $unpaid->sum('days'), 2),
            'owed' => (int) $unpaid->sum('amount'),
            'unpaid' => $unpaid->map(fn (WorkLog $l) => [
                'id' => $l->id,
                'date' => $l->date->toDateString(),
                'work_type' => $l->work_type,
                'center' => $l->costCenter->name,
                'days' => $l->days,
                'amount' => $l->amount,
            ])->values(),
        ];
    }

    /**
     * Recent logs, newest day first, grouped by day.
     *
     * @return list<array{date:string, total:int, logs:list<array<string, mixed>>}>
     */
    public function recent(int $days = 30): array
    {
        $logs = WorkLog::query()
            ->with(['worker:id,name', 'costCenter:id,name', 'season:id,name', 'paidEntry:id,number,reversed_by_id'])
            ->whereDate('date', '>=', now()->subDays($days)->toDateString())
            ->orderByDesc('date')->orderByDesc('id')
            ->limit(300)
            ->get();

        return array_values($logs->groupBy(fn (WorkLog $l) => $l->date->toDateString())
            ->map(fn (Collection $group, string $date) => [
                'date' => $date,
                'total' => (int) $group->sum('amount'),
                'logs' => array_values($group->map(fn (WorkLog $l) => [
                    'id' => $l->id,
                    'worker' => $l->worker->name,
                    'worker_id' => $l->worker_id,
                    'center' => $l->costCenter->name,
                    'season' => $l->season?->name,
                    'work_type' => $l->work_type,
                    'days' => $l->days,
                    'rate' => $l->rate,
                    'amount' => $l->amount,
                    'notes' => $l->notes,
                    'paid' => $l->isPaid(),
                    'paid_number' => $l->isPaid() ? $l->paidEntry?->number : null,
                    'deletable' => ! $l->isPaid() && (bool) $l->created_at?->greaterThan(now()->subDay()),
                ])->all()),
            ])->all());
    }

    /**
     * The month's labour cost (all logged work, paid or not) by project and by kind of work.
     *
     * @return array{month:string, total:int, days:float, by_center:list<array{name:string, amount:int, days:float}>, by_type:list<array{name:string, amount:int, days:float}>}
     */
    public function monthSummary(string $month): array
    {
        $start = Date::parse("{$month}-01")->startOfMonth();
        $logs = WorkLog::query()->with('costCenter:id,name')
            ->whereBetween('date', [$start->toDateString(), $start->endOfMonth()->toDateString()])
            ->get();

        return [
            'month' => $start->format('Y-m'),
            'total' => (int) $logs->sum('amount'),
            'days' => round((float) $logs->sum('days'), 2),
            'by_center' => $this->sumBy($logs, fn (WorkLog $l): string => $l->costCenter->name),
            'by_type' => $this->sumBy($logs, fn (WorkLog $l): string => $l->work_type),
        ];
    }

    /**
     * @param  iterable<int, WorkLog>  $logs
     * @param  Closure(WorkLog): string  $key
     * @return list<array{name:string, amount:int, days:float}>
     */
    private function sumBy(iterable $logs, Closure $key): array
    {
        $groups = [];

        foreach ($logs as $log) {
            $name = $key($log);
            $groups[$name] ??= ['name' => $name, 'amount' => 0, 'days' => 0.0];
            $groups[$name]['amount'] += $log->amount;
            $groups[$name]['days'] = round($groups[$name]['days'] + $log->days, 2);
        }

        usort($groups, fn (array $a, array $b) => $b['amount'] <=> $a['amount']);

        return $groups;
    }

    private function daysText(float $days): string
    {
        return rtrim(rtrim(number_format($days, 2, '.', ''), '0'), '.');
    }
}
