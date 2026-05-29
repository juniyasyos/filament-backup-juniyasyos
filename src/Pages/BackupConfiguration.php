<?php

namespace Juniyasyos\FilamentLaravelBackup\Pages;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Juniyasyos\FilamentLaravelBackup\Models\BackupConfiguration as BackupConfigurationModel;

class BackupConfiguration extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament-spatie-backup::pages.backup-configuration';
    protected static ?string $navigationGroup = 'Backup Management';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'backup-configuration';

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $this->loadConfiguration();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('General')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('data.default_disk')
                            ->label('Default Disk')
                            ->options([
                                'local' => 'Local',
                                's3' => 'S3',
                                'gcs' => 'GCS',
                            ])
                            ->required(),

                        TextInput::make('data.queue')
                            ->label('Queue')
                            ->default('default')
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('data.timeout')
                            ->label('Timeout (sec)')
                            ->numeric()
                            ->default(3600)
                            ->required(),

                        ToggleButtons::make('data.cleanup_enabled')
                            ->label('Auto Cleanup')
                            ->options([true => 'Yes', false => 'No'])
                            ->default(false),
                    ]),
                ]),

            Section::make('Storage')
                ->schema([
                    TextInput::make('data.local_path')->label('Local Path'),
                    TextInput::make('data.s3_bucket')->label('S3 Bucket'),
                    TextInput::make('data.s3_region')->label('S3 Region'),
                    TextInput::make('data.s3_key')->label('S3 Key'),
                    TextInput::make('data.s3_secret')->label('S3 Secret'),
                ]),
        ])->statePath('data');
    }

    public function loadConfiguration(): void
    {
        $row = BackupConfigurationModel::getRow();
        if ($row) {
            $this->data = $row->toArray();
        } else {
            $this->data = [
                'default_disk' => 'local',
                'local_path' => 'storage/app/backup',
                's3_bucket' => null,
                's3_region' => 'us-east-1',
                's3_key' => null,
                's3_secret' => null,
                'timeout' => 3600,
                'queue' => 'default',
                'cleanup_enabled' => false,
                'cleanup_days' => null,
            ];
        }
    }

    public function saveConfiguration(): void
    {
        try {
            $data = $this->data ?? [];
            $row = BackupConfigurationModel::getRow();
            if ($row) {
                $row->fill($data);
                $row->save();
            } else {
                BackupConfigurationModel::create($data);
            }

            Notification::make()
                ->title('Configuration Saved')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Save Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getActions(): array
    {
        return [];
    }
}
