<?php

use App\Models\Sale;
use App\Models\TreeType;
use App\Support\Format;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Sales')] class extends Component {
    use WithPagination;

    public string $buyer = '';

    public string $tree_type_id = '';

    public string $from = '';

    public string $to = '';

    public string $sort = 'sold_on';

    public string $direction = 'desc';

    /**
     * Authorize and initialize the page.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', Sale::class);
    }

    /**
     * Reset pagination when filters change.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['from', 'to'], true) && $this->{$property} !== '') {
            $this->validateOnly($property, [$property => ['date']]);
        }

        if (in_array($property, ['buyer', 'tree_type_id', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Sort by a whitelisted column.
     */
    public function sortBy(string $column): void
    {
        if (! in_array($column, ['sold_on', 'quantity', 'total_amount'], true)) {
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
        $this->reset(['buyer', 'tree_type_id', 'from', 'to']);
        $this->resetPage();
    }

    /**
     * Delete a sale.
     */
    public function delete(int $saleId): void
    {
        $sale = Sale::findOrFail($saleId);

        Gate::authorize('delete', $sale);

        $sale->delete();

        Flux::toast(variant: 'success', text: __('messages.deleted', ['item' => __('sale')]));

        $this->resetPage();
    }

    /**
     * Render the sales list.
     */
    public function render()
    {
        $sort = in_array($this->sort, ['sold_on', 'quantity', 'total_amount'], true) ? $this->sort : 'sold_on';
        $direction = $this->direction === 'asc' ? 'asc' : 'desc';

        $query = Sale::query()
            ->with(['harvest.tree.treeType', 'harvest.tree.block', 'user'])
            ->when($this->buyer !== '', function ($query): void {
                $query->where('buyer_name', 'ilike', '%'.$this->buyer.'%');
            })
            ->when($this->tree_type_id !== '', function ($query): void {
                $query->whereHas('harvest.tree.treeType', fn ($query) => $query->where('id', $this->tree_type_id));
            })
            ->when($this->from !== '', fn ($query) => $query->whereDate('sold_on', '>=', $this->from))
            ->when($this->to !== '', fn ($query) => $query->whereDate('sold_on', '<=', $this->to))
            ->orderBy($sort, $direction);

        $total = (clone $query)->reorder()->sum('total_amount');

        return $this->view([
            'sales' => $query->paginate(15),
            'total' => $total,
            'treeTypes' => TreeType::query()->orderBy('name')->get(),
        ]);
    }
}; ?>

<section class="w-full">
    <x-page-header :title="__('Sales')" :description="__('Track harvest sales and revenue by buyer, date, and tree type.')">
        <flux:button :href="route('sales.create')" variant="primary" icon="plus" wire:navigate>
            {{ __('Add sale') }}
        </flux:button>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="buyer" :label="__('Buyer')" icon="magnifying-glass" placeholder="{{ __('Search buyers...') }}" />

        <flux:select wire:model.live="tree_type_id" :label="__('Tree type')">
            <flux:select.option value="">{{ __('All tree types') }}</flux:select.option>
            @foreach ($treeTypes as $treeType)
                <flux:select.option value="{{ $treeType->id }}">{{ $treeType->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input wire:model.live="from" type="date" :label="__('From')" />
        <flux:input wire:model.live="to" type="date" :label="__('To')" />
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-1">
            <flux:text>
                {{ __('Total revenue') }}:
                <span class="font-semibold text-zinc-800 dark:text-white">{{ Format::money($total) }}</span>
            </flux:text>

            <flux:text>{{ trans_choice(':count sale|:count sales', $sales->total()) }}</flux:text>
        </div>

        <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="arrow-path">
            {{ __('Reset filters') }}
        </flux:button>
    </div>

    @if ($sales->isEmpty())
        <x-empty-state icon="shopping-cart" :title="__('No sales found.')" :description="__('Adjust your filters or record a new sale.')">
            <flux:button :href="route('sales.create')" variant="primary" icon="plus" wire:navigate>
                {{ __('Add sale') }}
            </flux:button>
        </x-empty-state>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:table :paginate="$sales">
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sort === 'sold_on'" :direction="$sort === 'sold_on' ? $direction : null" wire:click="sortBy('sold_on')">
                        {{ __('Sold on') }}
                    </flux:table.column>
                    <flux:table.column>{{ __('Buyer') }}</flux:table.column>
                    <flux:table.column>{{ __('Tree') }}</flux:table.column>
                    <flux:table.column>{{ __('Tree type') }}</flux:table.column>
                    <flux:table.column sortable align="end" :sorted="$sort === 'quantity'" :direction="$sort === 'quantity' ? $direction : null" wire:click="sortBy('quantity')">
                        {{ __('Quantity') }}
                    </flux:table.column>
                    <flux:table.column align="end">{{ __('Unit price') }}</flux:table.column>
                    <flux:table.column sortable align="end" :sorted="$sort === 'total_amount'" :direction="$sort === 'total_amount' ? $direction : null" wire:click="sortBy('total_amount')">
                        {{ __('Total') }}
                    </flux:table.column>
                    <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($sales as $sale)
                        <flux:table.row :key="$sale->id">
                            <flux:table.cell variant="strong">{{ Format::date($sale->sold_on) }}</flux:table.cell>
                            <flux:table.cell>{{ $sale->buyer_name }}</flux:table.cell>
                            <flux:table.cell>{{ $sale->harvest->tree->code }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="zinc">{{ $sale->harvest->tree->treeType->name }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                {{ Format::number($sale->quantity) }} {{ $sale->harvest->unit->label() }}
                            </flux:table.cell>
                            <flux:table.cell align="end">{{ Format::money($sale->unit_price) }}</flux:table.cell>
                            <flux:table.cell align="end" variant="strong">{{ Format::money($sale->total_amount) }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-1">
                                    <flux:button :href="route('sales.edit', $sale)" variant="ghost" size="sm" icon="pencil-square" :aria-label="__('Edit sale')" wire:navigate />
                                    <flux:button wire:click="delete({{ $sale->id }})" wire:confirm="{{ __('messages.confirm_delete', ['item' => __('sale')]) }}" variant="ghost" size="sm" icon="trash" :aria-label="__('Delete sale')" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @endif
</section>
