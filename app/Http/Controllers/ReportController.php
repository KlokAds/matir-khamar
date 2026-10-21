<?php

namespace App\Http\Controllers;

use App\Models\BudgetHead;
use App\Models\JournalLine;
use App\Services\ReportService;
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
        $result = $reports->farmTotals(Date::parse('2000-01-01'), now()->endOfDay());

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

        Inertia::flash('toast', ['type' => 'success', 'message' => 'বাজেট খাত আপডেট হয়েছে।']);

        return back();
    }
}
