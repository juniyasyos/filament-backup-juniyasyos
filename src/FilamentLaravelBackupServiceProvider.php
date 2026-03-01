<?php

namespace Juniyasyos\FilamentLaravelBackup;

use Livewire\Livewire;
use Juniyasyos\FilamentLaravelBackup\Components\BackupDestinationListRecords;
use Juniyasyos\FilamentLaravelBackup\Components\BackupDestinationStatusListRecords;
use Juniyasyos\FilamentLaravelBackup\Models\BackupSetting;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentLaravelBackupServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-spatie-backup')
            ->hasTranslations()
            ->hasViews()
            ->hasRoutes('web')
            ->hasConfigFile('filament-backup')
            ->hasMigrations([
                'create_backup_settings_table',
                'create_backup_jobs_table',
                'create_backup_logs_table'
            ]);
    }

    public function packageBooted(): void
    {
        Livewire::component('backup-destination-list-records', BackupDestinationListRecords::class);
        Livewire::component('backup-destination-status-list-records', BackupDestinationStatusListRecords::class);

        // Publish migrations with specific tag
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../database/migrations/2024_03_01_000001_create_backup_settings_table.php' =>
                database_path('migrations/' . date('Y_m_d_His', time()) . '_create_backup_settings_table.php'),
                __DIR__ . '/../database/migrations/2024_03_01_000002_create_backup_jobs_table.php' =>
                database_path('migrations/' . date('Y_m_d_His', time() + 1) . '_create_backup_jobs_table.php'),
                __DIR__ . '/../database/migrations/2024_03_01_000003_create_backup_logs_table.php' =>
                database_path('migrations/' . date('Y_m_d_His', time() + 2) . '_create_backup_logs_table.php'),
            ], 'filament-backup-migrations');

            // Publish config file if needed
            $this->publishes([
                __DIR__ . '/../config/filament-backup.php' => config_path('filament-backup.php'),
            ], 'filament-backup-config');

            // Register console commands
            $this->commands([
                \Juniyasyos\FilamentLaravelBackup\Commands\CleanupBackupJobsCommand::class,
                \Juniyasyos\FilamentLaravelBackup\Commands\CleanupBackupLogsCommand::class,
                \Juniyasyos\FilamentLaravelBackup\Commands\FixBackupPathsCommand::class,
            ]);
        }
    }

    public function packageRegistered(): void
    {
        // Merge backup configuration with settings from database
        $this->app->booted(function () {
            if (
                app()->environment() !== 'testing' &&
                \Schema::hasTable('backup_settings')
            ) {
                try {
                    $storageConfig = BackupSetting::getStorageConfig();

                    // Merge with existing backup config
                    config()->set('filesystems.disks', array_merge(
                        config('filesystems.disks', []),
                        $storageConfig['disks']
                    ));

                    // Update backup configuration
                    config()->set('backup.backup.destination.disks', [
                        $storageConfig['default_disk']
                    ]);
                } catch (\Exception $e) {
                    // Silently fail during installation/migration
                    logger('Failed to load backup settings: ' . $e->getMessage());
                }
            }
        });
    }
}
