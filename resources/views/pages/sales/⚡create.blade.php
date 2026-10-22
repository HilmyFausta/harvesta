<?php

use App\Models\Harvest;
use App\Models\Sale;
use App\Services\SaleService;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Sale')] class extends Component {
    public string $harvest_id = '';

    public string $buyer_name = '';

    public string $sold_on = '';

    public string $quantity = '';

    public string $unit_price = '';

    public string $notes = '';

    /**
     * Authorize and initialize the form.
     */
    public function mount(): void
    {
        Gate::authorize('create', Sale::class);

        $this->harvest_id = (string) request()->query('harvest_id', '');
        $this->sold_on = now()->format('Y-m-d');
    }

    /**
     * Store the sale.
     */
    public function save(SaleService $sales): void
    {
        Gate::authorize('create', Sale::class);

        $validated = $this->validate([
            'harvest_id' => ['required', 'integer', 'exists:harvests,id'],
            'buyer_name' => ['required', 'string', 'max:150'],
            'sold_on' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'unit_price' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $validated['notes'] = $this->notes !== '' ? $this->notes : null;

        $sales->create($validated);

        Flux::toast(variant: 'success', text: __('messages.created', ['item' => __('sale')]));

        $this->redirectRoute('sales.index', navigate: true);
    }

    /**
     * Render the form.
     */
    public function render()
    {
        return $this->view([
            'harvests' => Harvest::query()
                ->with(['tree.treeType', 'tree.block'])
                ->withSum('sales', 'quantity')
                ->orderByDesc('harvested_on')
                ->get(),
            'totalPreview' => round((float) $this->quantity * (float) $this->unit_price, 2),
        ]);
    }
}; ?>

<section class="w-full">
    <x-page-header :title="__('Create Sale')" :description="__('Record a sale against an unsold harvest.')" />

    <form wire:submit="save" class="max-w-3xl space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @include('pages.sales.partials.form')
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:button type="submit" variant="primary">
                {{ __('Create sale') }}
            </flux:button>

            <flux:button :href="route('sales.index')" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</section>
