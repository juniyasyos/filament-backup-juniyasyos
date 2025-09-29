<?php

namespace Juniyasyos\FilamentLaravelBackup\Models;

use Illuminate\Database\Eloquent\Model;

class BackupSetting extends Model
{
    protected $table = 'filament_backup_settings';

    protected $guarded = [];

    protected $fillable = [
        'enabled',
        'allow_manual_runs',
        'require_password',
        'password',
        'encrypt_backups',
        'encryption_password',
        'use_queue',
        'queue',
        'notification_channel',
        'notification_targets',
        'scheduled',
        'schedule_cron',
        'next_run_at',
        'last_run_at',
        'retention_days',
        'retention_copies',
        'allowed_disks',
        'options',
    ];

    protected $casts = [
        'enabled'              => 'boolean',
        'allow_manual_runs'    => 'boolean',
        'require_password'     => 'boolean',
        'encrypt_backups'      => 'boolean',
        'use_queue'            => 'boolean',
        'scheduled'            => 'boolean',
        'next_run_at'          => 'datetime',
        'last_run_at'          => 'datetime',
        'retention_days'       => 'integer',
        'retention_copies'     => 'integer',
        'notification_targets' => 'array',
        'allowed_disks'        => 'array',
        'options'              => 'array',
    ];

    public static function singleton(): self
    {
        return static::query()->firstOrCreate([], [
            'enabled' => true,
            'allow_manual_runs' => true,
            'use_queue' => true,
            'scheduled' => false,
        ]);
    }
}
