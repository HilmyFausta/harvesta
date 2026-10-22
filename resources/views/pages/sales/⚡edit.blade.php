<?php

use App\Models\Harvest;
use App\Models\Sale;
use App\Services\SaleService;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Sale')] class extends Component {
    public Sale $sale;

    public string $harvest_id = '';

    public string $buyer_name = '';

    public string $sold_on = '';

    public string $quantity = '';

    public string $unit_price = '';

    public string $notes = '';

    /**
     * Authorize and fill the form.
     */
    public function mount(Sale $sale): void
    {
        Gate::authorize('update', $sale);

        $this->sale = $sale;
        $this->harvest_id = (string) $sale->harvest_id;
        $this->buyer_name = $sale->buyer_name;
        $this->sold_on = $sale->sold_on->format('Y-m-d');
        $this->quantity = (string) $sale->quantity;
        $this->unit_price = (string) $sale->unit_price;
        $this->notes = $sale->notes ?? '';
    }

    /**
     * Update the sale.
     */
    public function save(SaleService $sales): void
    {
        Gate::authorize('update', $this->sale);

        $validated = $this->validate([
            'harvest_id' => ['required', 'integer', 'exists:harvests,id'],
            'buyer_name' => ['required', 'string', 'max:150'],
            'sold_on' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'unit_price' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $validated['notes'] = $this->notes !== '' ? $this->notes : null;

        $sales->update($this->sale, $validated);

        Flux::toast(variant: 'success', text: __('messages.updated', ['item' => __('sale')]));

        $this->redirectRoute('sales.index', navigate: true);
    }

    /**
     * Render the form.
     */
    public function render()
    {
        $harvests = Harvest::query()
            ->with(['tree.treeType', 'tree.block'])
            ->withSum('sales', 'quantity')
            ->orderByDesc('harvested_on')
            ->get();

        // Show the current sale's quantity as available again while editing it.
        $harvests->each(function (Harvest $harvest): void {
            if ((int) $harvest->id === (int) $this->sale->harvest_id) {
                $harvest->sales_sum_quantity = (float) $harvest->sales_sum_quantity - (float) $this->sale->quantity;
            }
        });

        return $this->view([
            'harvests' => $harvests,
            'totalPreview' => round((float) $this->quantity * (float) $this->unit_price, 2),
        ]);
    }
}; ?>

<section class="w-full">
    <x-page-header :title="__('Edit Sale')" :description="__('Update the details of this sale.')" />

    <form wire:submit="save" class="max-w-3xl space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @include('pages.sales.partials.form')
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:button type="submit" variant="primary">
                {{ __('Save changes') }}
            </flux:button>

            <flux:button :href="route('sales.index')" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</section>
