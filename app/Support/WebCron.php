<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Cron without an hPanel cron entry: at most once a minute, a web request starts
 * `php artisan schedule:run` as a detached background process (config/khamar.php → web_cron).
 * Every page view counts, and so does a free uptime monitor pinging /up every few minutes,
 * which keeps the nightly backup running when nobody opens the site.
 */
class WebCron
{
    public const SLOT_KEY = 'khamar:web-cron';

    /** Written every minute by the running khamar:clock. */
    public const CLOCK_BEAT = 'khamar:clock-beat';

    public const CLOCK_OWNER = 'khamar:clock-owner';

    /** True while a khamar:clock process is ticking. */
    public function clockAlive(): bool
    {
        return Cache::has(self::CLOCK_BEAT);
    }

    public function enabled(): bool
    {
        return (bool) config('khamar.web_cron')
            && ! app()->runningInConsole()
            && PHP_OS_FAMILY !== 'Windows'
            && is_file(base_path('.env'));
    }

    /** True once a minute: the first request of each minute wins the slot. */
    public function due(): bool
    {
        return Cache::add(self::SLOT_KEY, now()->toIso8601String(), 60);
    }

    public function start(): void
    {
        $command = sprintf(
            'cd %s && nohup %s artisan schedule:run >> %s 2>&1 &',
            escapeshellarg(base_path()),
            escapeshellarg(PhpCli::path()),
            escapeshellarg(storage_path('logs/web-cron.log')),
        );

        try {
            Process::timeout(10)->run(['sh', '-c', $command]);
        } catch (Throwable $exception) {
            Log::warning('Web cron could not start the scheduler: '.$exception->getMessage());
        }
    }

    /** Starts the background clock, at most once every five minutes while it is not ticking. */
    public function startClock(): void
    {
        if (! Cache::add('khamar:clock-starting', true, 300)) {
            return;
        }

        $this->spawnClock();
    }

    /** A new clock process, detached from whoever starts it (a request or the retiring clock). */
    public function spawnClock(): void
    {
        // setsid gives it its own session, so the web worker that started it can end without it.
        $run = sprintf('%s artisan khamar:clock >> %s 2>&1 < /dev/null &', escapeshellarg(PhpCli::path()), escapeshellarg(storage_path('logs/clock.log')));
        $command = sprintf('cd %s && if command -v setsid >/dev/null 2>&1; then setsid nohup %s else nohup %s fi', escapeshellarg(base_path()), $run, $run);

        try {
            Process::timeout(10)->run(['sh', '-c', $command]);
        } catch (Throwable $exception) {
            Log::warning('Web cron could not start the clock: '.$exception->getMessage());
        }
    }

    public function tick(): void
    {
        try {
            if (! $this->enabled()) {
                return;
            }

            // With the clock ticking, the minute is already covered; otherwise start it, and
            // run this minute from the request as before so nothing is missed meanwhile.
            if ($this->clockAlive()) {
                return;
            }

            $this->startClock();

            if ($this->due()) {
                $this->start();
            }
        } catch (Throwable $exception) {
            // Mid-install the cache table may not exist yet: nothing to schedule then anyway.
            report($exception);
        }
    }
}
