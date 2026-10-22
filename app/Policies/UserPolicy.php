<?php

namespace App\Policies;

use App\Models\User;

/**
 * User management is admin-only, and the last admin is protected (PRD F-11).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Changing the role is blocked when it would leave no admin.
     */
    public function changeRole(User $user, User $model): bool
    {
        return $user->isAdmin() && ! $model->isLastAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin() && ! $model->isLastAdmin();
    }
}
