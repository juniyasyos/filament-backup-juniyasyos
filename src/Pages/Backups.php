<?php

namespace Juniyasyos\FilamentLaravelBackup\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Juniyasyos\FilamentLaravelBackup\Enums\Option;
use Juniyasyos\FilamentLaravelBackup\Jobs\CreateBackupJob;
use Juniyasyos\FilamentLaravelBackup\FilamentLaravelBackupPlugin;
use Juniyasyos\FilamentSettingsHub\Traits\UseShield;

class Backups extends Page
{
    use UseShield;

    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-down';

    protected static string $view = 'filament-spatie-backup::pages.backups';

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

    protected function getActions(): array
    {
        return [
            Action::make('Create Backup')
                ->label('Create Backup')
                ->form([
                    ToggleButtons::make('option')
                        ->label('Backup Option')
                        ->inline()
                        ->options([
                            '' => 'All (Database + Files)',
                            'only-db' => 'Only Database',
                            'only-files' => 'Only Files',
                        ])
                        ->default('')
                        ->required()
                ])
                ->action(function (array $data) {
                    $this->create($data['option']);
                })
                ->modalWidth(MaxWidth::FourExtraLarge)
                ->modalHeading('Pilih Jenis Backup')
                ->modalSubmitActionLabel('Jalankan Backup')
                ->requiresConfirmation(),
        ];
    }

    /**
     * Filament v4 uses header actions instead of page actions for the page header.
     */
    protected function getHeaderActions(): array
    {
        return $this->getActions();
    }

    public function create(string $option = ''): void
    {
        /** @var FilamentLaravelBackupPlugin $plugin */
        $plugin = filament()->getPlugin('filament-spatie-backup');

        CreateBackupJob::dispatch(
            Option::from($option),
            $plugin->getTimeout(),
            Auth::user()
        )
            ->onQueue($plugin->getQueue())
            ->afterResponse();

        Notification::make()
            ->title('Backup sedang diproses')
            ->body('Anda akan mendapatkan notifikasi jika backup telah selesai.')
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
