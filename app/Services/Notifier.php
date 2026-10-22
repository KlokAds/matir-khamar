<?php

namespace App\Services;

use App\Http\Middleware\SetLocale;
use App\Models\JournalEntry;
use App\Models\MilkRecord;
use App\Models\Reminder;
use App\Models\User;
use App\Support\Tr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

/**
 * Decides what each person's phone should hear about, and when: reminders as they fall due,
 * a short morning list of the day's farm work, and an evening nudge when the day's milk or
 * feed has not been written.
 */
class Notifier
{
    public function __construct(private PushSender $push, private HerdCare $care, private StockForecast $stock) {}

    /** @return Collection<int, User> */
    public function dailyWorkers(): Collection
    {
        return User::query()->where('is_active', true)->get()->filter(fn (User $user) => $user->hasAbility('post-daily'))->values();
    }

    /** Sends every reminder that has fallen due. Returns how many went out. */
    public function sendDueReminders(): int
    {
        $due = Reminder::query()->whereNull('sent_at')->whereNull('done_at')->where('due_at', '<=', now())->orderBy('due_at')->limit(50)->get();

        // Alarms already sent ring again until someone answers them.
        $ringing = Reminder::query()->where('alarm', true)->whereNotNull('sent_at')->whereNull('done_at')
            ->where('rings', '<', Reminder::ALARM_MAX_RINGS)
            ->where('last_rung_at', '<=', now()->subMinutes(Reminder::ALARM_EVERY_MINUTES)->addSeconds(5))
            ->limit(50)->get();

        foreach ($ringing as $reminder) {
            $this->ring($reminder);
        }

        foreach ($due as $reminder) {
            $this->ring($reminder);
            $reminder->update(['sent_at' => now()]);

            if ($reminder->repeat !== 'none') {
                $reminder->replicate(['sent_at', 'done_at', 'done_by', 'rings', 'last_rung_at'])->fill([
                    'due_at' => $reminder->repeat === 'weekly' ? $reminder->due_at->addWeek() : $reminder->due_at->addDay(),
                ])->save();
            }
        }

        return $due->count() + $ringing->count();
    }

    /**
     * Who a reminder is for: the named person and its writer, or everyone on daily work.
     *
     * @return Collection<int, User>
     */
    public function recipientsOf(Reminder $reminder): Collection
    {
        return $reminder->for_user_id
            ? User::query()->whereKey([$reminder->for_user_id, $reminder->created_by])->where('is_active', true)->get()
            : $this->dailyWorkers();
    }

    /**
     * What a reminder's notification says, in the person's language, with its "done" and
     * "later" buttons. The farm app also schedules these on the phone, to ring without internet.
     *
     * @return array<string, mixed>
     */
    public function reminderPayload(Reminder $reminder, User $user): array
    {
        return $this->inLanguageOf($user, fn () => [
            'alarm' => $reminder->alarm,
            'reminder_id' => $reminder->id,
            // 0 on the first send: a phone that already rang it by itself skips that one.
            'rings' => $reminder->rings,
            'title' => ($reminder->alarm ? '🔔 ' : '⏰ ').$reminder->title,
            'body' => $reminder->body ?: Tr::get('মনে করিয়ে দিচ্ছি — হয়ে গেলে "হয়ে গেছে" চাপুন।'),
            'url' => '/reminders',
            'tag' => "reminder-{$reminder->id}",
            'requireInteraction' => true,
            'actions' => [
                ['action' => 'done', 'title' => Tr::get('হয়ে গেছে'), 'url' => $this->actionUrl($reminder, 'done', $user)],
                ['action' => 'later', 'title' => Tr::get('১ ঘণ্টা পরে'), 'url' => $this->actionUrl($reminder, 'later', $user)],
            ],
        ]);
    }

    /**
     * Sends one reminder to its people. An alarm says so, so the phone keeps it on screen,
     * vibrates long, and an open app shows a full-screen ringing alarm.
     *
     * @param  Collection<int, User>|null  $recipients
     */
    private function ring(Reminder $reminder, ?Collection $recipients = null): void
    {
        $recipients ??= $this->recipientsOf($reminder);

        foreach ($recipients->filter(fn (User $u) => $u->wantsNotification('reminders')) as $user) {
            $this->push->toUsers([$user], $this->reminderPayload($reminder, $user));
        }

        if ($reminder->alarm) {
            $reminder->update(['rings' => $reminder->rings + 1, 'last_rung_at' => now()]);
        }
    }

    /**
     * The morning list: farm reminders (heat, pregnancy checks, calving, batches), items about
     * to run out and today's own reminders.
     *
     * @return list<string>
     */
    public function morningLines(User $user): array
    {
        $lines = array_map(fn ($r) => $r['title'], $this->care->reminders());

        foreach ($this->stock->low() as $item) {
            $lines[] = $item['days_left'] !== null
                ? Tr::get(':name আর :days দিন চলবে — কেনার ব্যবস্থা করুন', ['name' => $item['name'], 'days' => round($item['days_left'])])
                : Tr::get(':name কমে গেছে', ['name' => $item['name']]);
        }

        $today = Reminder::query()->visibleTo($user)->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()])->orderBy('due_at')->get();

        foreach ($today as $reminder) {
            $lines[] = $reminder->due_at->format('g:i').' — '.$reminder->title;
        }

        return $lines;
    }

    public function sendMorningDigest(): int
    {
        $sent = 0;

        foreach ($this->dailyWorkers()->filter(fn (User $u) => $u->wantsNotification('morning')) as $user) {
            $payload = $this->inLanguageOf($user, function () use ($user) {
                $lines = $this->morningLines($user);

                return $lines === [] ? null : [
                    'title' => Tr::get('শুভ সকাল — আজ :countটি কাজ মনে রাখার', ['count' => count($lines)]),
                    'body' => implode("\n", array_slice($lines, 0, 5)).(count($lines) > 5 ? "\n".Tr::get('…আরও :countটি', ['count' => count($lines) - 5]) : ''),
                    'url' => '/dashboard',
                    'tag' => 'morning-'.now()->toDateString(),
                ];
            });

            if ($payload === null) {
                continue;
            }

            $sent += $this->push->toUsers([$user], $payload);
        }

        return $sent;
    }

    /** @return list<string> what has not been written today */
    public function missingToday(): array
    {
        $missing = [];
        $today = now()->toDateString();

        if (MilkRecord::query()->whereDate('date', $today)->doesntExist() && $this->care->hasMilkingCows()) {
            $missing[] = 'দুধ';
        }

        if (JournalEntry::query()->where('type', 'stock_issue')->whereDate('date', $today)->doesntExist()) {
            $missing[] = 'খাবার';
        }

        return $missing;
    }

    public function sendEveningCheck(): int
    {
        $missing = $this->missingToday();

        if ($missing === []) {
            return 0;
        }

        $sent = 0;

        foreach ($this->dailyWorkers()->filter(fn (User $u) => $u->wantsNotification('evening')) as $user) {
            $sent += $this->push->toUsers([$user], $this->inLanguageOf($user, fn () => [
                'title' => Tr::get('আজকের :what এখনো লেখা হয়নি', ['what' => implode(' '.Tr::get('ও').' ', Tr::all($missing))]),
                'body' => Tr::get('ঘুমানোর আগে দুই মিনিটে লিখে ফেলুন — ইন্টারনেট না থাকলেও ফোনে জমা থাকবে।'),
                'url' => in_array('দুধ', $missing, true) ? '/milk' : '/stock/move?mode=issue',
                'tag' => 'evening-'.now()->toDateString(),
            ]));
        }

        return $sent;
    }

    /**
     * Builds a message in the person's own language. Pushes go out from the scheduler, which
     * runs in Bangla, so each recipient's text is made under their locale and then put back.
     *
     * @template T
     *
     * @param  callable(): T  $build
     * @return T
     */
    private function inLanguageOf(User $user, callable $build): mixed
    {
        $previous = app()->getLocale();
        app()->setLocale(in_array($user->locale, SetLocale::LOCALES, true) ? $user->locale : 'bn');

        try {
            return $build();
        } finally {
            app()->setLocale($previous);
        }
    }

    private function actionUrl(Reminder $reminder, string $action, User $user): string
    {
        // Good until three days after it falls due, so buttons on a phone-scheduled alarm work too.
        return URL::temporarySignedRoute('reminders.act', ($reminder->due_at->isFuture() ? $reminder->due_at : now())->addDays(3), [
            'reminder' => $reminder->id,
            'action' => $action,
            'user' => $user->id,
        ]);
    }
}
