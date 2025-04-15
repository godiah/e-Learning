<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'pesapal' => [
        'environment' => env('PESAPAL_ENVIRONMENT', 'sandbox'),
        'base_url' => env('PESAPAL_BASE_URL', 'https://cybqa.pesapal.com/pesapalv3'),
        'base_url_live' => env('PESAPAL_BASE_URL_LIVE', 'https://pay.pesapal.com/v3'),
        'consumer_key' => env('PESAPAL_CONSUMER_KEY'),
        'consumer_secret' => env('PESAPAL_CONSUMER_SECRET'),
        // 'consumer_key_live' => env('PESAPAL_CONSUMER_KEY_LIVE'),
        // 'consumer_secret_live' => env('PESAPAL_CONSUMER_SECRET_LIVE'),
        'callback_url' => env('PESAPAL_CALLBACK_URL', '/api/payments/pesapal/callback'),
        'ipn_url' => env('PESAPAL_IPN_URL', config('app.url') . '/api/payments/pesapal/ipn'),
    ],

];
