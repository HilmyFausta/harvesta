<?php

use App\Models\Category;
use App\Models\TreeType;
use App\Support\Format;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Tree Types')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $category_id = '';

    public string $sort = 'name';

    public string $direction = 'asc';

    /**
     * Authorize and initialize the page.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', TreeType::class);
    }

    /**
     * Reset pagination when filters change.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'category_id'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Sort by a whitelisted column.
     */
    public function sortBy(string $column): void
    {
        if (! in_array($column, ['name', 'created_at'], true)) {
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
     * Clear filters.
     */
    public function resetFilters(): void
    {
        $this->reset(['search', 'category_id']);
        $this->resetPage();
    }

    /**
     * Delete a tree type.
     */
    public function delete(int $treeTypeId): void
    {
        $treeType = TreeType::findOrFail($treeTypeId);

        Gate::authorize('delete', $treeType);

        // Check if tree type is used by trees (including soft-deleted)
        if ($treeType->trees()->withTrashed()->exists()) {
            Flux::toast(variant: 'danger', text: __('This tree type cannot be deleted because it is still used by trees.'));

            return;
        }

        $treeType->delete();

        Flux::toast(variant: 'success', text: __('messages.deleted', ['item' => __('tree type')]));

        $this->resetPage();
    }

    /**
     * Render the tree type list.
     */
    public function render()
    {
        $sort = in_array($this->sort, ['name', 'created_at'], true) ? $this->sort : 'name';
        $direction = $this->direction === 'asc' ? 'asc' : 'desc';

        $query = TreeType::query()
            ->with('category')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($q): void {
                    $q->where('name', 'ilike', '%'.$this->search.'%')
                        ->orWhere('scientific_name', 'ilike', '%'.$this->search.'%')
                        ->orWhere('description', 'ilike', '%'.$this->search.'%');
                });
            })
            ->when($this->category_id !== '', fn ($query) => $query->where('category_id', $this->category_id))
            ->orderBy($sort, $direction);

        return $this->view([
            'treeTypes' => $query->paginate(15),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }
}; ?>

<section class="w-full">
    <x-page-header :title="__('Tree Types')" :description="__('Manage types of trees in your orchard.')">
        @can('create', App\Models\TreeType::class)
            <flux:button :href="route('tree-types.create')" variant="primary" icon="plus" wire:navigate>
                {{ __('Add tree type') }}
            </flux:button>
        @endcan
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" icon="magnifying-glass" placeholder="{{ __('Search tree types...') }}" class="lg:col-span-2" />

        <flux:select wire:model.live="category_id" :label="__('Category')">
            <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
            @foreach ($categories as $category)
                <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <flux:text>{{ trans_choice(':count tree type|:count tree types', $treeTypes->total(), ['count' => $treeTypes->total()]) }}</flux:text>

        @if ($search !== '' || $category_id !== '')
            <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="arrow-path">
                {{ __('Reset filters') }}
            </flux:button>
        @endif
    </div>

    @if ($treeTypes->isEmpty())
        <x-empty-state icon="beaker" :title="__('No tree types found.')" :description="__('Adjust your filters or add a new tree type.')">
            @can('create', App\Models\TreeType::class)
                <flux:button :href="route('tree-types.create')" variant="primary" icon="plus" wire:navigate>
                    {{ __('Add tree type') }}
                </flux:button>
            @endcan
        </x-empty-state>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:table :paginate="$treeTypes">
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sort === 'name'" :direction="$sort === 'name' ? $direction : null" wire:click="sortBy('name')">
                        {{ __('Name') }}
                    </flux:table.column>
                    <flux:table.column>{{ __('Category') }}</flux:table.column>
                    <flux:table.column>{{ __('Scientific name') }}</flux:table.column>
                    <flux:table.column>{{ __('Description') }}</flux:table.column>
                    <flux:table.column sortable :sorted="$sort === 'created_at'" :direction="$sort === 'created_at' ? $direction : null" wire:click="sortBy('created_at')">
                        {{ __('Created') }}
                    </flux:table.column>
                    <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($treeTypes as $treeType)
                        <flux:table.row :key="$treeType->id">
                            <flux:table.cell variant="strong">{{ $treeType->name }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="lime">{{ $treeType->category->name }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="max-w-xs truncate italic text-zinc-500">{{ $treeType->scientific_name ?: '—' }}</flux:table.cell>
                            <flux:table.cell class="max-w-md truncate">{{ $treeType->description ?: '—' }}</flux:table.cell>
                            <flux:table.cell>{{ Format::date($treeType->created_at) }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-1">
                                    @can('update', $treeType)
                                        <flux:button :href="route('tree-types.edit', $treeType)" variant="ghost" size="sm" icon="pencil-square" :aria-label="__('Edit tree type')" wire:navigate />
                                    @endcan
                                    @can('delete', $treeType)
                                        <flux:button wire:click="delete({{ $treeType->id }})" wire:confirm="{{ __('messages.confirm_delete', ['item' => __('tree type')]) }}" variant="ghost" size="sm" icon="trash" :aria-label="__('Delete tree type')" />
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
