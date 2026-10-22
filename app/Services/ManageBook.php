<?php

namespace App\Services;

use App\Http\Controllers\CostCenterController;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Item;
use App\Models\JournalLine;
use App\Models\Party;
use App\Models\Worker;
use App\Support\Tr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Add, rename and remove the things the dropdowns pick from, straight from the dropdown:
 * expense and income heads, money places, people, stock items, projects and workers.
 * Something that is already in the books is never deleted — it is hidden instead, so every
 * old entry still reads right; the app's own heads and projects stay as they are.
 */
class ManageBook
{
    /** Each kind and the ability it needs. */
    public const TYPES = [
        'account' => 'manage-setup',
        'money' => 'manage-setup',
        'center' => 'manage-setup',
        'party' => 'post-entries',
        'item' => 'post-entries',
        'worker' => 'post-entries',
    ];

    /**
     * @param  array<string, mixed>  $extra  account: group (top-level code, e.g. 5000); money: kind;
     *                                       party: party_type; item: unit, kind; center: parent (code)
     */
    public function create(string $type, string $name, array $extra = []): Model
    {
        $name = trim($name);
        $this->assertNameFree($type, $name);

        return DB::transaction(fn () => match ($type) {
            'account' => $this->createAccount($name, (string) ($extra['group'] ?? '5000')),
            'money' => $this->createMoney($name, (string) ($extra['kind'] ?? 'person')),
            'center' => $this->createCenter($name, (string) ($extra['parent'] ?? 'FARM')),
            'party' => Party::query()->create([
                'name' => $name,
                'type' => array_key_exists((string) ($extra['party_type'] ?? ''), Party::TYPES) ? $extra['party_type'] : 'other',
            ]),
            'item' => $this->createItem($name, $extra),
            'worker' => Worker::query()->create(['name' => $name, 'kind' => 'daily', 'is_active' => true]),
            default => throw ValidationException::withMessages(['name' => Tr::get('এটা এখান থেকে যোগ করা যায় না।')]),
        });
    }

    public function rename(string $type, int $id, string $name): Model
    {
        $model = $this->find($type, $id);
        $name = trim($name);
        $this->assertNameFree($type, $name, $id);
        $model->update(['name' => $name]);

        return $model;
    }

    /** Deletes what was never used; hides what is in the books. Returns 'deleted' or 'hidden'. */
    public function remove(string $type, int $id): string
    {
        $model = $this->find($type, $id);

        if ($this->isProtected($type, $model)) {
            throw ValidationException::withMessages(['name' => Tr::get('এটা অ্যাপের নিজের — মোছা বা লুকানো যায় না, শুধু নাম বদলানো যায়।')]);
        }

        if ($type === 'money' && $model instanceof Account && $this->balance($model) !== 0) {
            throw ValidationException::withMessages(['name' => Tr::get('এখানে এখনো টাকা আছে — আগে অন্য জায়গায় সরিয়ে নিন।')]);
        }

        if ($type === 'center' && $model instanceof CostCenter && $model->animals()->where('status', 'active')->exists()) {
            throw ValidationException::withMessages(['name' => Tr::get('এখানে এখনো পশু আছে — আগে অন্য ক্যাটাগরিতে নিন।')]);
        }

        if ($this->inUse($type, $model)) {
            $model->update(['is_active' => false]);

            return 'hidden';
        }

        try {
            DB::transaction(function () use ($type, $model) {
                if ($type === 'center') {
                    // Light references let go of it rather than block.
                    foreach (['farm_notes', 'goals', 'reminders'] as $table) {
                        DB::table($table)->where('cost_center_id', $model->getKey())->update(['cost_center_id' => null]);
                    }
                }

                $model->delete();
            });
        } catch (QueryException) {
            // Something else still points at it: keep it, hidden.
            $model->update(['is_active' => false]);

            return 'hidden';
        }

        return 'deleted';
    }

    public function find(string $type, int $id): Model
    {
        $model = match ($type) {
            'account', 'money' => Account::query()->find($id),
            'center' => CostCenter::query()->find($id),
            'party' => Party::query()->find($id),
            'item' => Item::query()->find($id),
            'worker' => Worker::query()->find($id),
            default => null,
        };

        if ($model === null) {
            throw ValidationException::withMessages(['name' => Tr::get('খুঁজে পাওয়া যায়নি।')]);
        }

        return $model;
    }

    private function createAccount(string $name, string $group): Account
    {
        $parent = Account::query()->where('code', $group)->whereNull('parent_id')->first();

        if ($parent === null || ! in_array($parent->type, ['expense', 'income', 'asset', 'liability'], true)) {
            throw ValidationException::withMessages(['name' => Tr::get('কোন ধরনের খাত, বোঝা যায়নি।')]);
        }

        // The next round code in the group: 5990 after 5900, then 5991... when the tens run out.
        $used = Account::query()->where('code', 'like', substr($group, 0, 1).'___')->pluck('code')->map(fn ($c) => (int) $c)->all();
        $base = (int) $group;
        $code = null;

        foreach ([10, 1] as $step) {
            for ($c = $base + $step; $c < $base + 1000; $c += $step) {
                if (! in_array($c, $used, true)) {
                    $code = $c;
                    break 2;
                }
            }
        }

        return Account::query()->create([
            'parent_id' => $parent->id,
            'code' => (string) $code,
            'name' => $name,
            'type' => $parent->type,
            'is_system' => false,
        ]);
    }

    private function createMoney(string $name, string $kind): Account
    {
        $used = Account::query()->where('code', 'like', '11__')->pluck('code')->map(fn ($c) => (int) $c)->all();
        $code = 1101;

        while (in_array($code, $used, true) && $code < 1199) {
            $code++;
        }

        return Account::query()->create([
            'parent_id' => Account::byCode('1000')->id,
            'code' => (string) $code,
            'name' => $name,
            'type' => 'asset',
            'subtype' => array_key_exists($kind, Account::MONEY_PLACES) ? $kind : 'person',
            'is_system' => false,
        ]);
    }

    private function createCenter(string $name, string $parentCode): CostCenter
    {
        $parent = CostCenter::query()->where('code', $parentCode)->first() ?? CostCenter::query()->where('code', 'FARM')->firstOrFail();
        $code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name)) ?: 'C', 0, 6));
        $base = $code;
        $n = 1;

        while (CostCenter::query()->where('code', $code)->exists()) {
            $code = $base.(++$n);
        }

        return CostCenter::query()->create([
            'parent_id' => $parent->id,
            'code' => $code,
            'name' => $name,
            'kind' => $parent->kind === 'group' ? 'other' : $parent->kind,
            'holds_animals' => $parent->holds_animals,
            'is_active' => true,
            'sort' => (int) CostCenter::query()->where('parent_id', $parent->id)->max('sort') + 10,
        ]);
    }

    /** @param  array<string, mixed>  $extra */
    private function createItem(string $name, array $extra): Item
    {
        $kind = array_key_exists((string) ($extra['kind'] ?? ''), Item::KINDS) ? (string) $extra['kind'] : 'other';
        $unit = array_key_exists((string) ($extra['unit'] ?? ''), Item::UNITS) ? (string) $extra['unit'] : 'kg';
        // A new item books like the others of its kind; the first of a kind goes to "other costs".
        $like = Item::query()->where('kind', $kind)->first();

        return Item::query()->create([
            'name' => $name,
            'unit' => $unit,
            'kind' => $kind,
            'inventory_account_id' => $like->inventory_account_id ?? Account::query()->where('subtype', 'inventory')->value('id'),
            'expense_account_id' => $like->expense_account_id ?? Account::byCode('5990')->id,
            'is_active' => true,
        ]);
    }

    private function assertNameFree(string $type, string $name, ?int $except = null): void
    {
        if ($name === '' || mb_strlen($name) > 120) {
            throw ValidationException::withMessages(['name' => Tr::get('নাম দিন (১২০ অক্ষরের মধ্যে)।')]);
        }

        $query = match ($type) {
            'account', 'money' => Account::query(),
            'center' => CostCenter::query(),
            'party' => Party::query(),
            'item' => Item::query(),
            'worker' => Worker::query(),
            default => null,
        };

        if ($query !== null && $query->where('name', $name)->when($except, fn ($q) => $q->whereKeyNot($except))->exists()) {
            throw ValidationException::withMessages(['name' => Tr::get('":name" নামে আগে থেকেই আছে।', ['name' => $name])]);
        }
    }

    private function isProtected(string $type, Model $model): bool
    {
        return match ($type) {
            'account', 'money' => $model instanceof Account && ($model->is_system || $model->parent_id === null),
            'center' => $model instanceof CostCenter && in_array($model->code, CostCenterController::SYSTEM_CODES, true),
            default => false,
        };
    }

    private function inUse(string $type, Model $model): bool
    {
        $id = $model->getKey();

        return match ($type) {
            'account', 'money' => JournalLine::query()->where('account_id', $id)->exists()
                || Item::query()->where('inventory_account_id', $id)->orWhere('expense_account_id', $id)->exists()
                || Account::query()->where('parent_id', $id)->exists(),
            'center' => JournalLine::query()->where('cost_center_id', $id)->exists()
                || DB::table('animals')->where('cost_center_id', $id)->exists()
                || DB::table('stock_movements')->where('cost_center_id', $id)->exists()
                || CostCenter::query()->where('parent_id', $id)->exists(),
            'party' => DB::table('journal_entries')->where('party_id', $id)->exists()
                || Account::query()->where('party_id', $id)->exists()
                || DB::table('debts')->where('party_id', $id)->exists(),
            'item' => DB::table('stock_movements')->where('item_id', $id)->exists(),
            'worker' => DB::table('work_logs')->where('worker_id', $id)->exists(),
            default => true,
        };
    }

    private function balance(Account $account): int
    {
        return (int) JournalLine::query()->where('account_id', $account->id)->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as n')->value('n');
    }
}
