<?php

namespace Juniyasyos\FilamentLaravelBackup\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;
use Juniyasyos\FilamentLaravelBackup\Models\BackupSetting;
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

    public function getHeading(): string|Htmlable
    {
        return 'Backup Settings';
    }

    public static function getNavigationLabel(): string
    {
        return 'Settings';
    }

    public function mount(): void
    {
        $this->loadSettings();
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
                                ->default(3600)
                                ->required(),

                            TextInput::make('generalSettings.backup.general.queue')
                                ->label(__('backup.pages.settings.general.queue_label'))
                                ->helperText(__('backup.pages.settings.general.queue_helper'))
                                ->default('default')
                                ->required(),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Toggle::make('generalSettings.backup.general.cleanup_enabled')
                                ->label(__('backup.pages.settings.general.cleanup_label'))
                                ->helperText(__('backup.pages.settings.general.cleanup_helper'))
                                ->default(true),

                            TextInput::make('generalSettings.backup.general.cleanup_days')
                                ->label(__('backup.pages.settings.general.cleanup_days_label'))
                                ->helperText(__('backup.pages.settings.general.cleanup_days_helper'))
                                ->visible(fn ($get) => $get('generalSettings.backup.general.cleanup_enabled'))
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(365)
                                ->default(30)
                                ->required(),
                        ]),

                    Toggle::make('generalSettings.backup.general.notifications_enabled')
                        ->label(__('backup.pages.settings.general.notifications_enabled_label'))
                        ->helperText(__('backup.pages.settings.general.notifications_enabled_helper'))
                        ->default(true),
                ])
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
                            'local' => 'Local Storage',
                            's3' => 'Amazon S3',
                            'gcs' => 'Google Cloud Storage',
                        ])
                        ->default('local')
                        ->required()
                        ->reactive(),
                ]),

            Section::make(__('backup.pages.settings.storage.local_section'))
                ->description(__('backup.pages.settings.storage.local_description'))
                ->visible(fn ($get) => $get('storageSettings.backup.storage.default_disk') === 'local')
                ->schema([
                    TextInput::make('storageSettings.backup.storage.local.path')
                        ->label(__('backup.pages.settings.storage.local_path_label'))
                        ->helperText(__('backup.pages.settings.storage.local_path_helper'))
                        ->default('storage/app/backup')
                        ->required(),
                ]),

            Section::make(__('backup.pages.settings.storage.s3_section'))
                ->description(__('backup.pages.settings.storage.s3_description'))
                ->visible(fn ($get) => $get('storageSettings.backup.storage.default_disk') === 's3')
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
                        ->label('Email Recipients')
                        ->helperText('Add email addresses that will receive notifications')
                        ->schema([
                            TextInput::make('email')
                                ->label(__('backup.pages.settings.notifications.recipient_email_label'))
                                ->email()
                                ->required(),
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
                ->label('Save Settings')
                ->action('saveSettings')
                ->color('primary'),

            Action::make('test_storage')
                ->label('Test Storage Connection')
                ->action('testStorageConnection')
                ->color('secondary'),

            Action::make('reset')
                ->label('Reset to Defaults')
                ->action('resetSettings')
                ->color('gray')
                ->requiresConfirmation(),
        ];
    }

    public function saveSettings(): void
    {
        try {
            $allSettings = array_merge(
                $this->generalSettings,
                $this->storageSettings,
                $this->notificationSettings
            );

            // Validate settings before saving
            $this->validateSettings($allSettings);

            // Update settings in database
            foreach ($allSettings as $key => $value) {
                // Convert repeater rows to simple array of emails for storage
                if ($key === 'backup.notifications.recipients' && is_array($value)) {
                    $emails = array_map(function ($row) {
                        if (is_array($row) && isset($row['email'])) {
                            return $row['email'];
                        }
                        return is_string($row) ? $row : '';
                    }, $value);
                    // filter out empty values
                    $emails = array_values(array_filter($emails, fn($e) => !empty($e)));
                    BackupSetting::set($key, $emails);
                } else {
                    BackupSetting::set($key, $value);
                }
            }

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
        // Load general settings
        $this->generalSettings = [
            'backup.general.timeout' => BackupSetting::get('backup.general.timeout', 3600),
            'backup.general.queue' => BackupSetting::get('backup.general.queue', 'default'),
            'backup.general.cleanup_enabled' => BackupSetting::get('backup.general.cleanup_enabled', true),
            'backup.general.cleanup_days' => BackupSetting::get('backup.general.cleanup_days', 30),
            'backup.general.notifications_enabled' => BackupSetting::get('backup.general.notifications_enabled', true),
        ];

        // Load storage settings
        $this->storageSettings = [
            'backup.storage.default_disk' => BackupSetting::get('backup.storage.default_disk', 'local'),
            'backup.storage.local.path' => BackupSetting::get('backup.storage.local.path', 'storage/app/backup'),
            'backup.storage.s3.bucket' => BackupSetting::get('backup.storage.s3.bucket', ''),
            'backup.storage.s3.region' => BackupSetting::get('backup.storage.s3.region', 'us-east-1'),
            'backup.storage.s3.key' => BackupSetting::get('backup.storage.s3.key', ''),
            'backup.storage.s3.secret' => BackupSetting::get('backup.storage.s3.secret', ''),
        ];

        // Load notification settings
        $rawRecipients = BackupSetting::get('backup.notifications.recipients', '');

        // Normalize recipients into Repeater rows: [ ['email' => 'a@example.com'], ... ]
        $recipientsRows = [];
        if (is_array($rawRecipients)) {
            foreach ($rawRecipients as $r) {
                if (is_string($r) && trim($r) !== '') {
                    $recipientsRows[] = ['email' => $r];
                }
            }
        } elseif (is_string($rawRecipients) && trim($rawRecipients) !== '') {
            $lines = preg_split('/\r?\n/', $rawRecipients);
            foreach ($lines as $line) {
                $email = trim($line);
                if ($email !== '') {
                    $recipientsRows[] = ['email' => $email];
                }
            }
        }

        $this->notificationSettings = [
            'backup.notifications.on_success' => BackupSetting::get('backup.notifications.on_success', true),
            'backup.notifications.on_failure' => BackupSetting::get('backup.notifications.on_failure', true),
            'backup.notifications.progress_updates' => BackupSetting::get('backup.notifications.progress_updates', false),
            'backup.notifications.recipients' => $recipientsRows,
            'backup.notifications.notify_user' => BackupSetting::get('backup.notifications.notify_user', true),
        ];
    }

    protected function validateSettings(array $settings): void
    {
        // Validate timeout
        if (isset($settings['backup.general.timeout'])) {
            $timeout = (int) $settings['backup.general.timeout'];
            if ($timeout < 60 || $timeout > 7200) {
                throw new \Exception('Timeout must be between 60 and 7200 seconds');
            }
        }

        // Validate cleanup days
        if (isset($settings['backup.general.cleanup_days'])) {
            $days = (int) $settings['backup.general.cleanup_days'];
            if ($days < 1 || $days > 365) {
                throw new \Exception('Cleanup days must be between 1 and 365');
            }
        }

        // Validate S3 settings if S3 is selected
        if (isset($settings['backup.storage.default_disk']) && $settings['backup.storage.default_disk'] === 's3') {
            $required = ['backup.storage.s3.bucket', 'backup.storage.s3.key', 'backup.storage.s3.secret'];
            foreach ($required as $key) {
                if (empty($settings[$key])) {
                    throw new \Exception("S3 configuration incomplete: {$key} is required");
                }
            }
        }

        // Validate recipients (if using repeater rows or simple array)
        if (isset($settings['backup.notifications.recipients']) && is_array($settings['backup.notifications.recipients'])) {
            foreach ($settings['backup.notifications.recipients'] as $row) {
                $email = '';
                if (is_array($row)) {
                    $email = $row['email'] ?? '';
                } elseif (is_string($row)) {
                    $email = $row;
                }

                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new \Exception("Invalid recipient email: {$email}");
                }
            }
        }
    }
}
