<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

/**
 * Finance is admin-only (PRD F-09).
 */
class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Sale $sale): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Sale $sale): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Sale $sale): bool
    {
        return $user->isAdmin();
    }
}
