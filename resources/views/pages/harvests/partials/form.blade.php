<div class="grid gap-6 sm:grid-cols-2">
    <flux:select wire:model="tree_id" :label="__('Tree')" class="sm:col-span-2" required>
        <flux:select.option value="">{{ __('Select a tree') }}</flux:select.option>
        @foreach ($trees as $tree)
            <flux:select.option value="{{ $tree->id }}">
                {{ $tree->code }} - {{ $tree->treeType->name }} ({{ $tree->block->name }})
            </flux:select.option>
        @endforeach
    </flux:select>

    <flux:input wire:model="harvested_on" :label="__('Harvested on')" type="date" required />

    <flux:input wire:model="quantity" :label="__('Quantity')" type="number" step="0.01" min="0.01" required />

    <flux:select wire:model="unit" :label="__('Unit')" required>
        <flux:select.option value="">{{ __('Select a unit') }}</flux:select.option>
        @foreach ($units as $value => $label)
            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
        @endforeach
    </flux:select>

    <flux:select wire:model="grade" :label="__('Grade')" required>
        <flux:select.option value="">{{ __('Select a grade') }}</flux:select.option>
        @foreach ($grades as $value => $label)
            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
        @endforeach
    </flux:select>

    <flux:textarea wire:model="notes" :label="__('Notes')" rows="4" :description="__('Optional notes about this harvest.')" class="sm:col-span-2" />
</div>
