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
        'backups' => [
            'actions' => [
                'create_backup' => 'Create Backup',
            ],

            'heading' => 'Backups',

            'messages' => [
                'backup_success' => 'Creating a new backup in background.',
                'backup_delete_success' => 'Deleting this backup in background.',
            ],

            'form' => [
                'option' => [
                    'label' => 'Backup Option',
                    'all' => 'All (Database + Files)',
                    'only_db' => 'Only Database',
                    'only_files' => 'Only Files',
                ],
            ],

            'modal' => [
                'heading' => 'Select Backup Type',
                'submit' => 'Run Backup',
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

        'settings' => [
            'heading' => 'Backup Settings',

            'navigation' => [
                'label' => 'Backup Settings',
            ],

            'sections' => [
                'general' => 'General',
                'security' => 'Security',
                'queue_and_notifications' => 'Queue & Notifications',
                'scheduling' => 'Scheduling',
                'retention' => 'Retention',
                'scopes' => 'Scopes',
                'advanced' => 'Advanced',
            ],

            'fields' => [
                'enabled' => 'Enabled',
                'allow_manual_runs' => 'Allow Manual Runs',
                'require_password' => 'Require Password',
                'new_password' => 'New Password',
                'encrypt_backups' => 'Encrypt Backups',
                'encryption_password' => 'Encryption Password',
                'use_queue' => 'Use Queue',
                'queue' => 'Queue Name',
                'notification_channel' => 'Notification Channel',
                'notification_targets' => 'Notification Targets',
                'scheduled' => 'Enable Schedule',
                'schedule_cron' => 'Schedule CRON',
                'retention_days' => 'Retention (Days)',
                'retention_copies' => 'Retention (Copies)',
                'allowed_disks' => 'Allowed Disks',
                'options' => 'Options (key => value)',
            ],

            'helper_texts' => [
                'password' => 'Leave blank if you do not want to change it.',
                'encryption_password' => 'Provide the passphrase used to encrypt generated archives.',
                'queue' => 'Leave empty to use the default queue connection.',
                'schedule_cron' => 'CRON format. Example: "0 3 * * *" for 03:00 every day.',
            ],

            'placeholders' => [
                'notification_channel' => 'mail, slack, database, etc.',
                'notification_targets' => 'email/username/channel',
                'allowed_disks' => 'type a disk name...',
            ],

            'actions' => [
                'save' => [
                    'label' => 'Save settings',
                ],
                'add_option' => 'Add Option',
            ],

            'descriptions' => [
                'general' => 'Configure basic behaviour and access restrictions before running backups.',
                'security' => 'Protect your backups with passwords and optional archive encryption.',
                'queue_and_notifications' => 'Control which queue processes backups and who gets notified.',
                'scheduling' => 'Automate backup execution on a recurring CRON expression.',
                'retention' => 'Limit how long backups are stored to conserve disk space.',
                'scopes' => 'Restrict which storage disks appear within the backup page.',
                'advanced' => 'Fine-tune extra options passed to the backup command.',
            ],

            'notifications' => [
                'saved' => [
                    'title' => 'Settings saved',
                    'body' => 'Backup configuration updated successfully.',
                ],
                'password_required' => [
                    'title' => 'Password required',
                    'body' => 'Fill in a password to enable password protection.',
                ],
                'encryption_password_required' => [
                    'title' => 'Encryption password required',
                    'body' => 'Fill in an encryption password to enable encryption.',
                ],
            ],
        ],
    ],

];
