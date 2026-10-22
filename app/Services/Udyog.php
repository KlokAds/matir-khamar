<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Setting;
use App\Support\Tr;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The government Udyog programme: an application, not money in hand. Until it is approved its
 * budget heads are only a plan — no form offers them and nothing is spent "from" them. On
 * approval the loan becomes a debt to repay over the years, with its instalments, and the
 * heads open for tagging spending. A grant may follow on a clean report, but is never assumed.
 */
class Udyog
{
    public const DEFAULTS = [
        'status' => 'applied',
        'loan' => 1000000_00,
        'rate' => '5',
        'years' => '5',
        'grant' => 1000000_00,
    ];

    public const STATUSES = ['applied' => 'আবেদন করা হয়েছে', 'approved' => 'অনুমোদন হয়েছে', 'declined' => 'হয়নি / বাদ'];

    public static function approved(): bool
    {
        return self::get('status') === 'approved';
    }

    public static function get(string $key): string
    {
        return (string) Setting::get("udyog.{$key}", (string) (self::DEFAULTS[$key] ?? ''));
    }

    /** @return array{status:string, loan:int, rate:float, years:int, grant:int, applied_on:?string, approved_on:?string, debt_id:?int, notes:string} */
    public function terms(): array
    {
        return [
            'status' => self::get('status'),
            'loan' => (int) self::get('loan'),
            'rate' => (float) self::get('rate'),
            'years' => max(1, (int) self::get('years')),
            'grant' => (int) self::get('grant'),
            'applied_on' => self::get('applied_on') ?: null,
            'approved_on' => self::get('approved_on') ?: null,
            'debt_id' => self::get('debt_id') !== '' ? (int) self::get('debt_id') : null,
            'notes' => self::get('notes'),
        ];
    }

    /**
     * Yearly repayment: an equal share of the loan each year, and interest on what is still
     * owed at the start of that year. A rough guide; the bank's own schedule is what counts.
     *
     * @return list<array{year:int, due_on:?string, principal:int, interest:int, total:int, left:int}>
     */
    public function schedule(int $loan, float $rate, int $years, ?string $from = null): array
    {
        $rows = [];
        $left = $loan;
        $share = intdiv($loan, $years);

        for ($year = 1; $year <= $years; $year++) {
            $principal = $year === $years ? $left : $share;
            $interest = (int) round($left * $rate / 100);
            $left -= $principal;
            $rows[] = [
                'year' => $year,
                'due_on' => $from ? CarbonImmutable::parse($from)->addYears($year)->toDateString() : null,
                'principal' => $principal,
                'interest' => $interest,
                'total' => $principal + $interest,
                'left' => $left,
            ];
        }

        return $rows;
    }

    /** @param  array{loan:int, rate:float, years:int, grant:int, applied_on:?string, notes:?string, status:string}  $data */
    public function saveTerms(array $data): void
    {
        if (self::approved() && $data['status'] !== 'approved') {
            throw ValidationException::withMessages(['status' => Tr::get('অনুমোদনের পর ঋণের হিসাব খোলা হয়েছে — এখান থেকে ফেরানো যায় না।')]);
        }

        foreach (['loan', 'rate', 'years', 'grant', 'applied_on', 'notes'] as $key) {
            Setting::put("udyog.{$key}", $data[$key] === null ? null : (string) $data[$key]);
        }

        if (! self::approved()) {
            Setting::put('udyog.status', $data['status'] === 'approved' ? 'applied' : $data['status']);
        }
    }

    /** The loan arrives: open the debt with its yearly instalments and open the budget heads. */
    public function approve(string $date, int $amount, int $moneyAccountId): Debt
    {
        if (self::approved()) {
            throw ValidationException::withMessages(['date' => Tr::get('উদ্যোগ ঋণ আগেই অনুমোদন হিসেবে লেখা আছে।')]);
        }

        return DB::transaction(function () use ($date, $amount, $moneyAccountId) {
            $terms = $this->terms();
            $plan = array_map(
                fn (array $row) => ['due_on' => (string) $row['due_on'], 'amount' => $row['principal']],
                $this->schedule($amount, $terms['rate'], $terms['years'], $date),
            );

            $debt = app(DebtBook::class)->open([
                'direction' => 'in',
                'person_name' => 'উদ্যোগ ঋণ (সরকারি কর্মসূচি)',
                'purpose' => Tr::get(':rate% সুদ, :years বছরে শোধ', ['rate' => $terms['rate'], 'years' => $terms['years']]),
                'date' => $date,
                'notes' => Tr::get('প্রতি কিস্তিতে আসল; সুদ আলাদা খরচ হিসেবে "ঋণের সুদ" খাতে লিখুন।'),
            ], $amount, $moneyAccountId, $plan);

            Setting::put('udyog.status', 'approved');
            Setting::put('udyog.approved_on', $date);
            Setting::put('udyog.loan', (string) $amount);
            Setting::put('udyog.debt_id', (string) $debt->id);

            return $debt;
        });
    }
}
