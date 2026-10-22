<x-layouts::app :title="__($title)">
    <x-page-header :title="__($title)" />

    <x-empty-state
        icon="wrench-screwdriver"
        :title="__('This page is under construction.')"
        :description="__('The :module module will be available soon.', ['module' => __($title)])"
    >
        <flux:button :href="route('dashboard')" variant="primary" wire:navigate>
            {{ __('Back to dashboard') }}
        </flux:button>
    </x-empty-state>
</x-layouts::app>
