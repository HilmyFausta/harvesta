<div class="grid gap-6 sm:grid-cols-2">
    <flux:input wire:model="form.code" :label="__('Code')" type="text" maxlength="30"
        :placeholder="$form->tree ? null : __('Leave blank to generate one')"
        :description="$form->tree ? null : __('Optional. A code such as T-0041 is generated when left blank.')"
        :required="(bool) $form->tree" />

    <flux:select wire:model="form.status" :label="__('Status')" required>
        <flux:select.option value="">{{ __('Select a status') }}</flux:select.option>
        @foreach ($statuses as $value => $label)
            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
        @endforeach
    </flux:select>

    <flux:select wire:model="form.tree_type_id" :label="__('Tree type')" required>
        <flux:select.option value="">{{ __('Select a tree type') }}</flux:select.option>
        @foreach ($treeTypes as $treeType)
            <flux:select.option value="{{ $treeType->id }}">{{ $treeType->name }} ({{ $treeType->category->name }})</flux:select.option>
        @endforeach
    </flux:select>

    <flux:select wire:model="form.block_id" :label="__('Block')" required>
        <flux:select.option value="">{{ __('Select a block') }}</flux:select.option>
        @foreach ($blocks as $block)
            <flux:select.option value="{{ $block->id }}">{{ $block->name }}</flux:select.option>
        @endforeach
    </flux:select>

    <flux:input wire:model="form.planted_on" :label="__('Planted on')" type="date" max="{{ today()->format('Y-m-d') }}" required />

    <flux:input wire:model="form.height_cm" :label="__('Height (cm)')" type="number" step="0.01" min="0" :placeholder="__('Optional')" />

    <flux:textarea wire:model="form.notes" :label="__('Notes')" rows="4" :description="__('Optional. Anything worth remembering about this tree.')" class="sm:col-span-2" />

    <div class="space-y-3 sm:col-span-2">
        <flux:input wire:model="form.photo" type="file" accept="image/jpeg,image/png,image/webp" :label="__('Photo')" :description="__('Optional. JPG, PNG, or WebP up to 2 MB.')" />

        <div wire:loading wire:target="form.photo" class="text-sm text-zinc-500">{{ __('Uploading...') }}</div>

        @php
            $preview = null;

            if ($form->photo && ! $errors->has('form.photo')) {
                try {
                    $preview = $form->photo->temporaryUrl();
                } catch (\Throwable) {
                    $preview = null;
                }
            } elseif ($form->tree?->photo_url && ! $form->remove_photo) {
                $preview = $form->tree->photo_url;
            }
        @endphp

        @if ($preview)
            <img src="{{ $preview }}" alt="{{ __('Tree photo preview') }}" class="h-40 w-auto rounded-lg border border-zinc-200 object-cover dark:border-zinc-700">
        @endif

        @if ($form->tree?->photo_path && ! $form->photo)
            <flux:checkbox wire:model.live="form.remove_photo" :label="__('Remove the current photo')" />
        @endif
    </div>
</div>
