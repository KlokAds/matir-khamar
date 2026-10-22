<?php

namespace App\Console\Commands;

use App\Support\PhpCli;
use App\Support\WebCron;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * The farm's own clock: a small background process that runs the scheduler at the start of
 * every minute, so reminders go out on time even when nobody has the site open. The first web
 * request starts it (App\Support\WebCron); if the host stops it, the next request starts it
 * again, and a GitHub workflow knocks on the site every five minutes so that happens even when
 * nobody opens it (.github/workflows/clock.yml). It retires after a few hours so an app update
 * is picked up, starting its successor first so the minutes never stop.
 */
#[Signature('khamar:clock {--hours=6 : Stop after this many hours} {--once : Run a single tick (tests)}')]
#[Description('Run the scheduler every minute in the background')]
class ClockCommand extends Command
{
    public function handle(): int
    {
        $me = (string) Str::uuid();
        Cache::put(WebCron::CLOCK_OWNER, $me, 3600 * 24);
        $stopAt = now()->addHours((int) $this->option('hours'));

        do {
            // Another clock took over (e.g. after a restart): this one steps aside.
            if (Cache::get(WebCron::CLOCK_OWNER) !== $me) {
                return self::SUCCESS;
            }

            Cache::put(WebCron::CLOCK_BEAT, now()->toIso8601String(), 180);

            Process::path(base_path())->timeout(55)->run([PhpCli::path(), 'artisan', 'schedule:run']);

            if ($this->option('once')) {
                return self::SUCCESS;
            }

            // Sleep to the start of the next minute.
            sleep(max(1, 60 - (int) now()->format('s')));
        } while (now()->lessThan($stopAt));

        app(WebCron::class)->spawnClock();

        return self::SUCCESS;
    }
}
