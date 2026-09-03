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

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),
        'timeout' => env('GEMINI_TIMEOUT', 20),
    ],

    'whatsapp' => [
        'api_version' => env('WHATSAPP_API_VERSION', 'v22.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'default_language' => env('WHATSAPP_DEFAULT_LANGUAGE', 'es_MX'),

        // Ademia's Tech Provider Business Manager: one system user token
        // operates every corretaje's WABA once they grant access via
        // Embedded Signup, so no per-tenant token is needed.
        'embedded_signup' => [
            'meta_app_id' => env('WHATSAPP_META_APP_ID'),
            'meta_app_secret' => env('WHATSAPP_META_APP_SECRET'),
            'config_id' => env('WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID'),
            'system_user_token' => env('WHATSAPP_SYSTEM_USER_TOKEN'),
        ],
    ],

];
