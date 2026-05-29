<?php

namespace Juniyasyos\FilamentLaravelBackup\Commands;

use Illuminate\Console\Command;
use Juniyasyos\FilamentLaravelBackup\Enums\Option;
use Juniyasyos\FilamentLaravelBackup\Jobs\ImprovedBackupJob;
use Juniyasyos\FilamentLaravelBackup\Models\BackupConfiguration;
use Juniyasyos\FilamentLaravelBackup\Models\BackupLog;

class RunScheduledBackupCommand extends Command
{
    protected $signature = 'backup:run-scheduled {--force : Run even when the cron expression is not due}';

    protected $description = 'Run the configured automatic backup when the schedule is due';

    public function handle(): int
    {
        logger()->info('backup:run-scheduled command started');

        $configuration = BackupConfiguration::ensureDefaults();
        $force = (bool) $this->option('force');

        if (! $configuration->schedule_enabled && ! $force) {
            logger()->info('backup schedule skipped', [
                'reason' => 'schedule disabled',
                'settings' => $configuration->toArray(),
                'now' => now()->toDateTimeString(),
            ]);
            $this->info('Scheduled backup is disabled.');
            return self::SUCCESS;
        }

        $isDue = $configuration->isScheduledBackupDue();

        if (! $force && ! $isDue) {
            logger()->info('backup schedule skipped', [
                'reason' => 'not due',
                'settings' => $configuration->toArray(),
                'now' => now()->toDateTimeString(),
                'last_run_at' => $configuration->schedule_last_run_at,
                'interval_value' => $configuration->schedule_interval_value,
                'interval_unit' => $configuration->schedule_interval_unit,
            ]);
            $this->info('Scheduled backup is not due yet.');
            return self::SUCCESS;
        }

        logger()->info('backup job dispatched from scheduled command', [
            'settings' => $configuration->toArray(),
            'now' => now()->toDateTimeString(),
        ]);

        $job = ImprovedBackupJob::dispatch(
            $configuration->getScheduleBackupOption(),
            null,
            null,
            null,
            [
                'initiated_via' => 'scheduler',
                'schedule_backup_type' => $configuration->schedule_backup_type,
                'schedule_interval_value' => $configuration->schedule_interval_value,
                'schedule_interval_unit' => $configuration->schedule_interval_unit,
                'schedule_forced' => $force,
            ]
        );

        if ($configuration->exists) {
            $configuration->forceFill([
                'schedule_last_run_at' => now(),
            ])->save();
        }

        BackupLog::logInfo('Scheduled backup dispatched', [
            'backup_type' => $configuration->schedule_backup_type,
            'interval_value' => $configuration->schedule_interval_value,
            'interval_unit' => $configuration->schedule_interval_unit,
            'forced' => $force,
        ]);

        $this->info('Scheduled backup dispatched to queue.');

        return self::SUCCESS;
    }
}