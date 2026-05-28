<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Filament Backup Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file allows you to customize the behavior of the
    | Filament Backup plugin. You can override these settings as needed
    | for your application.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | These are the default configuration values that will be used if no
    | database settings are found or if settings are being reset.
    |
    */
    'defaults' => [
        'general' => [
            'timeout' => 3600, // 1 hour in seconds
            'queue' => 'default',
            'cleanup_enabled' => true,
            'cleanup_days' => 30,
            'notifications_enabled' => true,
        ],

        'storage' => [
            'default_disk' => 'local',
            'local' => [
                'path' => 'storage/app/backup',
            ],
            's3' => [
                'bucket' => env('BACKUP_S3_BUCKET', ''),
                'region' => env('BACKUP_S3_REGION', 'us-east-1'),
                'key' => env('BACKUP_S3_KEY', ''),
                'secret' => env('BACKUP_S3_SECRET', ''),
            ],
        ],

        'notifications' => [
            'on_success' => true,
            'on_failure' => true,
            'progress_updates' => false,
            'recipients' => env('BACKUP_NOTIFICATION_RECIPIENTS', ''),
            'notify_user' => true,
        ],

        'security' => [
            'require_permission' => true,
            'allowed_roles' => 'admin,backup-manager',
            'encrypt_backups' => false,
            'encryption_key' => env('BACKUP_ENCRYPTION_KEY', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Configuration
    |--------------------------------------------------------------------------
    |
    | Configure backup job behavior and settings.
    |
    */
    'job' => [
        'max_retries' => 3,
        'retry_delay' => 60, // seconds
        'max_retry_delay' => 3600, // 1 hour max delay
        'timeout' => null, // Use default from general settings

        // Progress notification intervals (percentage)
        'progress_notifications' => [25, 50, 75, 100],

        // Enable real-time broadcasting
        'enable_broadcasting' => false,

        // Cleanup configuration
        'auto_cleanup_temp_files' => true,
        'temp_directory' => storage_path('app/temp'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure backup logging behavior.
    |
    */
    'logging' => [
        'enabled' => true,
        'level' => 'info', // debug, info, warning, error
        'retention_days' => 90,
        'max_log_size' => 10485760, // 10MB in bytes

        // Performance logging
        'log_performance' => true,
        'log_memory_usage' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the Filament UI behavior.
    |
    */
    'ui' => [
        'polling_interval' => '5s',
        'enable_real_time_updates' => true,
        'show_statistics' => true,
        'show_progress_bar' => true,

        // Navigation
        'navigation_group' => 'Backup Management',
        'navigation_sort' => null,

        // Table configuration
        'records_per_page' => 25,
        'show_status_table' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Define validation rules for backup settings.
    |
    */
    'validation' => [
        'timeout' => ['required', 'integer', 'min:60', 'max:7200'],
        'cleanup_days' => ['required', 'integer', 'min:1', 'max:365'],
        'queue' => ['required', 'string', 'max:255'],
        's3_bucket' => ['nullable', 'string', 'max:255'],
        's3_region' => ['nullable', 'string', 'max:50'],
        'local_path' => ['required', 'string', 'max:500'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features of the backup system.
    |
    */
    'features' => [
        'database_backup' => true,
        'files_backup' => true,
        'compression' => true,
        'encryption' => false,
        'verification' => true,
        'cloud_storage' => true,
        'scheduled_backups' => false, // Future feature
        'backup_splitting' => false, // Future feature
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Providers
    |--------------------------------------------------------------------------
    |
    | Configure available storage providers and their specific settings.
    |
    */
    'storage_providers' => [
        'local' => [
            'name' => 'Local Storage',
            'driver' => 'local',
            'icon' => 'heroicon-o-server',
            'enabled' => true,
        ],

        's3' => [
            'name' => 'Amazon S3',
            'driver' => 's3',
            'icon' => 'heroicon-o-cloud',
            'enabled' => true,
            'regions' => [
                'us-east-1' => 'US East (N. Virginia)',
                'us-east-2' => 'US East (Ohio)',
                'us-west-1' => 'US West (N. California)',
                'us-west-2' => 'US West (Oregon)',
                'eu-west-1' => 'Europe (Ireland)',
                'eu-west-2' => 'Europe (London)',
                'eu-central-1' => 'Europe (Frankfurt)',
                'ap-southeast-1' => 'Asia Pacific (Singapore)',
                'ap-southeast-2' => 'Asia Pacific (Sydney)',
                'ap-northeast-1' => 'Asia Pacific (Tokyo)',
            ],
        ],

        'gcs' => [
            'name' => 'Google Cloud Storage',
            'driver' => 'gcs',
            'icon' => 'heroicon-o-cube',
            'enabled' => false, // Not implemented yet
        ],

        'azure' => [
            'name' => 'Azure Blob Storage',
            'driver' => 'azure',
            'icon' => 'heroicon-o-building-office',
            'enabled' => false, // Not implemented yet
        ],
    ],
];
