<?php

namespace Juniyasyos\FilamentLaravelBackup\Pages;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TagsInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Juniyasyos\FilamentLaravelBackup\Models\BackupSetting as FilamentBackupSetting; // <— tambahkan ini
use Juniyasyos\FilamentLaravelBackup\Enums\Option;
use Juniyasyos\FilamentLaravelBackup\Jobs\CreateBackupJob;
use Juniyasyos\FilamentLaravelBackup\FilamentLaravelBackupPlugin;

class Backups extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

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
            // === Action modal Create Backup (punya Mas, tetap kita pakai) ===
            Action::make('Create Backup')
                ->label('Create Backup')
                ->form([
                    Forms\Components\ToggleButtons::make('option')
                        ->label('Backup Option')
                        ->inline()
                        ->options([
                            'all' => 'All (Database + Files)',
                            'only-db' => 'Only Database',
                            'only-files' => 'Only Files',
                        ])
                        ->default('all')
                        ->required()
                ])
                ->action(function (array $data) {
                    $this->create($data['option'] ?? 'all');
                })
                ->modalWidth(Width::FourExtraLarge)
                ->modalHeading('Pilih Jenis Backup')
                ->modalSubmitActionLabel('Jalankan Backup')
                ->requiresConfirmation(),

            // === Action modal Pengaturan (baru) ===
            Action::make('settings')
                ->label('')
                ->icon('heroicon-o-cog-6-tooth')
                ->modalHeading('Pengaturan Backup')
                ->modalSubmitActionLabel('Simpan Pengaturan')
                ->modalWidth(Width::FourExtraLarge)
                ->schema($this->settingsFormSchema())
                ->fillForm(function () {
                    $s = FilamentBackupSetting::singleton();

                    return [
                        'enabled'              => $s->enabled,
                        'allow_manual_runs'    => $s->allow_manual_runs,
                        'require_password'     => $s->require_password,
                        'password'             => '', // hanya isi untuk ganti
                        'encrypt_backups'      => $s->encrypt_backups,
                        'encryption_password'  => '',
                        'use_queue'            => $s->use_queue,
                        'queue'                => $s->queue,
                        'notification_channel' => $s->notification_channel,
                        'notification_targets' => $s->notification_targets ?? [],
                        'scheduled'            => $s->scheduled,
                        'schedule_cron'        => $s->schedule_cron,
                        'retention_days'       => $s->retention_days,
                        'retention_copies'     => $s->retention_copies,
                        'allowed_disks'        => $s->allowed_disks ?? [],
                        'options'              => $s->options ?? [],
                    ];
                })
                ->action(function (array $data) {
                    $s = FilamentBackupSetting::singleton();

                    $s->enabled             = (bool) ($data['enabled'] ?? false);
                    $s->allow_manual_runs   = (bool) ($data['allow_manual_runs'] ?? false);
                    $s->require_password    = (bool) ($data['require_password'] ?? false);
                    if (!empty($data['require_password'])) {
                        if (!empty($data['password'])) {
                            $s->password = Hash::make($data['password']);
                        } elseif (empty($s->password)) {
                            Notification::make()
                                ->title('Password wajib diisi')
                                ->body('Karena "Require Password" aktif, isi password untuk menyimpan.')
                                ->danger()
                                ->send();

                            $this->halt();
                        }
                    }

                    $s->encrypt_backups     = (bool) ($data['encrypt_backups'] ?? false);
                    if (!empty($data['encrypt_backups'])) {
                        if (!empty($data['encryption_password'])) {
                            $s->encryption_password = $data['encryption_password'];
                        } elseif (empty($s->encryption_password)) {
                            Notification::make()
                                ->title('Encryption password wajib')
                                ->body('Aktifkan enkripsi membutuhkan "Encryption Password".')
                                ->danger()
                                ->send();

                            $this->halt();
                        }
                    } else {
                        // opsional: kosongkan jika mematikan enkripsi
                        // $s->encryption_password = null;
                    }

                    $s->use_queue            = (bool) ($data['use_queue'] ?? false);
                    $s->queue                = $data['queue'] ?? null;

                    $s->notification_channel = $data['notification_channel'] ?? null;
                    $s->notification_targets = $data['notification_targets'] ?? [];

                    $s->scheduled            = (bool) ($data['scheduled'] ?? false);
                    $s->schedule_cron        = $data['schedule_cron'] ?? null;

                    $s->retention_days       = $data['retention_days'] ?? null;
                    $s->retention_copies     = $data['retention_copies'] ?? null;

                    $s->allowed_disks        = $data['allowed_disks'] ?? [];
                    $s->options              = $data['options'] ?? [];

                    $s->save();

                    Notification::make()
                        ->title('Pengaturan disimpan')
                        ->body('Konfigurasi backup berhasil diperbarui.')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getHeaderActions(): array
    {
        return $this->getActions();
    }

    // === Schema form untuk modal Pengaturan ===
    protected function settingsFormSchema(): array
    {
        $diskOptions = array_keys(config('filesystems.disks', []));

        return [
            Section::make('General')
                ->columns(3)
                ->schema([
                    Toggle::make('enabled')->label('Enabled')->inline(false),
                    Toggle::make('allow_manual_runs')->label('Allow Manual Runs')->inline(false),
                    Toggle::make('require_password')->label('Require Password')->inline(false),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->label('New Password')
                        ->helperText('Kosongkan jika tidak ingin mengganti.')
                        ->visible(fn(Get $get) => (bool) $get('require_password')),
                ]),

            Section::make('Security')
                ->columns(2)
                ->schema([
                    Toggle::make('encrypt_backups')->label('Encrypt Backups'),
                    TextInput::make('encryption_password')
                        ->password()
                        ->revealable()
                        ->label('Encryption Password')
                        ->visible(fn(Get $get) => (bool) $get('encrypt_backups')),
                ]),

            Section::make('Queue & Notifications')
                ->columns(3)
                ->schema([
                    Toggle::make('use_queue')->label('Use Queue'),
                    TextInput::make('queue')
                        ->label('Queue Name')
                        ->placeholder('default')
                        ->columnSpan(2)
                        ->visible(fn(Get $get) => (bool) $get('use_queue')),

                    TextInput::make('notification_channel')
                        ->label('Notification Channel')
                        ->placeholder('mail, slack, database, dsb.'),
                    TagsInput::make('notification_targets')
                        ->label('Notification Targets')
                        ->placeholder('email/username/channel'),
                ]),

            Section::make('Scheduling')
                ->columns(2)
                ->schema([
                    Toggle::make('scheduled')->label('Enable Schedule'),
                    TextInput::make('schedule_cron')
                        ->placeholder('0 3 * * *')
                        ->helperText('Format CRON. Contoh: "0 3 * * *" untuk jam 03:00 setiap hari.')
                        ->visible(fn(Get $get) => (bool) $get('scheduled')),
                ]),

            Section::make('Retention')
                ->columns(2)
                ->schema([
                    TextInput::make('retention_days')->numeric()->minValue(0)->label('Retention (Days)'),
                    TextInput::make('retention_copies')->numeric()->minValue(0)->label('Retention (Copies)'),
                ]),

            Section::make('Scopes')
                ->columns(2)
                ->schema([
                    TagsInput::make('allowed_disks')
                        ->label('Allowed Disks')
                        ->placeholder('ketik nama disk...')
                        ->suggestions($diskOptions),
                ]),

            Section::make('Advanced')
                ->schema([
                    Forms\Components\KeyValue::make('options')
                        ->label('Options (key => value)')
                        ->addButtonLabel('Tambah Opsi')
                        ->reorderable()
                        ->columnSpanFull(),
                ]),
        ];
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
