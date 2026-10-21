<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\CostCenter;
use App\Models\JournalLine;
use App\Models\MilkRecord;
use App\Services\ReportService;
use App\Support\Lookups;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The farm's categories and projects. Each one has its own dashboard, and
 * new ventures (fish, store, hotel...) are added here without new code.
 */
class CostCenterController extends Controller
{
    public function index(Request $request, ReportService $reports): Response
    {
        [$from, $to] = $this->range($request);

        return Inertia::render('centers/Index', [
            'tree' => $reports->centerTree($from, $to),
            'farm' => $reports->farmTotals($from, $to),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'kinds' => CostCenter::KINDS,
            'parents' => Lookups::centers(false),
        ]);
    }

    public function show(Request $request, CostCenter $center, ReportService $reports): Response
    {
        [$from, $to] = $this->range($request);
        $ids = $center->descendantIds();
        $totals = $reports->accountTotals($from, $to, $ids);

        $milk = (float) MilkRecord::query()->whereIn('cost_center_id', $ids)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString().' 23:59:59'])->sum('liters');

        $lines = JournalLine::query()
            ->with(['entry:id,number,date,type,description,reversed_by_id', 'account:id,code,name,type', 'costCenter:id,name', 'animal:id,tag'])
            ->whereIn('cost_center_id', $ids)
            ->whereHas('account', fn ($query) => $query->whereIn('type', ['income', 'expense']))
            ->whereHas('entry', fn ($query) => $query->whereBetween('date', [$from->toDateString(), $to->toDateString().' 23:59:59']))
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn (JournalLine $line) => [
                'id' => $line->id,
                'entry_id' => $line->journal_entry_id,
                'number' => $line->entry->number,
                'date' => $line->entry->date->toDateString(),
                'description' => $line->memo ?: $line->entry->description,
                'account' => $line->account->name,
                'type' => $line->account->type,
                'center' => $line->costCenter?->name,
                'animal' => $line->animal?->tag,
                'amount' => $line->account->type === 'income' ? $line->credit - $line->debit : $line->debit - $line->credit,
            ]);

        return Inertia::render('centers/Show', [
            'center' => $center->only(['id', 'code', 'name', 'kind', 'holds_animals', 'is_active', 'description', 'parent_id', 'sort']),
            'kindLabel' => CostCenter::KINDS[$center->kind] ?? $center->kind,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'totals' => $totals,
            'trend' => $reports->monthlyTrend(6, $ids, false),
            'node' => $this->findNode($reports->centerTree($from, $to), $center->id),
            'lines' => $lines,
            'milk' => $milk,
            'costPerLiter' => $milk > 0 ? (int) round($totals['expense'] / $milk) : null,
            'animals' => Animal::query()->whereIn('cost_center_id', $ids)->where('status', 'active')->orderBy('tag')
                ->get(['id', 'tag', 'name', 'sex', 'breed', 'book_value', 'acquired_on', 'cost_center_id']),
            'kinds' => CostCenter::KINDS,
            'parents' => Lookups::centers(false),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $center = CostCenter::query()->create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => "নতুন ক্যাটাগরি \"{$center->name}\" যোগ হয়েছে।"]);

        return to_route('centers.show', $center);
    }

    public function update(Request $request, CostCenter $center): RedirectResponse
    {
        $data = $this->validated($request, $center);

        if (isset($data['parent_id']) && in_array((int) $data['parent_id'], $center->descendantIds(), true)) {
            return back()->withErrors(['parent_id' => 'একটি ক্যাটাগরি নিজের নিচের ক্যাটাগরির ভেতরে যেতে পারে না।']);
        }

        if (! ($data['holds_animals'] ?? false) && $center->animals()->where('status', 'active')->exists()) {
            return back()->withErrors(['holds_animals' => 'এখানে এখনো পশু আছে, আগে সেগুলো অন্য ক্যাটাগরিতে নিন।']);
        }

        $center->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'ক্যাটাগরি আপডেট হয়েছে।']);

        return back();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?CostCenter $center = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('cost_centers', 'code')->ignore($center)],
            'kind' => ['required', Rule::in(array_keys(CostCenter::KINDS))],
            'parent_id' => ['nullable', 'exists:cost_centers,id'],
            'holds_animals' => ['boolean'],
            'is_active' => ['boolean'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'code.regex' => 'কোডে শুধু ইংরেজি বড় হাতের অক্ষর, সংখ্যা, - বা _ চলবে।',
        ]);

        $data['sort'] ??= 0;

        return $data;
    }

    /** @return array{0: CarbonInterface, 1: CarbonInterface} */
    private function range(Request $request): array
    {
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);

        return [
            $request->filled('from') ? Date::parse($request->string('from')) : now()->startOfMonth(),
            $request->filled('to') ? Date::parse($request->string('to')) : now()->endOfMonth(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return array<string, mixed>|null
     */
    private function findNode(array $nodes, int $id): ?array
    {
        foreach ($nodes as $node) {
            if ($node['id'] === $id) {
                return $node;
            }

            if ($found = $this->findNode($node['children'], $id)) {
                return $found;
            }
        }

        return null;
    }
}
