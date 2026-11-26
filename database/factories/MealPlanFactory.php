<?php

namespace Database\Factories;

use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealPlan>
 */
class MealPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'recipe_id' => Recipe::factory(),
            'date' => MealPlan::today()->toDateString(),
        ];
    }
}
