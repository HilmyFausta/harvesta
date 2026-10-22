<?php

use App\Enums\TreeStatus;
use App\Livewire\Forms\TreeForm;
use App\Models\Block;
use App\Models\Tree;
use App\Models\TreeType;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Edit Tree')] class extends Component {
    use WithFileUploads;

    public Tree $tree;

    public TreeForm $form;

    /**
     * Authorize and fill the form.
     */
    public function mount(): void
    {
        Gate::authorize('update', $this->tree);

        $this->form->setTree($this->tree);
    }

    /**
     * Update the tree.
     */
    public function save(): void
    {
        Gate::authorize('update', $this->tree);

        $this->form->save();

        Flux::toast(variant: 'success', text: __('messages.updated', ['item' => __('tree')]));

        $this->redirectRoute('trees.show', $this->tree, navigate: true);
    }

    /**
     * Render the form.
     */
    public function render()
    {
        return $this->view([
            'treeTypes' => TreeType::with('category')->orderBy('name')->get(),
            'blocks' => Block::orderBy('name')->get(['id', 'name']),
            'statuses' => TreeStatus::options(),
        ]);
    }
}; ?>

<section class="w-full">
    <x-page-header :title="__('Edit Tree :code', ['code' => $tree->code])" :description="__('Update the tree details and photo.')" />

    <form wire:submit="save" class="max-w-3xl space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @include('pages.trees.partials.form')
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save,form.photo">
                {{ __('Update tree') }}
            </flux:button>

            <flux:button :href="route('trees.show', $tree)" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</section>
