<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\JournalLine;
use App\Models\Party;
use App\Models\PeriodLock;
use App\Models\User;
use App\Services\AccountCodes;
use App\Support\BanglaDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/** Users, chart of accounts, parties, month locks and the audit trail. */
class SetupController extends Controller
{
    public function users(Request $request): Response
    {
        return Inertia::render('setup/Users', [
            // The owner's super account is invisible to everyone but itself.
            'users' => User::query()->when(! $request->user()->is_super, fn ($query) => $query->where('is_super', false))->orderBy('name')->get()->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'is_active' => $user->is_active,
                'two_factor' => $user->hasTwoFactorEnabled(),
                'last_login_at' => $user->last_login_at?->toDateTimeString(),
                'last_login_ip' => $user->last_login_ip,
            ]),
            'roles' => Role::options(),
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'role' => ['required', Rule::enum(Role::class)],
            'password' => ['required', Password::min(10)->letters()->numbers()],
        ]);

        User::query()->create([...$data, 'is_active' => true])->forceFill(['email_verified_at' => now()])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'নতুন ব্যবহারকারী যোগ হয়েছে। প্রথম লগইনে পাসওয়ার্ড বদলাতে বলুন।']);

        return back();
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is_super && ! $request->user()->is_super, 404);

        $data = $request->validate([
            'role' => ['required', Rule::enum(Role::class)],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', Password::min(10)->letters()->numbers()],
            'reset_two_factor' => ['boolean'],
        ]);

        if ($user->is($request->user()) && ($data['role'] !== Role::Admin->value || ! $data['is_active'])) {
            return back()->withErrors(['role' => 'নিজের অ্যাডমিন ক্ষমতা নিজে সরানো যাবে না।']);
        }

        $user->role = Role::from($data['role']);
        $user->is_active = (bool) $data['is_active'];

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        if ($data['reset_two_factor'] ?? false) {
            $user->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null]);
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'ব্যবহারকারী আপডেট হয়েছে।']);

        return back();
    }

    public function accounts(): Response
    {
        $sums = JournalLine::query()->toBase()->groupBy('account_id')->selectRaw('account_id, SUM(debit) as d, SUM(credit) as c')->get()->keyBy('account_id');

        return Inertia::render('setup/Accounts', [
            'accounts' => Account::query()->orderBy('code')->get()->map(function (Account $account) use ($sums) {
                $row = $sums->get($account->id);
                $net = $row ? (int) $row->d - (int) $row->c : 0;

                return [
                    ...$account->only(['id', 'code', 'name', 'type', 'subtype', 'parent_id', 'is_system', 'is_active']),
                    'balance' => $account->isDebitNormal() ? $net : -$net,
                    'used' => $row !== null,
                ];
            }),
            'types' => Account::TYPES,
            'parents' => Account::query()->whereNull('parent_id')->orderBy('code')->get(['id', 'code', 'name', 'type']),
        ]);
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'parent_id' => ['required', Rule::exists('accounts', 'id')->whereNull('parent_id')],
            'code' => ['required', 'string', 'max:20', 'regex:/^[0-9-]+$/', 'unique:accounts,code'],
            'name' => ['required', 'string', 'max:120'],
            'subtype' => ['nullable', Rule::in(['cash', 'bank', 'wallet', 'inventory', 'fixed_asset', 'land'])],
        ]);

        $parent = Account::query()->findOrFail((int) $data['parent_id']);

        Account::query()->create([...$data, 'type' => $parent->type, 'is_system' => false]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'নতুন হিসাব খাত যোগ হয়েছে।']);

        return back();
    }

    public function updateAccount(Request $request, Account $account): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['required', 'boolean'],
        ]);

        $protected = [AccountCodes::CASH, AccountCodes::PAYABLE, AccountCodes::RECEIVABLE, AccountCodes::LIVESTOCK, AccountCodes::INTERNAL_INCOME];

        if (! $data['is_active'] && in_array($account->code, $protected, true)) {
            return back()->withErrors(['is_active' => 'এই খাতটি সিস্টেম নিজে ব্যবহার করে, বন্ধ করা যাবে না।']);
        }

        $account->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'হিসাব খাত আপডেট হয়েছে।']);

        return back();
    }

    public function parties(): Response
    {
        $payable = Account::byCode(AccountCodes::PAYABLE)->id;
        $receivable = Account::byCode(AccountCodes::RECEIVABLE)->id;

        $dues = JournalLine::query()
            ->toBase()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->whereIn('journal_lines.account_id', [$payable, $receivable])
            ->whereNotNull('journal_entries.party_id')
            ->groupBy('journal_entries.party_id', 'journal_lines.account_id')
            ->selectRaw('journal_entries.party_id, journal_lines.account_id, SUM(journal_lines.debit) as d, SUM(journal_lines.credit) as c')
            ->get();

        $capitalAndLoans = Account::query()->whereNotNull('party_id')->get()->keyBy('party_id');

        return Inertia::render('setup/Parties', [
            'parties' => Party::query()->orderBy('type')->orderBy('name')->get()->map(function (Party $party) use ($dues, $payable, $capitalAndLoans) {
                $rows = $dues->where('party_id', $party->id);
                $weOwe = (int) $rows->where('account_id', $payable)->sum(fn ($row) => $row->c - $row->d);
                $theyOwe = (int) $rows->where('account_id', '!=', $payable)->sum(fn ($row) => $row->d - $row->c);
                $linked = $capitalAndLoans->get($party->id);

                return [
                    ...$party->only(['id', 'type', 'name', 'phone', 'address', 'notes', 'is_active']),
                    'type_label' => Party::TYPES[$party->type] ?? $party->type,
                    'we_owe' => $weOwe,
                    'they_owe' => $theyOwe,
                    'linked_balance' => $linked?->balance(),
                    'linked_label' => $linked ? ($linked->subtype === 'capital' ? 'মোট মূলধন' : 'ঋণ বাকি') : null,
                ];
            }),
            'types' => Party::TYPES,
        ]);
    }

    public function storeParty(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Party::TYPES))],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $party = Party::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$party->name} যোগ হয়েছে।"]);

        return back();
    }

    public function updateParty(Request $request, Party $party): RedirectResponse
    {
        $party->update($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'আপডেট হয়েছে।']);

        return back();
    }

    public function periods(): Response
    {
        return Inertia::render('setup/Periods', [
            'locks' => PeriodLock::query()->with('locker:id,name')->orderByDesc('month')->get()->map(fn (PeriodLock $lock) => [
                'id' => $lock->id,
                'month' => $lock->month->format('Y-m'),
                'label' => BanglaDate::month($lock->month),
                'by' => $lock->locker->name,
                'at' => $lock->created_at?->toDateTimeString(),
            ]),
        ]);
    }

    public function lockPeriod(Request $request): RedirectResponse
    {
        $data = $request->validate(['month' => ['required', 'date_format:Y-m']]);
        $month = Date::parse($data['month'].'-01');

        if ($month->greaterThanOrEqualTo(now()->startOfMonth())) {
            return back()->withErrors(['month' => 'চলতি বা ভবিষ্যৎ মাস লক করা যায় না; মাস শেষ হলে লক করুন।']);
        }

        PeriodLock::query()->firstOrCreate(['month' => $month->toDateString()], ['locked_by' => $request->user()->id]);

        Inertia::flash('toast', ['type' => 'success', 'message' => BanglaDate::month($month).' লক হয়েছে।']);

        return back();
    }

    public function unlockPeriod(Request $request, PeriodLock $lock): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'min:5', 'max:200']]);

        AuditLog::record('period_unlocked', $lock, ['month' => $lock->month->toDateString()], ['reason' => $request->string('reason')->toString()]);
        $lock->delete();

        Inertia::flash('toast', ['type' => 'warning', 'message' => 'মাসের লক খোলা হয়েছে। কারণ অডিট লগে রাখা আছে।']);

        return back();
    }

    public function audit(Request $request): Response
    {
        return Inertia::render('setup/Audit', [
            'logs' => AuditLog::query()->with('user:id,name')->latest('id')->paginate(50)->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'at' => $log->created_at->toDateTimeString(),
                'user' => $log->user?->name,
                'action' => $log->action,
                'model' => $log->auditable_type,
                'model_id' => $log->auditable_id,
                'old' => $log->old_values,
                'new' => $log->new_values,
                'ip' => $log->ip,
            ]),
        ]);
    }
}
