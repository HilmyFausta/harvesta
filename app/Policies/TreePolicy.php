<?php

namespace App\Policies;

use App\Models\Tree;
use App\Models\User;

/**
 * Staff can create, view, and edit trees; only admins can delete (PRD F-05).
 */
class TreePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Tree $tree): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Tree $tree): bool
    {
        return true;
    }

    public function delete(User $user, Tree $tree): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Tree $tree): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Tree $tree): bool
    {
        return $user->isAdmin();
    }
}
