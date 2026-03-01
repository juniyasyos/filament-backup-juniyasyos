<?php

namespace Juniyasyos\FilamentLaravelBackup\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Juniyasyos\FilamentLaravelBackup\Models\BackupJob;

class BackupProgressNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected BackupJob $backupJob
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'backup_progress',
            'backup_job_id' => $this->backupJob->id,
            'backup_job_uuid' => $this->backupJob->uuid,
            'job_name' => $this->backupJob->name,
            'progress_percentage' => $this->backupJob->progress_percentage,
            'current_step' => $this->backupJob->current_step,
            'status' => $this->backupJob->status,
            'started_at' => $this->backupJob->started_at?->toISOString(),
            'estimated_completion' => $this->getEstimatedCompletion(),
            'title' => $this->getTitle(),
            'message' => $this->getMessage(),
            'icon' => 'heroicon-o-cloud-arrow-down',
            'color' => 'info',
        ];
    }

    public function toBroadcast($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->getTitle())
            ->greeting("Hello {$notifiable->name},")
            ->line($this->getMessage())
            ->line("Progress: {$this->backupJob->progress_percentage}%")
            ->when($this->backupJob->current_step, function ($message) {
                return $message->line("Current Step: {$this->backupJob->current_step}");
            })
            ->line('You will be notified when the backup is completed.')
            ->action('View Backup Status', url('/admin/backups'));
    }

    protected function getTitle(): string
    {
        return "Backup Progress: {$this->backupJob->progress_percentage}%";
    }

    protected function getMessage(): string
    {
        $step = $this->backupJob->current_step ? " - {$this->backupJob->current_step}" : '';
        return "Your backup '{$this->backupJob->name}' is {$this->backupJob->progress_percentage}% complete{$step}.";
    }

    protected function getEstimatedCompletion(): ?string
    {
        if (!$this->backupJob->started_at || $this->backupJob->progress_percentage <= 0) {
            return null;
        }

        $elapsed = $this->backupJob->started_at->diffInSeconds(now());
        $estimatedTotal = ($elapsed / $this->backupJob->progress_percentage) * 100;
        $remaining = max(0, $estimatedTotal - $elapsed);

        if ($remaining < 60) {
            return "Less than 1 minute";
        } elseif ($remaining < 3600) {
            return round($remaining / 60) . " minutes";
        } else {
            return round($remaining / 3600, 1) . " hours";
        }
    }
}
