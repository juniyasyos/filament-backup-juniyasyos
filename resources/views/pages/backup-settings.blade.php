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
</x-filament-panels::page>