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

    'microsoft' => [
        'enabled' => filter_var(env('MICROSOFT_LOGIN_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'password_login_enabled' => filter_var(env('MICROSOFT_PASSWORD_LOGIN_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('MICROSOFT_REDIRECT_URI'),
        'tenant' => env('MICROSOFT_TENANT_ID', 'organizations'),
        'allowed_domains' => array_values(array_filter(array_map(
            fn ($domain) => strtolower(trim(ltrim($domain, '@'))),
            explode(',', env('MICROSOFT_ALLOWED_DOMAINS', ''))
        ))),
        'auto_create_users' => filter_var(env('MICROSOFT_AUTO_CREATE_USERS', false), FILTER_VALIDATE_BOOLEAN),
        'scopes' => array_values(array_filter(preg_split(
            '/\s+/',
            trim(env('MICROSOFT_SCOPES', 'openid profile email User.Read'))
        ))),
        'prompt' => env('MICROSOFT_PROMPT', 'select_account'),
        'include_tenant_info' => true,
    ],

];
