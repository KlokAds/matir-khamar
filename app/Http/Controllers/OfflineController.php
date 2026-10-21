<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Animal;
use App\Models\CostCenter;
use App\Models\Item;
use App\Models\MilkRecord;
use App\Models\OfflineSubmission;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Ledger;
use App\Services\MilkBook;
use App\Services\StockService;
use App\Support\Lookups;
use App\Support\Money;
use App\Support\Tr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Daily entries that work without internet. The page is cached on the phone
 * by the service worker; entries wait on the phone and are sent here when
 * the connection returns. Each is checked exactly like a normal entry.
 */
class OfflineController extends Controller
{
    /** How old an offline entry may be when it finally reaches the server. */
    private const MAX_AGE_DAYS = 30;

    public function show(Request $request): Response
    {
        $canExpense = $request->user()->can('post-entries');
        $gavi = CostCenter::query()->where('code', 'GAVI')->value('id');

        return Inertia::render('offline/Index', [
            'userId' => $request->user()->id,
            'snapshotAt' => now()->toIso8601String(),
            'sessions' => Tr::all(MilkRecord::SESSIONS),
            'feedSessions' => Tr::all(StockMovement::SESSIONS),
            'cows' => Animal::query()->where('status', 'active')->where('sex', 'female')->when($gavi, fn ($query) => $query->where('cost_center_id', $gavi))->orderBy('tag')->get(['id', 'tag', 'name']),
            'items' => Lookups::items(),
            'centers' => array_values(array_filter(Lookups::centers(), fn ($center) => ! in_array($center['kind'], ['group'], true))),
            'canExpense' => $canExpense,
            'moneyAccounts' => $canExpense ? Lookups::moneyAccounts() : [],
            'expenseAccounts' => $canExpense ? Lookups::accounts('expense') : [],
        ]);
    }

    public function sync(Request $request, StockService $stock, Ledger $ledger, MilkBook $milk): JsonResponse
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.uuid' => ['required', 'uuid'],
            'items.*.kind' => ['required', Rule::in(['milk', 'feed', 'expense'])],
            'items.*.recorded_at' => ['required', 'date'],
            'items.*.payload' => ['required', 'array'],
        ]);

        $user = $request->user();
        $results = [];

        foreach ($request->input('items') as $item) {
            $existing = OfflineSubmission::query()->where('uuid', $item['uuid'])->first();

            if ($existing) {
                // Another user's id is never revealed or reused.
                $results[] = $existing->user_id === $user->id
                    ? $existing->result()
                    : ['uuid' => $item['uuid'], 'status' => 'failed', 'message' => Tr::get('এই এন্ট্রি অন্য ব্যবহারকারীর।'), 'number' => null];

                continue;
            }

            $results[] = $this->process($item, $user, $stock, $ledger, $milk);
        }

        return response()->json(['results' => $results]);
    }

    /**
     * @param  array{uuid:string, kind:string, recorded_at:string, payload:array<string, mixed>}  $item
     * @return array{uuid:string, status:string, message:?string, number:?string}
     */
    private function process(array $item, User $user, StockService $stock, Ledger $ledger, MilkBook $milk): array
    {
        $payload = $item['payload'];
        $oldest = now()->subDays(self::MAX_AGE_DAYS)->toDateString();
        $entryId = null;
        $message = null;

        try {
            DB::transaction(function () use ($item, $payload, $user, $stock, $ledger, $milk, $oldest, &$entryId, &$message) {
                $this->authorizeKind($item['kind'], $user);

                switch ($item['kind']) {
                    case 'milk':
                        $data = Validator::make($payload, [
                            'date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.$oldest],
                            'session' => ['required', Rule::in(array_keys(MilkRecord::SESSIONS))],
                            'rows' => ['required', 'array', 'min:1', 'max:100'],
                            'rows.*.animal_id' => ['nullable', 'exists:animals,id'],
                            'rows.*.liters' => ['nullable', 'numeric', 'min:0', 'max:80'],
                        ])->validate();
                        $message = Tr::get(':countটি গাভীর দুধ রাখা হয়েছে', ['count' => $milk->record($data['date'], $data['session'], $data['rows'], $user->id)]);
                        break;

                    case 'feed':
                        $data = Validator::make($payload, [
                            'item_id' => ['required', 'exists:items,id'],
                            'date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.$oldest],
                            'splits' => ['required', 'array', 'min:1', 'max:20'],
                            'splits.*.cost_center_id' => ['required', 'exists:cost_centers,id'],
                            'splits.*.qty' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
                            'note' => ['nullable', 'string', 'max:255'],
                            'session' => ['nullable', Rule::in(array_keys(StockMovement::SESSIONS))],
                        ])->validate();
                        $entryId = $stock->issue(Item::query()->findOrFail((int) $data['item_id']), $data['date'], $data['splits'], trim(($data['note'] ?? '').' (অফলাইন)'), $data['session'] ?? null)->id;
                        break;

                    case 'expense':
                        $data = Validator::make($payload, [
                            'date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.$oldest],
                            'money_account_id' => ['required', Rule::exists('accounts', 'id')->whereIn('subtype', Account::MONEY_SUBTYPES)],
                            'account_id' => ['required', Rule::exists('accounts', 'id')->where('type', 'expense')],
                            'cost_center_id' => ['required', 'exists:cost_centers,id'],
                            'amount' => Money::rule(),
                            'description' => ['nullable', 'string', 'max:200'],
                        ])->validate();
                        $amount = Money::toPoisha($data['amount']);
                        $entryId = $ledger->post('expense', $data['date'], ($data['description'] ?: 'খরচ').' (অফলাইন)', [
                            ['account_id' => (int) $data['account_id'], 'debit' => $amount, 'cost_center_id' => (int) $data['cost_center_id']],
                            ['account_id' => (int) $data['money_account_id'], 'credit' => $amount],
                        ], ['reference' => 'offline:'.substr($item['uuid'], 0, 8)])->id;
                        break;
                }

                $this->remember($item, $user, 'done', $message, $entryId);
            });
        } catch (ValidationException $exception) {
            // A clear "no": kept, so sending it again gives the same answer.
            $message = collect($exception->errors())->flatten()->first();
            $this->remember($item, $user, 'failed', $message, null);
        } catch (Throwable $exception) {
            // A server hiccup: nothing is kept, the phone tries again later.
            report($exception);

            return ['uuid' => $item['uuid'], 'status' => 'retry', 'message' => Tr::get('সার্ভারে সমস্যা, পরে আবার পাঠানো হবে।'), 'number' => null];
        }

        return OfflineSubmission::query()->where('uuid', $item['uuid'])->firstOrFail()->result();
    }

    private function authorizeKind(string $kind, User $user): void
    {
        $ability = $kind === 'expense' ? 'post-entries' : 'post-daily';

        if (! $user->can($ability)) {
            throw ValidationException::withMessages(['kind' => __('আপনার এই ধরনের এন্ট্রি দেওয়ার অনুমতি নেই।')]);
        }
    }

    /** @param  array{uuid:string, kind:string, recorded_at:string, payload:array<string, mixed>}  $item */
    private function remember(array $item, User $user, string $status, ?string $message, ?int $entryId): void
    {
        OfflineSubmission::query()->create([
            'uuid' => $item['uuid'],
            'user_id' => $user->id,
            'kind' => $item['kind'],
            'payload' => $item['payload'],
            'status' => $status,
            'message' => $message ? mb_substr($message, 0, 250) : null,
            'journal_entry_id' => $entryId,
            'recorded_at' => $item['recorded_at'],
        ]);
    }
}
