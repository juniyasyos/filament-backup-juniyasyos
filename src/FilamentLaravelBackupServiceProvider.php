<?php

namespace Juniyasyos\FilamentLaravelBackup;

use Livewire\Livewire;
use Illuminate\Console\Scheduling\Schedule;
use Juniyasyos\FilamentLaravelBackup\Components\BackupDestinationListRecords;
use Juniyasyos\FilamentLaravelBackup\Components\BackupDestinationStatusListRecords;
use Juniyasyos\FilamentLaravelBackup\Models\BackupConfiguration;
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
                'simplify_backup_settings_table',
                'create_backup_configuration_table',
                'expand_backup_configuration_table',
                'add_minio_columns_to_backup_configuration_table',
                'drop_minio_region_from_backup_configuration_table',
                'add_schedule_columns_to_backup_configuration_table',
                'add_interval_schedule_columns_to_backup_configuration_table',
                'drop_legacy_backup_settings_table',
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
                __DIR__ . '/../database/migrations/2026_05_29_000004_simplify_backup_settings_table.php' =>
                database_path('migrations/' . date('Y_m_d_His', time() + 1) . '_simplify_backup_settings_table.php'),
                __DIR__ . '/../database/migrations/2026_05_29_000006_create_backup_configuration_table.php' =>
                database_path('migrations/' . date('Y_m_d_His', time() + 2) . '_create_backup_configuration_table.php'),
                __DIR__ . '/../database/migrations/2026_05_29_000007_expand_backup_configuration_table.php' =>
                database_path('migrations/' . date('Y_m_d_His', time() + 3) . '_expand_backup_configuration_table.php'),
                __DIR__ . '/../database/migrations/2026_05_29_000009_add_minio_columns_to_backup_configuration_table.php' =>
                database_path('migrations/' . date('Y_m_d_His', time() + 4) . '_add_minio_columns_to_backup_configuration_table.php'),
                __DIR__ . '/../database/migrations/2026_05_29_000010_drop_minio_region_from_backup_configuration_table.php' =>
                database_path('migrations/' . date('Y_m_d_His', time() + 5) . '_drop_minio_region_from_backup_configuration_table.php'),
                __DIR__ . '/../database/migrations/2026_05_29_000011_add_schedule_columns_to_backup_configuration_table.php' =>
                database_path('migrations/' . date('Y_m_d_His', time() + 6) . '_add_schedule_columns_to_backup_configuration_table.php'),
                __DIR__ . '/../database/migrations/2026_05_29_000012_add_interval_schedule_columns_to_backup_configuration_table.php' =>
                database_path('migrations/' . date('Y_m_d_His', time() + 7) . '_add_interval_schedule_columns_to_backup_configuration_table.php'),
                __DIR__ . '/../database/migrations/2026_05_29_000008_drop_legacy_backup_settings_table.php' =>
                database_path('migrations/' . date('Y_m_d_His', time() + 8) . '_drop_legacy_backup_settings_table.php'),
                __DIR__ . '/../database/migrations/2024_03_01_000002_create_backup_jobs_table.php' =>
                database_path('migrations/' . date('Y_m_d_His', time() + 9) . '_create_backup_jobs_table.php'),
                __DIR__ . '/../database/migrations/2024_03_01_000003_create_backup_logs_table.php' =>
                database_path('migrations/' . date('Y_m_d_His', time() + 10) . '_create_backup_logs_table.php'),
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
                \Juniyasyos\FilamentLaravelBackup\Commands\RunScheduledBackupCommand::class,
            ]);

            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);

                $schedule->command(\Juniyasyos\FilamentLaravelBackup\Commands\RunScheduledBackupCommand::class)
                    ->everyMinute()
                    ->withoutOverlapping()
                    ->onOneServer()
                    ->description('Run configured automatic backup when it is due');
            });
        }
    }

    public function packageRegistered(): void
    {
        // Merge backup configuration with settings from database
        $this->app->booted(function () {
            if (
                app()->environment() !== 'testing' &&
                \Schema::hasTable('backup_configuration')
            ) {
                try {
                    $configuration = BackupConfiguration::ensureDefaults();

                    $disks = [
                        'local' => [
                            'driver' => 'local',
                            'root' => $configuration->local_path ?: storage_path('app/backup'),
                        ],
                    ];

                    if (! empty($configuration->s3_bucket)) {
                        $disks['s3'] = [
                            'driver' => 's3',
                            'key' => $configuration->s3_key ?: '',
                            'secret' => $configuration->s3_secret ?: '',
                            'region' => $configuration->s3_region ?: 'us-east-1',
                            'bucket' => $configuration->s3_bucket,
                        ];
                    }

                    if (! empty($configuration->minio_bucket) && ! empty($configuration->minio_endpoint)) {
                        $disks['minio'] = [
                            'driver' => 's3',
                            'key' => $configuration->minio_key ?: '',
                            'secret' => $configuration->minio_secret ?: '',
                            'region' => 'us-east-1',
                            'bucket' => $configuration->minio_bucket,
                            'endpoint' => $configuration->minio_endpoint,
                            'use_path_style_endpoint' => (bool) $configuration->minio_path_style_endpoint,
                        ];
                    }

                    config()->set('filesystems.disks', array_merge(
                        config('filesystems.disks', []),
                        $disks
                    ));

                    config()->set('backup.backup.destination.disks', [
                        $configuration->default_disk ?: 'local',
                    ]);
                } catch (\Exception $e) {
                    // Silently fail during installation/migration
                    logger('Failed to load backup settings: ' . $e->getMessage());
                }
            }
        });
    }
}
