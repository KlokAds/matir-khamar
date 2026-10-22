<?php

use App\Models\Setting;
use App\Services\Notifier;
use App\Services\PrepaidService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('khamar:notify {--morning : Send the morning list} {--evening : Send the evening nudge}', function (Notifier $notifier) {
    if ($this->option('morning')) {
        $this->info('morning lists: '.$notifier->sendMorningDigest());
    } elseif ($this->option('evening')) {
        $this->info('evening nudges: '.$notifier->sendEveningCheck());
    } else {
        $this->info('reminders sent: '.$notifier->sendDueReminders());
    }
})->purpose('Send due reminders and the daily farm notifications');

Artisan::command('khamar:amortize', function (PrepaidService $prepaid) {
    $this->info('lease months posted: '.$prepaid->amortizeDue());
})->purpose('Move started lease months into their category');

/*
 * The scheduler is started by web traffic (App\Support\WebCron), not by a cron entry, so it
 * cannot count on running at an exact minute such as 02:00. Each task therefore runs "at the
 * first chance once it is due": the scheduler is asked every minute, and a cache slot lets the
 * task through at most once per period.
 */
$oncePer = fn (string $key, int $seconds): Closure => fn (): bool => Cache::add("khamar:schedule:{$key}", now()->toIso8601String(), $seconds);

// Nightly backup: the first run after 02:00 each day, unless one was already written today.
Schedule::command('khamar:backup')->everyMinute()->withoutOverlapping(30)
    ->when(fn (): bool => now()->hour >= 2
        && collect(File::glob(storage_path('app/private/backups/khamar-'.now()->format('Y-m-d').'-*.bak')))->isEmpty()
        && Cache::add('khamar:schedule:backup-try', true, 900));

Schedule::command('khamar:amortize')->everyMinute()->when($oncePer('amortize', 3600));

// Reminders go out within a minute of falling due: the background clock (khamar:clock, started
// by the first web visit) runs this every minute. Morning list after 07:00; evening nudges below.
Schedule::command('khamar:notify')->everyMinute()->withoutOverlapping(5);
// "Sent today" is kept in the database, not the cache: every app update clears the cache, and
// the evening nudge then went out again after each one.
$oncePerDay = function (string $key): bool {
    if (Setting::get("schedule.{$key}") === now()->toDateString()) {
        return false;
    }

    Setting::put("schedule.{$key}", now()->toDateString());

    return true;
};
Schedule::command('khamar:notify --morning')->everyMinute()
    ->when(fn (): bool => now()->hour >= 7 && now()->hour < 11 && $oncePerDay('morning'));
// Evening: at 8 pm if today's milk or feed is not written, and once more at 9:30 if still not.
Schedule::command('khamar:notify --evening')->everyMinute()
    ->when(fn (): bool => now()->hour >= 20 && now()->hour < 23 && $oncePerDay('evening'));
Schedule::command('khamar:notify --evening')->everyMinute()
    ->when(fn (): bool => now()->format('H:i') >= '21:30' && now()->hour < 23 && $oncePerDay('evening-again'));

if (config('khamar.updates.auto')) {
    Schedule::command('khamar:update --if-behind')->everyMinute()->withoutOverlapping(20)->when($oncePer('update', 600));
}
