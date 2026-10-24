<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Country lookup for the first-party analytics: a free GeoLite2 licence key
    // (php artisan analytics:update-geoip downloads the database weekly).
    'maxmind' => [
        'license_key' => env('MAXMIND_LICENSE_KEY'),
    ],

    // First-party analytics. Behind Cloudflare the CF-IPCountry header gives the
    // country; it is ignored otherwise, because any client could send it.
    'analytics' => [
        'behind_cloudflare' => (bool) env('ANALYTICS_BEHIND_CLOUDFLARE', false),
        'site_id' => env('ANALYTICS_SITE_ID'),
    ],

];
