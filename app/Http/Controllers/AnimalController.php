<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Animal;
use App\Models\JournalLine;
use App\Models\MilkRecord;
use App\Services\AllocationService;
use App\Services\AnimalService;
use App\Services\ReportService;
use App\Support\Lookups;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AnimalController extends Controller
{
    public function index(Request $request): Response
    {
        $status = in_array($request->string('status')->toString(), ['active', 'sold', 'dead'], true) ? $request->string('status')->toString() : 'active';

        return Inertia::render('animals/Index', [
            'status' => $status,
            'statuses' => Animal::STATUSES,
            'animals' => Animal::query()->with('costCenter:id,name')->where('status', $status)->orderBy('tag')->get()->map(fn (Animal $animal) => [
                'id' => $animal->id,
                'tag' => $animal->tag,
                'name' => $animal->name,
                'sex' => $animal->sex,
                'breed' => $animal->breed,
                'center' => $animal->costCenter->name,
                'acquired_on' => $animal->acquired_on->toDateString(),
                'purchase_cost' => $animal->purchase_cost,
                'sale_amount' => $animal->sale_amount,
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('animals/Create', [
            'centers' => array_values(array_filter(Lookups::centers(), fn ($center) => $center['holds_animals'])),
            'moneyAccounts' => Lookups::moneyAccounts(),
            'budgetHeads' => Lookups::budgetHeads(),
            'mothers' => Animal::query()->where('status', 'active')->where('sex', 'female')->orderBy('tag')->get(['id', 'tag', 'name']),
            'acquisitions' => Animal::ACQUISITIONS,
            'maxVoucherKb' => config('khamar.max_voucher_kb'),
        ]);
    }

    public function store(Request $request, AnimalService $animals): RedirectResponse
    {
        $data = $request->validate([
            'tag' => ['required', 'string', 'max:30', 'unique:animals,tag'],
            'name' => ['nullable', 'string', 'max:60'],
            'sex' => ['required', Rule::in(['female', 'male'])],
            'breed' => ['nullable', 'string', 'max:60'],
            'cost_center_id' => ['required', Rule::exists('cost_centers', 'id')->where('holds_animals', true)],
            'acquisition' => ['required', Rule::in(array_keys(Animal::ACQUISITIONS))],
            'acquired_on' => ['required', 'date', 'before_or_equal:today'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'mother_id' => ['nullable', 'exists:animals,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'paid_from_account_id' => ['required_if:acquisition,purchase', 'nullable', Rule::exists('accounts', 'id')->whereIn('subtype', Account::MONEY_SUBTYPES)],
            'budget_head_id' => ['nullable', 'exists:budget_heads,id'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:'.config('khamar.max_voucher_kb')],
        ]);

        $cost = $data['acquisition'] === 'born' ? 0 : Money::toPoisha($data['cost'] ?? 0);

        $animal = $animals->register(
            collect($data)->only(['tag', 'name', 'sex', 'breed', 'cost_center_id', 'acquisition', 'acquired_on', 'birth_date', 'mother_id', 'notes'])->all(),
            $cost,
            $data['paid_from_account_id'] ?? null,
            $data['budget_head_id'] ?? null,
            $request->file('attachments', []),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$animal->tag} খামারের তালিকায় যোগ হয়েছে।"]);

        return to_route('animals.show', $animal);
    }

    public function show(Animal $animal, ReportService $reports, AllocationService $allocation): Response
    {
        $animal->load(['costCenter:id,name', 'mother:id,tag', 'placements.costCenter:id,name']);

        return Inertia::render('animals/Show', [
            'animal' => [
                ...$animal->only(['id', 'tag', 'name', 'sex', 'breed', 'status', 'acquisition', 'purchase_cost', 'book_value', 'sale_amount', 'notes', 'cost_center_id']),
                'acquired_on' => $animal->acquired_on->toDateString(),
                'birth_date' => $animal->birth_date?->toDateString(),
                'disposed_on' => $animal->disposed_on?->toDateString(),
                'center' => $animal->costCenter->name,
                'mother' => $animal->mother?->tag,
                'status_label' => Animal::STATUSES[$animal->status],
                'acquisition_label' => Animal::ACQUISITIONS[$animal->acquisition],
            ],
            'placements' => $animal->placements->map(fn ($placement) => [
                'center' => $placement->costCenter->name,
                'from' => $placement->from_date->toDateString(),
                'to' => $placement->to_date?->toDateString(),
            ]),
            'cost' => $reports->animalCost($animal, $allocation),
            'milk' => (float) MilkRecord::query()->where('animal_id', $animal->id)->sum('liters'),
            'lines' => JournalLine::query()->with(['entry:id,number,date,description', 'account:id,name,type'])->where('animal_id', $animal->id)->orderByDesc('id')->get()->map(fn (JournalLine $line) => [
                'entry_id' => $line->journal_entry_id,
                'number' => $line->entry->number,
                'date' => $line->entry->date->toDateString(),
                'account' => $line->account->name,
                'description' => $line->memo ?: $line->entry->description,
                'debit' => $line->debit,
                'credit' => $line->credit,
            ]),
            'centers' => array_values(array_filter(Lookups::centers(), fn ($center) => $center['holds_animals'])),
            'moneyAccounts' => Lookups::moneyAccounts(),
            'customers' => Lookups::parties('customer'),
        ]);
    }

    public function transfer(Request $request, Animal $animal, AnimalService $animals): RedirectResponse
    {
        $data = $request->validate([
            'cost_center_id' => ['required', 'exists:cost_centers,id'],
            'date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.$animal->acquired_on->toDateString()],
        ]);

        $animals->transfer($animal, (int) $data['cost_center_id'], $data['date']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'ক্যাটাগরি বদল হয়েছে।']);

        return back();
    }

    public function sell(Request $request, Animal $animal, AnimalService $animals): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.$animal->acquired_on->toDateString()],
            'amount' => Money::rule(),
            'money_account_id' => ['required', Rule::exists('accounts', 'id')->whereIn('subtype', Account::MONEY_SUBTYPES)],
            'party_id' => ['nullable', 'exists:parties,id'],
        ]);

        $animals->sell($animal, $data['date'], Money::toPoisha($data['amount']), (int) $data['money_account_id'], $data['party_id'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$animal->tag} বিক্রি লেখা হয়েছে।"]);

        return back();
    }

    public function death(Request $request, Animal $animal, AnimalService $animals): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.$animal->acquired_on->toDateString()],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $animals->recordDeath($animal, $data['date'], $data['note'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'মৃত্যু লেখা হয়েছে।']);

        return back();
    }
}
