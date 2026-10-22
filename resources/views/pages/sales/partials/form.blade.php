<div class="grid gap-6 sm:grid-cols-2">
    <flux:select wire:model.live="harvest_id" :label="__('Harvest')" class="sm:col-span-2" required>
        <flux:select.option value="">{{ __('Select a harvest') }}</flux:select.option>
        @foreach ($harvests as $harvest)
            <flux:select.option value="{{ $harvest->id }}">
                {{ $harvest->tree->code }} — {{ $harvest->tree->treeType->name }} ({{ $harvest->tree->block->name }}) — {{ \App\Support\Format::number($harvest->remainingQuantity()) }} {{ $harvest->unit->label() }} {{ __('unsold') }}
            </flux:select.option>
        @endforeach
    </flux:select>

    <flux:input wire:model="buyer_name" :label="__('Buyer name')" type="text" required autocomplete="organization" />

    <flux:input wire:model="sold_on" :label="__('Sold on')" type="date" required />

    <flux:input wire:model.live="quantity" :label="__('Quantity')" type="number" step="0.01" min="0.01" required />

    <flux:input wire:model.live="unit_price" :label="__('Unit price')" type="number" step="0.01" min="0.01" required />

    <flux:textarea wire:model="notes" :label="__('Notes')" rows="3" :description="__('Optional notes about this sale.')" class="sm:col-span-2" />

    <div class="sm:col-span-2">
        <flux:text>
            {{ __('Total amount') }}:
            <span class="font-semibold text-zinc-800 dark:text-white">{{ \App\Support\Format::money($totalPreview) }}</span>
        </flux:text>
    </div>
</div>
