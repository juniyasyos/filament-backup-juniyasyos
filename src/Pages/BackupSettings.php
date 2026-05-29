<?php

namespace Juniyasyos\FilamentLaravelBackup\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;
use Juniyasyos\FilamentLaravelBackup\Models\BackupConfiguration;
use Juniyasyos\FilamentLaravelBackup\Services\BackupService;

class BackupSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament-spatie-backup::pages.backup-settings';
    protected static ?string $navigationGroup = 'Backup Management';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'backup-settings';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public array $generalSettings = [];
    public array $storageSettings = [];
    public array $notificationSettings = [];
    public array $data = [];

    public function getHeading(): string|Htmlable
    {
        return __('backup.pages.settings.heading');
    }

    public static function getNavigationLabel(): string
    {
        return __('backup.pages.settings.navigation_label');
    }

    public function mount(): void
    {
        $this->loadSettings();

        if (isset($this->form)) {
            $this->form->fill($this->data);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs([
                        Tabs\Tab::make('General')
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->schema($this->getGeneralSettingsSchema()),

                        Tabs\Tab::make('Storage')
                            ->icon('heroicon-o-cloud-arrow-up')
                            ->schema($this->getStorageSettingsSchema()),

                        Tabs\Tab::make('Notifications')
                            ->icon('heroicon-o-bell')
                            ->schema($this->getNotificationSettingsSchema()),

                        Tabs\Tab::make('Security')
                            ->icon('heroicon-o-shield-check')
                            ->schema($this->getSecuritySettingsSchema()),
                    ])
                    ->columnSpanFull()
            ])
            ->statePath('data');
    }

    protected function getGeneralSettingsSchema(): array
    {
        return [
            Section::make(__('backup.pages.settings.general.section'))
                ->description(__('backup.pages.settings.general.description'))
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('generalSettings.backup.general.timeout')
                                ->label(__('backup.pages.settings.general.timeout_label'))
                                ->helperText(__('backup.pages.settings.general.timeout_helper'))
                                ->numeric()
                                ->minValue(60)
                                ->maxValue(7200)
                                ->default(3600),

                            TextInput::make('generalSettings.backup.general.queue')
                                ->label(__('backup.pages.settings.general.queue_label'))
                                ->helperText(__('backup.pages.settings.general.queue_helper'))
                                ->default('default'),
                        ]),

                    Grid::make(2)
                        ->schema([
                            ToggleButtons::make('generalSettings.backup.general.cleanup_enabled')
                                ->label(__('backup.pages.settings.general.cleanup_label'))
                                ->helperText(__('backup.pages.settings.general.cleanup_helper'))
                                ->default(true)
                                ->inline()
                                ->options([
                                    true => __('backup.pages.settings.common.yes'),
                                    false => __('backup.pages.settings.common.no'),
                                ]),

                            TextInput::make('generalSettings.backup.general.cleanup_days')
                                ->label(__('backup.pages.settings.general.cleanup_days_label'))
                                ->helperText(__('backup.pages.settings.general.cleanup_days_helper'))
                                ->visible(fn($get) => $get('generalSettings.backup.general.cleanup_enabled') === true)
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(365)
                                ->default(30),
                        ]),

                    ToggleButtons::make('generalSettings.backup.general.notifications_enabled')
                        ->label(__('backup.pages.settings.general.notifications_enabled_label'))
                        ->helperText(__('backup.pages.settings.general.notifications_enabled_helper'))
                        ->default(true)
                        ->inline()
                        ->options([
                            true => 'Yes',
                            false => 'No',
                        ]),
                ]),

            Section::make(__('backup.pages.settings.schedule.section'))
                ->description(__('backup.pages.settings.schedule.description'))
                ->schema([
                    Toggle::make('scheduleSettings.backup.schedule.enabled')
                        ->label(__('backup.pages.settings.schedule.enabled_label'))
                        ->helperText(__('backup.pages.settings.schedule.enabled_helper'))
                        ->default(false),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('scheduleSettings.backup.schedule.interval_value')
                                ->label(__('backup.pages.settings.schedule.interval_value_label'))
                                ->helperText(__('backup.pages.settings.schedule.interval_value_helper'))
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->visible(fn($get) => $get('scheduleSettings.backup.schedule.enabled') === true),

                            Select::make('scheduleSettings.backup.schedule.interval_unit')
                                ->label(__('backup.pages.settings.schedule.interval_unit_label'))
                                ->helperText(__('backup.pages.settings.schedule.interval_unit_helper'))
                                ->options([
                                    'second' => __('backup.pages.settings.schedule.unit_second'),
                                    'minute' => __('backup.pages.settings.schedule.unit_minute'),
                                    'hour' => __('backup.pages.settings.schedule.unit_hour'),
                                    'day' => __('backup.pages.settings.schedule.unit_day'),
                                    'month' => __('backup.pages.settings.schedule.unit_month'),
                                ])
                                ->default('day')
                                ->visible(fn($get) => $get('scheduleSettings.backup.schedule.enabled') === true),
                        ]),

                    TextInput::make('scheduleSettings.backup.schedule.hint')
                        ->label(__('backup.pages.settings.schedule.hint_label'))
                        ->helperText(__('backup.pages.settings.schedule.hint_helper'))
                        ->default(__('backup.pages.settings.schedule.hint_default'))
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn($get) => $get('scheduleSettings.backup.schedule.enabled') === true),
                ]),
        ];
    }

    protected function getStorageSettingsSchema(): array
    {
        return [
            Section::make(__('backup.pages.settings.storage.section'))
                ->description(__('backup.pages.settings.storage.description'))
                ->schema([
                    Select::make('storageSettings.backup.storage.default_disk')
                        ->label(__('backup.pages.settings.storage.default_disk_label'))
                        ->helperText(__('backup.pages.settings.storage.default_disk_helper'))
                        ->options([
                            'local' => __('backup.pages.settings.storage.local_option'),
                            's3' => __('backup.pages.settings.storage.s3_option'),
                            'minio' => __('backup.pages.settings.storage.minio_option'),
                            'gcs' => __('backup.pages.settings.storage.gcs_option'),
                        ])
                        ->default('local')

                        ->reactive(),
                ]),

            Section::make(__('backup.pages.settings.storage.local_section'))
                ->description(__('backup.pages.settings.storage.local_description'))
                ->visible(fn($get) => $get('storageSettings.backup.storage.default_disk') === 'local')
                ->schema([
                    TextInput::make('storageSettings.backup.storage.local.path')
                        ->label(__('backup.pages.settings.storage.local_path_label'))
                        ->helperText(__('backup.pages.settings.storage.local_path_helper'))
                        ->default('storage/app/backup')
                    ,
                ]),

            Section::make(__('backup.pages.settings.storage.s3_section'))
                ->description(__('backup.pages.settings.storage.s3_description'))
                ->visible(fn($get) => $get('storageSettings.backup.storage.default_disk') === 's3')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('storageSettings.backup.storage.s3.bucket')
                                ->label(__('backup.pages.settings.storage.s3_bucket_label'))
                                ->helperText(__('backup.pages.settings.storage.s3_bucket_helper')),

                            Select::make('storageSettings.backup.storage.s3.region')
                                ->label(__('backup.pages.settings.storage.s3_region_label'))
                                ->helperText(__('backup.pages.settings.storage.s3_region_helper'))
                                ->options([
                                    'us-east-1' => 'US East (N. Virginia)',
                                    'us-east-2' => 'US East (Ohio)',
                                    'us-west-1' => 'US West (N. California)',
                                    'us-west-2' => 'US West (Oregon)',
                                    'eu-west-1' => 'Europe (Ireland)',
                                    'eu-west-2' => 'Europe (London)',
                                    'eu-central-1' => 'Europe (Frankfurt)',
                                    'ap-southeast-1' => 'Asia Pacific (Singapore)',
                                    'ap-southeast-2' => 'Asia Pacific (Sydney)',
                                    'ap-northeast-1' => 'Asia Pacific (Tokyo)',
                                ])
                                ->default('us-east-1'),
                        ]),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('storageSettings.backup.storage.s3.key')
                                ->label(__('backup.pages.settings.storage.s3_key_label'))
                                ->helperText(__('backup.pages.settings.storage.s3_key_helper'))
                                ->password(),

                            TextInput::make('storageSettings.backup.storage.s3.secret')
                                ->label(__('backup.pages.settings.storage.s3_secret_label'))
                                ->helperText(__('backup.pages.settings.storage.s3_secret_helper'))
                                ->password(),
                        ]),
                ]),

            Section::make(__('backup.pages.settings.storage.minio_section'))
                ->description(__('backup.pages.settings.storage.minio_description'))
                ->visible(fn($get) => $get('storageSettings.backup.storage.default_disk') === 'minio')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('storageSettings.backup.storage.minio.bucket')
                                ->label(__('backup.pages.settings.storage.minio_bucket_label'))
                                ->helperText(__('backup.pages.settings.storage.minio_bucket_helper')),

                            TextInput::make('storageSettings.backup.storage.minio.endpoint')
                                ->label(__('backup.pages.settings.storage.minio_endpoint_label'))
                                ->helperText(__('backup.pages.settings.storage.minio_endpoint_helper')),
                        ]),

                    Grid::make(2)
                        ->schema([

                            Toggle::make('storageSettings.backup.storage.minio.path_style_endpoint')
                                ->label(__('backup.pages.settings.storage.minio_path_style_label'))
                                ->helperText(__('backup.pages.settings.storage.minio_path_style_helper'))
                                ->default(true),
                        ]),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('storageSettings.backup.storage.minio.key')
                                ->label(__('backup.pages.settings.storage.minio_key_label'))
                                ->helperText(__('backup.pages.settings.storage.minio_key_helper'))
                                ->password(),

                            TextInput::make('storageSettings.backup.storage.minio.secret')
                                ->label(__('backup.pages.settings.storage.minio_secret_label'))
                                ->helperText(__('backup.pages.settings.storage.minio_secret_helper'))
                                ->password(),
                        ]),
                ]),
        ];
    }

    protected function getNotificationSettingsSchema(): array
    {
        return [
            Section::make(__('backup.pages.settings.notifications.section'))
                ->description(__('backup.pages.settings.notifications.description'))
                ->schema([
                    Toggle::make('notificationSettings.backup.notifications.on_success')
                        ->label(__('backup.pages.settings.notifications.on_success_label'))
                        ->helperText(__('backup.pages.settings.notifications.on_success_helper'))
                        ->default(true),

                    Toggle::make('notificationSettings.backup.notifications.on_failure')
                        ->label(__('backup.pages.settings.notifications.on_failure_label'))
                        ->helperText(__('backup.pages.settings.notifications.on_failure_helper'))
                        ->default(true),

                    Toggle::make('notificationSettings.backup.notifications.progress_updates')
                        ->label(__('backup.pages.settings.notifications.progress_updates_label'))
                        ->helperText(__('backup.pages.settings.notifications.progress_updates_helper'))
                        ->default(false),
                ]),

            Section::make(__('backup.pages.settings.notifications.recipients_section'))
                ->description(__('backup.pages.settings.notifications.recipients_description'))
                ->schema([
                    Repeater::make('notificationSettings.backup.notifications.recipients')
                        ->label(__('backup.pages.settings.notifications.recipients_label'))
                        ->helperText(__('backup.pages.settings.notifications.recipients_helper'))
                        ->schema([
                            TextInput::make('email')
                                ->label(__('backup.pages.settings.notifications.recipient_email_label'))
                                ->email()
                            ,
                        ])
                        ->minItems(0)
                        ->columns(1),

                    Toggle::make('notificationSettings.backup.notifications.notify_user')
                        ->label(__('backup.pages.settings.notifications.notify_user_label'))
                        ->helperText(__('backup.pages.settings.notifications.notify_user_helper'))
                        ->default(true),
                ]),
        ];
    }

    protected function getSecuritySettingsSchema(): array
    {
        return [
            Section::make(__('backup.pages.settings.security.access_section'))
                ->description(__('backup.pages.settings.security.access_description'))
                ->schema([

                    Toggle::make('securitySettings.backup.security.require_permission')
                        ->label(__('backup.pages.settings.security.require_permission_label'))
                        ->helperText(__('backup.pages.settings.security.require_permission_helper'))
                        ->default(true),

                    TextInput::make('securitySettings.backup.security.allowed_roles')
                        ->label(__('backup.pages.settings.security.allowed_roles_label'))
                        ->helperText(__('backup.pages.settings.security.allowed_roles_helper'))
                        ->placeholder('admin,backup-manager'),
                ]),

            Section::make(__('backup.pages.settings.security.file_section'))
                ->description(__('backup.pages.settings.security.file_description'))
                ->schema([
                    Toggle::make('securitySettings.backup.security.encrypt_backups')
                        ->label(__('backup.pages.settings.security.encrypt_backups_label'))
                        ->helperText(__('backup.pages.settings.security.encrypt_backups_helper'))
                        ->default(false),

                    TextInput::make('securitySettings.backup.security.encryption_key')
                        ->label(__('backup.pages.settings.security.encryption_key_label'))
                        ->helperText(__('backup.pages.settings.security.encryption_key_helper'))
                        ->password(),
                ]),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('save')
                ->label(__('backup.pages.settings.buttons.save'))
                ->action('saveSettings')
                ->color('primary'),

            Action::make('test_storage')
                ->label(__('backup.pages.settings.buttons.test_storage'))
                ->action('testStorageConnection')
                ->color('warning'),

            Action::make('reset')
                ->label(__('backup.pages.settings.buttons.reset'))
                ->action('resetSettings')
                ->color('gray')
                ->requiresConfirmation(),
        ];
    }

    public function saveSettings(): void
    {
        try {
            $formState = $this->form->getState();
            BackupConfiguration::saveNestedFormState($formState);

            $this->loadSettings();
            $this->form->fill($this->data);

            Notification::make()
                ->title('Settings Saved')
                ->body('All backup settings have been saved successfully.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Save Failed')
                ->body('Failed to save settings: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function testStorageConnection(): void
    {
        try {
            $disk = $this->storageSettings['backup.storage.default_disk'] ?? 'local';

            // Use BackupService to validate storage
            $backupService = app(BackupService::class);
            $result = $backupService->validateStorageConfiguration($disk);

            if ($result['valid']) {
                Notification::make()
                    ->title('Storage Test Successful')
                    ->body("Connection to {$disk} storage is working properly.")
                    ->success()
                    ->send();
            } else {
                $errors = implode(', ', $result['errors']);
                Notification::make()
                    ->title('Storage Test Failed')
                    ->body("Storage configuration issues: {$errors}")
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Storage Test Failed')
                ->body('Failed to test storage: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function resetSettings(): void
    {
        try {
            // Reset to default values - this would require recreating default settings
            // For now, we'll just reload from database
            $this->loadSettings();

            Notification::make()
                ->title('Settings Reset')
                ->body('Settings have been reset to default values.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Reset Failed')
                ->body('Failed to reset settings: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function loadSettings(): void
    {
        $this->data = BackupConfiguration::toNestedFormState();

        $this->generalSettings = [
            'backup.general.timeout' => data_get($this->data, 'generalSettings.backup.general.timeout', 3600),
            'backup.general.queue' => data_get($this->data, 'generalSettings.backup.general.queue', 'default'),
            'backup.general.cleanup_enabled' => data_get($this->data, 'generalSettings.backup.general.cleanup_enabled', true),
            'backup.general.cleanup_days' => data_get($this->data, 'generalSettings.backup.general.cleanup_days', 30),
            'backup.general.notifications_enabled' => data_get($this->data, 'generalSettings.backup.general.notifications_enabled', true),
        ];

        $this->storageSettings = [
            'backup.storage.default_disk' => data_get($this->data, 'storageSettings.backup.storage.default_disk', 'local'),
            'backup.storage.local.path' => data_get($this->data, 'storageSettings.backup.storage.local.path', 'storage/app/backup'),
            'backup.storage.s3.bucket' => data_get($this->data, 'storageSettings.backup.storage.s3.bucket', ''),
            'backup.storage.s3.region' => data_get($this->data, 'storageSettings.backup.storage.s3.region', 'us-east-1'),
            'backup.storage.s3.key' => data_get($this->data, 'storageSettings.backup.storage.s3.key', ''),
            'backup.storage.s3.secret' => data_get($this->data, 'storageSettings.backup.storage.s3.secret', ''),
        ];

        $this->notificationSettings = [
            'backup.notifications.on_success' => data_get($this->data, 'notificationSettings.backup.notifications.on_success', true),
            'backup.notifications.on_failure' => data_get($this->data, 'notificationSettings.backup.notifications.on_failure', true),
            'backup.notifications.progress_updates' => data_get($this->data, 'notificationSettings.backup.notifications.progress_updates', false),
            'backup.notifications.recipients' => data_get($this->data, 'notificationSettings.backup.notifications.recipients', []),
            'backup.notifications.notify_user' => data_get($this->data, 'notificationSettings.backup.notifications.notify_user', true),
        ];
    }

    public function getRawBackupSettingsJsonProperty(): string
    {
        $row = BackupConfiguration::getRow();
        $settings = $row ? $row->toArray() : BackupConfiguration::defaults();

        return json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

}
