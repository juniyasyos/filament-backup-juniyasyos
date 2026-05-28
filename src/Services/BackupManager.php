<?php

namespace Juniyasyos\FilamentLaravelBackup\Services;

use Juniyasyos\FilamentLaravelBackup\Models\BackupJob;
use Juniyasyos\FilamentLaravelBackup\Models\BackupSetting;
use Juniyasyos\FilamentLaravelBackup\Models\BackupLog;
use Juniyasyos\FilamentLaravelBackup\Enums\Option;

class BackupManager
{
    /**
     * Create and initialize a BackupJob record
     */
    public function createJobRecord(Option $option, ?string $customFilename = null, ?int $userId = null, ?string $userType = null, array $additionalOptions = []): BackupJob
    {
        $job = BackupJob::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => $this->generateJobName($option),
            'type' => $this->mapOptionToType($option),
            'status' => BackupJob::STATUS_QUEUED,
            'options' => array_merge([
                'option' => $option->value,
                'custom_filename' => $customFilename,
            ], $additionalOptions),
            'disk' => BackupSetting::get('backup.storage.default_disk', 'local'),
            'user_id' => $userId,
            'user_type' => $userType,
            'ip_address' => request()?->ip() ?? null,
            'user_agent' => request()?->userAgent() ?? null,
            'queue_name' => null,
            'connection' => null,
            'job_payload' => [
                'option' => $option->value,
                'filename' => $customFilename,
                'additional_options' => $additionalOptions,
            ],
            'max_retries' => 3,
        ]);

        // Initialize default steps
        $steps = [
            'initializing', 'validation', 'database_backup', 'files_backup', 'compressing', 'uploading', 'verification', 'cleanup', 'notification'
        ];

        foreach ($steps as $s) {
            $job->addStep($s);
        }

        BackupLog::logInfo('Backup job created', [
            'job_id' => $job->id,
            'type' => $job->type,
            'option' => $option->value,
        ], $job);

        return $job;
    }

    public function markAsFailed(BackupJob $job, string $error, array $details = []): BackupJob
    {
        return $job->markAsFailed($error, $details);
    }

    public function markAsCompleted(BackupJob $job, ?string $path = null, ?int $size = null): BackupJob
    {
        return $job->markAsCompleted($path, $size);
    }

    public function markAsProcessing(BackupJob $job, ?string $step = null): BackupJob
    {
        return $job->markAsProcessing($step);
    }

    public function updateProgress(BackupJob $job, int $percentage, ?string $step = null, array $context = []): BackupJob
    {
        $job->updateProgress($percentage, $step, $context);
        // Force refresh to ensure consumers see latest state
        try {
            $job->refresh();
        } catch (\Exception $e) {
            // ignore refresh failures in certain runtime contexts
        }

        return $job;
    }

    public function updateStep(BackupJob $job, string $stepName, string $status, array $data = []): BackupJob
    {
        return $job->updateStep($stepName, $status, $data);
    }

    protected function generateJobName(Option $option): string
    {
        $type = match ($option) {
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
}
