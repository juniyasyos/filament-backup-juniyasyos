<div class="space-y-6" x-data="{ refreshInterval: null }" x-init="
    if ('{{ $job->isActive() }}') {
        refreshInterval = setInterval(() => {
            // Trigger a modal refresh for active jobs
            $dispatch('refresh-modal');
        }, 3000);
    }
" x-destroy="if (refreshInterval) clearInterval(refreshInterval)">
    {{-- Live Status Indicator --}}
    @if($job->isActive())
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
        <div class="flex items-center space-x-2">
            <div class="w-3 h-3 bg-blue-500 rounded-full animate-pulse"></div>
            <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                Job is currently running... This view will auto-refresh.
            </span>
        </div>
    </div>
    @endif

    {{-- Job Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Job Information</h4>
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-600 dark:text-gray-400">Name:</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $job->name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-600 dark:text-gray-400">Type:</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">
                        <x-filament::badge color="{{ match($job->type) {
                            'full' => 'primary',
                            'database_only' => 'info',
                            'files_only' => 'warning',
                            default => 'gray'
                        } }}">
                            {{ ucfirst(str_replace('_', ' ', $job->type)) }}
                        </x-filament::badge>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-600 dark:text-gray-400">Status:</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">
                        <x-filament::badge color="{{ $job->status_color }}">
                            {{ ucfirst($job->status) }}
                        </x-filament::badge>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-600 dark:text-gray-400">UUID:</dt>
                    <dd class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $job->uuid }}</dd>
                </div>
            </dl>
        </div>

        <div>
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Progress & Timing</h4>
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-600 dark:text-gray-400">Progress:</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">
                        <div class="flex items-center space-x-2">
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-3 relative overflow-hidden">
                                @if($job->isActive() && $job->progress_percentage < 100)
                                    {{-- Animated progress bar for active jobs --}}
                                    <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-3 rounded-full transition-all duration-1000 ease-out relative"
                                    style="width: {{ $job->progress_percentage }}%">
                                    <div class="absolute inset-0 bg-white/20 animate-pulse rounded-full"></div>
                            </div>
                            @elseif($job->isCompleted())
                            {{-- Completed progress --}}
                            <div class="bg-green-500 h-3 rounded-full" style="width: 100%"></div>
                            @elseif($job->isFailed())
                            {{-- Failed progress --}}
                            <div class="bg-red-500 h-3 rounded-full" style="width: {{ $job->progress_percentage }}%"></div>
                            @else
                            {{-- Default progress --}}
                            <div class="bg-gray-400 h-3 rounded-full" style="width: {{ $job->progress_percentage }}%"></div>
                            @endif
                        </div>
                        <span class="text-sm font-bold {{ $job->isActive() ? 'text-blue-600 dark:text-blue-400' : '' }}">
                            {{ $job->progress_percentage }}%
                        </span>
                </div>
                </dd>
        </div>
        @if($job->current_step)
        <div class="flex justify-between">
            <dt class="text-sm text-gray-600 dark:text-gray-400">Current Step:</dt>
            <dd class="text-sm text-gray-900 dark:text-white">{{ $job->current_step }}</dd>
        </div>
        @endif
        <div class="flex justify-between">
            <dt class="text-sm text-gray-600 dark:text-gray-400">Created:</dt>
            <dd class="text-sm text-gray-900 dark:text-white">{{ $job->created_at->format('Y-m-d H:i:s') }}</dd>
        </div>
        @if($job->started_at)
        <div class="flex justify-between">
            <dt class="text-sm text-gray-600 dark:text-gray-400">Started:</dt>
            <dd class="text-sm text-gray-900 dark:text-white">{{ $job->started_at->format('Y-m-d H:i:s') }}</dd>
        </div>
        @endif
        @if($job->completed_at)
        <div class="flex justify-between">
            <dt class="text-sm text-gray-600 dark:text-gray-400">Completed:</dt>
            <dd class="text-sm text-gray-900 dark:text-white">{{ $job->completed_at->format('Y-m-d H:i:s') }}</dd>
        </div>
        @endif
        @if($job->duration)
        <div class="flex justify-between">
            <dt class="text-sm text-gray-600 dark:text-gray-400">Duration:</dt>
            <dd class="text-sm text-gray-900 dark:text-white">{{ $job->formatted_duration }}</dd>
        </div>
        @endif
        </dl>
    </div>
</div>

{{-- File Information --}}
@if($job->isCompleted() && ($job->file_size || $job->path))
<div>
    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">File Information</h4>
    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @if($job->file_size)
        <div class="flex justify-between">
            <dt class="text-sm text-gray-600 dark:text-gray-400">File Size:</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $job->formatted_file_size }}</dd>
        </div>
        @endif
        @if($job->disk)
        <div class="flex justify-between">
            <dt class="text-sm text-gray-600 dark:text-gray-400">Storage:</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-white">
                <x-filament::badge>{{ ucfirst($job->disk) }}</x-filament::badge>
            </dd>
        </div>
        @endif
        @if($job->path)
        <div class="col-span-full">
            <dt class="text-sm text-gray-600 dark:text-gray-400">Path:</dt>
            <dd class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-1 p-2 bg-gray-50 dark:bg-gray-800 rounded">
                {{ $job->path }}
            </dd>
        </div>
        @endif
    </dl>
</div>
@endif

{{-- Error Information --}}
@if($job->isFailed() && $job->error_message)
<div>
    <h4 class="text-sm font-medium text-red-500 dark:text-red-400 mb-2">Error Information</h4>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md p-4">
        <dt class="text-sm text-red-600 dark:text-red-400 font-medium">Error Message:</dt>
        <dd class="text-sm text-red-900 dark:text-red-200 mt-1">{{ $job->error_message }}</dd>

        @if($job->retry_count > 0)
        <div class="mt-3 pt-3 border-t border-red-200 dark:border-red-800">
            <div class="flex justify-between text-sm">
                <span class="text-red-600 dark:text-red-400">Retry Attempts:</span>
                <span class="text-red-900 dark:text-red-200">{{ $job->retry_count }} / {{ $job->max_retries }}</span>
            </div>
            @if($job->next_retry_at && $job->canRetry())
            <div class="flex justify-between text-sm mt-1">
                <span class="text-red-600 dark:text-red-400">Next Retry:</span>
                <span class="text-red-900 dark:text-red-200">{{ $job->next_retry_at->format('Y-m-d H:i:s') }}</span>
            </div>
            @endif
        </div>
        @endif
    </div>
</div>
@endif

{{-- Job Steps --}}
@if($job->steps && is_array($job->steps))
<div>
    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Processing Steps</h4>
    <div class="space-y-2">
        @foreach($job->steps as $stepName => $stepData)
        <div class="flex items-center space-x-3 p-2 bg-gray-50 dark:bg-gray-800 rounded">
            <div class="flex-shrink-0">
                @if(isset($stepData['status']))
                @if($stepData['status'] === 'completed')
                <x-heroicon-s-check-circle class="w-5 h-5 text-green-500" />
                @elseif($stepData['status'] === 'processing')
                <div class="w-5 h-5 text-blue-500">
                    <svg class="animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                @elseif($stepData['status'] === 'failed')
                <x-heroicon-s-x-circle class="w-5 h-5 text-red-500" />
                @else
                <x-heroicon-s-clock class="w-5 h-5 text-gray-400" />
                @endif
                @else
                <x-heroicon-s-clock class="w-5 h-5 text-gray-400" />
                @endif
            </div>
            <div class="flex-1">
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ ucfirst(str_replace('_', ' ', $stepName)) }}
                </div>
                @if(isset($stepData['started_at']) && isset($stepData['completed_at']))
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ \Carbon\Carbon::parse($stepData['started_at'])->format('H:i:s') }} -
                    {{ \Carbon\Carbon::parse($stepData['completed_at'])->format('H:i:s') }}
                </div>
                @elseif(isset($stepData['started_at']))
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Started: {{ \Carbon\Carbon::parse($stepData['started_at'])->format('H:i:s') }}
                </div>
                @endif
            </div>
            @if(isset($stepData['status']))
            <div class="text-xs">
                <x-filament::badge color="{{ match($stepData['status']) {
                        'completed' => 'success',
                        'processing' => 'primary',
                        'failed' => 'danger',
                        default => 'gray'
                    } }}">
                    {{ ucfirst($stepData['status']) }}
                </x-filament::badge>
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- User Information --}}
@if($job->user_id)
<div>
    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">User Information</h4>
    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="flex justify-between">
            <dt class="text-sm text-gray-600 dark:text-gray-400">Initiated By:</dt>
            <dd class="text-sm text-gray-900 dark:text-white">User ID: {{ $job->user_id }}</dd>
        </div>
        @if($job->ip_address)
        <div class="flex justify-between">
            <dt class="text-sm text-gray-600 dark:text-gray-400">IP Address:</dt>
            <dd class="text-sm font-mono text-gray-500 dark:text-gray-400">{{ $job->ip_address }}</dd>
        </div>
        @endif
    </dl>
</div>
@endif

{{-- Job Options --}}
@if($job->options && is_array($job->options))
<div>
    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Job Configuration</h4>
    <div class="bg-gray-50 dark:bg-gray-800 rounded-md p-3">
        <pre class="text-xs text-gray-600 dark:text-gray-300 whitespace-pre-wrap">{{ json_encode($job->options, JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>
@endif
</div>