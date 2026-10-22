<?php

use App\Enums\ExpenseCategory;
use App\Models\Block;
use App\Models\Expense;
use App\Support\Format;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Expenses')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $block_id = '';

    public string $category = '';

    public string $from = '';

    public string $to = '';

    public string $sort = 'spent_on';

    public string $direction = 'desc';

    /**
     * Authorize and initialize the page.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', Expense::class);
    }

    /**
     * Reset pagination when filters change.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['from', 'to'], true) && $this->{$property} !== '') {
            $this->validateOnly($property, [$property => ['date']]);
        }

        if (in_array($property, ['search', 'block_id', 'category', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Sort by a whitelisted column.
     */
    public function sortBy(string $column): void
    {
        if (! in_array($column, ['spent_on', 'category', 'amount'], true)) {
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
        $this->reset(['search', 'block_id', 'category', 'from', 'to']);
        $this->resetPage();
    }

    /**
     * Delete an expense.
     */
    public function delete(int $expenseId): void
    {
        $expense = Expense::findOrFail($expenseId);

        Gate::authorize('delete', $expense);

        $expense->delete();

        Flux::toast(variant: 'success', text: __('messages.deleted', ['item' => __('expense')]));

        $this->resetPage();
    }

    /**
     * Render the expense list.
     */
    public function render()
    {
        $sort = in_array($this->sort, ['spent_on', 'category', 'amount'], true) ? $this->sort : 'spent_on';
        $direction = $this->direction === 'asc' ? 'asc' : 'desc';

        $query = Expense::query()
            ->with('block')
            ->when($this->search !== '', function ($query): void {
                $query->where('description', 'ilike', '%'.$this->search.'%');
            })
            ->when($this->block_id === 'none', fn ($query) => $query->whereNull('block_id'))
            ->when($this->block_id !== '' && $this->block_id !== 'none', fn ($query) => $query->where('block_id', $this->block_id))
            ->when($this->category !== '', fn ($query) => $query->where('category', $this->category))
            ->when($this->from !== '', fn ($query) => $query->whereDate('spent_on', '>=', $this->from))
            ->when($this->to !== '', fn ($query) => $query->whereDate('spent_on', '<=', $this->to))
            ->orderBy($sort, $direction);

        $total = (clone $query)->sum('amount');

        return $this->view([
            'expenses' => $query->paginate(15),
            'total' => $total,
            'blocks' => Block::query()->orderBy('name')->get(),
            'categories' => ExpenseCategory::options(),
        ]);
    }
}; ?>

<section class="w-full">
    <x-page-header :title="__('Expenses')" :description="__('Track farm costs by block, category, and date.')">
        <flux:button :href="route('expenses.create')" variant="primary" icon="plus" wire:navigate>
            {{ __('Add expense') }}
        </flux:button>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" icon="magnifying-glass" placeholder="{{ __('Search descriptions...') }}" />

        <flux:select wire:model.live="block_id" :label="__('Block')">
            <flux:select.option value="">{{ __('All blocks') }}</flux:select.option>
            <flux:select.option value="none">{{ __('General (no block)') }}</flux:select.option>
            @foreach ($blocks as $block)
                <flux:select.option value="{{ $block->id }}">{{ $block->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="category" :label="__('Category')">
            <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
            @foreach ($categories as $value => $label)
                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input wire:model.live="from" type="date" :label="__('From')" />
        <flux:input wire:model.live="to" type="date" :label="__('To')" />
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-1">
            <flux:text>
                {{ __('Total') }}:
                <span class="font-semibold text-zinc-800 dark:text-white">{{ Format::money($total) }}</span>
            </flux:text>

            <flux:text>{{ trans_choice(':count expense|:count expenses', $expenses->total(), ['count' => $expenses->total()]) }}</flux:text>
        </div>

        <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="arrow-path">
            {{ __('Reset filters') }}
        </flux:button>
    </div>

    @if ($expenses->isEmpty())
        <x-empty-state icon="banknotes" :title="__('No expenses found.')" :description="__('Adjust your filters or add a new expense.')">
            <flux:button :href="route('expenses.create')" variant="primary" icon="plus" wire:navigate>
                {{ __('Add expense') }}
            </flux:button>
        </x-empty-state>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:table :paginate="$expenses">
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sort === 'spent_on'" :direction="$sort === 'spent_on' ? $direction : null" wire:click="sortBy('spent_on')">
                        {{ __('Date') }}
                    </flux:table.column>
                    <flux:table.column sortable :sorted="$sort === 'category'" :direction="$sort === 'category' ? $direction : null" wire:click="sortBy('category')">
                        {{ __('Category') }}
                    </flux:table.column>
                    <flux:table.column>{{ __('Block') }}</flux:table.column>
                    <flux:table.column sortable align="end" :sorted="$sort === 'amount'" :direction="$sort === 'amount' ? $direction : null" wire:click="sortBy('amount')">
                        {{ __('Amount') }}
                    </flux:table.column>
                    <flux:table.column>{{ __('Description') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($expenses as $expense)
                        <flux:table.row :key="$expense->id">
                            <flux:table.cell variant="strong">{{ Format::date($expense->spent_on) }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="zinc">{{ $expense->category->label() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $expense->block?->name ?? __('General') }}</flux:table.cell>
                            <flux:table.cell align="end" variant="strong">{{ Format::money($expense->amount) }}</flux:table.cell>
                            <flux:table.cell class="max-w-xs truncate">{{ $expense->description ?: '—' }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-1">
                                    <flux:button :href="route('expenses.edit', $expense)" variant="ghost" size="sm" icon="pencil-square" :aria-label="__('Edit expense')" wire:navigate />
                                    <flux:button wire:click="delete({{ $expense->id }})" wire:confirm="{{ __('messages.confirm_delete', ['item' => __('expense')]) }}" variant="ghost" size="sm" icon="trash" :aria-label="__('Delete expense')" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @endif
</section>
