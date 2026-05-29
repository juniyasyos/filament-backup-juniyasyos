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
            if (! Schema::hasColumn('backup_configuration', 'minio_bucket')) {
                $table->string('minio_bucket')->nullable()->after('s3_secret');
            }

            if (! Schema::hasColumn('backup_configuration', 'minio_endpoint')) {
                $table->string('minio_endpoint')->nullable()->after('minio_bucket');
            }

            if (! Schema::hasColumn('backup_configuration', 'minio_region')) {
                $table->string('minio_region')->nullable()->after('minio_endpoint');
            }

            if (! Schema::hasColumn('backup_configuration', 'minio_key')) {
                $table->string('minio_key')->nullable()->after('minio_region');
            }

            if (! Schema::hasColumn('backup_configuration', 'minio_secret')) {
                $table->string('minio_secret')->nullable()->after('minio_key');
            }

            if (! Schema::hasColumn('backup_configuration', 'minio_path_style_endpoint')) {
                $table->boolean('minio_path_style_endpoint')->default(true)->after('minio_secret');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('backup_configuration')) {
            return;
        }

        Schema::table('backup_configuration', function (Blueprint $table): void {
            foreach ([
                'minio_bucket',
                'minio_endpoint',
                'minio_region',
                'minio_key',
                'minio_secret',
                'minio_path_style_endpoint',
            ] as $column) {
                if (Schema::hasColumn('backup_configuration', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
