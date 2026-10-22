<?php

use App\Models\Tree;
use App\Support\Format;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Tree Details')] class extends Component {
    public Tree $tree;

    /**
     * Authorize the page.
     */
    public function mount(): void
    {
        Gate::authorize('view', $this->tree);
    }

    /**
     * Soft delete the tree (admin only).
     */
    public function delete(): void
    {
        Gate::authorize('delete', $this->tree);

        $this->tree->delete();

        Flux::toast(variant: 'success', text: __('messages.deleted', ['item' => __('tree')]));

        $this->redirectRoute('trees.index', navigate: true);
    }

    /**
     * Render the tree with its care timeline and harvests.
     */
    public function render()
    {
        $this->tree->loadMissing(['treeType.category', 'block', 'creator']);

        return $this->view([
            'careLogs' => $this->tree->careLogs()->with('user')->latest('performed_on')->latest('id')->get(),
            'harvests' => $this->tree->harvests()->latest('harvested_on')->latest('id')->get(),
        ]);
    }
}; ?>

<section class="w-full">
    <x-page-header :title="__('Tree :code', ['code' => $tree->code])" :description="$tree->treeType->name.' · '.$tree->block->name">
        <flux:button :href="route('trees.index')" variant="ghost" icon="arrow-left" wire:navigate>
            {{ __('Back to trees') }}
        </flux:button>
        @can('update', $tree)
            <flux:button :href="route('trees.edit', $tree)" variant="primary" icon="pencil-square" wire:navigate>
                {{ __('Edit tree') }}
            </flux:button>
        @endcan
        @can('delete', $tree)
            <flux:button wire:click="delete" wire:confirm="{{ __('messages.confirm_delete', ['item' => __('tree')]) }}" variant="danger" icon="trash">
                {{ __('Delete') }}
            </flux:button>
        @endcan
    </x-page-header>

    @unless ($tree->acceptsRecords())
        <flux:callout variant="danger" icon="exclamation-triangle" class="mb-6" :heading="__('This tree is marked as dead.')" :text="__('messages.dead_tree')" />
    @endunless

    <div class="mb-6 grid gap-6 lg:grid-cols-3">
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            @if ($tree->photo_url)
                <a href="{{ $tree->photo_url }}" target="_blank" rel="noopener">
                    <img src="{{ $tree->photo_url }}" alt="{{ __('Photo of tree :code', ['code' => $tree->code]) }}" class="aspect-[4/3] w-full object-cover">
                </a>
            @else
                <div class="flex aspect-[4/3] w-full flex-col items-center justify-center gap-2 bg-leaf/5 text-leaf">
                    <flux:icon.tree-deciduous class="size-12" />
                    <flux:text>{{ __('No photo yet.') }}</flux:text>
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between gap-3">
                <flux:heading size="lg">{{ __('Tree Information') }}</flux:heading>
                <x-tree-status-badge :status="$tree->status" />
            </div>

            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Tree type') }}</dt>
                    <dd class="font-medium text-zinc-800 dark:text-white">
                        {{ $tree->treeType->name }}
                        @if ($tree->treeType->scientific_name)
                            <span class="font-normal italic text-zinc-500">({{ $tree->treeType->scientific_name }})</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Category') }}</dt>
                    <dd class="font-medium text-zinc-800 dark:text-white">{{ $tree->treeType->category->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Block') }}</dt>
                    <dd class="font-medium text-zinc-800 dark:text-white">
                        <a href="{{ route('blocks.show', $tree->block) }}" class="hover:text-leaf" wire:navigate>{{ $tree->block->name }}</a>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Planted on') }}</dt>
                    <dd class="font-medium text-zinc-800 dark:text-white">
                        {{ Format::date($tree->planted_on) }}
                        <span class="font-normal text-zinc-500">({{ $tree->planted_on->diffForHumans(['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }})</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Height') }}</dt>
                    <dd class="font-medium text-zinc-800 dark:text-white">{{ $tree->height_cm !== null ? Format::number($tree->height_cm).' cm' : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Added by') }}</dt>
                    <dd class="font-medium text-zinc-800 dark:text-white">{{ $tree->creator?->name ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Notes') }}</dt>
                    <dd class="whitespace-pre-line text-zinc-800 dark:text-white">{{ $tree->notes ?: '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        @include('pages.trees.partials.care-logs-timeline')
        @include('pages.trees.partials.harvests-list')
    </div>
</section>
