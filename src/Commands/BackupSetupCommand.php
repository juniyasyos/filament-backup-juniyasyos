<?php

namespace Juniyasyos\FilamentLaravelBackup\Commands;

use Illuminate\Console\Command;

class BackupSetupCommand extends Command
{
    protected $signature = 'backup:setup
        {--F|force : Overwrite any existing published files}
        {--M|migrate : Run database migrations after publishing}
        {--A|assets : Build Filament assets after publishing}';

    protected $description = 'Publish Filament backup assets and prepare your project for use.';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $this->publishPackageAssets($force);

        if ($this->option('migrate')) {
            $this->info('Running database migrations...');
            $this->call('migrate');
        }

        if ($this->option('assets')) {
            $this->info('Building Filament assets...');
            $this->call('filament:assets');
        }

        $this->info('Filament backup package is ready to use.');

        return self::SUCCESS;
    }

    protected function publishPackageAssets(bool $force): void
    {
        $publishes = [
            'filament-spatie-backup-migrations' => 'Publishing migrations',
            'filament-spatie-backup-translations' => 'Publishing translations',
            'filament-spatie-backup-views' => 'Publishing views',
        ];

        foreach ($publishes as $tag => $message) {
            $this->info($message . '...');

            $exitCode = $this->call('vendor:publish', [
                '--provider' => 'Juniyasyos\\FilamentLaravelBackup\\FilamentLaravelBackupServiceProvider',
                '--tag' => $tag,
                '--force' => $force,
            ]);

            if ($exitCode !== self::SUCCESS) {
                $this->warn("Vendor publish command for tag [{$tag}] exited with code {$exitCode}.");
            }
        }
    }
}
