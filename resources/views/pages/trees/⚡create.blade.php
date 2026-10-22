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

new #[Title('Add Tree')] class extends Component {
    use WithFileUploads;

    public TreeForm $form;

    /**
     * Authorize and set sensible defaults (block can be preselected with ?block_id=).
     */
    public function mount(): void
    {
        Gate::authorize('create', Tree::class);

        $this->form->status = TreeStatus::Seedling->value;
        $this->form->planted_on = today()->format('Y-m-d');
        $this->form->block_id = (string) request()->integer('block_id') ?: '';
    }

    /**
     * Store the tree.
     */
    public function save(): void
    {
        Gate::authorize('create', Tree::class);

        $tree = $this->form->save();

        Flux::toast(variant: 'success', text: __('messages.created', ['item' => __('tree')]));

        $this->redirectRoute('trees.show', $tree, navigate: true);
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
    <x-page-header :title="__('Add Tree')" :description="__('Register a new tree in the orchard.')" />

    <form wire:submit="save" class="max-w-3xl space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @include('pages.trees.partials.form')
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save,form.photo">
                {{ __('Add tree') }}
            </flux:button>

            <flux:button :href="route('trees.index')" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</section>
