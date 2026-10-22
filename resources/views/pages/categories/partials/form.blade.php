<div class="grid gap-6">
    <flux:input wire:model="name" :label="__('Name')" type="text" maxlength="100" required placeholder="{{ __('e.g., Fruit, Timber, Ornamental') }}" />

    <flux:textarea wire:model="description" :label="__('Description')" rows="4" :description="__('Optional notes about this category.')" />
</div>
