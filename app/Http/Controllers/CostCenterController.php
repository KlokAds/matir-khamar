<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\CostCenter;
use App\Models\JournalLine;
use App\Models\MilkRecord;
use App\Services\ReportService;
use App\Support\Lookups;
use App\Support\Tr;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The farm's categories and projects. Each one has its own dashboard, and
 * new ventures (fish, store, hotel...) are added here without new code.
 */
class CostCenterController extends Controller
{
    /** The app looks these up by code, so they keep their code and cannot be removed. */
    public const SYSTEM_CODES = ['FARM', 'GORU', 'GAVI', 'BOKNA', 'MOTATAJA', 'BACHUR', 'GHAS', 'SHADHARON'];

    /**
     * Every project and category in one list, with what can be done to each: edit, turn on
     * or off (shown or hidden), move up or down, add one inside, and delete when never used.
     */
    public function manage(): Response
    {
        $used = fn (string $table) => DB::table($table)->whereNotNull('cost_center_id')->selectRaw('cost_center_id, COUNT(*) as n')->groupBy('cost_center_id')->pluck('n', 'cost_center_id');
        $lines = $used('journal_lines');
        $animals = $used('animals');
        $moves = $used('stock_movements');
        $batches = $used('batches');
        $history = $used('animal_placements')->union($used('milk_records'))->union($used('prepaid_schedules'));
        $children = DB::table('cost_centers')->whereNotNull('parent_id')->selectRaw('parent_id, COUNT(*) as n')->groupBy('parent_id')->pluck('n', 'parent_id');
        $all = CostCenter::query()->get()->keyBy('id');

        $rows = array_map(function (array $center) use ($all, $lines, $animals, $moves, $batches, $children, $history) {
            $model = $all[$center['id']];
            $uses = (int) ($lines[$center['id']] ?? 0) + (int) ($moves[$center['id']] ?? 0);

            return [
                ...$center,
                'parent_id' => $model->parent_id,
                'sort' => $model->sort,
                'description' => $model->description,
                'kind_label' => __(CostCenter::KINDS[$center['kind']] ?? $center['kind']),
                'system' => in_array($center['code'], self::SYSTEM_CODES, true),
                'entries' => $uses,
                'animals' => (int) ($animals[$center['id']] ?? 0),
                'children' => (int) ($children[$center['id']] ?? 0),
                'deletable' => ! in_array($center['code'], self::SYSTEM_CODES, true)
                    && $uses === 0
                    && ! isset($animals[$center['id']])
                    && ! isset($batches[$center['id']])
                    && ! isset($history[$center['id']])
                    && ! isset($children[$center['id']]),
            ];
        }, Lookups::centers(false));

        return Inertia::render('centers/Manage', [
            'rows' => $rows,
            'kinds' => Tr::all(CostCenter::KINDS),
        ]);
    }

    public function toggle(CostCenter $center): RedirectResponse
    {
        if ($center->is_active && in_array($center->code, ['FARM', 'SHADHARON'], true)) {
            return back()->withErrors(['center' => __('এটি বন্ধ করা যায় না — পুরো হিসাব এর ওপর চলে।')]);
        }

        if ($center->is_active && $center->animals()->where('status', 'active')->exists()) {
            return back()->withErrors(['center' => __('":name"-এ এখনো গরু আছে। আগে সেগুলো অন্য ক্যাটাগরিতে নিন, তারপর বন্ধ করুন।', ['name' => $center->name])]);
        }

        $center->update(['is_active' => ! $center->is_active]);
        Inertia::flash('toast', ['type' => 'success', 'message' => $center->is_active ? __('":name" চালু হলো।', ['name' => $center->name]) : __('":name" বন্ধ — তালিকা থেকে লুকানো থাকবে।', ['name' => $center->name])]);

        return back();
    }

    /** Swaps a category with its neighbour under the same parent. */
    public function move(Request $request, CostCenter $center): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]])['direction'];

        DB::transaction(function () use ($center, $direction) {
            $siblings = CostCenter::query()->where('parent_id', $center->parent_id)->orderBy('sort')->orderBy('name')->get()->values();
            $index = $siblings->search(fn (CostCenter $c) => $c->id === $center->id);
            $swap = $direction === 'up' ? $index - 1 : $index + 1;

            if ($index === false || ! isset($siblings[$swap])) {
                return;
            }

            $order = $siblings->all();
            [$order[$index], $order[$swap]] = [$order[$swap], $order[$index]];

            foreach (array_values($order) as $position => $sibling) {
                $sibling->update(['sort' => ($position + 1) * 10]);
            }
        });

        return back();
    }

    public function destroy(CostCenter $center): RedirectResponse
    {
        $inUse = in_array($center->code, self::SYSTEM_CODES, true)
            || DB::table('journal_lines')->where('cost_center_id', $center->id)->exists()
            || DB::table('stock_movements')->where('cost_center_id', $center->id)->exists()
            || DB::table('animals')->where('cost_center_id', $center->id)->exists()
            || DB::table('batches')->where('cost_center_id', $center->id)->exists()
            || DB::table('animal_placements')->where('cost_center_id', $center->id)->exists()
            || DB::table('milk_records')->where('cost_center_id', $center->id)->exists()
            || DB::table('prepaid_schedules')->where('cost_center_id', $center->id)->exists()
            || CostCenter::query()->where('parent_id', $center->id)->exists();

        if ($inUse) {
            return back()->withErrors(['center' => __('":name"-এ হিসাব বা গরু আছে, তাই মোছা যাবে না — বন্ধ করে লুকিয়ে রাখুন।', ['name' => $center->name])]);
        }

        // Light references (diary, goals, reminders) let go of it rather than block.
        foreach (['farm_notes', 'goals', 'reminders'] as $table) {
            if (DB::getSchemaBuilder()->hasColumn($table, 'cost_center_id')) {
                DB::table($table)->where('cost_center_id', $center->id)->update(['cost_center_id' => null]);
            }
        }

        $name = $center->name;
        $center->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => __('":name" মুছে ফেলা হলো।', ['name' => $name])]);

        return back();
    }

    public function index(Request $request, ReportService $reports): Response
    {
        [$from, $to] = $this->range($request);

        return Inertia::render('centers/Index', [
            'tree' => $reports->centerTree($from, $to),
            'farm' => $reports->farmTotals($from, $to),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'kinds' => Tr::all(CostCenter::KINDS),
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
            'kindLabel' => __(CostCenter::KINDS[$center->kind] ?? $center->kind),
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
            'kinds' => Tr::all(CostCenter::KINDS),
            'parents' => Lookups::centers(false),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $center = CostCenter::query()->create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('নতুন ক্যাটাগরি ":name" যোগ হয়েছে।', ['name' => $center->name])]);

        return to_route('centers.show', $center);
    }

    public function update(Request $request, CostCenter $center): RedirectResponse
    {
        $data = $this->validated($request, $center);

        if (in_array($center->code, self::SYSTEM_CODES, true) && $data['code'] !== $center->code) {
            return back()->withErrors(['code' => __('এই ক্যাটাগরির কোড অ্যাপ নিজে ব্যবহার করে — বদলানো যাবে না।')]);
        }

        if (isset($data['parent_id']) && in_array((int) $data['parent_id'], $center->descendantIds(), true)) {
            return back()->withErrors(['parent_id' => __('একটি ক্যাটাগরি নিজের নিচের ক্যাটাগরির ভেতরে যেতে পারে না।')]);
        }

        if (! ($data['holds_animals'] ?? false) && $center->animals()->where('status', 'active')->exists()) {
            return back()->withErrors(['holds_animals' => __('এখানে এখনো পশু আছে, আগে সেগুলো অন্য ক্যাটাগরিতে নিন।')]);
        }

        $center->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('ক্যাটাগরি আপডেট হয়েছে।')]);

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
            'code.regex' => __('কোডে শুধু ইংরেজি বড় হাতের অক্ষর, সংখ্যা, - বা _ চলবে।'),
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
