<div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-white">{{ __('Harvest History') }}</h3>
        <flux:button :href="route('harvests.create', ['tree_id' => $tree->id])" variant="primary" size="sm" icon="plus" wire:navigate>
            {{ __('Add harvest') }}
        </flux:button>
    </div>

    @if ($harvests->isEmpty())
        <div class="py-8 text-center text-zinc-500">
            <p>{{ __('No harvests yet.') }}</p>
        </div>
    @else
        <div class="mb-4 flex flex-wrap gap-4">
            @php
                $totalsByUnit = $harvests->groupBy('unit')->map(fn($group) => $group->sum('quantity'));
            @endphp
            @foreach ($totalsByUnit as $unit => $total)
                <div class="rounded-lg bg-zinc-100 px-3 py-2 dark:bg-zinc-800">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Total harvested') }} ({{ $unit }})</p>
                    <p class="text-lg font-semibold text-zinc-800 dark:text-white">{{ \App\Support\Format::number($total) }}</p>
                </div>
            @endforeach
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="pb-2 text-left font-medium text-zinc-600 dark:text-zinc-400">{{ __('Date') }}</th>
                        <th class="pb-2 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Quantity') }}</th>
                        <th class="pb-2 text-left font-medium text-zinc-600 dark:text-zinc-400">{{ __('Grade') }}</th>
                        <th class="pb-2 text-left font-medium text-zinc-600 dark:text-zinc-400">{{ __('Notes') }}</th>
                        <th class="pb-2 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($harvests as $harvest)
                        <tr>
                            <td class="py-3 font-medium text-zinc-800 dark:text-white">{{ \App\Support\Format::date($harvest->harvested_on) }}</td>
                            <td class="py-3 text-right font-medium text-zinc-800 dark:text-white">{{ \App\Support\Format::number($harvest->quantity) }} {{ $harvest->unit->label() }}</td>
                            <td class="py-3">
                                <flux:badge size="sm" :color="match($harvest->grade->value) {
                                    'premium' => 'green',
                                    'good' => 'blue',
                                    'fair' => 'yellow',
                                    'poor' => 'red',
                                }">{{ $harvest->grade->label() }}</flux:badge>
                            </td>
                            <td class="py-3 text-zinc-600 dark:text-zinc-400">{{ $harvest->notes ?: '—' }}</td>
                            <td class="py-3 text-right">
                                <div class="flex justify-end gap-1">
                                    @can('update', $harvest)
                                        <flux:button :href="route('harvests.edit', $harvest)" variant="ghost" size="sm" icon="pencil-square" :aria-label="__('Edit')" wire:navigate />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
