<?php

namespace Juniyasyos\FilamentLaravelBackup\Commands;

use Illuminate\Console\Command;
use Juniyasyos\FilamentLaravelBackup\Models\BackupJob;

class CleanupBackupJobsCommand extends Command
{
    protected $signature = 'backup:cleanup-jobs {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Clean up old backup jobs and associated files';

    public function handle(): int
    {
        $this->info('Starting backup jobs cleanup...');

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No actual deletion will occur');
        }

        $deleted = 0;

        if ($isDryRun) {
            $jobs = BackupJob::forCleanup()->get();
            $this->table(
                ['ID', 'Name', 'Status', 'Created', 'Cleanup At', 'File Size'],
                $jobs->map(function ($job) {
                    return [
                        $job->id,
                        $job->name,
                        $job->status,
                        $job->created_at->format('Y-m-d H:i:s'),
                        $job->cleanup_at?->format('Y-m-d H:i:s'),
                        $job->formatted_file_size,
                    ];
                })->toArray()
            );

            $this->info("Would delete {$jobs->count()} backup jobs");
        } else {
            $deleted = BackupJob::cleanup();
            $this->info("Deleted {$deleted} backup jobs and associated files");
        }

        return self::SUCCESS;
    }
}
