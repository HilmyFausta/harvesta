<?php

use App\Enums\TreeStatus;
use App\Models\Block;
use App\Models\Category;
use App\Models\Tree;
use App\Models\TreeType;
use App\Support\Format;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Trees')] class extends Component {
    use WithPagination;

    protected const SORTABLE = ['code', 'planted_on'];

    #[Url(except: '')]
    public string $search = '';

    #[Url(as: 'block', except: '')]
    public string $block_id = '';

    #[Url(as: 'category', except: '')]
    public string $category_id = '';

    #[Url(as: 'type', except: '')]
    public string $tree_type_id = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: 'code')]
    public string $sort = 'code';

    #[Url(except: 'asc')]
    public string $direction = 'asc';

    /**
     * Authorize the page.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', Tree::class);
    }

    /**
     * Reset pagination when a filter changes; a new category clears an unrelated tree type.
     */
    public function updated(string $property): void
    {
        if ($property === 'category_id') {
            $this->tree_type_id = '';
        }

        if (in_array($property, ['search', 'block_id', 'category_id', 'tree_type_id', 'status'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Sort by a whitelisted column.
     */
    public function sortBy(string $column): void
    {
        if (! in_array($column, self::SORTABLE, true)) {
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
        $this->reset(['search', 'block_id', 'category_id', 'tree_type_id', 'status']);
        $this->resetPage();
    }

    /**
     * Soft delete a tree (admin only).
     */
    public function delete(int $treeId): void
    {
        $tree = Tree::findOrFail($treeId);

        Gate::authorize('delete', $tree);

        $tree->delete();

        Flux::toast(variant: 'success', text: __('messages.deleted', ['item' => __('tree')]));
    }

    /**
     * Render the tree list.
     */
    public function render()
    {
        $sort = in_array($this->sort, self::SORTABLE, true) ? $this->sort : 'code';
        $direction = $this->direction === 'desc' ? 'desc' : 'asc';

        $trees = Tree::query()
            ->with(['treeType.category', 'block'])
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('code', 'ilike', '%'.$this->search.'%')
                        ->orWhere('notes', 'ilike', '%'.$this->search.'%');
                });
            })
            ->when($this->block_id !== '', fn (Builder $query) => $query->where('block_id', (int) $this->block_id))
            ->when($this->tree_type_id !== '', fn (Builder $query) => $query->where('tree_type_id', (int) $this->tree_type_id))
            ->when($this->category_id !== '', fn (Builder $query) => $query->whereHas(
                'treeType', fn (Builder $query) => $query->where('category_id', (int) $this->category_id),
            ))
            ->when(TreeStatus::tryFrom($this->status), fn (Builder $query, TreeStatus $status) => $query->where('status', $status))
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->paginate(15);

        return $this->view([
            'trees' => $trees,
            'blocks' => Block::orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'treeTypes' => TreeType::query()
                ->when($this->category_id !== '', fn (Builder $query) => $query->where('category_id', (int) $this->category_id))
                ->orderBy('name')
                ->get(['id', 'name']),
            'statuses' => TreeStatus::options(),
            'filtered' => $this->search !== '' || $this->block_id !== '' || $this->category_id !== ''
                || $this->tree_type_id !== '' || $this->status !== '',
        ]);
    }
}; ?>

<section class="w-full">
    <x-page-header :title="__('Trees')" :description="__('Every tree in the orchard, with its type, block, and current status.')">
        @can('create', App\Models\Tree::class)
            <flux:button :href="route('trees.create')" variant="primary" icon="plus" wire:navigate>
                {{ __('Add tree') }}
            </flux:button>
        @endcan
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" icon="magnifying-glass" :placeholder="__('Code or notes...')" />

        <flux:select wire:model.live="block_id" :label="__('Block')">
            <flux:select.option value="">{{ __('All blocks') }}</flux:select.option>
            @foreach ($blocks as $block)
                <flux:select.option value="{{ $block->id }}">{{ $block->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="category_id" :label="__('Category')">
            <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
            @foreach ($categories as $category)
                <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="tree_type_id" :label="__('Tree type')">
            <flux:select.option value="">{{ __('All tree types') }}</flux:select.option>
            @foreach ($treeTypes as $treeType)
                <flux:select.option value="{{ $treeType->id }}">{{ $treeType->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="status" :label="__('Status')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach ($statuses as $value => $label)
                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <flux:text>{{ trans_choice(':count tree|:count trees', $trees->total(), ['count' => $trees->total()]) }}</flux:text>

        @if ($filtered)
            <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="arrow-path">
                {{ __('Reset filters') }}
            </flux:button>
        @endif
    </div>

    @if ($trees->isEmpty())
        <x-empty-state icon="tree-deciduous" :title="$filtered ? __('No trees match your filters.') : __('No trees yet.')" :description="$filtered ? __('Try another search or reset the filters.') : __('Add your first tree to start tracking its care and harvests.')">
            @if ($filtered)
                <flux:button wire:click="resetFilters" variant="ghost" icon="arrow-path">{{ __('Reset filters') }}</flux:button>
            @else
                @can('create', App\Models\Tree::class)
                    <flux:button :href="route('trees.create')" variant="primary" icon="plus" wire:navigate>
                        {{ __('Add tree') }}
                    </flux:button>
                @endcan
            @endif
        </x-empty-state>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:table :paginate="$trees">
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sort === 'code'" :direction="$sort === 'code' ? $direction : null" wire:click="sortBy('code')">
                        {{ __('Tree') }}
                    </flux:table.column>
                    <flux:table.column>{{ __('Type') }}</flux:table.column>
                    <flux:table.column>{{ __('Block') }}</flux:table.column>
                    <flux:table.column sortable :sorted="$sort === 'planted_on'" :direction="$sort === 'planted_on' ? $direction : null" wire:click="sortBy('planted_on')">
                        {{ __('Planted on') }}
                    </flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($trees as $tree)
                        <flux:table.row :key="$tree->id">
                            <flux:table.cell>
                                <a href="{{ route('trees.show', $tree) }}" class="flex items-center gap-3" wire:navigate>
                                    @if ($tree->photo_url)
                                        <img src="{{ $tree->photo_url }}" alt="{{ __('Photo of tree :code', ['code' => $tree->code]) }}" class="size-10 shrink-0 rounded-lg object-cover" loading="lazy">
                                    @else
                                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-leaf/10 text-leaf">
                                            <flux:icon.tree-deciduous class="size-5" />
                                        </span>
                                    @endif
                                    <span class="font-medium text-zinc-800 hover:text-leaf dark:text-white">{{ $tree->code }}</span>
                                </a>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div>{{ $tree->treeType->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $tree->treeType->category->name }}</div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $tree->block->name }}</flux:table.cell>
                            <flux:table.cell>{{ Format::date($tree->planted_on) }}</flux:table.cell>
                            <flux:table.cell><x-tree-status-badge :status="$tree->status" /></flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-1">
                                    <flux:button :href="route('trees.show', $tree)" variant="ghost" size="sm" icon="eye" :aria-label="__('View tree')" wire:navigate />
                                    @can('update', $tree)
                                        <flux:button :href="route('trees.edit', $tree)" variant="ghost" size="sm" icon="pencil-square" :aria-label="__('Edit tree')" wire:navigate />
                                    @endcan
                                    @can('delete', $tree)
                                        <flux:button wire:click="delete({{ $tree->id }})" wire:confirm="{{ __('messages.confirm_delete', ['item' => __('tree')]) }}" variant="ghost" size="sm" icon="trash" :aria-label="__('Delete tree')" />
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
