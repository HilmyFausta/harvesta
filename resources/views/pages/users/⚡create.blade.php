<?php

use App\Enums\UserRole;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create User')] class extends Component {
    public string $name = '';

    public string $email = '';

    public string $role = 'staff';

    public string $password = '';

    /**
     * Authorize the form.
     */
    public function mount(): void
    {
        Gate::authorize('create', User::class);
    }

    /**
     * Store the user.
     */
    public function save(): void
    {
        Gate::authorize('create', User::class);

        $this->name = trim($this->name);
        $this->email = Str::lower(trim($this->email));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => ['required', 'string', Password::defaults()],
        ]);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = $validated['password'];
        $user->role = UserRole::from($validated['role']);
        $user->save();

        Flux::toast(variant: 'success', text: __('messages.created', ['item' => __('user')]));

        $this->redirectRoute('users.index', navigate: true);
    }

    /**
     * Render the form.
     */
    public function render()
    {
        return $this->view([
            'roles' => UserRole::options(),
        ]);
    }
}; ?>

<section class="w-full">
    <x-page-header :title="__('Create User')" :description="__('Add a new person to the farm team.')" />

    <form wire:submit="save" class="max-w-3xl space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @include('pages.users.partials.form')
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:button type="submit" variant="primary">
                {{ __('Create user') }}
            </flux:button>

            <flux:button :href="route('users.index')" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</section>
