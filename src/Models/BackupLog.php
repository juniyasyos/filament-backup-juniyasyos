<?php

namespace Juniyasyos\FilamentLaravelBackup\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Backup Log Model
 * 
 * @property int $id
 * @property int|null $backup_job_id
 * @property string|null $session_id
 * @property string $level
 * @property string $category
 * @property string $event
 * @property string $message
 * @property string|null $description
 * @property array|null $context
 * @property array|null $metadata
 * @property float|null $execution_time
 * @property int|null $memory_usage
 * @property int|null $files_processed
 * @property int|null $bytes_processed
 * @property string|null $error_code
 * @property string|null $error_stack
 * @property array|null $error_context
 * @property string|null $source_class
 * @property string|null $source_method
 * @property int|null $source_line
 * @property string|null $php_version
 * @property string|null $laravel_version
 * @property string|null $environment
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class BackupLog extends Model
{
    protected $fillable = [
        'backup_job_id',
        'session_id',
        'level',
        'category',
        'event',
        'message',
        'description',
        'context',
        'metadata',
        'execution_time',
        'memory_usage',
        'files_processed',
        'bytes_processed',
        'error_code',
        'error_stack',
        'error_context',
        'source_class',
        'source_method',
        'source_line',
        'php_version',
        'laravel_version',
        'environment'
    ];

    protected $casts = [
        'context' => 'json',
        'metadata' => 'json',
        'error_context' => 'json',
        'execution_time' => 'decimal:4',
        'memory_usage' => 'integer',
        'files_processed' => 'integer',
        'bytes_processed' => 'integer',
        'source_line' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Log levels
    const LEVEL_EMERGENCY = 'emergency';
    const LEVEL_ALERT = 'alert';
    const LEVEL_CRITICAL = 'critical';
    const LEVEL_ERROR = 'error';
    const LEVEL_WARNING = 'warning';
    const LEVEL_NOTICE = 'notice';
    const LEVEL_INFO = 'info';
    const LEVEL_DEBUG = 'debug';

    // Categories
    const CATEGORY_BACKUP = 'backup';
    const CATEGORY_STORAGE = 'storage';
    const CATEGORY_VALIDATION = 'validation';
    const CATEGORY_CLEANUP = 'cleanup';
    const CATEGORY_NOTIFICATION = 'notification';
    const CATEGORY_SYSTEM = 'system';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Auto-fill system information if not provided
            if (empty($model->php_version)) {
                $model->php_version = PHP_VERSION;
            }

            if (empty($model->laravel_version)) {
                $model->laravel_version = app()->version();
            }

            if (empty($model->environment)) {
                $model->environment = app()->environment();
            }

            if (empty($model->memory_usage)) {
                $model->memory_usage = memory_get_usage(true);
            }
        });
    }

    /**
     * Relations
     */
    public function backupJob(): BelongsTo
    {
        return $this->belongsTo(BackupJob::class);
    }

    /**
     * Scopes
     */
    public function scopeLevel(Builder $query, string $level): Builder
    {
        return $query->where('level', $level);
    }

    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    public function scopeSession(Builder $query, string $sessionId): Builder
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeErrors(Builder $query): Builder
    {
        return $query->whereIn('level', [
            self::LEVEL_EMERGENCY,
            self::LEVEL_ALERT,
            self::LEVEL_CRITICAL,
            self::LEVEL_ERROR
        ]);
    }

    public function scopeWarnings(Builder $query): Builder
    {
        return $query->where('level', self::LEVEL_WARNING);
    }

    public function scopeInfo(Builder $query): Builder
    {
        return $query->whereIn('level', [
            self::LEVEL_INFO,
            self::LEVEL_NOTICE
        ]);
    }

    public function scopeDebug(Builder $query): Builder
    {
        return $query->where('level', self::LEVEL_DEBUG);
    }

    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeForJob(Builder $query, BackupJob $job): Builder
    {
        return $query->where('backup_job_id', $job->id);
    }

    public function scopeOrderedByTime(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('created_at', $direction);
    }

    /**
     * Level checks
     */
    public function isError(): bool
    {
        return in_array($this->level, [
            self::LEVEL_EMERGENCY,
            self::LEVEL_ALERT,
            self::LEVEL_CRITICAL,
            self::LEVEL_ERROR
        ]);
    }

    public function isWarning(): bool
    {
        return $this->level === self::LEVEL_WARNING;
    }

    public function isInfo(): bool
    {
        return in_array($this->level, [
            self::LEVEL_INFO,
            self::LEVEL_NOTICE
        ]);
    }

    public function isDebug(): bool
    {
        return $this->level === self::LEVEL_DEBUG;
    }

    /**
     * Static logging methods
     */
    public static function logError(string $message, array $context = [], ?BackupJob $job = null): self
    {
        return self::createLog(self::LEVEL_ERROR, $message, $context, $job);
    }

    public static function logWarning(string $message, array $context = [], ?BackupJob $job = null): self
    {
        return self::createLog(self::LEVEL_WARNING, $message, $context, $job);
    }

    public static function logInfo(string $message, array $context = [], ?BackupJob $job = null): self
    {
        return self::createLog(self::LEVEL_INFO, $message, $context, $job);
    }

    public static function logDebug(string $message, array $context = [], ?BackupJob $job = null): self
    {
        return self::createLog(self::LEVEL_DEBUG, $message, $context, $job);
    }

    private static function createLog(string $level, string $message, array $context = [], ?BackupJob $job = null): self
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $trace[2] ?? [];

        return self::create([
            'backup_job_id' => $job?->id,
            'session_id' => $job?->uuid ?? ($context['session_id'] ?? null),
            'level' => $level,
            'category' => $context['category'] ?? self::CATEGORY_BACKUP,
            'event' => $context['event'] ?? 'general',
            'message' => $message,
            'description' => $context['description'] ?? null,
            'context' => $context,
            'source_class' => $caller['class'] ?? null,
            'source_method' => $caller['function'] ?? null,
            'source_line' => $caller['line'] ?? null,
            'execution_time' => $context['execution_time'] ?? null,
            'files_processed' => $context['files_processed'] ?? null,
            'bytes_processed' => $context['bytes_processed'] ?? null,
        ]);
    }

    /**
     * Formatting helpers
     */
    public function getFormattedMemoryUsageAttribute(): string
    {
        if (!$this->memory_usage) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->memory_usage;

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFormattedBytesProcessedAttribute(): string
    {
        if (!$this->bytes_processed) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = $this->bytes_processed;

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getLevelColorAttribute(): string
    {
        return match ($this->level) {
            self::LEVEL_EMERGENCY, self::LEVEL_ALERT, self::LEVEL_CRITICAL => 'red',
            self::LEVEL_ERROR => 'danger',
            self::LEVEL_WARNING => 'warning',
            self::LEVEL_NOTICE => 'info',
            self::LEVEL_INFO => 'success',
            self::LEVEL_DEBUG => 'gray',
            default => 'gray',
        };
    }

    public function getLevelIconAttribute(): string
    {
        return match ($this->level) {
            self::LEVEL_EMERGENCY, self::LEVEL_ALERT, self::LEVEL_CRITICAL => 'heroicon-o-exclamation-triangle',
            self::LEVEL_ERROR => 'heroicon-o-x-circle',
            self::LEVEL_WARNING => 'heroicon-o-exclamation-circle',
            self::LEVEL_NOTICE, self::LEVEL_INFO => 'heroicon-o-information-circle',
            self::LEVEL_DEBUG => 'heroicon-o-bug-ant',
            default => 'heroicon-o-document',
        };
    }

    /**
     * Search and filtering
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('message', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('event', 'like', "%{$search}%")
                ->orWhereJsonContains('context', $search)
                ->orWhere('error_code', 'like', "%{$search}%");
        });
    }

    public function scopeFilterByDateRange(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Statistics and aggregation
     */
    public static function getStatsByLevel(int $jobId = null, int $hours = 24): array
    {
        $query = self::recent($hours);

        if ($jobId) {
            $query->where('backup_job_id', $jobId);
        }

        return $query->selectRaw('level, COUNT(*) as count')
            ->groupBy('level')
            ->pluck('count', 'level')
            ->toArray();
    }

    public static function getStatsByCategory(int $jobId = null, int $hours = 24): array
    {
        $query = self::recent($hours);

        if ($jobId) {
            $query->where('backup_job_id', $jobId);
        }

        return $query->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();
    }

    /**
     * Cleanup old logs
     */
    public static function cleanup(int $days = 90): int
    {
        return self::where('created_at', '<', now()->subDays($days))->delete();
    }

    /**
     * Get full context for debugging
     */
    public function getFullContextAttribute(): array
    {
        return [
            'log' => [
                'id' => $this->id,
                'level' => $this->level,
                'category' => $this->category,
                'event' => $this->event,
                'message' => $this->message,
                'created_at' => $this->created_at->toISOString(),
            ],
            'context' => $this->context ?? [],
            'metadata' => $this->metadata ?? [],
            'performance' => [
                'execution_time' => $this->execution_time,
                'memory_usage' => $this->memory_usage,
                'files_processed' => $this->files_processed,
                'bytes_processed' => $this->bytes_processed,
            ],
            'source' => [
                'class' => $this->source_class,
                'method' => $this->source_method,
                'line' => $this->source_line,
            ],
            'environment' => [
                'php_version' => $this->php_version,
                'laravel_version' => $this->laravel_version,
                'environment' => $this->environment,
            ],
            'error' => $this->error_code ? [
                'code' => $this->error_code,
                'stack' => $this->error_stack,
                'context' => $this->error_context,
            ] : null,
        ];
    }
}
