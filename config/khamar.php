<?php

return [

    /*
    | Farm name shown across the app and on printed reports.
    */
    'name' => env('KHAMAR_NAME', 'Klok মাটির খামার'),

    /*
    | Admins and managers must switch on two-factor login before they can
    | use the books. Keep this on in production.
    */
    'require_two_factor' => (bool) env('KHAMAR_REQUIRE_2FA', true),

    /*
    | Largest voucher image or PDF accepted, in kilobytes.
    */
    'max_voucher_kb' => (int) env('KHAMAR_MAX_VOUCHER_KB', 5120),

    /*
    | Updates from GitHub. Every push to main is built into the hostinger-dist
    | branch by .github/workflows/hostinger-deploy.yml; the live site checks
    | that branch out (Setup → App update, or `php artisan khamar:update`).
    | auto: the scheduler pulls a newer build by itself every 10 minutes.
    */
    'updates' => [
        'branch' => env('KHAMAR_UPDATE_BRANCH', 'hostinger-dist'),
        'auto' => (bool) env('KHAMAR_AUTO_UPDATE', false),
        'repository' => env('KHAMAR_REPOSITORY', 'https://github.com/KlokAds/matir-khamar'),
    ],

    /*
    | No hPanel cron: web traffic starts `schedule:run` at most once a minute
    | (App\Support\WebCron). On for the live server through .env.
    */
    'web_cron' => (bool) env('KHAMAR_WEB_CRON', false),

    /*
    | Command-line php for processes the web app starts (updates). Empty: the
    | php next to the web one (Hostinger: /opt/alt/php85/usr/bin/php).
    */
    'php_cli' => env('KHAMAR_PHP_CLI'),

];
