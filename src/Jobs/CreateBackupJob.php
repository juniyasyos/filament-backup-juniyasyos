<?php

namespace Juniyasyos\FilamentLaravelBackup\Jobs;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;
use Juniyasyos\FilamentLaravelBackup\Enums\BackupStatus;
use Juniyasyos\FilamentLaravelBackup\Enums\Option;
use Juniyasyos\FilamentLaravelBackup\FilamentLaravelBackup;
use Juniyasyos\FilamentLaravelBackup\Models\BackupRun;
use Juniyasyos\FilamentLaravelBackup\Notifications\BackupSuccessNotification;
use Spatie\Backup\Commands\BackupCommand;
use Spatie\Backup\BackupDestination\BackupDestination;

use Throwable;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected readonly Option $option = Option::ALL,
        protected readonly ?int $timeout = null,
        protected readonly ?Authenticatable $user = null,
    ) {}

    public function handle(): void
    {
        $run = new BackupRun([
            'status' => BackupStatus::QUEUED,
            'option' => $this->option,
            'disks' => FilamentLaravelBackup::getDisks(),
        ]);

        if ($this->user) {
            $run->initiator()->associate($this->user);
        }

        $run->save();
        $run->markRunning();

        try {
            Artisan::call(BackupCommand::class, [
                '--only-db' => $this->option === Option::ONLY_DB,
                '--only-files' => $this->option === Option::ONLY_FILES,
                '--filename' => match ($this->option) {
                    Option::ALL => null,
                    default => str_replace('_', '-', $this->option->value) .
                        '-' . date('Y-m-d-H-i-s') . '.zip'
                },
                '--timeout' => $this->timeout,
            ]);

            $latestBackup = $this->resolveLatestBackup();

            $run->markFinishedSuccess(
                $latestBackup['disk'] ?? null,
                $latestBackup['filename'] ?? null,
                $latestBackup['size_in_bytes'] ?? null,
                Artisan::output()
            );

            if ($this->user) {
                $this->user->notify(new BackupSuccessNotification($this->option));
            }
        } catch (Throwable $exception) {
            $run->markFailed($exception, Artisan::output());

            throw $exception;
        }
    }

    /**
     * Ambil backup terbaru setelah proses dijalankan.
     */
    protected function resolveLatestBackup(): ?array
    {
        $latest = null;
        $backupName = config('backup.backup.name');

        foreach (FilamentLaravelBackup::getDisks() as $disk) {
            $destination = BackupDestination::create($disk, $backupName);
            $backup = $destination->newestBackup();

            if (! $backup) {
                continue;
            }

            if (! $latest || $backup->date()->greaterThan($latest['date'])) {
                $latest = [
                    'disk' => $disk,
                    'filename' => $backup->path(),
                    'size_in_bytes' => $backup->sizeInBytes(),
                    'date' => $backup->date(),
                ];
            }
        }

        if (! $latest) {
            return null;
        }

        unset($latest['date']);

        return $latest;
    }
}
