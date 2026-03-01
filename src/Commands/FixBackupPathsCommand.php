<?php

namespace Juniyasyos\FilamentLaravelBackup\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Juniyasyos\FilamentLaravelBackup\Models\BackupJob;

class FixBackupPathsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:fix-paths 
                          {--dry-run : Show what would be updated without making changes}
                          {--disk= : Only fix paths for specific disk}';

    /**
     * The console command description.
     */
    protected $description = 'Fix missing or incorrect backup file paths in database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $targetDisk = $this->option('disk');

        $this->info('Starting backup path fix process...');

        if ($dryRun) {
            $this->warn('Running in DRY RUN mode - no changes will be made');
        }

        // Get backup jobs that need path fixing
        $query = BackupJob::query()
            ->where('status', BackupJob::STATUS_COMPLETED)
            ->where(function ($q) {
                $q->whereNull('path')
                    ->orWhere('path', '');
            });

        if ($targetDisk) {
            $query->where('disk', $targetDisk);
        }

        $backupJobs = $query->get();

        if ($backupJobs->isEmpty()) {
            $this->info('No backup jobs found that need path fixing.');
            return 0;
        }

        $this->info("Found {$backupJobs->count()} backup jobs to process");

        $fixed = 0;
        $notFound = 0;
        $errors = 0;

        foreach ($backupJobs as $job) {
            try {
                $result = $this->fixBackupPath($job, $dryRun);

                if ($result['found']) {
                    $fixed++;
                    $this->line("✓ Job #{$job->id}: {$result['message']}");
                } else {
                    $notFound++;
                    $this->error("✗ Job #{$job->id}: {$result['message']}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Job #{$job->id}: Error - {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->line("  Fixed: {$fixed}");
        $this->line("  Not found: {$notFound}");
        $this->line("  Errors: {$errors}");

        if ($dryRun && $fixed > 0) {
            $this->newLine();
            $this->info('Run without --dry-run to apply the changes.');
        }

        return 0;
    }

    protected function fixBackupPath(BackupJob $job, bool $dryRun): array
    {
        $disk = $job->disk;
        $filename = $job->filename;
        $backupName = config('backup.backup.name', 'Laravel');

        if (!$filename) {
            return [
                'found' => false,
                'message' => 'No filename available'
            ];
        }

        // Possible paths to check
        $pathsToCheck = [];

        if (in_array($disk, ['local', 'backup'])) {
            if ($disk === 'backup') {
                // For backup disk, check relative paths
                $pathsToCheck = [
                    $backupName . '/' . $filename,
                    $filename,
                    'backups/' . $filename,
                ];
            } else {
                // For local disk, check actual filesystem then fallback to storage methods
                $actualPath = storage_path('app/backup/' . $backupName . '/' . $filename);
                if (file_exists($actualPath)) {
                    $foundPath = 'backup/' . $backupName . '/' . $filename;

                    if (!$dryRun) {
                        $job->update(['path' => $foundPath]);
                    }

                    return [
                        'found' => true,
                        'message' => "Set path to: {$foundPath} (filesystem check)"
                    ];
                }

                // Fallback paths for local
                $pathsToCheck = [
                    'backup/' . $backupName . '/' . $filename,
                    'backups/' . $filename,
                    $backupName . '/' . $filename,
                    $filename,
                ];
            }
        } else {
            // Cloud storage paths
            $pathsToCheck = [
                'backup/' . $backupName . '/' . $filename,
                'backups/' . $filename,
                $backupName . '/' . $filename,
                $filename,
            ];
        }

        // Check each path using Storage facade
        foreach ($pathsToCheck as $checkPath) {
            try {
                if (Storage::disk($disk)->exists($checkPath)) {
                    if (!$dryRun) {
                        $job->update(['path' => $checkPath]);
                    }

                    return [
                        'found' => true,
                        'message' => "Set path to: {$checkPath}"
                    ];
                }
            } catch (\Exception $e) {
                // Continue checking other paths if one fails
                continue;
            }
        }

        return [
            'found' => false,
            'message' => "File not found in any expected location. Disk: {$disk}, Filename: {$filename}"
        ];
    }
}
