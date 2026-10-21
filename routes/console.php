<?php

use App\Services\PrepaidService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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

if (config('khamar.updates.auto')) {
    Schedule::command('khamar:update --if-behind')->everyMinute()->withoutOverlapping(20)->when($oncePer('update', 600));
}
