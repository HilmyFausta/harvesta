<?php

use App\Enums\HarvestGrade;
use App\Models\Block;
use App\Models\Harvest;
use App\Models\Tree;
use App\Models\TreeType;
use App\Support\Format;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Harvests')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $tree_id = '';

    public string $block_id = '';

    public string $tree_type_id = '';

    public string $grade = '';

    public string $from = '';

    public string $to = '';

    public string $sort = 'harvested_on';

    public string $direction = 'desc';

    /**
     * Authorize and initialize the page.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', Harvest::class);
    }

    /**
     * Reset pagination when filters change.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['from', 'to'], true) && $this->{$property} !== '') {
            $this->validateOnly($property, [$property => ['date']]);
        }

        if (in_array($property, ['search', 'tree_id', 'block_id', 'tree_type_id', 'grade', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Sort by a whitelisted column.
     */
    public function sortBy(string $column): void
    {
        if (! in_array($column, ['harvested_on', 'quantity', 'grade'], true)) {
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
        $this->reset(['search', 'tree_id', 'block_id', 'tree_type_id', 'grade', 'from', 'to']);
        $this->resetPage();
    }

    /**
     * Delete a harvest.
     */
    public function delete(int $harvestId): void
    {
        $harvest = Harvest::findOrFail($harvestId);

        Gate::authorize('delete', $harvest);

        // Check if harvest has sales
        if ($harvest->sales()->exists()) {
            Flux::toast(variant: 'danger', text: __('This harvest cannot be deleted because it is associated with one or more sales.'));

            return;
        }

        $harvest->delete();

        Flux::toast(variant: 'success', text: __('messages.deleted', ['item' => __('harvest')]));

        $this->resetPage();
    }

    /**
     * Render the harvest list.
     */
    public function render()
    {
        $sort = in_array($this->sort, ['harvested_on', 'quantity', 'grade'], true) ? $this->sort : 'harvested_on';
        $direction = $this->direction === 'asc' ? 'asc' : 'desc';

        $query = Harvest::query()
            ->with(['tree.block', 'tree.treeType', 'user'])
            ->when($this->search !== '', function ($query): void {
                $query->where('notes', 'ilike', '%'.$this->search.'%');
            })
            ->when($this->tree_id !== '', fn ($query) => $query->where('tree_id', $this->tree_id))
            ->when($this->block_id !== '', function ($query): void {
                $query->whereHas('tree', fn ($q) => $q->where('block_id', $this->block_id));
            })
            ->when($this->tree_type_id !== '', function ($query): void {
                $query->whereHas('tree', fn ($q) => $q->where('tree_type_id', $this->tree_type_id));
            })
            ->when($this->grade !== '', fn ($query) => $query->where('grade', $this->grade))
            ->when($this->from !== '', fn ($query) => $query->whereDate('harvested_on', '>=', $this->from))
            ->when($this->to !== '', fn ($query) => $query->whereDate('harvested_on', '<=', $this->to))
            ->orderBy($sort, $direction);

        // Calculate totals per unit
        $totals = (clone $query)
            ->reorder()
            ->selectRaw('unit, SUM(quantity) as total')
            ->groupBy('unit')
            ->pluck('total', 'unit');

        return $this->view([
            'harvests' => $query->paginate(15),
            'totals' => $totals,
            'trees' => Tree::query()->with(['block', 'treeType'])->orderBy('code')->get(),
            'blocks' => Block::query()->orderBy('name')->get(),
            'treeTypes' => TreeType::query()->with('category')->orderBy('name')->get(),
            'grades' => HarvestGrade::options(),
        ]);
    }
}; ?>

<section class="w-full">
    <x-page-header :title="__('Harvests')" :description="__('Track harvest yields by tree, date, and quality grade.')">
        <flux:button :href="route('harvests.create')" variant="primary" icon="plus" wire:navigate>
            {{ __('Add harvest') }}
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

        <flux:select wire:model.live="tree_type_id" :label="__('Tree type')">
            <flux:select.option value="">{{ __('All tree types') }}</flux:select.option>
            @foreach ($treeTypes as $treeType)
                <flux:select.option value="{{ $treeType->id }}">{{ $treeType->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="grade" :label="__('Grade')">
            <flux:select.option value="">{{ __('All grades') }}</flux:select.option>
            @foreach ($grades as $value => $label)
                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="sm:col-span-2 lg:col-span-1">
            <flux:input wire:model.live="from" type="date" :label="__('From')" />
        </div>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
        <flux:input wire:model.live="to" type="date" :label="__('To')" />
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-1">
            @if ($totals->isNotEmpty())
                @foreach ($totals as $unit => $total)
                    <flux:text>
                        {{ __('Total') }} ({{ $unit }}):
                        <span class="font-semibold text-zinc-800 dark:text-white">{{ Format::number($total) }}</span>
                    </flux:text>
                @endforeach
            @endif

            <flux:text>{{ trans_choice(':count harvest|:count harvests', $harvests->total(), ['count' => $harvests->total()]) }}</flux:text>
        </div>

        <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="arrow-path">
            {{ __('Reset filters') }}
        </flux:button>
    </div>

    @if ($harvests->isEmpty())
        <x-empty-state icon="archive-box" :title="__('No harvests found.')" :description="__('Adjust your filters or add a new harvest.')">
            <flux:button :href="route('harvests.create')" variant="primary" icon="plus" wire:navigate>
                {{ __('Add harvest') }}
            </flux:button>
        </x-empty-state>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:table :paginate="$harvests">
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sort === 'harvested_on'" :direction="$sort === 'harvested_on' ? $direction : null" wire:click="sortBy('harvested_on')">
                        {{ __('Harvested on') }}
                    </flux:table.column>
                    <flux:table.column>{{ __('Tree') }}</flux:table.column>
                    <flux:table.column>{{ __('Block') }}</flux:table.column>
                    <flux:table.column>{{ __('Tree type') }}</flux:table.column>
                    <flux:table.column sortable align="end" :sorted="$sort === 'quantity'" :direction="$sort === 'quantity' ? $direction : null" wire:click="sortBy('quantity')">
                        {{ __('Quantity') }}
                    </flux:table.column>
                    <flux:table.column sortable :sorted="$sort === 'grade'" :direction="$sort === 'grade' ? $direction : null" wire:click="sortBy('grade')">
                        {{ __('Grade') }}
                    </flux:table.column>
                    <flux:table.column>{{ __('Notes') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($harvests as $harvest)
                        <flux:table.row :key="$harvest->id">
                            <flux:table.cell variant="strong">{{ Format::date($harvest->harvested_on) }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="font-medium text-zinc-800 dark:text-white">{{ $harvest->tree->code }}</div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $harvest->tree->block->name }}</flux:table.cell>
                            <flux:table.cell>{{ $harvest->tree->treeType->name }}</flux:table.cell>
                            <flux:table.cell align="end" variant="strong">{{ Format::number($harvest->quantity) }} {{ $harvest->unit->label() }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="match($harvest->grade->value) {
                                    'premium' => 'green',
                                    'good' => 'blue',
                                    'fair' => 'yellow',
                                    'poor' => 'red',
                                }">{{ $harvest->grade->label() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="max-w-xs truncate">{{ $harvest->notes ?: '—' }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-1">
                                    @can('update', $harvest)
                                        <flux:button :href="route('harvests.edit', $harvest)" variant="ghost" size="sm" icon="pencil-square" :aria-label="__('Edit harvest')" wire:navigate />
                                    @endcan
                                    @can('delete', $harvest)
                                        <flux:button wire:click="delete({{ $harvest->id }})" wire:confirm="{{ __('messages.confirm_delete', ['item' => __('harvest')]) }}" variant="ghost" size="sm" icon="trash" :aria-label="__('Delete harvest')" />
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
