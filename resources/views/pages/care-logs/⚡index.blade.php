<?php

use App\Enums\ActivityType;
use App\Models\Block;
use App\Models\CareLog;
use App\Models\Tree;
use App\Support\Format;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Care Logs')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $tree_id = '';

    public string $block_id = '';

    public string $activity_type = '';

    public string $from = '';

    public string $to = '';

    public string $sort = 'performed_on';

    public string $direction = 'desc';

    /**
     * Authorize and initialize the page.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', CareLog::class);
    }

    /**
     * Reset pagination when filters change.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['from', 'to'], true) && $this->{$property} !== '') {
            $this->validateOnly($property, [$property => ['date']]);
        }

        if (in_array($property, ['search', 'tree_id', 'block_id', 'activity_type', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Sort by a whitelisted column.
     */
    public function sortBy(string $column): void
    {
        if (! in_array($column, ['performed_on', 'activity_type', 'next_due_on'], true)) {
            return;
        }

        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = 'asc';
        }

        $this->resetPage();
    }

    /**
     * Clear every filter.
     */
    public function resetFilters(): void
    {
        $this->reset(['search', 'tree_id', 'block_id', 'activity_type', 'from', 'to']);
        $this->resetPage();
    }

    /**
     * Delete a care log.
     */
    public function delete(int $careLogId): void
    {
        $careLog = CareLog::findOrFail($careLogId);

        Gate::authorize('delete', $careLog);

        $careLog->delete();

        Flux::toast(variant: 'success', text: __('messages.deleted', ['item' => __('care log')]));

        $this->resetPage();
    }

    /**
     * Render the care log list.
     */
    public function render()
    {
        $sort = in_array($this->sort, ['performed_on', 'activity_type', 'next_due_on'], true) ? $this->sort : 'performed_on';
        $direction = $this->direction === 'asc' ? 'asc' : 'desc';

        $query = CareLog::query()
            ->with(['tree.block', 'tree.treeType', 'user'])
            ->when($this->search !== '', function ($query): void {
                $query->where('notes', 'ilike', '%'.$this->search.'%');
            })
            ->when($this->tree_id !== '', fn ($query) => $query->where('tree_id', $this->tree_id))
            ->when($this->block_id !== '', function ($query): void {
                $query->whereHas('tree', fn ($q) => $q->where('block_id', $this->block_id));
            })
            ->when($this->activity_type !== '', fn ($query) => $query->where('activity_type', $this->activity_type))
            ->when($this->from !== '', fn ($query) => $query->whereDate('performed_on', '>=', $this->from))
            ->when($this->to !== '', fn ($query) => $query->whereDate('performed_on', '<=', $this->to))
            ->orderBy($sort, $direction);

        return $this->view([
            'careLogs' => $query->paginate(15),
            'trees' => Tree::query()->with(['block', 'treeType'])->orderBy('code')->get(),
            'blocks' => Block::query()->orderBy('name')->get(),
            'activityTypes' => ActivityType::options(),
        ]);
    }
}; ?>

<section class="w-full">
    <x-page-header :title="__('Care Logs')" :description="__('Track tree maintenance and care activities.')">
        <flux:button :href="route('care-logs.create')" variant="primary" icon="plus" wire:navigate>
            {{ __('Add care log') }}
        </flux:button>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
        <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" icon="magnifying-glass" placeholder="{{ __('Search notes...') }}" />

        <flux:select wire:model.live="tree_id" :label="__('Tree')">
            <flux:select.option value="">{{ __('All trees') }}</flux:select.option>
            @foreach ($trees as $tree)
                <flux:select.option value="{{ $tree->id }}">{{ $tree->code }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="block_id" :label="__('Block')">
            <flux:select.option value="">{{ __('All blocks') }}</flux:select.option>
            @foreach ($blocks as $block)
                <flux:select.option value="{{ $block->id }}">{{ $block->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="activity_type" :label="__('Activity type')">
            <flux:select.option value="">{{ __('All activities') }}</flux:select.option>
            @foreach ($activityTypes as $value => $label)
                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input wire:model.live="from" type="date" :label="__('From')" />
        <flux:input wire:model.live="to" type="date" :label="__('To')" />
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <flux:text>{{ trans_choice(':count care log|:count care logs', $careLogs->total(), ['count' => $careLogs->total()]) }}</flux:text>

        <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="arrow-path">
            {{ __('Reset filters') }}
        </flux:button>
    </div>

    @if ($careLogs->isEmpty())
        <x-empty-state icon="wrench-screwdriver" :title="__('No care logs found.')" :description="__('Adjust your filters or add a new care log.')">
            <flux:button :href="route('care-logs.create')" variant="primary" icon="plus" wire:navigate>
                {{ __('Add care log') }}
            </flux:button>
        </x-empty-state>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:table :paginate="$careLogs">
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sort === 'performed_on'" :direction="$sort === 'performed_on' ? $direction : null" wire:click="sortBy('performed_on')">
                        {{ __('Performed on') }}
                    </flux:table.column>
                    <flux:table.column>{{ __('Tree') }}</flux:table.column>
                    <flux:table.column>{{ __('Block') }}</flux:table.column>
                    <flux:table.column sortable :sorted="$sort === 'activity_type'" :direction="$sort === 'activity_type' ? $direction : null" wire:click="sortBy('activity_type')">
                        {{ __('Activity') }}
                    </flux:table.column>
                    <flux:table.column sortable :sorted="$sort === 'next_due_on'" :direction="$sort === 'next_due_on' ? $direction : null" wire:click="sortBy('next_due_on')">
                        {{ __('Next due') }}
                    </flux:table.column>
                    <flux:table.column>{{ __('Notes') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($careLogs as $careLog)
                        <flux:table.row :key="$careLog->id">
                            <flux:table.cell variant="strong">{{ Format::date($careLog->performed_on) }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="font-medium text-zinc-800 dark:text-white">{{ $careLog->tree->code }}</div>
                                <div class="text-xs text-zinc-500">{{ $careLog->tree->treeType->name }}</div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $careLog->tree->block->name }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="lime">{{ $careLog->activity_type->label() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($careLog->next_due_on)
                                    <span class="{{ $careLog->next_due_on->isPast() ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-zinc-600 dark:text-zinc-400' }}">
                                        {{ Format::date($careLog->next_due_on) }}
                                    </span>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="max-w-xs truncate">{{ $careLog->notes ?: '—' }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-1">
                                    @can('update', $careLog)
                                        <flux:button :href="route('care-logs.edit', $careLog)" variant="ghost" size="sm" icon="pencil-square" :aria-label="__('Edit care log')" wire:navigate />
                                    @endcan
                                    @can('delete', $careLog)
                                        <flux:button wire:click="delete({{ $careLog->id }})" wire:confirm="{{ __('messages.confirm_delete', ['item' => __('care log')]) }}" variant="ghost" size="sm" icon="trash" :aria-label="__('Delete care log')" />
                                    @endcan
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @endif
</section>
