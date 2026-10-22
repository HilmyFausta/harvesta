<?php

use App\Models\Category;
use App\Support\Format;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Categories')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $sort = 'name';

    public string $direction = 'asc';

    /**
     * Authorize and initialize the page.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', Category::class);
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
     * Clear search filter.
     */
    public function resetFilters(): void
    {
        $this->reset(['search']);
        $this->resetPage();
    }

    /**
     * Delete a category.
     */
    public function delete(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        Gate::authorize('delete', $category);

        // Check if category is used by tree types
        if ($category->treeTypes()->exists()) {
            Flux::toast(variant: 'danger', text: __('This category cannot be deleted because it is still used by tree types.'));

            return;
        }

        $category->delete();

        Flux::toast(variant: 'success', text: __('messages.deleted', ['item' => __('category')]));

        $this->resetPage();
    }

    /**
     * Render the category list.
     */
    public function render()
    {
        $sort = in_array($this->sort, ['name', 'created_at'], true) ? $this->sort : 'name';
        $direction = $this->direction === 'asc' ? 'asc' : 'desc';

        $query = Category::query()
            ->withCount('treeTypes')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($q): void {
                    $q->where('name', 'ilike', '%'.$this->search.'%')
                        ->orWhere('description', 'ilike', '%'.$this->search.'%');
                });
            })
            ->orderBy($sort, $direction);

        return $this->view([
            'categories' => $query->paginate(15),
        ]);
    }
}; ?>

<section class="w-full">
    <x-page-header :title="__('Categories')" :description="__('Manage tree categories for classification.')">
        @can('create', App\Models\Category::class)
            <flux:button :href="route('categories.create')" variant="primary" icon="plus" wire:navigate>
                {{ __('Add category') }}
            </flux:button>
        @endcan
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" icon="magnifying-glass" placeholder="{{ __('Search categories...') }}" class="lg:col-span-2" />
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <flux:text>{{ trans_choice(':count category|:count categories', $categories->total(), ['count' => $categories->total()]) }}</flux:text>

        @if ($search !== '')
            <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="arrow-path">
                {{ __('Reset filters') }}
            </flux:button>
        @endif
    </div>

    @if ($categories->isEmpty())
        <x-empty-state icon="tag" :title="__('No categories found.')" :description="__('Adjust your search or add a new category.')">
            @can('create', App\Models\Category::class)
                <flux:button :href="route('categories.create')" variant="primary" icon="plus" wire:navigate>
                    {{ __('Add category') }}
                </flux:button>
            @endcan
        </x-empty-state>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:table :paginate="$categories">
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sort === 'name'" :direction="$sort === 'name' ? $direction : null" wire:click="sortBy('name')">
                        {{ __('Name') }}
                    </flux:table.column>
                    <flux:table.column>{{ __('Description') }}</flux:table.column>
                    <flux:table.column align="center">{{ __('Tree Types') }}</flux:table.column>
                    <flux:table.column sortable :sorted="$sort === 'created_at'" :direction="$sort === 'created_at' ? $direction : null" wire:click="sortBy('created_at')">
                        {{ __('Created') }}
                    </flux:table.column>
                    <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($categories as $category)
                        <flux:table.row :key="$category->id">
                            <flux:table.cell variant="strong">{{ $category->name }}</flux:table.cell>
                            <flux:table.cell class="max-w-md truncate">{{ $category->description ?: '—' }}</flux:table.cell>
                            <flux:table.cell align="center">
                                <flux:badge size="sm" color="zinc">{{ $category->tree_types_count }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ Format::date($category->created_at) }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-1">
                                    @can('update', $category)
                                        <flux:button :href="route('categories.edit', $category)" variant="ghost" size="sm" icon="pencil-square" :aria-label="__('Edit category')" wire:navigate />
                                    @endcan
                                    @can('delete', $category)
                                        <flux:button wire:click="delete({{ $category->id }})" wire:confirm="{{ __('messages.confirm_delete', ['item' => __('category')]) }}" variant="ghost" size="sm" icon="trash" :aria-label="__('Delete category')" />
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
