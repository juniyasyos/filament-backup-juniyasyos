<?php

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Juniyasyos\FilamentLaravelBackup\Models\BackupJob;
use Juniyasyos\FilamentLaravelBackup\Models\BackupLog;
use Juniyasyos\FilamentLaravelBackup\Models\BackupConfiguration;
use Juniyasyos\FilamentLaravelBackup\Services\BackupService;

uses()->group('feature');

beforeEach(function (): void {
    Schema::dropIfExists('backup_logs');
    Schema::dropIfExists('backup_jobs');
    Schema::dropIfExists('backup_configuration');

    $backupJobsMigration = require __DIR__ . '/../../database/migrations/2024_03_01_000002_create_backup_jobs_table.php';
    $backupJobsMigration->up();

    $backupLogsMigration = require __DIR__ . '/../../database/migrations/2024_03_01_000003_create_backup_logs_table.php';
    $backupLogsMigration->up();

    Schema::create('backup_configuration', function (Blueprint $table): void {
        $table->id();
        $table->boolean('schedule_enabled')->default(false);
        $table->string('default_disk')->default('local');
        $table->string('local_path')->default('storage/app/backup');
        $table->integer('timeout')->default(3600);
        $table->string('queue')->default('default');
        $table->boolean('cleanup_enabled')->default(true);
        $table->integer('cleanup_days')->default(30);
        $table->string('schedule_backup_type')->default('all');
        $table->integer('schedule_interval_value')->default(1);
        $table->string('schedule_interval_unit')->default('minute');
        $table->timestamp('schedule_last_run_at')->nullable();
        $table->timestamps();
    });
});

afterEach(function (): void {
    Carbon::setTestNow();

    Schema::dropIfExists('backup_logs');
    Schema::dropIfExists('backup_jobs');
    Schema::dropIfExists('backup_configuration');
});

it('creates a completed backup record when the scheduled backup is due', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-05-30 10:00:00'));

    DB::table('backup_configuration')->insert([
        'schedule_enabled' => true,
        'default_disk' => 'local',
        'local_path' => 'storage/app/backup',
        'timeout' => 3600,
        'queue' => 'default',
        'cleanup_enabled' => true,
        'cleanup_days' => 30,
        'schedule_backup_type' => 'all',
        'schedule_interval_value' => 1,
        'schedule_interval_unit' => 'minute',
        'schedule_last_run_at' => now()->subMinute(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    config(['backup.backup.name' => 'Laravel']);

    $expectedFilename = 'full-backup-2026-05-30-10-00-00.zip';
    $expectedPath = storage_path('app/backup/Laravel/' . $expectedFilename);

    if (! is_dir(dirname($expectedPath))) {
        mkdir(dirname($expectedPath), 0777, true);
    }

    file_put_contents($expectedPath, 'backup zip content');

    $backupService = Mockery::mock(BackupService::class);
    $backupService->shouldReceive('runSpatieBackup')
        ->once()
        ->withArgs(function (array $options): bool {
            return $options['--only-db'] === false
                && $options['--only-files'] === false
                && $options['--filename'] === 'full-backup-2026-05-30-10-00-00.zip';
        }, Mockery::type(BackupJob::class))
        ->andReturn([
            'actual_path' => $expectedPath,
            'output' => 'backup completed',
        ]);

    $this->app->instance(BackupService::class, $backupService);

    $this->artisan('backup:run-scheduled')
        ->expectsOutput('Scheduled backup dispatched to queue.')
        ->assertExitCode(0);

    expect(BackupJob::count())->toBe(1);
    expect(BackupJob::completed()->count())->toBe(1);
    expect(BackupJob::completed()->first()->path)->toBe('backup/Laravel/' . $expectedFilename);
    expect(BackupLog::where('message', 'Backup job created')->exists())->toBeTrue();
    expect(BackupLog::where('message', 'Backup completed successfully')->exists())->toBeTrue();
    expect(BackupLog::where('message', 'Scheduled backup dispatched')->exists())->toBeTrue();
});
