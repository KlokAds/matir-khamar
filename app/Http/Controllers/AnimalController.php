<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Animal;
use App\Models\Batch;
use App\Models\Breeding;
use App\Models\CostCenter;
use App\Models\HealthEvent;
use App\Models\JournalLine;
use App\Models\MilkRecord;
use App\Services\AllocationService;
use App\Services\AnimalService;
use App\Services\HerdCare;
use App\Services\ReportService;
use App\Support\CareGuide;
use App\Support\Lookups;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
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

    public function create(AnimalService $animals): Response
    {
        return Inertia::render('animals/Create', [
            'centers' => array_values(array_filter(Lookups::centers(), fn ($center) => $center['holds_animals'])),
            'moneyAccounts' => Lookups::moneyAccounts(),
            'budgetHeads' => Lookups::budgetHeads(),
            'mothers' => Animal::query()->where('status', 'active')->where('sex', 'female')->orderBy('tag')->get(['id', 'tag', 'name']),
            'acquisitions' => Animal::ACQUISITIONS,
            'maxVoucherKb' => config('khamar.max_voucher_kb'),
            'batches' => Batch::query()->whereNull('closed_on')->orderByDesc('number')->get()
                ->map(fn (Batch $b) => ['id' => $b->id, 'label' => $b->label(), 'cost_center_id' => $b->cost_center_id, 'started_on' => $b->started_on->toDateString()]),
            'nextTags' => (object) CostCenter::query()->where('holds_animals', true)->get()
                ->mapWithKeys(fn (CostCenter $c) => [$c->id => $animals->nextTag($c)])->all(),
            'nextBatchNumbers' => (object) CostCenter::query()->where('holds_animals', true)->pluck('id')
                ->mapWithKeys(fn ($id) => [$id => Batch::nextNumber((int) $id)])->all(),
        ]);
    }

    public function store(Request $request, AnimalService $animals, HerdCare $care): RedirectResponse
    {
        $data = $request->validate([
            // Left empty, the next running tag of the category is given (M-04, G-02…).
            'tag' => ['nullable', 'string', 'max:30', 'unique:animals,tag'],
            'count' => ['nullable', 'integer', 'min:1', 'max:50'],
            'name' => ['nullable', 'string', 'max:60'],
            'sex' => ['required', Rule::in(['female', 'male'])],
            'breed' => ['nullable', 'string', 'max:60'],
            'cost_center_id' => ['required', Rule::exists('cost_centers', 'id')->where('holds_animals', true)],
            'acquisition' => ['required', Rule::in(array_keys(Animal::ACQUISITIONS))],
            'acquired_on' => ['required', 'date', 'before_or_equal:today'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'mother_id' => ['nullable', 'exists:animals,id'],
            // An existing fattening round, or "new" to open the next numbered one.
            'batch' => ['nullable', Rule::in(['new', ...Batch::query()->whereNull('closed_on')->pluck('id')->map(fn ($id) => (string) $id)->all()])],
            'weight_kg' => ['nullable', 'numeric', 'min:5', 'max:2000'],
            'months_pregnant' => ['nullable', 'numeric', 'min:0.5', 'max:9.5'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'paid_from_account_id' => ['required_if:acquisition,purchase', 'nullable', Rule::exists('accounts', 'id')->whereIn('subtype', Account::MONEY_SUBTYPES)],
            'budget_head_id' => ['nullable', 'exists:budget_heads,id'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:'.config('khamar.max_voucher_kb')],
        ]);

        $cost = $data['acquisition'] === 'born' ? 0 : Money::toPoisha($data['cost'] ?? 0);
        $count = $data['acquisition'] === 'born' ? 1 : (int) ($data['count'] ?? 1);

        if ($count > 1) {
            return $this->storeGroup($request, $data, $count, $cost, $animals, $care);
        }

        $data['tag'] = ($data['tag'] ?? '') ?: $animals->nextTag(CostCenter::query()->findOrFail((int) $data['cost_center_id']));

        // The batch, the animal, its first weight and a known pregnancy are saved together or not at all.
        $animal = DB::transaction(function () use ($data, $cost, $request, $animals, $care): Animal {
            $batchId = $this->batchFor($data);

            $animal = $animals->register(
                [...Arr::only($data, ['tag', 'name', 'sex', 'breed', 'cost_center_id', 'acquisition', 'acquired_on', 'birth_date', 'mother_id', 'notes']), 'batch_id' => $batchId],
                $cost,
                $data['paid_from_account_id'] ?? null,
                $data['budget_head_id'] ?? null,
                $this->attachments($request),
            );

            if (! empty($data['weight_kg'])) {
                $animal->weights()->create(['date' => $data['acquired_on'], 'kg' => $data['weight_kg'], 'note' => 'শুরুর ওজন', 'created_by' => $request->user()->id]);
            }

            if (! empty($data['months_pregnant']) && $animal->sex === 'female') {
                $care->recordService($animal, ['months_pregnant' => $data['months_pregnant'], 'notes' => 'কেনার সময় গর্ভবতী']);
            }

            return $animal;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$animal->tag} খামারের তালিকায় যোগ হয়েছে।"]);

        return to_route('animals.show', $animal);
    }

    /**
     * Two or more bought together: one price shared, one entry, each animal its own card.
     *
     * @param  array<string, mixed>  $data
     */
    private function storeGroup(Request $request, array $data, int $count, int $cost, AnimalService $animals, HerdCare $care): RedirectResponse
    {
        $group = DB::transaction(function () use ($request, $data, $count, $cost, $animals, $care): array {
            $group = $animals->registerGroup(
                [...Arr::only($data, ['sex', 'breed', 'cost_center_id', 'acquisition', 'acquired_on', 'birth_date', 'notes']), 'batch_id' => $this->batchFor($data)],
                $count,
                $cost,
                $data['paid_from_account_id'] ?? null,
                $data['budget_head_id'] ?? null,
                $this->attachments($request),
            );

            if (! empty($data['months_pregnant']) && $data['sex'] === 'female') {
                foreach ($group as $animal) {
                    $care->recordService($animal, ['months_pregnant' => $data['months_pregnant'], 'notes' => 'কেনার সময় গর্ভবতী']);
                }
            }

            return $group;
        });

        $tags = implode(', ', array_map(fn (Animal $a) => $a->tag, $group));
        Inertia::flash('toast', ['type' => 'success', 'message' => "{$count}টি গরু যোগ হয়েছে: {$tags}"]);

        return to_route('animals.index');
    }

    /** @param  array<string, mixed>  $data */
    private function batchFor(array $data): ?int
    {
        return match ($data['batch'] ?? null) {
            null, '' => null,
            'new' => Batch::query()->create([
                'cost_center_id' => (int) $data['cost_center_id'],
                'number' => Batch::nextNumber((int) $data['cost_center_id']),
                'started_on' => $data['acquired_on'],
            ])->id,
            default => (int) $data['batch'],
        };
    }

    public function show(Animal $animal, ReportService $reports, AllocationService $allocation, HerdCare $care): Response
    {
        $animal->load(['costCenter:id,name', 'mother:id,tag', 'placements.costCenter:id,name', 'batch', 'weights', 'breedings.animal:id,tag,name']);
        $ageDays = $animal->birth_date ? (int) $animal->birth_date->diffInDays(now()->startOfDay()) : null;

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
            'batch' => $animal->batch ? ['id' => $animal->batch->id, 'label' => $animal->batch->label(), 'days' => $animal->batch->daysRunning(), 'target_days' => $animal->batch->target_days] : null,
            'weights' => $animal->weights->map(fn ($w) => ['id' => $w->id, 'date' => $w->date->toDateString(), 'kg' => $w->kg, 'note' => $w->note])->values(),
            'growth' => $care->growth($animal),
            'breedings' => $animal->breedings->map(fn ($b) => $care->breedingCard($b))->values(),
            'breedingOptions' => ['methods' => Breeding::METHODS, 'results' => Breeding::RESULTS, 'outcomes' => Breeding::OUTCOMES],
            'ageDays' => $ageDays,
            'calfStage' => $ageDays !== null && $ageDays <= 540 ? CareGuide::stageFor(CareGuide::calf(), $ageDays) : null,
            // Its own doses plus those given to its whole category or the whole farm.
            'health' => HealthEvent::query()->with(['animal:id,tag', 'costCenter:id,name'])
                ->where(fn ($q) => $q->where('animal_id', $animal->id)
                    ->orWhere(fn ($w) => $w->whereNull('animal_id')->where(fn ($c) => $c->where('cost_center_id', $animal->cost_center_id)->orWhereNull('cost_center_id'))))
                ->orderByDesc('given_on')->limit(20)->get()
                ->map(fn (HealthEvent $e) => [
                    'id' => $e->id,
                    'kind_label' => HealthEvent::KINDS[$e->kind] ?? $e->kind,
                    'name' => $e->name,
                    'target' => $e->target(),
                    'given_on' => $e->given_on->toDateString(),
                    'next_due_on' => $e->next_due_on?->toDateString(),
                ]),
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
