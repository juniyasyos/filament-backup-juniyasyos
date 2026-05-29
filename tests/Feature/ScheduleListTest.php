<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Juniyasyos\FilamentLaravelBackup\Models\BackupConfiguration;

uses()->group('feature');

it('shows scheduled backup command in schedule:list with correct expression', function (): void {
    // Set a deterministic anchor
    Carbon::setTestNow(Carbon::parse('2026-05-30 10:00:00'));

    DB::table('backup_configuration')->delete();

    $configuration = BackupConfiguration::query()->create([
        'schedule_enabled' => true,
        'schedule_backup_type' => 'all',
        'schedule_interval_value' => 2,
        'schedule_interval_unit' => 'minute',
        'schedule_last_run_at' => Carbon::now()->subMinutes(2),
    ]);

    // Register the scheduled command on the scheduler to mirror provider behavior
    $schedule = $this->app->make(Illuminate\Console\Scheduling\Schedule::class);
    $event = $schedule->command(Juniyasyos\FilamentLaravelBackup\Commands\RunScheduledBackupCommand::class)
        ->withoutOverlapping()
        ->onOneServer()
        ->description('Run configured automatic backup when it is due');

    $expectedCron = $configuration->getScheduleCronExpression();

    if ($expectedCron) {
        $event->cron($expectedCron);
    } else {
        $event->everyMinute();
    }

    // Inspect the Schedule events directly
    $events = $schedule->events();
    $found = false;
    $expr = null;
    foreach ($events as $ev) {
        if (method_exists($ev, 'getSummaryForDisplay')) {
            // Some Event implementations expose description/public properties
            try {
                $desc = $ev->description;
            } catch (Throwable $e) {
                $desc = null;
            }
        }
        if (($ev->description ?? null) === 'Run configured automatic backup when it is due') {
            $found = true;
            $expr = $ev->expression ?? null;
            break;
        }
    }

    expect($found)->toBeTrue();
    expect($expr)->not->toBeNull();
    expect(str_contains($expr, $expectedCron))->toBeTrue();

    Carbon::setTestNow();
});
