<?php

namespace Juniyasyos\FilamentLaravelBackup\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Juniyasyos\FilamentLaravelBackup\Enums\BackupStatus;
use Juniyasyos\FilamentLaravelBackup\Enums\Option;

class BackupRun extends Model
{
    protected $table = 'filament_backup_runs';

    protected $guarded = [];

    protected $casts = [
        'status' => BackupStatus::class,
        'option' => Option::class,
        'disks' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (BackupRun $run): void {
            if (! $run->uuid) {
                $run->uuid = (string) Str::uuid();
            }
        });
    }

    public function initiator(): MorphTo
    {
        return $this->morphTo();
    }

    public function markRunning(): void
    {
        $this->forceFill([
            'status' => BackupStatus::RUNNING,
            'started_at' => now(),
        ])->save();
    }

    public function markFinishedSuccess(?string $disk, ?string $filename, ?int $sizeInBytes, ?string $output): void
    {
        $this->forceFill([
            'status' => BackupStatus::SUCCESS,
            'disk' => $disk,
            'filename' => $filename,
            'size_in_bytes' => $sizeInBytes,
            'output' => $output,
            'finished_at' => now(),
        ])->save();
    }

    public function markFailed(\Throwable $throwable, ?string $output = null): void
    {
        $this->forceFill([
            'status' => BackupStatus::FAILED,
            'exception_message' => $throwable->getMessage(),
            'exception_trace' => $throwable->getTraceAsString(),
            'output' => $output,
            'finished_at' => now(),
        ])->save();
    }
}
