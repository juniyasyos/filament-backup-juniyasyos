<?php

namespace Juniyasyos\FilamentLaravelBackup\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Juniyasyos\FilamentLaravelBackup\Enums\Option;
use Juniyasyos\FilamentLaravelBackup\Models\BackupJob;
use Juniyasyos\FilamentLaravelBackup\Models\BackupSetting;
use Juniyasyos\FilamentLaravelBackup\Models\BackupLog;
use Juniyasyos\FilamentLaravelBackup\Services\BackupService;
use Juniyasyos\FilamentLaravelBackup\Notifications\BackupProgressNotification;
use Juniyasyos\FilamentLaravelBackup\Notifications\BackupCompletedNotification;
use Juniyasyos\FilamentLaravelBackup\Notifications\BackupFailedNotification;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\Tasks\Backup\BackupJob as SpatieBackupJob;

class ImprovedBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout;
    public $tries = 3;
    public $maxExceptions = 3;
    public $backoff = [30, 60, 120]; // Exponential backoff in seconds

    protected BackupJob $jobRecord;
    protected BackupService $backupService;
    protected array $steps = [
        'initializing' => 'Initializing backup process',
        'validation' => 'Validating configuration and permissions',
        'database_backup' => 'Creating database backup',
        'files_backup' => 'Backing up files',
        'compressing' => 'Compressing backup files',
        'uploading' => 'Uploading to storage destination',
        'verification' => 'Verifying backup integrity',
        'cleanup' => 'Cleaning up temporary files',
        'notification' => 'Sending completion notification'
    ];

    public function __construct(
        protected readonly Option $option = Option::ALL,
        protected readonly ?string $customFilename = null,
        protected readonly ?int $userId = null,
        protected readonly ?string $userType = null,
        protected readonly array $additionalOptions = []
    ) {
        // Set timeout from settings
        $this->timeout = BackupSetting::get('backup.general.timeout', 3600);

        // Set queue from settings  
        $queueName = BackupSetting::get('backup.general.queue', 'default');
        $this->onQueue($queueName);
    }

    public function handle(BackupService $backupService): void
    {
        $this->backupService = $backupService;

        // Create job record for tracking
        $this->createJobRecord();

        try {
            $this->executeBackup();
        } catch (\Exception $e) {
            $this->handleFailure($e);
            throw $e;
        }
    }

    protected function createJobRecord(): void
    {
        $this->jobRecord = BackupJob::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => $this->generateJobName(),
            'type' => $this->mapOptionToType($this->option),
            'status' => BackupJob::STATUS_QUEUED,
            'options' => array_merge([
                'option' => $this->option->value,
                'custom_filename' => $this->customFilename,
            ], $this->additionalOptions),
            'disk' => BackupSetting::get('backup.storage.default_disk', 'local'),
            'user_id' => $this->userId,
            'user_type' => $this->userType,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'queue_name' => $this->queue,
            'connection' => $this->connection,
            'job_payload' => [
                'option' => $this->option->value,
                'filename' => $this->customFilename,
                'additional_options' => $this->additionalOptions,
            ],
            'max_retries' => $this->tries,
        ]);

        // Initialize all steps
        foreach ($this->steps as $stepKey => $stepName) {
            $this->jobRecord->addStep($stepKey);
        }

        BackupLog::logInfo("Backup job created", [
            'job_id' => $this->jobRecord->id,
            'type' => $this->jobRecord->type,
            'option' => $this->option->value,
        ], $this->jobRecord);
    }

    protected function executeBackup(): void
    {
        $this->jobRecord->markAsProcessing();
        $startTime = microtime(true);

        // Step 1: Initialize
        $this->updateProgress(5, 'initializing');
        $this->validateConfiguration();

        // Step 2: Validation
        $this->updateProgress(10, 'validation');
        $this->validateStoragePermissions();

        // Step 3: Create backup based on type
        $backupPath = null;

        if ($this->option === Option::ALL || $this->option === Option::ONLY_DB) {
            $this->updateProgress(20, 'database_backup');
            $this->createDatabaseBackup();
        }

        if ($this->option === Option::ALL || $this->option === Option::ONLY_FILES) {
            $this->updateProgress(40, 'files_backup');
            $this->createFilesBackup();
        }

        // Step 4: Create final backup file
        $this->updateProgress(60, 'compressing');
        $backupPath = $this->createFinalBackup();

        // Step 5: Upload to storage
        $this->updateProgress(75, 'uploading');
        $finalPath = $this->uploadToStorage($backupPath);

        // Step 6: Verify backup (menggunakan finalPath yang sudah diunggah)
        $this->updateProgress(85, 'verification');
        $verifiedPath = $this->verifyBackup($finalPath);

        // Step 7: Cleanup
        $this->updateProgress(90, 'cleanup');
        $this->cleanupTempFiles($backupPath);

        // Step 8: Complete and notify
        $this->updateProgress(95, 'notification');
        $fileSize = Storage::disk($this->jobRecord->disk)->size($verifiedPath);
        $duration = round(microtime(true) - $startTime, 2);

        $this->jobRecord->markAsCompleted($verifiedPath, $fileSize);

        // Send completion notification
        $this->sendCompletionNotification();

        $this->updateProgress(100, null);

        BackupLog::logInfo("Backup completed successfully", [
            'duration' => $duration,
            'file_size' => $fileSize,
            'file_path' => $finalPath,
            'execution_time' => $duration,
        ], $this->jobRecord);
    }

    protected function validateConfiguration(): void
    {
        $this->jobRecord->updateStep('initializing', 'processing');

        // Check if spatie/laravel-backup is properly configured
        if (!config('backup.backup.name')) {
            throw new \Exception('Backup name is not configured');
        }

        // Validate storage configuration
        $storageConfig = BackupSetting::getStorageConfig();
        if (empty($storageConfig['disks'])) {
            throw new \Exception('No storage disks configured');
        }

        $this->jobRecord->updateStep('initializing', 'completed');

        BackupLog::logDebug("Configuration validated", [
            'backup_name' => config('backup.backup.name'),
            'storage_disks' => array_keys($storageConfig['disks']),
        ], $this->jobRecord);
    }

    protected function validateStoragePermissions(): void
    {
        $this->jobRecord->updateStep('validation', 'processing');

        $disk = $this->jobRecord->disk;

        try {
            // Test write permissions
            $testFile = 'backup-test-' . time() . '.txt';
            Storage::disk($disk)->put($testFile, 'test');

            if (!Storage::disk($disk)->exists($testFile)) {
                throw new \Exception("Cannot write to storage disk: {$disk}");
            }

            // Test delete permissions  
            Storage::disk($disk)->delete($testFile);

            $this->jobRecord->updateStep('validation', 'completed');

            BackupLog::logDebug("Storage permissions validated", [
                'disk' => $disk,
            ], $this->jobRecord);
        } catch (\Exception $e) {
            $this->jobRecord->updateStep('validation', 'failed', [
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Storage validation failed: " . $e->getMessage());
        }
    }

    protected function createDatabaseBackup(): void
    {
        $this->jobRecord->updateStep('database_backup', 'processing');

        try {
            // Use spatie backup service for database backup
            $result = $this->backupService->createDatabaseBackup($this->jobRecord);

            $this->jobRecord->updateStep('database_backup', 'completed', [
                'size' => $result['size'] ?? null,
                'tables_count' => $result['tables_count'] ?? null,
            ]);

            BackupLog::logInfo("Database backup created", [
                'size' => $result['size'] ?? 'unknown',
                'tables_count' => $result['tables_count'] ?? 'unknown',
            ], $this->jobRecord);
        } catch (\Exception $e) {
            $this->jobRecord->updateStep('database_backup', 'failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    protected function createFilesBackup(): void
    {
        $this->jobRecord->updateStep('files_backup', 'processing');

        try {
            // Use spatie backup service for files backup with progress callback
            $result = $this->backupService->createFilesBackup($this->jobRecord, function ($progress) {
                $percentage = 40 + ($progress * 0.2); // 40-60% range
                $this->updateProgress($percentage, 'files_backup');
            });

            $this->jobRecord->updateStep('files_backup', 'completed', [
                'files_count' => $result['files_count'] ?? null,
                'size' => $result['size'] ?? null,
            ]);

            BackupLog::logInfo("Files backup created", [
                'files_count' => $result['files_count'] ?? 'unknown',
                'size' => $result['size'] ?? 'unknown',
            ], $this->jobRecord);
        } catch (\Exception $e) {
            $this->jobRecord->updateStep('files_backup', 'failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    protected function createFinalBackup(): string
    {
        $this->jobRecord->updateStep('compressing', 'processing');

        try {
            $filename = $this->generateFilename();

            // Run the actual spatie backup command
            $result = $this->backupService->runSpatieBackup([
                '--only-db' => $this->option === Option::ONLY_DB,
                '--only-files' => $this->option === Option::ONLY_FILES,
                '--filename' => $filename,
                '--timeout' => $this->timeout,
            ], $this->jobRecord);

            // Use the actual path from the command output if available
            $actualPath = $result['actual_path'] ?? $filename;

            $this->jobRecord->update(['filename' => basename($actualPath)]);

            BackupLog::logDebug("Backup file created", [
                'generated_filename' => $filename,
                'actual_path' => $actualPath,
                'is_absolute_path' => str_starts_with($actualPath, '/'),
            ], $this->jobRecord);

            $this->jobRecord->updateStep('compressing', 'completed', [
                'filename' => basename($actualPath),
                'actual_path' => $actualPath,
                'command_output' => substr($result['output'] ?? '', 0, 500) ?? null,
            ]);

            return $actualPath;
        } catch (\Exception $e) {
            $this->jobRecord->updateStep('compressing', 'failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    protected function uploadToStorage(string $backupPath): string
    {
        $this->jobRecord->updateStep('uploading', 'processing');

        try {
            // For local storage, file is already in place
            // For cloud storage, we might need to move/copy the file
            $disk = $this->jobRecord->disk;

            if ($disk !== 'local') {
                // Move from local temp to target storage
                $result = $this->backupService->moveToStorage($backupPath, $disk, $this->jobRecord);
                $finalPath = $result['path'];
            } else {
                $finalPath = $backupPath;
            }

            $this->jobRecord->updateStep('uploading', 'completed', [
                'final_path' => $finalPath,
                'disk' => $disk,
            ]);

            return $finalPath;
        } catch (\Exception $e) {
            $this->jobRecord->updateStep('uploading', 'failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    protected function verifyBackup(string $backupPath): string
    {
        $this->jobRecord->updateStep('verification', 'processing');

        try {
            $disk = $this->jobRecord->disk;
            $filename = basename($backupPath);
            $backupName = config('backup.backup.name', 'Laravel');

            BackupLog::logDebug("Starting backup file verification", [
                'initial_path' => $backupPath,
                'disk' => $disk,
                'backup_name' => $backupName,
                'filename' => $filename,
            ], $this->jobRecord);

            // For local or backup disk, check actual storage structure used by spatie backup
            if (in_array($disk, ['local', 'backup'])) {
                // Construct possible paths to check
                $pathsToCheck = [];

                // If using 'backup' disk, paths are relative to storage/app/backup
                if ($disk === 'backup') {
                    $pathsToCheck[] = $backupName . '/' . $filename;  // SI-IMUT/filename.zip
                    $pathsToCheck[] = $filename;  // filename.zip
                    $pathsToCheck[] = 'backups/' . $filename;
                } else {
                    // Original logic for 'local' disk
                    $actualStoragePath = storage_path('app/backup/' . $backupName . '/' . $filename);

                    BackupLog::logDebug("Checking actual filesystem path for local disk", [
                        'actual_path' => $actualStoragePath,
                        'exists' => file_exists($actualStoragePath)
                    ], $this->jobRecord);

                    if (file_exists($actualStoragePath)) {
                        $fileSize = filesize($actualStoragePath);
                        // Use relative path from backup directory for database storage
                        $foundPath = 'backup/' . $backupName . '/' . $filename;

                        BackupLog::logDebug("File found in actual storage", [
                            'actual_path' => $actualStoragePath,
                            'relative_path' => $foundPath,
                            'size' => $fileSize,
                        ], $this->jobRecord);

                        // Update the job record with the correct path
                        $this->jobRecord->update(['path' => $foundPath]);

                        $this->jobRecord->updateStep('verification', 'completed', [
                            'file_size' => $fileSize,
                            'verified' => true,
                        ]);

                        BackupLog::logInfo("Backup verification successful", [
                            'file_path' => $foundPath,
                            'file_size' => $fileSize,
                            'actual_storage_path' => $actualStoragePath,
                        ], $this->jobRecord);

                        return $foundPath;
                    }

                    // Fallback paths for local disk
                    $pathsToCheck[] = $backupPath;
                    $pathsToCheck[] = 'backup/' . $backupName . '/' . $filename;
                    $pathsToCheck[] = 'backups/' . $filename;
                    $pathsToCheck[] = $backupName . '/' . $filename;
                    $pathsToCheck[] = $filename;
                }

                // Check each possible location using Storage facade
                $foundPath = null;
                $fileSize = 0;

                foreach ($pathsToCheck as $checkPath) {
                    BackupLog::logDebug("Checking storage path", [
                        'path' => $checkPath,
                        'disk' => $disk,
                        'exists' => Storage::disk($disk)->exists($checkPath),
                    ], $this->jobRecord);

                    if (Storage::disk($disk)->exists($checkPath)) {
                        $foundPath = $checkPath;
                        $fileSize = Storage::disk($disk)->size($checkPath);

                        BackupLog::logDebug("Path found via Storage", [
                            'path' => $foundPath,
                            'size' => $fileSize,
                            'disk' => $disk,
                        ], $this->jobRecord);

                        break;
                    }
                }
            } else {
                // Fallback to original verification logic for cloud disks
                $pathsToCheck = [$backupPath];
                $pathsToCheck[] = 'backup/' . $backupName . '/' . $filename;
                $pathsToCheck[] = 'backups/' . $filename;
                $pathsToCheck[] = $backupName . '/' . $filename;
                $pathsToCheck[] = $filename;

                // Remove duplicates
                $pathsToCheck = array_unique($pathsToCheck);

                $foundPath = null;
                $fileSize = 0;

                // Check each possible location
                foreach ($pathsToCheck as $checkPath) {
                    BackupLog::logDebug("Checking cloud storage path", [
                        'path' => $checkPath,
                        'disk' => $disk,
                        'exists' => Storage::disk($disk)->exists($checkPath),
                    ], $this->jobRecord);

                    if (Storage::disk($disk)->exists($checkPath)) {
                        $foundPath = $checkPath;
                        $fileSize = Storage::disk($disk)->size($checkPath);

                        BackupLog::logDebug("Path found in cloud storage", [
                            'path' => $foundPath,
                            'size' => $fileSize,
                        ], $this->jobRecord);

                        break;
                    }
                }
            }

            if (!$foundPath) {
                // List directory contents for debugging
                $diskContents = [];
                try {
                    $diskContents = Storage::disk($disk)->allFiles();
                } catch (\Exception $e) {
                    // Ignore errors when listing files
                }

                throw new \Exception("Backup file not found in any expected location. Searched paths: " . implode(', ', $pathsToCheck) . ". Available files: " . implode(', ', array_slice($diskContents, 0, 10)));
            }

            if ($fileSize === 0) {
                throw new \Exception("Backup file is empty: {$foundPath}");
            }

            // Update the job record with the correct path
            $this->jobRecord->update(['path' => $foundPath]);

            // Additional verification for zip files (gunakan foundPath, bukan backupPath)
            if (str_ends_with($foundPath, '.zip')) {
                $this->verifyZipFile($disk, $foundPath);
            }

            $this->jobRecord->updateStep('verification', 'completed', [
                'file_size' => $fileSize,
                'verified' => true,
            ]);

            BackupLog::logInfo("Backup verification successful", [
                'file_path' => $foundPath,
                'file_size' => $fileSize,
            ], $this->jobRecord);

            // Return the verified path for use in the rest of the flow
            return $foundPath;
        } catch (\Exception $e) {
            $this->jobRecord->updateStep('verification', 'failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    protected function verifyZipFile(string $disk, string $backupPath): void
    {
        // For local disk, we can verify zip integrity directly
        if ($disk === 'local') {
            $fullPath = Storage::disk($disk)->path($backupPath);

            if (!file_exists($fullPath)) {
                throw new \Exception("Backup file not found at path: {$fullPath}");
            }

            $zip = new \ZipArchive();
            $result = $zip->open($fullPath, \ZipArchive::CHECKCONS);

            if ($result !== true) {
                $errorMessages = [
                    \ZipArchive::ER_EXISTS => 'File already exists',
                    \ZipArchive::ER_INCONS => 'Zip archive inconsistent',
                    \ZipArchive::ER_INVAL => 'Invalid argument',
                    \ZipArchive::ER_MEMORY => 'Malloc failure',
                    \ZipArchive::ER_NOENT => 'No such file',
                    \ZipArchive::ER_NOZIP => 'Not a zip archive',
                    \ZipArchive::ER_OPEN => 'Can\'t open file',
                    \ZipArchive::ER_READ => 'Read error',
                    \ZipArchive::ER_SEEK => 'Seek error',
                ];

                $errorMsg = $errorMessages[$result] ?? "Unknown error (code: {$result})";
                throw new \Exception("Zip file is corrupted or invalid: {$errorMsg}");
            }

            // Additional checks
            $fileCount = $zip->numFiles;
            if ($fileCount === 0) {
                throw new \Exception("Zip file is empty");
            }

            $zip->close();

            BackupLog::logDebug("Zip file verified successfully", [
                'path' => $backupPath,
                'file_count' => $fileCount,
                'disk' => $disk,
            ], $this->jobRecord);
        } else {
            // For non-local disks (S3, etc), skip detailed verification
            // as we can't directly access the file system
            BackupLog::logDebug("Skipping detailed zip verification for non-local disk", [
                'disk' => $disk,
                'path' => $backupPath,
            ], $this->jobRecord);
        }
    }

    protected function cleanupTempFiles(string $backupPath): void
    {
        $this->jobRecord->updateStep('cleanup', 'processing');

        try {
            // Cleanup is handled by spatie/laravel-backup
            // Additional cleanup logic can be added here

            $this->jobRecord->updateStep('cleanup', 'completed');

            BackupLog::logDebug("Temporary files cleaned up", [], $this->jobRecord);
        } catch (\Exception $e) {
            // Don't fail the job if cleanup fails
            $this->jobRecord->updateStep('cleanup', 'failed', [
                'error' => $e->getMessage(),
                'non_critical' => true,
            ]);

            BackupLog::logWarning("Cleanup failed (non-critical)", [
                'error' => $e->getMessage()
            ], $this->jobRecord);
        }
    }

    protected function sendCompletionNotification(): void
    {
        $this->jobRecord->updateStep('notification', 'processing');

        try {
            if ($this->userId) {
                $user = $this->getUserModel();
                if ($user) {
                    $user->notify(new BackupCompletedNotification($this->jobRecord));
                }
            }

            $this->jobRecord->updateStep('notification', 'completed');
        } catch (\Exception $e) {
            $this->jobRecord->updateStep('notification', 'failed', [
                'error' => $e->getMessage(),
                'non_critical' => true,
            ]);
        }
    }

    protected function handleFailure(\Exception $exception): void
    {
        $errorMessage = $exception->getMessage();
        $errorDetails = [
            'exception' => get_class($exception),
            'message' => $errorMessage,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        $this->jobRecord->markAsFailed($errorMessage, $errorDetails);

        BackupLog::logError("Backup job failed", [
            'error' => $errorMessage,
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ], $this->jobRecord);

        // Send failure notification
        if ($this->userId) {
            $user = $this->getUserModel();
            if ($user) {
                $user->notify(new BackupFailedNotification($this->jobRecord, $exception));
            }
        }
    }

    protected function updateProgress(int $percentage, ?string $step = null): void
    {
        $this->jobRecord->updateProgress($percentage, $step ? $this->steps[$step] : null);

        // Force refresh the model to ensure changes are persisted and visible to UI
        $this->jobRecord->refresh();

        // Send progress notification for significant milestones
        if ($percentage % 25 === 0 && $this->userId) {
            $user = $this->getUserModel();
            if ($user) {
                $user->notify(new BackupProgressNotification($this->jobRecord));
            }
        }
    }

    protected function generateJobName(): string
    {
        $type = match ($this->option) {
            Option::ONLY_DB => 'Database',
            Option::ONLY_FILES => 'Files',
            Option::ALL => 'Full',
        };

        return "{$type} Backup - " . now()->format('Y-m-d H:i:s');
    }

    protected function mapOptionToType(Option $option): string
    {
        return match ($option) {
            Option::ONLY_DB => BackupJob::TYPE_DATABASE_ONLY,
            Option::ONLY_FILES => BackupJob::TYPE_FILES_ONLY,
            Option::ALL => BackupJob::TYPE_FULL,
        };
    }

    protected function generateFilename(): string
    {
        if ($this->customFilename) {
            return $this->customFilename;
        }

        $prefix = match ($this->option) {
            Option::ALL => 'full-backup',
            Option::ONLY_DB => 'database-backup',
            Option::ONLY_FILES => 'files-backup',
        };

        return $prefix . '-' . now()->format('Y-m-d-H-i-s') . '.zip';
    }

    protected function getUserModel()
    {
        if (!$this->userType || !$this->userId) {
            return null;
        }

        try {
            return $this->userType::find($this->userId);
        } catch (\Exception $e) {
            return null;
        }
    }

    // Queue failure handling
    public function failed(\Throwable $exception): void
    {
        if (isset($this->jobRecord)) {
            $this->handleFailure($exception);
        }
    }

    // Retry handling
    public function retryUntil(): \DateTime
    {
        return now()->addHours(24); // Allow retries for 24 hours
    }
}
