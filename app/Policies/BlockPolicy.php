<?php

namespace App\Policies;

use App\Models\Block;
use App\Models\User;

/**
 * Master data: everyone can view, only admins can change (PRD F-04).
 */
class BlockPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Block $block): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Block $block): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Block $block): bool
    {
        return $user->isAdmin();
    }
}
