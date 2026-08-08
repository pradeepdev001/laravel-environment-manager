<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Web UI
    |--------------------------------------------------------------------------
    | Set to false to disable the web-based admin interface entirely.
    */
    'enable_ui' => env('ENV_MANAGER_UI', true),

    /*
    |--------------------------------------------------------------------------
    | Enable REST API
    |--------------------------------------------------------------------------
    | Set to false to disable all REST API endpoints.
    */
    'enable_api' => env('ENV_MANAGER_API', true),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix & Middleware
    |--------------------------------------------------------------------------
    | The URI prefix and middleware stack for the admin UI routes.
    */
    'route_prefix'     => env('ENV_MANAGER_PREFIX', 'env-manager'),
    'route_middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | API Prefix & Middleware
    |--------------------------------------------------------------------------
    */
    'api_prefix'     => env('ENV_MANAGER_API_PREFIX', 'api/env-manager'),
    'api_middleware' => ['api', 'auth:sanctum'],
    'api_rate_limit' => 60,

    /*
    |--------------------------------------------------------------------------
    | Allowed Users
    |--------------------------------------------------------------------------
    | A callable or array of user IDs who can access the package.
    | Set to null to allow all authenticated users (still subject to permissions).
    |
    | Example with array: [1, 2, 3]
    | Example with callable: function ($user) { return $user->is_admin; }
    */
    'allowed_users' => null,

    /*
    |--------------------------------------------------------------------------
    | Authorization Callback
    |--------------------------------------------------------------------------
    | Override the default role-based authorization with your own logic.
    | Receives ($user, $permission) and should return bool.
    | Set to null to use the default role-based system.
    */
    'authorization_callback' => null,

    /*
    |--------------------------------------------------------------------------
    | Bypass Auth in Local Environment
    |--------------------------------------------------------------------------
    | When true and APP_ENV=local, all permissions are granted automatically.
    | This setting has NO effect in production environments.
    */
    'bypass_auth_in_local' => false,

    /*
    |--------------------------------------------------------------------------
    | .env File Path
    |--------------------------------------------------------------------------
    | The absolute path to the .env file to manage.
    */
    'env_file_path' => base_path('.env'),

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    */
    'backup_path'       => storage_path('env-backups'),
    'backup_retention'  => 20,
    'backup_encryption' => false,

    /*
    |--------------------------------------------------------------------------
    | Export Encryption
    |--------------------------------------------------------------------------
    */
    'export_encryption' => false,

    /*
    |--------------------------------------------------------------------------
    | Version History & Audit Log Retention
    |--------------------------------------------------------------------------
    | Number of days to retain records. Set to null for indefinite retention.
    */
    'version_history_retention_days' => 90,
    'audit_log_retention_days'       => 180,

    /*
    |--------------------------------------------------------------------------
    | Cache Commands
    |--------------------------------------------------------------------------
    | Artisan commands to run after every successful .env write operation.
    | Only whitelisted commands are permitted for security.
    */
    'cache_after_save' => true,
    'cache_commands'   => [
        'config:clear',
        'config:cache',
        'route:cache',
        'view:cache',
        'event:cache',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    | Seconds to cache the parsed .env variable list. 0 = no caching.
    */
    'cache_ttl' => 0,

    /*
    |--------------------------------------------------------------------------
    | Sensitive Value Masking Patterns
    |--------------------------------------------------------------------------
    | Glob-style patterns matched against variable keys (case-insensitive).
    | Any matching key will have its value masked in UI, API, and exports.
    */
    'masking_patterns' => [
        'APP_KEY',
        '*_PASSWORD',
        '*_SECRET',
        '*_SECRET_KEY',
        '*_API_KEY',
        '*_TOKEN',
        '*_PRIVATE_KEY',
        '*_ACCESS_KEY',
        'JWT_SECRET',
        'STRIPE_*',
        'AWS_SECRET*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    | Define validation rules for specific keys. Supports:
    | url, email, integer, boolean, enum, required, nullable, min, max, regex
    |
    | Example:
    | 'APP_URL'  => ['url', 'required'],
    | 'DB_PORT'  => ['integer', 'min:1', 'max:65535'],
    | 'APP_ENV'  => ['enum:local,development,staging,production', 'required'],
    */
    'validation_rules' => [
        'APP_URL'          => ['url'],
        'APP_ENV'          => ['enum:local,development,staging,testing,production'],
        'APP_DEBUG'        => ['boolean'],
        'MAIL_PORT'        => ['integer', 'min:1', 'max:65535'],
        'DB_CONNECTION'    => ['enum:mysql,pgsql,sqlite,sqlsrv,mariadb'],
        'CACHE_DRIVER'     => ['enum:array,file,database,redis,memcached,dynamodb,octane'],
        'QUEUE_CONNECTION' => ['enum:sync,database,beanstalkd,sqs,redis,null'],
        'SESSION_DRIVER'   => ['enum:file,cookie,database,apc,memcached,redis,dynamodb,array'],
        'MAIL_MAILER'      => ['enum:smtp,sendmail,mailgun,ses,sparkpost,log,array,failover'],
        'DB_PORT'          => ['integer', 'min:1', 'max:65535'],
        'REDIS_PORT'       => ['integer', 'min:1', 'max:65535'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Category Rules
    |--------------------------------------------------------------------------
    | Map key prefixes to categories. Order matters — first match wins.
    */
    'categories' => [
        'APP_'        => 'Application',
        'LOG_'        => 'Application',
        'DB_'         => 'Database',
        'MAIL_'       => 'Mail',
        'MAILGUN_'    => 'Mail',
        'SES_'        => 'Mail',
        'POSTMARK_'   => 'Mail',
        'CACHE_'      => 'Cache',
        'MEMCACHED_'  => 'Cache',
        'QUEUE_'      => 'Queue',
        'BROADCAST_'  => 'Broadcast',
        'PUSHER_'     => 'Broadcast',
        'ABLY_'       => 'Broadcast',
        'FILESYSTEM_' => 'Filesystem',
        'REDIS_'      => 'Redis',
        'AWS_'        => 'AWS',
        'STRIPE_'     => 'Stripe',
        'SESSION_'    => 'Session',
        'SANCTUM_'    => 'Security',
        'JWT_'        => 'Security',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'on_env_update' => false,

        'mail' => [
            'enabled'    => false,
            'recipients' => [],
        ],

        'slack' => [
            'enabled'     => false,
            'webhook_url' => env('ENV_MANAGER_SLACK_WEBHOOK', ''),
        ],

        'teams' => [
            'enabled'     => false,
            'webhook_url' => env('ENV_MANAGER_TEAMS_WEBHOOK', ''),
        ],

        'webhook' => [
            'enabled' => false,
            'url'     => env('ENV_MANAGER_WEBHOOK_URL', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Settings
    |--------------------------------------------------------------------------
    */
    'per_page' => 25,

    /*
    |--------------------------------------------------------------------------
    | Environment Snapshots for Comparison
    |--------------------------------------------------------------------------
    | Map environment names to their .env file paths for the comparison feature.
    */
    'environments' => [
        // 'local'       => base_path('.env'),
        // 'staging'     => '/path/to/staging/.env',
        // 'production'  => '/path/to/production/.env',
    ],

];
