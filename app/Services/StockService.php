<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\StockMovement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Feed and medicine go into stock when bought and leave it when used. The
 * cost that leaves is quantity x weighted average rate, so one bag of vushi
 * bought once can land in Bokna and Motataja in the exact share each ate.
 */
class StockService
{
    public function __construct(private Ledger $ledger) {}

    /**
     * @param  array{credit_account_id:int, party_id?:int|null, budget_head_id?:int|null, description?:string|null, reference?:string|null, attachments?:list<UploadedFile>}  $payment
     */
    public function purchase(Item $item, string $date, float $qty, int $amount, array $payment): JournalEntry
    {
        $qty = round($qty, 3);
        $description = $payment['description'] ?? null ?: "{$item->name} ক্রয় — ".$this->qtyLabel($item, $qty);

        return DB::transaction(function () use ($item, $date, $qty, $amount, $payment, $description) {
            $entry = $this->ledger->post('stock_purchase', $date, $description, [
                ['account_id' => $item->inventory_account_id, 'debit' => $amount, 'budget_head_id' => $payment['budget_head_id'] ?? null, 'memo' => $item->name],
                ['account_id' => $payment['credit_account_id'], 'credit' => $amount],
            ], [
                'party_id' => $payment['party_id'] ?? null,
                'reference' => $payment['reference'] ?? null,
                'attachments' => $payment['attachments'] ?? [],
            ]);

            $this->move($item, $entry, 'in', $qty, $amount, null, $date);

            return $entry;
        });
    }

    /**
     * Own production, e.g. grass cut from the lease land. It enters stock at
     * the rate the owner sets and is credited to the producing center as
     * internal income, so that center shows whether growing beats buying.
     */
    public function produce(Item $item, string $date, float $qty, int $ratePerUnit, int $producerCenterId, ?string $note): JournalEntry
    {
        $qty = round($qty, 3);
        $amount = (int) round($qty * $ratePerUnit);

        if ($amount <= 0) {
            throw ValidationException::withMessages(['rate' => 'পরিমাণ ও দর দিন।']);
        }

        return DB::transaction(function () use ($item, $date, $qty, $amount, $producerCenterId, $note) {
            $entry = $this->ledger->post('production', $date, $note ?: "{$item->name} উৎপাদন — ".$this->qtyLabel($item, $qty), [
                ['account_id' => $item->inventory_account_id, 'debit' => $amount, 'memo' => $item->name],
                ['account_id' => Account::byCode(AccountCodes::INTERNAL_INCOME)->id, 'credit' => $amount, 'cost_center_id' => $producerCenterId],
            ]);

            $this->move($item, $entry, 'in', $qty, $amount, $producerCenterId, $date);

            return $entry;
        });
    }

    /**
     * @param  list<array{cost_center_id:int, qty:float}>  $splits
     * @param  string|null  $session  morning, noon or evening (StockMovement::SESSIONS)
     */
    public function issue(Item $item, string $date, array $splits, ?string $note, ?string $session = null): JournalEntry
    {
        $splits = array_values(array_filter(
            array_map(fn ($split) => ['cost_center_id' => (int) $split['cost_center_id'], 'qty' => round((float) $split['qty'], 3)], $splits),
            fn ($split) => $split['qty'] > 0,
        ));

        if ($splits === []) {
            throw ValidationException::withMessages(['splits' => 'অন্তত একটি ক্যাটাগরিতে পরিমাণ দিন।']);
        }

        return DB::transaction(function () use ($item, $date, $splits, $note, $session) {
            $item = Item::query()->lockForUpdate()->findOrFail($item->id);
            $totalQty = round(array_sum(array_column($splits, 'qty')), 3);

            if ($totalQty > round($item->qty_on_hand, 3)) {
                throw ValidationException::withMessages([
                    'splits' => "স্টকে আছে {$this->qtyLabel($item, $item->qty_on_hand)}, কিন্তু চাওয়া হয়েছে {$this->qtyLabel($item, $totalQty)}।",
                ]);
            }

            $values = $this->splitValues($item, $splits, $totalQty);
            $lines = [];

            foreach ($splits as $index => $split) {
                $lines[] = [
                    'account_id' => $item->expense_account_id,
                    'cost_center_id' => $split['cost_center_id'],
                    'debit' => $values[$index],
                    'memo' => $item->name.' '.$this->qtyLabel($item, $split['qty']),
                ];
            }

            $lines[] = ['account_id' => $item->inventory_account_id, 'credit' => array_sum($values), 'memo' => $item->name];

            $when = $session !== null ? ' ('.(StockMovement::SESSIONS[$session] ?? $session).')' : '';
            $entry = $this->ledger->post('stock_issue', $date, ($note ?: "{$item->name} ব্যবহার — ".$this->qtyLabel($item, $totalQty)).$when, $lines);

            foreach ($splits as $index => $split) {
                $this->move($item, $entry, 'out', $split['qty'], $values[$index], $split['cost_center_id'], $date, $session);
            }

            return $entry;
        });
    }

    /**
     * Value of each split at the average rate. When the issue empties the
     * stock, the last split takes whatever value is left so nothing lingers.
     *
     * @param  list<array{cost_center_id:int, qty:float}>  $splits
     * @return list<int>
     */
    public function splitValues(Item $item, array $splits, float $totalQty): array
    {
        $rate = $item->averageCost();
        $emptiesStock = round($item->qty_on_hand - $totalQty, 3) <= 0;
        $values = [];

        foreach ($splits as $split) {
            $values[] = (int) round($split['qty'] * $rate);
        }

        if ($emptiesStock && $values !== []) {
            $values[array_key_last($values)] += $item->value_on_hand - array_sum($values);
        }

        return $values;
    }

    /** Puts back stock that a reversed entry had moved. */
    public function reverseMovements(JournalEntry $original, JournalEntry $reversal): void
    {
        $movements = StockMovement::query()->where('journal_entry_id', $original->id)->get();

        foreach ($movements as $movement) {
            $item = Item::query()->lockForUpdate()->findOrFail($movement->item_id);
            $opposite = $movement->direction === 'in' ? 'out' : 'in';

            if ($opposite === 'out' && round($item->qty_on_hand - $movement->qty, 3) < 0) {
                throw ValidationException::withMessages([
                    'entry' => "{$item->name} এর এই মাল ইতিমধ্যে ব্যবহার হয়েছে, তাই ক্রয়টি বাতিল করা যাবে না।",
                ]);
            }

            $this->move($item, $reversal, $opposite, $movement->qty, $movement->value, $movement->cost_center_id, $reversal->date->toDateString());
        }
    }

    private function move(Item $item, JournalEntry $entry, string $direction, float $qty, int $value, ?int $costCenterId, string $date, ?string $session = null): void
    {
        StockMovement::query()->create([
            'item_id' => $item->id,
            'journal_entry_id' => $entry->id,
            'cost_center_id' => $costCenterId,
            'date' => $date,
            'direction' => $direction,
            'session' => $session,
            'qty' => $qty,
            'value' => $value,
        ]);

        $sign = $direction === 'in' ? 1 : -1;
        $item->qty_on_hand = round($item->qty_on_hand + $sign * $qty, 3);
        $item->value_on_hand += $sign * $value;
        $item->save();
    }

    private function qtyLabel(Item $item, float $qty): string
    {
        return rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.').' '.(Item::UNITS[$item->unit] ?? $item->unit);
    }
}
