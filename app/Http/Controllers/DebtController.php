<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Models\Party;
use App\Services\DebtBook;
use App\Support\Lookups;
use App\Support\Money;
use App\Support\Tr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Money that has to come back: what we gave and get back, what we took and pay back. */
class DebtController extends Controller
{
    public function index(DebtBook $book): Response
    {
        $cards = Debt::query()->orderByRaw('closed_on IS NOT NULL')->orderBy('started_on')->get()->map(fn (Debt $d) => $book->card($d));

        return Inertia::render('debts/Index', [
            'debts' => $cards->values(),
            'totals' => [
                'out' => $cards->where('direction', 'out')->sum('balance'),
                'in' => $cards->where('direction', 'in')->sum('balance'),
            ],
            'directions' => Tr::all(Debt::DIRECTIONS),
            'moneyAccounts' => Lookups::moneyAccounts(),
            'parties' => Party::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone', 'type']),
        ]);
    }

    public function store(Request $request, DebtBook $book): RedirectResponse
    {
        $data = $request->validate([
            'direction' => ['required', Rule::in(array_keys(Debt::DIRECTIONS))],
            'person_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'purpose' => ['nullable', 'string', 'max:160'],
            'party_id' => ['nullable', 'exists:parties,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'due_on' => ['nullable', 'date', 'after_or_equal:date'],
            'amount' => Money::rule(),
            'money_account_id' => ['required', 'exists:accounts,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'installments' => ['nullable', 'array', 'max:120'],
            'installments.*.due_on' => ['required', 'date'],
            'installments.*.amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $book->open($data, Money::toPoisha($data['amount']), (int) $data['money_account_id'], $this->installments($data['installments'] ?? []));
        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get(':name-এর হিসাব খোলা হলো।', ['name' => $data['person_name']])]);

        return back();
    }

    public function move(Request $request, Debt $debt, DebtBook $book): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(['more', 'settle'])],
            'amount' => Money::rule(),
            'money_account_id' => ['required', 'exists:accounts,id'],
            'date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.$debt->started_on->toDateString()],
            'note' => ['nullable', 'string', 'max:160'],
        ]);

        $book->move($debt, $data['kind'], Money::toPoisha($data['amount']), (int) $data['money_account_id'], $data['date'], $data['note'] ?? null);
        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get('লেখা হলো।')]);

        return back();
    }

    public function plan(Request $request, Debt $debt, DebtBook $book): RedirectResponse
    {
        $data = $request->validate([
            'due_on' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'installments' => ['nullable', 'array', 'max:120'],
            'installments.*.due_on' => ['required', 'date'],
            'installments.*.amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $debt->update(['due_on' => $data['due_on'] ?? null, 'phone' => $data['phone'] ?? $debt->phone, 'notes' => $data['notes'] ?? $debt->notes]);
        $book->plan($debt, $this->installments($data['installments'] ?? []));
        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get('ফেরতের পরিকল্পনা রাখা হলো।')]);

        return back();
    }

    /**
     * @param  array<int, array{due_on:string, amount:float|int|string}>  $rows
     * @return list<array{due_on:string, amount:int}>
     */
    private function installments(array $rows): array
    {
        $out = [];

        foreach ($rows as $row) {
            $out[] = ['due_on' => (string) $row['due_on'], 'amount' => Money::toPoisha($row['amount'])];
        }

        return $out;
    }
}
