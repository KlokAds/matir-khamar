<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Services\DebtBook;
use App\Services\ReportService;
use App\Services\Udyog;
use App\Support\Lookups;
use App\Support\Money;
use App\Support\Tr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** The government Udyog loan: the application and its plan, and once approved, the loan itself. */
class UdyogController extends Controller
{
    public function index(Udyog $udyog, ReportService $reports, DebtBook $debts): Response
    {
        $terms = $udyog->terms();
        $debt = $terms['debt_id'] ? Debt::query()->find($terms['debt_id']) : null;

        return Inertia::render('udyog/Index', [
            'terms' => $terms,
            'statuses' => Tr::all(Udyog::STATUSES),
            'approved' => Udyog::approved(),
            'schedule' => $udyog->schedule($terms['loan'], $terms['rate'], $terms['years'], $terms['approved_on']),
            'heads' => $reports->budget(),
            'debt' => $debt ? $debts->card($debt) : null,
            'moneyAccounts' => Lookups::moneyAccounts(),
        ]);
    }

    public function update(Request $request, Udyog $udyog): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Udyog::STATUSES))],
            'loan' => Money::rule(),
            'rate' => ['required', 'numeric', 'min:0', 'max:30'],
            'years' => ['required', 'integer', 'min:1', 'max:20'],
            'grant' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'applied_on' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $udyog->saveTerms([
            'status' => $data['status'],
            'loan' => Money::toPoisha($data['loan']),
            'rate' => (float) $data['rate'],
            'years' => (int) $data['years'],
            'grant' => Money::toPoisha($data['grant'] ?? 0),
            'applied_on' => $data['applied_on'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get('উদ্যোগ ঋণের তথ্য রাখা হলো।')]);

        return back();
    }

    public function approve(Request $request, Udyog $udyog): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => Money::rule(),
            'money_account_id' => ['required', 'exists:accounts,id'],
        ]);

        $udyog->approve($data['date'], Money::toPoisha($data['amount']), (int) $data['money_account_id']);
        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get('উদ্যোগ ঋণ অনুমোদন লেখা হলো — "ধার ও জামানত"-এ কিস্তিসহ ঋণ খোলা হয়েছে।')]);

        return back();
    }
}
