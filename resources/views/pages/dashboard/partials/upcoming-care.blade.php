<div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
    <h3 class="mb-4 text-lg font-semibold text-zinc-800 dark:text-white">{{ __('Upcoming Care') }}</h3>

    @if ($upcomingCare->isEmpty())
        <div class="py-8 text-center text-zinc-500">
            <p>{{ __('No upcoming care activities.') }}</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="pb-2 text-left font-medium text-zinc-600 dark:text-zinc-400">{{ __('Tree') }}</th>
                        <th class="pb-2 text-left font-medium text-zinc-600 dark:text-zinc-400">{{ __('Activity') }}</th>
                        <th class="pb-2 text-left font-medium text-zinc-600 dark:text-zinc-400">{{ __('Due date') }}</th>
                        <th class="pb-2 text-left font-medium text-zinc-600 dark:text-zinc-400">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($upcomingCare as $careLog)
                        <tr>
                            <td class="py-3">
                                <a href="{{ \Illuminate\Support\Facades\Route::has('trees.show') ? route('trees.show', $careLog->tree) : route('trees.index') }}" class="font-medium text-zinc-800 hover:text-lime-600 dark:text-white dark:hover:text-lime-400" wire:navigate>
                                    {{ $careLog->tree->code }}
                                </a>
                            </td>
                            <td class="py-3">
                                <flux:badge size="sm" color="lime">{{ $careLog->activity_type->label() }}</flux:badge>
                            </td>
                            <td class="py-3 text-zinc-600 dark:text-zinc-400">
                                {{ \App\Support\Format::date($careLog->next_due_on) }}
                            </td>
                            <td class="py-3">
                                @if ($careLog->next_due_on->isPast())
                                    <flux:badge size="sm" color="red">{{ __('Overdue') }}</flux:badge>
                                @elseif ($careLog->next_due_on->isToday())
                                    <flux:badge size="sm" color="orange">{{ __('Due today') }}</flux:badge>
                                @else
                                    <flux:badge size="sm" color="yellow">
                                        {{ __('In :days days', ['days' => now()->diffInDays($careLog->next_due_on)]) }}
                                    </flux:badge>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($upcomingCare->count() >= 10)
            <div class="mt-4 text-center">
                <flux:button :href="route('care-logs.index')" variant="ghost" size="sm" wire:navigate>
                    {{ __('View all care logs') }}
                </flux:button>
            </div>
        @endif
    @endif
</div>
