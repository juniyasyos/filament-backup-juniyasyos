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
        $configuration = BackupConfiguration::ensureDefaults();

        $force = (bool) $this->option('force');

        if (! $configuration->schedule_enabled && ! $force) {
            $this->info('Scheduled backup is disabled.');

            return self::SUCCESS;
        }

        $isDue = $configuration->isScheduledBackupDue();

        if (! $force && ! $isDue) {
            $this->info('Scheduled backup is not due yet.');

            return self::SUCCESS;
        }

        $job = ImprovedBackupJob::dispatch(
            Option::ALL,
            null,
            null,
            null,
            [
                'initiated_via' => 'scheduler',
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
            'interval_value' => $configuration->schedule_interval_value,
            'interval_unit' => $configuration->schedule_interval_unit,
            'forced' => $force,
        ]);

        $this->info('Scheduled backup dispatched to queue.');

        return self::SUCCESS;
    }
}