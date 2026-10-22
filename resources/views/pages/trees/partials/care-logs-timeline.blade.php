<div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-white">{{ __('Care History') }}</h3>
        <flux:button :href="route('care-logs.create', ['tree_id' => $tree->id])" variant="primary" size="sm" icon="plus" wire:navigate>
            {{ __('Add care log') }}
        </flux:button>
    </div>

    @if ($careLogs->isEmpty())
        <div class="py-8 text-center text-zinc-500">
            <p>{{ __('No care logs yet.') }}</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($careLogs as $careLog)
                <div class="flex gap-4 border-l-2 border-lime-500 pl-4 dark:border-lime-600">
                    <div class="flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <flux:badge size="sm" color="lime">{{ $careLog->activity_type->label() }}</flux:badge>
                                    <span class="text-sm font-medium text-zinc-800 dark:text-white">{{ \App\Support\Format::date($careLog->performed_on) }}</span>
                                </div>
                                @if ($careLog->notes)
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $careLog->notes }}</p>
                                @endif
                                @if ($careLog->next_due_on)
                                    <p class="mt-1 text-xs text-zinc-500">
                                        {{ __('Next due') }}:
                                        <span class="{{ $careLog->next_due_on->isPast() ? 'font-semibold text-red-600 dark:text-red-400' : 'text-zinc-600 dark:text-zinc-400' }}">
                                            {{ \App\Support\Format::date($careLog->next_due_on) }}
                                        </span>
                                    </p>
                                @endif
                            </div>
                            <div class="flex gap-1">
                                @can('update', $careLog)
                                    <flux:button :href="route('care-logs.edit', $careLog)" variant="ghost" size="sm" icon="pencil-square" :aria-label="__('Edit')" wire:navigate />
                                @endcan
                            </div>
                        </div>
                        @if ($careLog->user)
                            <p class="mt-1 text-xs text-zinc-400">{{ __('By') }} {{ $careLog->user->name }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
