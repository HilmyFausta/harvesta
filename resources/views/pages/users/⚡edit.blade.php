<?php

use App\Enums\UserRole;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit User')] class extends Component {
    public User $user;

    public string $name = '';

    public string $email = '';

    public string $role = 'staff';

    public string $password = '';

    /**
     * Authorize and fill the form.
     */
    public function mount(User $user): void
    {
        Gate::authorize('update', $user);

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role->value;
    }

    /**
     * Update the user.
     */
    public function save(): void
    {
        Gate::authorize('update', $this->user);

        $this->name = trim($this->name);
        $this->email = Str::lower(trim($this->email));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user->id)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $role = UserRole::from($validated['role']);

        if ($role !== $this->user->role) {
            Gate::authorize('changeRole', $this->user);

            $this->user->role = $role;
        }

        $this->user->name = $validated['name'];
        $this->user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $this->user->password = $validated['password'];
        }

        $this->user->save();

        Flux::toast(variant: 'success', text: __('messages.updated', ['item' => __('user')]));

        if (Auth::id() === $this->user->id && ! $this->user->isAdmin()) {
            $this->redirectRoute('dashboard', navigate: true);

            return;
        }

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
    <x-page-header :title="__('Edit User')" :description="__('Update this person\'s account details and access.')" />

    <form wire:submit="save" class="max-w-3xl space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @include('pages.users.partials.form', [
                'passwordRequired' => false,
                'roleDisabled' => $user->isLastAdmin(),
            ])
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:button type="submit" variant="primary">
                {{ __('Save changes') }}
            </flux:button>

            <flux:button :href="route('users.index')" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</section>
