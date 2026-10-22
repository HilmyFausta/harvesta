<?php

use App\Models\Block;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Block')] class extends Component {
    public string $name = '';

    public string $area_sqm = '';

    public string $location_note = '';

    public string $description = '';

    /**
     * Authorize and initialize the form.
     */
    public function mount(): void
    {
        Gate::authorize('create', Block::class);
    }

    /**
     * Store the block.
     */
    public function save(): void
    {
        Gate::authorize('create', Block::class);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('blocks', 'name')],
            'area_sqm' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'location_note' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $validated['area_sqm'] = $this->area_sqm !== '' ? $this->area_sqm : null;
        $validated['location_note'] = $this->location_note !== '' ? $this->location_note : null;
        $validated['description'] = $this->description !== '' ? $this->description : null;

        Block::create($validated);

        Flux::toast(variant: 'success', text: __('messages.created', ['item' => __('block')]));

        $this->redirectRoute('blocks.index', navigate: true);
    }

    /**
     * Render the form.
     */
    public function render()
    {
        return $this->view();
    }
}; ?>

<section class="w-full">
    <x-page-header :title="__('Create Block')" :description="__('Add a new land block to your orchard.')" />

    <form wire:submit="save" class="max-w-3xl space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @include('pages.blocks.partials.form')
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:button type="submit" variant="primary">
                {{ __('Create block') }}
            </flux:button>

            <flux:button :href="route('blocks.index')" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</section>
