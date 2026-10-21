<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\CostCenter;
use App\Models\JournalEntry;
use App\Services\AllocationService;
use App\Support\Lookups;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Month-end sharing of the "Shadharon" pool into the real categories. */
class AllocationController extends Controller
{
    public function create(Request $request, AllocationService $allocation): Response
    {
        $data = $request->validate([
            'source' => ['nullable', 'exists:cost_centers,id'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $source = isset($data['source'])
            ? CostCenter::query()->findOrFail((int) $data['source'])
            : CostCenter::query()->where('kind', 'overhead')->first();
        $month = Date::parse(($data['month'] ?? now()->format('Y-m')).'-01');
        $pool = $source ? $allocation->pool($source, $month) : [];
        $names = Account::query()->whereIn('id', array_keys($pool))->pluck('name', 'id');

        return Inertia::render('allocation/Create', [
            'sourceId' => $source?->id,
            'month' => $month->format('Y-m'),
            'sources' => CostCenter::query()->where('kind', 'overhead')->orderBy('sort')->get(['id', 'name']),
            'centers' => array_values(array_filter(Lookups::centers(), fn ($center) => ! in_array($center['kind'], ['group', 'overhead'], true))),
            'pool' => collect($pool)->map(fn ($amount, $accountId) => ['account' => $names[$accountId] ?? '', 'amount' => $amount])->values(),
            'animalDays' => (object) $allocation->animalDays($month),
            'done' => JournalEntry::query()->where('reference', 'like', 'alloc-'.($source->id ?? 0).'-'.$month->format('Y-m'))->whereNull('reversed_by_id')->get(['id', 'number', 'description']),
        ]);
    }

    public function store(Request $request, AllocationService $allocation): RedirectResponse
    {
        $data = $request->validate([
            'source_id' => ['required', Rule::exists('cost_centers', 'id')->where('kind', 'overhead')],
            'month' => ['required', 'date_format:Y-m'],
            'method' => ['required', Rule::in(['animal_days', 'percent'])],
            'weights' => ['required', 'array'],
            'weights.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);

        $month = Date::parse($data['month'].'-01');

        if ($month->greaterThan(now()->startOfMonth())) {
            return back()->withErrors(['month' => 'ভবিষ্যতের মাসের খরচ ভাগ করা যায় না।']);
        }

        $weights = collect((array) $data['weights'])->mapWithKeys(fn ($weight, $id) => [(int) $id => (float) $weight])->all();
        $entry = $allocation->post(CostCenter::query()->findOrFail((int) $data['source_id']), $month, $weights, $data['method']);

        Inertia::flash('toast', ['type' => 'success', 'message' => "খরচ ভাগ হয়েছে ({$entry->number})।"]);

        return to_route('entries.show', $entry);
    }
}
