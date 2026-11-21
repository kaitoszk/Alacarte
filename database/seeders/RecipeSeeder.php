<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Step;
use App\Models\StepImage;
use App\Models\User;
use Database\Factories\Support\PlaceholderImage;
use Database\Seeders\Support\SeedPhoto;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = User::pluck('id');

        Recipe::factory()
            ->count(40)
            ->state(fn () => [
                'user_id' => $userIds->random(),
                // レシピ名に合う料理写真があれば使い、なければ単色のプレースホルダー
                'recipe_image_path' => fn (array $attributes) => SeedPhoto::storeForRecipe($attributes['title'])
                    ?? PlaceholderImage::store('recipes', 'Recipe'),
            ])
            ->create()
            ->each(function (Recipe $recipe) {
                foreach (range(1, fake()->numberBetween(3, 8)) as $order) {
                    Ingredient::factory()->create([
                        'recipe_id' => $recipe->id,
                        'order_number' => $order,
                    ]);
                }

                foreach (range(1, fake()->numberBetween(3, 6)) as $order) {
                    $step = Step::factory()->create([
                        'recipe_id' => $recipe->id,
                        'order_number' => $order,
                    ]);

                    if (fake()->boolean(40)) {
                        StepImage::factory()
                            ->count(fake()->numberBetween(1, 2))
                            ->state(fn () => [
                                'step_image_path' => SeedPhoto::storeRandomStep() ?? PlaceholderImage::store('steps', 'Step'),
                            ])
                            ->create(['step_id' => $step->id]);
                    }
                }
            });
    }
}
