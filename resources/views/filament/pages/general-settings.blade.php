<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        <label class="flex items-start gap-3">
            <x-filament::input.checkbox wire:model="loginRequired" />
            <span>
                <span class="font-medium text-gray-950 dark:text-white">Nur mit Login zu nutzen</span>
                <span class="block text-sm text-gray-500 dark:text-gray-400">
                    Ja: Die Anwendung ist nur mit Login nutzbar — jeder Aufruf wird zum Login geleitet.
                    Nein: wie bisher öffentlich zugänglich.
                </span>
            </span>
        </label>

        <x-filament::button type="submit">
            Speichern
        </x-filament::button>
    </form>
</x-filament-panels::page>
