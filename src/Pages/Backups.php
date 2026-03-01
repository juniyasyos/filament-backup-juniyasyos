<?php

namespace Juniyasyos\FilamentLaravelBackup\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Juniyasyos\FilamentLaravelBackup\Enums\Option;
use Juniyasyos\FilamentLaravelBackup\Jobs\ImprovedBackupJob;
use Juniyasyos\FilamentLaravelBackup\Models\BackupJob;
use Juniyasyos\FilamentLaravelBackup\Models\BackupSetting;
use Juniyasyos\FilamentLaravelBackup\Pages\BackupSettings;
use Juniyasyos\FilamentSettingsHub\Traits\UseShield;
use Juniyasyos\FilamentLaravelBackup\FilamentLaravelBackupPlugin;

class Backups extends Page implements HasTable
{
    use UseShield;
    use InteractsWithTable;

    public bool $cleanupModalOpen = false;
    public bool $resetAllModalOpen = false;
    public bool $resetAllDeleteFiles = false;
    public array $cleanupOptions = [
        'older_than_days' => 30,
        'keep_minimum' => 5,
        'cleanup_failed' => true,
        'cleanup_cancelled' => true,
        'cleanup_files' => false,
    ];

    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-down';
    protected static string $view = 'filament-spatie-backup::pages.backups';
    protected static ?string $slug = 'backups';

    public function getHeading(): string|Htmlable
    {
        return __('filament-spatie-backup::backup.pages.backups.heading');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-spatie-backup::backup.pages.backups.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-spatie-backup::backup.pages.backups.navigation.label');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(BackupJob::query()->latest())
            ->columns([
                TextColumn::make('name')
                    ->label('Job Name')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn(string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->colors([
                        'primary' => BackupJob::TYPE_FULL,
                        'info' => BackupJob::TYPE_DATABASE_ONLY,
                        'warning' => BackupJob::TYPE_FILES_ONLY,
                    ]),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->colors([
                        'warning' => BackupJob::STATUS_PENDING,
                        'info' => BackupJob::STATUS_QUEUED,
                        'primary' => BackupJob::STATUS_PROCESSING,
                        'success' => BackupJob::STATUS_COMPLETED,
                        'danger' => [BackupJob::STATUS_FAILED, BackupJob::STATUS_TIMEOUT],
                        'gray' => BackupJob::STATUS_CANCELLED,
                    ]),

                TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->formatStateUsing(function (?int $state, $record): string {
                        $percentage = $state ?? 0;
                        $color = match (true) {
                            $percentage >= 100 => 'text-green-600 dark:text-green-400',
                            $percentage >= 75 => 'text-blue-600 dark:text-blue-400',
                            $percentage >= 50 => 'text-yellow-600 dark:text-yellow-400',
                            default => 'text-gray-600 dark:text-gray-400'
                        };

                        $progressBar = '';
                        if ($record->isActive() && $percentage < 100) {
                            $progressBar = '<div class="w-12 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-1"><div class="bg-blue-500 h-2 rounded-full" style="width: ' . $percentage . '%"></div></div>';
                        }

                        return new \Illuminate\Support\HtmlString($progressBar . '<span class="' . $color . ' font-medium">' . $percentage . '%</span>');
                    })
                    ->html()
                    ->alignCenter(),

                TextColumn::make('current_step')
                    ->label('Current Step')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->current_step),

                TextColumn::make('formatted_file_size')
                    ->label('File Size')
                    ->alignRight(),

                TextColumn::make('formatted_duration')
                    ->label('Duration')
                    ->alignRight(),

                TextColumn::make('disk')
                    ->label('Storage')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        BackupJob::STATUS_PENDING => 'Pending',
                        BackupJob::STATUS_QUEUED => 'Queued',
                        BackupJob::STATUS_PROCESSING => 'Processing',
                        BackupJob::STATUS_COMPLETED => 'Completed',
                        BackupJob::STATUS_FAILED => 'Failed',
                        BackupJob::STATUS_CANCELLED => 'Cancelled',
                        BackupJob::STATUS_TIMEOUT => 'Timeout',
                    ]),

                SelectFilter::make('type')
                    ->options([
                        BackupJob::TYPE_FULL => 'Full Backup',
                        BackupJob::TYPE_DATABASE_ONLY => 'Database Only',
                        BackupJob::TYPE_FILES_ONLY => 'Files Only',
                    ]),
            ])
            ->actions([
                TableAction::make('view')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn(BackupJob $record) => view('filament-spatie-backup::components.backup-job-details', ['job' => $record->refresh()]))
                    ->modalHeading(fn(BackupJob $record): string => "Backup Job: {$record->name}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->extraModalFooterActions([
                        \Filament\Actions\Action::make('refresh')
                            ->label('Refresh')
                            ->icon('heroicon-o-arrow-path')
                            ->color('gray')
                            ->action(fn() => null) // Will be handled by modal refresh
                    ]),

                TableAction::make('retry')
                    ->label('Retry')
                    ->icon('heroicon-o-arrow-path')
                    ->action(fn(BackupJob $record) => $this->retryJob($record))
                    ->visible(fn(BackupJob $record): bool => $record->canRetry())
                    ->requiresConfirmation(),

                TableAction::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-mark')
                    ->action(fn(BackupJob $record) => $this->cancelJob($record))
                    ->visible(fn(BackupJob $record): bool => $record->isActive())
                    ->requiresConfirmation()
                    ->color('danger'),

                TableAction::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (BackupJob $record) {
                        try {
                            dd($record->toArray());
                            return $this->downloadBackup($record);
                        } catch (\Exception $e) {
                            Log::error('Failed to download backup', [
                                'job_id' => $record->id,
                                'error' => $e->getMessage()
                            ]);
                            Notification::make()
                                ->title('Download Failed')
                                ->body('Unable to download backup: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    // ->visible(
                    //     fn(BackupJob $record): bool =>
                    //     $record->isCompleted() &&
                    //         $record->disk === 'local' &&
                    //         $record->path
                    // )
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('2s') // More frequent polling for better real-time updates
            ->deferLoading()
            ->striped();
    }

    protected function getActions(): array
    {
        return [
            Action::make('Create Backup')
                ->label('Create Backup')
                ->icon('heroicon-o-plus')
                ->form([
                    ToggleButtons::make('option')
                        ->label('Backup Type')
                        ->inline()
                        ->options([
                            '' => 'Full Backup (Database + Files)',
                            'only-db' => 'Database Only',
                            'only-files' => 'Files Only',
                        ])
                        ->default('')
                        ->required()
                        ->columnSpanFull(),

                    TextInput::make('custom_filename')
                        ->label('Custom Filename (Optional)')
                        ->helperText('Leave empty to auto-generate filename')
                        ->placeholder('custom-backup-name.zip')
                        ->columnSpanFull(),

                    Toggle::make('notifications')
                        ->label('Send Notifications')
                        ->helperText('Receive email notifications about backup progress')
                        ->default(BackupSetting::get('backup.general.notifications_enabled', true))
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    $this->createBackup(
                        $data['option'],
                        $data['custom_filename'] ?? null,
                        $data['notifications'] ?? false
                    );
                })
                ->modalWidth(MaxWidth::TwoExtraLarge)
                ->modalHeading('Create New Backup')
                ->modalSubmitActionLabel('Start Backup')
                ->requiresConfirmation()
                ->modalDescription('This will create a new backup based on your selected options. The process will run in the background and you will be notified when it completes.'),

            Action::make('Settings')
                ->label('Backup Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->url(BackupSettings::getUrl())
                ->color('gray'),
        ];
    }

    public function createBackup(string $option = '', ?string $customFilename = null, bool $notifications = false): void
    {
        try {
            // Validate storage configuration
            $defaultDisk = BackupSetting::get('backup.storage.default_disk', 'local');

            // Create job record first
            $user = Auth::user();

            ImprovedBackupJob::dispatch(
                Option::from($option),
                $customFilename,
                $user?->id,
                $user ? get_class($user) : null,
                [
                    'notifications_enabled' => $notifications,
                    'initiated_via' => 'filament_ui',
                ]
            );

            Notification::make()
                ->title('Backup Started')
                ->body('Your backup has been queued and will start processing shortly. You can monitor progress in the table below.')
                ->success()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view_jobs')
                        ->label('View Jobs')
                        ->button()
                        ->close(),
                ])
                ->persistent()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Backup Failed to Start')
                ->body('Failed to queue backup: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function retryJob(BackupJob $job): void
    {
        try {
            if (!$job->canRetry()) {
                throw new \Exception('Job cannot be retried');
            }

            $job->incrementRetry();

            $user = Auth::user();

            ImprovedBackupJob::dispatch(
                Option::from($job->options['option'] ?? ''),
                $job->options['custom_filename'] ?? null,
                $user?->id,
                $user ? get_class($user) : null,
                array_merge($job->options, ['retry_of' => $job->id])
            );

            Notification::make()
                ->title('Job Retried')
                ->body("Backup job '{$job->name}' has been queued for retry.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Retry Failed')
                ->body('Failed to retry job: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cancelJob(BackupJob $job): void
    {
        try {
            if (!$job->isActive()) {
                throw new \Exception('Job is not active and cannot be cancelled');
            }

            $job->cancel('Cancelled by user via UI');

            Notification::make()
                ->title('Job Cancelled')
                ->body("Backup job '{$job->name}' has been cancelled.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Cancel Failed')
                ->body('Failed to cancel job: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function shouldDisplayStatusListRecords(): bool
    {
        /** @var FilamentLaravelBackupPlugin $plugin */
        $plugin = filament()->getPlugin('filament-spatie-backup');

        return $plugin->hasStatusListRecordsTable();
    }

    public function getActiveJobsCount(): int
    {
        return BackupJob::active()->count();
    }

    public function getCompletedJobsCount(): int
    {
        return BackupJob::completed()->count();
    }

    public function getFailedJobsCount(): int
    {
        return BackupJob::failed()->count();
    }

    public function downloadBackup(BackupJob $job): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (!$job->isCompleted() || !$job->path) {
            Notification::make()
                ->title('Unduhan Gagal')
                ->body('File cadangan tidak tersedia untuk diunduh.')
                ->danger()
                ->send();
            throw new \Exception('Backup file is not available for download.');
        }

        $disk = Storage::disk($job->disk);
        $filename = basename($job->path);

        // Cari file di berbagai kemungkinan lokasi
        $pathsToCheck = array_unique([
            $job->path,
            'backups/' . $filename,
            config('backup.backup.name', 'Laravel') . '/' . $filename,
            $filename,
        ]);

        $resolvedPath = null;
        foreach ($pathsToCheck as $candidate) {
            if ($disk->exists($candidate)) {
                $resolvedPath = $candidate;
                break;
            }
        }

        if (!$resolvedPath) {
            // Update path di database agar sinkron
            if ($job->path !== null) {
                $job->update(['path' => null]);
            }

            Notification::make()
                ->title('File Tidak Ditemukan')
                ->body('File cadangan sudah tidak ada di penyimpanan. Silakan buat cadangan baru.')
                ->danger()
                ->duration(8000)
                ->send();
            throw new \Exception('Backup file no longer exists on storage.');
        }

        // Sinkronkan path jika berbeda dengan yang tersimpan
        if ($resolvedPath !== $job->path) {
            $job->update(['path' => $resolvedPath]);
        }

        return $disk->download($resolvedPath, $filename);
    }

    // Real-time polling method for job updates
    public function pollJobs(): array
    {
        return [
            'active_count' => $this->getActiveJobsCount(),
            'completed_count' => $this->getCompletedJobsCount(),
            'failed_count' => $this->getFailedJobsCount(),
        ];
    }

    // Refresh method for livewire updates
    public function refresh(): void
    {
        // This method allows JavaScript to refresh the component
        // The table will auto-refresh due to the poll('5s') configuration
    }

    public function openCleanupModal(): void
    {
        $this->cleanupModalOpen = true;
    }

    public function closeCleanupModal(): void
    {
        $this->cleanupModalOpen = false;
    }

    public function openResetAllModal(): void
    {
        $this->resetAllDeleteFiles = false;
        $this->resetAllModalOpen = true;
    }

    public function closeResetAllModal(): void
    {
        $this->resetAllModalOpen = false;
    }

    public function resetAllJobs(): void
    {
        try {
            $deletedCount = 0;

            $jobs = BackupJob::query()->get();

            foreach ($jobs as $job) {
                if ($this->resetAllDeleteFiles && $job->path) {
                    try {
                        Storage::disk($job->disk)->delete($job->path);
                    } catch (\Exception $e) {
                        \Log::warning('Gagal menghapus file cadangan', [
                            'job_id' => $job->id,
                            'path' => $job->path,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $job->delete();
                $deletedCount++;
            }

            $this->closeResetAllModal();

            Notification::make()
                ->title('✅ Reset Berhasil')
                ->body("Semua {$deletedCount} riwayat tugas cadangan telah dihapus." . ($this->resetAllDeleteFiles ? ' Termasuk file dari penyimpanan.' : ''))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Reset Gagal')
                ->body('Gagal mereset data: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cleanupJobs(): void
    {
        try {
            $deletedCount = 0;
            $olderThan = now()->subDays($this->cleanupOptions['older_than_days']);

            // Get jobs to keep (minimum count)
            $keepJobs = BackupJob::query()
                ->where('status', BackupJob::STATUS_COMPLETED)
                ->orderBy('created_at', 'desc')
                ->limit($this->cleanupOptions['keep_minimum'])
                ->pluck('id');

            $query = BackupJob::query()
                ->where('created_at', '<', $olderThan)
                ->whereNotIn('id', $keepJobs);

            // Add status filters
            $statusesToCleanup = [];
            if ($this->cleanupOptions['cleanup_failed']) {
                $statusesToCleanup[] = BackupJob::STATUS_FAILED;
            }
            if ($this->cleanupOptions['cleanup_cancelled']) {
                $statusesToCleanup[] = BackupJob::STATUS_CANCELLED;
            }

            if (!empty($statusesToCleanup)) {
                $query->whereIn('status', $statusesToCleanup);
            }

            $jobsToDelete = $query->get();

            foreach ($jobsToDelete as $job) {
                // Delete backup file if enabled
                if ($this->cleanupOptions['cleanup_files'] && $job->path) {
                    try {
                        Storage::disk($job->disk)->delete($job->path);
                    } catch (\Exception $e) {
                        // Log but don't fail cleanup
                        \Log::warning('Failed to delete backup file', [
                            'job_id' => $job->id,
                            'path' => $job->path,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $job->delete();
                $deletedCount++;
            }

            $this->closeCleanupModal();

            Notification::make()
                ->title('✅ Pembersihan Berhasil')
                ->body("Telah berhasil membersihkan {$deletedCount} tugas cadangan.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Pembersihan Gagal')
                ->body('Gagal membersihkan tugas: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
