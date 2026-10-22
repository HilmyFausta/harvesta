<?php

use App\Models\Block;
use App\Support\Format;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Blocks')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $sort = 'name';

    public string $direction = 'asc';

    /**
     * Authorize and initialize the page.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', Block::class);
    }

    /**
     * Reset pagination when search changes.
     */
    public function updated(string $property): void
    {
        if ($property === 'search') {
            $this->resetPage();
        }
    }

    /**
     * Sort by a whitelisted column.
     */
    public function sortBy(string $column): void
    {
        if (! in_array($column, ['name', 'area_sqm', 'created_at'], true)) {
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
     * Clear search filter.
     */
    public function resetFilters(): void
    {
        $this->reset(['search']);
        $this->resetPage();
    }

    /**
     * Delete a block.
     */
    public function delete(int $blockId): void
    {
        $block = Block::findOrFail($blockId);

        Gate::authorize('delete', $block);

        // Check if block has trees (including soft-deleted)
        if ($block->trees()->withTrashed()->exists()) {
            Flux::toast(variant: 'danger', text: __('This block cannot be deleted because it still has trees.'));

            return;
        }

        $block->delete();

        Flux::toast(variant: 'success', text: __('messages.deleted', ['item' => __('block')]));

        $this->resetPage();
    }

    /**
     * Render the block list.
     */
    public function render()
    {
        $sort = in_array($this->sort, ['name', 'area_sqm', 'created_at'], true) ? $this->sort : 'name';
        $direction = $this->direction === 'asc' ? 'asc' : 'desc';

        $query = Block::query()
            ->withCount('trees')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($q): void {
                    $q->where('name', 'ilike', '%'.$this->search.'%')
                        ->orWhere('location_note', 'ilike', '%'.$this->search.'%')
                        ->orWhere('description', 'ilike', '%'.$this->search.'%');
                });
            })
            ->orderBy($sort, $direction);

        return $this->view([
            'blocks' => $query->paginate(15),
        ]);
    }
}; ?>

<section class="w-full">
    <x-page-header :title="__('Blocks')" :description="__('Manage land blocks in your orchard.')">
        @can('create', App\Models\Block::class)
            <flux:button :href="route('blocks.create')" variant="primary" icon="plus" wire:navigate>
                {{ __('Add block') }}
            </flux:button>
        @endcan
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" icon="magnifying-glass" placeholder="{{ __('Search blocks...') }}" class="lg:col-span-2" />
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <flux:text>{{ trans_choice(':count block|:count blocks', $blocks->total(), ['count' => $blocks->total()]) }}</flux:text>

        @if ($search !== '')
            <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="arrow-path">
                {{ __('Reset filters') }}
            </flux:button>
        @endif
    </div>

    @if ($blocks->isEmpty())
        <x-empty-state icon="map" :title="__('No blocks found.')" :description="__('Adjust your search or add a new block.')">
            @can('create', App\Models\Block::class)
                <flux:button :href="route('blocks.create')" variant="primary" icon="plus" wire:navigate>
                    {{ __('Add block') }}
                </flux:button>
            @endcan
        </x-empty-state>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:table :paginate="$blocks">
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sort === 'name'" :direction="$sort === 'name' ? $direction : null" wire:click="sortBy('name')">
                        {{ __('Name') }}
                    </flux:table.column>
                    <flux:table.column sortable align="end" :sorted="$sort === 'area_sqm'" :direction="$sort === 'area_sqm' ? $direction : null" wire:click="sortBy('area_sqm')">
                        {{ __('Area (m²)') }}
                    </flux:table.column>
                    <flux:table.column>{{ __('Location') }}</flux:table.column>
                    <flux:table.column align="center">{{ __('Trees') }}</flux:table.column>
                    <flux:table.column sortable :sorted="$sort === 'created_at'" :direction="$sort === 'created_at' ? $direction : null" wire:click="sortBy('created_at')">
                        {{ __('Created') }}
                    </flux:table.column>
                    <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($blocks as $block)
                        <flux:table.row :key="$block->id">
                            <flux:table.cell variant="strong">
                                <a href="{{ route('blocks.show', $block) }}" class="hover:text-lime-600 dark:hover:text-lime-400" wire:navigate>
                                    {{ $block->name }}
                                </a>
                            </flux:table.cell>
                            <flux:table.cell align="end">{{ $block->area_sqm ? Format::number($block->area_sqm, 2) : '—' }}</flux:table.cell>
                            <flux:table.cell class="max-w-xs truncate">{{ $block->location_note ?: '—' }}</flux:table.cell>
                            <flux:table.cell align="center">
                                <flux:badge size="sm" color="zinc">{{ $block->trees_count }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ Format::date($block->created_at) }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-1">
                                    <flux:button :href="route('blocks.show', $block)" variant="ghost" size="sm" icon="eye" :aria-label="__('View block')" wire:navigate />
                                    @can('update', $block)
                                        <flux:button :href="route('blocks.edit', $block)" variant="ghost" size="sm" icon="pencil-square" :aria-label="__('Edit block')" wire:navigate />
                                    @endcan
                                    @can('delete', $block)
                                        <flux:button wire:click="delete({{ $block->id }})" wire:confirm="{{ __('messages.confirm_delete', ['item' => __('block')]) }}" variant="ghost" size="sm" icon="trash" :aria-label="__('Delete block')" />
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
