<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Backup Configuration</x-slot>
            <x-slot name="description">Edit backup configuration stored in a single row.</x-slot>

            <form wire:submit="saveConfiguration">
                {{ $this->form }}

                <div class="mt-6 flex items-center justify-end">
                    <x-filament::button type="submit" color="primary">Save</x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
