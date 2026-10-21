<?php

use App\Support\WebCron;
use Illuminate\Support\Facades\Cache;

it('lets only the first request of a minute start the scheduler', function () {
    Cache::flush();
    $cron = new WebCron;

    expect($cron->due())->toBeTrue()
        ->and($cron->due())->toBeFalse();
});

it('stays off unless switched on, and never from the command line', function () {
    config(['khamar.web_cron' => false]);
    expect((new WebCron)->enabled())->toBeFalse();

    // Tests run in the console, like artisan: the scheduler must not start itself from there.
    config(['khamar.web_cron' => true]);
    expect((new WebCron)->enabled())->toBeFalse();
});
