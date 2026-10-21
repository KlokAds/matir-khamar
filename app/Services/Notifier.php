<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\MilkRecord;
use App\Models\Reminder;
use App\Models\User;
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

        foreach ($due as $reminder) {
            $recipients = $reminder->for_user_id
                ? User::query()->whereKey([$reminder->for_user_id, $reminder->created_by])->where('is_active', true)->get()
                : $this->dailyWorkers();

            foreach ($recipients->filter(fn (User $u) => $u->wantsNotification('reminders')) as $user) {
                $this->push->toUsers([$user], [
                    'title' => '⏰ '.$reminder->title,
                    'body' => $reminder->body ?: 'মনে করিয়ে দিচ্ছি — হয়ে গেলে "হয়ে গেছে" চাপুন।',
                    'url' => '/reminders',
                    'tag' => "reminder-{$reminder->id}",
                    'requireInteraction' => true,
                    'actions' => [
                        ['action' => 'done', 'title' => 'হয়ে গেছে', 'url' => $this->actionUrl($reminder, 'done', $user)],
                        ['action' => 'later', 'title' => '১ ঘণ্টা পরে', 'url' => $this->actionUrl($reminder, 'later', $user)],
                    ],
                ]);
            }

            $reminder->update(['sent_at' => now()]);

            if ($reminder->repeat !== 'none') {
                $reminder->replicate(['sent_at', 'done_at', 'done_by'])->fill([
                    'due_at' => $reminder->repeat === 'weekly' ? $reminder->due_at->addWeek() : $reminder->due_at->addDay(),
                ])->save();
            }
        }

        return $due->count();
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
                ? "{$item['name']} আর ".round($item['days_left']).' দিন চলবে — কেনার ব্যবস্থা করুন'
                : "{$item['name']} কমে গেছে";
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
            $lines = $this->morningLines($user);

            if ($lines === []) {
                continue;
            }

            $sent += $this->push->toUsers([$user], [
                'title' => 'শুভ সকাল — আজ '.count($lines).'টি কাজ মনে রাখার',
                'body' => implode("\n", array_slice($lines, 0, 5)).(count($lines) > 5 ? "\n…আরও ".(count($lines) - 5).'টি' : ''),
                'url' => '/dashboard',
                'tag' => 'morning-'.now()->toDateString(),
            ]);
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

        return $this->push->toUsers($this->dailyWorkers()->filter(fn (User $u) => $u->wantsNotification('evening')), [
            'title' => 'আজকের '.implode(' ও ', $missing).' এখনো লেখা হয়নি',
            'body' => 'ঘুমানোর আগে দুই মিনিটে লিখে ফেলুন — ইন্টারনেট না থাকলেও ফোনে জমা থাকবে।',
            'url' => in_array('দুধ', $missing, true) ? '/milk' : '/stock/move?mode=issue',
            'tag' => 'evening-'.now()->toDateString(),
        ]);
    }

    private function actionUrl(Reminder $reminder, string $action, User $user): string
    {
        return URL::temporarySignedRoute('reminders.act', now()->addDays(3), [
            'reminder' => $reminder->id,
            'action' => $action,
            'user' => $user->id,
        ]);
    }
}
