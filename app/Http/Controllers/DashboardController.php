<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Animal;
use App\Models\CostCenter;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\MilkRecord;
use App\Services\PrepaidService;
use App\Services\ReportService;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ReportService $reports, PrepaidService $prepaid): Response
    {
        // Lease months that have started are moved into their category before anything is shown.
        $prepaid->amortizeDue();

        $from = now()->startOfMonth();
        $to = now()->endOfMonth();
        $canSeeMoney = $request->user()->can('view-reports');

        return Inertia::render('Dashboard', [
            'canSeeMoney' => $canSeeMoney,
            'month' => $from->format('Y-m'),
            'farm' => $canSeeMoney ? $reports->farmTotals($from, $to) : null,
            'farmYear' => $canSeeMoney ? $reports->farmTotals(now()->startOfYear(), $to) : null,
            'tree' => $canSeeMoney ? $reports->centerTree($from, $to) : [],
            'projects' => $canSeeMoney ? $this->projects($reports, $from, $to) : [],
            'trend' => $canSeeMoney ? $reports->monthlyTrend(6, null, true) : [],
            'money' => $canSeeMoney ? $reports->balances(['asset'])->filter(fn ($row) => in_array($row['subtype'], Account::MONEY_SUBTYPES, true))->values() : [],
            'milkThisMonth' => $reports->milkLiters($from, $to),
            'animals' => Animal::query()->where('status', 'active')->with('costCenter:id,name')->get()
                ->groupBy('cost_center_id')
                ->map(fn ($group) => ['center' => $group->first()->costCenter->name, 'count' => $group->count()])
                ->values(),
            'lowStock' => Item::query()->where('is_active', true)->whereNotNull('reorder_level')->whereColumn('qty_on_hand', '<=', 'reorder_level')->get(['id', 'name', 'unit', 'qty_on_hand']),
            'recent' => $canSeeMoney
                ? JournalEntry::query()->with('lines:id,journal_entry_id,debit')->latest('id')->limit(8)->get()->map(fn (JournalEntry $entry) => [
                    'id' => $entry->id,
                    'number' => $entry->number,
                    'date' => $entry->date->toDateString(),
                    'type' => JournalEntry::TYPES[$entry->type] ?? $entry->type,
                    'description' => $entry->description,
                    'amount' => (int) $entry->lines->sum('debit'),
                    'reversed' => $entry->reversed_by_id !== null,
                ])
                : [],
        ]);
    }

    /**
     * One card per working category (Gavi, Bokna, Motataja, Ghas jomi...): its own month and
     * year, so each project is read on its own before the farm total.
     *
     * @return list<array<string, mixed>>
     */
    private function projects(ReportService $reports, CarbonInterface $from, CarbonInterface $to): array
    {
        $month = $reports->centerTotals($from, $to);
        $year = $reports->centerTotals($from->startOfYear(), $to);
        $herd = Animal::query()->where('status', 'active')->selectRaw('cost_center_id, COUNT(*) as n')->groupBy('cost_center_id')->pluck('n', 'cost_center_id');
        $milk = MilkRecord::query()->whereBetween('date', [$from->toDateString(), $to->toDateString().' 23:59:59'])->selectRaw('cost_center_id, SUM(liters) as l')->groupBy('cost_center_id')->pluck('l', 'cost_center_id');
        // A category's own figures include its internal transfers (own grass in, fed out).
        $income = fn (array $totals, int $id): int => (int) (($totals[$id]['income'] ?? 0) + ($totals[$id]['internal_income'] ?? 0));
        $expense = fn (array $totals, int $id): int => (int) (($totals[$id]['expense'] ?? 0) + ($totals[$id]['internal_expense'] ?? 0));

        $centers = CostCenter::query()->where('is_active', true)->whereNotIn('kind', ['group', 'overhead'])->orderBy('sort')->get();
        $cards = [];

        foreach ($centers as $center) {
            $cards[] = [
                'id' => $center->id,
                'name' => $center->name,
                'kind' => $center->kind,
                'income' => $income($month, $center->id),
                'expense' => $expense($month, $center->id),
                'profit' => $income($month, $center->id) - $expense($month, $center->id),
                'year_profit' => $income($year, $center->id) - $expense($year, $center->id),
                'animals' => $center->holds_animals ? (int) ($herd[$center->id] ?? 0) : null,
                'milk' => (float) ($milk[$center->id] ?? 0),
            ];
        }

        return $cards;
    }
}
