<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Format;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Users')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $role = '';

    public string $sort = 'name';

    public string $direction = 'asc';

    /**
     * Authorize and initialize the page.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    /**
     * Reset pagination when filters change.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'role'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Sort by a whitelisted column.
     */
    public function sortBy(string $column): void
    {
        if (! in_array($column, ['name', 'email', 'created_at'], true)) {
            return;
        }

        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = 'asc';
        }

        $this->resetPage();
    }

    /**
     * Clear every filter.
     */
    public function resetFilters(): void
    {
        $this->reset(['search', 'role']);
        $this->resetPage();
    }

    /**
     * Delete a user account.
     */
    public function delete(int $userId): void
    {
        $user = User::findOrFail($userId);

        Gate::authorize('delete', $user);

        $isSelf = Auth::id() === $user->id;

        $user->delete();

        Flux::toast(variant: 'success', text: __('messages.deleted', ['item' => __('user')]));

        if ($isSelf) {
            Auth::logout();
            Session::invalidate();
            Session::regenerateToken();

            $this->redirectRoute('login', navigate: true);

            return;
        }

        $this->resetPage();
    }

    /**
     * Render the user list.
     */
    public function render()
    {
        $sort = in_array($this->sort, ['name', 'email', 'created_at'], true) ? $this->sort : 'name';
        $direction = $this->direction === 'asc' ? 'asc' : 'desc';

        $users = User::query()
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('name', 'ilike', '%'.$this->search.'%')
                        ->orWhere('email', 'ilike', '%'.$this->search.'%');
                });
            })
            ->when($this->role !== '', fn ($query) => $query->where('role', $this->role))
            ->orderBy($sort, $direction)
            ->paginate(15);

        return $this->view([
            'users' => $users,
            'roles' => UserRole::options(),
            'adminCount' => User::query()->admins()->count(),
        ]);
    }
}; ?>

<section class="w-full">
    <x-page-header :title="__('Users')" :description="__('Manage who can sign in and what they can access.')">
        <flux:button :href="route('users.create')" variant="primary" icon="plus" wire:navigate>
            {{ __('Add user') }}
        </flux:button>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" icon="magnifying-glass" placeholder="{{ __('Search name or email...') }}" />

        <flux:select wire:model.live="role" :label="__('Role')">
            <flux:select.option value="">{{ __('All roles') }}</flux:select.option>
            @foreach ($roles as $value => $label)
                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <flux:text>{{ trans_choice(':count user|:count users', $users->total()) }}</flux:text>

        <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="arrow-path">
            {{ __('Reset filters') }}
        </flux:button>
    </div>

    @if ($users->isEmpty())
        <x-empty-state icon="users" :title="__('No users found.')" :description="__('Adjust your filters or add a new user.')">
            <flux:button :href="route('users.create')" variant="primary" icon="plus" wire:navigate>
                {{ __('Add user') }}
            </flux:button>
        </x-empty-state>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:table :paginate="$users">
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sort === 'name'" :direction="$sort === 'name' ? $direction : null" wire:click="sortBy('name')">
                        {{ __('Name') }}
                    </flux:table.column>
                    <flux:table.column sortable :sorted="$sort === 'email'" :direction="$sort === 'email' ? $direction : null" wire:click="sortBy('email')">
                        {{ __('Email') }}
                    </flux:table.column>
                    <flux:table.column>{{ __('Role') }}</flux:table.column>
                    <flux:table.column sortable :sorted="$sort === 'created_at'" :direction="$sort === 'created_at' ? $direction : null" wire:click="sortBy('created_at')">
                        {{ __('Created') }}
                    </flux:table.column>
                    <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($users as $user)
                        <flux:table.row :key="$user->id">
                            <flux:table.cell variant="strong">{{ $user->name }}</flux:table.cell>
                            <flux:table.cell>{{ $user->email }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$user->isAdmin() ? 'violet' : 'zinc'">{{ $user->role->label() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ Format::date($user->created_at) }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-1">
                                    <flux:button :href="route('users.edit', $user)" variant="ghost" size="sm" icon="pencil-square" :aria-label="__('Edit user')" wire:navigate />
                                    <flux:button wire:click="delete({{ $user->id }})" wire:confirm="{{ __('messages.confirm_delete', ['item' => __('user')]) }}" variant="ghost" size="sm" icon="trash" :aria-label="__('Delete user')" :disabled="$user->isAdmin() && $adminCount === 1" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @endif
</section>
