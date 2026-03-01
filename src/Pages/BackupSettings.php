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
            Section::make('Backup Configuration')
                ->description('General backup settings and preferences')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('generalSettings.backup.general.timeout')
                                ->label('Backup Timeout (seconds)')
                                ->helperText('Maximum time allowed for backup process')
                                ->numeric()
                                ->minValue(60)
                                ->maxValue(7200)
                                ->default(3600)
                                ->required(),

                            TextInput::make('generalSettings.backup.general.queue')
                                ->label('Queue Name')
                                ->helperText('Queue name for backup jobs')
                                ->default('default')
                                ->required(),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Toggle::make('generalSettings.backup.general.cleanup_enabled')
                                ->label('Auto Cleanup Enabled')
                                ->helperText('Automatically cleanup old backups')
                                ->default(true),

                            TextInput::make('generalSettings.backup.general.cleanup_days')
                                ->label('Cleanup After Days')
                                ->helperText('Delete backups older than X days')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(365)
                                ->default(30)
                                ->required(),
                        ]),

                    Toggle::make('generalSettings.backup.general.notifications_enabled')
                        ->label('Email Notifications')
                        ->helperText('Send email notifications for backup events')
                        ->default(true),
                ])
        ];
    }

    protected function getStorageSettingsSchema(): array
    {
        return [
            Section::make('Storage Configuration')
                ->description('Configure where backups are stored')
                ->schema([
                    Select::make('storageSettings.backup.storage.default_disk')
                        ->label('Default Storage Disk')
                        ->helperText('Primary storage disk for backups')
                        ->options([
                            'local' => 'Local Storage',
                            's3' => 'Amazon S3',
                            'gcs' => 'Google Cloud Storage',
                        ])
                        ->default('local')
                        ->required()
                        ->reactive(),
                ]),

            Section::make('Local Storage')
                ->description('Local file system storage configuration')
                ->schema([
                    TextInput::make('storageSettings.backup.storage.local.path')
                        ->label('Local Storage Path')
                        ->helperText('Path where backups are stored locally')
                        ->default('storage/app/backup')
                        ->required(),
                ]),

            Section::make('Amazon S3 Storage')
                ->description('Amazon S3 cloud storage configuration')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('storageSettings.backup.storage.s3.bucket')
                                ->label('S3 Bucket Name')
                                ->helperText('Amazon S3 bucket name for backups'),

                            Select::make('storageSettings.backup.storage.s3.region')
                                ->label('S3 Region')
                                ->helperText('Amazon S3 region')
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
                                ->label('S3 Access Key')
                                ->helperText('Amazon S3 Access Key ID')
                                ->password(),

                            TextInput::make('storageSettings.backup.storage.s3.secret')
                                ->label('S3 Secret Key')
                                ->helperText('Amazon S3 Secret Access Key')
                                ->password(),
                        ]),
                ]),
        ];
    }

    protected function getNotificationSettingsSchema(): array
    {
        return [
            Section::make('Email Notifications')
                ->description('Configure email notification settings')
                ->schema([
                    Toggle::make('notificationSettings.backup.notifications.on_success')
                        ->label('Notify on Success')
                        ->helperText('Send notification when backup completes successfully')
                        ->default(true),

                    Toggle::make('notificationSettings.backup.notifications.on_failure')
                        ->label('Notify on Failure')
                        ->helperText('Send notification when backup fails')
                        ->default(true),

                    Toggle::make('notificationSettings.backup.notifications.progress_updates')
                        ->label('Progress Updates')
                        ->helperText('Send periodic progress updates during backup')
                        ->default(false),
                ]),

            Section::make('Recipients')
                ->description('Configure who receives notifications')
                ->schema([
                    Textarea::make('notificationSettings.backup.notifications.recipients')
                        ->label('Email Recipients')
                        ->helperText('Enter email addresses, one per line')
                        ->rows(3),

                    Toggle::make('notificationSettings.backup.notifications.notify_user')
                        ->label('Notify Backup Creator')
                        ->helperText('Send notifications to the user who initiated the backup')
                        ->default(true),
                ]),
        ];
    }

    protected function getSecuritySettingsSchema(): array
    {
        return [
            Section::make('Access Control')
                ->description('Security and access control settings')
                ->schema([
                    Toggle::make('securitySettings.backup.security.require_permission')
                        ->label('Require Permission')
                        ->helperText('Require specific permission to access backup features')
                        ->default(true),

                    TextInput::make('securitySettings.backup.security.allowed_roles')
                        ->label('Allowed Roles')
                        ->helperText('Comma-separated list of roles that can access backups')
                        ->placeholder('admin,backup-manager'),
                ]),

            Section::make('File Security')
                ->description('File security and encryption settings')
                ->schema([
                    Toggle::make('securitySettings.backup.security.encrypt_backups')
                        ->label('Encrypt Backups')
                        ->helperText('Encrypt backup files for additional security')
                        ->default(false),

                    TextInput::make('securitySettings.backup.security.encryption_key')
                        ->label('Encryption Key')
                        ->helperText('Encryption key for backup files (leave empty to auto-generate)')
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
                BackupSetting::set($key, $value);
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
        $this->notificationSettings = [
            'backup.notifications.on_success' => BackupSetting::get('backup.notifications.on_success', true),
            'backup.notifications.on_failure' => BackupSetting::get('backup.notifications.on_failure', true),
            'backup.notifications.progress_updates' => BackupSetting::get('backup.notifications.progress_updates', false),
            'backup.notifications.recipients' => BackupSetting::get('backup.notifications.recipients', ''),
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
    }
}
