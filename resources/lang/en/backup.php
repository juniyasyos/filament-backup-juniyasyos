<?php

return [

    'components' => [
        'backup_destination_list' => [
            'table' => [
                'actions' => [
                    'download' => 'Download',
                    'delete' => 'Delete',
                ],

                'fields' => [
                    'path' => 'Path',
                    'disk' => 'Disk',
                    'date' => 'Date',
                    'size' => 'Size',
                ],

                'filters' => [
                    'disk' => 'Disk',
                ],
            ],
        ],

        'backup_destination_status_list' => [
            'table' => [
                'fields' => [
                    'name' => 'Name',
                    'disk' => 'Disk',
                    'healthy' => 'Healthy',
                    'amount' => 'Amount',
                    'newest' => 'Newest',
                    'used_storage' => 'Used Storage',
                ],
            ],
        ],
    ],

    'pages' => [
        'settings' => [
            'heading' => 'Backup Settings',
            'navigation_label' => 'Settings',

            'common' => [
                'yes' => 'Yes',
                'no' => 'No',
            ],

            'general' => [
                'section' => 'Backup Configuration',
                'description' => 'General backup settings and preferences',
                'timeout_label' => 'Backup Timeout (seconds)',
                'timeout_helper' => 'Maximum time allowed for the backup process (seconds). Default: 3600',
                'queue_label' => 'Queue Name',
                'queue_helper' => 'Which queue to push backup jobs to (e.g. default, high). Keep default unless you need separation',
                'cleanup_label' => 'Auto Cleanup Enabled',
                'cleanup_helper' => 'Automatically remove old backups according to cleanup days',
                'cleanup_days_label' => 'Cleanup After Days',
                'cleanup_days_helper' => 'Number of days to keep backups before automatic cleanup',
                'notifications_enabled_label' => 'Email Notifications',
                'notifications_enabled_helper' => 'Toggle to send email notifications for backup events',
            ],

            'schedule' => [
                'section' => 'Automatic Schedule',
                'description' => 'Set how often automatic backups should run',
                'enabled_label' => 'Enable Automatic Backup',
                'enabled_helper' => 'Turn on automatic backup scheduling',
                'interval_value_label' => 'Repeat Every',
                'interval_value_helper' => 'Examples: 1, 2, 3, 4',
                'interval_unit_label' => 'Time Unit',
                'interval_unit_helper' => 'Choose second, minute, hour, day, or month',
                'unit_second' => 'Second(s)',
                'unit_minute' => 'Minute(s)',
                'unit_hour' => 'Hour(s)',
                'unit_day' => 'Day(s)',
                'unit_month' => 'Month(s)',
                'hint_label' => 'Format Hint',
                'hint_helper' => 'Simple format only. Example: every 1 day or every 2 hours',
                'hint_default' => 'Use numbers only: 1, 2, 3, 4... then choose the unit.',
                'preview_label' => 'Preview',
                'preview_enabled' => 'Backups will run every :value :unit.',
                'preview_disabled' => 'Schedule is disabled.',
            ],

            'storage' => [
                'section' => 'Storage Configuration',
                'description' => 'Configure where backups are stored',
                'default_disk_label' => 'Default Storage Disk',
                'default_disk_helper' => 'Primary storage disk for backups',
                'local_option' => 'Local Storage',
                's3_option' => 'Amazon S3',
                'minio_option' => 'MinIO',
                'gcs_option' => 'Google Cloud Storage',
                'local_section' => 'Local Storage',
                'local_description' => 'Local file system storage configuration',
                'local_path_label' => 'Local Storage Path',
                'local_path_helper' => 'Relative path inside the app where backups are stored (e.g. storage/app/backup). Use absolute path only if necessary',
                's3_section' => 'Amazon S3 Storage',
                's3_description' => 'Amazon S3 cloud storage configuration',
                's3_bucket_label' => 'S3 Bucket Name',
                's3_bucket_helper' => 'The S3 bucket where backups will be stored (e.g. my-app-backups)',
                's3_region_label' => 'S3 Region',
                's3_region_helper' => 'Amazon S3 region',
                's3_key_label' => 'S3 Access Key',
                's3_key_helper' => 'Access Key ID for S3. For security, prefer using environment variables if possible',
                's3_secret_label' => 'S3 Secret Key',
                's3_secret_helper' => 'Secret access key for S3. Store securely (env vars recommended)',
                'minio_section' => 'MinIO Storage',
                'minio_description' => 'Configure MinIO-compatible S3 storage',
                'minio_bucket_label' => 'MinIO Bucket Name',
                'minio_bucket_helper' => 'The bucket used to store backups in MinIO',
                'minio_endpoint_label' => 'MinIO Endpoint',
                'minio_endpoint_helper' => 'MinIO endpoint URL, for example http://127.0.0.1:9000',
                'minio_key_label' => 'MinIO Username',
                'minio_key_helper' => 'MinIO username or access key (e.g. MINIO_ROOT_USER)',
                'minio_secret_label' => 'MinIO Password',
                'minio_secret_helper' => 'MinIO password or secret key (e.g. MINIO_ROOT_PASSWORD)',
                'minio_path_style_label' => 'Use Path Style Endpoint',
                'minio_path_style_helper' => 'Enable path-style addressing for MinIO/S3-compatible storage',
                'gcs_section' => 'Google Cloud',
                'status' => [
                    'available' => 'Available and configured',
                    'requires_configuration' => 'Requires configuration',
                    'not_configured' => 'Not configured',
                ],
            ],

            'buttons' => [
                'save' => 'Save Settings',
                'test_storage' => 'Test Storage',
                'reset' => 'Reset to Defaults',
                'saving' => 'Saving...',
                'testing' => 'Testing...',
            ],

            'recent' => [
                'heading' => 'Recent Configuration Changes',
                'description' => 'Log of recent changes to backup configuration.',
                'empty' => 'No recent changes to display.',
            ],

            'notifications' => [
                'section' => 'Email Notifications',
                'description' => 'Configure email notification settings',
                'on_success_label' => 'Notify on Success',
                'on_success_helper' => 'Send notification when backup completes successfully',
                'on_failure_label' => 'Notify on Failure',
                'on_failure_helper' => 'Send notification when backup fails',
                'progress_updates_label' => 'Progress Updates',
                'progress_updates_helper' => 'Send periodic progress updates during backup',
                'recipients_section' => 'Recipients',
                'recipients_description' => 'Configure who receives notifications',
                'recipients_label' => 'Email Recipients',
                'recipients_helper' => 'Add email addresses that will receive notifications',
                'recipient_email_label' => 'Email',
                'notify_user_label' => 'Notify Backup Creator',
                'notify_user_helper' => 'Send notifications to the user who initiated the backup',
            ],

            'security' => [
                'access_section' => 'Access Control',
                'access_description' => 'Security and access control settings',
                'require_permission_label' => 'Require Permission',
                'require_permission_helper' => 'Require specific permission to access backup features',
                'allowed_roles_label' => 'Allowed Roles (comma separated)',
                'allowed_roles_helper' => 'Roles that can access backup features. Enter roles separated by commas, e.g. admin,backup-manager',
                'file_section' => 'File Security',
                'file_description' => 'File security and encryption settings',
                'encrypt_backups_label' => 'Encrypt Backups',
                'encrypt_backups_helper' => 'Encrypt backup files for additional security',
                'encryption_key_label' => 'Encryption Key',
                'encryption_key_helper' => 'Optional: provide a key to encrypt backup files. Leave empty to auto-generate and store externally',
            ],

            'raw' => [
                'section' => 'Raw Backup Settings',
                'description' => 'Pretty-printed raw data from the backup_settings table.',
            ],
        ],

        'backups' => [
            'actions' => [
                'create_backup' => 'Create Backup',
            ],

            'heading' => 'Backups',

            'messages' => [
                'backup_success' => 'Creating a new backup in background.',
                'backup_delete_success' => 'Deleting this backup in background.',
            ],

            'modal' => [
                'buttons' => [
                    'only_db' => 'Only DB',
                    'only_files' => 'Only Files',
                    'db_and_files' => 'DB & Files',
                ],

                'label' => 'Please choose an option',
            ],

            'navigation' => [
                'group' => 'Settings',
                'label' => 'Backups',
            ],
        ],
    ],

];
