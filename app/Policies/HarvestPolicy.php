<?php

namespace App\Policies;

use App\Models\Harvest;
use App\Models\User;

/**
 * Staff can create records and edit only their own; admins can do everything.
 * Viewer can only view (PRD F-07).
 */
class HarvestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Harvest $harvest): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return ! $user->isViewer();
    }

    public function update(User $user, Harvest $harvest): bool
    {
        if ($user->isViewer()) {
            return false;
        }

        return $user->isAdmin() || $harvest->user_id === $user->id;
    }

    public function delete(User $user, Harvest $harvest): bool
    {
        return $user->isAdmin();
    }
}
