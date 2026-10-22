<?php

namespace App\Policies;

use App\Models\TreeType;
use App\Models\User;

/**
 * Master data: everyone can view, only admins can change (PRD F-03).
 */
class TreeTypePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TreeType $treeType): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, TreeType $treeType): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, TreeType $treeType): bool
    {
        return $user->isAdmin();
    }
}
