<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Step;
use App\Models\StepImage;
use App\Models\User;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = User::pluck('id');

        // テストユーザーのフォロー中フィードが空にならないよう、
        // 最初の1件はフォロー先の誰かのレシピにする（E2E がこれを前提にしている）
        $followeeId = User::where('handle', 'testuser')->firstOrFail()
            ->followings()->inRandomOrder()->value('users.id');

        Recipe::factory()
            ->count(40)
            ->sequence(fn ($sequence) => [
                'user_id' => $sequence->index === 0 ? $followeeId : $userIds->random(),
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
                            ->create(['step_id' => $step->id]);
                    }
                }
            });
    }
}
