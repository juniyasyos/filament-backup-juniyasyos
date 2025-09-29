<?php

namespace Juniyasyos\FilamentLaravelBackup\Models;

use Illuminate\Database\Eloquent\Model;

class BackupSetting extends Model
{
    protected $table = 'filament_backup_settings';

    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
        'allow_manual_runs' => 'boolean',
        'require_password' => 'boolean',
        'encrypt_backups' => 'boolean',
        'use_queue' => 'boolean',
        'scheduled' => 'boolean',
        'notification_targets' => 'array',
        'allowed_disks' => 'array',
        'options' => 'array',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
    ];
}
