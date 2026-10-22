@props([
    'title',
    'description' => null,
])

{{-- Page title with optional description and action buttons (pass them in the slot). --}}
<div {{ $attributes->class('mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between') }}>
    <div>
        <flux:heading size="xl" level="1">{{ $title }}</flux:heading>

        @if ($description)
            <flux:text class="mt-1">{{ $description }}</flux:text>
        @endif
    </div>

    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
