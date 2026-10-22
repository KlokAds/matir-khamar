<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\CropSeason;
use App\Models\Worker;
use App\Models\WorkLog;
use App\Services\LabourBook;
use App\Support\Lookups;
use App\Support\Money;
use App\Support\Tr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** মজুরির খাতা: who worked how many days on what, what is owed, and paying it. */
class LabourController extends Controller
{
    public function index(Request $request, LabourBook $book): Response
    {
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('month')) ? (string) $request->query('month') : now()->format('Y-m');

        return Inertia::render('labour/Index', [
            'workers' => Worker::query()->with('costCenter:id,name')->orderByDesc('is_active')->orderBy('name')->get()
                ->map(fn (Worker $w) => $book->workerRow($w))->values(),
            'recent' => $book->recent(),
            'summary' => $book->monthSummary($month),
            'workTypes' => WorkLog::WORK_TYPES,
            'kinds' => Tr::all(Worker::KINDS),
            'centers' => Lookups::centers(),
            'seasons' => CropSeason::query()->whereNull('closed_on')->orderByDesc('started_on')->get(['id', 'name', 'cost_center_id']),
            'moneyAccounts' => array_map(fn (Account $a) => ['id' => $a->id, 'name' => $a->name, 'code' => $a->code, 'subtype' => $a->subtype], Lookups::moneyAccounts()),
        ]);
    }

    public function storeWorker(Request $request): RedirectResponse
    {
        $worker = Worker::query()->create($this->workerData($request));
        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get('":name" শ্রমিকের তালিকায় যোগ হলো।', ['name' => $worker->name])]);

        return back();
    }

    public function updateWorker(Request $request, Worker $worker): RedirectResponse
    {
        $worker->update($this->workerData($request));
        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get('শ্রমিকের তথ্য হালনাগাদ হলো।')]);

        return back();
    }

    public function storeLogs(Request $request, LabourBook $book): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'cost_center_id' => ['required', 'exists:cost_centers,id'],
            'crop_season_id' => ['nullable', 'exists:crop_seasons,id'],
            'work_type' => ['required', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:500'],
            'workers' => ['required', 'array', 'min:1', 'max:60'],
            'workers.*.worker_id' => ['required', 'distinct', Rule::exists('workers', 'id')->where('is_active', true)],
            'workers.*.days' => ['required', 'numeric', 'min:0.25', 'max:31'],
            'workers.*.rate' => ['required', 'numeric', 'min:0', 'max:1000000'],
        ]);

        $rows = array_map(fn (array $w) => [
            'worker_id' => (int) $w['worker_id'],
            'days' => (float) $w['days'],
            'rate' => Money::toPoisha($w['rate']),
        ], array_values($data['workers']));

        $logs = $book->log($data, $rows);
        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get('কাজ লেখা হলো (:n জন)।', ['n' => count($logs)])]);

        return back();
    }

    public function destroyLog(WorkLog $log): RedirectResponse
    {
        abort_if($log->isPaid(), 403, Tr::get('মজুরি দেওয়া হয়ে গেছে, এই লেখা মোছা যায় না।'));
        abort_unless($log->created_at?->greaterThan(now()->subDay()), 403, Tr::get('একদিনের বেশি পুরনো লেখা মোছা যায় না।'));
        $log->delete();

        return back();
    }

    public function pay(Request $request, Worker $worker, LabourBook $book): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'money_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'log_ids' => ['nullable', 'array', 'max:500'],
            'log_ids.*' => ['integer'],
        ]);

        $ids = isset($data['log_ids']) ? array_values(array_map('intval', $data['log_ids'])) : null;
        $entry = $book->pay($worker, $data['date'], (int) $data['money_account_id'], $ids);
        Inertia::flash('toast', ['type' => 'success', 'message' => Tr::get(':name-কে মজুরি দেওয়া হলো (:number)।', ['name' => $worker->name, 'number' => $entry->number])]);

        return back();
    }

    /** @return array<string, mixed> */
    private function workerData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'kind' => ['required', Rule::in(array_keys(Worker::KINDS))],
            'daily_rate' => ['nullable', 'required_if:kind,daily', 'numeric', 'min:0', 'max:1000000'],
            'monthly_salary' => ['nullable', 'required_if:kind,monthly', 'numeric', 'min:0', 'max:10000000'],
            'cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach (['daily_rate', 'monthly_salary'] as $field) {
            $data[$field] = isset($data[$field]) && $data[$field] !== '' ? Money::toPoisha($data[$field]) : null;
        }

        return $data;
    }
}
