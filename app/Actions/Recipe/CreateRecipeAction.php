<?php

namespace App\Actions\Recipe;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateRecipeAction
{
    /**
     * 下書き（$publish = false）はタイトル以外が未入力の場合がある。
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data, bool $publish): Recipe
    {
        return DB::transaction(function () use ($user, $data, $publish) {
            $recipe = Recipe::create([
                'user_id' => $user->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'recipe_image_path' => isset($data['recipe_image']) ? $data['recipe_image']->store('recipes', 'public') : null,
                'cooking_time' => $data['cooking_time'] ?? null,
                'servings' => $data['servings'] ?? null,
                'published_at' => $publish ? now() : null,
            ]);

            $recipe->ingredients()->createMany(
                collect($data['ingredients'] ?? [])->values()->map(fn (array $ingredient, int $index) => [
                    'order_number' => $index + 1,
                    // 下書きの途中まで入力した行は空欄を '' で保存する
                    'name' => $ingredient['name'] ?? '',
                    'quantity' => $ingredient['quantity'] ?? '',
                ])->all()
            );

            foreach (collect($data['steps'] ?? [])->values() as $index => $step) {
                $createdStep = $recipe->steps()->create([
                    'order_number' => $index + 1,
                    'description' => $step['description'] ?? '',
                ]);

                $createdStep->stepImages()->createMany(
                    collect($step['images'] ?? [])->map(fn ($image) => [
                        'step_image_path' => $image->store('steps', 'public'),
                    ])->all()
                );
            }

            return $recipe;
        });
    }
}
