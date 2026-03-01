<?php

namespace Juniyasyos\FilamentLaravelBackup\Commands;

use Illuminate\Console\Command;
use Juniyasyos\FilamentLaravelBackup\Models\BackupLog;
use Juniyasyos\FilamentLaravelBackup\Models\BackupSetting;

class CleanupBackupLogsCommand extends Command
{
    protected $signature = 'backup:cleanup-logs 
                            {--days= : Number of days to keep logs (default from settings)}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Clean up old backup logs';

    public function handle(): int
    {
        $this->info('Starting backup logs cleanup...');

        $days = (int) ($this->option('days') ?? BackupSetting::get('backup.general.cleanup_days', 30));
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No actual deletion will occur');
        }

        $this->info("Cleaning up logs older than {$days} days");

        $cutoffDate = now()->subDays($days);
        $query = BackupLog::where('created_at', '<', $cutoffDate);

        if ($isDryRun) {
            $count = $query->count();
            $stats = $query->selectRaw('level, COUNT(*) as count')
                ->groupBy('level')
                ->pluck('count', 'level')
                ->toArray();

            $this->table(
                ['Level', 'Count'],
                collect($stats)->map(fn($count, $level) => [$level, $count])->toArray()
            );

            $this->info("Would delete {$count} log entries");
        } else {
            $deleted = $query->delete();
            $this->info("Deleted {$deleted} log entries");
        }

        return self::SUCCESS;
    }
}
