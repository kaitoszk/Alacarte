<?php

namespace App\Policies;

use App\Models\User;

class CartPolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }
}
