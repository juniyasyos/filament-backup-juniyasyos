<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('backup_configuration')) {
            return;
        }

        Schema::table('backup_configuration', function (Blueprint $table): void {
            if (! Schema::hasColumn('backup_configuration', 'schedule_interval_value')) {
                $table->integer('schedule_interval_value')->nullable()->after('schedule_enabled');
            }

            if (! Schema::hasColumn('backup_configuration', 'schedule_interval_unit')) {
                $table->string('schedule_interval_unit')->nullable()->after('schedule_interval_value');
            }

            if (! Schema::hasColumn('backup_configuration', 'schedule_last_run_at')) {
                $table->timestamp('schedule_last_run_at')->nullable()->after('schedule_interval_unit');
            }
        });

        $row = DB::table('backup_configuration')->first();
        if ($row) {
            DB::table('backup_configuration')->where('id', $row->id)->update([
                'schedule_interval_value' => $row->schedule_interval_value ?? 1,
                'schedule_interval_unit' => $row->schedule_interval_unit ?? 'day',
                'schedule_last_run_at' => $row->schedule_last_run_at ?? null,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('backup_configuration')) {
            return;
        }

        Schema::table('backup_configuration', function (Blueprint $table): void {
            foreach (['schedule_interval_value', 'schedule_interval_unit', 'schedule_last_run_at'] as $column) {
                if (Schema::hasColumn('backup_configuration', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};