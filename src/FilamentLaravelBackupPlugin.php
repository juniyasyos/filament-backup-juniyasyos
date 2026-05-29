<?php

namespace Juniyasyos\FilamentLaravelBackup;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Juniyasyos\FilamentLaravelBackup\Pages\Backups;
use Juniyasyos\FilamentLaravelBackup\Pages\BackupSettings;
use Juniyasyos\FilamentLaravelBackup\Pages\BackupConfiguration;

class FilamentLaravelBackupPlugin implements Plugin
{
    use EvaluatesClosures;

    protected bool | \Closure $authorizeUsing = true;

    protected string $page = Backups::class;

    protected ?string $queue = null;

    protected string $interval = '4s';

    protected bool $hasStatusListRecordsTable = true;

    protected ?int $timeout = null;

    protected bool $includeSettingsPage = true;

    public function register(Panel $panel): void
    {
        $pages = [$this->getPage()];

        if ($this->includeSettingsPage) {
            $pages[] = BackupSettings::class;
            $pages[] = BackupConfiguration::class;
        }

        $panel->pages($pages);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public function authorize(bool | \Closure $callback = true): static
    {
        $this->authorizeUsing = $callback;

        return $this;
    }

    public function isAuthorized(): bool
    {
        return $this->evaluate($this->authorizeUsing) === true;
    }

    public static function get(): static
    {
        /** @var static $instance */
        $instance = filament(app(static::class)->getId());

        return $instance;
    }

    public function getId(): string
    {
        return 'filament-spatie-backup';
    }

    public static function make(): static
    {
        return new static;
    }

    public function usingPage(string $page): static
    {
        $this->page = $page;

        return $this;
    }

    public function getPage(): string
    {
        return $this->page;
    }

    public function withoutSettingsPage(): static
    {
        $this->includeSettingsPage = false;

        return $this;
    }

    public function withSettingsPage(): static
    {
        $this->includeSettingsPage = true;

        return $this;
    }

    public function usingQueue(string $queue): static
    {
        $this->queue = $queue;

        return $this;
    }

    public function getQueue(): ?string
    {
        return $this->queue;
    }

    public function usingPolingInterval(string $interval): static
    {
        $this->interval = $interval;

        return $this;
    }

    public function getPolingInterval(): string
    {
        return $this->interval;
    }

    public function usingTimeout(int $timeout): static
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

    public function withStatusListRecordsTable(): static
    {
        $this->hasStatusListRecordsTable = true;

        return $this;
    }

    public function withoutStatusListRecordsTable(): static
    {
        $this->hasStatusListRecordsTable = false;

        return $this;
    }

    public function hasStatusListRecordsTable(): bool
    {
        return $this->hasStatusListRecordsTable;
    }
}
