@props([
    'icon' => 'inbox',
    'title',
    'description' => null,
])

{{-- Informative empty state (PRD 8): icon, sentence, and an optional action in the slot. --}}
<div {{ $attributes->class('flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-zinc-300 bg-white px-6 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900') }}>
    <div class="flex size-12 items-center justify-center rounded-full bg-leaf/10 text-leaf">
        <flux:icon :icon="$icon" class="size-6" />
    </div>

    <flux:heading size="lg">{{ $title }}</flux:heading>

    @if ($description)
        <flux:text class="max-w-md">{{ $description }}</flux:text>
    @endif

    @if ($slot->isNotEmpty())
        <div class="mt-2">{{ $slot }}</div>
    @endif
</div>
