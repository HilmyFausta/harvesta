<?php

use App\Enums\ActivityType;
use App\Models\CareLog;
use App\Models\Tree;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Care Log')] class extends Component {
    public string $tree_id = '';

    public string $activity_type = '';

    public string $performed_on = '';

    public string $next_due_on = '';

    public string $notes = '';

    /**
     * Authorize and initialize the form.
     */
    public function mount(): void
    {
        Gate::authorize('create', CareLog::class);

        // Pre-fill tree_id from query string if provided
        $this->tree_id = request()->query('tree_id', '');
        $this->performed_on = now()->format('Y-m-d');
    }

    /**
     * Store the care log.
     */
    public function save(): void
    {
        Gate::authorize('create', CareLog::class);

        $validated = $this->validate([
            'tree_id' => ['required', 'integer', 'exists:trees,id'],
            'activity_type' => ['required', Rule::enum(ActivityType::class)],
            'performed_on' => ['required', 'date', 'before_or_equal:today'],
            'next_due_on' => ['nullable', 'date', 'after_or_equal:performed_on'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        // Check if tree accepts records (not dead)
        $tree = Tree::findOrFail($validated['tree_id']);
        if (! $tree->acceptsRecords()) {
            $this->addError('tree_id', 'This tree cannot receive new care logs because its status is "'.$tree->status->label().'".');

            return;
        }

        $validated['next_due_on'] = $this->next_due_on !== '' ? $this->next_due_on : null;
        $validated['notes'] = $this->notes !== '' ? $this->notes : null;

        CareLog::create($validated);

        Flux::toast(variant: 'success', text: __('messages.created', ['item' => __('care log')]));

        $this->redirectRoute('care-logs.index', navigate: true);
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
            'activityTypes' => ActivityType::options(),
        ]);
    }
}; ?>

<section class="w-full">
    <x-page-header :title="__('Create Care Log')" :description="__('Record a new tree care activity.')" />

    <form wire:submit="save" class="max-w-3xl space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @include('pages.care-logs.partials.form')
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:button type="submit" variant="primary">
                {{ __('Create care log') }}
            </flux:button>

            <flux:button :href="route('care-logs.index')" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</section>
