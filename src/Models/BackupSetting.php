<?php

namespace Juniyasyos\FilamentLaravelBackup\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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
 * @property array|null $options
 * @property array|null $validation_rules
 * @property bool $is_active
 * @property bool $is_required
 * @property int $sort_order
 * @property array|null $meta
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
        'options',
        'validation_rules',
        'is_active',
        'is_required',
        'sort_order',
        'meta'
    ];

    protected $casts = [
        'value' => 'json',
        'options' => 'json',
        'validation_rules' => 'json',
        'meta' => 'json',
        'is_active' => 'boolean',
        'is_required' => 'boolean',
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
     * Scope: Required settings only
     */
    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_required', true);
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

                $decoded = is_string($value) ? json_decode($value, true) : $value;

                return match ($this->type) {
                    'boolean' => (bool) $decoded,
                    'integer' => (int) $decoded,
                    'array', 'multiselect' => (array) $decoded,
                    'json' => $decoded,
                    default => $decoded,
                };
            },
            set: function ($value) {
                if ($value === null) {
                    return null;
                }

                // Ensure proper type conversion before JSON encoding
                $processedValue = match ($this->type) {
                    'boolean' => (bool) $value,
                    'integer' => (int) $value,
                    'array', 'multiselect' => is_array($value) ? $value : [$value],
                    'json' => is_array($value) || is_object($value) ? $value : json_decode($value, true),
                    default => $value,
                };

                return json_encode($processedValue);
            }
        );
    }

    /**
     * Get setting value by key with caching
     */
    public static function get(string $key, $default = null)
    {
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
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return false;
        }

        $setting->value = $value;
        $result = $setting->save();

        self::clearCache();

        return $result;
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
                        'options' => $setting->options,
                        'validation_rules' => $setting->validation_rules,
                        'is_required' => $setting->is_required,
                        'meta' => $setting->meta,
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
     * Validate setting value
     */
    public function validateValue($value): array
    {
        $errors = [];

        if ($this->is_required && ($value === null || $value === '')) {
            $errors[] = "The {$this->name} is required.";
        }

        if ($this->validation_rules && !empty($value)) {
            // Here you can implement Laravel validation
            // or custom validation logic based on validation_rules
        }

        return $errors;
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

        // Clear value caches (harder to do efficiently, so we could use tags)
        Cache::flush(); // More aggressive, but ensures clean state
    }

    /**
     * Get form schema for Filament
     */
    public function getFilamentComponent()
    {
        $component = match ($this->type) {
            'boolean' => \Filament\Forms\Components\Toggle::make($this->key),
            'integer' => \Filament\Forms\Components\TextInput::make($this->key)->numeric(),
            'password' => \Filament\Forms\Components\TextInput::make($this->key)->password(),
            'text' => \Filament\Forms\Components\Textarea::make($this->key),
            'select' => \Filament\Forms\Components\Select::make($this->key)->options($this->options ?? []),
            'multiselect' => \Filament\Forms\Components\CheckboxList::make($this->key)->options($this->options ?? []),
            'json', 'array' => \Filament\Forms\Components\KeyValue::make($this->key),
            default => \Filament\Forms\Components\TextInput::make($this->key),
        };

        return $component
            ->label($this->name)
            ->helperText($this->description)
            ->required($this->is_required)
            ->default($this->value);
    }
}
