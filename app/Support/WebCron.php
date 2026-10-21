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

    public function tick(): void
    {
        try {
            if ($this->enabled() && $this->due()) {
                $this->start();
            }
        } catch (Throwable $exception) {
            // Mid-install the cache table may not exist yet: nothing to schedule then anyway.
            report($exception);
        }
    }
}
