<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Animal;
use App\Models\AnimalPlacement;
use App\Models\Batch;
use App\Models\Breeding;
use App\Models\BudgetHead;
use App\Models\CostCenter;
use App\Models\FarmNote;
use App\Models\Goal;
use App\Models\JournalLine;
use App\Services\HerdCare;
use App\Services\ReportService;
use App\Services\UsageReport;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports): Response
    {
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $from = $request->filled('from') ? Date::parse($request->string('from')) : now()->startOfYear();
        $to = $request->filled('to') ? Date::parse($request->string('to')) : now()->endOfMonth();

        $balances = $reports->balances()->filter(fn ($row) => $row['balance'] !== 0 && in_array($row['type'], ['asset', 'liability', 'equity'], true))->values();
        // Every income and expense ever posted, own produce included, so the balance sheet always balances.
        $result = $reports->accountTotals(Date::parse('2000-01-01'), now()->endOfDay(), null);
        $result['profit'] = $result['income'] - $result['expense'];

        return Inertia::render('reports/Index', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'tree' => $reports->centerTree($from, $to),
            'farm' => $reports->farmTotals($from, $to),
            'accounts' => $reports->accountTotals($from, $to, null)['accounts'],
            'trend' => $reports->monthlyTrend(12, null, true),
            'balances' => $balances,
            'retained' => $result['profit'],
        ]);
    }

    /** Feed, grass and fertiliser by kilo: from how much land, to which category, for how many animals. */
    public function usage(Request $request, UsageReport $usage): Response
    {
        $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = Date::parse(($request->string('month')->toString() ?: now()->format('Y-m')).'-01');

        return Inertia::render('reports/Usage', [
            'month' => $month->format('Y-m'),
            'items' => $usage->items($month->startOfMonth(), $month->endOfMonth()),
        ]);
    }

    /** One month on one page: money, milk, herd, feed and what was learned, ready to print or share. */
    public function monthly(Request $request, ReportService $reports, UsageReport $usage, HerdCare $care): Response
    {
        $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $start = Date::parse(($request->string('month')->toString() ?: now()->format('Y-m')).'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $prevStart = $start->copy()->subMonthNoOverflow()->startOfMonth();
        $prevEnd = $prevStart->copy()->endOfMonth();

        // Top-level categories and their direct sub-categories, only those that moved money.
        $projects = [];
        foreach ($reports->centerTree($start, $end) as $node) {
            $nodes = [[$node, 0], ...array_map(fn (array $child) => [$child, 1], $node['children'])];
            foreach ($nodes as [$row, $depth]) {
                if ($row['income'] !== 0 || $row['expense'] !== 0) {
                    $projects[] = ['id' => $row['id'], 'name' => $row['name'], 'kind' => $row['kind'], 'depth' => $depth, 'income' => $row['income'], 'expense' => $row['expense'], 'profit' => $row['profit']];
                }
            }
        }

        $expenses = collect($reports->accountTotals($start, $end, null)['accounts'])
            ->filter(fn (array $row) => $row['type'] === 'expense' && ! $row['internal'])
            ->sortByDesc('amount')
            ->take(8)
            ->map(fn (array $row) => ['id' => $row['id'], 'name' => $row['name'], 'amount' => $row['amount']])
            ->values();

        $money = $reports->balances(['asset'])
            ->filter(fn (array $row) => in_array($row['subtype'], Account::MONEY_SUBTYPES, true))
            ->map(fn (array $row) => ['id' => $row['id'], 'name' => $row['name'], 'subtype' => $row['subtype'], 'balance' => $row['balance']])
            ->values();

        // Who was on the farm on the last day of the month, and in which category.
        $herdAnimals = Animal::query()
            ->with('placements')
            ->whereDate('acquired_on', '<=', $end)
            ->where(fn ($query) => $query->whereNull('disposed_on')->orWhereDate('disposed_on', '>', $end))
            ->get();
        $centerNames = CostCenter::query()->pluck('name', 'id');
        $herd = $herdAnimals
            ->groupBy(function (Animal $animal) use ($end): int {
                $placement = $animal->placements->first(fn (AnimalPlacement $p) => $p->from_date->lessThanOrEqualTo($end) && ($p->to_date === null || $p->to_date->greaterThan($end)));

                return $placement !== null ? $placement->cost_center_id : $animal->cost_center_id;
            })
            ->map(fn ($group, $centerId) => ['center' => $centerNames[$centerId] ?? '—', 'count' => $group->count()])
            ->sortByDesc('count')
            ->values();

        $range = [$start->toDateString(), $end->toDateString()];
        $bought = Animal::query()->where('acquisition', 'purchase')->whereBetween('acquired_on', $range)->orderBy('acquired_on')->get()
            ->map(fn (Animal $animal) => ['id' => $animal->id, 'tag' => $animal->tag, 'name' => $animal->name, 'date' => $animal->acquired_on->toDateString(), 'amount' => $animal->purchase_cost])
            ->values();
        $disposed = fn (string $status) => Animal::query()->where('status', $status)->whereBetween('disposed_on', $range)->orderBy('disposed_on')->get()
            ->map(fn (Animal $animal) => [
                'id' => $animal->id,
                'tag' => $animal->tag,
                'name' => $animal->name,
                'date' => $animal->disposed_on?->toDateString(),
                // A sale shows what it fetched; a death shows the book value lost.
                'amount' => $status === 'sold' ? (int) $animal->sale_amount : $animal->book_value,
            ])
            ->values();

        $batches = Batch::query()->whereNull('closed_on')->whereDate('started_on', '<=', $end)->orderBy('started_on')->get()
            ->map(function (Batch $batch) use ($care): array {
                $card = $care->batchCard($batch);

                return ['id' => $card['id'], 'label' => $card['label'], 'center' => $card['center'], 'span' => $card['span'], 'head' => $card['head'], 'spent' => $card['spent'], 'avg_gain' => $card['avg_gain']];
            })
            ->values();

        // Calvings coming within 60 days of the month end (or of today, for the running month).
        $reference = $end->lessThan(now()) ? $end->copy()->startOfDay() : now()->startOfDay();
        $due = Breeding::query()
            ->with('animal:id,tag,name,status')
            ->whereNull('calved_on')
            ->where('result', '!=', 'empty')
            ->whereHas('animal', fn ($query) => $query->where('status', 'active'))
            ->whereDate('expected_on', '<=', $reference->copy()->addDays(60))
            ->orderBy('expected_on')
            ->get()
            ->map(function (Breeding $breeding) use ($care): array {
                $card = $care->breedingCard($breeding);

                return ['id' => $card['id'], 'tag' => $card['tag'], 'name' => $card['name'], 'expected_on' => $card['expected_on'], 'days_left' => $card['days_left'], 'result_label' => $card['result_label'], 'months' => $card['months']];
            })
            ->values();

        $feed = collect($usage->items($start, $end))
            ->filter(fn (array $row) => $row['used_qty'] > 0)
            ->sortByDesc('used_value')
            ->map(fn (array $row) => ['id' => $row['id'], 'name' => $row['name'], 'used_qty' => $row['used_qty'], 'unit' => $row['unit'], 'used_value' => $row['used_value']])
            ->values();

        $notes = FarmNote::query()->whereIn('kind', ['mistake', 'lesson'])->whereBetween('date', $range)->orderBy('date')->get()
            ->map(fn (FarmNote $note) => ['id' => $note->id, 'date' => $note->date->toDateString(), 'kind' => $note->kind, 'title' => $note->title, 'body' => $note->body])
            ->values();

        $goals = Goal::query()->orderByRaw('achieved_at IS NOT NULL')->orderBy('due_on')->get()
            ->map(fn (Goal $goal) => [
                'id' => $goal->id,
                'title' => $goal->title,
                'unit' => $goal->unit,
                'target_value' => $goal->target_value,
                'current_value' => $goal->current_value,
                'due_on' => $goal->due_on?->toDateString(),
                'achieved' => $goal->achieved_at !== null,
                'progress' => $goal->progress(),
            ])
            ->values();

        return Inertia::render('reports/Monthly', [
            'month' => $start->format('Y-m'),
            'previousMonth' => $prevStart->format('Y-m'),
            'farm' => $reports->farmTotals($start, $end),
            'previous' => $reports->farmTotals($prevStart, $prevEnd),
            'projects' => $projects,
            'expenses' => $expenses,
            'money' => $money,
            'milk' => ['liters' => round($reports->milkLiters($start, $end), 1), 'previous' => round($reports->milkLiters($prevStart, $prevEnd), 1)],
            'herd' => $herd,
            'herdTotal' => $herdAnimals->count(),
            'animals' => ['bought' => $bought, 'sold' => $disposed('sold'), 'dead' => $disposed('dead')],
            'batches' => $batches,
            'due' => $due,
            'feed' => $feed,
            'notes' => $notes,
            'goals' => $goals,
        ]);
    }

    public function budget(ReportService $reports): Response
    {
        return Inertia::render('reports/Budget', [
            'heads' => $reports->budget(),
            'missing' => JournalLine::query()
                ->with('entry:id,number,date,description')
                ->whereNotNull('budget_head_id')
                ->whereHas('entry', fn ($query) => $query->whereNull('reversed_by_id')->where('type', '!=', 'reversal')->doesntHave('attachments'))
                ->get()
                ->unique('journal_entry_id')
                ->map(fn (JournalLine $line) => [
                    'entry_id' => $line->journal_entry_id,
                    'number' => $line->entry->number,
                    'date' => $line->entry->date->toDateString(),
                    'description' => $line->entry->description,
                ])
                ->values(),
        ]);
    }

    public function updateBudgetHead(Request $request, BudgetHead $head): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'details' => ['nullable', 'string', 'max:255'],
            'approved_amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $head->update([...$data, 'approved_amount' => Money::toPoisha($data['approved_amount'])]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('বাজেট খাত আপডেট হয়েছে।')]);

        return back();
    }
}
