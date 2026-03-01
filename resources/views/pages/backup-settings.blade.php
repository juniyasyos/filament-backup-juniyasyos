<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="mb-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                    Backup Configuration
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Configure backup storage, notifications, and general settings for your backup system.
                </p>
            </div>

            <form wire:submit="saveSettings">
                {{ $this->form }}

                <div class="mt-6 flex justify-between items-center">
                    <div class="flex space-x-3">
                        <x-filament::button
                            type="submit"
                            color="primary"
                            loading-text="Saving..."
                            wire:loading.attr="disabled">
                            Save Settings
                        </x-filament::button>

                        <x-filament::button
                            type="button"
                            color="secondary"
                            wire:click="testStorageConnection"
                            loading-text="Testing...">
                            Test Storage
                        </x-filament::button>
                    </div>

                    <x-filament::button
                        type="button"
                        color="gray"
                        wire:click="resetSettings">
                        Reset to Defaults
                    </x-filament::button>
                </div>
            </form>
        </div>

        {{-- Storage Status Section --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Storage Status
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <div class="flex items-center">
                        <x-heroicon-o-check-circle class="w-5 h-5 text-green-400 mr-2" />
                        <span class="text-sm font-medium text-green-800 dark:text-green-200">
                            Local Storage
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-green-600 dark:text-green-300">
                        Available and configured
                    </p>
                </div>

                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                    <div class="flex items-center">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-400 mr-2" />
                        <span class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            S3 Storage
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-yellow-600 dark:text-yellow-300">
                        Requires configuration
                    </p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <div class="flex items-center">
                        <x-heroicon-o-cloud class="w-5 h-5 text-gray-400 mr-2" />
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">
                            Google Cloud
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Not configured
                    </p>
                </div>
            </div>
        </div>

        {{-- Recent Activity Section --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Recent Configuration Changes
            </h3>

            <div class="flow-root">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No recent changes to display.
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page>