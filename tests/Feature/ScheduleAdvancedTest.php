<?php

use Carbon\Carbon;
use Juniyasyos\FilamentLaravelBackup\Models\BackupConfiguration;
use Illuminate\Support\Facades\DB;

uses()->group('feature');

$units = ['second', 'minute', 'hour', 'day', 'month'];
$values = [1, 2, 3];

foreach ($units as $unit) {
    foreach ($values as $value) {
        it(sprintf('schedules correctly for %d %s(s)', $value, $unit), function () use ($unit, $value): void {
            // Pick two anchors to cover normal and month-end edge cases
            $anchors = [
                Carbon::parse('2026-05-30 10:00:00'),
                Carbon::parse('2026-01-31 23:59:30'),
            ];

            foreach ($anchors as $anchor) {
                Carbon::setTestNow($anchor);

                DB::table('backup_configuration')->delete();

                $configuration = BackupConfiguration::query()->create([
                    'schedule_enabled' => true,
                    'schedule_backup_type' => 'all',
                    'schedule_interval_value' => $value,
                    'schedule_interval_unit' => $unit,
                    'schedule_last_run_at' => $anchor->copy(),
                ]);

                $interval = $configuration->getScheduleInterval();
                $expectedCron = $unit === 'minute'
                    ? sprintf('*/%d * * * *', $value)
                    : sprintf(
                        '%d %d %d %d *',
                        $anchor->copy()->add($interval)->startOfMinute()->minute,
                        $anchor->copy()->add($interval)->startOfMinute()->hour,
                        $anchor->copy()->add($interval)->startOfMinute()->day,
                        $anchor->copy()->add($interval)->startOfMinute()->month,
                    );

                // cron expression
                $cron = $configuration->getScheduleCronExpression();
                expect($cron)->toBe($expectedCron);

                // due checks use exact interval arithmetic (no startOfMinute)
                $dueAt = $anchor->copy()->add($interval);

                Carbon::setTestNow($dueAt->copy()->subSecond());
                expect($configuration->isScheduledBackupDue())->toBeFalse();

                Carbon::setTestNow($dueAt);
                expect($configuration->isScheduledBackupDue())->toBeTrue();

                // cleanup test time
                Carbon::setTestNow();
            }
        });
    }
}
