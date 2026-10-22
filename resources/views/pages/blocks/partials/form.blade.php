<div class="grid gap-6 sm:grid-cols-2">
    <flux:input wire:model="name" :label="__('Name')" type="text" maxlength="100" required placeholder="{{ __('e.g., North Field, Block A') }}" class="sm:col-span-2" />

    <flux:input wire:model="area_sqm" :label="__('Area (m²)')" type="number" step="0.01" min="0" placeholder="{{ __('Optional') }}" />

    <flux:input wire:model="location_note" :label="__('Location note')" type="text" maxlength="255" placeholder="{{ __('e.g., Near the main gate (optional)') }}" />

    <flux:textarea wire:model="description" :label="__('Description')" rows="4" :description="__('Optional notes about this block.')" class="sm:col-span-2" />
</div>
