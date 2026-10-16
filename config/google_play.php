<?php

/*
|--------------------------------------------------------------------------
| Google Play External Offers Program (Android, EEA users)
|--------------------------------------------------------------------------
| Payments made after a user follows an external offer link from the
| Android app must be reported to Google within 24 hours.
| Docs: https://developer.android.com/google/play/billing/outside-gpb-backend
*/

return [

    // Master switch. Keep false on staging/local: rows are still recorded
    // (status "pending") but nothing is sent to Google.
    'enabled' => env('GOOGLE_PLAY_EXTERNAL_REPORTING_ENABLED', false),

    'package_name' => env('GOOGLE_PLAY_PACKAGE_NAME', 'apply.revealclub'),

    // Absolute path to the Google Cloud service account JSON key on the server.
    // Never commit this file and never put it under public/.
    'credentials_path' => env('GOOGLE_PLAY_CREDENTIALS_PATH'),

    // Android debug test mode token: ignored when reporting is enabled (production),
    // recorded but never sent when reporting is disabled (staging), for end-to-end tests.
    'ignored_tokens' => ['debug_fake_token'],

    // Rows still not reported after this many hours are logged as critical
    // (Google requires reporting within 24 hours of the transaction).
    'alert_after_hours' => 20,

    'max_attempts' => 20,

    /*
    | VAT: prices are VAT-inclusive, Google needs pre-tax and tax separately.
    | If GOOGLE_PLAY_VAT_FIXED_RATE is set (e.g. 20), that single rate is used
    | for every country. Otherwise the customer's country rate below is used.
    | Standard rates as of 2026 – VERIFY WITH YOUR ACCOUNTANT before going live.
    */
    'vat_fixed_rate' => env('GOOGLE_PLAY_VAT_FIXED_RATE'),

    'vat_rates' => [
        'AT' => 20,   'BE' => 21,   'BG' => 20,   'HR' => 25,   'CY' => 19,
        'CZ' => 21,   'DK' => 25,   'EE' => 24,   'FI' => 25.5, 'FR' => 20,
        'DE' => 19,   'GR' => 24,   'HU' => 27,   'IE' => 23,   'IT' => 22,
        'LV' => 21,   'LT' => 21,   'LU' => 17,   'MT' => 18,   'NL' => 21,
        'PL' => 23,   'PT' => 23,   'RO' => 21,   'SK' => 23,   'SI' => 22,
        'ES' => 21,   'SE' => 25,
        // EEA, non-EU
        'IS' => 24,   'LI' => 8.1,  'NO' => 25,
    ],
];
