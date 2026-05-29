<?php

use Carbon\Carbon;
use Juniyasyos\FilamentLaravelBackup\Models\BackupConfiguration;
use Illuminate\Support\Facades\DB;

uses()->group('unit');

it('calculates schedule intervals for seconds, minutes, and hours', function (int $value, string $unit, int $expectedSeconds): void {
    $configuration = BackupConfiguration::query()->create([
        'schedule_enabled' => true,
        'schedule_backup_type' => 'all',
        'schedule_interval_value' => $value,
        'schedule_interval_unit' => $unit,
        'schedule_last_run_at' => Carbon::now(),
    ]);

    expect((int) $configuration->getScheduleInterval()->totalSeconds)->toBe($expectedSeconds);
})->with([
    '1 second' => [1, 'second', 1],
    '2 seconds' => [2, 'seconds', 2],
    '3 seconds' => [3, 'second', 3],
    '1 minute' => [1, 'minute', 60],
    '2 minutes' => [2, 'minutes', 120],
    '3 minutes' => [3, 'minute', 180],
    '1 hour' => [1, 'hour', 3600],
    '2 hours' => [2, 'hours', 7200],
    '3 hours' => [3, 'hour', 10800],
]);

it('marks scheduled backups as due only after the configured interval has elapsed', function (int $value, string $unit): void {
    Carbon::setTestNow(Carbon::parse('2026-05-30 10:00:00'));

    try {
        DB::table('backup_configuration')->delete();

        $start = Carbon::now();
        $configuration = BackupConfiguration::query()->create([
            'schedule_enabled' => true,
            'schedule_backup_type' => 'all',
            'schedule_interval_value' => $value,
            'schedule_interval_unit' => $unit,
            'schedule_last_run_at' => $start->copy()->sub($value, $unit),
        ]);

        $interval = $configuration->getScheduleInterval();

        $configuration->forceFill([
            'schedule_last_run_at' => Carbon::now()->sub($interval)->addSecond(),
        ])->save();
        $configuration->refresh();

        expect($configuration->isScheduledBackupDue())->toBeFalse();

        $configuration->forceFill([
            'schedule_last_run_at' => Carbon::now()->sub($interval),
        ])->save();
        $configuration->refresh();

        expect($configuration->isScheduledBackupDue())->toBeTrue();
    } finally {
        Carbon::setTestNow();
    }
})->with([
    '1 second' => [1, 'second'],
    '2 seconds' => [2, 'seconds'],
    '3 seconds' => [3, 'second'],
    '1 minute' => [1, 'minute'],
    '2 minutes' => [2, 'minutes'],
    '3 minutes' => [3, 'minute'],
    '1 hour' => [1, 'hour'],
    '2 hours' => [2, 'hours'],
    '3 hours' => [3, 'hour'],
]);
