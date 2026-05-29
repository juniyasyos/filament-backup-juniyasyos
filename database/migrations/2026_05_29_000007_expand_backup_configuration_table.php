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

        $columns = [
            'default_disk' => fn (Blueprint $table) => $table->string('default_disk')->nullable()->after('id'),
            'local_path' => fn (Blueprint $table) => $table->string('local_path')->nullable()->after('default_disk'),
            's3_bucket' => fn (Blueprint $table) => $table->string('s3_bucket')->nullable()->after('local_path'),
            's3_region' => fn (Blueprint $table) => $table->string('s3_region')->nullable()->after('s3_bucket'),
            's3_key' => fn (Blueprint $table) => $table->string('s3_key')->nullable()->after('s3_region'),
            's3_secret' => fn (Blueprint $table) => $table->string('s3_secret')->nullable()->after('s3_key'),
            'timeout' => fn (Blueprint $table) => $table->integer('timeout')->nullable()->after('s3_secret'),
            'queue' => fn (Blueprint $table) => $table->string('queue')->nullable()->after('timeout'),
            'cleanup_enabled' => fn (Blueprint $table) => $table->boolean('cleanup_enabled')->default(false)->after('queue'),
            'cleanup_days' => fn (Blueprint $table) => $table->integer('cleanup_days')->nullable()->after('cleanup_enabled'),
            'notifications_enabled' => fn (Blueprint $table) => $table->boolean('notifications_enabled')->default(false)->after('cleanup_days'),
            'on_success' => fn (Blueprint $table) => $table->boolean('on_success')->default(false)->after('notifications_enabled'),
            'on_failure' => fn (Blueprint $table) => $table->boolean('on_failure')->default(false)->after('on_success'),
            'progress_updates' => fn (Blueprint $table) => $table->boolean('progress_updates')->default(false)->after('on_failure'),
            'recipients' => fn (Blueprint $table) => $table->text('recipients')->nullable()->after('progress_updates'),
            'notify_user' => fn (Blueprint $table) => $table->boolean('notify_user')->default(false)->after('recipients'),
            'require_permission' => fn (Blueprint $table) => $table->boolean('require_permission')->default(false)->after('notify_user'),
            'allowed_roles' => fn (Blueprint $table) => $table->string('allowed_roles')->nullable()->after('require_permission'),
            'encrypt_backups' => fn (Blueprint $table) => $table->boolean('encrypt_backups')->default(false)->after('allowed_roles'),
            'encryption_key' => fn (Blueprint $table) => $table->string('encryption_key')->nullable()->after('encrypt_backups'),
        ];

        Schema::table('backup_configuration', function (Blueprint $table) use ($columns): void {
            foreach ($columns as $column => $adder) {
                if (! Schema::hasColumn('backup_configuration', $column)) {
                    $adder($table);
                }
            }
        });

        if (! Schema::hasTable('backup_settings')) {
            return;
        }

        $legacy = DB::table('backup_settings')->get(['key', 'value'])->mapWithKeys(function ($row) {
            return [$row->key => $row->value];
        })->all();

        $defaults = [
            'default_disk' => data_get($legacy, 'backup.storage.default_disk', data_get($legacy, 'backup.backup.storage.default_disk', 'local')),
            'local_path' => data_get($legacy, 'backup.storage.local.path', data_get($legacy, 'backup.backup.storage.local.path', 'storage/app/backup')),
            's3_bucket' => data_get($legacy, 'backup.storage.s3.bucket', data_get($legacy, 'backup.backup.storage.s3.bucket', '')),
            's3_region' => data_get($legacy, 'backup.storage.s3.region', data_get($legacy, 'backup.backup.storage.s3.region', 'us-east-1')),
            's3_key' => data_get($legacy, 'backup.storage.s3.key', data_get($legacy, 'backup.backup.storage.s3.key', '')),
            's3_secret' => data_get($legacy, 'backup.storage.s3.secret', data_get($legacy, 'backup.backup.storage.s3.secret', '')),
            'timeout' => (int) data_get($legacy, 'backup.general.timeout', data_get($legacy, 'backup.backup.general.timeout', 3600)),
            'queue' => data_get($legacy, 'backup.general.queue', data_get($legacy, 'backup.backup.general.queue', 'default')),
            'cleanup_enabled' => (bool) data_get($legacy, 'backup.general.cleanup_enabled', data_get($legacy, 'backup.backup.general.cleanup_enabled', true)),
            'cleanup_days' => (int) data_get($legacy, 'backup.general.cleanup_days', data_get($legacy, 'backup.backup.general.cleanup_days', 30)),
            'notifications_enabled' => (bool) data_get($legacy, 'backup.general.notifications_enabled', data_get($legacy, 'backup.backup.general.notifications_enabled', true)),
            'on_success' => (bool) data_get($legacy, 'backup.notifications.on_success', data_get($legacy, 'backup.backup.notifications.on_success', true)),
            'on_failure' => (bool) data_get($legacy, 'backup.notifications.on_failure', data_get($legacy, 'backup.backup.notifications.on_failure', true)),
            'progress_updates' => (bool) data_get($legacy, 'backup.notifications.progress_updates', data_get($legacy, 'backup.backup.notifications.progress_updates', false)),
            'recipients' => implode("\n", (array) data_get($legacy, 'backup.notifications.recipients', data_get($legacy, 'backup.backup.notifications.recipients', []))),
            'notify_user' => (bool) data_get($legacy, 'backup.notifications.notify_user', data_get($legacy, 'backup.backup.notifications.notify_user', true)),
            'require_permission' => (bool) data_get($legacy, 'backup.security.require_permission', data_get($legacy, 'backup.backup.security.require_permission', true)),
            'allowed_roles' => data_get($legacy, 'backup.security.allowed_roles', data_get($legacy, 'backup.backup.security.allowed_roles', '')),
            'encrypt_backups' => (bool) data_get($legacy, 'backup.security.encrypt_backups', data_get($legacy, 'backup.backup.security.encrypt_backups', false)),
            'encryption_key' => data_get($legacy, 'backup.security.encryption_key', data_get($legacy, 'backup.backup.security.encryption_key', '')),
        ];

        $row = DB::table('backup_configuration')->first();
        if ($row) {
            DB::table('backup_configuration')->where('id', $row->id)->update(array_merge($defaults, [
                'updated_at' => now(),
            ]));
        } else {
            DB::table('backup_configuration')->insert(array_merge($defaults, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('backup_configuration')) {
            return;
        }

        Schema::table('backup_configuration', function (Blueprint $table): void {
            foreach ([
                'default_disk', 'local_path', 's3_bucket', 's3_region', 's3_key', 's3_secret',
                'timeout', 'queue', 'cleanup_enabled', 'cleanup_days', 'notifications_enabled',
                'on_success', 'on_failure', 'progress_updates', 'recipients', 'notify_user',
                'require_permission', 'allowed_roles', 'encrypt_backups', 'encryption_key',
            ] as $column) {
                if (Schema::hasColumn('backup_configuration', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
