<?php

use App\Models\Block;
use App\Support\Format;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Block Details')] class extends Component {
    use WithPagination;

    public Block $block;

    /**
     * Authorize and initialize the page.
     */
    public function mount(): void
    {
        Gate::authorize('view', $this->block);
    }

    /**
     * Render the block detail page.
     */
    public function render()
    {
        // Get trees in this block with pagination
        $trees = $this->block->trees()
            ->with(['treeType.category', 'creator'])
            ->orderBy('code')
            ->paginate(15);

        // Get summary statistics
        $treeCount = $this->block->trees()->count();
        $harvestTotal = $this->block->harvests()->sum('quantity');

        return $this->view([
            'trees' => $trees,
            'treeCount' => $treeCount,
            'harvestTotal' => $harvestTotal,
        ]);
    }
}; ?>

<section class="w-full">
    <x-page-header :title="$block->name" :description="__('Block details and trees')">
        @can('update', $block)
            <flux:button :href="route('blocks.edit', $block)" variant="primary" icon="pencil-square" wire:navigate>
                {{ __('Edit block') }}
            </flux:button>
        @endcan
    </x-page-header>

    <!-- Block Information -->
    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <h3 class="mb-4 text-lg font-semibold text-zinc-800 dark:text-white">{{ __('Block Information') }}</h3>
        
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</dt>
                <dd class="mt-1 text-base font-semibold text-zinc-800 dark:text-white">{{ $block->name }}</dd>
            </div>

            @if ($block->area_sqm)
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Area') }}</dt>
                    <dd class="mt-1 text-base font-semibold text-zinc-800 dark:text-white">{{ Format::number($block->area_sqm, 2) }} m²</dd>
                </div>
            @endif

            @if ($block->location_note)
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Location') }}</dt>
                    <dd class="mt-1 text-base text-zinc-800 dark:text-white">{{ $block->location_note }}</dd>
                </div>
            @endif

            @if ($block->description)
                <div class="sm:col-span-2 lg:col-span-4">
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Description') }}</dt>
                    <dd class="mt-1 text-base text-zinc-600 dark:text-zinc-400">{{ $block->description }}</dd>
                </div>
            @endif

            <div class="sm:col-span-2 lg:col-span-4">
                <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Created') }}</dt>
                <dd class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ Format::date($block->created_at) }}</dd>
            </div>
        </dl>
    </div>

    <!-- Summary Statistics -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-lime-100 p-3 dark:bg-lime-900/20">
                    <svg class="h-6 w-6 text-lime-600 dark:text-lime-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Total Trees') }}</p>
                    <p class="text-2xl font-bold text-zinc-800 dark:text-white">{{ $treeCount }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-green-100 p-3 dark:bg-green-900/20">
                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Total Harvests') }}</p>
                    <p class="text-2xl font-bold text-zinc-800 dark:text-white">{{ Format::number($harvestTotal) }}</p>
                </div>
            </div>
        </div>

        @if ($block->area_sqm && $treeCount > 0)
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-blue-100 p-3 dark:bg-blue-900/20">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Density') }}</p>
                        <p class="text-2xl font-bold text-zinc-800 dark:text-white">{{ Format::number($treeCount / $block->area_sqm, 3) }}/m²</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Trees in This Block -->
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-zinc-800 dark:text-white">{{ __('Trees in This Block') }}</h3>
        </div>

        @if ($trees->isEmpty())
            <div class="py-8 text-center text-zinc-500">
                <p>{{ __('No trees in this block yet.') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <flux:table :paginate="$trees">
                    <flux:table.columns>
                        <flux:table.column>{{ __('Code') }}</flux:table.column>
                        <flux:table.column>{{ __('Tree Type') }}</flux:table.column>
                        <flux:table.column>{{ __('Category') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column>{{ __('Planted') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($trees as $tree)
                            <flux:table.row :key="$tree->id">
                                <flux:table.cell variant="strong">{{ $tree->code }}</flux:table.cell>
                                <flux:table.cell>{{ $tree->treeType->name }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" color="lime">{{ $tree->treeType->category->name }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <x-tree-status-badge :status="$tree->status" />
                                </flux:table.cell>
                                <flux:table.cell>{{ Format::date($tree->planted_on) }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </div>

    <!-- Back Button -->
    <div class="mt-6">
        <flux:button :href="route('blocks.index')" variant="ghost" icon="arrow-left" wire:navigate>
            {{ __('Back to blocks') }}
        </flux:button>
    </div>
</section>
