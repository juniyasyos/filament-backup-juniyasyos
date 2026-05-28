<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">{{ __('backup.pages.settings.general.section') }}</x-slot>
            <x-slot name="description">{{ __('backup.pages.settings.general.description') }}</x-slot>

            <form wire:submit="saveSettings">
                {{ $this->form }}

                <div class="mt-6 flex justify-between items-center">
                    <div class="flex space-x-3">
                        <x-filament::button
                            type="submit"
                            color="primary"
                            loading-text="Saving..."
                            wire:loading.attr="disabled">
                            {{ __('backup.pages.settings.buttons.save') }}
                        </x-filament::button>

                        <x-filament::button
                            type="button"
                            color="warning"
                            wire:click="testStorageConnection"
                            loading-text="Testing...">
                            {{ __('backup.pages.settings.buttons.test_storage') }}
                        </x-filament::button>
                    </div>

                    <x-filament::button
                        type="button"
                        color="gray"
                        wire:click="resetSettings">
                        {{ __('backup.pages.settings.buttons.reset') }}
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('backup.pages.settings.storage.section') }}</x-slot>
            <x-slot name="description">{{ __('backup.pages.settings.storage.description') }}</x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <div class="flex items-center">
                        <x-heroicon-o-check-circle class="w-5 h-5 text-green-400 mr-2" />
                        <span class="text-sm font-medium text-green-800 dark:text-green-200">{{ __('backup.pages.settings.storage.local_section') }}</span>
                    </div>
                    <p class="mt-1 text-sm text-green-600 dark:text-green-300">{{ __('backup.pages.settings.storage.status.available') }}</p>
                </div>

                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                    <div class="flex items-center">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-400 mr-2" />
                        <span class="text-sm font-medium text-yellow-800 dark:text-yellow-200">{{ __('backup.pages.settings.storage.s3_section') }}</span>
                    </div>
                    <p class="mt-1 text-sm text-yellow-600 dark:text-yellow-300">{{ __('backup.pages.settings.storage.status.requires_configuration') }}</p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <div class="flex items-center">
                        <x-heroicon-o-cloud class="w-5 h-5 text-gray-400 mr-2" />
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ __('backup.pages.settings.storage.gcs_section') }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('backup.pages.settings.storage.status.not_configured') }}</p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('backup.pages.settings.recent.heading') }}</x-slot>
            <x-slot name="description">{{ __('backup.pages.settings.recent.description') }}</x-slot>

            <div class="flow-root">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('backup.pages.settings.recent.empty') }}</p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>