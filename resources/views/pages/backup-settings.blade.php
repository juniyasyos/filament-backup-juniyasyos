<x-filament-panels::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="flex justify-end gap-x-2">
            <x-filament::button type="submit" icon="heroicon-o-check">
                {{ __('filament-spatie-backup::backup.pages.settings.actions.save.label') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
