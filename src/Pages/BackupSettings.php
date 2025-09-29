<?php

namespace Juniyasyos\FilamentLaravelBackup\Pages;

use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;
use Juniyasyos\FilamentLaravelBackup\Models\BackupSetting;

class BackupSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $slug = 'backups/settings';

    protected static bool $shouldRegisterNavigation = false;

    public function getView(): string
    {
        return 'filament-spatie-backup::pages.backup-settings';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $setting = BackupSetting::singleton();

        $this->form->fill([
            'enabled' => $setting->enabled,
            'allow_manual_runs' => $setting->allow_manual_runs,
            'require_password' => $setting->require_password,
            'password' => '',
            'encrypt_backups' => $setting->encrypt_backups,
            'encryption_password' => '',
            'use_queue' => $setting->use_queue,
            'queue' => $setting->queue,
            'notification_channel' => $setting->notification_channel,
            'notification_targets' => $setting->notification_targets ?? [],
            'scheduled' => $setting->scheduled,
            'schedule_cron' => $setting->schedule_cron,
            'retention_days' => $setting->retention_days,
            'retention_copies' => $setting->retention_copies,
            'allowed_disks' => $setting->allowed_disks ?? [],
            'options' => $setting->options ?? [],
        ]);
    }

    public function getHeading(): string|Htmlable
    {
        return __('filament-spatie-backup::backup.pages.settings.heading');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-spatie-backup::backup.pages.backups.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-spatie-backup::backup.pages.settings.navigation.label');
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema($this->settingsFormSchema())
            ->statePath('data');
    }

    protected function settingsFormSchema(): array
    {
        $diskOptions = array_keys(config('filesystems.disks', []));

        return [
            Section::make(__('filament-spatie-backup::backup.pages.settings.sections.general'))
                ->description(__('filament-spatie-backup::backup.pages.settings.descriptions.general'))
                ->columns(3)
                ->schema([
                    Toggle::make('enabled')->label(__('filament-spatie-backup::backup.pages.settings.fields.enabled'))->inline(false),
                    Toggle::make('allow_manual_runs')->label(__('filament-spatie-backup::backup.pages.settings.fields.allow_manual_runs'))->inline(false),
                    Toggle::make('require_password')->label(__('filament-spatie-backup::backup.pages.settings.fields.require_password'))->inline(false),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->label(__('filament-spatie-backup::backup.pages.settings.fields.new_password'))
                        ->helperText(__('filament-spatie-backup::backup.pages.settings.helper_texts.password'))
                        ->visible(fn(Get $get) => (bool) $get('require_password')),
                ]),

            Section::make(__('filament-spatie-backup::backup.pages.settings.sections.security'))
                ->description(__('filament-spatie-backup::backup.pages.settings.descriptions.security'))
                ->columns(2)
                ->schema([
                    Toggle::make('encrypt_backups')->label(__('filament-spatie-backup::backup.pages.settings.fields.encrypt_backups')),
                    TextInput::make('encryption_password')
                        ->password()
                        ->revealable()
                        ->label(__('filament-spatie-backup::backup.pages.settings.fields.encryption_password'))
                        ->helperText(__('filament-spatie-backup::backup.pages.settings.helper_texts.encryption_password'))
                        ->visible(fn(Get $get) => (bool) $get('encrypt_backups')),
                ]),

            Section::make(__('filament-spatie-backup::backup.pages.settings.sections.queue_and_notifications'))
                ->description(__('filament-spatie-backup::backup.pages.settings.descriptions.queue_and_notifications'))
                ->columns(3)
                ->schema([
                    Toggle::make('use_queue')->label(__('filament-spatie-backup::backup.pages.settings.fields.use_queue')),
                    TextInput::make('queue')
                        ->label(__('filament-spatie-backup::backup.pages.settings.fields.queue'))
                        ->placeholder('default')
                        ->helperText(__('filament-spatie-backup::backup.pages.settings.helper_texts.queue'))
                        ->columnSpan(2)
                        ->visible(fn(Get $get) => (bool) $get('use_queue')),

                    TextInput::make('notification_channel')
                        ->label(__('filament-spatie-backup::backup.pages.settings.fields.notification_channel'))
                        ->placeholder(__('filament-spatie-backup::backup.pages.settings.placeholders.notification_channel')),
                    TagsInput::make('notification_targets')
                        ->label(__('filament-spatie-backup::backup.pages.settings.fields.notification_targets'))
                        ->placeholder(__('filament-spatie-backup::backup.pages.settings.placeholders.notification_targets')),
                ]),

            Section::make(__('filament-spatie-backup::backup.pages.settings.sections.scheduling'))
                ->description(__('filament-spatie-backup::backup.pages.settings.descriptions.scheduling'))
                ->columns(2)
                ->schema([
                    Toggle::make('scheduled')->label(__('filament-spatie-backup::backup.pages.settings.fields.scheduled')),
                    TextInput::make('schedule_cron')
                        ->placeholder('0 3 * * *')
                        ->helperText(__('filament-spatie-backup::backup.pages.settings.helper_texts.schedule_cron'))
                        ->visible(fn(Get $get) => (bool) $get('scheduled')),
                ]),

            Section::make(__('filament-spatie-backup::backup.pages.settings.sections.retention'))
                ->description(__('filament-spatie-backup::backup.pages.settings.descriptions.retention'))
                ->columns(2)
                ->schema([
                    TextInput::make('retention_days')->numeric()->minValue(0)->label(__('filament-spatie-backup::backup.pages.settings.fields.retention_days')),
                    TextInput::make('retention_copies')->numeric()->minValue(0)->label(__('filament-spatie-backup::backup.pages.settings.fields.retention_copies')),
                ]),

            Section::make(__('filament-spatie-backup::backup.pages.settings.sections.scopes'))
                ->description(__('filament-spatie-backup::backup.pages.settings.descriptions.scopes'))
                ->columns(2)
                ->schema([
                    TagsInput::make('allowed_disks')
                        ->label(__('filament-spatie-backup::backup.pages.settings.fields.allowed_disks'))
                        ->placeholder(__('filament-spatie-backup::backup.pages.settings.placeholders.allowed_disks'))
                        ->suggestions($diskOptions),
                ]),

            Section::make(__('filament-spatie-backup::backup.pages.settings.sections.advanced'))
                ->description(__('filament-spatie-backup::backup.pages.settings.descriptions.advanced'))
                ->schema([
                    KeyValue::make('options')
                        ->label(__('filament-spatie-backup::backup.pages.settings.fields.options'))
                        ->addButtonLabel(__('filament-spatie-backup::backup.pages.settings.actions.add_option'))
                        ->reorderable()
                        ->columnSpanFull(),
                ]),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $setting = BackupSetting::singleton();

        $requiresPassword = (bool) ($data['require_password'] ?? false);
        $encryptBackups = (bool) ($data['encrypt_backups'] ?? false);

        if ($requiresPassword && empty($data['password']) && empty($setting->password)) {
            Notification::make()
                ->title(__('filament-spatie-backup::backup.pages.settings.notifications.password_required.title'))
                ->body(__('filament-spatie-backup::backup.pages.settings.notifications.password_required.body'))
                ->danger()
                ->send();

            return;
        }

        if ($encryptBackups && empty($data['encryption_password']) && empty($setting->encryption_password)) {
            Notification::make()
                ->title(__('filament-spatie-backup::backup.pages.settings.notifications.encryption_password_required.title'))
                ->body(__('filament-spatie-backup::backup.pages.settings.notifications.encryption_password_required.body'))
                ->danger()
                ->send();

            return;
        }

        $setting->enabled = (bool) ($data['enabled'] ?? false);
        $setting->allow_manual_runs = (bool) ($data['allow_manual_runs'] ?? false);
        $setting->require_password = $requiresPassword;

        if (! empty($data['password'])) {
            $setting->password = Hash::make($data['password']);
        } elseif (! $requiresPassword) {
            $setting->password = null;
        }

        $setting->encrypt_backups = $encryptBackups;
        if (! empty($data['encryption_password'])) {
            $setting->encryption_password = $data['encryption_password'];
        } elseif (! $encryptBackups) {
            $setting->encryption_password = null;
        }

        $setting->use_queue = (bool) ($data['use_queue'] ?? false);
        $setting->queue = $data['queue'] ?? null;

        $notificationTargets = $data['notification_targets'] ?? [];
        if (! is_array($notificationTargets)) {
            $notificationTargets = (array) $notificationTargets;
        }

        $setting->notification_channel = $data['notification_channel'] ?? null;
        $setting->notification_targets = array_values($notificationTargets);

        $setting->scheduled = (bool) ($data['scheduled'] ?? false);
        $setting->schedule_cron = $data['schedule_cron'] ?? null;

        $setting->retention_days = $data['retention_days'] !== '' ? $data['retention_days'] : null;
        $setting->retention_copies = $data['retention_copies'] !== '' ? $data['retention_copies'] : null;

        $allowedDisks = $data['allowed_disks'] ?? [];
        if (! is_array($allowedDisks)) {
            $allowedDisks = (array) $allowedDisks;
        }

        $setting->allowed_disks = array_values($allowedDisks);
        $setting->options = $data['options'] ?? [];

        $setting->save();

        $this->form->fill(array_merge($data, [
            'password' => '',
            'encryption_password' => '',
        ]));

        Notification::make()
            ->title(__('filament-spatie-backup::backup.pages.settings.notifications.saved.title'))
            ->body(__('filament-spatie-backup::backup.pages.settings.notifications.saved.body'))
            ->success()
            ->send();
    }
}
