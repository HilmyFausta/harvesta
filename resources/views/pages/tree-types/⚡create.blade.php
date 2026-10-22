<?php

use App\Models\Category;
use App\Models\TreeType;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Tree Type')] class extends Component {
    public string $category_id = '';

    public string $name = '';

    public string $scientific_name = '';

    public string $description = '';

    /**
     * Authorize and initialize the form.
     */
    public function mount(): void
    {
        Gate::authorize('create', TreeType::class);
    }

    /**
     * Store the tree type.
     */
    public function save(): void
    {
        Gate::authorize('create', TreeType::class);

        $validated = $this->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tree_types', 'name')->where('category_id', $this->category_id),
            ],
            'scientific_name' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $validated['scientific_name'] = $this->scientific_name !== '' ? $this->scientific_name : null;
        $validated['description'] = $this->description !== '' ? $this->description : null;

        TreeType::create($validated);

        Flux::toast(variant: 'success', text: __('messages.created', ['item' => __('tree type')]));

        $this->redirectRoute('tree-types.index', navigate: true);
    }

    /**
     * Render the form.
     */
    public function render()
    {
        return $this->view([
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }
}; ?>

<section class="w-full">
    <x-page-header :title="__('Create Tree Type')" :description="__('Add a new type of tree to your orchard.')" />

    <form wire:submit="save" class="max-w-3xl space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @include('pages.tree-types.partials.form')
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:button type="submit" variant="primary">
                {{ __('Create tree type') }}
            </flux:button>

            <flux:button :href="route('tree-types.index')" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</section>
