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

    'emitto' => [
        'secret' => env('EMITTO_SECRET_KEY'),
        'base_url' => env('EMITTO_BASE_URL', 'https://api-emitto.softecsa.com'),
    ],

    'personas' => [
        'token' => env('API_PERSONAS', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2xvZ2luIiwiaWF0IjoxNzI1OTE0NDU3LCJleHAiOjE3NTc0NTA0NTcsIm5iZiI6MTcyNTkxNDQ1NywianRpIjoiZnVsQ2M2cW1kbnRCVXBJTSIsInN1YiI6IjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.o-QZmnktnyzDUaoYc_KIHUeYV_arfq14H5g-0G8VJGA'),
        'base_url' => env('URL_PERSONAS', 'https://apipersonas.softecsa.com/'),
    ],

];
