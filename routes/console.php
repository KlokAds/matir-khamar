<?php

use App\Services\PrepaidService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('khamar:amortize', function (PrepaidService $prepaid) {
    $this->info('lease months posted: '.$prepaid->amortizeDue());
})->purpose('Move started lease months into their category');

Schedule::command('khamar:amortize')->dailyAt('00:30');
Schedule::command('khamar:crater-sync')->hourly()->withoutOverlapping();
Schedule::command('khamar:backup')->dailyAt('02:00');
