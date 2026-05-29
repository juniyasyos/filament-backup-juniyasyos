<?php

namespace Juniyasyos\FilamentLaravelBackup\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Juniyasyos\FilamentLaravelBackup\FilamentLaravelBackupServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        Schema::create('backup_configuration', function (Blueprint $table): void {
            $table->id();
            $table->boolean('schedule_enabled')->default(false);
            $table->integer('timeout')->default(3600);
            $table->string('queue')->default('default');
            $table->string('schedule_backup_type')->nullable();
            $table->integer('schedule_interval_value')->nullable();
            $table->string('schedule_interval_unit')->nullable();
            $table->timestamp('schedule_last_run_at')->nullable();
            $table->timestamps();
        });

        DB::table('backup_configuration')->insert([
            'schedule_enabled' => false,
            'timeout' => 3600,
            'queue' => 'default',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            FilamentLaravelBackupServiceProvider::class,
        ];
    }
}
