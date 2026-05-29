<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_configuration', function (Blueprint $table): void {
            if (Schema::hasColumn('backup_configuration', 'minio_region')) {
                $table->dropColumn('minio_region');
            }
        });
    }

    public function down(): void
    {
        Schema::table('backup_configuration', function (Blueprint $table): void {
            if (! Schema::hasColumn('backup_configuration', 'minio_region')) {
                $table->string('minio_region')->nullable()->after('minio_endpoint');
            }
        });
    }
};