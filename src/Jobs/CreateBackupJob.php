<?php

namespace Juniyasyos\FilamentLaravelBackup\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;
use Juniyasyos\FilamentLaravelBackup\Enums\Option;
use Juniyasyos\FilamentLaravelBackup\Notifications\BackupSuccessNotification;
use Spatie\Backup\Commands\BackupCommand;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected readonly Option $option = Option::ALL,
        protected readonly ?int $timeout = null,
        protected readonly ?User $user = null,
    ) {}

    public function handle(): void
    {
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

        // Kirim notifikasi ke user jika ada
        if ($this->user) {
            $this->user->notify(new BackupSuccessNotification($this->option));
        }
    }
}