<?php

namespace App\Actions\Recipe;

use App\Models\Recipe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateRecipeAction
{
    /**
     * 下書きを $publish = true で保存したときに公開する。公開済みを下書きに戻すことはしない。
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Recipe $recipe, array $data, bool $publish): Recipe
    {
        return DB::transaction(function () use ($recipe, $data, $publish) {
            $recipeImagePath = $recipe->recipe_image_path;

            if (isset($data['recipe_image'])) {
                if ($recipeImagePath !== null) {
                    Storage::disk('public')->delete($recipeImagePath);
                }

                $recipeImagePath = $data['recipe_image']->store('recipes', 'public');
            }

            $recipe->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'recipe_image_path' => $recipeImagePath,
                'cooking_time' => $data['cooking_time'] ?? null,
                'servings' => $data['servings'] ?? null,
                'published_at' => $recipe->published_at ?? ($publish ? now() : null),
            ]);

            // 残す画像はファイルをそのまま使い、新しい手順に付け直す。残さない画像だけファイルを消す
            $recipe->load('steps.stepImages');
            $currentImages = $recipe->steps->flatMap(fn ($step) => $step->stepImages)->keyBy('id');
            $keptIds = collect($data['steps'] ?? [])->flatMap(fn (array $step) => $step['kept_image_ids'] ?? [])->all();
            $currentImages->except($keptIds)->each(
                fn ($stepImage) => Storage::disk('public')->delete($stepImage->step_image_path)
            );

            $recipe->ingredients()->delete();
            $recipe->steps()->delete();

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

                $keptPaths = collect($step['kept_image_ids'] ?? [])
                    ->map(fn ($id) => $currentImages[$id]->step_image_path);
                $newPaths = collect($step['images'] ?? [])
                    ->map(fn ($image) => $image->store('steps', 'public'));

                $createdStep->stepImages()->createMany(
                    $keptPaths->concat($newPaths)->map(fn (string $path) => ['step_image_path' => $path])->all()
                );
            }

            return $recipe;
        });
    }
}
