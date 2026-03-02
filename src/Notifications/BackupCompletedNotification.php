<?php

namespace Juniyasyos\FilamentLaravelBackup\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Juniyasyos\FilamentLaravelBackup\Models\BackupJob;

class BackupCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected BackupJob $backupJob
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'backup_completed',
            'backup_job_id' => $this->backupJob->id,
            'backup_job_uuid' => $this->backupJob->uuid,
            'job_name' => $this->backupJob->name,
            'job_type' => $this->backupJob->type,
            'file_size' => $this->backupJob->file_size,
            'formatted_file_size' => $this->backupJob->formatted_file_size,
            'duration' => $this->backupJob->duration,
            'formatted_duration' => $this->backupJob->formatted_duration,
            'disk' => $this->backupJob->disk,
            'path' => $this->backupJob->path,
            'completed_at' => $this->backupJob->completed_at?->toISOString(),
            'title' => $this->getTitle(),
            'message' => $this->getMessage(),
            'icon' => 'heroicon-o-check-circle',
            'color' => 'success',
            'actions' => $this->getActions(),
        ];
    }



    protected function getTitle(): string
    {
        return "Backup Completed Successfully";
    }

    protected function getMessage(): string
    {
        $type = ucfirst(str_replace('_', ' ', $this->backupJob->type));
        return "Your {$type} backup '{$this->backupJob->name}' has been completed successfully. File size: {$this->backupJob->formatted_file_size}, Duration: {$this->backupJob->formatted_duration}.";
    }

    protected function getActions(): array
    {
        $actions = [
            [
                'label' => 'View Details',
                'url' => url('/admin/backups'),
                'style' => 'primary',
            ]
        ];

        // Add download action if file is accessible
        if ($this->backupJob->disk && $this->backupJob->path) {
            try {
                // Check if route exists before using it
                if (app('router')->has('backup.download')) {
                    $downloadUrl = route('backup.download', $this->backupJob->id);
                } else {
                    $downloadUrl = url('/admin/backups');
                }

                $actions[] = [
                    'label' => 'Download Backup',
                    'url' => $downloadUrl,
                    'style' => 'secondary',
                ];
            } catch (\Exception $e) {
                // Fallback if route generation fails
                $actions[] = [
                    'label' => 'View Backup Files',
                    'url' => url('/admin/backups'),
                    'style' => 'secondary',
                ];
            }
        }

        return $actions;
    }
}
