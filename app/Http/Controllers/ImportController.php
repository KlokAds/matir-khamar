<?php

namespace App\Http\Controllers;

use App\Models\ImportedRecord;
use App\Models\ImportMapping;
use App\Services\CraterImporter;
use App\Support\Lookups;
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
            'status' => array_key_exists($status, ImportedRecord::STATUSES) ? $status : null,
            'statuses' => ImportedRecord::STATUSES,
            'counts' => ImportedRecord::query()->where('source', CraterImporter::SOURCE)->selectRaw('status, COUNT(*) as n, SUM(amount) as total')->groupBy('status')->get()->keyBy('status'),
            'mappings' => ImportMapping::query()->where('source', CraterImporter::SOURCE)->orderBy('kind')->orderBy('external_name')->get()->map(fn (ImportMapping $map) => [
                ...$map->only(['id', 'kind', 'external_id', 'external_name', 'account_id', 'cost_center_id', 'item_id', 'ignore']),
                'complete' => $map->isComplete(),
            ]),
            'kinds' => ImportMapping::KINDS,
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
        ]);
    }

    public function fetch(CraterImporter $importer): RedirectResponse
    {
        if (! $importer->configured()) {
            return back()->withErrors(['import' => 'বিলিং ডাটাবেসের সংযোগ (.env-এ CRATER_DB_*) এখনো দেওয়া হয়নি।']);
        }

        try {
            $counts = $importer->fetch();
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['import' => 'বিলিং ডাটাবেসে সংযোগ হয়নি। সংযোগের তথ্য দেখুন।']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "নতুন {$counts['new']}টি, বদলানো {$counts['changed']}টি, আগের মতো {$counts['unchanged']}টি।"]);

        return back();
    }

    public function post(CraterImporter $importer): RedirectResponse
    {
        $result = $importer->post();

        Inertia::flash('toast', ['type' => $result['failed'] > 0 ? 'warning' : 'success', 'message' => "হিসাবে উঠেছে {$result['posted']}টি, ম্যাপিং বাকি {$result['waiting']}টি, বাদ {$result['ignored']}টি, সমস্যা {$result['failed']}টি।"]);

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

        Inertia::flash('toast', ['type' => 'success', 'message' => "\"{$mapping->external_name}\" ম্যাপিং রাখা হয়েছে।"]);

        return back();
    }

    public function ignoreUndecided(CraterImporter $importer): RedirectResponse
    {
        $count = $importer->ignoreUndecided();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$count}টি ক্যাটাগরি/ক্রেতা \"খামারের নয়\" হিসেবে রাখা হয়েছে।"]);

        return back();
    }

    /** Quantity for a billing row that goes into stock (Crater has no kg). */
    public function setQty(Request $request, ImportedRecord $record): RedirectResponse
    {
        $data = $request->validate(['qty' => ['required', 'numeric', 'min:0.001', 'max:9999999']]);

        if ($record->status !== 'pending') {
            return back()->withErrors(['import' => 'এই সারি আগেই হিসাবে উঠেছে।']);
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
                return back()->withErrors(['import' => 'আগে পুরনো এন্ট্রি '.$record->entry->number.' বাতিল করুন, তারপর আবার পোস্ট করুন।']);
            }

            $record->update(['status' => 'pending', 'problem' => null, 'journal_entry_id' => null, 'posted_at' => null]);
        } else {
            $record->update(['status' => 'posted', 'problem' => 'বিলিংয়ের পরিবর্তন জেনে আগের হিসাব রাখা হয়েছে।']);
        }

        return back();
    }
}
