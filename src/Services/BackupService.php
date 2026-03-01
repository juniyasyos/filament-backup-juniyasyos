<?php

namespace Juniyasyos\FilamentLaravelBackup\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Juniyasyos\FilamentLaravelBackup\Models\BackupJob;
use Juniyasyos\FilamentLaravelBackup\Models\BackupLog;
use Spatie\Backup\Commands\BackupCommand;
use Spatie\Backup\Tasks\Backup\BackupJob as SpatieBackupJob;
use Spatie\Backup\Tasks\Backup\DbDumperFactory;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;

class BackupService
{
    protected array $tempFiles = [];

    public function createDatabaseBackup(BackupJob $jobRecord): array
    {
        $startTime = microtime(true);

        try {
            // Get database configuration
            $defaultConnection = config('database.default');
            $dbConfig = config("database.connections.{$defaultConnection}");

            if (!$dbConfig) {
                throw new \Exception("Database connection '{$defaultConnection}' not found");
            }

            // Create database dumper
            $dumper = DbDumperFactory::createFromConnection($defaultConnection);

            // Generate temp file for database dump
            $tempDir = storage_path('app/temp');
            if (!File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            $dumpFile = $tempDir . '/database-' . time() . '.sql';
            $this->tempFiles[] = $dumpFile;

            BackupLog::logInfo("Starting database backup", [
                'connection' => $defaultConnection,
                'database' => $dbConfig['database'] ?? 'unknown',
                'dump_file' => basename($dumpFile),
            ], $jobRecord);

            // Create the dump
            $dumper->dumpToFile($dumpFile);

            $fileSize = File::size($dumpFile);
            $tablesCount = $this->getDatabaseTablesCount($defaultConnection);
            $duration = microtime(true) - $startTime;

            BackupLog::logInfo("Database backup completed", [
                'file_size' => $fileSize,
                'tables_count' => $tablesCount,
                'duration' => $duration,
                'execution_time' => $duration,
            ], $jobRecord);

            return [
                'file_path' => $dumpFile,
                'size' => $fileSize,
                'tables_count' => $tablesCount,
                'duration' => $duration,
            ];
        } catch (\Exception $e) {
            BackupLog::logError("Database backup failed", [
                'error' => $e->getMessage(),
                'connection' => $defaultConnection ?? 'unknown',
            ], $jobRecord);

            throw $e;
        }
    }

    public function createFilesBackup(BackupJob $jobRecord, ?callable $progressCallback = null): array
    {
        $startTime = microtime(true);
        $filesProcessed = 0;
        $totalSize = 0;

        try {
            // Get backup source configuration
            $backupSources = config('backup.backup.source.files');
            if (!$backupSources || !isset($backupSources['include'])) {
                throw new \Exception("No backup source files configured");
            }

            BackupLog::logInfo("Starting files backup", [
                'sources' => $backupSources['include'],
                'excludes' => $backupSources['exclude'] ?? [],
            ], $jobRecord);

            // Create temp directory for file collection
            $tempDir = storage_path('app/temp/files-' . time());
            File::makeDirectory($tempDir, 0755, true);
            $this->tempFiles[] = $tempDir;

            // Process each source directory
            foreach ($backupSources['include'] as $sourcePath) {
                $realPath = base_path($sourcePath);

                if (!File::exists($realPath)) {
                    BackupLog::logWarning("Source path not found", [
                        'path' => $sourcePath,
                        'real_path' => $realPath,
                    ], $jobRecord);
                    continue;
                }

                $result = $this->copyDirectoryWithProgress(
                    $realPath,
                    $tempDir . '/' . basename($sourcePath),
                    $backupSources['exclude'] ?? [],
                    $progressCallback
                );

                $filesProcessed += $result['files_count'];
                $totalSize += $result['size'];
            }

            $duration = microtime(true) - $startTime;

            BackupLog::logInfo("Files backup completed", [
                'files_count' => $filesProcessed,
                'total_size' => $totalSize,
                'duration' => $duration,
                'execution_time' => $duration,
                'files_processed' => $filesProcessed,
                'bytes_processed' => $totalSize,
            ], $jobRecord);

            return [
                'temp_dir' => $tempDir,
                'files_count' => $filesProcessed,
                'size' => $totalSize,
                'duration' => $duration,
            ];
        } catch (\Exception $e) {
            BackupLog::logError("Files backup failed", [
                'error' => $e->getMessage(),
                'files_processed' => $filesProcessed,
                'bytes_processed' => $totalSize,
            ], $jobRecord);

            throw $e;
        }
    }

    public function runSpatieBackup(array $options, BackupJob $jobRecord): array
    {
        $startTime = microtime(true);

        try {
            BackupLog::logInfo("Running spatie backup command", [
                'options' => $options,
            ], $jobRecord);

            // Create a buffered output to capture command output
            $output = new BufferedOutput();

            // Run the backup command using the correct spatie command
            $exitCode = Artisan::call('backup:run', $options, $output);

            $commandOutput = $output->fetch();
            $duration = microtime(true) - $startTime;

            if ($exitCode !== 0) {
                throw new \Exception("Backup command failed with exit code {$exitCode}: {$commandOutput}");
            }

            // Parse output to find actual backup file path
            $actualPath = $this->parseBackupPath($commandOutput, $options['--filename'] ?? null);

            BackupLog::logInfo("Spatie backup command completed", [
                'exit_code' => $exitCode,
                'duration' => $duration,
                'execution_time' => $duration,
                'output_length' => strlen($commandOutput),
                'actual_path' => $actualPath,
            ], $jobRecord);

            return [
                'exit_code' => $exitCode,
                'output' => $commandOutput,
                'duration' => $duration,
                'actual_path' => $actualPath,
            ];
        } catch (\Exception $e) {
            BackupLog::logError("Spatie backup command failed", [
                'error' => $e->getMessage(),
                'options' => $options,
                'execution_time' => microtime(true) - $startTime,
            ], $jobRecord);

            throw $e;
        }
    }

    /**
     * Parse backup command output to find the actual file path
     */
    private function parseBackupPath(string $output, ?string $expectedFilename): ?string
    {
        // Look for patterns in the output that indicate where the file was saved
        $patterns = [
            '/Successfully backed up .* to disk .* at path ([^\s]+)/',
            '/Backup stored at: ([^\s]+)/',
            '/Created backup: ([^\s]+)/',
            '/path\s*:\s*([^\s\n]+)/i',
            '/file\s*:\s*([^\s\n]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $output, $matches)) {
                $path = trim($matches[1]);
                if (!empty($path)) {
                    return $path;
                }
            }
        }

        // If no specific path found, construct a reasonable default path
        // Spatie typically stores files in backups/ directory
        if ($expectedFilename) {
            // Try existing directory structures
            $possiblePaths = [
                'backups/' . $expectedFilename,
                config('backup.backup.name', 'Laravel') . '/' . $expectedFilename,
                $expectedFilename,
            ];

            // Return the first relative path that makes sense
            return $possiblePaths[0];
        }

        return null;
    }

    public function moveToStorage(string $sourcePath, string $targetDisk, BackupJob $jobRecord): array
    {
        $startTime = microtime(true);

        try {
            $targetPath = 'backups/' . basename($sourcePath);

            BackupLog::logInfo("Moving backup to storage", [
                'source_path' => basename($sourcePath),
                'target_disk' => $targetDisk,
                'target_path' => $targetPath,
            ], $jobRecord);

            // Read source file and upload to target storage
            $sourceStream = Storage::disk('local')->readStream($sourcePath);
            if (!$sourceStream) {
                throw new \Exception("Cannot read source file: {$sourcePath}");
            }

            $success = Storage::disk($targetDisk)->writeStream($targetPath, $sourceStream);
            if (!$success) {
                throw new \Exception("Failed to write to target storage: {$targetDisk}");
            }

            // Verify the upload
            $targetSize = Storage::disk($targetDisk)->size($targetPath);
            $sourceSize = Storage::disk('local')->size($sourcePath);

            if ($targetSize !== $sourceSize) {
                throw new \Exception("File size mismatch after upload: source={$sourceSize}, target={$targetSize}");
            }

            // Clean up source file (if different from target)
            if ($targetDisk !== 'local') {
                Storage::disk('local')->delete($sourcePath);
            }

            $duration = microtime(true) - $startTime;

            BackupLog::logInfo("Backup moved to storage successfully", [
                'target_path' => $targetPath,
                'file_size' => $targetSize,
                'duration' => $duration,
                'execution_time' => $duration,
                'bytes_processed' => $targetSize,
            ], $jobRecord);

            return [
                'path' => $targetPath,
                'size' => $targetSize,
                'duration' => $duration,
            ];
        } catch (\Exception $e) {
            BackupLog::logError("Failed to move backup to storage", [
                'error' => $e->getMessage(),
                'source_path' => basename($sourcePath),
                'target_disk' => $targetDisk,
            ], $jobRecord);

            throw $e;
        }
    }

    public function validateStorageConfiguration(string $disk): array
    {
        try {
            $config = config("filesystems.disks.{$disk}");
            if (!$config) {
                throw new \Exception("Disk configuration not found: {$disk}");
            }

            $errors = [];
            $warnings = [];

            switch ($config['driver']) {
                case 'local':
                    $this->validateLocalStorage($config, $errors, $warnings);
                    break;

                case 's3':
                    $this->validateS3Storage($config, $errors, $warnings);
                    break;

                default:
                    $warnings[] = "Unknown storage driver: {$config['driver']}";
            }

            return [
                'valid' => empty($errors),
                'errors' => $errors,
                'warnings' => $warnings,
                'config' => $config,
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'errors' => [$e->getMessage()],
                'warnings' => [],
                'config' => null,
            ];
        }
    }

    protected function validateLocalStorage(array $config, array &$errors, array &$warnings): void
    {
        $root = $config['root'] ?? '';

        if (!File::exists($root)) {
            try {
                File::makeDirectory($root, 0755, true);
            } catch (\Exception $e) {
                $errors[] = "Cannot create storage directory: {$root}";
                return;
            }
        }

        if (!File::isWritable($root)) {
            $errors[] = "Storage directory is not writable: {$root}";
        }

        $freeBytes = disk_free_space($root);
        if ($freeBytes !== false && $freeBytes < 1073741824) { // Less than 1GB
            $warnings[] = "Low disk space: " . $this->formatBytes($freeBytes) . " available";
        }
    }

    protected function validateS3Storage(array $config, array &$errors, array &$warnings): void
    {
        $required = ['key', 'secret', 'region', 'bucket'];

        foreach ($required as $field) {
            if (empty($config[$field])) {
                $errors[] = "S3 configuration missing: {$field}";
            }
        }

        if (!empty($errors)) {
            return;
        }

        try {
            // Test S3 connection
            Storage::disk('s3')->put('backup-test.txt', 'test');
            Storage::disk('s3')->delete('backup-test.txt');
        } catch (\Exception $e) {
            $errors[] = "S3 connection test failed: " . $e->getMessage();
        }
    }

    protected function copyDirectoryWithProgress(string $source, string $destination, array $excludes, ?callable $callback): array
    {
        $filesCount = 0;
        $totalSize = 0;

        if (!File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = $iterator->getSubPathName();

            // Check if file should be excluded
            if ($this->shouldExclude($relativePath, $excludes)) {
                continue;
            }

            $targetPath = $destination . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                File::makeDirectory($targetPath, 0755, true);
            } else {
                File::copy($item->getRealPath(), $targetPath);
                $filesCount++;
                $totalSize += $item->getSize();

                // Call progress callback every 10 files
                if ($callback && $filesCount % 10 === 0) {
                    $callback($filesCount);
                }
            }
        }

        return [
            'files_count' => $filesCount,
            'size' => $totalSize,
        ];
    }

    protected function shouldExclude(string $path, array $excludes): bool
    {
        foreach ($excludes as $exclude) {
            if (fnmatch($exclude, $path)) {
                return true;
            }
        }
        return false;
    }

    protected function getDatabaseTablesCount(string $connection): int
    {
        try {
            return count(DB::connection($connection)->getDoctrineSchemaManager()->listTableNames());
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function __destruct()
    {
        // Cleanup temp files
        foreach ($this->tempFiles as $tempFile) {
            if (File::exists($tempFile)) {
                if (File::isDirectory($tempFile)) {
                    File::deleteDirectory($tempFile);
                } else {
                    File::delete($tempFile);
                }
            }
        }
    }
}
