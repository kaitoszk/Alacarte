<?php

namespace App\Policies;

use App\Models\MealPlan;
use App\Models\User;

class MealPlanPolicy
{
    /**
     * 献立から外せるのは本人だけ。
     */
    public function delete(User $user, MealPlan $mealPlan): bool
    {
        return $user->id === $mealPlan->user_id;
    }
}
