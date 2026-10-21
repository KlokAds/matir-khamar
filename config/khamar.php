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

];
