<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Attachment;
use App\Models\CostCenter;
use App\Models\JournalEntry;
use App\Models\Party;
use App\Services\AccountCodes;
use App\Services\Ledger;
use App\Services\PartyAccounts;
use App\Services\PrepaidService;
use App\Support\Lookups;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use LogicException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Money in and out: expenses, income, transfers, investment, loans, grants,
 * asset purchases and land lease. Every form ends in one balanced voucher.
 */
class EntryController extends Controller
{
    public const FORMS = [
        'expense' => 'খরচ',
        'income' => 'আয় / বিক্রি',
        'supplier_payment' => 'বাকি পরিশোধ (সরবরাহকারী)',
        'customer_receipt' => 'বাকি আদায় (ক্রেতা)',
        'transfer' => 'টাকা স্থানান্তর',
        'asset' => 'জমি / শেড / যন্ত্র ক্রয়',
        'lease' => 'জমি লিজ',
        'investment' => 'বিনিয়োগ গ্রহণ',
        'withdrawal' => 'মূলধন উত্তোলন',
        'loan' => 'ঋণ গ্রহণ',
        'loan_repayment' => 'ঋণের কিস্তি',
        'grant' => 'অনুদান গ্রহণ',
    ];

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'type' => ['nullable', Rule::in(array_keys(JournalEntry::TYPES))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:100'],
            'center' => ['nullable', 'exists:cost_centers,id'],
            'party' => ['nullable', 'exists:parties,id'],
        ]);

        $entries = JournalEntry::query()
            ->with(['lines:id,journal_entry_id,debit', 'creator:id,name'])
            ->withCount('attachments')
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('date', '<=', $to))
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(fn ($inner) => $inner->where('description', 'like', "%{$q}%")->orWhere('number', 'like', "%{$q}%")))
            ->when($filters['party'] ?? null, fn ($query, $party) => $query->where('party_id', $party))
            ->when($filters['center'] ?? null, function ($query, $centerId) {
                $ids = CostCenter::query()->findOrFail((int) $centerId)->descendantIds();
                $query->whereHas('lines', fn ($lines) => $lines->whereIn('cost_center_id', $ids));
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (JournalEntry $entry) => [
                'id' => $entry->id,
                'number' => $entry->number,
                'date' => $entry->date->toDateString(),
                'type' => $entry->type,
                'type_label' => JournalEntry::TYPES[$entry->type] ?? $entry->type,
                'description' => $entry->description,
                'amount' => (int) $entry->lines->sum('debit'),
                'creator' => $entry->creator->name,
                'attachments' => $entry->attachments_count,
                'reversed' => $entry->reversed_by_id !== null,
            ]);

        return Inertia::render('entries/Index', [
            'entries' => $entries,
            'filters' => $filters,
            'types' => JournalEntry::TYPES,
            'centers' => Lookups::centers(false),
        ]);
    }

    public function show(JournalEntry $entry): Response
    {
        $entry->load(['lines.account:id,code,name,type', 'lines.costCenter:id,name', 'lines.animal:id,tag', 'lines.budgetHead:id,code,name', 'attachments', 'party:id,name', 'creator:id,name', 'reversalOf:id,number', 'reversedBy:id,number']);

        return Inertia::render('entries/Show', [
            'entry' => [
                'id' => $entry->id,
                'number' => $entry->number,
                'date' => $entry->date->toDateString(),
                'type' => $entry->type,
                'type_label' => JournalEntry::TYPES[$entry->type] ?? $entry->type,
                'description' => $entry->description,
                'reference' => $entry->reference,
                'party' => $entry->party?->name,
                'creator' => $entry->creator->name,
                'created_at' => $entry->created_at?->toDateTimeString(),
                'reversal_of' => $entry->reversalOf?->only(['id', 'number']),
                'reversed_by' => $entry->reversedBy?->only(['id', 'number']),
                'can_reverse' => $entry->reversed_by_id === null && $entry->type !== 'reversal' && ! str_starts_with($entry->type, 'animal_'),
                'lines' => $entry->lines->map(fn ($line) => [
                    'id' => $line->id,
                    'account' => $line->account->code.' · '.$line->account->name,
                    'center' => $line->costCenter?->name,
                    'animal' => $line->animal?->tag,
                    'budget_head' => $line->budgetHead ? $line->budgetHead->code.' · '.$line->budgetHead->name : null,
                    'debit' => $line->debit,
                    'credit' => $line->credit,
                    'memo' => $line->memo,
                ]),
                'attachments' => $entry->attachments->map(fn (Attachment $file) => [
                    'id' => $file->id,
                    'name' => $file->original_name,
                    'mime' => $file->mime,
                    'size' => $file->size,
                ]),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $form = $request->string('form')->toString();

        return Inertia::render('entries/Create', [
            'form' => array_key_exists($form, self::FORMS) ? $form : 'expense',
            'forms' => self::FORMS,
            'centers' => Lookups::centers(),
            'moneyAccounts' => Lookups::moneyAccounts(),
            'expenseAccounts' => Lookups::accounts('expense'),
            'incomeAccounts' => Lookups::accounts('income'),
            'assetAccounts' => Lookups::assetAccounts(),
            'budgetHeads' => Lookups::budgetHeads(),
            'parties' => Lookups::parties(),
            'partyTypes' => Party::TYPES,
            'animals' => Lookups::activeAnimals(),
            'defaultCenterId' => CostCenter::query()->where('code', 'SHADHARON')->value('id'),
            'maxVoucherKb' => config('khamar.max_voucher_kb'),
        ]);
    }

    public function store(Request $request, Ledger $ledger, PartyAccounts $partyAccounts, PrepaidService $prepaid): RedirectResponse
    {
        $form = $request->validate(['form' => ['required', Rule::in(array_keys(self::FORMS))]])['form'];

        $common = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:60'],
            'party_id' => ['nullable', 'exists:parties,id'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:'.config('khamar.max_voucher_kb')],
        ]);

        $date = $common['date'];
        $options = [
            'party_id' => $common['party_id'] ?? null,
            'reference' => $common['reference'] ?? null,
            'attachments' => $this->attachments($request),
        ];
        $describe = fn (string $fallback) => $common['description'] ?? null ?: $fallback;
        $money = ['required', Rule::exists('accounts', 'id')->whereIn('subtype', Account::MONEY_SUBTYPES)];

        $entry = match ($form) {
            'expense' => $this->postSplit($request, $ledger, 'expense', $date, $describe('খরচ'), $options),
            'income' => $this->postSplit($request, $ledger, 'income', $date, $describe('আয়'), $options),

            'supplier_payment', 'customer_receipt' => (function () use ($request, $ledger, $form, $date, $describe, $options, $money) {
                $data = $request->validate(['money_account_id' => $money, 'amount' => Money::rule(), 'party_id' => ['required', 'exists:parties,id']]);
                $amount = Money::toPoisha($data['amount']);
                $partyAccount = Account::byCode($form === 'supplier_payment' ? AccountCodes::PAYABLE : AccountCodes::RECEIVABLE)->id;
                $lines = $form === 'supplier_payment'
                    ? [['account_id' => $partyAccount, 'debit' => $amount], ['account_id' => $data['money_account_id'], 'credit' => $amount]]
                    : [['account_id' => $data['money_account_id'], 'debit' => $amount], ['account_id' => $partyAccount, 'credit' => $amount]];

                return $ledger->post($form === 'supplier_payment' ? 'payment' : 'receipt', $date, $describe(self::FORMS[$form]), $lines, $options);
            })(),

            'transfer' => (function () use ($request, $ledger, $date, $describe, $options, $money) {
                $data = $request->validate(['from_account_id' => $money, 'to_account_id' => [...$money, 'different:from_account_id'], 'amount' => Money::rule()]);
                $amount = Money::toPoisha($data['amount']);

                return $ledger->post('transfer', $date, $describe('টাকা স্থানান্তর'), [
                    ['account_id' => $data['to_account_id'], 'debit' => $amount],
                    ['account_id' => $data['from_account_id'], 'credit' => $amount],
                ], $options);
            })(),

            'asset' => (function () use ($request, $ledger, $date, $describe, $options, $money) {
                $data = $request->validate([
                    'asset_account_id' => ['required', Rule::exists('accounts', 'id')->whereIn('subtype', ['land', 'fixed_asset'])],
                    'cost_center_id' => ['required', 'exists:cost_centers,id'],
                    'money_account_id' => $money,
                    'amount' => Money::rule(),
                    'budget_head_id' => ['nullable', 'exists:budget_heads,id'],
                ]);
                $amount = Money::toPoisha($data['amount']);

                return $ledger->post('asset', $date, $describe('সম্পদ ক্রয়'), [
                    ['account_id' => $data['asset_account_id'], 'debit' => $amount, 'cost_center_id' => $data['cost_center_id'], 'budget_head_id' => $data['budget_head_id'] ?? null],
                    ['account_id' => $data['money_account_id'], 'credit' => $amount],
                ], $options);
            })(),

            'lease' => (function () use ($request, $prepaid, $date, $describe, $options, $money) {
                $data = $request->validate([
                    'cost_center_id' => ['required', 'exists:cost_centers,id'],
                    'money_account_id' => $money,
                    'amount' => Money::rule(),
                    'months' => ['required', 'integer', 'min:1', 'max:120'],
                    'start_month' => ['required', 'date'],
                    'budget_head_id' => ['nullable', 'exists:budget_heads,id'],
                ]);

                return $prepaid->payLease($date, $describe('জমি লিজ'), Money::toPoisha($data['amount']), (int) $data['months'], $data['start_month'], (int) $data['cost_center_id'], (int) $data['money_account_id'], $options['party_id'], $data['budget_head_id'] ?? null, $options['attachments']);
            })(),

            'investment', 'withdrawal' => (function () use ($request, $ledger, $partyAccounts, $form, $date, $describe, $options, $money) {
                $data = $request->validate(['party_id' => ['required', Rule::exists('parties', 'id')->where('type', 'investor')], 'money_account_id' => $money, 'amount' => Money::rule()]);
                $party = Party::query()->findOrFail((int) $data['party_id']);
                $capital = $partyAccounts->capitalFor($party)->id;
                $amount = Money::toPoisha($data['amount']);

                $lines = $form === 'investment'
                    ? [['account_id' => $data['money_account_id'], 'debit' => $amount], ['account_id' => $capital, 'credit' => $amount]]
                    : [['account_id' => $capital, 'debit' => $amount], ['account_id' => $data['money_account_id'], 'credit' => $amount]];

                return $ledger->post($form, $date, $describe(self::FORMS[$form].' — '.$party->name), $lines, $options);
            })(),

            'loan' => (function () use ($request, $ledger, $partyAccounts, $date, $describe, $options, $money) {
                $data = $request->validate(['party_id' => ['required', Rule::exists('parties', 'id')->where('type', 'lender')], 'money_account_id' => $money, 'amount' => Money::rule()]);
                $party = Party::query()->findOrFail((int) $data['party_id']);
                $amount = Money::toPoisha($data['amount']);

                return $ledger->post('loan', $date, $describe('ঋণ গ্রহণ — '.$party->name), [
                    ['account_id' => $data['money_account_id'], 'debit' => $amount],
                    ['account_id' => $partyAccounts->loanFor($party)->id, 'credit' => $amount],
                ], $options);
            })(),

            'loan_repayment' => (function () use ($request, $ledger, $partyAccounts, $date, $describe, $options, $money) {
                $data = $request->validate([
                    'party_id' => ['required', Rule::exists('parties', 'id')->where('type', 'lender')],
                    'money_account_id' => $money,
                    'principal' => Money::rule(false),
                    'interest' => Money::rule(false),
                    'cost_center_id' => ['nullable', 'exists:cost_centers,id'],
                ]);
                $party = Party::query()->findOrFail((int) $data['party_id']);
                $principal = Money::toPoisha($data['principal'] ?? 0);
                $interest = Money::toPoisha($data['interest'] ?? 0);

                if ($principal + $interest === 0) {
                    throw ValidationException::withMessages(['principal' => 'আসল বা সুদের টাকা দিন।']);
                }

                $lines = [];
                $principal > 0 && $lines[] = ['account_id' => $partyAccounts->loanFor($party)->id, 'debit' => $principal];
                $interest > 0 && $lines[] = ['account_id' => Account::byCode(AccountCodes::LOAN_INTEREST)->id, 'debit' => $interest, 'cost_center_id' => $data['cost_center_id'] ?? null];
                $lines[] = ['account_id' => $data['money_account_id'], 'credit' => $principal + $interest];

                return $ledger->post('loan_repayment', $date, $describe('ঋণের কিস্তি — '.$party->name), $lines, $options);
            })(),

            'grant' => (function () use ($request, $ledger, $date, $describe, $options, $money) {
                $data = $request->validate(['money_account_id' => $money, 'amount' => Money::rule()]);
                $amount = Money::toPoisha($data['amount']);

                return $ledger->post('grant', $date, $describe('উদ্যোগ অনুদান গ্রহণ'), [
                    ['account_id' => $data['money_account_id'], 'debit' => $amount],
                    ['account_id' => Account::byCode(AccountCodes::GRANT)->id, 'credit' => $amount],
                ], $options);
            })(),

            // FORMS and this match list the same keys; validation keeps anything else out.
            default => throw new LogicException("Unknown entry form {$form}."),
        };

        Inertia::flash('toast', ['type' => 'success', 'message' => "এন্ট্রি {$entry->number} সংরক্ষিত হয়েছে।"]);

        return $request->boolean('stay') ? back() : to_route('entries.show', $entry);
    }

    /**
     * Expense or income split over several lines, each with its own category.
     *
     * @param  array<string, mixed>  $options
     */
    private function postSplit(Request $request, Ledger $ledger, string $type, string $date, string $description, array $options): JournalEntry
    {
        $accountType = $type === 'expense' ? 'expense' : 'income';
        $data = $request->validate([
            'settle' => ['required', Rule::in(['money', 'credit'])],
            'money_account_id' => ['required_if:settle,money', 'nullable', Rule::exists('accounts', 'id')->whereIn('subtype', Account::MONEY_SUBTYPES)],
            'party_id' => ['required_if:settle,credit', 'nullable', 'exists:parties,id'],
            'lines' => ['required', 'array', 'min:1', 'max:30'],
            'lines.*.account_id' => ['required', Rule::exists('accounts', 'id')->where('type', $accountType)],
            'lines.*.cost_center_id' => ['required', 'exists:cost_centers,id'],
            'lines.*.amount' => Money::rule(),
            'lines.*.budget_head_id' => ['nullable', 'exists:budget_heads,id'],
            'lines.*.animal_id' => ['nullable', 'exists:animals,id'],
            'lines.*.memo' => ['nullable', 'string', 'max:255'],
        ], [
            'money_account_id.required_if' => 'কোন হিসাব থেকে টাকা গেল/এল তা বাছুন।',
            'party_id.required_if' => 'বাকির জন্য কার সাথে লেনদেন তা বাছুন।',
        ]);

        $total = 0;
        $lines = [];

        foreach ($data['lines'] as $line) {
            $amount = Money::toPoisha($line['amount']);
            $total += $amount;
            $lines[] = [
                'account_id' => (int) $line['account_id'],
                'cost_center_id' => (int) $line['cost_center_id'],
                'budget_head_id' => $type === 'expense' ? ($line['budget_head_id'] ?? null) : null,
                'animal_id' => $line['animal_id'] ?? null,
                'memo' => $line['memo'] ?? null,
                $type === 'expense' ? 'debit' : 'credit' => $amount,
            ];
        }

        $counterAccount = $data['settle'] === 'money'
            ? (int) $data['money_account_id']
            : Account::byCode($type === 'expense' ? AccountCodes::PAYABLE : AccountCodes::RECEIVABLE)->id;

        $lines[] = ['account_id' => $counterAccount, $type === 'expense' ? 'credit' : 'debit' => $total];

        return $ledger->post($type, $date, $description, $lines, $options);
    }

    public function reverse(Request $request, JournalEntry $entry, Ledger $ledger): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:200'],
            'date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $reversal = $ledger->reverse($entry->load('lines'), $data['date'], $data['reason']);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$entry->number} বাতিল হয়েছে (উল্টো এন্ট্রি {$reversal->number})।"]);

        return to_route('entries.show', $reversal);
    }

    public function attach(Request $request, JournalEntry $entry, Ledger $ledger): RedirectResponse
    {
        $request->validate([
            'attachments' => ['required', 'array', 'min:1', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:'.config('khamar.max_voucher_kb')],
        ]);

        foreach ($this->attachments($request) as $file) {
            $ledger->attach($entry, $file);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'ভাউচার যোগ হয়েছে।']);

        return back();
    }

    public function attachment(Attachment $attachment): StreamedResponse
    {
        Gate::authorize('view-reports');

        return Storage::disk('local')->response($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline; filename="'.addslashes($attachment->original_name).'"',
        ]);
    }
}
