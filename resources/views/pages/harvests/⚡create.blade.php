<?php

use App\Enums\HarvestGrade;
use App\Enums\HarvestUnit;
use App\Models\Harvest;
use App\Models\Tree;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Harvest')] class extends Component {
    public string $tree_id = '';

    public string $harvested_on = '';

    public string $quantity = '';

    public string $unit = '';

    public string $grade = '';

    public string $notes = '';

    /**
     * Authorize and initialize the form.
     */
    public function mount(): void
    {
        Gate::authorize('create', Harvest::class);

        // Pre-fill tree_id from query string if provided
        $this->tree_id = request()->query('tree_id', '');
        $this->harvested_on = now()->format('Y-m-d');
    }

    /**
     * Store the harvest.
     */
    public function save(): void
    {
        Gate::authorize('create', Harvest::class);

        $validated = $this->validate([
            'tree_id' => ['required', 'integer', 'exists:trees,id'],
            'harvested_on' => ['required', 'date', 'before_or_equal:today'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'unit' => ['required', Rule::enum(HarvestUnit::class)],
            'grade' => ['required', Rule::enum(HarvestGrade::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        // Check if tree accepts records (not dead)
        $tree = Tree::findOrFail($validated['tree_id']);
        if (! $tree->acceptsRecords()) {
            $this->addError('tree_id', 'This tree cannot receive new harvests because its status is "'.$tree->status->label().'".');

            return;
        }

        $validated['notes'] = $this->notes !== '' ? $this->notes : null;

        Harvest::create($validated);

        Flux::toast(variant: 'success', text: __('messages.created', ['item' => __('harvest')]));

        $this->redirectRoute('harvests.index', navigate: true);
    }

    /**
     * Render the form.
     */
    public function render()
    {
        return $this->view([
            'trees' => Tree::query()
                ->with(['block', 'treeType'])
                ->orderBy('code')
                ->get(),
            'units' => HarvestUnit::options(),
            'grades' => HarvestGrade::options(),
        ]);
    }
}; ?>

<section class="w-full">
    <x-page-header :title="__('Create Harvest')" :description="__('Record a new harvest yield.')" />

    <form wire:submit="save" class="max-w-3xl space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @include('pages.harvests.partials.form')
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:button type="submit" variant="primary">
                {{ __('Create harvest') }}
            </flux:button>

            <flux:button :href="route('harvests.index')" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</section>
