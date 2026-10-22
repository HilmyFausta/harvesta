<?php

use App\Models\Category;
use App\Models\TreeType;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Tree Type')] class extends Component {
    public TreeType $treeType;

    public string $category_id = '';

    public string $name = '';

    public string $scientific_name = '';

    public string $description = '';

    /**
     * Authorize and initialize the form.
     */
    public function mount(): void
    {
        Gate::authorize('update', $this->treeType);

        $this->category_id = (string) $this->treeType->category_id;
        $this->name = $this->treeType->name;
        $this->scientific_name = $this->treeType->scientific_name ?? '';
        $this->description = $this->treeType->description ?? '';
    }

    /**
     * Update the tree type.
     */
    public function save(): void
    {
        Gate::authorize('update', $this->treeType);

        $validated = $this->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tree_types', 'name')
                    ->where('category_id', $this->category_id)
                    ->ignore($this->treeType->id),
            ],
            'scientific_name' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $validated['scientific_name'] = $this->scientific_name !== '' ? $this->scientific_name : null;
        $validated['description'] = $this->description !== '' ? $this->description : null;

        $this->treeType->update($validated);

        Flux::toast(variant: 'success', text: __('messages.updated', ['item' => __('tree type')]));

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
    <x-page-header :title="__('Edit Tree Type')" :description="__('Update the tree type details.')" />

    <form wire:submit="save" class="max-w-3xl space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @include('pages.tree-types.partials.form')
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:button type="submit" variant="primary">
                {{ __('Update tree type') }}
            </flux:button>

            <flux:button :href="route('tree-types.index')" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</section>
