<div class="grid gap-6 sm:grid-cols-2">
    <flux:select wire:model="category_id" :label="__('Category')" class="sm:col-span-2" required>
        <flux:select.option value="">{{ __('Select a category') }}</flux:select.option>
        @foreach ($categories as $category)
            <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
        @endforeach
    </flux:select>

    <flux:input wire:model="name" :label="__('Name')" type="text" maxlength="100" required placeholder="{{ __('e.g., Durian, Mango, Mahogany') }}" />

    <flux:input wire:model="scientific_name" :label="__('Scientific name')" type="text" maxlength="150" placeholder="{{ __('e.g., Durio zibethinus (optional)') }}" />

    <flux:textarea wire:model="description" :label="__('Description')" rows="4" :description="__('Optional notes about this tree type.')" class="sm:col-span-2" />
</div>
