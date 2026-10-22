<?php

namespace App\Policies;

use App\Models\CareLog;
use App\Models\User;

/**
 * Staff can create records and edit only their own; admins can do everything
 * (PRD F-06).
 */
class CareLogPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CareLog $careLog): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CareLog $careLog): bool
    {
        return $user->isAdmin() || $careLog->user_id === $user->id;
    }

    public function delete(User $user, CareLog $careLog): bool
    {
        return $user->isAdmin();
    }
}
