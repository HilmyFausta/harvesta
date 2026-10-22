<?php

use App\Enums\ExpenseCategory;
use App\Models\Block;
use App\Models\Expense;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Expense')] class extends Component {
    public string $block_id = '';

    public string $category = '';

    public string $amount = '';

    public string $spent_on = '';

    public string $description = '';

    /**
     * Authorize and initialize the form.
     */
    public function mount(): void
    {
        Gate::authorize('create', Expense::class);

        $this->spent_on = now()->format('Y-m-d');
    }

    /**
     * Store the expense.
     */
    public function save(): void
    {
        Gate::authorize('create', Expense::class);

        $validated = $this->validate([
            'block_id' => $this->block_id === ''
                ? ['nullable']
                : ['required', 'integer', 'exists:blocks,id'],
            'category' => ['required', Rule::enum(ExpenseCategory::class)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'spent_on' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['block_id'] = $this->block_id === '' ? null : (int) $this->block_id;
        $validated['description'] = $this->description !== '' ? $this->description : null;

        Expense::create($validated);

        Flux::toast(variant: 'success', text: __('messages.created', ['item' => __('expense')]));

        $this->redirectRoute('expenses.index', navigate: true);
    }

    /**
     * Render the form.
     */
    public function render()
    {
        return $this->view([
            'blocks' => Block::query()->orderBy('name')->get(),
            'categories' => ExpenseCategory::options(),
        ]);
    }
}; ?>

<section class="w-full">
    <x-page-header :title="__('Create Expense')" :description="__('Record a new farm cost.')" />

    <form wire:submit="save" class="max-w-3xl space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @include('pages.expenses.partials.form')
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:button type="submit" variant="primary">
                {{ __('Create expense') }}
            </flux:button>

            <flux:button :href="route('expenses.index')" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</section>
