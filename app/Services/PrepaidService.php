<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\PeriodLock;
use App\Models\PrepaidSchedule;
use App\Support\BanglaDate;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * Land lease is paid for a year (or a season) at once but belongs to every
 * month it covers. The payment waits as an advance and moves into the land's
 * category one month at a time.
 */
class PrepaidService
{
    public function __construct(private Ledger $ledger) {}

    /** @param  list<UploadedFile>  $attachments */
    public function payLease(string $date, string $description, int $amount, int $months, string $startMonth, int $costCenterId, int $paidFromAccountId, ?int $partyId, ?int $budgetHeadId, array $attachments = []): JournalEntry
    {
        return DB::transaction(function () use ($date, $description, $amount, $months, $startMonth, $costCenterId, $paidFromAccountId, $partyId, $budgetHeadId, $attachments) {
            $prepaid = Account::byCode(AccountCodes::PREPAID_LEASE);

            $entry = $this->ledger->post('lease', $date, $description, [
                ['account_id' => $prepaid->id, 'debit' => $amount, 'cost_center_id' => $costCenterId, 'budget_head_id' => $budgetHeadId],
                ['account_id' => $paidFromAccountId, 'credit' => $amount],
            ], ['party_id' => $partyId, 'attachments' => $attachments]);

            PrepaidSchedule::query()->create([
                'journal_entry_id' => $entry->id,
                'prepaid_account_id' => $prepaid->id,
                'expense_account_id' => Account::byCode(AccountCodes::LEASE_EXPENSE)->id,
                'cost_center_id' => $costCenterId,
                'description' => $description,
                'total' => $amount,
                'months' => $months,
                'start_month' => Date::parse($startMonth)->startOfMonth(),
            ]);

            $this->amortizeDue();

            return $entry;
        });
    }

    /**
     * Posts every monthly share whose month has started. Safe to run any
     * number of times: each schedule remembers how many months it has done.
     * Months that are already locked are skipped and stay pending.
     */
    public function amortizeDue(?CarbonInterface $asOf = null): int
    {
        $asOf ??= now();
        $posted = 0;

        $schedules = PrepaidSchedule::query()->whereColumn('amortized_months', '<', 'months')->get();

        foreach ($schedules as $schedule) {
            while ($schedule->amortized_months < $schedule->months) {
                $month = $schedule->start_month->copy()->addMonthsNoOverflow($schedule->amortized_months);

                if ($month->greaterThan($asOf->copy()->startOfMonth())) {
                    break;
                }

                if (PeriodLock::isLocked($month)) {
                    break;
                }

                DB::transaction(function () use ($schedule, $month) {
                    $amount = $schedule->amountForMonth($schedule->amortized_months);

                    $this->ledger->post('lease_amortization', $month->copy()->endOfMonth()->min(now())->toDateString(), "{$schedule->description} — ".BanglaDate::month($month), [
                        ['account_id' => $schedule->expense_account_id, 'debit' => $amount, 'cost_center_id' => $schedule->cost_center_id],
                        ['account_id' => $schedule->prepaid_account_id, 'credit' => $amount, 'cost_center_id' => $schedule->cost_center_id],
                    ], ['reference' => 'lease-'.$schedule->id.'-'.($schedule->amortized_months + 1)]);

                    $schedule->increment('amortized_months');
                });

                $posted++;
            }
        }

        return $posted;
    }
}
