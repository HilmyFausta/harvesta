<div class="grid gap-6 sm:grid-cols-2">
    <flux:select wire:model="block_id" :label="__('Block')" :description="__('Leave empty for a general expense.')">
        <flux:select.option value="">{{ __('General (no block)') }}</flux:select.option>
        @foreach ($blocks as $block)
            <flux:select.option value="{{ $block->id }}">{{ $block->name }}</flux:select.option>
        @endforeach
    </flux:select>

    <flux:select wire:model="category" :label="__('Category')">
        <flux:select.option value="">{{ __('Select a category') }}</flux:select.option>
        @foreach ($categories as $value => $label)
            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
        @endforeach
    </flux:select>

    <flux:input wire:model="amount" :label="__('Amount')" type="number" step="0.01" min="0.01" required />

    <flux:input wire:model="spent_on" :label="__('Date')" type="date" required />

    <flux:textarea wire:model="description" :label="__('Description')" rows="3" :description="__('Optional notes about this expense.')" class="sm:col-span-2" />
</div>
