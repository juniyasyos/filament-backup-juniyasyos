<?php

namespace Juniyasyos\FilamentLaravelBackup\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Juniyasyos\FilamentLaravelBackup\Models\BackupConfiguration;
use Illuminate\Support\Facades\DB;

/**
 * Backup Setting Model
 * 
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string|null $description
 * @property string $group
 * @property string $type
 * @property mixed $value
 * @property bool $is_active
 * @property int $sort_order
 */
class BackupSetting extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'group',
        'type',
        'value',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'value' => 'json',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Cache keys
    const CACHE_PREFIX = 'backup_settings';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Boot model events
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            self::clearCache();
        });

        static::deleted(function () {
            self::clearCache();
        });
    }

    /**
     * Scope: Active settings only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By group
     */
    public function scopeByGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    /**
     * Scope: Ordered by sort order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get typed value attribute
     */
    protected function value(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value === null) {
                    return null;
                }

                return is_string($value) ? json_decode($value, true) : $value;
            },
            set: function ($value) {
                if ($value === null) {
                    return null;
                }

                return json_encode($value);
            }
        );
    }

    /**
     * Get setting value by key with caching
     */
    public static function get(string $key, $default = null)
    {
        // If the new single-row configuration table exists, prefer its values
        if (Schema::hasTable('backup_configuration')) {
            $map = [
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

            if (isset($map[$key])) {
                try {
                    $row = BackupConfiguration::getRow();
                    if ($row && isset($row->{$map[$key]})) {
                        return $row->{$map[$key]} === null ? $default : $row->{$map[$key]};
                    }
                } catch (\Exception $e) {
                    // ignore and fallback to legacy source
                }
            }
        }

        $cacheKey = self::CACHE_PREFIX . '.values.' . md5($key);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            $setting = self::where('key', $key)->where('is_active', true)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set setting value by key
     */
    public static function set(string $key, $value): bool
    {
        // If new configuration table exists and key maps to a column, update that instead
        if (Schema::hasTable('backup_configuration')) {
            $map = [
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

            if (isset($map[$key])) {
                try {
                    $col = $map[$key];
                    $row = BackupConfiguration::getRow();
                    if ($row) {
                        $row->fill([$col => $value]);
                        $row->save();
                    } else {
                        BackupConfiguration::create([
                            $col => $value,
                        ]);
                    }
                    self::clearCache();
                    return true;
                } catch (\Exception $e) {
                    // fallback to legacy behavior
                }
            }
        }

        $setting = self::where('key', $key)->first();

        if (!$setting) {
            try {
                $setting = self::create([
                    'key' => $key,
                    'name' => self::humanizeKey($key),
                    'group' => explode('.', $key)[1] ?? 'general',
                    'type' => self::inferType($value),
                    'value' => $value,
                    'is_active' => true,
                ]);
                self::clearCache();
                return (bool) $setting;
            } catch (\Exception $e) {
                return false;
            }
        }

        $setting->value = $value;
        $result = $setting->save();

        self::clearCache();

        return $result;
    }

    public static function ensureDefaults(array $defaults): void
    {
        foreach ($defaults as $setting) {
            self::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    /**
     * Get all settings by group with caching
     */
    public static function getByGroup(string $group): Collection
    {
        $cacheKey = self::CACHE_PREFIX . '.group.' . $group;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($group) {
            return self::active()
                ->byGroup($group)
                ->ordered()
                ->get()
                ->keyBy('key')
                ->map(function ($setting) {
                    return [
                        'value' => $setting->value,
                        'type' => $setting->type,
                        'name' => $setting->name,
                        'description' => $setting->description,
                        'sort_order' => $setting->sort_order,
                        'is_active' => $setting->is_active,
                    ];
                });
        });
    }

    /**
     * Get all settings as key-value pairs
     */
    public static function getAll(): array
    {
        $cacheKey = self::CACHE_PREFIX . '.all';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return self::active()
                ->pluck('value', 'key')
                ->map(function ($value, $key) {
                    $setting = self::where('key', $key)->first();
                    return $setting ? $setting->value : $value;
                })
                ->toArray();
        });
    }

    /**
     * Bulk update settings
     */
    public static function updateMany(array $settings): bool
    {
        try {
            foreach ($settings as $key => $value) {
                self::set($key, $value);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get storage configuration
     */
    public static function getStorageConfig(): array
    {
        // If new configuration table exists, use its single-row values
        if (Schema::hasTable('backup_configuration')) {
            try {
                $row = \DB::table('backup_configuration')->first();
                if ($row) {
                    $config = [
                        'default_disk' => $row->default_disk ?? 'local',
                        'disks' => [],
                    ];

                    $config['disks']['local'] = [
                        'driver' => 'local',
                        'root' => $row->local_path ?? storage_path('app/backup'),
                    ];

                    if (!empty($row->s3_bucket)) {
                        $config['disks']['s3'] = [
                            'driver' => 's3',
                            'key' => $row->s3_key ?? '',
                            'secret' => $row->s3_secret ?? '',
                            'region' => $row->s3_region ?? 'us-east-1',
                            'bucket' => $row->s3_bucket,
                        ];
                    }

                    return $config;
                }
            } catch (\Exception $e) {
                // fallback to legacy source
            }
        }

        $settings = self::getByGroup('storage');

        $config = [
            'default_disk' => $settings['backup.storage.default_disk']['value'] ?? 'local',
            'disks' => []
        ];

        // Local storage config
        $config['disks']['local'] = [
            'driver' => 'local',
            'root' => $settings['backup.storage.local.path']['value'] ?? storage_path('app/backup'),
        ];

        // S3 storage config
        if (!empty($settings['backup.storage.s3.bucket']['value'])) {
            $config['disks']['s3'] = [
                'driver' => 's3',
                'key' => $settings['backup.storage.s3.key']['value'] ?? '',
                'secret' => $settings['backup.storage.s3.secret']['value'] ?? '',
                'region' => $settings['backup.storage.s3.region']['value'] ?? 'us-east-1',
                'bucket' => $settings['backup.storage.s3.bucket']['value'],
            ];
        }

        return $config;
    }

    /**
     * Clear all caches
     */
    public static function clearCache(): void
    {
        $keys = [
            self::CACHE_PREFIX . '.all',
            self::CACHE_PREFIX . '.group.storage',
            self::CACHE_PREFIX . '.group.general',
            self::CACHE_PREFIX . '.group.notifications',
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        // Clear per-key cached values to avoid flushing entire application cache
        try {
            $allKeys = self::pluck('key')->toArray();
            foreach ($allKeys as $k) {
                Cache::forget(self::CACHE_PREFIX . '.values.' . md5($k));
            }
        } catch (\Exception $e) {
            // If something goes wrong (e.g. DB not available), avoid throwing during cache clear
        }
    }

    protected static function inferType($value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_array($value), is_object($value) => 'json',
            default => 'string',
        };
    }

    protected static function humanizeKey(string $key): string
    {
        return str($key)
            ->afterLast('.')
            ->replace(['_', '-'], ' ')
            ->headline()
            ->toString();
    }
}
