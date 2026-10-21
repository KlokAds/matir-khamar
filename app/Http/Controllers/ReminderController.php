<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\PushSubscription;
use App\Models\Reminder;
use App\Models\User;
use App\Services\PushSender;
use App\Services\StockForecast;
use App\Support\Lookups;
use App\Support\Tr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Reminders ("জমিতে সার — কাল সকাল ৭টা") and the phone notifications that carry them.
 */
class ReminderController extends Controller
{
    public function index(Request $request, PushSender $push, StockForecast $stock): Response
    {
        $user = $request->user();
        $map = fn (Reminder $r) => [
            'id' => $r->id,
            'title' => $r->title,
            'body' => $r->body,
            'due_at' => $r->due_at->format('Y-m-d\TH:i'),
            'repeat' => $r->repeat,
            'for' => $r->recipient?->name,
            'by' => $r->author?->name,
            'center' => $r->costCenter?->name,
            'animal' => $r->animal?->tag,
            'animal_id' => $r->animal_id,
            'done_at' => $r->done_at?->format('Y-m-d H:i'),
            'overdue' => $r->done_at === null && $r->due_at->isPast(),
        ];

        return Inertia::render('reminders/Index', [
            'open' => Reminder::query()->with(['recipient:id,name', 'author:id,name', 'costCenter:id,name', 'animal:id,tag'])
                ->visibleTo($user)->orderBy('due_at')->limit(200)->get()->map($map),
            'done' => Reminder::query()->with(['recipient:id,name', 'author:id,name', 'costCenter:id,name', 'animal:id,tag'])
                ->whereNotNull('done_at')->where(fn ($q) => $q->whereNull('for_user_id')->orWhere('for_user_id', $user->id)->orWhere('created_by', $user->id))
                ->orderByDesc('done_at')->limit(20)->get()->map($map),
            'repeats' => Tr::all(Reminder::REPEATS),
            'people' => User::query()->where('is_active', true)->where('is_super', false)->orderBy('name')->get(['id', 'name']),
            'centers' => Lookups::centers(),
            'animals' => Animal::query()->where('status', 'active')->orderBy('tag')->get(['id', 'tag', 'name']),
            'vapidKey' => $push->publicKey(),
            'devices' => PushSubscription::query()->where('user_id', $user->id)->count(),
            'prefs' => [
                'reminders' => $user->wantsNotification('reminders'),
                'morning' => $user->wantsNotification('morning'),
                'evening' => $user->wantsNotification('evening'),
            ],
            'stock' => $stock->items(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['nullable', 'string', 'max:1000'],
            'due_at' => ['required', 'date', 'after:-1 day'],
            'repeat' => ['required', Rule::in(array_keys(Reminder::REPEATS))],
            'for_user_id' => ['nullable', 'exists:users,id'],
            'cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'animal_id' => ['nullable', 'exists:animals,id'],
        ]);

        $reminder = Reminder::query()->create([...$data, 'due_at' => Date::parse($data['due_at']), 'created_by' => $request->user()->id]);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('মনে করিয়ে দেওয়া হবে :when।', ['when' => $reminder->due_at->format('d M, g:i A')])]);

        return back();
    }

    public function update(Request $request, Reminder $reminder): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['done', 'undo', 'snooze'])],
            'minutes' => ['required_if:action,snooze', 'nullable', 'integer', 'min:5', 'max:20160'],
        ]);

        $this->apply($reminder, $data['action'], $request->user(), (int) ($data['minutes'] ?? 60));

        return back();
    }

    /** The buttons on the notification itself: a signed link, so the phone needs no session. */
    public function act(Request $request, Reminder $reminder, string $action): JsonResponse
    {
        abort_unless(in_array($action, ['done', 'later'], true), 404);
        $user = User::query()->find($request->integer('user'));

        $this->apply($reminder, $action === 'done' ? 'done' : 'snooze', $user, 60);

        return response()->json(['ok' => true]);
    }

    public function subscribe(Request $request): HttpResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:2000', 'starts_with:https://'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'max:20'],
        ]);

        PushSubscription::query()->updateOrCreate(['endpoint_hash' => hash('sha256', $data['endpoint'])], [
            'user_id' => $request->user()->id,
            'endpoint' => $data['endpoint'],
            'public_key' => $data['keys']['p256dh'],
            'auth_token' => $data['keys']['auth'],
            'content_encoding' => $data['contentEncoding'] ?? 'aes128gcm',
            'device' => mb_substr((string) $request->userAgent(), 0, 250),
            'failures' => 0,
        ]);

        return response()->noContent();
    }

    public function unsubscribe(Request $request): HttpResponse
    {
        $endpoint = (string) $request->input('endpoint');
        PushSubscription::query()->where('user_id', $request->user()->id)->where('endpoint_hash', hash('sha256', $endpoint))->delete();

        return response()->noContent();
    }

    public function test(Request $request, PushSender $push): RedirectResponse
    {
        $reached = $push->toUsers([$request->user()], [
            'title' => Tr::get('মাটির খামার — পরীক্ষা'),
            'body' => Tr::get('নোটিফিকেশন ঠিকমতো আসছে। ✅'),
            'url' => '/reminders',
            'tag' => 'test',
        ]);

        Inertia::flash('toast', $reached > 0
            ? ['type' => 'success', 'message' => __(':countটি ফোনে পাঠানো হলো।', ['count' => $reached])]
            : ['type' => 'error', 'message' => __('কোনো ফোনে পৌঁছায়নি — এই ফোনে নোটিফিকেশন চালু করুন।')]);

        return back();
    }

    public function prefs(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reminders' => ['required', 'boolean'],
            'morning' => ['required', 'boolean'],
            'evening' => ['required', 'boolean'],
        ]);

        $request->user()->forceFill(['notify_prefs' => array_map('boolval', $data)])->save();

        return back();
    }

    private function apply(Reminder $reminder, string $action, ?User $user, int $minutes): void
    {
        match ($action) {
            'done' => $reminder->update(['done_at' => now(), 'done_by' => $user?->id]),
            'undo' => $reminder->update(['done_at' => null, 'done_by' => null]),
            default => $reminder->update(['due_at' => now()->addMinutes($minutes), 'sent_at' => null, 'done_at' => null]),
        };
    }
}
