<?php

use App\Models\Category;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Category')] class extends Component {
    public Category $category;

    public string $name = '';

    public string $description = '';

    /**
     * Authorize and initialize the form.
     */
    public function mount(): void
    {
        Gate::authorize('update', $this->category);

        $this->name = $this->category->name;
        $this->description = $this->category->description ?? '';
    }

    /**
     * Update the category.
     */
    public function save(): void
    {
        Gate::authorize('update', $this->category);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($this->category->id)],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $validated['description'] = $this->description !== '' ? $this->description : null;

        $this->category->update($validated);

        Flux::toast(variant: 'success', text: __('messages.updated', ['item' => __('category')]));

        $this->redirectRoute('categories.index', navigate: true);
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
    <x-page-header :title="__('Edit Category')" :description="__('Update the category details.')" />

    <form wire:submit="save" class="max-w-3xl space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @include('pages.categories.partials.form')
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:button type="submit" variant="primary">
                {{ __('Update category') }}
            </flux:button>

            <flux:button :href="route('categories.index')" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</section>
