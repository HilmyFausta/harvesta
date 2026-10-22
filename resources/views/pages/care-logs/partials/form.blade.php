<div class="grid gap-6 sm:grid-cols-2">
    <flux:select wire:model="tree_id" :label="__('Tree')" class="sm:col-span-2" required>
        <flux:select.option value="">{{ __('Select a tree') }}</flux:select.option>
        @foreach ($trees as $tree)
            <flux:select.option value="{{ $tree->id }}">
                {{ $tree->code }} - {{ $tree->treeType->name }} ({{ $tree->block->name }})
            </flux:select.option>
        @endforeach
    </flux:select>

    <flux:select wire:model="activity_type" :label="__('Activity type')" required>
        <flux:select.option value="">{{ __('Select an activity type') }}</flux:select.option>
        @foreach ($activityTypes as $value => $label)
            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
        @endforeach
    </flux:select>

    <flux:input wire:model="performed_on" :label="__('Performed on')" type="date" required />

    <flux:input wire:model="next_due_on" :label="__('Next due on')" type="date" :description="__('Optional. When this activity should be done again.')" />

    <flux:textarea wire:model="notes" :label="__('Notes')" rows="4" :description="__('Optional notes about this care activity.')" class="sm:col-span-2" />
</div>
