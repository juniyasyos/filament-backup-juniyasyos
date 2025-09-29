<?php

namespace Juniyasyos\FilamentLaravelBackup\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Juniyasyos\FilamentLaravelBackup\Enums\Option;
use Juniyasyos\FilamentLaravelBackup\FilamentLaravelBackupPlugin;
use Juniyasyos\FilamentLaravelBackup\Jobs\CreateBackupJob;
use Juniyasyos\FilamentLaravelBackup\Pages\BackupSettings;

class Backups extends Page
{

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cloud-arrow-down';

    public function getHeading(): string|Htmlable
    {
        return __('filament-spatie-backup::backup.pages.backups.heading');
    }

    public function getView(): string
    {
        return 'filament-spatie-backup::pages.backups';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-spatie-backup::backup.pages.backups.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-spatie-backup::backup.pages.backups.navigation.label');
    }

    protected function getActions(): array
    {
        return [
            Action::make('createBackup')
                ->label(__('filament-spatie-backup::backup.pages.backups.actions.create_backup'))
                ->form([
                    ToggleButtons::make('option')
                        ->label(__('filament-spatie-backup::backup.pages.backups.form.option.label'))
                        ->inline()
                        ->options([
                            Option::ALL->value => __('filament-spatie-backup::backup.pages.backups.form.option.all'),
                            Option::ONLY_DB->value => __('filament-spatie-backup::backup.pages.backups.form.option.only_db'),
                            Option::ONLY_FILES->value => __('filament-spatie-backup::backup.pages.backups.form.option.only_files'),
                        ])
                        ->default(Option::ALL->value)
                        ->required()
                ])
                ->action(function (array $data) {
                    $this->create($data['option'] ?? 'all');
                })
                ->modalWidth(Width::ThreeExtraLarge)
                ->modalHeading(__('filament-spatie-backup::backup.pages.backups.modal.heading'))
                ->modalSubmitActionLabel(__('filament-spatie-backup::backup.pages.backups.modal.submit'))
                ->requiresConfirmation(),

            Action::make('openSettings')
                ->label(__('filament-spatie-backup::backup.pages.settings.navigation.label'))
                ->icon('heroicon-o-cog-6-tooth')
                ->url(BackupSettings::getUrl())
                ->color('gray')
                ->outlined(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return $this->getActions();
    }

    public function create(string $option = 'all'): void
    {
        /** @var FilamentLaravelBackupPlugin $plugin */
        $plugin = filament()->getPlugin('filament-spatie-backup');

        CreateBackupJob::dispatchAfterResponse(
            Option::from($option),
            $plugin->getTimeout(),
            Auth::user()
        )
            ->onQueue($plugin->getQueue());

        Notification::make()
            ->title(__('filament-spatie-backup::backup.pages.backups.messages.backup_success'))
            ->info()
            ->send();
    }

    public function shouldDisplayStatusListRecords(): bool
    {
        /** @var FilamentLaravelBackupPlugin $plugin */
        $plugin = filament()->getPlugin('filament-spatie-backup');

        return $plugin->hasStatusListRecordsTable();
    }
}
