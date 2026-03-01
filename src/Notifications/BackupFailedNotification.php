<?php

namespace Juniyasyos\FilamentLaravelBackup\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
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
        return ['database', 'mail'];
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

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->error()
            ->subject($this->getTitle())
            ->greeting("Hello {$notifiable->name},")
            ->line($this->getMessage())
            ->line("**Error Details:**")
            ->line("- Job: {$this->backupJob->name}")
            ->line("- Type: " . ucfirst(str_replace('_', ' ', $this->backupJob->type)))
            ->line("- Error: {$this->backupJob->error_message}")
            ->line("- Failed At: " . $this->backupJob->completed_at?->format('Y-m-d H:i:s'));

        if ($this->backupJob->canRetry()) {
            $next = $this->backupJob->next_retry_at?->format('Y-m-d H:i:s') ?? 'Shortly';
            $mail->line("- Next Retry: {$next}")
                ->line("- Retry Attempt: {$this->backupJob->retry_count}/{$this->backupJob->max_retries}");
        }

        if ($this->exception) {
            $mail->line("**Technical Details:**")
                ->line("- Exception: " . get_class($this->exception))
                ->line("- File: {$this->exception->getFile()}:{$this->exception->getLine()}");
        }

        return $mail->action('View Backup Logs', url('/admin/backups'))
            ->when(!$this->backupJob->canRetry(), function ($message) {
                return $message->line('Please check your backup configuration and try again.');
            })
            ->line('If this problem persists, please contact support.');
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
