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
            if (! Schema::hasColumn('backup_configuration', 'schedule_backup_type')) {
                $table->string('schedule_backup_type')->nullable()->after('schedule_enabled');
            }
        });

        $row = DB::table('backup_configuration')->first();
        if ($row) {
            DB::table('backup_configuration')->where('id', $row->id)->update([
                'schedule_backup_type' => $row->schedule_backup_type ?? 'all',
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
            if (Schema::hasColumn('backup_configuration', 'schedule_backup_type')) {
                $table->dropColumn('schedule_backup_type');
            }
        });
    }
};