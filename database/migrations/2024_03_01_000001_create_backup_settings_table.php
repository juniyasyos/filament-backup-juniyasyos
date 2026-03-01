<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('group')->default('general'); // general, storage, notifications, etc
            $table->enum('type', [
                'string',
                'integer',
                'boolean',
                'array',
                'json',
                'select',
                'multiselect',
                'password',
                'text'
            ])->default('string');
            $table->json('value')->nullable(); // Store any type of value as JSON
            $table->json('options')->nullable(); // For select/multiselect options
            $table->json('validation_rules')->nullable(); // Store validation rules as JSON
            $table->boolean('is_active')->default(true);
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('meta')->nullable(); // Additional metadata
            $table->timestamps();

            $table->index(['group', 'is_active']);
            $table->index(['sort_order']);
        });

        // Insert default settings
        $defaultSettings = [
            [
                'key' => 'backup.storage.default_disk',
                'name' => 'Default Storage Disk',
                'description' => 'Primary storage disk for backups',
                'group' => 'storage',
                'type' => 'select',
                'value' => json_encode('local'),
                'options' => json_encode(['local' => 'Local Storage', 's3' => 'Amazon S3', 'gcs' => 'Google Cloud Storage']),
                'is_required' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'backup.storage.local.path',
                'name' => 'Local Storage Path',
                'description' => 'Path where backups are stored locally',
                'group' => 'storage',
                'type' => 'string',
                'value' => json_encode('storage/app/backup'),
                'validation_rules' => json_encode(['required', 'string']),
                'sort_order' => 2,
            ],
            [
                'key' => 'backup.storage.s3.bucket',
                'name' => 'S3 Bucket Name',
                'description' => 'Amazon S3 bucket name for backups',
                'group' => 'storage',
                'type' => 'string',
                'value' => json_encode(''),
                'validation_rules' => json_encode(['nullable', 'string']),
                'sort_order' => 3,
            ],
            [
                'key' => 'backup.storage.s3.region',
                'name' => 'S3 Region',
                'description' => 'Amazon S3 region',
                'group' => 'storage',
                'type' => 'select',
                'value' => json_encode('us-east-1'),
                'options' => json_encode([
                    'us-east-1' => 'US East (N. Virginia)',
                    'us-west-2' => 'US West (Oregon)',
                    'eu-west-1' => 'Europe (Ireland)',
                    'ap-southeast-1' => 'Asia Pacific (Singapore)'
                ]),
                'sort_order' => 4,
            ],
            [
                'key' => 'backup.storage.s3.key',
                'name' => 'S3 Access Key',
                'description' => 'Amazon S3 Access Key ID',
                'group' => 'storage',
                'type' => 'password',
                'value' => json_encode(''),
                'validation_rules' => json_encode(['nullable', 'string']),
                'sort_order' => 5,
            ],
            [
                'key' => 'backup.storage.s3.secret',
                'name' => 'S3 Secret Key',
                'description' => 'Amazon S3 Secret Access Key',
                'group' => 'storage',
                'type' => 'password',
                'value' => json_encode(''),
                'validation_rules' => json_encode(['nullable', 'string']),
                'sort_order' => 6,
            ],
            [
                'key' => 'backup.general.timeout',
                'name' => 'Backup Timeout (seconds)',
                'description' => 'Maximum time allowed for backup process',
                'group' => 'general',
                'type' => 'integer',
                'value' => json_encode(3600),
                'validation_rules' => json_encode(['required', 'integer', 'min:60']),
                'is_required' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'backup.general.queue',
                'name' => 'Queue Name',
                'description' => 'Queue name for backup jobs',
                'group' => 'general',
                'type' => 'string',
                'value' => json_encode('default'),
                'validation_rules' => json_encode(['required', 'string']),
                'is_required' => true,
                'sort_order' => 2,
            ],
            [
                'key' => 'backup.general.cleanup_enabled',
                'name' => 'Auto Cleanup Enabled',
                'description' => 'Automatically cleanup old backups',
                'group' => 'general',
                'type' => 'boolean',
                'value' => json_encode(true),
                'sort_order' => 3,
            ],
            [
                'key' => 'backup.general.cleanup_days',
                'name' => 'Cleanup After Days',
                'description' => 'Delete backups older than X days',
                'group' => 'general',
                'type' => 'integer',
                'value' => json_encode(30),
                'validation_rules' => json_encode(['required', 'integer', 'min:1']),
                'sort_order' => 4,
            ],
        ];

        foreach ($defaultSettings as $setting) {
            DB::table('backup_settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_settings');
    }
};
