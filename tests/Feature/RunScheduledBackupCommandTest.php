<?php

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Juniyasyos\FilamentLaravelBackup\Jobs\ImprovedBackupJob;
use Juniyasyos\FilamentLaravelBackup\Enums\Option;

beforeEach(function (): void {
    Schema::dropIfExists('backup_configuration');

    Schema::create('backup_configuration', function (Blueprint $table): void {
        $table->id();
        $table->boolean('schedule_enabled')->default(false);
        $table->integer('timeout')->default(3600);
        $table->string('queue')->default('default');
        $table->string('schedule_backup_type')->default('all');
        $table->integer('schedule_interval_value')->default(1);
        $table->string('schedule_interval_unit')->default('day');
        $table->timestamp('schedule_last_run_at')->nullable();
        $table->timestamps();
    });
});

afterEach(function (): void {
    Carbon::setTestNow();
    Schema::dropIfExists('backup_configuration');
    Mockery::close();
});

uses()->group('feature');

it('dispatches the backup job once the scheduled interval is reached', function (): void {
    DB::table('backup_configuration')->insert([
        'schedule_enabled' => true,
        'timeout' => 3600,
        'queue' => 'default',
        'schedule_backup_type' => 'all',
        'schedule_interval_value' => 2,
        'schedule_interval_unit' => 'minute',
        'schedule_last_run_at' => now()->subMinutes(2),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Bus::fake();

    Mockery::mock('alias:Juniyasyos\\FilamentLaravelBackup\\Models\\BackupLog')
        ->shouldReceive('logInfo')
        ->once()
        ->with(
            'Scheduled backup dispatched',
            Mockery::on(function (array $context): bool {
                return $context['backup_type'] === 'all'
                    && $context['interval_value'] === 2
                    && $context['interval_unit'] === 'minute'
                    && $context['forced'] === false;
            })
        )
        ->andReturnNull();

    $this->artisan('backup:run-scheduled')
        ->expectsOutput('Scheduled backup dispatched to queue.')
        ->assertExitCode(0);

    Bus::assertDispatched(ImprovedBackupJob::class, function (ImprovedBackupJob $job): bool {
        $reflection = new ReflectionClass($job);

        $optionProperty = $reflection->getProperty('option');
        $optionProperty->setAccessible(true);

        $additionalOptionsProperty = $reflection->getProperty('additionalOptions');
        $additionalOptionsProperty->setAccessible(true);

        $additionalOptions = $additionalOptionsProperty->getValue($job);

        return $optionProperty->getValue($job) === Option::ALL
            && $additionalOptions['initiated_via'] === 'scheduler'
            && $additionalOptions['schedule_backup_type'] === 'all'
            && $additionalOptions['schedule_interval_value'] === 2
            && $additionalOptions['schedule_interval_unit'] === 'minute'
            && $additionalOptions['schedule_forced'] === false;
    });

    Carbon::setTestNow();
});
