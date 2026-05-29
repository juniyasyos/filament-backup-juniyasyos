<?php

namespace Juniyasyos\FilamentLaravelBackup\Models;

use Carbon\CarbonInterval;
use Cron\CronExpression;
use Illuminate\Database\Eloquent\Model;

class BackupConfiguration extends Model
{
    protected $table = 'backup_configuration';

    protected $fillable = [
        'default_disk',
        'local_path',
        's3_bucket',
        's3_region',
        's3_key',
        's3_secret',
        'minio_bucket',
        'minio_endpoint',
        'minio_key',
        'minio_secret',
        'minio_path_style_endpoint',
        'timeout',
        'queue',
        'cleanup_enabled',
        'cleanup_days',
        'notifications_enabled',
        'on_success',
        'on_failure',
        'progress_updates',
        'recipients',
        'notify_user',
        'require_permission',
        'allowed_roles',
        'encrypt_backups',
        'encryption_key',
        'schedule_enabled',
        'schedule_interval_value',
        'schedule_interval_unit',
        'schedule_last_run_at',
    ];

    protected $casts = [
        'default_disk' => 'string',
        'local_path' => 'string',
        's3_bucket' => 'string',
        's3_region' => 'string',
        's3_key' => 'string',
        's3_secret' => 'string',
        'minio_bucket' => 'string',
        'minio_endpoint' => 'string',
        'minio_key' => 'string',
        'minio_secret' => 'string',
        'minio_path_style_endpoint' => 'boolean',
        'timeout' => 'integer',
        'queue' => 'string',
        'cleanup_enabled' => 'boolean',
        'cleanup_days' => 'integer',
        'notifications_enabled' => 'boolean',
        'on_success' => 'boolean',
        'on_failure' => 'boolean',
        'progress_updates' => 'boolean',
        'recipients' => 'string',
        'notify_user' => 'boolean',
        'require_permission' => 'boolean',
        'allowed_roles' => 'string',
        'encrypt_backups' => 'boolean',
        'encryption_key' => 'string',
        'schedule_enabled' => 'boolean',
        'schedule_interval_value' => 'integer',
        'schedule_interval_unit' => 'string',
        'schedule_last_run_at' => 'datetime',
    ];

    public $timestamps = true;

    public static function getRow(): ?self
    {
        try {
            return self::query()->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function defaults(): array
    {
        return [
            'default_disk' => 'local',
            'local_path' => 'storage/app/backup',
            's3_bucket' => '',
            's3_region' => 'us-east-1',
            's3_key' => '',
            's3_secret' => '',
            'minio_bucket' => '',
            'minio_endpoint' => '',
            'minio_key' => '',
            'minio_secret' => '',
            'minio_path_style_endpoint' => true,
            'timeout' => 3600,
            'queue' => 'default',
            'cleanup_enabled' => true,
            'cleanup_days' => 30,
            'notifications_enabled' => true,
            'on_success' => true,
            'on_failure' => true,
            'progress_updates' => false,
            'recipients' => '',
            'notify_user' => true,
            'require_permission' => true,
            'allowed_roles' => '',
            'encrypt_backups' => false,
            'encryption_key' => '',
            'schedule_enabled' => false,
            'schedule_interval_value' => 1,
            'schedule_interval_unit' => 'day',
            'schedule_last_run_at' => null,
        ];
    }

    public static function ensureDefaults(): self
    {
        $row = self::query()->first();

        if ($row) {
            return $row;
        }

        return self::create(self::defaults());
    }

    public static function fromNestedFormState(array $state): array
    {
        $general = data_get($state, 'generalSettings.backup.general', []);
        $storage = data_get($state, 'storageSettings.backup.storage', []);
        $notifications = data_get($state, 'notificationSettings.backup.notifications', []);
        $security = data_get($state, 'securitySettings.backup.security', []);
        $schedule = data_get($state, 'scheduleSettings.backup.schedule', []);

        $recipients = data_get($notifications, 'recipients', []);
        if (is_array($recipients)) {
            $recipients = array_values(array_filter(array_map(function ($row) {
                if (is_array($row)) {
                    return trim((string) ($row['email'] ?? ''));
                }

                return trim((string) $row);
            }, $recipients)));
        } else {
            $recipients = [];
        }

        return [
            'default_disk' => data_get($storage, 'default_disk', 'local'),
            'local_path' => data_get($storage, 'local.path', 'storage/app/backup'),
            's3_bucket' => data_get($storage, 's3.bucket', ''),
            's3_region' => data_get($storage, 's3.region', 'us-east-1'),
            's3_key' => data_get($storage, 's3.key', ''),
            's3_secret' => data_get($storage, 's3.secret', ''),
            'minio_bucket' => data_get($storage, 'minio.bucket', ''),
            'minio_endpoint' => data_get($storage, 'minio.endpoint', ''),
            'minio_key' => data_get($storage, 'minio.key', ''),
            'minio_secret' => data_get($storage, 'minio.secret', ''),
            'minio_path_style_endpoint' => (bool) data_get($storage, 'minio.path_style_endpoint', true),
            'timeout' => (int) data_get($general, 'timeout', 3600),
            'queue' => data_get($general, 'queue', 'default'),
            'cleanup_enabled' => (bool) data_get($general, 'cleanup_enabled', true),
            'cleanup_days' => data_get($general, 'cleanup_days', 30),
            'notifications_enabled' => (bool) data_get($general, 'notifications_enabled', true),
            'on_success' => (bool) data_get($notifications, 'on_success', true),
            'on_failure' => (bool) data_get($notifications, 'on_failure', true),
            'progress_updates' => (bool) data_get($notifications, 'progress_updates', false),
            'recipients' => implode("\n", $recipients),
            'notify_user' => (bool) data_get($notifications, 'notify_user', true),
            'require_permission' => (bool) data_get($security, 'require_permission', true),
            'allowed_roles' => data_get($security, 'allowed_roles', ''),
            'encrypt_backups' => (bool) data_get($security, 'encrypt_backups', false),
            'encryption_key' => data_get($security, 'encryption_key', ''),
            'schedule_enabled' => (bool) data_get($schedule, 'enabled', false),
            'schedule_interval_value' => max(1, (int) data_get($schedule, 'interval_value', 1)),
            'schedule_interval_unit' => data_get($schedule, 'interval_unit', 'day'),
        ];
    }

    public static function toNestedFormState(?self $row = null): array
    {
        $row ??= self::getRow() ?? new self(self::defaults());

        $recipients = [];
        $rawRecipients = (string) ($row->recipients ?? '');
        foreach (preg_split('/\r?\n/', $rawRecipients) ?: [] as $email) {
            $email = trim($email);
            if ($email !== '') {
                $recipients[] = ['email' => $email];
            }
        }

        return [
            'generalSettings' => [
                'backup' => [
                    'general' => [
                        'timeout' => $row->timeout ?? 3600,
                        'queue' => $row->queue ?? 'default',
                        'cleanup_enabled' => (bool) ($row->cleanup_enabled ?? true),
                        'cleanup_days' => $row->cleanup_days ?? 30,
                        'notifications_enabled' => (bool) ($row->notifications_enabled ?? true),
                    ],
                ],
            ],
            'storageSettings' => [
                'backup' => [
                    'storage' => [
                        'default_disk' => $row->default_disk ?? 'local',
                        'local' => [
                            'path' => $row->local_path ?? 'storage/app/backup',
                        ],
                        's3' => [
                            'bucket' => $row->s3_bucket ?? '',
                            'region' => $row->s3_region ?? 'us-east-1',
                            'key' => $row->s3_key ?? '',
                            'secret' => $row->s3_secret ?? '',
                        ],
                        'minio' => [
                            'bucket' => $row->minio_bucket ?? '',
                            'endpoint' => $row->minio_endpoint ?? '',
                            'key' => $row->minio_key ?? '',
                            'secret' => $row->minio_secret ?? '',
                            'path_style_endpoint' => (bool) ($row->minio_path_style_endpoint ?? true),
                        ],
                    ],
                ],
            ],
            'notificationSettings' => [
                'backup' => [
                    'notifications' => [
                        'on_success' => (bool) ($row->on_success ?? true),
                        'on_failure' => (bool) ($row->on_failure ?? true),
                        'progress_updates' => (bool) ($row->progress_updates ?? false),
                        'recipients' => $recipients,
                        'notify_user' => (bool) ($row->notify_user ?? true),
                    ],
                ],
            ],
            'securitySettings' => [
                'backup' => [
                    'security' => [
                        'require_permission' => (bool) ($row->require_permission ?? true),
                        'allowed_roles' => $row->allowed_roles ?? '',
                        'encrypt_backups' => (bool) ($row->encrypt_backups ?? false),
                        'encryption_key' => $row->encryption_key ?? '',
                    ],
                ],
            ],
            'scheduleSettings' => [
                'backup' => [
                    'schedule' => [
                        'enabled' => (bool) ($row->schedule_enabled ?? false),
                        'interval_value' => $row->schedule_interval_value ?? 1,
                        'interval_unit' => $row->schedule_interval_unit ?? 'day',
                    ],
                ],
            ],
        ];
    }

    public function isScheduledBackupDue(?\DateTimeInterface $moment = null): bool
    {
        if (! $this->schedule_enabled) {
            return false;
        }

        try {
            $interval = $this->getScheduleInterval();
            if (! $interval && ! empty($this->schedule_expression)) {
                $cron = CronExpression::factory(trim((string) $this->schedule_expression));
                $dateTime = $moment instanceof \DateTimeInterface ? $moment : now();

                return $cron->isDue($dateTime);
            }

            if (! $interval) {
                return false;
            }

            $dateTime = $moment instanceof \DateTimeInterface ? now()->setTimestamp($moment->getTimestamp()) : now();
            $lastRunAt = $this->schedule_last_run_at;

            if (! $lastRunAt instanceof \DateTimeInterface) {
                return true;
            }

            return $dateTime->greaterThanOrEqualTo($lastRunAt->copy()->add($interval));
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getScheduleInterval(): ?CarbonInterval
    {
        if ($this->schedule_interval_value === null && empty($this->schedule_interval_unit)) {
            return null;
        }

        $value = max(1, (int) ($this->schedule_interval_value ?? 1));
        $unit = strtolower(trim((string) ($this->schedule_interval_unit ?? 'day')));

        return match ($unit) {
            'second', 'seconds' => CarbonInterval::seconds($value),
            'minute', 'minutes' => CarbonInterval::minutes($value),
            'hour', 'hours' => CarbonInterval::hours($value),
            'day', 'days' => CarbonInterval::days($value),
            'month', 'months' => CarbonInterval::months($value),
            default => CarbonInterval::days($value),
        };
    }

    public static function saveNestedFormState(array $state): bool
    {
        $payload = array_merge(self::defaults(), self::fromNestedFormState($state));
        $row = self::getRow();

        if (! empty($payload['schedule_enabled']) && empty($payload['schedule_last_run_at'])) {
            $payload['schedule_last_run_at'] = now();
        }

        if ($row) {
            return (bool) $row->fill($payload)->save();
        }

        return (bool) self::create($payload);
    }
}
