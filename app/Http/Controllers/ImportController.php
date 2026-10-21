<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ImportedRecord;
use App\Models\ImportMapping;
use App\Models\Setting;
use App\Services\CraterImporter;
use App\Support\Lookups;
use App\Support\Tr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/** billing.klokads.com (Crater) -> farm books. */
class ImportController extends Controller
{
    public function index(Request $request, CraterImporter $importer): Response
    {
        $status = $request->string('status')->toString();

        return Inertia::render('import/Index', [
            'configured' => $importer->configured(),
            'connection' => $this->connectionForm(),
            'status' => array_key_exists($status, ImportedRecord::STATUSES) ? $status : null,
            'statuses' => Tr::all(ImportedRecord::STATUSES),
            'counts' => ImportedRecord::query()->where('source', CraterImporter::SOURCE)->selectRaw('status, COUNT(*) as n, SUM(amount) as total')->groupBy('status')->get()->keyBy('status'),
            'mappings' => ImportMapping::query()->where('source', CraterImporter::SOURCE)->orderBy('kind')->orderBy('external_name')->get()->map(fn (ImportMapping $map) => [
                ...$map->only(['id', 'kind', 'external_id', 'external_name', 'account_id', 'cost_center_id', 'item_id', 'ignore']),
                'complete' => $map->isComplete(),
            ]),
            'kinds' => Tr::all(ImportMapping::KINDS),
            'accounts' => [
                'expense_category' => CraterImporter::accountsFor('expense_category'),
                'customer' => CraterImporter::accountsFor('customer'),
                'payment_method' => CraterImporter::accountsFor('payment_method'),
            ],
            'centers' => Lookups::centers(),
            'items' => Lookups::items(),
            'records' => ImportedRecord::query()
                ->where('source', CraterImporter::SOURCE)
                ->when(array_key_exists($status, ImportedRecord::STATUSES), fn ($query) => $query->where('status', $status))
                ->orderByDesc('external_date')
                ->orderByDesc('id')
                ->paginate(40)
                ->withQueryString()
                ->through(fn (ImportedRecord $record) => [
                    'id' => $record->id,
                    'table' => $record->external_table,
                    'external_id' => $record->external_id,
                    'number' => $record->external_number,
                    'date' => $record->external_date->toDateString(),
                    'amount' => $record->amount,
                    'qty' => $record->qty,
                    'category_id' => $record->raw['expense_category_id'] ?? null,
                    'notes' => mb_substr(strip_tags((string) ($record->raw['notes'] ?? '')), 0, 120),
                    'status' => $record->status,
                    'problem' => $record->problem,
                    'entry_id' => $record->journal_entry_id,
                ]),
            'reconcile' => $importer->reconcile(),
            'connectionTest' => session('connectionTest'),
        ]);
    }

    /**
     * What the form may show. The password never leaves the server; the page
     * only learns whether one is saved.
     *
     * @return array<string, mixed>
     */
    private function connectionForm(): array
    {
        $saved = Setting::query()->where('key', 'like', 'crater.%')->get()->keyBy('key');
        $value = fn (string $key) => $saved->get("crater.{$key}")?->value;

        return [
            'source' => $value('database') ? 'app' : (config('database.connections.crater.database') ? 'env' : 'none'),
            'host' => $value('host') ?? '',
            'port' => $value('port') ?? '3306',
            'database' => $value('database') ?? '',
            'username' => $value('username') ?? '',
            'company_id' => $value('company_id') ?? '',
            'password_set' => filled($value('password')),
            'updated_at' => $saved->max('updated_at')?->toDateTimeString(),
        ];
    }

    public function saveConnection(Request $request, CraterImporter $importer): RedirectResponse
    {
        $data = $request->validate([
            'host' => ['required', 'string', 'max:190', 'regex:/^[A-Za-z0-9.\-]+$/'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'database' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'username' => ['required', 'string', 'max:64'],
            'password' => ['nullable', 'string', 'max:190'],
            'company_id' => ['nullable', 'integer', 'min:1'],
            'current_password' => ['required', 'current_password'],
        ], [
            'current_password.current_password' => __('আপনার নিজের লগইন পাসওয়ার্ড মেলেনি।'),
            'host.regex' => __('হোস্টে শুধু অক্ষর, সংখ্যা, ডট ও হাইফেন চলবে (যেমন localhost বা 127.0.0.1)।'),
            'database.regex' => __('ডাটাবেসের নামে শুধু অক্ষর, সংখ্যা, _ ও - চলবে।'),
        ]);

        foreach (['host', 'port', 'database', 'username', 'company_id'] as $key) {
            Setting::put("crater.{$key}", isset($data[$key]) ? (string) $data[$key] : null);
        }

        // An empty password box keeps the saved password.
        if (filled($data['password'] ?? null)) {
            Setting::put('crater.password', $data['password']);
        }

        AuditLog::record('billing_connection_saved', null, null, ['host' => $data['host'], 'database' => $data['database'], 'username' => $data['username'], 'password_changed' => filled($data['password'] ?? null)]);

        $importer->refreshSettings();
        $test = $importer->testConnection();

        Inertia::flash('toast', ['type' => $test['ok'] ? 'success' : 'warning', 'message' => Tr::get('সংযোগের তথ্য রাখা হয়েছে।').' '.$test['message']]);

        return back()->with('connectionTest', $test);
    }

    public function testConnection(CraterImporter $importer): RedirectResponse
    {
        if (! $importer->configured()) {
            return back()->withErrors(['import' => __('আগে সংযোগের তথ্য দিন।')]);
        }

        return back()->with('connectionTest', $importer->testConnection());
    }

    public function forgetConnection(Request $request): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']], ['current_password.current_password' => __('আপনার নিজের লগইন পাসওয়ার্ড মেলেনি।')]);

        Setting::query()->where('key', 'like', 'crater.%')->delete();
        AuditLog::record('billing_connection_removed', null, null, null);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('বিলিং সংযোগের তথ্য মুছে ফেলা হয়েছে।')]);

        return back();
    }

    public function fetch(CraterImporter $importer): RedirectResponse
    {
        if (! $importer->configured()) {
            return back()->withErrors(['import' => __('বিলিং ডাটাবেসের সংযোগ (.env-এ CRATER_DB_*) এখনো দেওয়া হয়নি।')]);
        }

        try {
            $counts = $importer->fetch();
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['import' => __('বিলিং ডাটাবেসে সংযোগ হয়নি। সংযোগের তথ্য দেখুন।')]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('নতুন :newটি, বদলানো :changedটি, আগের মতো :unchangedটি।', ['new' => $counts['new'], 'changed' => $counts['changed'], 'unchanged' => $counts['unchanged']])]);

        return back();
    }

    public function post(CraterImporter $importer): RedirectResponse
    {
        $result = $importer->post();

        Inertia::flash('toast', ['type' => $result['failed'] > 0 ? 'warning' : 'success', 'message' => __('হিসাবে উঠেছে :postedটি, ম্যাপিং বাকি :waitingটি, বাদ :ignoredটি, সমস্যা :failedটি।', ['posted' => $result['posted'], 'waiting' => $result['waiting'], 'ignored' => $result['ignored'], 'failed' => $result['failed']])]);

        return back();
    }

    public function updateMapping(Request $request, ImportMapping $mapping): RedirectResponse
    {
        $data = $request->validate([
            'account_id' => ['nullable', Rule::in(array_column(CraterImporter::accountsFor($mapping->kind), 'id'))],
            'cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'item_id' => [Rule::prohibitedIf($mapping->kind !== 'expense_category'), 'nullable', 'exists:items,id'],
            'ignore' => ['required', 'boolean'],
        ]);

        if (! empty($data['item_id'])) {
            $data['account_id'] = null;
            $data['cost_center_id'] = null;
        }

        $mapping->update($data);

        // Rows that were skipped as "not farm" come back if the mapping is un-ignored.
        if (! $data['ignore']) {
            ImportedRecord::query()->where('source', CraterImporter::SOURCE)->where('status', 'ignored')->update(['status' => 'pending']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('":name" ম্যাপিং রাখা হয়েছে।', ['name' => $mapping->external_name])]);

        return back();
    }

    public function ignoreUndecided(CraterImporter $importer): RedirectResponse
    {
        $count = $importer->ignoreUndecided();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':countটি ক্যাটাগরি/ক্রেতা "খামারের নয়" হিসেবে রাখা হয়েছে।', ['count' => $count])]);

        return back();
    }

    /** Quantity for a billing row that goes into stock (Crater has no kg). */
    public function setQty(Request $request, ImportedRecord $record): RedirectResponse
    {
        $data = $request->validate(['qty' => ['required', 'numeric', 'min:0.001', 'max:9999999']]);

        if ($record->status !== 'pending') {
            return back()->withErrors(['import' => __('এই সারি আগেই হিসাবে উঠেছে।')]);
        }

        $record->update(['qty' => $data['qty'], 'problem' => null]);

        return back();
    }

    /** A changed row was dealt with (its voucher reversed or confirmed correct). */
    public function resolve(Request $request, ImportedRecord $record): RedirectResponse
    {
        $data = $request->validate(['action' => ['required', Rule::in(['repost', 'keep'])]]);

        if ($record->status !== 'changed') {
            return back();
        }

        if ($data['action'] === 'repost') {
            if ($record->entry && $record->entry->reversed_by_id === null) {
                return back()->withErrors(['import' => __('আগে পুরনো এন্ট্রি :number বাতিল করুন, তারপর আবার পোস্ট করুন।', ['number' => $record->entry->number])]);
            }

            $record->update(['status' => 'pending', 'problem' => null, 'journal_entry_id' => null, 'posted_at' => null]);
        } else {
            $record->update(['status' => 'posted', 'problem' => 'বিলিংয়ের পরিবর্তন জেনে আগের হিসাব রাখা হয়েছে।']);
        }

        return back();
    }
}
