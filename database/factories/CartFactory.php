<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
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
            'name' => fake()->word(),
            'quantity' => fake()->randomElement(['1個', '100g', '大さじ1', '少々']),
            'order_number' => 1,
            'is_checked' => false,
        ];
    }
}
