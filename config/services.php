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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sms' => [
        'url' => env('SMS_API_URL', 'https://edge.ippanel.com/v1/api/send'),
        'authorization' => env('SMS_AUTHORIZATION', env('SMS_API_TOKEN')),
        'pattern_code' => env('SMS_PATTERN_CODE', 'f532bys7isf15yl'),
        'sending_type' => env('SMS_SENDING_TYPE', 'pattern'),
        'sender' => env('SMS_SENDER', '+983000505'),
        'timeout' => (int) env('SMS_TIMEOUT', 10),
    ],

];
