<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Services\PrepaidService;
use App\Services\ReportService;
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
            'trend' => $canSeeMoney ? $reports->monthlyTrend(6, null, true) : [],
            'money' => $canSeeMoney ? $reports->balances(['asset'])->filter(fn ($row) => in_array($row['subtype'], ['cash', 'bank', 'wallet'], true))->values() : [],
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
}
