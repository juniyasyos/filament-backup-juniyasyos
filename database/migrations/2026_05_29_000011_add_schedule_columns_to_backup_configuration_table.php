<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('backup_configuration')) {
            return;
        }

        Schema::table('backup_configuration', function (Blueprint $table): void {
            if (! Schema::hasColumn('backup_configuration', 'schedule_enabled')) {
                $table->boolean('schedule_enabled')->default(false)->after('encryption_key');
            }

            if (! Schema::hasColumn('backup_configuration', 'schedule_expression')) {
                $table->string('schedule_expression')->nullable()->after('schedule_enabled');
            }

            if (! Schema::hasColumn('backup_configuration', 'schedule_timezone')) {
                $table->string('schedule_timezone')->nullable()->after('schedule_expression');
            }
        });

        $row = Schema::hasTable('backup_configuration') ? \Illuminate\Support\Facades\DB::table('backup_configuration')->first() : null;
        if ($row) {
            \Illuminate\Support\Facades\DB::table('backup_configuration')->where('id', $row->id)->update([
                'schedule_enabled' => $row->schedule_enabled ?? false,
                'schedule_expression' => $row->schedule_expression ?? '0 1 * * *',
                'schedule_timezone' => $row->schedule_timezone ?? config('app.timezone', 'UTC'),
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
            foreach (['schedule_enabled', 'schedule_expression', 'schedule_timezone'] as $column) {
                if (Schema::hasColumn('backup_configuration', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};