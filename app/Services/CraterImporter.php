<?php

namespace App\Services;

use App\Models\Account;
use App\Models\ImportedRecord;
use App\Models\ImportMapping;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Brings farm rows from billing.klokads.com (Crater) into the books.
 *
 * 1. fetch(): copies expenses and payments into imported_records exactly as
 *    they are (raw JSON + checksum). Crater keeps money in cents, which is
 *    already poisha, so no amount is ever converted or rounded.
 * 2. Every Crater category, payment method and customer gets a mapping row
 *    the owner fills in (which account, which farm category), or marks as
 *    "not farm".
 * 3. post(): posts mapped rows oldest first (date, then Crater id), one
 *    voucher per Crater row, referenced "crater:<table>:<id>" so the same row
 *    can never be posted twice.
 * 4. reconcile(): compares Crater totals with what the books hold for them.
 */
class CraterImporter
{
    public const SOURCE = 'crater';

    public function __construct(private Ledger $ledger, private StockService $stock) {}

    /**
     * Marks every billing category, method and customer nobody has decided
     * on as "not farm" — the agency's own accounts, for example.
     */
    public function ignoreUndecided(): int
    {
        $count = 0;

        foreach (ImportMapping::query()->where('source', self::SOURCE)->get() as $mapping) {
            // Payment methods are shared by the agency and the farm, so they are never switched off.
            if ($mapping->kind !== 'payment_method' && $mapping->isUndecided()) {
                $mapping->update(['ignore' => true]);
                $count++;
            }
        }

        return $count;
    }

    public function configured(): bool
    {
        return config('database.connections.crater.database') !== '' && config('database.connections.crater.username') !== '';
    }

    public function connection(): ConnectionInterface
    {
        return DB::connection('crater');
    }

    /**
     * @return array{new:int, unchanged:int, changed:int}
     */
    public function fetch(): array
    {
        $db = $this->connection();
        $company = config('services.crater.company_id');
        $counts = ['new' => 0, 'unchanged' => 0, 'changed' => 0];

        $this->syncMappings($db, $company);

        $expenses = $db->table('expenses')
            ->when($company, fn ($query) => $query->where('company_id', $company))
            ->orderBy('expense_date')->orderBy('id')
            ->get();

        foreach ($expenses as $row) {
            $this->store('expenses', $row, null, $row->expense_date, (int) $row->amount, $counts);
        }

        $payments = $db->table('payments')
            ->when($company, fn ($query) => $query->where('company_id', $company))
            ->orderBy('payment_date')->orderBy('id')
            ->get();

        foreach ($payments as $row) {
            $this->store('payments', $row, $row->payment_number ?? null, $row->payment_date, (int) $row->amount, $counts);
        }

        return $counts;
    }

    /**
     * Posts every pending row whose mappings are complete, oldest first.
     *
     * @return array{posted:int, waiting:int, ignored:int, failed:int}
     */
    public function post(): array
    {
        $result = ['posted' => 0, 'waiting' => 0, 'ignored' => 0, 'failed' => 0];
        $mappings = ImportMapping::query()->where('source', self::SOURCE)->get()->keyBy(fn ($map) => $map->kind.':'.$map->external_id);

        $records = ImportedRecord::query()
            ->where('source', self::SOURCE)
            ->where('status', 'pending')
            ->orderBy('external_date')
            ->orderByRaw('LENGTH(external_id), external_id')
            ->get();

        foreach ($records as $record) {
            $raw = $record->raw;
            $isExpense = $record->external_table === 'expenses';
            $main = $isExpense
                ? $mappings->get('expense_category:'.($raw['expense_category_id'] ?? ''))
                : $mappings->get('customer:'.($raw['customer_id'] ?? ''));
            $method = $mappings->get('payment_method:'.($raw['payment_method_id'] ?? 'none'));

            if ($main?->ignore) {
                $record->update(['status' => 'ignored', 'problem' => null]);
                $result['ignored']++;

                continue;
            }

            $moneyAccount = $method && ! $method->ignore ? $method->account_id : null;
            $toStock = $isExpense && $main?->item_id !== null;

            $problem = match (true) {
                ! $main || ! $main->isComplete() => 'ক্যাটাগরি ম্যাপ করা হয়নি',
                ! $moneyAccount => 'টাকার মাধ্যম ম্যাপ করা হয়নি',
                $toStock && ! $record->qty => 'স্টকে নিতে পরিমাণ (কেজি ইত্যাদি) লিখুন',
                default => null,
            };

            // Rows go into the books strictly in billing order. One that is
            // not ready holds back everything after it, so the farm ledger
            // never shows a later bill before an earlier one.
            if ($problem !== null) {
                $record->update(['problem' => $problem]);
                $result['waiting'] = ImportedRecord::query()->where('source', self::SOURCE)->where('status', 'pending')->count();

                break;
            }

            try {
                DB::transaction(function () use ($record, $raw, $isExpense, $main, $moneyAccount, $toStock) {
                    $amount = $record->amount;
                    $note = trim(strip_tags((string) ($raw['notes'] ?? '')));
                    $label = $isExpense ? 'বিলিং খরচ #'.$record->external_id : 'বিলিং পেমেন্ট '.($record->external_number ?? '#'.$record->external_id);
                    $reference = self::SOURCE.':'.$record->external_table.':'.$record->external_id;

                    if ($toStock) {
                        $entry = $this->stock->purchase($main->item, $record->external_date->toDateString(), $record->qty, $amount, [
                            'credit_account_id' => $moneyAccount,
                            'description' => $label.' — '.$main->item->name.($note !== '' ? ' — '.mb_substr($note, 0, 120) : ''),
                            'reference' => $reference,
                        ]);
                        $record->update(['status' => 'posted', 'problem' => null, 'journal_entry_id' => $entry->id, 'posted_at' => now()]);

                        return;
                    }

                    $lines = $isExpense
                        ? [
                            ['account_id' => $main->account_id, 'debit' => $amount, 'cost_center_id' => $main->cost_center_id, 'memo' => $main->external_name],
                            ['account_id' => $moneyAccount, 'credit' => $amount],
                        ]
                        : [
                            ['account_id' => $moneyAccount, 'debit' => $amount],
                            ['account_id' => $main->account_id, 'credit' => $amount, 'cost_center_id' => $main->cost_center_id, 'memo' => $main->external_name],
                        ];

                    $entry = $this->ledger->post(
                        $isExpense ? 'expense' : 'income',
                        $record->external_date->toDateString(),
                        $label.($note !== '' ? ' — '.mb_substr($note, 0, 150) : ''),
                        $lines,
                        ['reference' => $reference],
                    );

                    $record->update(['status' => 'posted', 'problem' => null, 'journal_entry_id' => $entry->id, 'posted_at' => now()]);
                });
                $result['posted']++;
            } catch (Throwable $exception) {
                $record->update(['problem' => mb_substr($exception->getMessage(), 0, 250)]);
                $result['failed']++;

                break;
            }
        }

        return $result;
    }

    /**
     * Crater total vs books total, per table and month, for posted rows.
     * Any difference means a row was changed on the billing side after it
     * was posted, and is listed for the owner to look at.
     *
     * @return list<array{table:string, month:string, rows:int, crater:int, books:int, difference:int}>
     */
    public function reconcile(): array
    {
        $records = ImportedRecord::query()->where('source', self::SOURCE)->whereIn('status', ['posted', 'changed'])->with('entry.lines')->get();
        $live = $this->liveAmounts($records);
        $groups = [];

        foreach ($records as $record) {
            $key = $record->external_table.'|'.$record->external_date->format('Y-m');
            $groups[$key] ??= ['table' => $record->external_table, 'month' => $record->external_date->format('Y-m'), 'rows' => 0, 'crater' => 0, 'books' => 0];
            $groups[$key]['rows']++;
            $groups[$key]['crater'] += $live[$record->external_table][$record->external_id] ?? (int) ($record->raw['amount'] ?? 0);

            $reversed = $record->entry?->reversed_by_id !== null;
            $groups[$key]['books'] += $reversed ? 0 : (int) $record->entry?->lines->sum('debit');
        }

        ksort($groups);

        return array_values(array_map(fn ($group) => [...$group, 'difference' => $group['crater'] - $group['books']], $groups));
    }

    /**
     * Today's amounts on the billing side for the given rows, so a change
     * made in Crater after posting shows up as a difference. Falls back to
     * the stored copy when billing cannot be reached.
     *
     * @param  iterable<ImportedRecord>  $records
     * @return array<string, array<string, int>>
     */
    private function liveAmounts(iterable $records): array
    {
        if (! $this->configured()) {
            return [];
        }

        $ids = [];

        foreach ($records as $record) {
            $ids[$record->external_table][] = $record->external_id;
        }

        $live = [];

        try {
            foreach ($ids as $table => $tableIds) {
                foreach (array_chunk($tableIds, 500) as $chunk) {
                    foreach ($this->connection()->table($table)->whereIn('id', $chunk)->get(['id', 'amount']) as $row) {
                        $live[$table][(string) $row->id] = (int) $row->amount;
                    }
                }

                // A row deleted in billing counts as zero there.
                foreach ($tableIds as $id) {
                    $live[$table][$id] ??= 0;
                }
            }
        } catch (Throwable) {
            return [];
        }

        return $live;
    }

    /** @param  array{new:int, unchanged:int, changed:int}  $counts */
    private function store(string $table, object $row, ?string $number, string $date, int $amount, array &$counts): void
    {
        $raw = (array) $row;
        $checksum = hash('sha256', json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
        $existing = ImportedRecord::query()->where('source', self::SOURCE)->where('external_table', $table)->where('external_id', (string) $row->id)->first();

        if (! $existing) {
            ImportedRecord::query()->create([
                'source' => self::SOURCE,
                'external_table' => $table,
                'external_id' => (string) $row->id,
                'external_number' => $number,
                'external_date' => Date::parse($date)->toDateString(),
                'amount' => $amount,
                'checksum' => $checksum,
                'raw' => $raw,
            ]);
            $counts['new']++;

            return;
        }

        if ($existing->checksum === $checksum) {
            $counts['unchanged']++;

            return;
        }

        // A row that is not posted yet simply takes the new values. A posted
        // one is never rewritten: it is flagged so the owner can reverse and redo it.
        if ($existing->status !== 'posted') {
            $existing->update(['external_date' => Date::parse($date)->toDateString(), 'amount' => $amount, 'checksum' => $checksum, 'raw' => $raw, 'external_number' => $number]);
        } else {
            $existing->update(['status' => 'changed', 'problem' => 'বিলিংয়ে এই সারি পোস্ট হওয়ার পরে বদলেছে। নতুন মান: '.number_format($amount / 100, 2)]);
        }

        $counts['changed']++;
    }

    private function syncMappings(ConnectionInterface $db, mixed $company): void
    {
        $lists = [
            'expense_category' => $db->table('expense_categories')->when($company, fn ($query) => $query->where('company_id', $company))->get(['id', 'name']),
            'payment_method' => $db->table('payment_methods')->when($company, fn ($query) => $query->where('company_id', $company))->get(['id', 'name']),
            'customer' => $db->table('customers')->when($company, fn ($query) => $query->where('company_id', $company))->get(['id', 'name']),
        ];

        // Billing rows saved without a payment method still need a money account here.
        $lists['payment_method']->push((object) ['id' => 'none', 'name' => '(মাধ্যম দেওয়া নেই)']);

        foreach ($lists as $kind => $rows) {
            foreach ($rows as $row) {
                ImportMapping::query()->updateOrCreate(
                    ['source' => self::SOURCE, 'kind' => $kind, 'external_id' => (string) $row->id],
                    ['external_name' => (string) $row->name],
                );
            }
        }
    }

    /** Account choices a mapping of this kind may point at. */
    public static function accountsFor(string $kind): array
    {
        return match ($kind) {
            'expense_category' => Account::query()->where('type', 'expense')->whereNotNull('parent_id')->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name'])->all(),
            'customer' => Account::query()->where('type', 'income')->whereNotNull('parent_id')->where('is_active', true)->where(fn ($q) => $q->whereNull('subtype')->orWhere('subtype', '!=', Account::INTERNAL_SUBTYPE))->orderBy('code')->get(['id', 'code', 'name'])->all(),
            default => Account::query()->money()->orderBy('code')->get(['id', 'code', 'name'])->all(),
        };
    }
}
