<?php

namespace Juniyasyos\FilamentLaravelBackup\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Backup Job Model
 * 
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $type
 * @property string $status
 * @property array|null $options
 * @property string|null $disk
 * @property string|null $filename
 * @property string|null $path
 * @property int $progress_percentage
 * @property string|null $current_step
 * @property array|null $steps
 * @property int|null $file_size
 * @property int|null $duration
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property string|null $error_message
 * @property array|null $error_details
 * @property int $retry_count
 * @property int $max_retries
 * @property Carbon|null $next_retry_at
 * @property MorphTo $user
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $queue_name
 * @property string|null $connection
 * @property array|null $job_payload
 * @property bool $should_cleanup
 * @property Carbon|null $cleanup_at
 * @property bool $is_protected
 */
class BackupJob extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'type',
        'status',
        'options',
        'disk',
        'filename',
        'path',
        'progress_percentage',
        'current_step',
        'steps',
        'file_size',
        'duration',
        'started_at',
        'completed_at',
        'error_message',
        'error_details',
        'retry_count',
        'max_retries',
        'next_retry_at',
        'user_id',
        'user_type',
        'ip_address',
        'user_agent',
        'queue_name',
        'connection',
        'job_payload',
        'should_cleanup',
        'cleanup_at',
        'is_protected'
    ];

    protected $casts = [
        'options' => 'json',
        'steps' => 'json',
        'error_details' => 'json',
        'job_payload' => 'json',
        'file_size' => 'integer',
        'duration' => 'integer',
        'progress_percentage' => 'integer',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'should_cleanup' => 'boolean',
        'is_protected' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'cleanup_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_QUEUED = 'queued';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_TIMEOUT = 'timeout';

    // Type constants
    const TYPE_FULL = 'full';
    const TYPE_DATABASE_ONLY = 'database_only';
    const TYPE_FILES_ONLY = 'files_only';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Relations
     */
    public function logs(): HasMany
    {
        return $this->hasMany(BackupLog::class);
    }

    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scopes
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_QUEUED,
            self::STATUS_PROCESSING
        ]);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
            self::STATUS_TIMEOUT
        ]);
    }

    public function scopeRetryable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED)
            ->whereRaw('retry_count < max_retries');
    }

    public function scopeReadyForRetry(Builder $query): Builder
    {
        return $query->retryable()
            ->where(function ($q) {
                $q->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            });
    }

    public function scopeForCleanup(Builder $query): Builder
    {
        return $query->where('should_cleanup', true)
            ->where('is_protected', false)
            ->where('cleanup_at', '<=', now());
    }

    /**
     * Status checks
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isQueued(): bool
    {
        return $this->status === self::STATUS_QUEUED;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return in_array($this->status, [
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
            self::STATUS_TIMEOUT
        ]);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_QUEUED,
            self::STATUS_PROCESSING
        ]);
    }

    public function canRetry(): bool
    {
        return $this->isFailed() && $this->retry_count < $this->max_retries;
    }

    /**
     * Status management
     */
    public function markAsQueued(): self
    {
        $this->update(['status' => self::STATUS_QUEUED]);
        $this->log('Job queued for processing');
        return $this;
    }

    public function markAsProcessing(?string $step = null): self
    {
        $updates = [
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
            'progress_percentage' => 0,
        ];

        if ($step) {
            $updates['current_step'] = $step;
        }

        $this->update($updates);
        $this->log('Job processing started', 'info', ['step' => $step]);
        return $this;
    }

    public function markAsCompleted(?string $filePath = null, ?int $fileSize = null): self
    {
        $updates = [
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'progress_percentage' => 100,
            'duration' => $this->started_at ? $this->started_at->diffInSeconds(now()) : null,
        ];

        if ($filePath) {
            $updates['path'] = $filePath;
        }

        if ($fileSize) {
            $updates['file_size'] = $fileSize;
        }

        // Set cleanup date based on settings
        if ($this->should_cleanup && !$this->is_protected) {
            $cleanupDays = BackupSetting::get('backup.general.cleanup_days', 30);
            $updates['cleanup_at'] = now()->addDays($cleanupDays);
        }

        $this->update($updates);
        $this->log('Job completed successfully', 'info', [
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'duration' => $updates['duration']
        ]);

        return $this;
    }

    public function markAsFailed(string $error, array $details = []): self
    {
        $updates = [
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error_message' => $error,
            'error_details' => $details,
            'duration' => $this->started_at ? $this->started_at->diffInSeconds(now()) : null,
        ];

        // Set next retry time if retries are available
        if ($this->canRetry()) {
            $retryDelay = min(60 * pow(2, $this->retry_count), 3600); // Exponential backoff, max 1 hour
            $updates['next_retry_at'] = now()->addSeconds($retryDelay);
        }

        $this->update($updates);
        $this->log('Job failed', 'error', array_merge(['error' => $error], $details));

        return $this;
    }

    public function markAsTimeout(): self
    {
        $this->update([
            'status' => self::STATUS_TIMEOUT,
            'completed_at' => now(),
            'error_message' => 'Job execution timeout',
            'duration' => $this->started_at ? $this->started_at->diffInSeconds(now()) : null,
        ]);

        $this->log('Job timed out', 'error');
        return $this;
    }

    public function cancel(string $reason = 'Cancelled by user'): self
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'completed_at' => now(),
            'error_message' => $reason,
            'duration' => $this->started_at ? $this->started_at->diffInSeconds(now()) : null,
        ]);

        $this->log('Job cancelled', 'warning', ['reason' => $reason]);
        return $this;
    }

    /**
     * Progress tracking
     */
    public function updateProgress(int $percentage, ?string $step = null, array $context = []): self
    {
        $updates = ['progress_percentage' => max(0, min(100, $percentage))];

        if ($step) {
            $updates['current_step'] = $step;
        }

        $this->update($updates);

        if ($step) {
            $this->log("Progress: {$percentage}% - {$step}", 'info', $context);
        }

        return $this;
    }

    public function addStep(string $stepName, string $status = 'pending'): self
    {
        $steps = $this->steps ?? [];
        $steps[$stepName] = [
            'status' => $status,
            'started_at' => $status === 'processing' ? now()->toISOString() : null,
            'completed_at' => null,
        ];

        $this->update(['steps' => $steps]);
        return $this;
    }

    public function updateStep(string $stepName, string $status, array $data = []): self
    {
        $steps = $this->steps ?? [];

        if (!isset($steps[$stepName])) {
            $steps[$stepName] = [];
        }

        $steps[$stepName] = array_merge($steps[$stepName], [
            'status' => $status,
        ], $data);

        if ($status === 'processing') {
            $steps[$stepName]['started_at'] = now()->toISOString();
        } elseif (in_array($status, ['completed', 'failed'])) {
            $steps[$stepName]['completed_at'] = now()->toISOString();
        }

        $this->update(['steps' => $steps]);
        return $this;
    }

    /**
     * Retry management
     */
    public function incrementRetry(): self
    {
        $this->increment('retry_count');
        $this->update([
            'status' => self::STATUS_PENDING,
            'error_message' => null,
            'error_details' => null,
            'next_retry_at' => null,
        ]);

        $this->log("Retry attempt #{$this->retry_count}", 'info');
        return $this;
    }

    /**
     * Logging helper
     */
    public function log(string $message, string $level = 'info', array $context = []): void
    {
        $this->logs()->create([
            'session_id' => $this->uuid,
            'level' => $level,
            'category' => 'backup',
            'event' => 'job_update',
            'message' => $message,
            'context' => $context,
            'environment' => app()->environment(),
        ]);
    }

    /**
     * Useful accessors
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = $this->file_size;

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration) {
            return 'Unknown';
        }

        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_QUEUED => 'info',
            self::STATUS_PROCESSING => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED, self::STATUS_TIMEOUT => 'danger',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Static helper methods
     */
    public static function createForUser($user, array $attributes = []): self
    {
        return static::create(array_merge([
            'user_id' => $user->id,
            'user_type' => get_class($user),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $attributes));
    }

    /**
     * Cleanup old jobs
     */
    public static function cleanup(): int
    {
        $deleted = 0;

        // Delete jobs marked for cleanup
        $jobs = static::forCleanup()->get();

        foreach ($jobs as $job) {
            // Delete backup file if exists
            if ($job->path && Storage::disk($job->disk)->exists($job->path)) {
                Storage::disk($job->disk)->delete($job->path);
            }

            $job->delete();
            $deleted++;
        }

        return $deleted;
    }
}
