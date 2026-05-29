<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('backup_configuration')) {
            return;
        }

        Schema::create('backup_configuration', function (Blueprint $table) {
            $table->id();
            $table->string('default_disk')->nullable();
            $table->string('local_path')->nullable();
            $table->string('s3_bucket')->nullable();
            $table->string('s3_region')->nullable();
            $table->string('s3_key')->nullable();
            $table->string('s3_secret')->nullable();
            $table->integer('timeout')->nullable();
            $table->string('queue')->nullable();
            $table->boolean('cleanup_enabled')->default(false);
            $table->integer('cleanup_days')->nullable();
            $table->timestamps();
        });

        if (! Schema::hasTable('backup_settings')) {
            return;
        }

        // Map keys from key-value settings to columns
        $mapping = [
            'backup.storage.default_disk' => 'default_disk',
            'backup.storage.local.path' => 'local_path',
            'backup.storage.s3.bucket' => 's3_bucket',
            'backup.storage.s3.region' => 's3_region',
            'backup.storage.s3.key' => 's3_key',
            'backup.storage.s3.secret' => 's3_secret',
            'backup.general.timeout' => 'timeout',
            'backup.general.queue' => 'queue',
            'backup.general.cleanup_enabled' => 'cleanup_enabled',
            'backup.general.cleanup_days' => 'cleanup_days',
        ];

        $rows = DB::table('backup_settings')
            ->whereIn('key', array_keys($mapping))
            ->get(['key', 'value']);

        $values = [];
        foreach ($rows as $row) {
            $v = $row->value;
            $decoded = null;
            if (is_string($v)) {
                $decoded = json_decode($v, true);
            }

            $values[$row->key] = $decoded !== null && json_last_error() === JSON_ERROR_NONE
                ? $decoded
                : $v;
        }

        $insert = [];
        foreach ($mapping as $key => $col) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            $val = $values[$key];

            // cast types for known columns
            if (in_array($col, ['timeout', 'cleanup_days'], true)) {
                $insert[$col] = is_numeric($val) ? (int) $val : null;
                continue;
            }

            if ($col === 'cleanup_enabled') {
                if (is_bool($val)) {
                    $insert[$col] = $val;
                } elseif (is_string($val)) {
                    $lower = strtolower($val);
                    $insert[$col] = in_array($lower, ['1', 'true', 'yes', 'on'], true);
                } else {
                    $insert[$col] = (bool) $val;
                }
                continue;
            }

            $insert[$col] = $val === null ? null : (string) $val;
        }

        // Insert a single row with migrated values (or defaults)
        DB::table('backup_configuration')->insert(array_merge([
            'default_disk' => null,
            'local_path' => null,
            's3_bucket' => null,
            's3_region' => null,
            's3_key' => null,
            's3_secret' => null,
            'timeout' => null,
            'queue' => null,
            'cleanup_enabled' => false,
            'cleanup_days' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $insert));
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_configuration');
    }
};
