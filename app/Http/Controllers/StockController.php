<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Item;
use App\Models\StockMovement;
use App\Services\AccountCodes;
use App\Services\StockService;
use App\Support\Lookups;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Feed and medicine stock: buy into stock, use out of it by kg per category,
 * and record own production such as grass from the lease land.
 */
class StockController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('stock/Index', [
            'items' => Lookups::items(),
            'kinds' => Item::KINDS,
            'units' => Item::UNITS,
            'inventoryAccounts' => Account::query()->where('subtype', 'inventory')->orderBy('code')->get(['id', 'code', 'name']),
            'expenseAccounts' => Lookups::accounts('expense'),
            'recent' => StockMovement::query()->with(['item:id,name,unit', 'costCenter:id,name', 'entry:id,number'])->latest('id')->limit(30)->get()->map(fn (StockMovement $movement) => [
                'id' => $movement->id,
                'date' => $movement->date->toDateString(),
                'item' => $movement->item->name,
                'unit' => Item::UNITS[$movement->item->unit] ?? $movement->item->unit,
                'direction' => $movement->direction,
                'session' => $movement->session ? StockMovement::SESSIONS[$movement->session] ?? null : null,
                'qty' => $movement->qty,
                'value' => $movement->value,
                'center' => $movement->costCenter?->name,
                'entry_id' => $movement->journal_entry_id,
                'number' => $movement->entry->number,
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        $mode = in_array($request->string('mode')->toString(), ['purchase', 'issue', 'produce'], true) ? $request->string('mode')->toString() : 'issue';

        return Inertia::render('stock/Move', [
            'mode' => $mode,
            'sessions' => StockMovement::SESSIONS,
            'items' => Lookups::items(),
            'centers' => Lookups::centers(),
            'moneyAccounts' => Lookups::moneyAccounts(),
            'budgetHeads' => Lookups::budgetHeads(),
            'suppliers' => Lookups::parties('supplier'),
            'canPurchase' => $request->user()->can('post-entries'),
            'maxVoucherKb' => config('khamar.max_voucher_kb'),
            'grassCenterId' => CostCenter::query()->where('code', 'GHAS')->value('id'),
        ]);
    }

    public function purchase(Request $request, StockService $stock): RedirectResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'qty' => ['required', 'numeric', 'min:0.001', 'max:9999999'],
            'amount' => Money::rule(),
            'settle' => ['required', Rule::in(['money', 'credit'])],
            'money_account_id' => ['required_if:settle,money', 'nullable', Rule::exists('accounts', 'id')->whereIn('subtype', Account::MONEY_SUBTYPES)],
            'party_id' => ['required_if:settle,credit', 'nullable', 'exists:parties,id'],
            'budget_head_id' => ['nullable', 'exists:budget_heads,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:'.config('khamar.max_voucher_kb')],
        ]);

        $entry = $stock->purchase(Item::query()->findOrFail((int) $data['item_id']), $data['date'], (float) $data['qty'], Money::toPoisha($data['amount']), [
            'credit_account_id' => $data['settle'] === 'money' ? (int) $data['money_account_id'] : Account::byCode(AccountCodes::PAYABLE)->id,
            'party_id' => $data['party_id'] ?? null,
            'budget_head_id' => $data['budget_head_id'] ?? null,
            'description' => $data['description'] ?? null,
            'attachments' => $this->attachments($request),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "স্টকে যোগ হয়েছে ({$entry->number})।"]);

        return to_route('stock.index');
    }

    public function issue(Request $request, StockService $stock): RedirectResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'splits' => ['required', 'array', 'min:1', 'max:20'],
            'splits.*.cost_center_id' => ['required', 'exists:cost_centers,id'],
            'splits.*.qty' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'note' => ['nullable', 'string', 'max:255'],
            'session' => ['nullable', Rule::in(array_keys(StockMovement::SESSIONS))],
        ]);

        $entry = $stock->issue(Item::query()->findOrFail((int) $data['item_id']), $data['date'], $data['splits'], $data['note'] ?? null, $data['session'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => "ব্যবহার লেখা হয়েছে ({$entry->number})।"]);

        return $request->boolean('stay') ? back() : to_route('stock.index');
    }

    public function produce(Request $request, StockService $stock): RedirectResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'qty' => ['required', 'numeric', 'min:0.001', 'max:9999999'],
            'rate' => Money::rule(),
            'cost_center_id' => ['required', 'exists:cost_centers,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $entry = $stock->produce(Item::query()->findOrFail((int) $data['item_id']), $data['date'], (float) $data['qty'], Money::toPoisha($data['rate']), (int) $data['cost_center_id'], $data['note'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => "উৎপাদন স্টকে উঠেছে ({$entry->number})।"]);

        return to_route('stock.index');
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:items,name'],
            'unit' => ['required', Rule::in(array_keys(Item::UNITS))],
            'kind' => ['required', Rule::in(array_keys(Item::KINDS))],
            'inventory_account_id' => ['required', Rule::exists('accounts', 'id')->where('subtype', 'inventory')],
            'expense_account_id' => ['required', Rule::exists('accounts', 'id')->where('type', 'expense')],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
        ]);

        Item::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'নতুন মাল যোগ হয়েছে।']);

        return back();
    }

    public function updateItem(Request $request, Item $item): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('items', 'name')->ignore($item)],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $item->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'আপডেট হয়েছে।']);

        return back();
    }
}
