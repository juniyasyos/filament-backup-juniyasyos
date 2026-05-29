<x-filament-panels::page>
    <div class="space-y-6">

        <x-filament::section>
            <x-slot name="heading">{{ __('backup.pages.settings.general.section') }}</x-slot>
            <x-slot name="description">{{ __('backup.pages.settings.general.description') }}</x-slot>

            <form wire:submit="saveSettings">
                {{ $this->form }}
            </form>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">{{ __('backup.pages.settings.raw.section') }}</x-slot>
        <x-slot name="description">{{ __('backup.pages.settings.raw.description') }}</x-slot>

        <div
            class="overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
            <pre
                class="whitespace-pre-wrap break-words text-xs leading-6 text-gray-800 dark:text-gray-100">{{ $this->rawBackupSettingsJson }}</pre>
        </div>
    </x-filament::section>
</x-filament-panels::page>