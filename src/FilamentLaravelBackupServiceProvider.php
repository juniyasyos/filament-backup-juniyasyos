<?php

namespace Juniyasyos\FilamentLaravelBackup;

use Livewire\Livewire;
use Juniyasyos\FilamentLaravelBackup\Commands\BackupSetupCommand;
use Juniyasyos\FilamentLaravelBackup\Components\BackupDestinationListRecords;
use Juniyasyos\FilamentLaravelBackup\Components\BackupDestinationStatusListRecords;
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
            ->hasMigration('create_filament_backup_runs_table')
            ->hasMigration('create_filament_backup_settings_table')
            ->hasCommands([
                BackupSetupCommand::class,
            ]);
    }

    public function packageBooted(): void
    {
        Livewire::component('backup-destination-list-records', BackupDestinationListRecords::class);
        Livewire::component('backup-destination-status-list-records', BackupDestinationStatusListRecords::class);
    }
}
