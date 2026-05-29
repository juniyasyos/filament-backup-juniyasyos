<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('backup_settings')) {
            return;
        }

        $columnsToDrop = array_values(array_filter(
            ['options', 'validation_rules', 'is_required', 'meta'],
            fn (string $column) => Schema::hasColumn('backup_settings', $column)
        ));

        if ($columnsToDrop !== []) {
            Schema::table('backup_settings', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('backup_settings')) {
            return;
        }

        Schema::table('backup_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('backup_settings', 'options')) {
                $table->json('options')->nullable()->after('value');
            }

            if (!Schema::hasColumn('backup_settings', 'validation_rules')) {
                $table->json('validation_rules')->nullable()->after('options');
            }

            if (!Schema::hasColumn('backup_settings', 'is_required')) {
                $table->boolean('is_required')->default(false)->after('validation_rules');
            }

            if (!Schema::hasColumn('backup_settings', 'meta')) {
                $table->json('meta')->nullable()->after('sort_order');
            }
        });
    }
};
