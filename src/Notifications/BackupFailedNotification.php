<?php

namespace Juniyasyos\FilamentLaravelBackup\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Juniyasyos\FilamentLaravelBackup\Models\BackupJob;

class BackupFailedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected BackupJob $backupJob,
        protected ?\Throwable $exception = null
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'backup_failed',
            'backup_job_id' => $this->backupJob->id,
            'backup_job_uuid' => $this->backupJob->uuid,
            'job_name' => $this->backupJob->name,
            'job_type' => $this->backupJob->type,
            'error_message' => $this->backupJob->error_message,
            'retry_count' => $this->backupJob->retry_count,
            'max_retries' => $this->backupJob->max_retries,
            'can_retry' => $this->backupJob->canRetry(),
            'next_retry_at' => $this->backupJob->next_retry_at?->toISOString(),
            'failed_at' => $this->backupJob->completed_at?->toISOString(),
            'duration' => $this->backupJob->duration,
            'formatted_duration' => $this->backupJob->formatted_duration,
            'title' => $this->getTitle(),
            'message' => $this->getMessage(),
            'icon' => 'heroicon-o-x-circle',
            'color' => 'danger',
            'actions' => $this->getActions(),
        ];
    }



    protected function getTitle(): string
    {
        return "Backup Failed";
    }

    protected function getMessage(): string
    {
        $retryInfo = $this->backupJob->canRetry() ?
            " Retry {$this->backupJob->retry_count}/{$this->backupJob->max_retries} will be attempted." :
            " All retry attempts have been exhausted.";

        return "Your backup '{$this->backupJob->name}' has failed: {$this->backupJob->error_message}.{$retryInfo}";
    }

    protected function getActions(): array
    {
        $actions = [
            [
                'label' => 'View Logs',
                'url' => url('/admin/backups'),
                'style' => 'primary',
            ]
        ];

        if ($this->backupJob->canRetry()) {
            try {
                // Check if route exists before using it
                if (app('router')->has('backup.retry')) {
                    $retryUrl = route('backup.retry', $this->backupJob->id);
                } else {
                    $retryUrl = url('/admin/backups');
                }

                $actions[] = [
                    'label' => 'Retry Now',
                    'url' => $retryUrl,
                    'style' => 'secondary',
                ];
            } catch (\Exception $e) {
                // Fallback if route generation fails
                $actions[] = [
                    'label' => 'Retry from Backups Page',
                    'url' => url('/admin/backups'),
                    'style' => 'secondary',
                ];
            }
        } else {
            $actions[] = [
                'label' => 'Create New Backup',
                'url' => url('/admin/backups'),
                'style' => 'secondary',
            ];
        }

        return $actions;
    }
}
