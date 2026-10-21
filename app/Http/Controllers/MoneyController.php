<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalLine;
use App\Services\AccountCodes;
use App\Services\Ledger;
use App\Support\Lookups;
use App\Support\Money;
use App\Support\Tr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Where the farm's money sits: cash in hand, each bank, each mobile wallet, and money left with
 * a person. Every place is an account, so its balance is simply what the books say.
 */
class MoneyController extends Controller
{
    public function index(Request $request): Response
    {
        $places = Account::query()->whereIn('subtype', Account::MONEY_SUBTYPES)->orderBy('code')->get();
        $sums = JournalLine::query()->toBase()
            ->whereIn('account_id', $places->pluck('id'))
            ->groupBy('account_id')
            ->selectRaw('account_id, SUM(debit) - SUM(credit) as net, MAX(journal_entry_id) as last_entry')
            ->get()
            ->keyBy('account_id');

        $selectedId = $request->integer('place') ?: null;
        $selected = $selectedId ? $places->firstWhere('id', $selectedId) : null;

        return Inertia::render('money/Index', [
            'places' => $places->map(fn (Account $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'kind' => $account->subtype,
                'kind_label' => __(Account::MONEY_PLACES[$account->subtype] ?? $account->subtype),
                'balance' => (int) data_get($sums->get($account->id), 'net', 0),
                'is_active' => $account->is_active,
            ])->values(),
            'kinds' => Tr::all(Account::MONEY_PLACES),
            'selected' => $selected ? $this->statement($selected) : null,
            'moneyAccounts' => Lookups::moneyAccounts(),
        ]);
    }

    /** @return array<string, mixed> */
    private function statement(Account $account): array
    {
        $lines = JournalLine::query()
            ->with('entry:id,number,date,description,type')
            ->where('account_id', $account->id)
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->orderBy('journal_entries.date')
            ->orderBy('journal_entries.id')
            ->select('journal_lines.*')
            ->get();

        $running = 0;
        $rows = [];

        foreach ($lines as $line) {
            $running += $line->debit - $line->credit;
            $rows[] = [
                'entry_id' => $line->journal_entry_id,
                'number' => $line->entry->number,
                'date' => $line->entry->date->toDateString(),
                'description' => $line->entry->description,
                'in' => $line->debit,
                'out' => $line->credit,
                'balance' => $running,
            ];
        }

        return [
            'id' => $account->id,
            'name' => $account->name,
            'kind_label' => __(Account::MONEY_PLACES[$account->subtype] ?? $account->subtype),
            // Newest first, the last 200 movements.
            'rows' => array_slice(array_reverse($rows), 0, 200),
        ];
    }

    public function store(Request $request, Ledger $ledger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:accounts,name'],
            'kind' => ['required', Rule::in(array_keys(Account::MONEY_PLACES))],
            'opening' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'opening_date' => ['required_with:opening', 'nullable', 'date', 'before_or_equal:today'],
        ]);

        $parent = Account::byCode('1000');
        $code = $this->nextCode();

        $account = Account::query()->create([
            'parent_id' => $parent->id,
            'code' => $code,
            'name' => $data['name'],
            'type' => 'asset',
            'subtype' => $data['kind'],
            'is_system' => false,
        ]);

        $opening = Money::toPoisha($data['opening'] ?? 0);

        // Money that was already there before the books started: brought in once, as an opening balance.
        if ($opening > 0) {
            $ledger->post('opening', (string) $data['opening_date'], "প্রারম্ভিক জের — {$account->name}", [
                ['account_id' => $account->id, 'debit' => $opening],
                ['account_id' => Account::byCode(AccountCodes::OPENING)->id, 'credit' => $opening],
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('":name" যোগ হয়েছে।', ['name' => $account->name])]);

        return to_route('money.index', ['place' => $account->id]);
    }

    /** Next free code in the 1100–1199 range kept for money places. */
    private function nextCode(): string
    {
        $used = Account::query()->where('code', 'like', '11__')->pluck('code')->map(fn ($code) => (int) $code)->all();

        for ($code = 1101; $code <= 1199; $code++) {
            if (! in_array($code, $used, true)) {
                return (string) $code;
            }
        }

        return '1199-'.count($used);
    }
}
